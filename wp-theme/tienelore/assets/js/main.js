(function () {
  'use strict';

  // Menú de categorías en móvil
  var navToggle = document.getElementById('nav-toggle');
  var catnav = document.getElementById('site-catnav');
  if (navToggle && catnav) {
    navToggle.addEventListener('click', function () {
      var isOpen = catnav.classList.toggle('is-open');
      navToggle.setAttribute('aria-expanded', String(isOpen));
    });
  }

  // Buscador
  var searchToggle = document.getElementById('search-toggle');
  var searchOverlay = document.getElementById('tienelore-search');
  if (searchToggle && searchOverlay) {
    searchToggle.addEventListener('click', function () {
      var isOpen = searchOverlay.hasAttribute('hidden');
      if (isOpen) {
        searchOverlay.removeAttribute('hidden');
        var input = searchOverlay.querySelector('input');
        if (input) input.focus();
      } else {
        searchOverlay.setAttribute('hidden', '');
      }
      searchToggle.setAttribute('aria-expanded', String(isOpen));
    });
  }

  // Barra de progreso de lectura (solo en single.php, cuando existe #article-body)
  var readingBar = document.getElementById('reading-bar');
  var articleBody = document.getElementById('article-body');
  if (readingBar && articleBody) {
    var onScroll = function () {
      var rect = articleBody.getBoundingClientRect();
      var total = rect.height - window.innerHeight;
      var scrolled = -rect.top;
      var pct = total > 0 ? Math.min(1, Math.max(0, scrolled / total)) : 0;
      readingBar.style.transform = 'scaleX(' + pct + ')';
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }
})();
