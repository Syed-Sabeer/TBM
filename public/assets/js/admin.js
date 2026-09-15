/* ==========================================================================
   TBM — back office behaviour
   --------------------------------------------------------------------------
   The back office is server-rendered too. This adds the two things the
   screens genuinely need: an editable stock grid that only submits what
   actually changed, and the chart tables.
   ========================================================================== */
(function () {
  'use strict';

  var d = document;

  function all(sel, root) { return Array.prototype.slice.call((root || d).querySelectorAll(sel)); }
  function on(el, type, fn) { if (el) el.addEventListener(type, fn); }

  /* ------------------------------------------------------- Chart tables */

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

  /* --------------------------------------------------------- Stock grid */

  /*
     The grid renders every warehouse cell as an input, but posting all of
     them would write a movement for every untouched figure and bury the real
     corrections in the log. So a cell is only submitted once its value
     actually differs from what the server rendered — everything else is
     disabled on submit, which keeps it out of the request entirely.
  */
  function stockGrid() {
    var form = d.getElementById('stockForm');
    if (!form) return;

    var cells = all('.cell-edit', form);
    var counter = d.createElement('span');
    counter.className = 'small muted';

    function dirtyCells() {
      return cells.filter(function (cell) {
        return String(cell.value).trim() !== String(cell.dataset.original).trim();
      });
    }

    function paint() {
      cells.forEach(function (cell) {
        cell.classList.toggle('is-dirty', String(cell.value).trim() !== String(cell.dataset.original).trim());
      });

      var n = dirtyCells().length;
      counter.textContent = n === 0
        ? 'No changes yet.'
        : n + (n === 1 ? ' change' : ' changes') + ' pending.';
    }

    cells.forEach(function (cell) { on(cell, 'input', paint); });

    var submit = form.querySelector('button[type=submit]');
    if (submit && submit.parentNode) submit.parentNode.insertBefore(counter, submit.nextSibling);

    on(form, 'submit', function (e) {
      var dirty = dirtyCells();

      if (dirty.length === 0) {
        e.preventDefault();
        counter.textContent = 'Nothing has changed — no corrections to save.';

        return;
      }

      // Drop the untouched rows out of the payload.
      cells.forEach(function (cell) {
        if (dirty.indexOf(cell) !== -1) return;

        cell.disabled = true;
        var row = cell.closest('td');
        if (row) all('input[type=hidden]', row).forEach(function (h) { h.disabled = true; });
      });
    });

    paint();
  }

  /* ------------------------------------------------------- Confirmations */

  /*
     Native confirm() rather than a custom dialog: these are rare, destructive
     actions where the browser's own dialog is the clearest possible signal
     that something is about to happen.
  */
  function confirmations() {
    all('form[action*="cancel"], form[action*="rollback"], form[action*="destroy"]').forEach(function (form) {
      on(form, 'submit', function (e) {
        var label = form.querySelector('button');
        if (!label) return;

        if (!window.confirm(label.textContent.trim() + ' — are you sure?')) {
          e.preventDefault();
        }
      });
    });
  }

  function init() {
    chartTables();
    stockGrid();
    confirmations();
  }

  if (d.readyState === 'loading') {
    on(d, 'DOMContentLoaded', init);
  } else {
    init();
  }
})();
