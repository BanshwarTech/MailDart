<button type="button" data-back-to-top aria-label="Back to top" title="Back to top" style="display:none;position:fixed;right:24px;bottom:24px;z-index:50;width:48px;height:48px" class="fixed bottom-6 right-6 z-50 h-12 w-12 items-center justify-center rounded-full bg-brand-600 text-white shadow-lg transition hover:bg-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-500/30">
  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5" /><path d="m5 12 7-7 7 7" /></svg>
</button>
<script>
  (function () {
    var button = document.querySelector('[data-back-to-top]');
    if (!button) return;
    function updateButton() {
      var scrollTop = window.scrollY || document.documentElement.scrollTop || document.body.scrollTop || 0;
      button.style.display = scrollTop > 300 ? 'flex' : 'none';
    }
    window.addEventListener('scroll', updateButton, { passive: true });
    document.addEventListener('scroll', updateButton, { passive: true, capture: true });
    updateButton();
    button.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
  })();
</script>
