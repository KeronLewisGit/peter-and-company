# Hadeed Motors

Website and sales desk for Hadeed Motors Ltd., the Nissan and Suzuki dealer on Old Parham Road, St John's, Antigua and Barbuda (since 1972).

## What it does

- **Public site.** Home, the new Nissan and Suzuki line-up with a page per model (trims and features from the dealership's own model pages), pre-owned stock with filters (make, body type, price, year, transmission) and photo galleries, finance with Finance & Development Co. (FDC), service and parts, trade-in, about, group companies, contact and hours, privacy notice, photo credits and a site map. Mobile-first, light and dark themes, and every page and filter works without JavaScript. Links from the old single-page site (`/index.html?page=vehicle&model=jimny` and the rest) redirect to the new pages.
- **Five lead flows, each with a reference number and an email confirmation.**
  - Test drive (`TD-26-00042`): a car from stock or the line-up, a weekday and a time; slots fill up (2 per slot by default) and close an hour before closing time.
  - Service booking (`SV-…`): licence plate, odometer, service type, drop-off day and time. The form shows how many places are left each day and refuses a full day (daily capacity is set in Settings; claims run under a lock so two people can't take the last place).
  - Parts request (`PT-…`): VIN (checked: 17 characters, never I, O or Q), the part, and an optional photo.
  - Trade-in valuation (`TI-…`): the car, its condition and up to 6 photos.
  - Finance pre-qualification (`FA-…`): residency, employment, income band, deposit, the car, optional ID and pay slips, and an explicit consent checkbox. It never asks for bank or card numbers and refuses them if sent.
  - Plus a contact form (`EN-…`). All uploads go to the private disk, never the public web root.
- **Loan estimator.** A clearly labelled estimate on `/finance` and on each priced car. It stays hidden until an administrator enters FDC's indicative rate in Settings. The browser and the server use the same formula (`resources/js/app.js` and `App\Support\LoanEstimator`), and the form also works without JavaScript.
- **Sales desk** at `/admin`, with four roles:
  - **Admin**: everything except opening finance documents.
  - **Sales**: test drives, trade-ins, enquiries and finance leads (without the income details), plus inventory.
  - **Service**: service bookings, parts and enquiries, plus the workshop calendar.
  - **Finance**: finance applications only, and the only role that can open ID and pay slips. Every time a document is opened, it is logged on the lead's timeline.

  The dashboard leads with one number: leads nobody has called for more than 2 hours. It also shows trend tiles, the median time to first call, leads per day, a breakdown by type and the pipeline. The unified inbox has filters (type, status, assignee, overdue), search (press `/`) and CSV export. Each lead page has the status (new → contacted → appointment → negotiating → sold / lost), the assignee, the appointment, a notes timeline and one-tap call, WhatsApp or email. There is also a month calendar of workshop bookings against capacity, the finance pipeline board, inventory with multi-photo upload (resized to WebP by GD), cover photo, available / reserved / sold and featured flags, staff management and settings.

## Requirements

PHP 8.3 or 8.4 with GD, Composer 2, Node 20+ for building assets, and SQLite locally or MySQL in production.

## Local setup

```sh
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seeder=DemoSeeder   # demo staff, 9 sample vehicles, ~120 leads over 30 days
npm install && npm run build
php artisan serve --port=8105
```

Demo logins all use the password `password`: `admin@hadeed.test`, `sales@hadeed.test`, `service@hadeed.test` and `finance@hadeed.test`. Every demo vehicle is marked "Sample listing" and shows "Call for price". No prices, rates or stock levels are invented.

## Checks

```sh
php artisan test                 # feature tests for every flow, roles, document access and capacity
vendor/bin/phpstan analyse       # Larastan level 6, analysed as PHP 8.3
vendor/bin/pint --test
```

## Deploying

See [INSTALL-HOSTINGER.md](INSTALL-HOSTINGER.md). `scripts/package-hostinger.sh` builds `dist/hadeed-hostinger.zip`, which extracts straight into `public_html/`, with the app in `hadeed-app/`.

## Before launch: confirm with the client

- **Contact details.** The phone (268-481-2500), email and hours come from hadeedmotors.com, which lists the same number for sales, service and parts. Ask for department extensions and a WhatsApp Business number (`HADEED_WHATSAPP`).
- **The line-up** in `config/hadeed.php`: Frontier, Grand Vitara, Baleno, Fronx, S-Presso, Jimny, S-Cross, XL7 and Carry, taken from the current site's model menu. Trim features are copied from its model pages. Trims that only had placeholder text there are left out.
- **Prices.** Nothing is priced. The old site's 2021 data file had "MinPrice" figures, but they are five years old and have no currency, so they are not used.
- **Finance.** The FDC process and document list come from fdcantigua.com. The indicative rate stays blank until FDC approves a figure for publication.
- **Group companies.** HML Autos Ltd. needs a one-line description and link. Caribbean Premium Motors (BMW) links to its Facebook page.
- **Public holidays** in `hadeed.closed_dates`, for the online booking calendar.
- **Photos.** The showroom photo is the dealership's own (from hadeedmotors.com). The vehicle and Antigua photos are Creative Commons images from Wikimedia Commons, credited on `/photo-credits`. Replace them with photos of real stock and of the showroom.
- **Logo.** The HM monogram uses the red and blue sampled from the current logo. Swap in the original artwork.
- **Privacy notice**: a draft for the client's legal adviser.
