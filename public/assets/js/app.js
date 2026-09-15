/* ==========================================================================
   TBM — storefront behaviour
   --------------------------------------------------------------------------
   The pages are rendered by the server and work without this file: every form
   submits, every link navigates, every price is already on the page. What is
   here is the layer that makes the shell pleasant — the menus, the tabs, the
   live price on the quantity stepper.

   No framework and no build step, because there is nothing here that needs
   one, and a wholesale buyer on a warehouse laptop should not wait for a
   bundle to boot before they can read a stock figure.
   ========================================================================== */
(function () {
  'use strict';

  var d = document;

  function on(el, type, fn) { if (el) el.addEventListener(type, fn); }
  function all(sel, root) { return Array.prototype.slice.call((root || d).querySelectorAll(sel)); }

  /* ---------------------------------------------------------------- Toast */

  function toast(message) {
    var wrap = d.getElementById('toastWrap');
    if (!wrap) return;

    var el = d.createElement('div');
    el.className = 'toast';
    el.textContent = message;
    wrap.appendChild(el);

    setTimeout(function () { el.classList.add('is-out'); }, 3600);
    setTimeout(function () { el.remove(); }, 4000);
  }

  /* --------------------------------------------------------------- Header */

  function header() {
    // Mega menu, opened by the Bags item and closed by anything sensible.
    var trigger = d.querySelector('.has-mega button');
    var mega = d.getElementById('mega');

    if (trigger && mega) {
      var openMega = function (open) {
        mega.classList.toggle('is-open', open);
        trigger.setAttribute('aria-expanded', String(open));
      };

      on(trigger, 'click', function (e) {
        e.stopPropagation();
        openMega(!mega.classList.contains('is-open'));
      });

      on(mega, 'click', function (e) { e.stopPropagation(); });
      on(d, 'click', function () { openMega(false); });
      on(d, 'keydown', function (e) { if (e.key === 'Escape') openMega(false); });
    }

    // Account menu.
    var acctBtn = d.getElementById('acctBtn');
    var acctMenu = d.getElementById('acctMenu');

    if (acctBtn && acctMenu) {
      on(acctBtn, 'click', function (e) {
        e.stopPropagation();
        var open = !acctMenu.classList.contains('is-open');
        acctMenu.classList.toggle('is-open', open);
        acctBtn.setAttribute('aria-expanded', String(open));
      });

      on(acctMenu, 'click', function (e) { e.stopPropagation(); });
      on(d, 'click', function () {
        acctMenu.classList.remove('is-open');
        acctBtn.setAttribute('aria-expanded', 'false');
      });
    }

    // Mobile drawer.
    var nav = d.getElementById('mobileNav');
    on(d.getElementById('burger'), 'click', function () { if (nav) nav.classList.add('is-open'); });
    on(d.getElementById('mobClose'), 'click', function () { if (nav) nav.classList.remove('is-open'); });
  }

  /* ----------------------------------------------------------------- Tabs */

  function tabs() {
    all('.tabbar').forEach(function (bar) {
      var panels = all('.tab-panel', bar.parentNode);

      all('.tab', bar).forEach(function (tab) {
        on(tab, 'click', function () {
          all('.tab', bar).forEach(function (t) {
            t.classList.remove('is-active');
            t.setAttribute('aria-selected', 'false');
          });

          tab.classList.add('is-active');
          tab.setAttribute('aria-selected', 'true');

          panels.forEach(function (panel) {
            panel.classList.toggle('is-active', panel.dataset.panel === tab.dataset.tab);
          });
        });
      });
    });
  }

  /* ------------------------------------------------------- Chart tables */

  /*
     Every chart has the figures underneath it. They start collapsed because
     the picture is usually the answer, and open in one click because sometimes
     it is not.
  */
  function chartTables() {
    all('[data-chart-table]').forEach(function (button) {
      on(button, 'click', function () {
        var figure = d.getElementById(button.dataset.chartTable);
        var table = figure && figure.querySelector('.chart-table');
        if (!table) return;

        table.hidden = !table.hidden;
        button.textContent = table.hidden ? 'Show the figures' : 'Hide the figures';
      });
    });
  }

  /* ----------------------------------------------------- Item page ------ */

  function productPage() {
    var form = d.getElementById('pdpForm');
    if (!form) return;

    var qty = d.getElementById('qty');
    var stage = d.getElementById('pdpStage');
    var priceBox = d.getElementById('pdpPrice');
    var hint = d.getElementById('decHint');

    /* Swatches and option buttons: the radio is the source of truth, the
       class is only the look. */
    function syncGroup(selector) {
      all(selector).forEach(function (label) {
        var input = label.querySelector('input');
        if (input) label.classList.toggle('is-active', input.checked);
      });
    }

    on(form, 'change', function (e) {
      if (!e.target.matches('input[type=radio]')) return;

      syncGroup('.swatch-pick');
      syncGroup('.size-opt');

      // Repaint the illustration from the matching thumbnail, which the
      // server already rendered in the right colour.
      if (e.target.name === 'colourway_id' && stage) {
        var thumb = d.querySelector('.pdp-thumb[data-colour="' + e.target.value + '"]');
        if (thumb) stage.innerHTML = thumb.innerHTML;
        all('.pdp-thumb').forEach(function (t) {
          t.classList.toggle('is-active', t.dataset.colour === e.target.value);
        });
      }

      if (e.target.name === 'decoration' && hint) {
        hint.textContent = e.target.value.indexOf('Blank') === 0
          ? 'Blank stock ships in 48 hours. Decoration is quoted separately and added by your rep.'
          : e.target.value + ' is quoted per colour and per location once artwork is in. We will attach a quote before this order is confirmed.';
      }
    });

    // Clicking a thumbnail selects that colour.
    all('.pdp-thumb').forEach(function (thumb) {
      on(thumb, 'click', function () {
        var input = form.querySelector('input[name="colourway_id"][value="' + thumb.dataset.colour + '"]');
        if (input) { input.checked = true; input.dispatchEvent(new Event('change', { bubbles: true })); }
      });
    });

    // Quantity stepper.
    all('[data-step]').forEach(function (button) {
      on(button, 'click', function () {
        var step = parseInt(button.dataset.step, 10);
        var min = parseInt(qty.min, 10) || 1;
        qty.value = Math.max(min, (parseInt(qty.value, 10) || min) + step);
        qty.dispatchEvent(new Event('change', { bubbles: true }));
      });
    });

    /*
       Live price. At these quantities the break a buyer lands on is the
       decision they are actually making, so the number moves as they type
       rather than after a reload. If the request fails the figure the server
       rendered simply stays put — nothing breaks.
    */
    var timer;
    var endpoint = qty && qty.dataset.quote;

    if (endpoint && priceBox) {
      on(qty, 'input', function () {
        clearTimeout(timer);
        timer = setTimeout(refreshPrice, 260);
      });
      on(qty, 'change', refreshPrice);
    }

    function refreshPrice() {
      var quantity = parseInt(qty.value, 10);
      if (!quantity || quantity < 1) return;

      fetch(endpoint + '?quantity=' + quantity, { headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (data) {
          if (!data) return;

          var next = data.next_break
            ? '<span class="price-next">Add ' + data.next_break.shortfall.toLocaleString() +
              ' more and it is ' + fmt(data.next_break.unit_price) + ' a piece</span>'
            : '';

          priceBox.innerHTML =
            '<div class="price price-lg"><b>' + data.unit_price_formatted + '</b>' +
            '<span class="price-unit">/ pc at ' + quantity.toLocaleString() + '</span></div>' +
            '<div class="price-note">Extended <b>' + data.line_total_formatted +
            '</b> · blank, FOB warehouse, before freight and decoration.</div>' + next;
        })
        .catch(function () { /* keep whatever the server rendered */ });
    }

    function fmt(value) {
      return '$' + value.toFixed(value === Math.round(value * 100) / 100 ? 2 : 4);
    }
  }

  /* -------------------------------------------------------------- Startup */

  function init() {
    header();
    tabs();
    chartTables();
    productPage();

    // Flash messages surface as a toast as well as in the page, because on a
    // long form the notice can be well above the fold.
    var status = d.querySelector('.notice-info p');
    if (status && d.body.dataset.toast === 'yes') toast(status.textContent.trim());
  }

  if (d.readyState === 'loading') {
    on(d, 'DOMContentLoaded', init);
  } else {
    init();
  }

  window.TBM = { toast: toast };
})();
