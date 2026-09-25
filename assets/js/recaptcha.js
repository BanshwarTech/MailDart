/*
 * reCAPTCHA v3 for forms marked form[data-recaptcha-action][data-recaptcha-site-key] (plain JavaScript).
 * On submit: ask Google for a fresh token (they expire after 2 minutes), put it in the hidden
 * "g-recaptcha-response" field, then submit for real. The server verifies it (includes/recaptcha.php).
 */
(function () {
  'use strict';

  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form.hasAttribute || !form.hasAttribute('data-recaptcha-action')) return;
    if (form.getAttribute('data-recaptcha-ready') === '1') return; // token already attached: let it go

    e.preventDefault();
    var field = form.querySelector('input[name="g-recaptcha-response"]');
    var action = form.getAttribute('data-recaptcha-action');
    var siteKey = form.getAttribute('data-recaptcha-site-key');

    var send = function (token) {
      if (field) field.value = token || '';
      form.setAttribute('data-recaptcha-ready', '1');
      form.submit();
    };

    if (!window.grecaptcha || !grecaptcha.ready) {
      send(''); // Google script blocked/offline: the server shows a friendly error
      return;
    }
    grecaptcha.ready(function () {
      grecaptcha.execute(siteKey, { action: action }).then(send, function () { send(''); });
    });
  });
})();
