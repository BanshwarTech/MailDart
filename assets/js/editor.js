/*
 * MailDart — Template Studio page script (plain JavaScript, no libraries).
 * Everything here is a convenience on top of features that already work without it:
 *   [data-insert-tag][data-insert-target]   insert the tag at the textarea caret (falls back to the shared
 *                                            app.js [data-copy] clipboard-copy behaviour when JS is unavailable)
 *   #template-subject / #template-html      debounced live preview via POST api/preview
 *   [data-show-when-engine]                 shows/hides the NVIDIA model picker as the AI engine radio changes
 *   [data-loading-label]                    swaps a submit button to its loading label while its form submits
 *   #var-name-input                         lowercases / sanitises the custom-tag name live, like the old TSX
 */
(function () {
  'use strict';

  /* ---- Insert a variable tag at the textarea caret (was TemplateEditor.tsx handleInsertTag) ------------- */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest && e.target.closest('[data-insert-tag]');
    if (!btn) return;
    var targetId = btn.getAttribute('data-insert-target');
    var field = targetId && document.getElementById(targetId);
    if (!field) return;

    var tag = btn.getAttribute('data-insert-tag');
    var start = field.selectionStart == null ? field.value.length : field.selectionStart;
    var end = field.selectionEnd == null ? field.value.length : field.selectionEnd;
    field.value = field.value.slice(0, start) + tag + field.value.slice(end);

    setTimeout(function () {
      field.focus();
      var pos = start + tag.length;
      if (field.setSelectionRange) field.setSelectionRange(pos, pos);
      field.dispatchEvent(new Event('input', { bubbles: true }));
    }, 50);
  });

  /* ---- Live preview while typing (debounced POST to api/preview) ------------------------------------ */
  var subjectField = document.getElementById('template-subject');
  var htmlField = document.getElementById('template-html');
  var frame = document.getElementById('template-preview-frame');
  var subjectDisplay = document.querySelector('[data-preview-subject]');
  var recipientInput = document.getElementById('preview-recipient-id');

  if (htmlField && frame) {
    var previewTimer = null;
    var refreshPreview = function () {
      fetch('api/preview', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({
          subject: subjectField ? subjectField.value : '',
          html: htmlField.value,
          recipientId: recipientInput ? recipientInput.value : '',
        }),
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (!data || data.success === false) return;
          frame.srcdoc = data.html;
          if (subjectDisplay) subjectDisplay.textContent = data.subject || 'No Subject';
        })
        .catch(function () { /* network hiccup: keep showing the last preview */ });
    };
    var scheduleRefresh = function () {
      clearTimeout(previewTimer);
      previewTimer = setTimeout(refreshPreview, 400);
    };
    htmlField.addEventListener('input', scheduleRefresh);
    if (subjectField) subjectField.addEventListener('input', scheduleRefresh);
  }

  /* ---- Show/hide the NVIDIA model picker based on the selected AI engine --------------------------------- */
  document.addEventListener('change', function (e) {
    if (!e.target.matches || !e.target.matches('input[name="engine"]')) return;
    var value = e.target.value;
    document.querySelectorAll('[data-show-when-engine]').forEach(function (panel) {
      var allowed = (panel.getAttribute('data-show-when-engine') || '').split(',');
      panel.hidden = allowed.indexOf(value) === -1;
    });
  });

  /* ---- Loading label on submit (was AiTemplateModal.tsx's isLoading button) ------------------------------ */
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form.querySelectorAll) return;
    form.querySelectorAll('[data-loading-label]').forEach(function (btn) {
      btn.disabled = true;
      var idle = btn.querySelector('[data-idle-label]');
      var loading = btn.querySelector('[data-loading-content]');
      if (idle) idle.classList.add('hidden');
      if (loading) loading.classList.remove('hidden');
    });
  });

  /* ---- Live-sanitise the custom variable name (was AiTemplateModal... TemplateEditor.tsx's setNewVarName) - */
  var varNameInput = document.getElementById('var-name-input');
  if (varNameInput) {
    varNameInput.addEventListener('input', function () {
      var cleaned = varNameInput.value.toLowerCase().replace(/[^\w-]/g, '_');
      if (cleaned !== varNameInput.value) varNameInput.value = cleaned;
    });
  }
})();
