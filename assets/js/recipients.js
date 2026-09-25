/*
 * Recipients page — small progressive-enhancement script (plain JavaScript, no libraries).
 * Everything here is a convenience layered on top of markup that already works without JS:
 *   [data-dropzone]                 drag-over highlight + auto-submit the upload form once a file is chosen
 *   [data-var-rows] + [data-add-var-row] / [data-remove-var-row]
 *                                    reveal/hide the extra blank "custom variable" input pairs one at a time
 *                                    (all pairs already exist in the HTML — a <noscript> style shows them all
 *                                    when JS is unavailable, so nothing is lost without JS)
 *   [data-select-all]               toggles every input[name="ids[]"] checkbox on the page (bulk delete); without
 *                                    JS, recipients can still be bulk-deleted by checking rows individually
 */
(function () {
  'use strict';

  // Excel/CSV dropzone: drag highlight + auto-submit on file choose
  document.querySelectorAll('[data-dropzone]').forEach(function (zone) {
    var input = zone.querySelector('input[type="file"]');
    var form = input && input.form;
    zone.addEventListener('dragover', function (e) {
      e.preventDefault();
      zone.classList.add('border-brand-500', 'bg-brand-50');
    });
    zone.addEventListener('dragleave', function () {
      zone.classList.remove('border-brand-500', 'bg-brand-50');
    });
    zone.addEventListener('drop', function (e) {
      zone.classList.remove('border-brand-500', 'bg-brand-50');
      if (!input || !e.dataTransfer || !e.dataTransfer.files || !e.dataTransfer.files.length) return;
      e.preventDefault();
      input.files = e.dataTransfer.files;
      if (form) form.requestSubmit ? form.requestSubmit() : form.submit();
    });
    if (input) {
      input.addEventListener('change', function () {
        if (input.files && input.files.length && form) {
          form.requestSubmit ? form.requestSubmit() : form.submit();
        }
      });
    }
  });

  // "Select all" checkbox toggles every recipient row checkbox (bulk delete)
  document.querySelectorAll('[data-select-all]').forEach(function (master) {
    master.addEventListener('change', function () {
      document.querySelectorAll('input[name="ids[]"]').forEach(function (cb) { cb.checked = master.checked; });
    });
  });

  // Custom-variable rows: reveal one more blank pair per click, hide on remove
  document.querySelectorAll('[data-var-rows]').forEach(function (group) {
    var rows = group.querySelectorAll('[data-var-row]');
    rows.forEach(function (row, i) {
      if (row.hasAttribute('data-var-row-extra')) row.classList.add('hidden');
    });
    var addBtn = group.querySelector('[data-add-var-row]');
    if (addBtn) {
      addBtn.addEventListener('click', function () {
        for (var i = 0; i < rows.length; i++) {
          if (rows[i].classList.contains('hidden')) {
            rows[i].classList.remove('hidden');
            break;
          }
        }
      });
    }
    group.addEventListener('click', function (e) {
      var removeBtn = e.target.closest && e.target.closest('[data-remove-var-row]');
      if (!removeBtn) return;
      var row = removeBtn.closest('[data-var-row]');
      if (!row) return;
      row.querySelectorAll('input[type="text"]').forEach(function (input) { input.value = ''; });
      if (row.hasAttribute('data-var-row-extra')) row.classList.add('hidden');
    });
  });
})();
