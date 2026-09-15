# TBM — Tote Bag Market

A wholesale (B2B) bag supply platform: public catalogue, per-account pricing behind a login,
ordering on account terms with no payment merchant, and a back office for orders, accounts,
stock and the morning mill sheet.

Laravel 10 · PHP 8.1+ · Spatie laravel-permission v6 · Blade · no build step.

---

## Getting it running

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Point `.env` at a database, then:

```bash
php artisan migrate --seed
php artisan storage:link
```

On Laragon, drop the folder in `www/` and browse to `http://tbm.test` (or whatever the
virtual host is called). The document root must be **`public/`**, not the project root.

### Demo logins

Seeded by `AccountSeeder`. Both use the password `adminadmin` — **change this before the
application touches a real server.**

| | |
|---|---|
| Back office | `admin@gmail.com` → `/admin` |
| Customer | `customer@gmail.com` → `/account` |

The seeded book also contains two accounts still awaiting approval and one on hold, so the
pricing gate and the approval flow can be seen working end to end.

---

## The five rules this is built around

Everything else is plumbing. These are the decisions the code exists to enforce.

### 1. No price is visible until an account is approved

One gate, asked in one place. `User::canSeePricing()` is the check; the `<x-price>` Blade
component is the only thing in the application that renders a figure on the storefront; the
`see-pricing` Gate covers everything else. No view formats money by hand, so there is no page
where a price can leak because somebody forgot a condition.

A signed-out visitor sees the catalogue, the specifications and the live warehouse
quantities — just never a number. That is deliberate: stock is the first thing a wholesale
buyer asks, and hiding it costs enquiries.

### 2. Different accounts pay different prices for the same item

```
unit price = base price
           × quantity-break factor
           × the account's tier factor
           × any factor negotiated on that item for that account
```

`PricingService` is the only class in the codebase that multiplies money. Tiers live in
`price_tiers`; per-item concessions live in `price_overrides` and are stored as a **multiplier,
not a fixed price**, so a later change to the underlying card still flows through and the
concession stays a concession.

Quantity breaks are config, not code — `config/tbm.php`.

### 3. The mill reference never reaches a customer

Two identifiers on every item, deliberately unrelated:

| | |
|---|---|
| `parent_sku` | The mill's own code (`BPK18`). Purchase orders and pick lists only. |
| `sku` | The customer-facing item number (`DS4500`). Site, packing slip, invoice. |

Re-source an item from a different mill and only `parent_sku` moves — every historical
document still reprints correctly, because order lines freeze both.

`parent_sku` is in `$hidden` on the model, the customer search cannot match on it, and
`SkuSeparationTest` checks every customer-facing surface including the invoice and the
packing slip. The pick list is the one document that leads with it, and it is staff-only.

### 4. An order belongs to the company, not to the person

One company, many logins, one shared order history. Any contact with the Buyer or Admin role
can place an order; everybody on the account sees all of them. `OrderPolicy` scopes on
`company_id`, never on `placed_by_id`.

Roles inside an account (`customer-admin`, `customer-buyer`, `customer-viewer`) decide what a
person may **do**. They never affect what the company is **charged** — price comes from the
account.

### 5. Money and addresses are frozen when an order is placed

An order captures its own totals and a JSON snapshot of the ship-to address. Change a rate
card, rename a product or edit an address afterwards and no historical invoice moves.

---

## The morning sheet

The mill sends a daily CSV of quantity and cost, keyed on its own reference. `StockImportService`
runs it in three deliberate steps:

1. **Stage** — parse the file, nothing written.
2. **Preview** — resolve every line against the catalogue and show what will change,
   including the lines that matched nothing. Still nothing written.
3. **Apply** — write the stock figures. This is the step that carries the `imports.apply`
   permission, and each figure leaves a `StockMovement` recording what it replaced, which is
   what makes a run reversible.

A wrong column map is therefore a wasted minute, not a wrong storefront.

`php artisan tbm:import-inbox` picks up anything waiting in `storage/app/imports/inbox`,
stages and previews it, and stops. Applying stays a human decision, because a mill occasionally
sends a truncated file and an unattended apply would publish an empty warehouse.

---

## Layout

```
app/
  Enums/              AccountStatus, OrderStatus, ImportStatus, roles — each with label()
  Models/             17 Eloquent models
  Policies/           Order, Company, Product, Address, StockImport, User
  Services/
    Pricing/          PricingService — the only place a price is calculated
    Cart/             Session basket; stores selections only, never money
    Inventory/        Every stock write, each leaving a movement
    Orders/           Basket → order, and the status transitions
    Imports/          Stage / preview / apply for the mill sheet
    Reporting/        By month, item, colour, category, user, warehouse
    Rendering/        BagRenderer — SVG product illustrations, drawn in code
  Support/            Money and Icons helpers
database/
  data/               catalogue.php and accounts.php — seed data as plain arrays
  seeders/            Roles, tiers, catalogue, accounts, 26 months of orders
resources/views/
  layouts/            storefront · account · admin · auth
  components/         price, product-card, column-chart, bar-list, stat, icon …
  documents/          invoice · packing slip (pick list lives under admin/)
```

### Illustrations

There is no photography yet, and a catalogue needs a picture of every item in every colour.
`BagRenderer` draws them: ten shapes, any colourway from the database, as inline SVG. Adding a
colour in the back office puts it on the storefront. When real photography arrives, replace the
call sites with `<img>` — nothing else depends on it.

---

## Permissions

Two families in one table, kept apart by naming.

**Staff roles** are job titles: `owner`, `account-manager`, `inventory`, `customer-care`. The
permissions behind each are defined once in `RoleSeeder::STAFF_MATRIX`, not ticked per person.
`owner` is granted everything through a `Gate::before`, so a permission added later never has to
be remembered.

**Customer roles** carry no back-office permissions at all. What a customer may do is decided by
the policies, against their own company — so granting a staff permission can never widen what a
customer can reach.

---

## Tests

```bash
php artisan test
```

Four suites, covering the rules above rather than the framework:

| | |
|---|---|
| `PricingGateTest` | No price for a signed-out visitor, a pending account, a held account or a lapsed certificate — checked in rendered HTML, because that is where a leak would happen. |
| `PricingServiceTest` | The formula, with every figure worked out by hand in the assertion. |
| `SkuSeparationTest` | The mill reference on every customer surface, including invoice and packing slip. |
| `SharedOrderHistoryTest` | Colleagues see each other's orders; another company sees nothing. |
| `AuthenticationTest` | Both doors, deactivated logins, and registration landing on a pending account that still cannot see a price. |

---

## Before this goes live

- [ ] **Change the seeded passwords.** `AccountSeeder::DEMO_PASSWORD` is `adminadmin`.
- [ ] **Replace the content-page copy.** Every factual claim on `/our-story` and
      `/sustainability` — certifications, audit scope, square footage, founding dates — is
      placeholder text and is marked as such in the Blade comments. An unsubstantiated GOTS or
      GRS claim is a legal problem, not a marketing one; have whoever holds the certificates
      check that page.
- [ ] Replace the company details in `config/tbm.php` (address, phone, email addresses).
- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`.
- [ ] Wire a mailer — the contact form currently logs the enquiry to the activity log and
      acknowledges it, rather than pretending to send mail it cannot send.
- [ ] Decide whether `TBM_DEMAND_SCALE` (default 12) should be 1. It grosses the seeded sample
      up to a realistic customer base so weeks-of-cover reads true; with real order volume it
      should be 1.
- [ ] Review the margin floor (`TBM_MARGIN_FLOOR`, default 28%).

---

Powered by [Deveon Inc](https://deveoninc.com/).
