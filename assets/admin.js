(function () {
  'use strict';

  // ── Dev mode toggle ───────────────────────────────────────────────
  // Uses row classes (bs-dev-row) added via WP add_settings_field 6th param.
  var devToggle = document.getElementById('sa-dev-mode');

  function getDevRows() {
    return document.querySelectorAll('.sa-dev-row, .sa-dev-heading');
  }

  function applyDevMode() {
    var show = devToggle && devToggle.checked;
    getDevRows().forEach(function (el) {
      el.style.display = show ? '' : 'none';
    });
  }

  if (devToggle) {
    applyDevMode(); // apply on load
    devToggle.addEventListener('change', applyDevMode);
  }

  // ── Suggestions dynamic list ──────────────────────────────────────
  var container = document.getElementById('sa-suggestions-list');
  var addBtn    = document.getElementById('sa-add-suggestion');

  function makeSuggestionRow(value) {
    var row = document.createElement('div');
    row.className = 'sa-suggestion-row';
    row.style.cssText = 'display:flex;gap:6px;margin-bottom:6px;align-items:center;';

    var input = document.createElement('input');
    input.type = 'text';
    input.name = 'sommai_settings[suggestions][]';
    input.value = value || '';
    input.className = 'regular-text';
    input.placeholder = 'e.g. Pairing with grilled steak 🥩';
    input.style.flex = '1';

    var del = document.createElement('button');
    del.type = 'button';
    del.className = 'button';
    del.textContent = '✕ Remove';
    del.style.flexShrink = '0';
    del.addEventListener('click', function () { row.remove(); });

    row.appendChild(input);
    row.appendChild(del);
    return row;
  }

  if (container) {
    var existing = window.saSommAISuggestions || [];
    existing.forEach(function (s) {
      container.appendChild(makeSuggestionRow(s));
    });

    if (addBtn) {
      addBtn.addEventListener('click', function () {
        var row = makeSuggestionRow('');
        container.appendChild(row);
        row.querySelector('input').focus();
      });
    }
  }
})();
