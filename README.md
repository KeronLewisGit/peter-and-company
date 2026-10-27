# Acado St. Lucia (formerly Peter & Company Distribution)

Website, trade ordering and back office for Acado St. Lucia Limited, the FMCG and pharmaceutical distributor at Cul De Sac, Castries, Saint Lucia. The company was Peter & Company Limited (founded 1891) and traded as Peter & Company Distribution (PCD) until it was renamed in February 2025. It is part of Acado Limited, the Agostini's and Goddard Enterprises joint venture.

The current site (acadostlucia.com) is a brochure with a contact form. This build gives trade customers what they ask the sales reps for by phone: a catalogue, an order list, a trade account and credit application, and a place for brand owners and job applicants to land.

## What it does

- **Public site.** Home, the brand portfolio by division (Wines, Spirits & Tobacco; Brewery, Beverages & Snacks; Food, Household, Frozen & Personal Care; Pharmaceuticals), the trade catalogue, the order list, trade accounts, principals, careers, about (history, values, retail stores), contact, privacy notice, photo credits and a site map. Mobile-first, light and dark themes, and every form and filter works without JavaScript.
- **Trade catalogue.** Products by division, brand, category and search. No prices and no stock levels, on purpose: the trade desk confirms both on every order. The pharmaceutical range is never listed publicly.
- **Order requests** (`OR-26-00042`). Anyone can add cases to a session order list from the catalogue, then send it with the business, district, delivery address and a preferred working day (weekdays, up to 14 days ahead, minus `peterco.closed_dates`). Quoting a trade account number (`PC-10004`, typed in any format) links the request to the account; held or closed accounts are refused. Items not in the catalogue go in "Other items".
- **Pharmaceuticals are B2B only.** A pharmaceutical order is accepted only from an active pharmacy or clinic account with a licence on file. There is no consumer medicine sale anywhere on the site.
- **Trade account / credit application** (`TA-…`). Business details, trade licence and VAT numbers, pharmacy licence and responsible pharmacist (pharmacies and clinics), cash-on-delivery or credit terms with trade references, and uploads of the trade licence, VAT certificate, owner ID, pharmacy licence and an optional bank reference. Files go to the private disk, and if the form comes back with an error the files already attached are kept (`App\Support\UploadStash`, deleted after a day if the form is abandoned). Consent and a declaration are required, and bank or card numbers are refused.
- **Principal enquiries** (`PR-…`) from brand owners, with categories and an optional deck. **Careers** with vacancies managed by HR and applications with a CV (`CV-…`). **Contact** enquiries (`EN-…`).
- Every request gets a reference on screen and by email, and the office is emailed (per-team inboxes optional).
- **Back office** with four roles:
  - **Admin**: everything except opening credit documents.
  - **Sales / trade desk**: orders, enquiries, principal enquiries, and that an account application exists (without its credit details); trade accounts (read only); the catalogue.
  - **Credit**: trade account applications, the credit board, and approving an application, which opens a `PC-` trade account and emails the customer the number. The only role that can open trade licences, VAT certificates, IDs, pharmacy licences and bank references. Every opening is logged on the request's timeline.
  - **HR**: job applications, CVs (also open to admin) and vacancies.

  The dashboard leads with one number: requests still new, with nobody having spoken to the customer, after their response target (order requests 2 hours, enquiries 24 hours, account applications and principals 48 hours, job applications 5 days). It also shows up to four trend tiles for the role, requests per day, a breakdown by type, the oldest new requests and requested deliveries. The inbox has per-type stages, filters, search (press `/`) and CSV export. Each request page has the stage, the assignee, the delivery day for orders, a notes timeline and one-tap call, WhatsApp or email.

## Requirements

PHP 8.3 or 8.4 with GD, Composer 2, Node 20+ for building assets, and SQLite locally or MySQL in production.

## Local setup

```sh
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seeder=DemoSeeder   # demo staff, 36 sample catalogue lines, accounts, vacancies, ~120 requests over 30 days
npm install && npm run build
php artisan serve --port=8108
```

Demo logins all use the password `password`: `admin@peterco.test`, `sales@peterco.test`, `credit@peterco.test` and `hr@peterco.test`. Every demo product is marked "Sample listing", every demo vacancy "Sample vacancy", and demo businesses start with "Demo". Demo products only use brands sourced from the company's own division pages. No prices, stock levels, staff names or figures are invented.

## Checks

```sh
php artisan test                 # feature tests for every flow, role and policy, including credit-document access
vendor/bin/phpstan analyse       # Larastan level 6, analysed as PHP 8.3
vendor/bin/pint --test
```

`npm run proposal` (with the dev server on 8108 and demo data) captures the screens and builds the sales proposal PDF into `dist/proposal/`.

## Deploying

See [INSTALL-HOSTINGER.md](INSTALL-HOSTINGER.md). `scripts/package-hostinger.sh` builds `dist/peterco-hostinger.zip`, which extracts straight into `public_html/`, with the app in `peterco-app/`.

## Before launch: confirm with the client

- **Name and branding.** The site uses "Acado St. Lucia", with "formerly Peter & Company Distribution", following the February 2025 rename (The Voice, 21 February 2025) and acadostlucia.com. Confirm how much of the PCD name customers still need to see, and whether Acado Limited has regional brand guidelines or a group web platform this must fit.
- **Logo.** Taken from acadostlucia.com (800 × 205 PNG). Ask for vector artwork.
- **Contact details.** Head office phone 758-457-7000 and the address (Cul De Sac, P.O. Box 84-85, Castries) are from acadostlucia.com/contact. No public email address or WhatsApp number was found, so `PETERCO_EMAIL` and `PETERCO_WHATSAPP` are blank. Ask for the trade desk, credit control and HR inboxes (`PETERCO_NOTIFY_*`), office hours, and any order line or WhatsApp Business number.
- **Retail stores** in `peterco.retail`: names, locations and phone numbers from acadostlucia.com. Confirm they are current. acadostlucia.com/our-retail-stores also lists opening hours, which can be added once confirmed.
- **The brand portfolio** in `peterco.divisions`, read from the logo grids on the four division pages (uploaded May 2025). Confirm every brand is still carried, add missing ones and remove lapsed agencies. Rechecked 27 October 2026 against every logo tile (161 brands): "Pepe's" and "Nature's Pride" are one logo on the site, so confirm whether they are one brand or two; the red "MAGNUM" tile on the beverages page may be Magnum Tonic Wine rather than the ice cream; and the tobacco brands (Viceroy, Rothmans) are listed as text with no tobacco category in the catalogue. Dated claims come from The Voice: Hennessy for 35+ years (March 2024), the Carib range taken on in June 2017 (November 2017), exclusive distributor of Carib Brewery products and El Dorado (August 2023), and the 2020 Intersol Diquez award.
- **"Largest pharmaceutical distributor in St Lucia"** is the company's own claim on acadostlucia.com. Confirm it may be repeated, and confirm the pharmacy and clinic licensing rules and documents the credit team requires.
- **Catalogue.** All 36 demo lines are samples. The real catalogue needs the company's product list with case sizes and codes (a CSV import can be added), and pack shots from the company or its principals.
- **Trade account rules.** Account number format (`PC-` plus five digits is a placeholder), the documents asked for, credit terms, and whether existing account numbers should be imported.
- **Delivery.** Weekday delivery up to 14 days ahead is an assumption. Confirm the delivery days by district and the public holidays in `peterco.closed_dates`.
- **Response targets** per request type (2 hours for orders, and so on) are suggestions for the team to agree.
- **Group and coverage claims** on the principals page (island-wide coverage, Acado Limited ownership) are from public sources. Staff numbers (201–500 appears only in a directory) and Chamber membership were not verified and are not used.
- **Values and vision** are quoted from acadostlucia.com. **Leadership** is not named anywhere on the site.
- **Photos.** The logo is the company's own. Every photograph is from Wikimedia Commons, credited on `/photo-credits`. The warehouse, bottling, bar, supermarket and pharmacy photos show similar operations elsewhere; replace them with the company's warehouse, fleet and team.
- **Privacy notice**: a draft for the company's legal adviser (Saint Lucia's Data Protection Act).
- **Domain.** Set `PETERCO_PREVIEW=true` on a temporary preview domain so a banner shows and search engines skip it.
