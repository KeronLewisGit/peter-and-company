@php
    $districts = (array) peterco('districts');
    $total = $lines->sum('cases');
@endphp
<x-layouts.site title="Order list" description="Send a trade order request to Acado St. Lucia. The trade desk confirms prices, availability and your delivery day.">
    <x-page-hero title="Your order list" lead="Check the cases, tell us where to deliver and send the request. The trade desk calls or messages you to confirm prices, availability and the delivery day. Nothing is charged online." code="O-01" label="Order request" photo="castries-container-ship" alt="A container ship moored at the port of Castries" :label-code="$lines->count().' lines'" :label-line="$total.' cases'" />

    <section class="wrap mt-12 md:mt-16">
        <x-form-errors />
        <form method="POST" action="{{ route('order.store') }}" class="grid gap-12 lg:grid-cols-[1.25fr_0.75fr] lg:items-start" x-data="leadForm" @submit="submit">
            @csrf
            <div class="min-w-0 space-y-12">
                <fieldset>
                    <legend class="fieldset-title mb-5"><span class="step">1</span>What you need</legend>
                    @if ($lines->isEmpty())
                        <div class="carton p-6 pt-7">
                            <p class="font-bold">Your list is empty.</p>
                            <p class="mt-1 text-ink-2">Add cases from the catalogue, or type what you need under "Other items" below.</p>
                            <a href="{{ route('catalogue') }}" class="btn btn-outline btn-sm mt-4">Open the catalogue</a>
                        </div>
                    @else
                        <div class="table-box">
                            <table class="manifest min-w-[30rem]">
                                <thead><tr><th scope="col">Line</th><th scope="col">Case</th><th scope="col" class="r">Cases</th><th scope="col"><span class="sr-only">Remove</span></th></tr></thead>
                                <tbody>
                                    @foreach ($lines as $line)
                                        @php($p = $line['product'])
                                        <tr>
                                            <td><span class="block text-sm text-ink-3">{{ $p->brand }}</span><span class="font-semibold">{{ $p->name }}</span></td>
                                            <td class="num whitespace-nowrap text-ink-2">{{ $p->pack_size ?? '—' }}</td>
                                            <td class="r"><label class="sr-only" for="q-{{ $p->id }}">Cases of {{ $p->name }}</label>
                                                <input id="q-{{ $p->id }}" name="cases[{{ $p->id }}]" type="number" inputmode="numeric" min="0" max="{{ \App\Support\OrderList::MAX_CASES }}" value="{{ old('cases.'.$p->id, $line['cases']) }}" class="input qty !min-h-11"></td>
                                            <td class="r"><button type="submit" formaction="{{ route('order.remove') }}" formnovalidate name="product_id" value="{{ $p->id }}" class="inline-flex h-11 w-11 items-center justify-center rounded text-ink-3 hover:bg-surface-2 hover:text-ink" aria-label="Remove {{ $p->name }}"><x-icon name="x" class="h-5 w-5" /></button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot><tr><td colspan="2" class="font-bold">Total</td><td class="r font-bold">{{ $total }} cases</td><td></td></tr></tfoot>
                            </table>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-3">
                            <button type="submit" formaction="{{ route('order.update') }}" formnovalidate class="btn btn-outline btn-sm">Update quantities</button>
                            <a href="{{ route('catalogue') }}" class="btn btn-outline btn-sm"><x-icon name="plus" class="h-4 w-4" />Add more lines</a>
                        </div>
                    @endif
                    <x-error for="cases" />
                    <div class="mt-8">
                        <x-field name="other_items" label="Other items" type="textarea" :optional="true" rows="4" maxlength="2000" hint="Anything not in the catalogue, one per line with the quantity, e.g. “Carib 24 × 275 ml cans, 10 cases”." />
                    </div>
                    <div class="mt-5">
                        <label class="choice"><input type="checkbox" name="pharmaceutical" value="1" @checked(old('pharmaceutical'))><span>This order includes pharmaceutical products <span class="block text-sm text-ink-3">Approved pharmacy and clinic accounts only. List the items above.</span></span></label>
                        <x-error for="pharmaceutical" />
                    </div>
                </fieldset>

                <x-contact-fields :step="2" title="Who is ordering" />

                <fieldset class="space-y-5">
                    <legend class="fieldset-title mb-5"><span class="step">3</span>Your business and delivery</legend>
                    <x-field name="account_number" label="Trade account number" :optional="true" maxlength="20" autocomplete="off" hint="Starts with PC-, e.g. PC-10004. No account yet? Leave it blank." class="uppercase" />
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field name="business_name" label="Business name" :required="true" maxlength="160" autocomplete="organization" />
                        <x-select name="trade_type" label="Type of business" :required="true" :options="\App\Enums\TradeType::options()" />
                    </div>
                    <div class="grid gap-5 sm:grid-cols-[1fr_2fr]">
                        <x-select name="district" label="District" :required="true" :options="$districts" />
                        <x-field name="delivery_address" label="Delivery address" :required="true" maxlength="500" autocomplete="street-address" />
                    </div>
                    <div>
                        <p class="label" id="day-label">Preferred delivery day <span class="opt">(optional)</span></p>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4" role="radiogroup" aria-labelledby="day-label">
                            <label class="choice"><input type="radio" name="delivery_date" value="" @checked(old('delivery_date', '') === '')>No preference</label>
                            @foreach (array_slice($days, 0, 7) as $d)
                                <label class="choice"><input type="radio" name="delivery_date" value="{{ $d->toDateString() }}" @checked(old('delivery_date') === $d->toDateString())><span class="num">{{ $d->format('D j M') }}</span></label>
                            @endforeach
                        </div>
                        <span class="hint">Weekdays only. The trade desk confirms the day for your area.</span>
                        <x-error for="delivery_date" />
                    </div>
                    <x-field name="message" label="Notes for the trade desk" type="textarea" :optional="true" rows="3" maxlength="1500" hint="Receiving hours, a gate code, who to ask for." />
                </fieldset>
            </div>

            <aside class="lg:sticky lg:top-28">
                <div class="pallet-label !shadow-none" aria-hidden="true">
                    <div class="row"><span>Order request</span><span>Cul De Sac · LC</span></div>
                    <p class="big num">{{ $lines->count() }} lines · {{ $total }} cs</p>
                    <div class="barcode"></div>
                </div>
                <div class="carton mt-5 p-6 pt-7">
                    <h2 class="text-lg font-bold">What happens next</h2>
                    <ol class="mt-3 list-decimal space-y-2 pl-5 text-[0.9375rem] text-ink-2">
                        <li>You get a reference number on screen and by email.</li>
                        <li>The trade desk checks stock and your account, and calls or messages you with prices and the delivery day.</li>
                        <li>Nothing ships until you confirm.</li>
                    </ol>
                    <div class="mt-6"><x-submit label="Send order request" /></div>
                    <p class="mt-4 text-sm text-ink-3">By sending, you agree we may use these details to handle your order. See the <a class="text-link" href="{{ route('privacy') }}">privacy notice</a>.</p>
                </div>
            </aside>
        </form>
    </section>
</x-layouts.site>
