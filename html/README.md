# Static prototype — archived

This folder is the original HTML/CSS/JS prototype of the TBM storefront, customer dashboard
and back office. It is kept for reference only.

**The live application is the Laravel app in the parent folder.** All work happens there now.
Nothing in here is served, built, or linked from the Laravel application, and changes made in
here have no effect on the running site.

## What was carried over

| Prototype | Where it lives now |
|---|---|
| `index.html`, `shop.html`, `product.html` … | `resources/views/pages/`, `shop/`, `product/` |
| `account*.html` | `resources/views/account/` |
| `admin/*/index.html` | `resources/views/admin/` |
| `assets/css/style.css`, `admin.css` | `public/assets/css/` (plus `app.css` for the Blade-only classes) |
| `assets/js/bags.js` | `app/Services/Rendering/BagRenderer.php` — ported to PHP |
| `assets/js/data.js` | `database/data/catalogue.php` + the migrations |
| `assets/js/admin-data.js` | `database/data/accounts.php` + `OrderSeeder` |
| `assets/js/app.js`, `account.js`, `admin.js` | `public/assets/js/app.js` and `admin.js`, rewritten for server-rendered pages |
| `.htaccess`, `CPANEL-DEPLOYMENT.md` | Superseded — Laravel serves from `public/`, which has its own `.htaccess` |

## Why it is still here

The prototype is the design reference: the page layouts, spacing and component look were
settled here first and the Blade templates reproduce them. If a Laravel page ever looks wrong,
the matching file in here is the intended result.

It can be deleted once the Laravel app is signed off.
