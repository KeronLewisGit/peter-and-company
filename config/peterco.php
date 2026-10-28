<?php

/*
 * Company facts, divisions, the brand portfolio, form options and photo metadata, read
 * with peterco('key').
 *
 * Sources (checked 26 October 2026): acadostlucia.com (home, who-we-are, contact, the four
 * division pages and their brand-logo grids, uploaded May 2025); The Voice St Lucia
 * (21 Feb 2025 rename; Aug 2023 CPL launch; Mar 2024 Henny White launch and Ministry of
 * Health donation; Dec 2020 Diquez award; Nov 2017 Carib Brewery line); the Wayback Machine
 * copy of peterandcompanydistribution.com (2019 to 2024). Nothing here is a price, a stock
 * level, a staff count or an award we could not source. See README "Before launch".
 */
return [
    'name' => 'Acado St. Lucia Limited',
    'short_name' => 'Acado St. Lucia',
    'former_name' => 'Peter & Company Distribution',
    'founded' => 1891,
    'preview' => (bool) env('PETERCO_PREVIEW', false),
    // Head office line from acadostlucia.com (contact page and footer). No email address
    // and no WhatsApp number are published, so both stay empty until the client gives them.
    'phone' => env('PETERCO_PHONE', '758-457-7000'),
    'whatsapp' => env('PETERCO_WHATSAPP') ?: null,
    'email' => env('PETERCO_EMAIL') ?: null,
    'notify' => env('PETERCO_NOTIFY_EMAIL', ''),
    // Optional inbox per request type; empty ones fall back to PETERCO_NOTIFY_EMAIL.
    'notify_by_type' => [
        'order' => env('PETERCO_NOTIFY_ORDERS', ''),
        'account' => env('PETERCO_NOTIFY_CREDIT', ''),
        'application' => env('PETERCO_NOTIFY_HR', ''),
        'principal' => env('PETERCO_NOTIFY_PRINCIPALS', ''),
    ],
    'tagline' => 'Creating Value Enriching Lives.',
    'currency' => 'EC$',

    'address' => [
        'area' => 'Cul De Sac',
        'po_box' => 'P.O. Box 84-85',
        'city' => 'Castries',
        'country' => 'Saint Lucia',
        'country_code' => 'LC',
    ],
    'map_url' => 'https://www.google.com/maps/search/?api=1&query=Cul+De+Sac+Castries+Saint+Lucia',

    'social' => [
        'Facebook' => 'https://www.facebook.com/acadostluciaexperiences',
        'Instagram' => 'https://www.instagram.com/acadostlucia/',
        'LinkedIn' => 'https://www.linkedin.com/company/acadostlucia/',
    ],

    // From acadostlucia.com/our-retail-stores (the company's own shops, not trade customers),
    // with the photo of each store published there.
    'retail' => [
        ['name' => 'PCD Wines & Spirits', 'where' => 'Bridge Street, Castries', 'phone' => '758-457-7082', 'photo' => 'store-bridge-street'],
        ['name' => 'PCD Wines & Spirits', 'where' => 'JQ Mall, Rodney Bay', 'phone' => '758-457-7083', 'photo' => 'store-jq-mall'],
        ['name' => 'PCD Food Mart', 'where' => 'Clark Street, Vieux Fort', 'phone' => '758-457-7084', 'photo' => 'store-vieux-fort'],
        ['name' => 'PCD Food Mart', 'where' => 'S&S Plaza, Bois D\'Orange', 'phone' => '758-457-7089', 'photo' => 'store-bois-dorange'],
        ['name' => 'Wet & Cold Express', 'where' => 'Mongiraud Street, Castries', 'phone' => '758-457-7086', 'photo' => 'store-wet-cold'],
    ],

    'history' => [
        ['year' => '1891', 'text' => 'William Peter founds Peter & Company in Castries as a coaling business.'],
        ['year' => '1961', 'text' => 'J. N. Goddard & Sons of Barbados acquires a majority stake. The company grows into food and beverage, building materials and Nissan dealerships, and opens its own shops.'],
        ['year' => '2015', 'text' => 'Peter & Company buys Bryden & Partners (St. Lucia) Ltd and refocuses on fast-moving consumer goods. Caribbean Distribution Partners, the Agostini\'s and Goddard Enterprises joint venture, buys the company, and the businesses merge as Peter & Company Distribution (PCD).'],
        ['year' => '2017', 'text' => 'The Carib Brewery range joins the portfolio.'],
        ['year' => '2023', 'text' => 'PCD is the exclusive distributor of Carib Brewery products and El Dorado rum in St Lucia, and brings the CPL launch to the Harbour Club.'],
        ['year' => '2025', 'text' => 'Caribbean Distribution Partners becomes Acado Limited, and Peter & Company Limited becomes Acado St. Lucia Limited.'],
    ],

    // The "Featured brands" grid on the acadostlucia.com home page, in its order.
    'featured_brands' => ['Veuve Clicquot', 'Eve', 'Anchor', 'Diquez', 'Carib', 'Beep', 'Smirnoff', 'Bombay Sapphire', 'Campari', 'Johnnie Walker',
        'El Dorado', 'Carlisle Laboratories', 'Dewar\'s', 'Peardrax', 'BOP', 'Go!', 'Pinehill', 'Sante', 'Farmer\'s Choice', 'Hennessy',
        'Moët & Chandon', 'Swiss', 'Super Cow', 'Grace'],

    // Acado's core values, as published on acadostlucia.com/who-we-are.
    'values' => [
        'People Centered' => 'We value our people and recognize that people are at the heart of our business and at the heart of everything we do.',
        'Integrity' => 'We act with honesty, openness and transparency.',
        'Trust' => 'We offer products and services that we believe in and that our customers trust.',
        'Ownership' => 'We encourage and engender empowered teams who take responsibility for successfully leading each subsidiary.',
        'Entrepreneurial Spirit' => 'We foster a mindset that embraces critical questioning, innovation and continuous improvement and inspires a sense of ownership.',
    ],
    'vision' => 'To be the benchmark of our region by sustainably building brands that enrich lives for generations to come.',

    // Delivery requests on the order form: weekdays, the next working day up to two weeks ahead.
    'delivery' => ['days_ahead' => 14, 'weekdays' => [1, 2, 3, 4, 5]],
    // Public holidays (Y-m-d) on weekdays, when no delivery day is offered. Confirm the list with the client.
    'closed_dates' => ['2026-12-25', '2027-01-01', '2027-02-22', '2027-03-26', '2027-03-29', '2027-05-17', '2027-05-27'],

    // The ten quarters (districts) of Saint Lucia.
    'districts' => [
        'castries' => 'Castries', 'gros_islet' => 'Gros Islet', 'anse_la_raye' => 'Anse la Raye', 'canaries' => 'Canaries',
        'soufriere' => 'Soufrière', 'choiseul' => 'Choiseul', 'laborie' => 'Laborie', 'vieux_fort' => 'Vieux Fort',
        'micoud' => 'Micoud', 'dennery' => 'Dennery',
    ],
    'years_trading' => ['new' => 'Opening soon or under 1 year', '1_3' => '1 to 3 years', '3_10' => '3 to 10 years', 'o10' => 'Over 10 years'],
    'availability' => ['now' => 'Straight away', '2w' => 'In 2 weeks', '1m' => 'In a month', 'later' => 'Later'],
    'enquiry_topics' => [
        'order' => 'An order or a delivery',
        'account' => 'My trade account or credit',
        'principal' => 'Distribution of my brand',
        'careers' => 'Jobs and careers',
        'sponsorship' => 'Sponsorship or events',
        'other' => 'Something else',
    ],

    /*
     * The four divisions, named as on acadostlucia.com, with the brands shown on each
     * division page there (logo grids uploaded May 2025, read tile by tile). Dated relationships
     * come from The Voice. Each brand's logo tile, as published on those pages, is mapped in
     * config/brand-logos.php.
     */
    'divisions' => [
        'wines-spirits' => [
            'name' => 'Wines, Spirits & Tobacco',
            'short' => 'Wines & spirits',
            'code' => 'WS',
            'photo' => 'division-wines-spirits',
            'blurb' => 'Champagne, wine, rum, whisky, cognac, vodka, gin and tequila for bars, hotels, restaurants and shops across the island.',
            'note' => 'Hennessy has been distributed by the company for more than 35 years (The Voice, March 2024), and El Dorado rum exclusively since at least 2023.',
            'categories' => ['Rum', 'Cognac & brandy', 'Whisky', 'Vodka', 'Gin', 'Tequila', 'Liqueurs & aperitifs', 'Champagne & sparkling', 'Wine'],
            'brands' => ['Hennessy', 'El Dorado', 'Appleton Estate', 'Bacardí', 'Zacapa', 'Johnnie Walker', 'Buchanan\'s', 'J&B', 'Dewar\'s', 'Wild Turkey',
                'Smirnoff', 'Cîroc', 'Grey Goose', 'Ketel One', 'Skyy', 'Bombay Sapphire', 'Tanqueray', 'Gordon\'s', 'Bulldog', 'Don Julio', 'Casamigos',
                'Black & White', 'Baileys', 'Grand Marnier', 'De Kuyper', 'Campari', 'Aperol', 'Cinzano', 'Moët & Chandon', 'Veuve Clicquot', 'Dom Pérignon', 'Ruinart', 'Armand de Brignac',
                'Barefoot', 'Carlo Rossi', 'Robert Mondavi', 'Woodbridge', 'Apothic', 'André', 'Santa Rita', 'Gérard Bertrand', 'Fat Bastard', 'Folonari',
                'Cavicchioli', 'Joseph Drouhin', 'J. Moreau & Fils', 'Barton & Guestier', 'Ca\' del Bosco', 'Torresella', 'La Marca', 'Terrazas de los Andes',
                'Santa Ana', 'Simi', '3 Medallas', 'Santa Margherita', 'Duckhorn', 'Lamothe Parrot', 'Acqua Panna', 'S.Pellegrino', 'Viceroy', 'Rothmans'],
        ],
        'beverages' => [
            'name' => 'Brewery, Beverages & Snacks',
            'short' => 'Beer, beverages & snacks',
            'code' => 'BB',
            'photo' => 'division-beverages',
            'blurb' => 'Beer, stout, cider, malt, soft drinks, sports drinks, water and the confectionery and snacks that sell beside them.',
            'note' => 'Exclusive St Lucia distributor of Carib Brewery products (The Voice, August 2023); the Carib range joined in 2017.',
            'categories' => ['Beer & stout', 'Cider & shandy', 'Malt & tonic wine', 'Soft drinks', 'Juice', 'Water', 'Sports & energy', 'Confectionery', 'Snacks'],
            'brands' => ['Carib', 'Caribé', 'Shandy', 'Mackeson', 'Smirnoff Ice', 'Rockstone Tonic Wine', 'Endura Malt', 'Pepsi', 'Peardrax', 'Cydrax', 'Mauby Fizz',
                'Sorrel Fizz', 'Pinehill', 'Tropical Rhythms', 'Ju-C', 'Ribena', 'Lucozade', 'Gatorade', 'Paradise Water', 'Perrier', 'Pringles', 'Combos',
                'Snickers', 'M&M\'s', 'Twix', 'Milky Way', '3 Musketeers', 'Magnum', 'Tunnock\'s', 'Diana', 'OKF', 'TinyBites', 'Ice Breakers', 'Jolly Rancher'],
        ],
        'food' => [
            'name' => 'Food, Household, Frozen & Personal Care',
            'short' => 'Food, household & personal care',
            'code' => 'FH',
            'photo' => 'division-food',
            'blurb' => 'Grocery, dairy, frozen, coffee and tea, cleaning and laundry, oral care and personal care for supermarkets, minimarts and caterers.',
            'note' => 'A distributor of Intersol\'s Diquez range since 1995, and one of two Intersol Distributor Achievement Award winners across nine territories in 2020 (The Voice, December 2020).',
            'categories' => ['Grocery', 'Dairy', 'Frozen', 'Coffee & tea', 'Household & cleaning', 'Laundry', 'Oral care', 'Personal care', 'Baby care', 'Vitamins'],
            'brands' => ['Grace', 'Swiss', 'Sante', 'Super Cow', 'Dutch Lady', 'Anchor', 'Farmer\'s Choice', 'Perdue', 'Pepe\'s', 'Nature\'s Pride', 'Prego', 'Ragú',
                'Smucker\'s', 'Jif', 'Saf-instant', 'Lotus', 'Lion Brand', 'Tropic Fresh', 'Café Brasil', 'Café Bustelo', 'Folgers', 'Dunkin\'', 'Twinings', 'Bigelow',
                'Beep', 'Diquez', 'Fabuloso', 'Ajax', 'Clean & White', 'Arm & Hammer', 'Snuggle', 'Super Suretox', 'Rhino', 'Colgate', 'Palmolive', 'Irish Spring',
                'Protex', 'Coast', 'Sensodyne', 'Aquafresh', 'Eve', 'Feelings', 'Seven', 'Lovege', 'Roberts', 'BOP', 'Baby Love', 'Bebin', 'Haliborange', 'Seven Seas', 'Go!', 'Oba Mie', 'DAK'],
        ],
        'pharmaceutical' => [
            'name' => 'Pharmaceuticals',
            'short' => 'Pharmaceuticals',
            'code' => 'RX',
            'photo' => 'division-pharmaceutical',
            'restricted' => true,
            'blurb' => 'Medicines, health and diagnostic products for licensed pharmacies, hospitals and clinics. Acado St. Lucia describes itself as the largest pharmaceutical distributor in St Lucia.',
            'note' => 'Supplied to licensed pharmacies and medical facilities only. We do not sell medicines to the public, and this range is not listed in the online catalogue.',
            'categories' => ['Pain relief', 'Cough, cold & allergy', 'Vitamins & supplements', 'Oral health', 'Diagnostics', 'Generics'],
            'brands' => ['Carlisle Laboratories', 'Genethics', 'Panadol', 'Cetamol', 'Alka-Seltzer', 'Hemafed', 'DPH', 'Gravinate', 'Vigorton 2',
                'Neubria', 'TheraBreath', 'OneTouch', 'Senior'],
        ],
    ],

    /*
     * Photo sizes (largest WebP) for the width/height attributes. Files: public/img/{key}-{w}.webp
     * at 480/960/1920 wide, or at the listed 'widths' for the company's own photos, which
     * acadostlucia.com publishes smaller. Sources and licences: config/photo-credits.php.
     */
    'photos' => [
        // The company's own photos, from acadostlucia.com.
        'acado-team' => ['w' => 1366, 'h' => 768, 'widths' => [480, 960, 1366]],
        'acado-vision' => ['w' => 1920, 'h' => 1080],
        'acado-careers' => ['w' => 1920, 'h' => 1080],
        'acado-careers-tablet' => ['w' => 1080, 'h' => 1080, 'widths' => [480, 1080]],
        'division-wines-spirits' => ['w' => 500, 'h' => 500, 'widths' => [500]],
        'division-food' => ['w' => 500, 'h' => 500, 'widths' => [500]],
        'division-beverages' => ['w' => 500, 'h' => 500, 'widths' => [500]],
        'division-pharmaceutical' => ['w' => 500, 'h' => 500, 'widths' => [500]],
        'store-bridge-street' => ['w' => 1000, 'h' => 900, 'widths' => [480, 1000]],
        'store-jq-mall' => ['w' => 1000, 'h' => 900, 'widths' => [480, 1000]],
        'store-vieux-fort' => ['w' => 1000, 'h' => 900, 'widths' => [480, 1000]],
        'store-bois-dorange' => ['w' => 1000, 'h' => 900, 'widths' => [480, 1000]],
        'store-wet-cold' => ['w' => 1000, 'h' => 900, 'widths' => [480, 1000]],
        // Wikimedia Commons, for the pages the company has no photo for yet.
        'castries-container-ship' => ['w' => 1920, 'h' => 1280],
        'castries-harbour' => ['w' => 1920, 'h' => 1268],
        'cul-de-sac-valley' => ['w' => 1920, 'h' => 1280],
        'pitons' => ['w' => 1920, 'h' => 1280],
        'marigot-bay' => ['w' => 1920, 'h' => 1222],
        'castries-market' => ['w' => 1920, 'h' => 1280],
    ],

    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrator'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],
];
