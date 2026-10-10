/*
 * MailDart — small progressive-enhancement script (plain JavaScript, no libraries).
 * Every page works without it; it only adds convenience:
 *   form[data-confirm="Question?"]      ask before submitting
 *   form[data-autosubmit]               submit when a field inside changes (selects, radios, text on blur)
 *   details[data-dropdown]              close on outside click / Escape, only one open at a time
 *   [data-copy="text"]                  copy text to the clipboard, flash "Copied!" (data-copied-label)
 *   [data-dismiss]                      remove the closest [data-flash] / [data-dismissible] element
 *   [data-countdown][data-seconds]      live mm:ss countdown text
 *   [data-dispatch-runner]              while a campaign runs: call api/dispatch-tick when the timer hits zero, then reload
 *   [data-modal-root]                   Escape follows the element's [data-modal-close] link
 */
(function () {
  'use strict';

  // Confirm before submitting forms with data-confirm="question" — an in-app dialog instead of window.confirm().
  // Destructive wording (delete / remove / clear / reset / replace / off) gets a red confirm button.
  var DANGER_WORDS = /\b(delete|remove|clear|reset|replace|off|discard)\b/i;
  var ALERT_ICON = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>';
  var QUESTION_ICON = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>';

  function confirmDialog(question, onConfirm) {
    var danger = DANGER_WORDS.test(question);
    var root = document.createElement('div');
    root.className = 'fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-[3px] animate-fade-in';
    root.setAttribute('role', 'alertdialog');
    root.setAttribute('aria-modal', 'true');
    root.innerHTML =
      '<div class="relative w-full max-w-md bg-surface border border-slate-200 rounded-2xl shadow-modal p-6">' +
        '<div class="flex items-start gap-4">' +
          '<div class="w-11 h-11 shrink-0 rounded-xl flex items-center justify-center" style="' +
            (danger ? 'background:rgb(220 38 38/.15);color:#f87171' : 'background:rgb(106 135 166/.18);color:#c4d2e1') + '">' +
            (danger ? ALERT_ICON : QUESTION_ICON) + '</div>' +
          '<div class="min-w-0 pt-0.5">' +
            '<h2 class="text-base font-semibold text-slate-900">Are you sure?</h2>' +
            '<p class="mt-1.5 text-sm text-slate-500 leading-relaxed" data-confirm-text></p>' +
          '</div>' +
        '</div>' +
        '<div class="mt-6 flex justify-end gap-2">' +
          '<button type="button" data-confirm-cancel class="inline-flex items-center justify-center h-10 px-4 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 rounded-lg transition">Cancel</button>' +
          '<button type="button" data-confirm-ok class="inline-flex items-center justify-center h-10 px-4 text-sm font-semibold text-white rounded-lg transition ' +
            (danger ? '" style="background:#dc2626"' : 'bg-brand-600 hover:bg-brand-500 shadow-button"') + '>' +
            (danger ? 'Yes, continue' : 'Confirm') + '</button>' +
        '</div>' +
      '</div>';
    root.querySelector('[data-confirm-text]').textContent = question;

    var previousFocus = document.activeElement;
    function close(confirmed) {
      document.removeEventListener('keydown', onKey, true);
      root.remove();
      if (previousFocus && previousFocus.focus) previousFocus.focus();
      if (confirmed) onConfirm();
    }
    function onKey(e) {
      if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); close(false); }
      if (e.key === 'Tab') { // keep focus inside the dialog
        var buttons = root.querySelectorAll('button');
        var first = buttons[0], last = buttons[buttons.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
      }
    }
    root.addEventListener('click', function (e) { if (e.target === root) close(false); });
    root.querySelector('[data-confirm-cancel]').addEventListener('click', function () { close(false); });
    root.querySelector('[data-confirm-ok]').addEventListener('click', function () { close(true); });
    document.addEventListener('keydown', onKey, true);
    document.body.appendChild(root);
    root.querySelector('[data-confirm-ok]').focus();
  }

  document.addEventListener('submit', function (e) {
    var form = e.target;
    var question = form.getAttribute && form.getAttribute('data-confirm');
    if (!question) return;
    if (form.__confirmed) { form.__confirmed = false; return; } // second pass after "Confirm"
    e.preventDefault();
    e.stopImmediatePropagation();
    var submitter = e.submitter || null; // keep the clicked button's name/value (e.g. action=…)
    confirmDialog(question, function () {
      form.__confirmed = true;
      if (form.requestSubmit) form.requestSubmit(submitter && submitter.form === form ? submitter : undefined);
      else form.submit();
    });
  }, true);

  // Auto-submit forms when a control changes
  document.addEventListener('change', function (e) {
    var form = e.target.form;
    if (!form || !form.hasAttribute('data-autosubmit') || e.target.type === 'file') return;
    if (form.requestSubmit) form.requestSubmit();
    else form.submit();
  });

  // Dropdowns built with <details data-dropdown>
  function closeDropdowns(except) {
    document.querySelectorAll('details[data-dropdown][open]').forEach(function (d) {
      if (d !== except) d.removeAttribute('open');
    });
  }
  document.addEventListener('click', function (e) {
    var inside = e.target.closest && e.target.closest('details[data-dropdown]');
    closeDropdowns(inside);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    closeDropdowns(null);
    var modal = document.querySelector('[data-modal-root] [data-modal-close]');
    if (modal && modal.href) window.location.href = modal.href;
  });

  // Copy to clipboard
  document.addEventListener('click', function (e) {
    var btn = e.target.closest && e.target.closest('[data-copy]');
    if (!btn) return;
    e.preventDefault();
    var text = btn.getAttribute('data-copy');
    var done = function () {
      var label = btn.querySelector('[data-copy-label]') || btn;
      var original = label.innerHTML;
      label.textContent = btn.getAttribute('data-copied-label') || 'Copied!';
      setTimeout(function () { label.innerHTML = original; }, 1500);
    };
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(done, function () { fallbackCopy(text); done(); });
    } else {
      fallbackCopy(text);
      done();
    }
  });
  function fallbackCopy(text) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); } catch (err) { /* ignore */ }
    document.body.removeChild(ta);
  }

  // Dismiss flash messages / banners
  document.addEventListener('click', function (e) {
    var btn = e.target.closest && e.target.closest('[data-dismiss]');
    if (!btn) return;
    var box = btn.closest('[data-flash], [data-dismissible]');
    if (box) box.remove();
  });

  // Countdown + staggered dispatch runner
  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function fmt(s) { return s <= 0 ? '00:00' : pad(Math.floor(s / 60)) + ':' + pad(s % 60); }

  var runner = document.querySelector('[data-dispatch-runner]');
  var countdowns = document.querySelectorAll('[data-countdown]');
  var remaining = runner ? parseInt(runner.getAttribute('data-remaining'), 10) || 0 : null;
  var ticking = false;

  function paint() {
    countdowns.forEach(function (el) {
      var s = remaining !== null ? remaining : parseInt(el.getAttribute('data-seconds'), 10) || 0;
      el.textContent = fmt(s);
      var bar = el.getAttribute('data-progress-target') && document.getElementById(el.getAttribute('data-progress-target'));
      var total = parseInt(el.getAttribute('data-total'), 10) || 0;
      if (bar && total > 0) bar.style.width = Math.max(0, Math.min(100, ((total - s) / total) * 100)) + '%';
    });
  }

  function tick() {
    if (ticking) return;
    ticking = true;
    fetch('api/dispatch-tick', { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, credentials: 'same-origin', body: '{}' })
      .then(function (r) { return r.json(); })
      .then(function () { window.location.reload(); })
      .catch(function () { ticking = false; remaining = 5; }); // network hiccup: retry in 5s
  }

  if (runner) {
    paint();
    setInterval(function () {
      if (remaining > 0) remaining -= 1;
      paint();
      if (remaining <= 0) tick();
    }, 1000);
  }

  /* ---------------------------------------------------------------------
   * SelectMenu (views/partials/select_menu.php, mode="radio"): the radio input already
   * submits correctly without JS; this only mirrors the checked option into the trigger
   * (label + badge) and closes the <details> so it behaves like a normal dropdown pick.
   *   details[data-select-menu] > summary [data-trigger-label] / [data-trigger-badge]
   *   details[data-select-menu] label[data-option-label] input[type=radio]
   *     > [data-option-text] text / [data-option-badge]
   * ------------------------------------------------------------------- */
  document.addEventListener('change', function (e) {
    var input = e.target;
    if (!input.matches || input.type !== 'radio' || !input.matches('[data-option-label] input')) return;
    var details = input.closest('details[data-select-menu]');
    if (!details) return;
    var optionLabel = input.closest('[data-option-label]');
    var trigger = details.querySelector('[data-trigger-label]');
    if (trigger) {
      var text = optionLabel.querySelector('[data-option-text]');
      trigger.textContent = text ? text.textContent.trim() : input.value;
    }
    var triggerBadge = details.querySelector('[data-trigger-badge]');
    if (triggerBadge) {
      var optionBadge = optionLabel.querySelector('[data-option-badge]');
      triggerBadge.textContent = optionBadge ? optionBadge.textContent : '';
      triggerBadge.hidden = !optionBadge;
    }
    details.removeAttribute('open');
  });
})();
