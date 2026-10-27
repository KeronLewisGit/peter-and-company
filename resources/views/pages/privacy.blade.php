<x-layouts.site title="Privacy notice" description="How Acado St. Lucia handles the details you send through this website.">
    <x-page-hero title="Privacy notice" lead="What we collect through this website, why, who sees it and how long we keep it." code="L-01" label="Privacy" photo="pitons" alt="The Pitons, St Lucia" label-code="PRIVACY" label-line="Draft for review" />
    <section class="wrap mt-12 md:mt-16">
        <div class="prose-copy measure text-ink-2">
            <p><strong class="text-ink">Draft for review by Acado St. Lucia's legal adviser before launch.</strong> Last updated {{ now()->format('j F Y') }}.</p>
            <h2 class="text-ink">Who we are</h2>
            <p>{{ peterco('name') }}, {{ peterco('address.area') }}, {{ peterco('address.po_box') }}, {{ peterco('address.city') }}, Saint Lucia. Phone {{ format_phone(peterco('phone')) }}@if (peterco('email')), email <a href="mailto:{{ peterco('email') }}">{{ peterco('email') }}</a>@endif.</p>
            <h2 class="text-ink">What we collect</h2>
            <ul>
                <li>On every form: your name, phone, email and how you prefer to be contacted.</li>
                <li>Order requests: your business, delivery address, the products and quantities, and your trade account number if you have one.</li>
                <li>Trade account applications: business registration, trade licence, VAT and pharmacy licence numbers, trade references, your bank's name, and copies of your trade licence, VAT certificate, ID and (for pharmacies and clinics) licence.</li>
                <li>Principal enquiries: your company, brands and any presentation you attach.</li>
                <li>Job applications: your CV, cover letter, district, availability and driving licence.</li>
            </ul>
            <p>We never ask for bank account numbers, card numbers or PINs through this website.</p>
            <h2 class="text-ink">Why we use it</h2>
            <p>To answer your request: confirm an order and deliver it, assess and open a trade account, consider a distribution partnership, or consider you for a job. We don't sell your details or use them for unrelated marketing.</p>
            <h2 class="text-ink">Who can see it</h2>
            <p>Staff who handle your type of request. Trade account documents are stored privately, outside the public website, and only credit control can open them. CVs can be opened only by HR. Each time a document is opened it is recorded.</p>
            <h2 class="text-ink">How long we keep it</h2>
            <p>Order requests and enquiries for up to two years. Account applications that are declined, and their documents, for up to twelve months. Job applications for twelve months. You can ask us to delete your details sooner.</p>
            <h2 class="text-ink">Your choices</h2>
            <p>You can ask for a copy of the details we hold about you, or ask us to correct or delete them. Call us using the details above.</p>
            <h2 class="text-ink">Cookies</h2>
            <p>This site uses only the cookies needed to send forms securely, keep your order list, and keep staff signed in. There are no advertising or tracking cookies. Your light or dark theme choice is kept in your browser.</p>
        </div>
    </section>
</x-layouts.site>
