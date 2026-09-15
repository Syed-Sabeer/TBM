/* ==========================================================================
   TBM Admin — shell, auth guard and chart helpers
   Every admin page lives at /admin/<page>/index.html, so sibling links are
   always "../<page>/" and assets are always "../../assets/".
   ========================================================================== */
(function (w, d) {
  'use strict';
  var I = w.TBM.I, D = w.TBMData, A = w.TBMAdmin;

  var KEY = 'tbm.admin';
  var CRED = { email: 'admin@gmail.com', password: 'adminadmin' };

  function read(k, fb) { try { var v = localStorage.getItem(k); return v ? JSON.parse(v) : fb; } catch (e) { return fb; } }
  function write(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} }

  function admin() { return read(KEY, null); }
  function signIn(u) { write(KEY, u); }
  function signOut() { try { localStorage.removeItem(KEY); } catch (e) {} }

  function guard() {
    if (!admin()) { location.replace('../login/'); return false; }
    return true;
  }

  /* ---------- Navigation ------------------------------------------------- */
  var NAV = [
    ['Overview', [
      ['dashboard', 'grid',  'Dashboard']
    ]],
    ['Sales', [
      ['orders',    'doc',   'Orders'],
      ['customers', 'users', 'Customers']
    ]],
    ['Catalog', [
      ['products',  'bag',   'Products & SKUs'],
      ['pricing',   'tag',   'Rate cards']
    ]],
    ['Inventory', [
      ['inventory',  'box',  'Stock'],
      ['warehouses', 'pin',  'Warehouses']
    ]],
    ['Data', [
      ['import',  'refresh', 'Imports'],
      ['reports', 'chart',   'Reports']
    ]],
    ['System', [
      ['settings', 'shield', 'Settings']
    ]]
  ];

  function pendingCount() {
    return A.ORDERS.filter(function (o) { return o.status === 'Pending confirmation'; }).length;
  }
  function pendingAccounts() {
    return A.COMPANIES.filter(function (c) { return c.status === 'Pending approval'; }).length;
  }

  function renderShell(current, title, crumb) {
    var me = admin();
    var badges = { orders: pendingCount(), customers: pendingAccounts() };

    var side =
      '<aside class="adm-side">' +
        '<div class="adm-brand">' +
          '<a class="logo" href="../dashboard/">' +
            '<svg class="logo-mark" viewBox="0 0 40 40" fill="none" aria-hidden="true">' +
              '<rect width="40" height="40" rx="10" fill="#F7F4ED"/>' +
              '<path d="M12 15h16l1 13.5a1.2 1.2 0 0 1-1.2 1.3H12.2a1.2 1.2 0 0 1-1.2-1.3Z" fill="#1C3D2E"/>' +
              '<path d="M16 15.5c0-4 8-4 8 0" stroke="#B9793C" stroke-width="2.1" stroke-linecap="round"/>' +
            '</svg>' +
            '<span class="logo-text"><b>TBM</b><small>Back office</small></span>' +
          '</a>' +
          '<span class="env">Prototype data</span>' +
        '</div>' +
        '<nav class="adm-nav">' +
          NAV.map(function (g) {
            return '<div class="grp">' + g[0] + '</div>' +
              g[1].map(function (n) {
                var b = badges[n[0]];
                return '<a class="' + (current === n[0] ? 'is-current' : '') + '" href="../' + n[0] + '/">' +
                  I[n[1]] + n[2] + (b ? '<span class="n">' + b + '</span>' : '') + '</a>';
              }).join('');
          }).join('') +
          '<div class="grp">Storefront</div>' +
          '<a href="../../index.html" target="_blank">' + I.arrow + 'View the site</a>' +
        '</nav>' +
        '<div class="adm-side-foot">' +
          '<div class="adm-who"><span class="av">' + me.init + '</span>' +
            '<span style="min-width:0"><b>' + me.name + '</b><span>' + me.role + '</span></span></div>' +
          '<button class="out" id="admOut">' + I.logout + 'Sign out</button>' +
        '</div>' +
      '</aside>';

    var top =
      '<div class="adm-main">' +
        '<header class="adm-top">' +
          '<div><h1>' + title + '</h1>' + (crumb ? '<div class="crumb">' + crumb + '</div>' : '') + '</div>' +
          '<div class="adm-search">' + I.search +
            '<input type="search" id="admSearch" placeholder="Order, customer or item number">' +
          '</div>' +
          '<a class="icon-btn" href="../import/" title="Imports">' + I.refresh + '</a>' +
          '<a class="btn btn-primary btn-sm" href="../orders/">' + (badges.orders || 0) + ' awaiting confirmation</a>' +
        '</header>' +
        '<div class="adm-content" id="admContent"></div>' +
      '</div>';

    var root = d.getElementById('adm-root');
    root.className = 'adm';
    root.innerHTML = side + top;

    d.getElementById('admOut').addEventListener('click', function () {
      signOut(); location.href = '../login/';
    });
    d.getElementById('admSearch').addEventListener('keydown', function (e) {
      if (e.key !== 'Enter') return;
      var q = e.target.value.trim();
      if (!q) return;
      if (/^TBM-/i.test(q)) location.href = '../order/?id=' + encodeURIComponent(q.toUpperCase());
      else if (/^DS\d+$/i.test(q)) location.href = '../product/?sku=' + encodeURIComponent(q.toUpperCase());
      else location.href = '../customers/?q=' + encodeURIComponent(q);
    });

    return d.getElementById('admContent');
  }

  /* ---------- Formatting -------------------------------------------------- */
  function fmtDate(dt) { return dt.toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' }); }
  function fmtShort(dt) { return dt.toLocaleDateString('en-US', { month:'short', day:'numeric' }); }
  function statusPill(k) {
    var s = A.statusOf(k);
    return '<span class="st-pill ' + s.cls + '"><i class="dot"></i>' + k + '</span>';
  }
  function acctPill(status) {
    var cls = status === 'Active' ? 'st-delivered' : status === 'On hold' ? 'st-cancelled' : 'st-production';
    return '<span class="st-pill ' + cls + '"><i class="dot"></i>' + status + '</span>';
  }
  function csv(rows) {
    return rows.map(function (r) {
      return r.map(function (c) { return '"' + String(c).replace(/"/g, '""') + '"'; }).join(',');
    }).join('\n');
  }
  function download(name, text) {
    var blob = new Blob([text], { type:'text/csv;charset=utf-8' });
    var url = URL.createObjectURL(blob);
    var a = d.createElement('a'); a.href = url; a.download = name; a.click();
    setTimeout(function () { URL.revokeObjectURL(url); }, 1200);
  }

  /* ---------- Charts ------------------------------------------------------
     Single-series magnitude work: one validated hue, no legend, hairline
     gridlines, <=24px bars with a 4px cap, one direct label on the peak,
     hover tooltip on every mark, and a table view alongside.            */
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

  function columnChart(el, data, opts) {
    opts = opts || {};
    var W = 760, H = opts.height || 230;
    var padL = 48, padR = 10, padT = 18, padB = 28;
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
      var cx = padL + band * i + band / 2, y = padT + plotH - h, r = Math.min(4, h);
      return '<path class="bar" data-i="' + i + '" fill="var(--series-1)" d="' +
        'M' + (cx - bw / 2) + ' ' + (padT + plotH) + ' V' + (y + r) +
        ' a' + r + ' ' + r + ' 0 0 1 ' + r + ' -' + r + ' H' + (cx + bw / 2 - r) +
        ' a' + r + ' ' + r + ' 0 0 1 ' + r + ' ' + r + ' V' + (padT + plotH) + ' Z"/>';
    }).join('') + '</g>';

    var pk = data[maxIdx];
    if (pk && pk.value > 0) {
      svg += '<text class="val-label" x="' + (padL + band * maxIdx + band / 2) + '" y="' +
        (padT + plotH - (pk.value / max) * plotH - 7) + '" text-anchor="middle">' +
        (opts.prefix || '') + fmt(pk.value) + '</text>';
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

    var tip = el.querySelector('.viz-tip'), s = el.querySelector('svg');
    el.querySelectorAll('.hit').forEach(function (hit) {
      var show = function () {
        var i = +hit.dataset.i, x = data[i];
        var box = el.getBoundingClientRect(), hb = hit.getBoundingClientRect();
        tip.innerHTML = '<b>' + (x.full || x.label) + '</b>' + (x.tip || ((opts.prefix || '') + fmt(x.value)));
        tip.style.left = (hb.left - box.left + hb.width / 2) + 'px';
        tip.style.top = (hb.top - box.top + 10) + 'px';
        tip.classList.add('is-on'); el.classList.add('is-hovering');
        el.querySelectorAll('.bar').forEach(function (b) { b.classList.toggle('is-on', b.dataset.i === hit.dataset.i); });
      };
      hit.addEventListener('mouseenter', show);
      hit.addEventListener('focus', show);
      hit.setAttribute('tabindex', '0');
    });
    s.addEventListener('mouseleave', function () { tip.classList.remove('is-on'); el.classList.remove('is-hovering'); });
  }

  function sparkline(values) {
    var W = 120, H = 30;
    var max = Math.max.apply(null, values) || 1, min = Math.min.apply(null, values);
    var span = (max - min) || 1;
    var pts = values.map(function (v, i) {
      return [(i / (values.length - 1)) * W, H - 2 - ((v - min) / span) * (H - 6)];
    });
    var path = pts.map(function (p, i) { return (i ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1); }).join(' ');
    var last = pts[pts.length - 1];
    return '<svg class="viz" viewBox="0 0 ' + W + ' ' + H + '" preserveAspectRatio="none" aria-hidden="true" style="width:100%;height:30px;overflow:visible">' +
      '<path d="' + path + ' L' + W + ' ' + H + ' L0 ' + H + ' Z" fill="var(--series-wash)"/>' +
      '<path d="' + path + '" fill="none" stroke="var(--series-1)" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>' +
      '<circle cx="' + last[0].toFixed(1) + '" cy="' + last[1].toFixed(1) + '" r="4" fill="var(--series-1)" stroke="#fff" stroke-width="2"/></svg>';
  }

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

  w.TBMAdminUI = {
    CRED: CRED,
    admin: admin, signIn: signIn, signOut: signOut, guard: guard, renderShell: renderShell,
    fmtDate: fmtDate, fmtShort: fmtShort, statusPill: statusPill, acctPill: acctPill,
    csv: csv, download: download, compact: compact,
    columnChart: columnChart, sparkline: sparkline, barList: barList
  };
})(window, document);
