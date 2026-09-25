/*
 * MailDart — Guide page table-of-contents scroll-spy (was a useEffect + IntersectionObserver in GuidePage.tsx).
 * The [data-guide-toc-link] items are plain <a href="#id"> anchors, so the page and its links work perfectly
 * without this script; it only adds the "currently reading" highlight as you scroll.
 */
(function () {
  'use strict';

  var sections = document.querySelectorAll('[data-guide-section]');
  var tocLinks = document.querySelectorAll('[data-guide-toc-link]');
  if (!sections.length || !tocLinks.length || !window.IntersectionObserver) return;

  function setActive(id) {
    tocLinks.forEach(function (a) {
      var match = a.getAttribute('data-guide-toc-link') === id;
      a.classList.toggle('border-brand-600', match);
      a.classList.toggle('text-brand-700', match);
      a.classList.toggle('font-semibold', match);
      a.classList.toggle('border-transparent', !match);
      a.classList.toggle('text-slate-500', !match);
    });
  }

  var observer = new IntersectionObserver(function (entries) {
    var visible = entries
      .filter(function (entry) { return entry.isIntersecting; })
      .sort(function (a, b) { return a.boundingClientRect.top - b.boundingClientRect.top; });
    if (visible[0]) setActive(visible[0].target.id);
  }, { rootMargin: '-96px 0px -60% 0px' });

  sections.forEach(function (el) { observer.observe(el); });
})();
