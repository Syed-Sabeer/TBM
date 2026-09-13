/* ==========================================================================
   TBM — Storefront shell: header, footer, auth gate, cart, shared renderers
   ========================================================================== */
(function (w, d) {
  'use strict';

  /* ---------- Icons ----------------------------------------------------- */
  var I = {
    bag:      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8h14l1.2 11.2A1.5 1.5 0 0 1 18.7 21H5.3a1.5 1.5 0 0 1-1.5-1.8Z"/><path d="M8.5 8V6a3.5 3.5 0 0 1 7 0v2"/></svg>',
    user:     '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.6"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0"/></svg>',
    search:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><circle cx="11" cy="11" r="6.4"/><path d="m16 16 4 4"/></svg>',
    lock:     '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4.5" y="10.5" width="15" height="10" rx="2.2"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/></svg>',
    arrow:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>',
    chev:     '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>',
    chevD:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>',
    check:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7"/></svg>',
    x:        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>',
    truck:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 6.5h10v10h-10z"/><path d="M12.5 10h4l3 3v3.5h-7z"/><circle cx="6.5" cy="18.5" r="1.8"/><circle cx="16.5" cy="18.5" r="1.8"/></svg>',
    leaf:     '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M20 4S8.5 3 5.5 9.5 8 20 8 20 19 18 20 4Z"/><path d="M8 20C9.5 14.5 13 10.5 17 8"/></svg>',
    box:      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 8 4.2v9.6L12 21l-8-4.2V7.2Z"/><path d="m4 7.2 8 4.3 8-4.3M12 21v-9.5"/></svg>',
    printer:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M7 9V3.5h10V9"/><rect x="3.5" y="9" width="17" height="7.5" rx="2"/><path d="M7 14h10v6.5H7z"/></svg>',
    thread:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19c4-1 5-4 5-7s1-6 5-7"/><path d="M20 5c-4 1-5 4-5 7s-1 6-5 7"/><circle cx="4" cy="19" r="1.4"/><circle cx="20" cy="5" r="1.4"/></svg>',
    palette:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a9 9 0 1 0 0 18c1.4 0 2-1 2-2s-.8-1.6-.8-2.4c0-.9.8-1.6 1.8-1.6H17a4 4 0 0 0 4-4c0-4.4-4-8-9-8Z"/><circle cx="7.5" cy="12" r="1.2" fill="currentColor" stroke="none"/><circle cx="10" cy="7.8" r="1.2" fill="currentColor" stroke="none"/><circle cx="15" cy="8.2" r="1.2" fill="currentColor" stroke="none"/></svg>',
    shield:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 5 6v6c0 4.4 3 7.6 7 9 4-1.4 7-4.6 7-9V6Z"/><path d="m9 12 2 2 4-4"/></svg>',
    chart:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg>',
    tag:      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M11 3H4v7l10 10 7-7Z"/><circle cx="7.8" cy="7" r="1.3"/></svg>',
    users:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.2"/><path d="M3 19a6 6 0 0 1 12 0"/><path d="M16 5.3a3.2 3.2 0 0 1 0 5.4M17.5 19a6 6 0 0 0-1.6-4.1"/></svg>',
    clock:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/></svg>',
    doc:      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M13 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V9Z"/><path d="M13 3v6h6M9 13h6M9 17h4"/></svg>',
    pin:      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11Z"/><circle cx="12" cy="10" r="2.6"/></svg>',
    phone:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 3.5h3l1.5 4-2 1.5a12 12 0 0 0 6 6L16.5 13l4 1.5v3a2 2 0 0 1-2.2 2C10.6 19 5 13.4 4.5 5.7a2 2 0 0 1 2-2.2Z"/></svg>',
    mail:     '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 6.5 8.5 6 8.5-6"/></svg>',
    grid:     '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/></svg>',
    burger:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>',
    logout:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M10 4.5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h4"/><path d="M15 8.5 19 12l-4 3.5M19 12H9.5"/></svg>',
    zoom:     '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><circle cx="11" cy="11" r="6.4"/><path d="m16 16 4 4M11 8.6v4.8M8.6 11h4.8"/></svg>',
    refresh:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M20 11a8 8 0 1 0-.7 4.5"/><path d="M20 4.5V11h-6.5"/></svg>',
    star:     '<svg viewBox="0 0 24 24" fill="currentColor"><path d="m12 3.5 2.6 5.3 5.9.9-4.3 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8L3.5 9.7l5.9-.9Z"/></svg>',
    sparkle:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9Z"/><path d="M18.5 16.5 19.4 19l2.5.9-2.5.9-.9 2.5"/></svg>'
  };

  /* ---------- Storage --------------------------------------------------- */
  function read(k, fb) {
    try { var v = localStorage.getItem(k); return v ? JSON.parse(v) : fb; }
    catch (e) { return fb; }
  }
  function write(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} }

  var AUTH_KEY = 'tbm.account', CART_KEY = 'tbm.cart';

  function account() { return read(AUTH_KEY, null); }
  function isIn() { return !!account(); }
  function tier() { var a = account(); return a ? a.tier : 'C'; }
  function signIn(acc) { write(AUTH_KEY, acc); }
  function signOut() { try { localStorage.removeItem(AUTH_KEY); } catch (e) {} }
  function setTier(t) { var a = account(); if (a) { a.tier = t; write(AUTH_KEY, a); } }

  function cart() { return read(CART_KEY, []); }
  function saveCart(c) { write(CART_KEY, c); paintCount(); }
  function cartCount() { return cart().reduce(function (s, l) { return s + l.qty; }, 0); }
  function addToCart(line) {
    var c = cart();
    var key = function (l) { return l.sku + '|' + l.color + '|' + l.size + '|' + (l.imprint || ''); };
    var hit = c.filter(function (l) { return key(l) === key(line); })[0];
    if (hit) hit.qty += line.qty; else c.push(line);
    saveCart(c);
  }

  /* ---------- Money ----------------------------------------------------- */
  function usd(n) {
    return '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
  function num(n) { return Number(n).toLocaleString('en-US'); }

  /* ---------- Price gate ------------------------------------------------ */
  /* The single place the storefront decides whether a price may be shown. */
  function priceHTML(product, qty, opts) {
    opts = opts || {};
    if (!isIn()) {
      return '<a class="price-lock" href="login.html?next=' + encodeURIComponent(location.pathname.split('/').pop() + location.search) + '">' +
        I.lock + 'Log in for pricing</a>';
    }
    var t = tier();
    var p = TBMData.priceAt(product, qty || product.moq, t);
    return '<span class="price-val"><b>' + usd(p) + '</b><span>/ ea</span></span>' +
      (opts.tierTag === false ? '' : '<div class="tier-tag">' + TBMData.TIERS[t].name.split('—')[0].trim() + ' price</div>');
  }

  /* ---------- Product card ---------------------------------------------- */
  function flagHTML(p) {
    var out = '';
    p.flags.slice(0, 2).forEach(function (f) {
      var m = TBMData.FLAG_LABEL[f]; if (!m) return;
      out += '<span class="badge ' + m.cls + '">' + m.text + '</span>';
    });
    return out;
  }
  function stockBadge(p) {
    var t = TBMData.totalStock(p);
    if (t > 20000) return '<span class="badge badge-ok"><i class="dot"></i>In stock</span>';
    if (t > 3000)  return '<span class="badge badge-ok"><i class="dot"></i>' + num(t) + ' available</span>';
    return '<span class="badge badge-warn"><i class="dot"></i>Low — ' + num(t) + ' left</span>';
  }
  function productCard(p) {
    var col = p.colors[0];
    return '<article class="prod-card" data-id="' + p.id + '">' +
      '<a class="prod-media" href="product.html?sku=' + p.sku + '">' +
        '<div class="prod-flags">' + flagHTML(p) + '</div>' +
        TBMBags.figure(p.shape, col, { alt: p.name }) +
        '<div class="prod-quick"><span class="btn btn-outline btn-sm btn-block">View item</span></div>' +
      '</a>' +
      '<div class="prod-body">' +
        '<div class="prod-sku">' + p.sku + '</div>' +
        '<a class="prod-title" href="product.html?sku=' + p.sku + '">' + p.name + '</a>' +
        '<div class="prod-meta"><span>' + p.material + ' &middot; ' + p.sizes[0] + '</span></div>' +
        '<div class="swatches">' +
          p.colors.slice(0, 5).map(function (k, i) {
            var c = TBMBags.color(k);
            return '<span class="swatch' + (i === 0 ? ' is-active' : '') + '" data-color="' + k + '" title="' + c.name + '" style="background:' + c.body + '"></span>';
          }).join('') +
          (p.colors.length > 5 ? '<span class="swatch-more">+' + (p.colors.length - 5) + '</span>' : '') +
        '</div>' +
        '<div class="prod-foot">' +
          '<div>' + priceHTML(p, p.moq) + '</div>' +
          '<div style="text-align:right">' + stockBadge(p) + '<div class="small muted" style="margin-top:5px">MOQ ' + num(p.moq) + '</div></div>' +
        '</div>' +
      '</div>' +
    '</article>';
  }

  /* Swatch hover on cards swaps the illustration */
  function wireCardSwatches(root) {
    (root || d).querySelectorAll('.prod-card').forEach(function (card) {
      var p = TBMData.byId(card.dataset.id); if (!p) return;
      card.querySelectorAll('.swatch[data-color]').forEach(function (s) {
        s.addEventListener('mouseenter', function () {
          card.querySelectorAll('.swatch').forEach(function (x) { x.classList.remove('is-active'); });
          s.classList.add('is-active');
          card.querySelector('.bag-img').innerHTML = TBMBags.svg(p.shape, s.dataset.color, { alt: p.name });
        });
      });
    });
  }

  /* ---------- Header ---------------------------------------------------- */
  var NAV_GROUPS = ['Totes', 'Drawstring', 'Specialty'];

  function megaHTML() {
    var cols = NAV_GROUPS.map(function (g) {
      var items = TBMData.CATEGORIES.filter(function (c) { return c.group === g; });
      return '<div><h5>' + g + '</h5><ul>' + items.map(function (c) {
        return '<li><a href="shop.html?cat=' + c.slug + '">' + c.name + '<span>' + TBMData.byCat(c.slug).length + '</span></a></li>';
      }).join('') + '</ul></div>';
    }).join('');
    var shopBy = '<div><h5>Shop by</h5><ul>' +
      '<li><a href="shop.html?flag=bestseller">Bestsellers</a></li>' +
      '<li><a href="shop.html?flag=new">New this season</a></li>' +
      '<li><a href="shop.html?flag=eco">Certified organic &amp; recycled</a></li>' +
      '<li><a href="shop.html?flag=value">Lowest landed cost</a></li>' +
      '<li><a href="shop.html?stock=LAX">Ready in Los Angeles</a></li>' +
      '<li><a href="shop.html?stock=EWR">Ready in New Jersey</a></li>' +
      '<li><a href="shop.html">Full catalog</a></li>' +
      '</ul></div>';
    var promo = '<div class="mega-promo">' +
      '<div><h4>Sample packs</h4><p>Five bags, your choice of construction, shipped free to approved accounts. Touch the goods before you commit a container.</p></div>' +
      '<a class="btn btn-light btn-sm" href="register.html">Request samples ' + I.arrow + '</a></div>';
    return '<div class="mega" id="mega"><div class="container"><div class="mega-inner">' + cols + shopBy + promo + '</div></div></div>';
  }

  function logoHTML(cls) {
    return '<a class="logo ' + (cls || '') + '" href="index.html" aria-label="TBM — Tote Bag Market">' +
      '<svg class="logo-mark" viewBox="0 0 40 40" fill="none" aria-hidden="true">' +
        '<rect width="40" height="40" rx="10" fill="#1C3D2E"/>' +
        '<path d="M12 15h16l1 13.5a1.2 1.2 0 0 1-1.2 1.3H12.2a1.2 1.2 0 0 1-1.2-1.3Z" fill="#F7F4ED"/>' +
        '<path d="M16 15.5c0-4 8-4 8 0" stroke="#B9793C" stroke-width="2.1" stroke-linecap="round"/>' +
        '<path d="M15 20.5h10" stroke="#1C3D2E" stroke-width="1.8" stroke-linecap="round"/>' +
      '</svg>' +
      '<span class="logo-text"><b>TBM</b><small>Tote Bag Market</small></span></a>';
  }

  function headerHTML(current) {
    var a = account();
    var nav = [
      { key: 'bags', label: 'Bags', mega: true },
      { key: 'wholesale', label: 'Wholesale', href: 'register.html' },
      { key: 'custom', label: 'Customization', href: 'customization.html' },
      { key: 'story', label: 'Our Story', href: 'story.html' },
      { key: 'sustain', label: 'Sustainability', href: 'sustainability.html' },
      { key: 'contact', label: 'Contact', href: 'contact.html' }
    ];
    var navHTML = nav.map(function (n) {
      if (n.mega) {
        return '<li class="has-mega"><button type="button" aria-expanded="false">' + n.label +
          '<span class="nav-caret">' + I.chevD + '</span></button></li>';
      }
      return '<li><a class="' + (current === n.key ? 'is-current' : '') + '" href="' + n.href + '">' + n.label + '</a></li>';
    }).join('');

    var acct = a
      ? '<div class="acct-wrap">' +
          '<button class="icon-btn" id="acctBtn" aria-label="Account" aria-expanded="false">' + I.user + '</button>' +
          '<div class="acct-menu" id="acctMenu">' +
            '<div class="head"><b>' + a.name + '</b><span>' + a.company + ' &middot; ' + TBMData.TIERS[a.tier].name + '</span></div>' +
            '<a href="account.html"><span>' + I.grid + '</span>Dashboard</a>' +
            '<a href="account-orders.html"><span>' + I.doc + '</span>Order history</a>' +
            '<a href="account-reports.html"><span>' + I.chart + '</span>Purchase reports</a>' +
            '<a href="account-users.html"><span>' + I.users + '</span>Company users</a>' +
            '<a href="account-pricing.html"><span>' + I.tag + '</span>My price list</a>' +
            '<a href="account-addresses.html"><span>' + I.pin + '</span>Addresses</a>' +
            '<div class="tier-switch"><span class="label">Demo: switch price tier</span><div class="tier-btns" id="tierBtns">' +
              ['A', 'B', 'C'].map(function (t) {
                return '<button type="button" data-tier="' + t + '" class="' + (a.tier === t ? 'is-active' : '') + '">Tier ' + t + '</button>';
              }).join('') +
            '</div><div class="hint">Same catalog, different contract rates.</div></div>' +
            '<button class="mi" id="signOutBtn" type="button"><span>' + I.logout + '</span>Sign out</button>' +
          '</div>' +
        '</div>'
      : '<a class="icon-btn" href="login.html" aria-label="Log in">' + I.user + '</a>';

    return '<div class="announce"><div class="container">' +
        '<div class="announce-items">' +
          '<span>' + I.truck + 'Ships from Los Angeles, Edison NJ &amp; Dallas</span>' +
          '<span>' + I.leaf + 'GOTS &amp; GRS certified programs</span>' +
          '<span>' + I.box + 'Stock updated daily from the mill</span>' +
        '</div>' +
        '<div class="row">' +
          (a ? '<span>Signed in as <a href="#">' + a.company + '</a></span>'
             : '<span>Wholesale only &mdash; <a href="register.html">open an account</a> to see pricing</span>') +
        '</div>' +
      '</div></div>' +

      '<header class="site-header"><div class="container"><div class="header-main">' +
        logoHTML() +
        '<nav aria-label="Main"><ul class="nav">' + navHTML + '</ul></nav>' +
        '<div class="header-actions">' +
          '<a class="icon-btn" href="shop.html" aria-label="Search catalog">' + I.search + '</a>' +
          acct +
          '<a class="icon-btn" href="cart.html" aria-label="Cart">' + I.bag + '<span class="count" id="cartCount">0</span></a>' +
          '<a class="btn btn-primary btn-sm header-cta" href="' + (a ? 'account.html' : 'register.html') + '">' +
            (a ? 'My account' : 'Open an account') + '</a>' +
          '<button class="icon-btn burger" id="burger" aria-label="Menu">' + I.burger + '</button>' +
        '</div>' +
      '</div></div>' + megaHTML() + '</header>' +

      '<div class="mobile-nav" id="mobileNav">' +
        '<div class="row-between">' + logoHTML() + '<button class="icon-btn" id="mobClose" aria-label="Close">' + I.x + '</button></div>' +
        NAV_GROUPS.map(function (g) {
          return '<details><summary>' + g + '<span>' + I.chevD + '</span></summary><ul>' +
            TBMData.CATEGORIES.filter(function (c) { return c.group === g; })
              .map(function (c) { return '<li><a href="shop.html?cat=' + c.slug + '">' + c.name + '</a></li>'; }).join('') +
            '</ul></details>';
        }).join('') +
        '<ul>' +
          '<li><a href="shop.html">Full catalog</a></li>' +
          '<li><a href="register.html">Wholesale account</a></li>' +
          '<li><a href="index.html#customization">Customization</a></li>' +
          '<li><a href="index.html#sustainability">Sustainability</a></li>' +
          '<li><a href="index.html#contact">Contact</a></li>' +
        '</ul>' +
        '<div style="margin-top:24px;display:grid;gap:10px">' +
          (a ? '<a class="btn btn-outline btn-block" href="account.html">My account</a>' : '<a class="btn btn-outline btn-block" href="login.html">Log in</a>') +
          '<a class="btn btn-primary btn-block" href="' + (a ? 'shop.html' : 'register.html') + '">' + (a ? 'Start an order' : 'Open an account') + '</a>' +
        '</div>' +
      '</div>';
  }

  function footerHTML() {
    var col = function (title, links) {
      return '<div><h5>' + title + '</h5><ul>' + links.map(function (l) {
        return '<li><a href="' + l[1] + '">' + l[0] + '</a></li>';
      }).join('') + '</ul></div>';
    };
    return '<footer class="site-footer" id="contact"><div class="container">' +
      '<div class="footer-top">' +
        '<div class="footer-brand">' + logoHTML() +
          '<p>Wholesale bag supply for distributors, promotional agencies, retailers and brands. Stocked in the US, made in audited mills.</p>' +
          '<div class="footer-news">' +
            '<input class="input" type="email" placeholder="Work email" aria-label="Work email">' +
            '<button class="btn btn-accent">Join</button>' +
          '</div>' +
          '<div class="social">' +
            '<a href="#" aria-label="LinkedIn"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M4.98 3.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5ZM3 9.5h4v11H3v-11Zm6.5 0h3.8v1.5h.05c.53-.95 1.83-1.95 3.77-1.95 4.03 0 4.78 2.5 4.78 5.75v5.7h-4v-5.05c0-1.2-.02-2.75-1.7-2.75-1.7 0-1.96 1.3-1.96 2.66v5.14h-4v-11Z"/></svg></a>' +
            '<a href="#" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17" cy="7" r="1.1" fill="currentColor"/></svg></a>' +
            '<a href="#" aria-label="Email">' + I.mail + '</a>' +
          '</div>' +
        '</div>' +
        col('Catalog', [['Cotton totes', 'shop.html?cat=cotton-totes'], ['Canvas totes', 'shop.html?cat=canvas-totes'], ['Organic totes', 'shop.html?cat=organic-totes'], ['Recycled totes', 'shop.html?cat=recycled-totes'], ['Jute &amp; burlap', 'shop.html?cat=jute-totes'], ['Drawstring', 'shop.html?cat=drawstring-backpacks'], ['Specialty bags', 'shop.html?cat=wine-bags']]) +
        col('Wholesale', [['Open an account', 'register.html'], ['Log in', 'login.html'], ['Price tiers &amp; terms', 'register.html#terms'], ['Sample packs', 'customization.html#samples'], ['Warehouses', 'contact.html#locations'], ['Drop shipping', 'contact.html'], ['Purchase orders', 'contact.html']]) +
        col('Company', [['Our story', 'story.html'], ['Sustainability', 'sustainability.html'], ['Certifications', 'sustainability.html#certifications'], ['Customization', 'customization.html'], ['Contact', 'contact.html'], ['Careers', 'story.html#careers']]) +
        '<div><h5>Talk to a rep</h5><ul>' +
          '<li><a href="tel:+18005550123" style="display:flex;gap:10px;align-items:center">' + I.phone + '+1 (800) 555-0123</a></li>' +
          '<li><a href="mailto:sales@tbm.com" style="display:flex;gap:10px;align-items:center">' + I.mail + 'sales@tbm.com</a></li>' +
          '<li style="display:flex;gap:10px;align-items:flex-start;font-size:.875rem">' + I.clock + '<span>Mon–Fri, 7am–5pm PT</span></li>' +
          '<li style="display:flex;gap:10px;align-items:flex-start;font-size:.875rem">' + I.pin + '<span>1420 E 15th St<br>Los Angeles, CA 90021</span></li>' +
        '</ul></div>' +
      '</div>' +
      '<div class="footer-bot">' +
        '<span>&copy; ' + new Date().getFullYear() + ' TBM — Tote Bag Market. Wholesale only. Prices shown to approved accounts.</span>' +
        '<ul><li><a href="#">Terms</a></li><li><a href="#">Privacy</a></li><li><a href="#">Shipping policy</a></li><li><a href="#">Returns</a></li><li><a href="#">Prop 65</a></li></ul>' +
      '</div>' +
      '<div class="footer-credit">' +
        '<span>Powered by <a href="https://deveoninc.com/" target="_blank" rel="noopener">Deveon Inc</a></span>' +
      '</div>' +
    '</div></footer>';
  }

  /* ---------- Mount + wire --------------------------------------------- */
  /* Both are called inline, right after their placeholder element, so the
     shell paints during parse with no flash. Swap for PHP includes later. */
  function renderHeader(current) {
    var el = d.getElementById('site-header');
    if (el) el.innerHTML = headerHTML(current);
  }
  function renderFooter() {
    var el = d.getElementById('site-footer');
    if (el) el.outerHTML = footerHTML();
  }

  function paintCount() {
    var el = d.getElementById('cartCount'); if (!el) return;
    var n = cartCount();
    el.textContent = n > 99 ? '99+' : n;
    el.style.display = n ? 'grid' : 'none';
  }

  function wireHeader() {
    var megaLi = d.querySelector('.nav .has-mega');
    var mega = d.getElementById('mega');
    var header = d.querySelector('.site-header');
    if (megaLi && mega && header) {
      var open = function (v) {
        mega.classList.toggle('is-open', v);
        megaLi.classList.toggle('is-open', v);
        megaLi.querySelector('button').setAttribute('aria-expanded', v ? 'true' : 'false');
      };
      megaLi.querySelector('button').addEventListener('click', function (e) {
        e.stopPropagation(); open(!mega.classList.contains('is-open'));
      });
      header.addEventListener('mouseleave', function () { open(false); });
      d.addEventListener('click', function (e) { if (!mega.contains(e.target)) open(false); });
      d.addEventListener('keydown', function (e) { if (e.key === 'Escape') open(false); });
    }

    var ab = d.getElementById('acctBtn'), am = d.getElementById('acctMenu');
    if (ab && am) {
      ab.addEventListener('click', function (e) {
        e.stopPropagation();
        var v = !am.classList.contains('is-open');
        am.classList.toggle('is-open', v);
        ab.setAttribute('aria-expanded', v ? 'true' : 'false');
      });
      d.addEventListener('click', function (e) { if (!am.contains(e.target)) am.classList.remove('is-open'); });
    }
    var so = d.getElementById('signOutBtn');
    if (so) so.addEventListener('click', function () { signOut(); location.reload(); });
    var tb = d.getElementById('tierBtns');
    if (tb) tb.addEventListener('click', function (e) {
      var b = e.target.closest('button[data-tier]'); if (!b) return;
      setTier(b.dataset.tier);
      toast('Switched to ' + TBMData.TIERS[b.dataset.tier].name + '. Prices updated.');
      setTimeout(function () { location.reload(); }, 550);
    });

    var burger = d.getElementById('burger'), mob = d.getElementById('mobileNav'), mc = d.getElementById('mobClose');
    if (burger && mob) {
      burger.addEventListener('click', function () { mob.classList.add('is-open'); d.body.style.overflow = 'hidden'; });
      if (mc) mc.addEventListener('click', function () { mob.classList.remove('is-open'); d.body.style.overflow = ''; });
    }
    paintCount();
  }

  /* ---------- Toast ----------------------------------------------------- */
  function toast(msg) {
    var wrap = d.querySelector('.toast-wrap');
    if (!wrap) { wrap = d.createElement('div'); wrap.className = 'toast-wrap'; d.body.appendChild(wrap); }
    var t = d.createElement('div');
    t.className = 'toast';
    t.innerHTML = I.check + '<span>' + msg + '</span>';
    wrap.appendChild(t);
    setTimeout(function () {
      t.style.transition = 'opacity .3s, transform .3s';
      t.style.opacity = '0'; t.style.transform = 'translateY(8px)';
      setTimeout(function () { t.remove(); }, 320);
    }, 2800);
  }

  /* ---------- Reveal on scroll ------------------------------------------ */
  function wireReveal() {
    var els = d.querySelectorAll('.reveal');
    if (!els.length) return;
    if (!('IntersectionObserver' in w)) { els.forEach(function (e) { e.classList.add('in'); }); return; }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); }
      });
    }, { threshold: .12, rootMargin: '0px 0px -40px' });
    els.forEach(function (e, i) { e.style.transitionDelay = (Math.min(i, 6) * 55) + 'ms'; io.observe(e); });
  }

  /* ---------- Quantity steppers ---------------------------------------- */
  function wireQty(root) {
    (root || d).querySelectorAll('.qty').forEach(function (q) {
      if (q.dataset.wired) return; q.dataset.wired = '1';
      var input = q.querySelector('input');
      var step = parseInt(q.dataset.step || '1', 10);
      var min = parseInt(q.dataset.min || input.min || '1', 10);
      q.addEventListener('click', function (e) {
        var b = e.target.closest('button'); if (!b) return;
        var v = parseInt(input.value || min, 10) || min;
        v += (b.dataset.d === '+' ? step : -step);
        if (v < min) v = min;
        input.value = v;
        input.dispatchEvent(new Event('change', { bubbles: true }));
      });
    });
  }

  function qtyHTML(value, min, step, cls) {
    return '<div class="qty ' + (cls || '') + '" data-min="' + min + '" data-step="' + step + '">' +
      '<button type="button" data-d="-" aria-label="Decrease">&minus;</button>' +
      '<input type="number" value="' + value + '" min="' + min + '" step="' + step + '">' +
      '<button type="button" data-d="+" aria-label="Increase">+</button></div>';
  }

  /* ---------- Query params --------------------------------------------- */
  function qp(name) {
    return new URLSearchParams(location.search).get(name);
  }

  /* ---------- Boot ------------------------------------------------------ */
  d.addEventListener('DOMContentLoaded', function () {
    wireHeader(); wireReveal(); wireQty();
  });

  w.TBM = {
    I: I, usd: usd, num: num, qp: qp, toast: toast,
    account: account, isIn: isIn, tier: tier, signIn: signIn, signOut: signOut, setTier: setTier,
    cart: cart, saveCart: saveCart, addToCart: addToCart, cartCount: cartCount, paintCount: paintCount,
    priceHTML: priceHTML, productCard: productCard, wireCardSwatches: wireCardSwatches,
    flagHTML: flagHTML, stockBadge: stockBadge,
    renderHeader: renderHeader, renderFooter: renderFooter,
    qtyHTML: qtyHTML, wireQty: wireQty, logoHTML: logoHTML
  };
})(window, document);
