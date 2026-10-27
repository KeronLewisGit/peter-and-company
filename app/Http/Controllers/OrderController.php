<?php

namespace App\Http\Controllers;

use App\Enums\AccountStatus;
use App\Enums\LeadType;
use App\Enums\TradeType;
use App\Models\Product;
use App\Models\TradeAccount;
use App\Support\Delivery;
use App\Support\LeadIntake;
use App\Support\OrderList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\View\View;

/**
 * Trade order requests. Anyone can build a list from the catalogue and send it; quoting an
 * approved trade account number links the request to that account. Pharmaceutical items
 * are only accepted from an active pharmacy or clinic account with a licence on file.
 * Nothing here is a sale: the trade desk confirms price, availability and delivery by phone.
 */
class OrderController extends Controller
{
    public function show(): View
    {
        return view('pages.order', [
            'lines' => OrderList::lines(),
            'days' => Delivery::days(),
        ]);
    }

    /** "Add to order list" on a catalogue card. Works as a plain form post. */
    public function add(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product' => ['required', 'string', 'max:140'],
            'cases' => ['nullable', 'integer', 'min:1', 'max:'.OrderList::MAX_CASES],
        ]);
        $product = Product::listed()->where('slug', $data['product'])->firstOrFail();
        if (! array_key_exists($product->id, OrderList::items()) && OrderList::count() >= OrderList::MAX_LINES) {
            return back()->with('list_status', 'Your list is full ('.OrderList::MAX_LINES.' lines). Send it, then start another.');
        }
        OrderList::add($product, (int) ($data['cases'] ?? 1));

        return back()->with('list_status', "Added {$product->name} to your order list.")->with('list_added', $product->id)->withFragment('p-'.$product->slug);
    }

    /** The quantity boxes on /order, posted with the "Update list" button. */
    public function update(Request $request): RedirectResponse
    {
        $request->validate(['cases' => ['array'], 'cases.*' => ['nullable', 'integer', 'min:0', 'max:'.OrderList::MAX_CASES]]);
        $items = [];
        foreach ((array) $request->input('cases', []) as $id => $cases) {
            if (array_key_exists((int) $id, OrderList::items())) {
                $items[(int) $id] = (int) $cases;
            }
        }
        OrderList::replace($items);

        return to_route('order.show')->withInput($this->typedDetails($request))->with('list_status', 'Order list updated.');
    }

    public function remove(Request $request): RedirectResponse
    {
        OrderList::remove((int) $request->input('product_id'));

        return to_route('order.show')->withInput($this->typedDetails($request))->with('list_status', 'Removed from your order list.');
    }

    /**
     * "Update quantities" and "Remove" post the whole order form. Keep what the buyer has
     * already typed (name, address, notes …) so they don't have to type it again; the
     * quantities come from the list itself.
     *
     * @return array<string, mixed>
     */
    private function typedDetails(Request $request): array
    {
        return array_filter($request->except(['_token', 'cases', 'product_id', 'website']), fn ($v) => is_scalar($v));
    }

    public function store(Request $request): RedirectResponse
    {
        if (LeadIntake::isBot($request)) {
            return to_route('thanks');
        }

        $validator = validator($request->all(), [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-. ]{7,}$/'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'contact_pref' => ['nullable', Rule::in(['phone', 'whatsapp', 'email'])],
            'account_number' => ['nullable', 'string', 'max:20'],
            'business_name' => ['required', 'string', 'max:160'],
            'trade_type' => ['required', Rule::enum(TradeType::class)],
            'district' => ['required', Rule::in(array_keys((array) peterco('districts')))],
            'delivery_address' => ['required', 'string', 'max:500'],
            'delivery_date' => ['nullable', 'date_format:Y-m-d'],
            'cases' => ['nullable', 'array'],
            'cases.*' => ['nullable', 'integer', 'min:0', 'max:'.OrderList::MAX_CASES],
            'other_items' => ['nullable', 'string', 'max:2000'],
            'pharmaceutical' => ['nullable', 'boolean'],
            'message' => ['nullable', 'string', 'max:1500'],
        ], [
            'district.required' => 'Choose the district we deliver to.',
            'delivery_address.required' => 'Tell us where to deliver.',
        ], ['trade_type' => 'type of business', 'other_items' => 'other items']);

        $account = null;
        $validator->after(function (Validator $v) use ($request, &$account) {
            $typed = input_string($request->input('account_number'));
            if ($typed !== '') {
                $account = TradeAccount::findByNumber($typed);
                if (! $account) {
                    $v->errors()->add('account_number', 'We cannot find that account number. Check it, or leave it blank and we will match your order by phone.');
                } elseif ($account->status !== AccountStatus::Active) {
                    $v->errors()->add('account_number', 'That account is '.strtolower($account->status->label()).'. Please call credit control before ordering.');
                }
            }
            if ($request->boolean('pharmaceutical')) {
                if (! $account || ! $account->canOrderPharmaceuticals()) {
                    $v->errors()->add('pharmaceutical', 'Pharmaceutical products are supplied to licensed pharmacies and clinics only. Enter your approved pharmacy account number, or apply for a trade account first.');
                }
                if (input_string($request->input('other_items')) === '') {
                    $v->errors()->add('other_items', 'List the pharmaceutical products and quantities you need.');
                }
            }
            if ($this->lines($request) === [] && input_string($request->input('other_items')) === '') {
                $v->errors()->add('cases', 'Your order list is empty. Add products from the catalogue, or list what you need under "Other items".');
            }
            $date = input_string($request->input('delivery_date'));
            if ($date !== '' && ! Delivery::isRequestable($date)) {
                $v->errors()->add('delivery_date', 'Choose a working day in the next '.peterco('delivery.days_ahead').' days, or "No preference".');
            }
        });
        $data = $validator->validate();

        /** @var TradeAccount|null $account */
        $lines = $this->lines($request);
        LeadIntake::create(LeadType::Order, [
            'name' => trim($data['name']),
            'business_name' => trim($data['business_name']),
            'phone' => trim($data['phone']),
            'email' => strtolower(trim($data['email'])),
            'contact_pref' => $data['contact_pref'] ?? 'phone',
            'trade_account_id' => $account?->id,
            'delivery_date' => $data['delivery_date'] ?? null,
            'message' => $data['message'] ?? null,
            'details' => [
                'lines' => $lines,
                'other_items' => $data['other_items'] ?? null,
                'pharmaceutical' => (bool) ($data['pharmaceutical'] ?? false),
                'trade_type' => $data['trade_type'],
                'district' => $data['district'],
                'delivery_address' => trim($data['delivery_address']),
                'account_number' => $account?->number,
            ],
        ], [], $request);
        OrderList::clear();

        return to_route('thanks');
    }

    /**
     * Lines from the quantity boxes, for products that are still listed.
     *
     * @return list<array{product_id: int, name: string, brand: string, pack: string|null, sku: string|null, cases: int}>
     */
    private function lines(Request $request): array
    {
        $cases = collect((array) $request->input('cases', []))
            ->filter(fn ($c, $id) => is_numeric($id) && is_numeric($c) && (int) $c > 0)
            ->map(fn ($c) => min(OrderList::MAX_CASES, (int) $c))
            ->take(OrderList::MAX_LINES);
        if ($cases->isEmpty()) {
            return [];
        }
        $products = Product::listed()->whereIn('id', $cases->keys()->map(fn ($id) => (int) $id))->get()->keyBy('id');

        return $cases->filter(fn ($c, $id) => $products->has((int) $id))
            ->map(fn (int $c, $id) => [
                'product_id' => (int) $id,
                'name' => $products[(int) $id]->name,
                'brand' => $products[(int) $id]->brand,
                'pack' => $products[(int) $id]->pack_size,
                'sku' => $products[(int) $id]->sku,
                'cases' => $c,
            ])->values()->all();
    }
}
