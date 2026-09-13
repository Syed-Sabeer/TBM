/* ==========================================================================
   TBM Admin — back-office data model (prototype)
   Seeded so every figure is stable across reloads and across pages.
   Reuses TBMData for the catalog, warehouses and tier definitions.
   ========================================================================== */
(function (w) {
  'use strict';
  var D = w.TBMData;

  function rng(seed) {
    return function () {
      seed |= 0; seed = seed + 0x6D2B79F5 | 0;
      var t = Math.imul(seed ^ seed >>> 15, 1 | seed);
      t = t + Math.imul(t ^ t >>> 7, 61 | t) ^ t;
      return ((t ^ t >>> 14) >>> 0) / 4294967296;
    };
  }
  function pick(r, a) { return a[Math.floor(r() * a.length)]; }
  function between(r, a, b) { return a + Math.floor(r() * (b - a + 1)); }

  var TODAY = new Date(2026, 8, 13);

  /* ---------- Customers (companies) ------------------------------------- */
  /* overrides = per-item multiplier against the tier price, i.e. a negotiated
     line on top of the rate card. 0.92 means 8% under the tier for that item. */
  var COMPANIES = [
    { id:'C10428', name:'Northline Supply Co.',   tier:'B', terms:'Net 30', status:'Active',
      state:'CA', city:'Los Angeles', rep:'Jordan Alvarez', since:2021, limit:75000, used:18420,
      overrides:{ DS4520:0.92, DS4580:0.88 }, seats:5, cert:'Verified', certExp:'2027-03-31' },
    { id:'C10102', name:'Harbor Promo Group',     tier:'A', terms:'Net 45', status:'Active',
      state:'WA', city:'Seattle', rep:'Jordan Alvarez', since:2016, limit:250000, used:96400,
      overrides:{ DS4520:0.90, DS4525:0.90, DS4655:0.86 }, seats:14, cert:'Verified', certExp:'2028-01-31' },
    { id:'C10877', name:'Midtown Print Co.',      tier:'B', terms:'Net 30', status:'Active',
      state:'NY', city:'Brooklyn', rep:'Brandon Nye', since:2019, limit:60000, used:41180,
      overrides:{ DS4590:0.94 }, seats:6, cert:'Verified', certExp:'2027-08-31' },
    { id:'C11240', name:'Salt & Stone Retail',    tier:'C', terms:'Prepay', status:'Active',
      state:'OR', city:'Portland', rep:'Amara Hale', since:2025, limit:15000, used:2140,
      overrides:{}, seats:2, cert:'Verified', certExp:'2027-01-31' },
    { id:'C10553', name:'Gulf Coast Merch',       tier:'B', terms:'Net 30', status:'Active',
      state:'TX', city:'Houston', rep:'Jordan Alvarez', since:2020, limit:90000, used:37600,
      overrides:{ DS4585:0.90 }, seats:8, cert:'Verified', certExp:'2027-05-31' },
    { id:'C10719', name:'Beacon Events Group',    tier:'B', terms:'Net 30', status:'Active',
      state:'IL', city:'Chicago', rep:'Brandon Nye', since:2018, limit:120000, used:58900,
      overrides:{ DS4580:0.86, DS4585:0.88 }, seats:9, cert:'Verified', certExp:'2027-11-30' },
    { id:'C11015', name:'Pine & Post Goods',      tier:'C', terms:'Net 15', status:'Active',
      state:'CO', city:'Denver', rep:'Amara Hale', since:2023, limit:30000, used:11250,
      overrides:{}, seats:3, cert:'Verified', certExp:'2027-02-28' },
    { id:'C10390', name:'Atlas Corporate Gifting', tier:'A', terms:'Net 45', status:'Active',
      state:'GA', city:'Atlanta', rep:'Jordan Alvarez', since:2017, limit:200000, used:143200,
      overrides:{ DS4500:0.93, DS4600:0.91 }, seats:11, cert:'Verified', certExp:'2027-09-30' },
    { id:'C11302', name:'Verdant Grocery Co-op',  tier:'C', terms:'Prepay', status:'On hold',
      state:'MN', city:'Minneapolis', rep:'Amara Hale', since:2024, limit:20000, used:19850,
      overrides:{}, seats:4, cert:'Expired', certExp:'2026-06-30' },
    { id:'C11418', name:'Cedar Lane Apparel',     tier:'C', terms:'Prepay', status:'Pending approval',
      state:'NC', city:'Raleigh', rep:'Unassigned', since:2026, limit:0, used:0,
      overrides:{}, seats:1, cert:'Awaiting', certExp:'—' },
    { id:'C11421', name:'Two Rivers Promotions',  tier:'C', terms:'Prepay', status:'Pending approval',
      state:'MO', city:'Kansas City', rep:'Unassigned', since:2026, limit:0, used:0,
      overrides:{}, seats:1, cert:'Awaiting', certExp:'—' }
  ];

  var FIRST = ['Alex','Dana','Marcus','Priya','Sam','Rosa','Ken','Lena','Tobias','Nadia','Owen','Farah','Iris','Caleb','Mei','Diego'];
  var LAST  = ['Morgan','Whitfield','Reed','Raman','Okafor','Delgado','Tanaka','Brooks','Vance','Aziz','Hughes','Nasser','Kelly','Ford','Lin','Marquez'];
  var ROLES = ['Purchasing Manager','Senior Buyer','Warehouse Lead','Accounts Payable','Account Executive','Operations Manager','Owner'];
  var PERMS = ['Admin','Buyer','Buyer','View only'];

  (function seedUsers() {
    var r = rng(4242);
    COMPANIES.forEach(function (c, ci) {
      c.users = [];
      for (var i = 0; i < c.seats; i++) {
        var fn = FIRST[(ci * 3 + i * 5) % FIRST.length];
        var ln = LAST[(ci * 7 + i * 3) % LAST.length];
        c.users.push({
          name: fn + ' ' + ln,
          email: (i === 0 && c.id === 'C10428') ? 'customer@gmail.com'
               : fn.toLowerCase() + '@' + c.name.toLowerCase().replace(/[^a-z]/g, '').slice(0, 12) + '.com',
          role: i === 0 ? 'Purchasing Manager' : ROLES[between(r, 1, ROLES.length - 1)],
          perm: i === 0 ? 'Admin' : PERMS[between(r, 1, PERMS.length - 1)],
          init: fn[0] + ln[0],
          last: i === 0 ? 'Today' : pick(r, ['2 days ago','6 days ago','3 weeks ago','5 weeks ago','Yesterday'])
        });
      }
    });
  })();

  function company(id) { return COMPANIES.filter(function (c) { return c.id === id; })[0] || null; }

  /* ---------- Pricing ---------------------------------------------------- */
  function priceFor(comp, product, qty) {
    var t = D.TIERS[comp.tier] || D.TIERS.C;
    var f = D.BREAK_FACTOR[D.breakIndex(qty || product.moq)];
    var ov = (comp.overrides && comp.overrides[product.sku]) || 1;
    return Math.round(product.base * f * t.factor * ov * 100) / 100;
  }
  function rowFor(comp, product) {
    var t = D.TIERS[comp.tier] || D.TIERS.C;
    var ov = (comp.overrides && comp.overrides[product.sku]) || 1;
    return D.BREAK_FACTOR.map(function (f) {
      return Math.round(product.base * f * t.factor * ov * 100) / 100;
    });
  }

  /* ---------- Orders across every account -------------------------------- */
  var STATUSES = [
    { key:'Delivered',            cls:'st-delivered',  step:4 },
    { key:'Shipped',              cls:'st-shipped',    step:3 },
    { key:'In production',        cls:'st-production', step:2 },
    { key:'Pending confirmation', cls:'st-pending',    step:1 },
    { key:'Cancelled',            cls:'st-cancelled',  step:0 }
  ];
  function statusOf(k) { return STATUSES.filter(function (s) { return s.key === k; })[0] || STATUSES[3]; }

  var DECOR = ['Blank (undecorated)', 'Screen print', 'DTF transfer', 'Embroidery', 'Sublimation'];

  function buildOrders() {
    var r = rng(90210);
    var out = [], n = 3200;
    var active = COMPANIES.filter(function (c) { return c.status !== 'Pending approval'; });

    for (var back = 25; back >= 0; back--) {
      var base = new Date(TODAY.getFullYear(), TODAY.getMonth() - back, 1);
      var m = base.getMonth();
      var perMonth = between(r, 6, 10);
      if (m === 9 || m === 10) perMonth += 4;
      if (m === 2 || m === 3) perMonth += 2;
      if (back === 0) perMonth = 8;

      for (var i = 0; i < perMonth; i++) {
        n++;
        var c = active[between(r, 0, active.length - 1)];
        var day = between(r, 1, back === 0 ? 12 : 27);
        var date = new Date(base.getFullYear(), base.getMonth(), day);
        var user = c.users[between(r, 0, c.users.length - 1)];
        var wh = pick(r, D.WAREHOUSES).code;

        var status;
        if (back === 0) status = pick(r, ['Pending confirmation','Pending confirmation','In production','Shipped']);
        else if (back === 1) status = pick(r, ['Shipped','Delivered','Delivered','In production']);
        else status = r() < 0.04 ? 'Cancelled' : 'Delivered';

        var lines = [], used = {}, lc = between(r, 1, 4);
        for (var L = 0; L < lc; L++) {
          var p = D.PRODUCTS[between(r, 0, D.PRODUCTS.length - 1)];
          if (used[p.sku]) p = D.PRODUCTS[(between(r, 0, D.PRODUCTS.length - 1) + L * 7) % D.PRODUCTS.length];
          used[p.sku] = 1;
          var step = p.moq >= 500 ? 250 : (p.moq >= 250 ? 50 : 25);
          var qty = p.moq * between(r, 1, 6) + step * between(r, 0, 4);
          qty = Math.max(p.moq, Math.round(qty * (0.8 + (25 - back) * 0.016) / step) * step);
          lines.push({
            sku: p.sku, parentSku: p.parentSku, id: p.id, name: p.name, shape: p.shape, cat: p.cat,
            color: p.colors[between(r, 0, p.colors.length - 1)],
            size: p.sizes[between(r, 0, p.sizes.length - 1)],
            imprint: r() < 0.42 ? DECOR[between(r, 1, 4)] : DECOR[0],
            qty: qty
          });
        }

        out.push({
          id: 'TBM-' + date.getFullYear() + '-' + n,
          date: date,
          po: 'PO-' + date.getFullYear() + '-' + String(100 + n % 900).padStart(4, '0'),
          companyId: c.id,
          company: c.name,
          user: user.name,
          warehouse: wh,
          status: status,
          lines: lines
        });
      }
    }
    return out.sort(function (a, b) { return b.date - a.date; });
  }

  var ORDERS = buildOrders();

  function orderPieces(o) { return o.lines.reduce(function (s, l) { return s + l.qty; }, 0); }
  function orderTotal(o) {
    var c = company(o.companyId);
    if (!c) return 0;
    return o.lines.reduce(function (s, l) {
      var p = D.byId(l.id);
      return s + (p ? priceFor(c, p, l.qty) * l.qty : 0);
    }, 0);
  }
  function orderById(id) { return ORDERS.filter(function (o) { return o.id === id; })[0] || null; }
  function live(list) { return (list || ORDERS).filter(function (o) { return o.status !== 'Cancelled'; }); }

  /* ---------- Aggregations ---------------------------------------------- */
  var MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

  function byMonth(list, count) {
    count = count || 12;
    var buckets = [], map = {};
    for (var i = count - 1; i >= 0; i--) {
      var dt = new Date(TODAY.getFullYear(), TODAY.getMonth() - i, 1);
      var b = {
        key: dt.getFullYear() + '-' + String(dt.getMonth() + 1).padStart(2, '0'),
        label: MONTHS[dt.getMonth()], full: MONTHS[dt.getMonth()] + ' ' + dt.getFullYear(),
        orders: 0, pieces: 0, spend: 0
      };
      buckets.push(b); map[b.key] = b;
    }
    live(list).forEach(function (o) {
      var k = o.date.getFullYear() + '-' + String(o.date.getMonth() + 1).padStart(2, '0');
      if (!map[k]) return;
      map[k].orders++; map[k].pieces += orderPieces(o); map[k].spend += orderTotal(o);
    });
    return buckets;
  }

  function rollup(list, keyFn, labelFn) {
    var map = {};
    live(list).forEach(function (o) {
      var c = company(o.companyId);
      o.lines.forEach(function (l) {
        var k = keyFn(l, o);
        if (!map[k]) map[k] = { key:k, label: labelFn(l, o), pieces:0, spend:0, orders:{} };
        var p = D.byId(l.id);
        map[k].pieces += l.qty;
        map[k].spend += p && c ? priceFor(c, p, l.qty) * l.qty : 0;
        map[k].orders[o.id] = 1;
      });
    });
    return Object.keys(map).map(function (k) {
      map[k].orderCount = Object.keys(map[k].orders).length; return map[k];
    }).sort(function (a, b) { return b.pieces - a.pieces; });
  }
  function byItem(l)  { return rollup(l, function (x) { return x.sku; },   function (x) { return x.name; }); }
  function byColor(l) { return rollup(l, function (x) { return x.color; }, function (x) { return w.TBMBags.color(x.color).name; }); }
  function byCat(l)   { return rollup(l, function (x) { return x.cat; },   function (x) { var c = D.catBySlug(x.cat); return c ? c.name : x.cat; }); }
  function byDecor(l) { return rollup(l, function (x) { return x.imprint; }, function (x) { return x.imprint; }); }

  function byCompany(list) {
    var map = {};
    live(list).forEach(function (o) {
      if (!map[o.companyId]) map[o.companyId] = { key:o.companyId, label:o.company, pieces:0, spend:0, orderCount:0 };
      map[o.companyId].orderCount++;
      map[o.companyId].pieces += orderPieces(o);
      map[o.companyId].spend += orderTotal(o);
    });
    return Object.keys(map).map(function (k) { return map[k]; }).sort(function (a, b) { return b.spend - a.spend; });
  }
  function byWarehouse(list) {
    var map = {};
    live(list).forEach(function (o) {
      var wh = D.WAREHOUSES.filter(function (x) { return x.code === o.warehouse; })[0];
      if (!map[o.warehouse]) map[o.warehouse] = { key:o.warehouse, label: wh ? wh.name : o.warehouse, pieces:0, spend:0, orderCount:0 };
      map[o.warehouse].orderCount++;
      map[o.warehouse].pieces += orderPieces(o);
      map[o.warehouse].spend += orderTotal(o);
    });
    return Object.keys(map).map(function (k) { return map[k]; }).sort(function (a, b) { return b.spend - a.spend; });
  }
  function byRep(list) {
    var map = {};
    live(list).forEach(function (o) {
      var c = company(o.companyId); if (!c) return;
      if (!map[c.rep]) map[c.rep] = { key:c.rep, label:c.rep, pieces:0, spend:0, orderCount:0 };
      map[c.rep].orderCount++; map[c.rep].pieces += orderPieces(o); map[c.rep].spend += orderTotal(o);
    });
    return Object.keys(map).map(function (k) { return map[k]; }).sort(function (a, b) { return b.spend - a.spend; });
  }

  /* ---------- Demand ------------------------------------------------------
     The eleven accounts modelled here are a sample of the book, not all of it,
     so raw line volume understates real demand. DEMAND_SCALE grosses the sample
     up to the whole customer base, which is what makes weeks-of-cover and the
     reorder alerts read sensibly. Replace it with actual shipped quantities.  */
  var DEMAND_SCALE = 12;
  var _usage = null;
  function usage() {
    if (_usage) return _usage;
    var t = TODAY;
    var from = new Date(t.getFullYear(), t.getMonth() - 12, t.getDate());
    var recent = ORDERS.filter(function (o) { return o.status !== 'Cancelled' && o.date >= from; });
    _usage = {};
    byItem(recent).forEach(function (x) { _usage[x.key] = x.pieces / 12 * DEMAND_SCALE; });
    return _usage;
  }
  function monthlyUse(sku) { return usage()[sku] || 1; }
  function weeksCover(product) {
    return (w.TBMData.totalStock(product) / monthlyUse(product.sku)) * 4.345;
  }

  /* ---------- Import history --------------------------------------------- */
  var IMPORTS = (function () {
    var r = rng(777), out = [];
    for (var i = 0; i < 14; i++) {
      var dt = new Date(TODAY.getFullYear(), TODAY.getMonth(), TODAY.getDate() - i);
      var rows = between(r, 28, 34);
      var warn = i === 0 ? 2 : between(r, 0, 3);
      out.push({
        id: 'IMP-' + dt.getFullYear() + String(dt.getMonth() + 1).padStart(2, '0') + String(dt.getDate()).padStart(2, '0'),
        date: dt,
        file: 'mill-stock-' + dt.getFullYear() + '-' + String(dt.getMonth() + 1).padStart(2, '0') + '-' + String(dt.getDate()).padStart(2, '0') + '.xlsx',
        kind: i % 7 === 3 ? 'Pricing' : 'Stock',
        rows: rows,
        updated: rows - warn,
        added: i % 5 === 0 ? 1 : 0,
        warnings: warn,
        by: i % 3 === 0 ? 'Renu Kapoor' : 'System (scheduled 06:15 PT)',
        status: warn > 2 ? 'Applied with warnings' : 'Applied'
      });
    }
    return out;
  })();

  /* A realistic-looking supplier sheet, as it arrives, for the import demo.
     Note the mill's own column names and the parent SKUs. */
  var SHEET_COLUMNS = ['MILL_REF', 'DESCRIPTION', 'SHADE', 'LOT_QTY', 'WH', 'READY_DATE', 'FOB_USD'];
  var SHEET_ROWS = (function () {
    var r = rng(31337), out = [];
    var colorNames = { natural:'NATURAL', black:'BLACK', navy:'NAVY', forest:'FOREST GRN', bone:'BONE',
      white:'WHITE', royal:'ROYAL BLU', red:'RED', olive:'OLIVE', jute:'RAW JUTE', grey:'HTHR GREY',
      denim:'WSH DENIM', sage:'SAGE', burgundy:'BURGUNDY', kraft:'KRAFT', blush:'BLUSH', teal:'TEAL', rust:'RUST' };
    D.PRODUCTS.forEach(function (p, i) {
      var col = p.colors[i % p.colors.length];
      var wh = D.WAREHOUSES[i % D.WAREHOUSES.length];
      var dt = new Date(TODAY.getFullYear(), TODAY.getMonth(), TODAY.getDate() + between(r, 4, 28));
      var delta = between(r, -22, 34) / 100;
      var newQty = Math.max(0, Math.round((p.stock[wh.code] || 0) * (1 + delta) / 50) * 50);
      var fob = Math.round(p.base * (0.52 + between(r, -4, 6) / 100) * 100) / 100;
      out.push({
        MILL_REF: p.parentSku,
        DESCRIPTION: p.name.toUpperCase(),
        SHADE: colorNames[col] || col.toUpperCase(),
        LOT_QTY: newQty,
        WH: wh.code,
        READY_DATE: dt.toISOString().slice(0, 10),
        FOB_USD: fob,
        /* resolved for the preview */
        _sku: p.sku, _id: p.id, _wh: wh.code, _old: p.stock[wh.code] || 0, _color: col
      });
    });
    /* two rows the importer cannot match — the interesting case */
    out.push({ MILL_REF:'BPK140', DESCRIPTION:'CANVAS CROSSBODY 12OZ', SHADE:'NATURAL', LOT_QTY:3200,
      WH:'LAX', READY_DATE:'2026-10-04', FOB_USD:4.10, _sku:null, _id:null, _wh:'LAX', _old:0, _color:'natural' });
    out.push({ MILL_REF:'BPK31', DESCRIPTION:'HEAVYWEIGHT CANVAS CARRYALL', SHADE:'BURNT ORANGE', LOT_QTY:1800,
      WH:'DFW', READY_DATE:'2026-09-29', FOB_USD:3.05, _sku:'DS4540', _id:'ds4540', _wh:'DFW', _old:900, _color:null });
    return out;
  })();

  /* ---------- Admin users ------------------------------------------------- */
  var STAFF = [
    { name:'Maya Castellanos', email:'admin@gmail.com',      role:'Owner',              init:'MC', last:'Today' },
    { name:'Jordan Alvarez',   email:'jordan@tbm.com',       role:'Account Manager',    init:'JA', last:'Today' },
    { name:'Renu Kapoor',      email:'renu@tbm.com',         role:'Quality & Inventory',init:'RK', last:'Today' },
    { name:'Brandon Nye',      email:'brandon@tbm.com',      role:'East Coast Ops',     init:'BN', last:'Yesterday' },
    { name:'Amara Hale',       email:'amara@tbm.com',        role:'Customer Care',      init:'AH', last:'2 days ago' },
    { name:'Devon Shaw',       email:'devon@tbm.com',        role:'Decoration Manager', init:'DS', last:'4 days ago' }
  ];

  /* ---------- Activity feed ----------------------------------------------- */
  var ACTIVITY = [
    ['Stock import applied', '32 rows updated from mill-stock-2026-09-13.xlsx', 'System', '06:15'],
    ['Order confirmed', 'TBM-2026-3441 for Harbor Promo Group', 'Jordan Alvarez', '08:02'],
    ['Rate card changed', 'Beacon Events Group — DS4580 override set to −14%', 'Maya Castellanos', '08:47'],
    ['Account approved', 'Pine & Post Goods moved to Tier C', 'Amara Hale', '09:15'],
    ['Low stock alert', 'DS4575 Denim Weekend Carryall below 30 days cover', 'System', '09:30'],
    ['Item number mapped', 'BPK140 → DS4685 created and linked', 'Renu Kapoor', '10:04'],
    ['Certificate expired', 'Verdant Grocery Co-op resale certificate lapsed — account on hold', 'System', '10:20']
  ];

  w.TBMAdmin = {
    TODAY: TODAY, MONTHS: MONTHS,
    COMPANIES: COMPANIES, STAFF: STAFF, ORDERS: ORDERS, IMPORTS: IMPORTS, ACTIVITY: ACTIVITY,
    SHEET_COLUMNS: SHEET_COLUMNS, SHEET_ROWS: SHEET_ROWS, STATUSES: STATUSES,
    company: company, priceFor: priceFor, rowFor: rowFor, statusOf: statusOf,
    orderById: orderById, orderPieces: orderPieces, orderTotal: orderTotal, live: live,
    byMonth: byMonth, byItem: byItem, byColor: byColor, byCat: byCat, byDecor: byDecor,
    usage: usage, monthlyUse: monthlyUse, weeksCover: weeksCover, DEMAND_SCALE: DEMAND_SCALE,
    byCompany: byCompany, byWarehouse: byWarehouse, byRep: byRep
  };
})(window);
