/*
 * MailDart — auth pages (Sign In / Sign Up / Forgot / Reset) progressive enhancement.
 * Ported from src/utils/password.ts. Every page still works with this script disabled: fields behave as plain
 * password inputs and the server (includes/auth.php) re-validates the password rules on submit regardless.
 *
 *   [data-generate-btn]                 fill every [data-password-input] in the form with a strong password,
 *                                        reveal it and copy it to the clipboard
 *   [data-password-toggle]              show/hide every [data-password-input] in the form
 *   [data-strength-input]               live strength meter in the form's [data-strength-group]
 *   [data-confirm-input]                live "passwords match" check against [data-strength-input]
 *   form[data-loading-submit]           tiny spinner on [data-submit-button] while the page navigates away
 */
(function () {
  'use strict';

  function randomIndex(max) {
    if (window.crypto && window.crypto.getRandomValues) {
      var buf = new Uint32Array(1);
      window.crypto.getRandomValues(buf);
      return buf[0] % max;
    }
    return Math.floor(Math.random() * max);
  }

  /** Strong random password: upper, lower, digits and symbols, shuffled. */
  function generateStrongPassword(length) {
    length = length || 16;
    var sets = ['ABCDEFGHJKLMNPQRSTUVWXYZ', 'abcdefghijkmnpqrstuvwxyz', '23456789', '!@#$%&*?-_+='];
    var all = sets.join('');
    var chars = sets.map(function (set) { return set.charAt(randomIndex(set.length)); });
    while (chars.length < length) chars.push(all.charAt(randomIndex(all.length)));
    for (var i = chars.length - 1; i > 0; i--) {
      var j = randomIndex(i + 1);
      var tmp = chars[i];
      chars[i] = chars[j];
      chars[j] = tmp;
    }
    return chars.join('');
  }

  function checkPasswordRules(pw) {
    return {
      length: pw.length >= 8,
      upper: /[A-Z]/.test(pw),
      lower: /[a-z]/.test(pw),
      number: /\d/.test(pw),
      symbol: /[^A-Za-z0-9]/.test(pw)
    };
  }

  function passwordStrength(pw) {
    var r = checkPasswordRules(pw);
    var score = (r.length ? 1 : 0) + (r.upper ? 1 : 0) + (r.lower ? 1 : 0) + (r.number ? 1 : 0) + (r.symbol ? 1 : 0) + (pw.length >= 12 ? 1 : 0);
    if (score <= 3) return { label: 'Weak', level: 1 };
    if (score <= 4) return { label: 'Fair', level: 2 };
    return { label: 'Strong', level: 3 };
  }

  function fireInput(input) {
    var evt;
    try {
      evt = new Event('input', { bubbles: true });
    } catch (e) {
      evt = document.createEvent('Event');
      evt.initEvent('input', true, true);
    }
    input.dispatchEvent(evt);
  }

  function fallbackCopy(text) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); } catch (err) { /* clipboard unavailable */ }
    document.body.removeChild(ta);
  }

  function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text);
    }
    fallbackCopy(text);
    return Promise.resolve();
  }

  function updatePasswordTypes(form, showing) {
    var inputs = form.querySelectorAll('[data-password-input]');
    for (var i = 0; i < inputs.length; i++) {
      var input = inputs[i];
      input.type = showing ? 'text' : 'password';
      var mono = showing && input.value;
      input.classList.toggle('font-mono', !!mono);
      input.classList.toggle('tracking-wide', !!mono);
    }
    var toggles = form.querySelectorAll('[data-password-toggle]');
    for (var t = 0; t < toggles.length; t++) {
      var btn = toggles[t];
      var hiddenIcon = btn.querySelector('[data-icon-hidden]');
      var visibleIcon = btn.querySelector('[data-icon-visible]');
      if (hiddenIcon) hiddenIcon.classList.toggle('hidden', showing);
      if (visibleIcon) visibleIcon.classList.toggle('hidden', !showing);
      btn.setAttribute('aria-label', showing ? 'Hide password' : 'Show password');
      btn.setAttribute('title', showing ? 'Hide password' : 'Show password');
    }
  }

  function refreshStrength(input) {
    var form = input.closest('form');
    if (!form) return;
    var group = form.querySelector('[data-strength-group]');
    if (!group) return;
    var pw = input.value;
    if (!pw) {
      group.classList.add('hidden');
      group.classList.remove('flex');
      return;
    }
    group.classList.remove('hidden');
    group.classList.add('flex');
    var strength = passwordStrength(pw);
    var color = strength.level === 1 ? 'bg-red-500' : strength.level === 2 ? 'bg-amber-500' : 'bg-[#A1B2C4]';
    for (var n = 1; n <= 3; n++) {
      var bar = group.querySelector('[data-strength-bar="' + n + '"]');
      if (!bar) continue;
      bar.classList.remove('bg-red-500', 'bg-amber-500', 'bg-[#A1B2C4]');
      bar.classList.toggle('bg-slate-200', n > strength.level);
      if (n <= strength.level) bar.classList.add(color);
    }
    var label = group.querySelector('[data-strength-label]');
    if (label) label.textContent = strength.label;
  }

  function refreshConfirmMatch(form) {
    var pwInput = form.querySelector('[data-strength-input]');
    var confirmInput = form.querySelector('[data-confirm-input]');
    if (!pwInput || !confirmInput) return;
    var errorEl = form.querySelector('[data-confirm-error]');
    var mismatch = !!(confirmInput.value && confirmInput.value !== pwInput.value);
    confirmInput.classList.toggle('border-red-300', mismatch);
    confirmInput.classList.toggle('focus:border-red-400', mismatch);
    confirmInput.classList.toggle('focus:ring-red-500/10', mismatch);
    if (errorEl) errorEl.classList.toggle('hidden', !mismatch);
  }

  document.addEventListener('click', function (e) {
    var toggle = e.target.closest && e.target.closest('[data-password-toggle]');
    if (toggle) {
      var toggleForm = toggle.closest('form');
      if (!toggleForm) return;
      var anyInput = toggleForm.querySelector('[data-password-input]');
      var showing = !!anyInput && anyInput.type === 'text';
      updatePasswordTypes(toggleForm, !showing);
      return;
    }

    var genBtn = e.target.closest && e.target.closest('[data-generate-btn]');
    if (genBtn) {
      var genForm = genBtn.closest('form');
      if (!genForm || genBtn.disabled) return;
      var idle = genBtn.querySelector('[data-generate-idle]');
      var loading = genBtn.querySelector('[data-generate-loading]');
      var done = genBtn.querySelector('[data-generate-done]');
      function show(state) {
        if (idle) idle.hidden = state !== idle;
        if (loading) loading.hidden = state !== loading;
        if (done) done.hidden = state !== done;
      }

      // Idle -> "Generating..." spinner -> fill + copy -> "Generated & copied" -> back to idle
      genBtn.disabled = true;
      show(loading);
      setTimeout(function () {
        var pw = generateStrongPassword(16);
        var inputs = genForm.querySelectorAll('[data-password-input]');
        for (var i = 0; i < inputs.length; i++) {
          inputs[i].value = pw;
          fireInput(inputs[i]);
        }
        updatePasswordTypes(genForm, true);
        copyText(pw).catch(function () { /* Clipboard unavailable: password is still filled in and visible */ });

        show(done);
        setTimeout(function () {
          show(idle);
          genBtn.disabled = false;
        }, 2500);
      }, 700);
    }
  });

  document.addEventListener('input', function (e) {
    var target = e.target;
    if (!target || target.nodeType !== 1) return;
    if (target.matches && target.matches('[data-strength-input]')) refreshStrength(target);
    var form = target.closest && target.closest('form');
    if (form && target.matches && (target.matches('[data-strength-input]') || target.matches('[data-confirm-input]'))) {
      refreshConfirmMatch(form);
    }
  });

  // Tiny loading spinner while a form navigates away (purely cosmetic; the page still submits without JS)
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form.hasAttribute || !form.hasAttribute('data-loading-submit')) return;
    var btn = form.querySelector('[data-submit-button]');
    if (!btn) return;
    btn.disabled = true;
    var spinner = btn.querySelector('[data-loading-spinner]');
    if (spinner) spinner.classList.remove('hidden');
  });
})();
