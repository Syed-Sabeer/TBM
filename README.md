# TBM — Tote Bag Market (storefront + customer dashboard)

Static front-end for a wholesale-only bag supplier. No build step — open `index.html`
or serve the folder from Laragon at `http://tbm.test/`.

## Demo logins

**Storefront / customer dashboard**

| | |
|---|---|
| URL | `/` |
| Email | `customer@gmail.com` |
| Password | `adminadmin` |

**Admin / back office**

| | |
|---|---|
| URL | `/admin/login` |
| Email | `admin@gmail.com` |
| Password | `adminadmin` |

The admin URL is a folder (`admin/login/index.html`), so it needs to be served by
Laragon — open `http://tbm.test/admin/login`, not the file directly.

Signs you in as **Alex Morgan**, Purchasing Manager at **Northline Supply Co.**,
on the **Tier B — Preferred** rate card, and lands on the dashboard. The login page
also has A / B / C buttons that sign the same account in on a different contract rate,
so you can watch the entire catalog and dashboard reprice.

## Storefront pages

| File | What it is |
|---|---|
| `index.html` | Home — hero, categories, "price after login" explainer, bestsellers, live stock feed, item-number mapping, decoration, sustainability, warehouses, story |
| `shop.html` | Catalog listing with working filters (category, material, colour, programme, warehouse, MOQ), sort, chips, pagination. Reads/writes `?cat=`, `?flag=`, `?stock=`, `?q=` |
| `product.html` | Item detail — `?sku=DS4545`. Gallery with colourway switching, size + decoration pickers, quantity breaks, price gate, warehouse stock, 5 tabs, related items |
| `cart.html` | Order builder — line editing, PO number, notes, summary |
| `checkout.html` | Contact → ship-to → fulfilment → terms → submit. **No payment merchant.** Ends in an order acknowledgement |
| `login.html` | Sign in with the credentials above. Wrong password is rejected. Rate-card preview buttons below the form |
| `register.html` | Wholesale application — company, tax/terms, address, primary contact, additional logins |
| `customization.html` | Decoration — four methods compared, the four-step process, imprint areas by body, artwork specs and templates, lead times, samples, FAQ, quote/proof form |
| `story.html` | Our story — origin, by-the-numbers, 13-year timeline, how we work, the team, facilities, careers |
| `sustainability.html` | Certifications with exact scope, every material and the claim you can make about it, supply chain and audits, packaging roadmap, a "what we do not claim" section, document library |
| `contact.html` | Four department cards, routed contact form, opening hours, the three locations with dock hours, FAQ |

## Customer dashboard (after login)

| File | What it is |
|---|---|
| `account.html` | Overview — spend hero figure, KPI tiles with sparklines, spend-by-month chart (spend / pieces / orders), recent orders, top items, open orders, one-click reorder of what they buy most |
| `account-orders.html` | Shared order history — every order by every login on the account. Filters by period, status, buyer, warehouse, free text. CSV export |
| `account-order.html` | One order — `?id=TBM-2026-4085`. Status timeline, lines with customer item numbers, documents, ship-to, billing, reorder |
| `account-reports.html` | **Item-wise / colour-wise / month-wise** reporting, plus by category, buyer, warehouse and decoration. Chart + full table + CSV export, filtered by period / buyer / warehouse / category |
| `account-pricing.html` | The account's own rate card across the whole catalog, price at every quantity break, searchable, CSV download |
| `account-users.html` | Company logins, permissions (Admin / Buyer / View only), who is buying, invite a user |
| `account-addresses.html` | Ship-to addresses, stocking warehouses, default fulfilment, request your own stocking location |
| `account-settings.html` | Company profile, resale certificate, terms & credit, notifications, security |

Every dashboard page redirects to `login.html?next=…` when signed out.

## Admin / back office

Served from `/admin/<page>/`, its own shell — no storefront header or footer, and no
link to it from the public site. Signed out, every page redirects to `/admin/login`.

| URL | What it is |
|---|---|
| `/admin/login` | Staff sign-in. Rejects a wrong password |
| `/admin/dashboard` | Revenue hero + KPIs, revenue-by-month chart, the confirmation queue, low-cover stock, this morning's import status, activity feed, top accounts and items |
| `/admin/orders` | Every order from every account. Filters, bulk status changes, CSV export with both SKUs |
| `/admin/order` | One order — `?id=`. Lines with parent SKU *and* item number, margin per line, allocation across warehouses, internal notes, invoice, credit warning |
| `/admin/customers` | All accounts, with a pending-approval queue that assigns a rep and a tier on approval |
| `/admin/customer` | One account — `?id=`. Overview, **rate card** (tier + editable per-item overrides + live price preview + margin check), logins, orders, settings |
| `/admin/products` | Parent SKU ↔ customer item number mapping, unmapped mill references held back, cost and sold-volume per item |
| `/admin/product` | One item — `?sku=`. Identity (both SKUs), colourways, stock by warehouse, price by tier, who buys it |
| `/admin/pricing` | **Who pays what** — one item, every account, the price each one sees. Plus tiers, quantity breaks, all overrides and margin policy |
| `/admin/inventory` | Stock by item and warehouse with inline correction, reasons, weeks of cover, reorder |
| `/admin/warehouses` | Locations, allocation rules, and a working **create a warehouse** form (including customer consignment) |
| `/admin/import` | **The daily supplier sheet.** Four-step wizard: source → column mapping → preview → apply. Plus scheduled feeds and rollback history |
| `/admin/reports` | Eight dimensions across the whole book: month, item, colour, category, account, rep, warehouse, decoration — with margin |
| `/admin/settings` | Staff and roles, document numbering (with the parent-SKU-on-documents switch), storefront rules, integrations, audit log |

### The import wizard

`/admin/import` is the piece that matches the daily spreadsheet in the brief. It reads a
mill sheet whose columns are the mill's own names (`MILL_REF`, `LOT_QTY`, `WH`, `FOB_USD`),
maps them to TBM fields, resolves each parent SKU to a customer item number, and shows a
row-by-row preview of what will change — increases in green, decreases in red, and rows it
cannot match flagged rather than guessed. Unmatched rows can be mapped inline. Applying it
writes the new quantities into the live catalog, so the storefront reflects them immediately.

Costs from that sheet are stored for margin reporting and never become a customer price.
Selling prices live only in the rate cards, which a stock import never touches.

## Assets

```
assets/css/style.css   design tokens + all components + dashboard + charts
assets/js/bags.js      SVG bag illustration engine (10 shapes × 18 colourways)
assets/js/data.js      catalog, categories, warehouses, price tiers
assets/js/app.js       header/footer shell, auth gate, cart, shared renderers
assets/js/account.js   company data, 26 months of order history, aggregations, charts
assets/css/admin.css   back-office shell (sidebar, dense tables, wizard, toggles)
assets/js/admin-data.js 11 customer accounts, ~230 orders, overrides, import sheet
assets/js/admin.js     admin shell, auth guard, chart helpers
```

The four content pages above replace the old `index.html#customization` style anchors — the homepage sections stay as teasers and link through.

Charts are hand-built inline SVG — no library, no CDN. Single-series magnitude work in
one validated green (`#0E7A4A`), hairline gridlines, 24px bars with a 4px cap, one
direct label on the peak, hover tooltip on every mark, and a table view under every
chart so nothing is locked behind colour.

## The concepts already wired in

**Price only after login.** `TBM.priceHTML()` in `app.js` is the single place the
storefront decides whether a number may be rendered. Logged out, every price on
every page becomes a "Log in for pricing" link. There is no price in the HTML at all.

**Different rates for different customers.** `data.js` holds a `base` price per item;
the shown price is `base × quantityBreakFactor × tierFactor`, where `tierFactor` comes
from the signed-in account. Tiers A / B / C are defined in `TBMData.TIERS`. Sign in as
any demo account and switch tier from the account menu to see the whole site reprice.

**Parent SKU hidden, customer item number shown.** Each product carries both
`parentSku` (e.g. `BPK18`, mill-side, used for POs) and `sku` (e.g. `DS4500`, what the
buyer sees). Only `sku` is rendered — listing, PDP, cart, checkout, packing-slip copy.
The home page has a live demo of the mapping.

**One company, many logins, shared history.** Copy and UI assume this throughout
(checkout contact block, register "additional logins" section, account menu).

**Warehouses.** `TBMData.WAREHOUSES` drives the stock tables, the shop filter and the
checkout "ship from" picker, including a "split across warehouses" option.

**Daily stock feed.** The home page inventory panel and the PDP warehouse table both
read `product.stock` — the shape your supplier sheet import would populate.

**Checkout without a merchant.** No card fields anywhere. The final step is
"Submit order for confirmation" with payment terms (Net 30 / wire / prepay) chosen,
and the confirmation explicitly states nothing was charged.

## Prototype state

Auth and cart are held in `localStorage` (`tbm.account`, `tbm.cart`) purely so the
gate is demonstrable. Replace with sessions + a `carts` table when this becomes PHP.

Order history is generated deterministically from a seed — 26 months in `account.js` for
the one customer account, and ~230 orders across eleven accounts in `admin-data.js` — so
the numbers are stable across reloads and pages. `DEMAND_SCALE` in `admin-data.js` grosses
the modelled accounts up to a full customer base so weeks-of-cover reads sensibly; replace
it with real shipped quantities. Historical
lines are priced at the account's *current* rate card so switching tier re-prices the
whole dashboard and the effect is visible; a production build stores the unit price
agreed at the time the order was placed.

Bag images are generated SVG, not photography — drop real product shots in by
replacing the `TBMBags.figure()` calls with `<img>` tags; every call site already
passes the product and colour.

## Converting to PHP

`TBM.renderHeader()` / `TBM.renderFooter()` inject the shell from `app.js` so there is
exactly one copy of it. Those two calls are the natural seam for
`include 'partials/header.php'`.

## Demo logins

| Rate card | Effect |
|---|---|
| Tier A — Distributor | −14% off the standard card |
| Tier B — Preferred | −6% (the demo account's card) |
| Tier C — Standard | list rate |

The account menu in the header also has a tier switcher, so you can flip between them
without signing out.

## Credit

Footer carries "Powered by [Deveon Inc](https://deveoninc.com/)" on every page.
