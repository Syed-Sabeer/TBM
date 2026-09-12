/* ==========================================================================
   TBM — Customer dashboard: company data, order history, reports, charts
   Everything here is prototype data generated deterministically from a seed,
   so the numbers are stable across reloads and across pages.
   ========================================================================== */
(function (w, d) {
  'use strict';

  var D = w.TBMData, B = w.TBMBags;

  /* ---------- Seeded RNG so the demo data never shuffles ---------------- */
  function rng(seed) {
    return function () {
      seed |= 0; seed = seed + 0x6D2B79F5 | 0;
      var t = Math.imul(seed ^ seed >>> 15, 1 | seed);
      t = t + Math.imul(t ^ t >>> 7, 61 | t) ^ t;
      return ((t ^ t >>> 14) >>> 0) / 4294967296;
    };
  }
  function pick(r, arr) { return arr[Math.floor(r() * arr.length)]; }
  function between(r, a, b) { return a + Math.floor(r() * (b - a + 1)); }

  /* ---------- The company ----------------------------------------------- */
  var COMPANY = {
    name: 'Northline Supply Co.',
    account: 'TBM-C-10428',
    since: '2021',
    terms: 'Net 30',
    creditLimit: 75000,
    creditUsed: 18420,
    rep: { name: 'Jordan Alvarez', title: 'Account Manager', email: 'jordan@tbm.com', phone: '+1 (800) 555-0123 ext. 218' },
    resaleCert: { number: 'CA-SR-0447-2219', expires: '2027-03-31', status: 'Verified' },
    billing: '410 W 9th St, Suite 1200\nLos Angeles, CA 90015'
  };

  var USERS = [
    { name: 'Alex Morgan',     email: 'customer@gmail.com',            role: 'Purchasing Manager', perm: 'Admin',     init: 'AM', last: 'Today' },
    { name: 'Dana Whitfield',  email: 'dana@northlinesupply.com',      role: 'Senior Buyer',       perm: 'Buyer',     init: 'DW', last: '2 days ago' },
    { name: 'Marcus Reed',     email: 'marcus@northlinesupply.com',    role: 'Warehouse Lead',     perm: 'Buyer',     init: 'MR', last: '6 days ago' },
    { name: 'Priya Raman',     email: 'priya@northlinesupply.com',     role: 'Accounts Payable',   perm: 'View only', init: 'PR', last: '3 weeks ago' },
    { name: 'Sam Okafor',      email: 'sam@northlinesupply.com',       role: 'Account Executive',  perm: 'Buyer',     init: 'SO', last: '5 weeks ago' }
  ];

  var ADDRESSES = [
    { id: 'a1', label: 'Warehouse — receiving dock', co: 'Northline Supply Co.', street: '2200 Meridian Blvd, Bay 7', city: 'Long Beach, CA 90802', def: true,  note: 'Dock hours 7am–3pm. Appointment required over 4 pallets.' },
    { id: 'a2', label: 'Head office',                co: 'Northline Supply Co.', street: '410 W 9th St, Suite 1200',  city: 'Los Angeles, CA 90015', def: false, note: 'Samples and small parcel only.' },
    { id: 'a3', label: 'East coast 3PL',             co: 'Redline Fulfilment',   street: '85 Raritan Center Pkwy',    city: 'Edison, NJ 08837',      def: false, note: 'Reference Northline on the BOL.' },
    { id: 'a4', label: 'Retail — Pasadena',          co: 'Northline Retail',     street: '78 S Lake Ave',             city: 'Pasadena, CA 91101',    def: false, note: 'Residential-rate delivery zone.' }
  ];

  var STATUSES = [
    { key: 'Delivered',            cls: 'st-delivered',  step: 4 },
    { key: 'Shipped',              cls: 'st-shipped',    step: 3 },
    { key: 'In production',        cls: 'st-production', step: 2 },
    { key: 'Pending confirmation', cls: 'st-pending',    step: 1 },
    { key: 'Cancelled',            cls: 'st-cancelled',  step: 0 }
  ];
  function statusOf(k) { return STATUSES.filter(function (s) { return s.key === k; })[0] || STATUSES[3]; }

  /* ---------- Order history --------------------------------------------- */
  /* 26 months of shared company history — every user's orders in one list. */
  var TODAY = new Date(2026, 8, 12);           /* 12 Sep 2026 */
  var DECOR = ['Blank (undecorated)', 'Screen print', 'DTF transfer', 'Embroidery'];

  function buildOrders() {
    var r = rng(20260912);
    var out = [];
    var n = 0;

    for (var back = 25; back >= 0; back--) {
      var base = new Date(TODAY.getFullYear(), TODAY.getMonth() - back, 1);
      var perMonth = between(r, 2, 4);
      /* Q4 and spring run heavier — gives the month chart a real shape */
      var m = base.getMonth();
      if (m === 9 || m === 10) perMonth += 2;
      if (m === 2 || m === 3) perMonth += 1;
      if (back === 0) perMonth = 3;

      for (var i = 0; i < perMonth; i++) {
        n++;
        var day = between(r, 1, back === 0 ? 11 : 27);
        var date = new Date(base.getFullYear(), base.getMonth(), day);
        var user = USERS[between(r, 0, 3)];
        var wh = pick(r, D.WAREHOUSES).code;
        var addr = pick(r, ADDRESSES);

        var status;
        if (back === 0) status = pick(r, ['Pending confirmation', 'In production', 'Shipped']);
        else if (back === 1) status = pick(r, ['Shipped', 'Delivered', 'Delivered', 'In production']);
        else status = r() < 0.05 ? 'Cancelled' : 'Delivered';

        var lineCount = between(r, 1, 4);
        var lines = [], used = {};
        for (var L = 0; L < lineCount; L++) {
          var p = D.PRODUCTS[between(r, 0, D.PRODUCTS.length - 1)];
          if (used[p.sku]) { p = D.PRODUCTS[(between(r, 0, D.PRODUCTS.length - 1) + L * 7) % D.PRODUCTS.length]; }
          used[p.sku] = 1;
          var step = p.moq >= 500 ? 250 : (p.moq >= 250 ? 50 : 25);
          var qty = p.moq * between(r, 1, 6) + step * between(r, 0, 4);
          /* the account grows over the history, so year-on-year reads true */
          var growth = 0.78 + (25 - back) * 0.018;
          qty = Math.max(p.moq, Math.round(qty * growth / step) * step);
          lines.push({
            sku: p.sku, id: p.id, name: p.name, shape: p.shape, cat: p.cat,
            color: p.colors[between(r, 0, p.colors.length - 1)],
            size: p.sizes[between(r, 0, p.sizes.length - 1)],
            imprint: r() < 0.45 ? DECOR[between(r, 1, 3)] : DECOR[0],
            qty: qty
          });
        }

        out.push({
          id: 'TBM-' + date.getFullYear() + '-' + (4000 + n),
          date: date,
          po: 'PO-' + date.getFullYear() + '-' + String(100 + n * 3).padStart(4, '0'),
          user: user.name,
          userInit: user.init,
          warehouse: wh,
          shipTo: addr.label,
          status: status,
          lines: lines
        });
      }
    }
    return out.sort(function (a, b) { return b.date - a.date; });
  }

  var ORDERS = buildOrders();

  /* ---------- Money -------------------------------------------------------
     Historical lines are priced at the account's CURRENT rate card so that
     switching tier re-prices the whole dashboard and the effect of the tier
     system is visible. A production build would store the unit price that
     was agreed at the time the order was placed.                          */
  function unitFor(line, qty) {
    var p = D.byId(line.id);
    return p ? D.priceAt(p, qty || line.qty, w.TBM.tier()) : 0;
  }
  function lineTotal(line) { return unitFor(line) * line.qty; }
  function orderPieces(o) { return o.lines.reduce(function (s, l) { return s + l.qty; }, 0); }
  function orderTotal(o) { return o.lines.reduce(function (s, l) { return s + lineTotal(l); }, 0); }

  function live(list) { return (list || ORDERS).filter(function (o) { return o.status !== 'Cancelled'; }); }
  function byId(id) { return ORDERS.filter(function (o) { return o.id === id; })[0] || null; }

  /* ---------- Aggregations ---------------------------------------------- */
  var MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

  function byMonth(list, count) {
    count = count || 12;
    var buckets = [];
    for (var i = count - 1; i >= 0; i--) {
      var dt = new Date(TODAY.getFullYear(), TODAY.getMonth() - i, 1);
      buckets.push({
        key: dt.getFullYear() + '-' + String(dt.getMonth() + 1).padStart(2, '0'),
        label: MONTHS[dt.getMonth()],
        full: MONTHS[dt.getMonth()] + ' ' + dt.getFullYear(),
        year: dt.getFullYear(), month: dt.getMonth(),
        orders: 0, pieces: 0, spend: 0
      });
    }
    var map = {};
    buckets.forEach(function (b) { map[b.key] = b; });
    live(list).forEach(function (o) {
      var k = o.date.getFullYear() + '-' + String(o.date.getMonth() + 1).padStart(2, '0');
      if (!map[k]) return;
      map[k].orders++;
      map[k].pieces += orderPieces(o);
      map[k].spend += orderTotal(o);
    });
    return buckets;
  }

  function rollup(list, keyFn, labelFn) {
    var map = {};
    live(list).forEach(function (o) {
      o.lines.forEach(function (l) {
        var k = keyFn(l, o);
        if (!map[k]) map[k] = { key: k, label: labelFn(l, o), pieces: 0, spend: 0, orders: {} };
        map[k].pieces += l.qty;
        map[k].spend += lineTotal(l);
        map[k].orders[o.id] = 1;
      });
    });
    return Object.keys(map).map(function (k) {
      map[k].orderCount = Object.keys(map[k].orders).length;
      return map[k];
    }).sort(function (a, b) { return b.pieces - a.pieces; });
  }

  function byItem(list)  { return rollup(list, function (l) { return l.sku; }, function (l) { return l.name; }); }
  function byColor(list) { return rollup(list, function (l) { return l.color; }, function (l) { return B.color(l.color).name; }); }
  function byCat(list)   { return rollup(list, function (l) { return l.cat; }, function (l) { var c = D.catBySlug(l.cat); return c ? c.name : l.cat; }); }
  function byDecor(list) { return rollup(list, function (l) { return l.imprint; }, function (l) { return l.imprint; }); }

  function byUser(list) {
    var map = {};
    live(list).forEach(function (o) {
      if (!map[o.user]) map[o.user] = { key: o.user, label: o.user, pieces: 0, spend: 0, orderCount: 0 };
      map[o.user].orderCount++;
      map[o.user].pieces += orderPieces(o);
      map[o.user].spend += orderTotal(o);
    });
    return Object.keys(map).map(function (k) { return map[k]; }).sort(function (a, b) { return b.spend - a.spend; });
  }

  function byWarehouse(list) {
    var map = {};
    live(list).forEach(function (o) {
      var wh = D.WAREHOUSES.filter(function (x) { return x.code === o.warehouse; })[0];
      var k = o.warehouse;
      if (!map[k]) map[k] = { key: k, label: wh ? wh.name : k, pieces: 0, spend: 0, orderCount: 0 };
      map[k].orderCount++;
      map[k].pieces += orderPieces(o);
      map[k].spend += orderTotal(o);
    });
    return Object.keys(map).map(function (k) { return map[k]; }).sort(function (a, b) { return b.spend - a.spend; });
  }

  /* ---------- Charts ------------------------------------------------------
     Single-series magnitude work, so no categorical palette and no legend:
     the card title names what is plotted. Bars are capped at 24px with a 4px
     rounded cap, hairline solid gridlines, and a hover tooltip on every mark. */

  function niceMax(v) {
    if (v <= 0) return 10;
    var mag = Math.pow(10, Math.floor(Math.log10(v)));
    var n = v / mag;
    var step = n <= 1 ? 1 : n <= 2 ? 2 : n <= 2.5 ? 2.5 : n <= 5 ? 5 : 10;
    return step * mag;
  }
  function compact(n) {
    if (n >= 1e6) return (n / 1e6).toFixed(n >= 1e7 ? 0 : 1).replace(/\.0$/, '') + 'M';
    if (n >= 1e3) return (n / 1e3).toFixed(n >= 1e4 ? 0 : 1).replace(/\.0$/, '') + 'K';
    return String(Math.round(n));
  }

  /* data: [{label, value, full, meta}] */
  function columnChart(el, data, opts) {
    opts = opts || {};
    var W = 760, H = opts.height || 240;
    var padL = 46, padR = 10, padT = 18, padB = 28;
    var plotW = W - padL - padR, plotH = H - padT - padB;
    var max = niceMax(Math.max.apply(null, data.map(function (x) { return x.value; })) || 1);
    var band = plotW / data.length;
    var bw = Math.min(24, band - 10);
    var fmt = opts.format || compact;

    var ticks = [0, .25, .5, .75, 1].map(function (t) { return max * t; });
    var svg = '<svg viewBox="0 0 ' + W + ' ' + H + '" preserveAspectRatio="none" role="img" aria-label="' + (opts.alt || 'Column chart') + '">';

    svg += '<g class="grid">' + ticks.map(function (t) {
      var y = padT + plotH - (t / max) * plotH;
      return '<line x1="' + padL + '" y1="' + y + '" x2="' + (W - padR) + '" y2="' + y + '"/>';
    }).join('') + '</g>';

    svg += '<g class="tick">' + ticks.map(function (t) {
      var y = padT + plotH - (t / max) * plotH;
      return '<text x="' + (padL - 9) + '" y="' + (y + 3.5) + '" text-anchor="end">' + (opts.prefix || '') + fmt(t) + '</text>';
    }).join('') + '</g>';

    var maxIdx = data.reduce(function (m, x, i) { return x.value > data[m].value ? i : m; }, 0);

    svg += '<g>' + data.map(function (x, i) {
      var h = Math.max(x.value > 0 ? 2 : 0, (x.value / max) * plotH);
      var cx = padL + band * i + band / 2;
      var y = padT + plotH - h;
      var r = Math.min(4, h);
      var pth = 'M' + (cx - bw / 2) + ' ' + (padT + plotH) +
        ' V' + (y + r) + ' a' + r + ' ' + r + ' 0 0 1 ' + r + ' -' + r +
        ' H' + (cx + bw / 2 - r) + ' a' + r + ' ' + r + ' 0 0 1 ' + r + ' ' + r +
        ' V' + (padT + plotH) + ' Z';
      return '<path class="bar" data-i="' + i + '" d="' + pth + '" fill="var(--series-1)"/>';
    }).join('') + '</g>';

    /* one direct label — the peak; the axis carries the rest */
    var pk = data[maxIdx];
    if (pk && pk.value > 0) {
      var pkH = (pk.value / max) * plotH;
      svg += '<text class="val-label" x="' + (padL + band * maxIdx + band / 2) + '" y="' + (padT + plotH - pkH - 7) +
        '" text-anchor="middle">' + (opts.prefix || '') + fmt(pk.value) + '</text>';
    }

    svg += '<g class="axis"><line x1="' + padL + '" y1="' + (padT + plotH) + '" x2="' + (W - padR) + '" y2="' + (padT + plotH) + '"/></g>';

    svg += '<g>' + data.map(function (x, i) {
      return '<text x="' + (padL + band * i + band / 2) + '" y="' + (H - 9) + '" text-anchor="middle">' + x.label + '</text>';
    }).join('') + '</g>';

    svg += '<g>' + data.map(function (x, i) {
      return '<rect class="hit" data-i="' + i + '" x="' + (padL + band * i) + '" y="' + padT + '" width="' + band + '" height="' + plotH + '"/>';
    }).join('') + '</g>';

    svg += '</svg>';

    el.classList.add('viz');
    el.innerHTML = svg + '<div class="viz-tip" role="status"></div>';
    wireTip(el, data, opts);
  }

  function wireTip(el, data, opts) {
    var tip = el.querySelector('.viz-tip');
    var svg = el.querySelector('svg');
    el.querySelectorAll('.hit').forEach(function (hit) {
      var show = function (e) {
        var i = +hit.dataset.i, x = data[i];
        var box = el.getBoundingClientRect();
        var hb = hit.getBoundingClientRect();
        tip.innerHTML = '<b>' + (x.full || x.label) + '</b>' + (x.tip || ((opts.prefix || '') + (opts.format || compact)(x.value)));
        tip.style.left = (hb.left - box.left + hb.width / 2) + 'px';
        tip.style.top = (hb.top - box.top + 10) + 'px';
        tip.classList.add('is-on');
        el.classList.add('is-hovering');
        el.querySelectorAll('.bar').forEach(function (b) { b.classList.toggle('is-on', b.dataset.i === hit.dataset.i); });
      };
      hit.addEventListener('mouseenter', show);
      hit.addEventListener('focus', show);
      hit.setAttribute('tabindex', '0');
    });
    svg.addEventListener('mouseleave', function () {
      tip.classList.remove('is-on'); el.classList.remove('is-hovering');
    });
  }

  function sparkline(values, opts) {
    opts = opts || {};
    var W = 120, H = 30, max = Math.max.apply(null, values) || 1, min = Math.min.apply(null, values);
    var span = (max - min) || 1;
    var pts = values.map(function (v, i) {
      return [(i / (values.length - 1)) * W, H - 2 - ((v - min) / span) * (H - 6)];
    });
    var dPath = pts.map(function (p, i) { return (i ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1); }).join(' ');
    var area = dPath + ' L' + W + ' ' + H + ' L0 ' + H + ' Z';
    var last = pts[pts.length - 1];
    return '<svg class="viz" viewBox="0 0 ' + W + ' ' + H + '" preserveAspectRatio="none" aria-hidden="true" style="width:100%;height:30px;overflow:visible">' +
      '<path d="' + area + '" fill="var(--series-wash)"/>' +
      '<path d="' + dPath + '" fill="none" stroke="var(--series-1)" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>' +
      '<circle cx="' + last[0].toFixed(1) + '" cy="' + last[1].toFixed(1) + '" r="4" fill="var(--series-1)" stroke="#fff" stroke-width="2"/>' +
      '</svg>';
  }

  /* rows: [{label, value, sub, swatch, mono}] */
  function barList(rows, opts) {
    opts = opts || {};
    var max = Math.max.apply(null, rows.map(function (r) { return r.value; })) || 1;
    return '<div class="barlist' + (opts.narrow ? ' barlist-narrow' : '') + '">' + rows.map(function (r) {
      return '<div class="barlist-row">' +
        '<span class="barlist-name">' +
          (r.swatch ? '<span class="swatch" style="width:18px;height:18px;background:' + r.swatch + '"></span>' : '') +
          '<span class="nm"><b title="' + r.label + '">' + r.label + '</b>' +
          (r.mono ? '<span class="mono">' + r.mono + '</span>' : '') + '</span>' +
        '</span>' +
        '<span class="barlist-track"><span class="barlist-fill" style="width:' + Math.max(2, (r.value / max) * 100).toFixed(1) + '%"></span></span>' +
        '<span class="barlist-val">' + r.display + (r.sub ? '<span>' + r.sub + '</span>' : '') + '</span>' +
      '</div>';
    }).join('') + '</div>';
  }

  /* ---------- Dashboard shell ------------------------------------------- */
  var NAV = [
    ['account.html',           'grid',    'Overview'],
    ['account-orders.html',    'doc',     'Orders'],
    ['account-reports.html',   'chart',   'Purchase reports'],
    ['account-pricing.html',   'tag',     'My price list'],
    ['account-users.html',     'users',   'Company users'],
    ['account-addresses.html', 'pin',     'Addresses & warehouses'],
    ['account-settings.html',  'shield',  'Company profile']
  ];

  function guard() {
    if (!w.TBM.isIn()) {
      var here = location.pathname.split('/').pop() + location.search;
      location.replace('login.html?next=' + encodeURIComponent(here));
      return false;
    }
    return true;
  }

  function renderNav(current) {
    var el = d.getElementById('dash-nav');
    if (!el) return;
    var a = w.TBM.account(), I = w.TBM.I;
    var openCount = ORDERS.filter(function (o) { return o.status === 'Pending confirmation' || o.status === 'In production'; }).length;

    el.innerHTML =
      '<div class="dash-card">' +
        '<div class="dash-who"><div class="row" style="gap:12px">' +
          '<span class="av">' + (a.init || 'AM') + '</span>' +
          '<span style="min-width:0"><b>' + a.name + '</b><span>' + COMPANY.name + '</span></span>' +
        '</div>' +
        '<div class="row" style="gap:8px;margin-top:14px;flex-wrap:wrap">' +
          '<span class="badge badge-eco">' + D.TIERS[w.TBM.tier()].name + '</span>' +
          '<span class="badge">' + COMPANY.terms + '</span>' +
        '</div></div>' +
        '<nav class="dash-menu">' +
          NAV.map(function (n) {
            var cur = current === n[0];
            var count = n[0] === 'account-orders.html' ? '<span class="n">' + openCount + '</span>' : '';
            return '<a class="' + (cur ? 'is-current' : '') + '" href="' + n[0] + '">' + I[n[1]] + n[2] + count + '</a>';
          }).join('') +
          '<hr>' +
          '<a href="shop.html">' + I.bag + 'Browse catalog</a>' +
          '<a href="cart.html">' + I.box + 'Current order</a>' +
        '</nav>' +
        '<div class="dash-foot">' +
          '<div class="small muted" style="margin-bottom:10px">Your rep</div>' +
          '<div class="user-row"><span class="av">JA</span><span><b>' + COMPANY.rep.name + '</b><span>' + COMPANY.rep.title + '</span></span></div>' +
          '<a class="btn btn-outline btn-sm btn-block" style="margin-top:12px" href="mailto:' + COMPANY.rep.email + '">Message ' + COMPANY.rep.name.split(' ')[0] + '</a>' +
        '</div>' +
      '</div>';
  }

  /* ---------- Helpers shared by the pages -------------------------------- */
  function fmtDate(dt) {
    return dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  }
  function statusPill(k) {
    var s = statusOf(k);
    return '<span class="st-pill ' + s.cls + '"><i class="dot"></i>' + k + '</span>';
  }
  function csv(rows) {
    return rows.map(function (r) {
      return r.map(function (c) { return '"' + String(c).replace(/"/g, '""') + '"'; }).join(',');
    }).join('\n');
  }
  function download(name, text) {
    var blob = new Blob([text], { type: 'text/csv;charset=utf-8' });
    var url = URL.createObjectURL(blob);
    var a = d.createElement('a');
    a.href = url; a.download = name; a.click();
    setTimeout(function () { URL.revokeObjectURL(url); }, 1200);
  }

  w.TBMAcct = {
    COMPANY: COMPANY, USERS: USERS, ADDRESSES: ADDRESSES, ORDERS: ORDERS, TODAY: TODAY, MONTHS: MONTHS,
    guard: guard, renderNav: renderNav,
    byId: byId, live: live, orderPieces: orderPieces, orderTotal: orderTotal, unitFor: unitFor, lineTotal: lineTotal,
    byMonth: byMonth, byItem: byItem, byColor: byColor, byCat: byCat, byDecor: byDecor, byUser: byUser, byWarehouse: byWarehouse,
    columnChart: columnChart, sparkline: sparkline, barList: barList, compact: compact,
    statusOf: statusOf, statusPill: statusPill, fmtDate: fmtDate, csv: csv, download: download
  };
})(window, document);
