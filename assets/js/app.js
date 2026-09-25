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

  // Confirm before submitting destructive forms
  document.addEventListener('submit', function (e) {
    var form = e.target;
    var question = form.getAttribute && form.getAttribute('data-confirm');
    if (question && !window.confirm(question)) e.preventDefault();
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
