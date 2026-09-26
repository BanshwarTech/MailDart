/*
 * SMTP settings page — switch Quick Setup presets and setup guides in place, without a page reload.
 * Without JS the same buttons still submit their smtp.* action (see views/pages/settings.php).
 *   [data-smtp-presets] button[data-preset-host][data-preset-port][data-preset-help]
 *     fills host/port/secure, marks itself active (data-active-class / data-inactive-class), shows its guide
 *   [data-help-toggle="elastic|gmail|"]   toggle a guide ("" closes it)
 *   [data-help-panel="elastic|gmail"]     the guide boxes
 */
(function () {
  'use strict';

  var presets = document.querySelector('[data-smtp-presets]');
  if (!presets) return;
  var form = presets.closest('form');

  function field(name) { return form.querySelector('[name="' + name + '"]'); }

  function setActive(el, active) {
    el.className = el.getAttribute(active ? 'data-active-class' : 'data-inactive-class');
  }

  function showHelp(help) {
    field('help').value = help;
    form.querySelectorAll('[data-help-panel]').forEach(function (panel) {
      panel.hidden = panel.getAttribute('data-help-panel') !== help;
    });
  }

  function markActive(button) {
    presets.querySelectorAll('button[data-preset-host]').forEach(function (b) {
      var active = b === button;
      b.setAttribute('aria-pressed', active ? 'true' : 'false');
      setActive(b, active);
      b.querySelectorAll('[data-active-class]').forEach(function (child) { setActive(child, active); });
    });
  }

  presets.addEventListener('click', function (e) {
    var button = e.target.closest('button[data-preset-host]');
    if (!button) return;
    e.preventDefault();
    var port = button.getAttribute('data-preset-port');
    field('host').value = button.getAttribute('data-preset-host');
    field('port').value = port;
    field('secure').checked = port === '465';
    markActive(button);
    showHelp(button.getAttribute('data-preset-help'));
  });

  form.addEventListener('click', function (e) {
    var toggle = e.target.closest('[data-help-toggle]');
    if (!toggle) return;
    e.preventDefault();
    var help = toggle.getAttribute('data-help-toggle');
    showHelp(help !== '' && field('help').value === help ? '' : help);
  });

  // Typing a host by hand: highlight the matching preset, or none.
  field('host').addEventListener('input', function () {
    var host = this.value.trim().toLowerCase();
    var match = null;
    presets.querySelectorAll('button[data-preset-host]').forEach(function (b) {
      if (b.getAttribute('data-preset-host') === host) match = b;
    });
    markActive(match);
  });
})();
