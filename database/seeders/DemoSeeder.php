<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Enums\PaymentTerms;
use App\Enums\Role;
use App\Enums\TradeType;
use App\Models\Lead;
use App\Models\Product;
use App\Models\TradeAccount;
use App\Models\User;
use App\Models\Vacancy;
use App\Support\Delivery;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Pitch demo and local work: php artisan migrate:fresh --seeder=DemoSeeder
 *
 * Everything here is sample data and is flagged is_demo. Catalogue lines use brands the
 * company lists on acadostlucia.com, but the product names and pack sizes are examples to
 * confirm, with no prices, codes or stock levels. Trade accounts, vacancies and the people
 * and businesses in the requests are made up and carry "Demo" or "Sample" in their names.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $users = collect([
            ['admin@peterco.test', 'Demo Administrator', Role::Admin],
            ['sales@peterco.test', 'Demo Trade Desk', Role::Sales],
            ['sales2@peterco.test', 'Second Sales Rep', Role::Sales],
            ['credit@peterco.test', 'Demo Credit Controller', Role::Credit],
            ['hr@peterco.test', 'Demo HR Officer', Role::Hr],
        ])->mapWithKeys(fn (array $u) => [$u[0] => User::updateOrCreate(['email' => $u[0]], [
            'name' => $u[1], 'role' => $u[2], 'active' => true, 'password' => Hash::make('password'),
        ])]);

        $products = $this->products();
        $accounts = $this->accounts($users['credit@peterco.test']);
        $vacancies = $this->vacancies();
        $this->leads($users->all(), $products, $accounts, $vacancies);
    }

    /** @return list<Product> */
    private function products(): array
    {
        $rows = [
            // division, brand, name, category, pack, featured
            ['wines-spirits', 'Hennessy', 'Hennessy V.S Cognac', 'Cognac & brandy', '12 × 700 ml', true],
            ['wines-spirits', 'Hennessy', 'Hennessy V.S.O.P Privilège Cognac', 'Cognac & brandy', '6 × 700 ml', false],
            ['wines-spirits', 'El Dorado', 'El Dorado 12 Year Old Rum', 'Rum', '12 × 750 ml', true],
            ['wines-spirits', 'El Dorado', 'El Dorado 5 Year Old Rum', 'Rum', '12 × 750 ml', false],
            ['wines-spirits', 'Appleton Estate', 'Appleton Estate Signature Rum', 'Rum', '12 × 750 ml', false],
            ['wines-spirits', 'Bacardí', 'Bacardí Carta Blanca Rum', 'Rum', '12 × 750 ml', false],
            ['wines-spirits', 'Johnnie Walker', 'Johnnie Walker Black Label Whisky', 'Whisky', '12 × 750 ml', true],
            ['wines-spirits', 'Smirnoff', 'Smirnoff No. 21 Vodka', 'Vodka', '12 × 750 ml', false],
            ['wines-spirits', 'Grey Goose', 'Grey Goose Vodka', 'Vodka', '6 × 750 ml', false],
            ['wines-spirits', 'Bombay Sapphire', 'Bombay Sapphire London Dry Gin', 'Gin', '12 × 750 ml', false],
            ['wines-spirits', 'Baileys', 'Baileys Original Irish Cream', 'Liqueurs & aperitifs', '12 × 750 ml', false],
            ['wines-spirits', 'Don Julio', 'Don Julio Blanco Tequila', 'Tequila', '6 × 750 ml', false],
            ['wines-spirits', 'Moët & Chandon', 'Moët & Chandon Impérial Brut', 'Champagne & sparkling', '6 × 750 ml', true],
            ['wines-spirits', 'Barefoot', 'Barefoot Moscato', 'Wine', '12 × 750 ml', false],
            ['beverages', 'Carib', 'Carib Lager', 'Beer & stout', '24 × 275 ml bottles', true],
            ['beverages', 'Mackeson', 'Mackeson Stout', 'Beer & stout', '24 × 275 ml bottles', false],
            ['beverages', 'Smirnoff Ice', 'Smirnoff Ice Original', 'Cider & shandy', '24 × 275 ml bottles', false],
            ['beverages', 'Rockstone Tonic Wine', 'Rockstone Tonic Wine', 'Malt & tonic wine', '12 × 750 ml', false],
            ['beverages', 'Endura Malt', 'Endura Malt', 'Malt & tonic wine', '24 × 275 ml bottles', false],
            ['beverages', 'Peardrax', 'Peardrax', 'Soft drinks', '24 × 275 ml bottles', false],
            ['beverages', 'Pepsi', 'Pepsi', 'Soft drinks', '24 × 355 ml cans', true],
            ['beverages', 'Gatorade', 'Gatorade Thirst Quencher', 'Sports & energy', '24 × 591 ml', false],
            ['beverages', 'Lucozade', 'Lucozade Energy Original', 'Sports & energy', '24 × 380 ml', false],
            ['beverages', 'Pringles', 'Pringles Original', 'Snacks', '14 × 149 g', false],
            ['beverages', 'Snickers', 'Snickers Bar', 'Confectionery', '48 × 52.7 g', false],
            ['food', 'Anchor', 'Anchor Full Cream Milk Powder', 'Dairy', '12 × 400 g', true],
            ['food', 'Café Bustelo', 'Café Bustelo Espresso Ground Coffee', 'Coffee & tea', '24 × 283 g', false],
            ['food', 'Twinings', 'Twinings English Breakfast Tea', 'Coffee & tea', '12 × 25 bags', false],
            ['food', 'Jif', 'Jif Creamy Peanut Butter', 'Grocery', '12 × 454 g', false],
            ['food', 'Prego', 'Prego Traditional Pasta Sauce', 'Grocery', '12 × 680 g', false],
            ['food', 'Fabuloso', 'Fabuloso Lavender Multi-Purpose Cleaner', 'Household & cleaning', '12 × 828 ml', true],
            ['food', 'Beep', 'Beep Hand Sanitiser', 'Household & cleaning', '24 × 500 ml', false],
            ['food', 'Arm & Hammer', 'Arm & Hammer Baking Soda', 'Household & cleaning', '24 × 454 g', false],
            ['food', 'Colgate', 'Colgate Total Toothpaste', 'Oral care', '48 × 100 ml', false],
            ['food', 'Irish Spring', 'Irish Spring Original Bar Soap', 'Personal care', '48 × 104 g', false],
            ['food', 'Sensodyne', 'Sensodyne Repair & Protect', 'Oral care', '36 × 75 ml', false],
        ];
        $out = [];
        foreach ($rows as [$division, $brand, $name, $category, $pack, $featured]) {
            $out[] = Product::updateOrCreate(['slug' => 'sample-'.Str::slug($name)], [
                'name' => $name, 'brand' => $brand, 'division' => $division, 'category' => $category, 'pack_size' => $pack,
                'sku' => null, 'featured' => $featured, 'published' => true, 'is_demo' => true,
                'description' => 'Sample catalogue line for the website preview. The trade desk confirms the range, pack sizes, prices and availability.',
            ]);
        }

        return $out;
    }

    /** @return list<TradeAccount> */
    private function accounts(User $credit): array
    {
        $rows = [
            ['Demo Bayside Supermarket', TradeType::Supermarket, 'gros_islet', PaymentTerms::Credit, AccountStatus::Active, null],
            ['Demo Valley Minimart', TradeType::Minimart, 'castries', PaymentTerms::Cod, AccountStatus::Active, null],
            ['Demo Harbour Bar & Grill', TradeType::Bar, 'castries', PaymentTerms::Cod, AccountStatus::Active, null],
            ['Demo Southside Pharmacy', TradeType::Pharmacy, 'vieux_fort', PaymentTerms::Credit, AccountStatus::Active, 'DEMO-PH-0412'],
            ['Demo Ridge Hotel', TradeType::Hotel, 'soufriere', PaymentTerms::Credit, AccountStatus::Active, null],
            ['Demo Corner Shop', TradeType::Minimart, 'micoud', PaymentTerms::Cod, AccountStatus::OnHold, null],
            ['Demo Village Rum Shop', TradeType::Bar, 'dennery', PaymentTerms::Cod, AccountStatus::Active, null],
            ['Demo Family Clinic', TradeType::Clinic, 'castries', PaymentTerms::Credit, AccountStatus::Active, 'DEMO-MC-0088'],
        ];
        $out = [];
        foreach ($rows as $i => [$name, $type, $district, $terms, $status, $licence]) {
            $out[] = TradeAccount::firstOrCreate(['business_name' => $name], [
                'trade_type' => $type, 'contact_name' => ['Sample Manager', 'Sample Owner', 'Sample Buyer'][$i % 3], 'phone' => '758-450-'.str_pad((string) (1000 + $i * 37), 4, '0', STR_PAD_LEFT),
                'email' => Str::slug($name).'@example.com', 'district' => $district, 'terms' => $terms, 'status' => $status, 'pharmacy_licence' => $licence,
                'approved_by' => $credit->id, 'approved_at' => now()->subMonths(6 - ($i % 5)), 'is_demo' => true,
            ]);
        }

        return $out;
    }

    /** @return list<Vacancy> */
    private function vacancies(): array
    {
        $rows = [
            ['Merchandiser (sample vacancy)', 'Sales', 'Visit supermarkets and minimarts on a set route to keep shelves stocked, faced and priced to the trade desk\'s planogram.',
                "Visit customer stores on your route\nRotate stock and build displays\nReport out-of-stocks to the trade desk", "CXC passes in English and Mathematics\nA valid driving licence is an advantage"],
            ['Delivery driver (sample vacancy)', 'Logistics', 'Deliver confirmed trade orders from Cul de Sac to customers around the island, safely and on time.',
                "Load, deliver and unload orders\nCollect signatures and returns\nKeep the truck clean and report faults", "A valid licence for commercial vehicles\nA clean driving record"],
            ['Warehouse associate (sample vacancy)', 'Warehouse', 'Receive containers, put away stock and pick and pack trade orders in the Cul de Sac warehouse.',
                "Receive and check deliveries\nPick and pack orders accurately\nKeep aisles safe and tidy", "Able to lift cases safely\nForklift certification is an advantage"],
            ['Sales representative (sample vacancy)', 'Sales', 'Look after a book of trade customers, take orders and open new accounts in your territory.',
                "Visit customers and take orders\nOpen new trade accounts\nFollow up on credit and collections with credit control", "Two years of FMCG or field sales experience\nA valid driving licence"],
        ];
        $out = [];
        foreach ($rows as $i => [$title, $dept, $summary, $duties, $reqs]) {
            $out[] = Vacancy::updateOrCreate(['slug' => Str::slug($title)], [
                'title' => $title, 'department' => $dept, 'summary' => $summary, 'duties' => $duties, 'requirements' => $reqs,
                'location' => 'Cul de Sac, Castries', 'employment_type' => 'Full time', 'closes_on' => today()->addDays(14 + $i * 7), 'published' => true, 'is_demo' => true,
            ]);
        }

        return $out;
    }

    /**
     * @param  array<string, User>  $users
     * @param  list<Product>  $products
     * @param  list<TradeAccount>  $accounts
     * @param  list<Vacancy>  $vacancies
     */
    private function leads(array $users, array $products, array $accounts, array $vacancies): void
    {
        if (Lead::exists()) {
            return;
        }
        mt_srand(1891);
        $first = ['Kareem', 'Shania', 'Marvin', 'Celestine', 'Jerome', 'Natasha', 'Kervin', 'Anisha', 'Derek', 'Merle', 'Tevin', 'Sherma', 'Allan', 'Lydia', 'Curtis', 'Danielle', 'Rodney', 'Josiah'];
        $last = ['Joseph', 'Charles', 'Francis', 'St. Rose', 'Jn Baptiste', 'Alexander', 'Emmanuel', 'Edmund', 'Augustin', 'Mathurin', 'Polius', 'Charlemagne', 'Felix', 'Louis'];
        $shops = ['Demo Seaview Minimart', 'Demo Hilltop Grocery', 'Demo Beach Bar', 'Demo Main Street Mart', 'Demo Riverside Snackette', 'Demo Sunset Lounge'];
        $sales = [$users['sales@peterco.test'], $users['sales2@peterco.test']];
        $credit = $users['credit@peterco.test'];
        $hr = $users['hr@peterco.test'];
        $admin = $users['admin@peterco.test'];
        $districts = array_keys((array) config('peterco.districts'));

        $make = function (LeadType $type, Carbon $at, array $extra = []) use ($first, $last): Lead {
            $name = $first[array_rand($first)].' '.$last[array_rand($last)];
            $lead = Lead::create(array_merge([
                'type' => $type,
                'status' => LeadStatus::New,
                'name' => $name,
                'phone' => '758-'.mt_rand(450, 728).'-'.str_pad((string) mt_rand(0, 9999), 4, '0', STR_PAD_LEFT),
                'email' => strtolower(str_replace([' ', '.'], ['.', ''], $name)).'@example.com',
                'contact_pref' => Arr::random(['phone', 'phone', 'whatsapp', 'email']),
                'source_url' => url('/'),
            ], $extra));
            $lead->forceFill(['created_at' => $at, 'updated_at' => $at])->saveQuietly();
            $lead->forceFill(['reference' => sprintf('%s-%s-%05d', $type->prefix(), $at->format('y'), $lead->id)])->saveQuietly();
            $lead->notes()->create(['kind' => 'system', 'body' => 'Received from the website'.($lead->subject() ? ' about '.$lead->subject() : '').'.'])
                ->forceFill(['created_at' => $at])->saveQuietly();

            return $lead;
        };

        $progress = function (Lead $lead, User $by, LeadStatus $to, int $minutes, string $note): void {
            $at = $lead->created_at->copy()->addMinutes($minutes);
            $lead->forceFill(['status' => $to, 'status_changed_at' => $at, 'first_contacted_at' => $at, 'assigned_to' => $by->id])->saveQuietly();
            $lead->notes()->create(['user_id' => $by->id, 'kind' => 'assign', 'body' => "Assigned to {$by->name}."])->forceFill(['created_at' => $at])->saveQuietly();
            $lead->notes()->create(['user_id' => $by->id, 'kind' => 'note', 'body' => $note])->forceFill(['created_at' => $at])->saveQuietly();
            $lead->notes()->create(['user_id' => $by->id, 'kind' => 'status', 'body' => 'Status changed to '.$to->label().'.'])->forceFill(['created_at' => $at])->saveQuietly();
        };

        $order = function (Carbon $at) use ($products, $accounts, $shops, $districts): array {
            $account = mt_rand(0, 2) ? $accounts[array_rand($accounts)] : null;
            if ($account && $account->status !== AccountStatus::Active) {
                $account = null;
            }
            $picked = Arr::random($products, mt_rand(2, 6));
            $lines = array_map(fn (Product $p) => ['product_id' => $p->id, 'name' => $p->name, 'brand' => $p->brand, 'pack' => $p->pack_size, 'sku' => null, 'cases' => Arr::random([1, 2, 2, 3, 5, 10])], $picked);
            $day = $at->copy()->addDays(mt_rand(1, 4));
            while (! Delivery::isWorkingDay($day)) {
                $day->addDay();
            }

            return [
                'business_name' => $account->business_name ?? $shops[array_rand($shops)],
                'trade_account_id' => $account?->id,
                'delivery_date' => mt_rand(0, 3) ? $day->toDateString() : null,
                'details' => [
                    'lines' => $lines, 'other_items' => mt_rand(0, 4) ? null : 'Any point-of-sale material for the bar counter, please.',
                    'pharmaceutical' => false, 'trade_type' => ($account->trade_type ?? Arr::random([TradeType::Minimart, TradeType::Bar, TradeType::Supermarket]))->value,
                    'district' => $account->district ?? $districts[array_rand($districts)], 'delivery_address' => 'Sample address for the demo, '.Str::headline($account->district ?? 'castries'),
                    'account_number' => $account?->number,
                ],
            ];
        };

        $accountPayload = function (Carbon $at) use ($districts): array {
            $type = Arr::random([TradeType::Supermarket, TradeType::Minimart, TradeType::Bar, TradeType::Hotel, TradeType::Pharmacy]);
            $terms = Arr::random([PaymentTerms::Cod, PaymentTerms::Credit]);

            return [
                'business_name' => 'Demo '.Arr::random(['Morning Star', 'Palm Grove', 'Blue Lagoon', 'Coconut Bay', 'Green Hill', 'Lighthouse']).' '.match ($type) {
                    TradeType::Pharmacy => 'Pharmacy', TradeType::Bar => 'Bar', TradeType::Hotel => 'Guest House', default => 'Mart',
                },
                'consented_at' => $at,
                'details' => [
                    'position' => Arr::random(['Owner', 'Manager', 'Director']), 'trading_as' => null, 'trade_type' => $type->value, 'trade_type_label' => $type->label(),
                    'registration_no' => 'DEMO-'.mt_rand(1000, 9999), 'trade_licence_no' => 'DEMO-TL-'.mt_rand(10000, 99999), 'vat_registered' => (bool) mt_rand(0, 1),
                    'vat_no' => 'DEMO-VAT-'.mt_rand(100000, 999999), 'years_trading' => Arr::random(['1_3', '3_10', 'o10']), 'outlets' => 1,
                    'address' => 'Sample address for the demo', 'district' => $districts[array_rand($districts)],
                    'pharmacy_licence_no' => $type->isMedical() ? 'DEMO-PH-'.mt_rand(1000, 9999) : null, 'pharmacist' => $type->isMedical() ? 'Sample Pharmacist' : null,
                    'terms' => $terms->value, 'credit_limit' => null, 'bank_name' => $terms === PaymentTerms::Credit ? 'Sample Bank' : null,
                    'references' => $terms === PaymentTerms::Credit ? [['name' => 'Sample Supplier Ltd', 'phone' => '758-450-0000']] : [],
                ],
            ];
        };

        // Thirty days of history.
        for ($d = 29; $d >= 1; $d--) {
            $day = today()->subDays($d);
            if ($day->isSunday()) {
                continue;
            }
            for ($n = 0, $count = mt_rand(2, 6); $n < $count; $n++) {
                $at = $day->copy()->setTime(mt_rand(7, 16), mt_rand(0, 59));
                $type = Arr::random([LeadType::Order, LeadType::Order, LeadType::Order, LeadType::Order, LeadType::Order, LeadType::Account, LeadType::Application, LeadType::Application, LeadType::Enquiry, LeadType::Principal]);
                [$lead, $who, $to, $note] = match ($type) {
                    LeadType::Order => [$make($type, $at, $order($at)), $sales[array_rand($sales)], Arr::random([LeadStatus::Confirmed, LeadStatus::Dispatched, LeadStatus::Delivered, LeadStatus::Delivered, LeadStatus::Delivered, LeadStatus::Cancelled]),
                        Arr::random(['Called and confirmed prices and stock. Delivery on the next run.', 'Confirmed by WhatsApp. Two lines substituted with the customer\'s OK.', 'Customer asked to add two cases; updated and confirmed.'])],
                    LeadType::Account => [$make($type, $at, $accountPayload($at)), $credit, Arr::random([LeadStatus::Reviewing, LeadStatus::NeedsDocuments, LeadStatus::Declined]),
                        Arr::random(['Documents checked. Calling the trade reference.', 'VAT certificate is unreadable; asked the customer to send it again.', 'Not trading yet; asked them to reapply after opening.'])],
                    LeadType::Application => [$make($type, $at, ['vacancy_id' => mt_rand(0, 4) ? $vacancies[array_rand($vacancies)]->id : null, 'consented_at' => $at,
                        'message' => 'Sample applicant for the demo.', 'details' => ['role_interest' => null, 'district' => $districts[array_rand($districts)], 'available' => Arr::random(['now', '2w', '1m']), 'driving_licence' => Arr::random(['none', 'car', 'commercial'])]]),
                        $hr, Arr::random([LeadStatus::Shortlisted, LeadStatus::Interview, LeadStatus::Declined, LeadStatus::Declined]), 'CV reviewed against the role requirements.'],
                    LeadType::Principal => [$make($type, $at, ['business_name' => 'Demo '.Arr::random(['Snack Foods', 'Beverage Co.', 'Home Care', 'Spirits House']).' Ltd',
                        'message' => 'Sample principal enquiry for the demo.', 'details' => ['job_title' => 'Export Manager', 'country' => Arr::random(['Trinidad and Tobago', 'Barbados', 'United States', 'United Kingdom', 'Guyana']),
                            'company_site' => null, 'brands' => 'Sample Brand', 'categories' => [Arr::random(['beverages', 'food', 'wines-spirits'])], 'caribbean' => 'Sold in Trinidad and Barbados.']]),
                        $admin, Arr::random([LeadStatus::Contacted, LeadStatus::InDiscussion, LeadStatus::Declined]), 'Sent our trade terms questionnaire.'],
                    LeadType::Enquiry => [$make($type, $at, ['message' => Arr::random(['Can you deliver to Dennery on Fridays?', 'Who is the sales rep for Gros Islet?', 'Do you sponsor community football tournaments?']),
                        'details' => ['topic' => Arr::random(['order', 'sponsorship', 'other'])]]), $sales[array_rand($sales)], Arr::random([LeadStatus::Contacted, LeadStatus::Closed]), 'Called back and answered.'],
                };
                $minutes = $type === LeadType::Order ? mt_rand(10, 140) : mt_rand(60, 2000);
                $progress($lead, $who, $to, $minutes, $note);
            }
        }

        // Today and yesterday: some fresh, some past their response target.
        foreach ([[LeadType::Order, 5], [LeadType::Order, 3.2], [LeadType::Order, 2.4], [LeadType::Order, 1.1], [LeadType::Order, 0.4],
            [LeadType::Account, 60], [LeadType::Account, 6], [LeadType::Application, 20], [LeadType::Application, 2], [LeadType::Principal, 30], [LeadType::Enquiry, 27]] as [$type, $hoursAgo]) {
            $at = now()->subMinutes((int) ($hoursAgo * 60));
            $make($type, $at, match ($type) {
                LeadType::Order => $order($at),
                LeadType::Account => $accountPayload($at),
                LeadType::Application => ['vacancy_id' => $vacancies[array_rand($vacancies)]->id, 'consented_at' => $at, 'details' => ['district' => 'castries', 'available' => 'now', 'driving_licence' => 'commercial']],
                LeadType::Principal => ['business_name' => 'Demo Island Snacks Ltd', 'message' => 'We would like to discuss distribution of our plantain chips in St Lucia.',
                    'details' => ['job_title' => 'Regional Sales Director', 'country' => 'Trinidad and Tobago', 'brands' => 'Sample Chips', 'categories' => ['beverages'], 'caribbean' => 'Trinidad, Barbados, Grenada']],
                LeadType::Enquiry => ['message' => 'Please have a rep call me about opening an account for my new shop.', 'details' => ['topic' => 'account']],
            });
        }

        // Placeholder documents, clearly marked, so the access rules can be demonstrated.
        foreach (Lead::where('type', LeadType::Account)->get() as $lead) {
            $kinds = ['trade_licence' => 'Trade licence', 'id' => 'Owner ID'] + ($lead->detail('vat_registered') ? ['vat_certificate' => 'VAT certificate'] : [])
                + (TradeType::tryFrom((string) $lead->detail('trade_type'))?->isMedical() ? ['pharmacy_licence' => 'Pharmacy licence'] : []);
            $this->files($lead, $kinds);
        }
        foreach (Lead::where('type', LeadType::Application)->get() as $lead) {
            $this->files($lead, ['cv' => 'CV']);
        }
    }

    /** @param array<string, string> $kinds */
    private function files(Lead $lead, array $kinds): void
    {
        foreach ($kinds as $kind => $label) {
            $path = "leads/{$lead->id}/demo-{$kind}.pdf";
            Storage::disk('local')->put($path, $this->placeholderPdf("SAMPLE {$label} for {$lead->reference}. Not a real document."));
            $lead->files()->create(['kind' => $kind, 'path' => $path, 'original_name' => "sample-{$kind}.pdf", 'mime' => 'application/pdf', 'size' => 600]);
        }
    }

    private function placeholderPdf(string $text): string
    {
        $stream = 'BT /F1 14 Tf 60 760 Td ('.addcslashes($text, '()\\').') Tj ET';
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n{$obj}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $o) {
            $pdf .= sprintf("%010d 00000 n \n", $o);
        }

        return $pdf.'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }
}
