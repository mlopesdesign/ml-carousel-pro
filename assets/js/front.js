(function(){
  'use strict';

  /* =============================================
     MLCP LIGHTBOX
     ============================================= */
  var lb = null;

  function buildLightbox(){
    if (lb) return lb;
    var overlay = document.createElement('div');
    overlay.id = 'mlcp-lb-overlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', 'Lightbox');
    overlay.innerHTML =
      '<button id="mlcp-lb-close" type="button" aria-label="Fechar">&times;</button>' +
      '<div id="mlcp-lb-inner"><img id="mlcp-lb-img" src="" alt="" /></div>';
    document.body.appendChild(overlay);
    lb = {
      overlay: overlay,
      img:     overlay.querySelector('#mlcp-lb-img'),
      close:   overlay.querySelector('#mlcp-lb-close')
    };
    function closeLb(){
      overlay.classList.remove('mlcp-lb-open');
      overlay.setAttribute('aria-hidden', 'true');
      lb.img.src = '';
      lb.img.alt = '';
    }
    lb.close.addEventListener('click', function(e){ e.stopPropagation(); closeLb(); });
    overlay.addEventListener('click', function(e){
      if (e.target === overlay || e.target === overlay.querySelector('#mlcp-lb-inner')) closeLb();
    });
    document.addEventListener('keydown', function(e){
      if ((e.key === 'Escape' || e.keyCode === 27) && overlay.classList.contains('mlcp-lb-open')) closeLb();
    });
    return lb;
  }

  function openLightbox(src, alt){
    var l = buildLightbox();
    l.img.src  = src;
    l.img.alt  = alt || '';
    l.overlay.removeAttribute('aria-hidden');
    l.overlay.classList.add('mlcp-lb-open');
    l.close.focus();
  }

  document.addEventListener('click', function(e){
    var el = e.target.closest('[data-mlcp-lightbox]');
    if (!el) return;
    e.preventDefault();
    e.stopPropagation();
    openLightbox(el.getAttribute('data-mlcp-lightbox'), el.getAttribute('data-mlcp-lightbox-alt') || '');
  });

  document.addEventListener('keydown', function(e){
    if (e.key !== 'Enter' && e.key !== ' ') return;
    var el = e.target.closest('[data-mlcp-lightbox]');
    if (!el) return;
    e.preventDefault();
    openLightbox(el.getAttribute('data-mlcp-lightbox'), el.getAttribute('data-mlcp-lightbox-alt') || '');
  });

  /* =============================================
     MLCP ANALYTICS TRACKING
     Anti-inflation: carouselId:itemId key, debounce clicks 650ms
     Click capture phase — prevents bubbling duplication
  ============================================= */
  var mlcpViewed    = Object.create(null);
  var mlcpClickedAt = Object.create(null);

  function sendTrack(itemId, eventName){
    if (!itemId || !window.mlcpFront || !window.mlcpFront.ajaxUrl) return;
    var form = new FormData();
    form.append('action',  'mlcp_track_event');
    form.append('nonce',   window.mlcpFront.nonce || '');
    form.append('item_id', itemId);
    form.append('event',   eventName);
    if (navigator.sendBeacon){
      navigator.sendBeacon(window.mlcpFront.ajaxUrl, form);
      return;
    }
    fetch(window.mlcpFront.ajaxUrl, {
      method: 'POST', body: form, credentials: 'same-origin', keepalive: true
    }).catch(function(){});
  }

  function trackView(carouselId, itemId){
    if (!itemId) return;
    var key = (carouselId || 'root') + ':' + itemId;
    if (mlcpViewed[key]) return;
    mlcpViewed[key] = true;
    sendTrack(itemId, 'view');
  }

  function trackClick(itemId){
    if (!itemId) return;
    var now = Date.now();
    if (mlcpClickedAt[itemId] && now - mlcpClickedAt[itemId] < 650) return;
    mlcpClickedAt[itemId] = now;
    sendTrack(itemId, 'click');
  }

  document.addEventListener('click', function(e){
    var el = e.target.closest('[data-mlcp-track-click]');
    if (!el) return;
    var card   = el.closest('.mlcp-card');
    var itemId = el.getAttribute('data-mlcp-item-id') || (card && card.getAttribute('data-mlcp-item-id'));
    if (itemId) trackClick(itemId);
  }, true);

  function trackVisibleSlides(root, index){
    var carouselId = root.id || root.getAttribute('data-mlcp-id') || '';
    var cards      = root.querySelectorAll('.mlcp-card');
    var count      = visibleCount(root);
    for (var i = index; i < Math.min(cards.length, index + count); i++){
      var card   = cards[i];
      if (!card) continue;
      var itemId = card.getAttribute('data-mlcp-item-id');
      if (!itemId) continue;
      trackView(carouselId, itemId);
    }
  }

  /* =============================================
     MLCP CAROUSEL ENGINE
     Conflict-resistant:
     - cardStep() uses CSS custom property fallback when DOM metrics are 0
     - boot retries up to 3x with rAF + delay to handle themes with
       deferred visibility / AOS / Intersection Observer animations
     - CSS specificity handled via !important in front.css
     - nav buttons: stopPropagation to prevent theme click handlers
  ============================================= */

  function isMobileLayout(root){
    var w = window.innerWidth || document.documentElement.clientWidth || 0;
    var r = Math.round(root.getBoundingClientRect().width || 0);
    return w <= 640 || (r > 0 && r <= 640);
  }

  function visibleCount(root){
    if (isMobileLayout(root)) return 1;
    var w = window.innerWidth || document.documentElement.clientWidth;
    if (w <= 980) return parseInt(root.dataset.tablet  || '2', 10) || 2;
    return              parseInt(root.dataset.desktop || '3', 10) || 3;
  }

  function applyMobileMetrics(root){
    var viewport = root.querySelector('.mlcp-viewport');
    if (!viewport) return;
    var computed            = window.getComputedStyle(root);
    var configuredCardWidth = parseFloat(computed.getPropertyValue('--mlcp-card-width')) || 320;
    var rootWidth           = Math.round(root.getBoundingClientRect().width || 0);
    var viewportWidth       = Math.round(viewport.getBoundingClientRect().width || 0);
    var usableWidth = Math.max(1, Math.min(
      configuredCardWidth,
      viewportWidth || configuredCardWidth,
      rootWidth     || configuredCardWidth
    ));
    if (isMobileLayout(root)){
      root.style.setProperty('--mlcp-mobile-card-width', usableWidth + 'px');
      root.style.setProperty('--mlcp-mobile-gap', '12px');
      root.dataset.mlcpMobileLayout = '1';
      return;
    }
    root.style.removeProperty('--mlcp-mobile-card-width');
    root.style.removeProperty('--mlcp-mobile-gap');
    root.dataset.mlcpMobileLayout = '0';
  }

  /**
   * cardStep() — conflict-resistant
   *
   * Primary: offsetLeft diff between card[0] and card[1] (real DOM layout)
   * Fallback A: card getBoundingClientRect width + CSS gap
   * Fallback B: CSS custom property --mlcp-card-width from inline style
   * Fallback C: hard 320px
   *
   * Returns 0 only when all cards have zero width — indicates the carousel
   * is not yet visible (hidden parent, AOS, etc). The retry loop in
   * initCarousel() handles this by deferring until dimensions are real.
   */
  function cardStep(root, cards){
    var first  = cards[0];
    var second = cards[1];

    // Primary: offsetLeft diff
    if (second){
      var diff = second.offsetLeft - first.offsetLeft;
      if (diff > 1) return diff;
    }

    // Fallback A: card width from getBoundingClientRect + gap from computed style
    var track     = root.querySelector('.mlcp-track');
    var cardWidth = first.getBoundingClientRect().width || first.offsetWidth || 0;
    if (cardWidth > 1){
      var gap = 0;
      if (track){
        var ts = window.getComputedStyle(track);
        gap = parseFloat(ts.columnGap || ts.gap || 0) || 0;
      }
      return cardWidth + gap;
    }

    // Fallback B: read --mlcp-card-width from root inline style
    var rootStyle = root.getAttribute('style') || '';
    var match = rootStyle.match(/--mlcp-card-width:\s*([\d.]+)px/);
    if (match){
      return parseFloat(match[1]) || 0;
    }

    // Fallback C: read from computed CSS custom property
    var computed = window.getComputedStyle(root);
    var cssVal = parseFloat(computed.getPropertyValue('--mlcp-card-width')) || 0;
    if (cssVal > 1) return cssVal;

    return 0; // not visible yet — retry will handle
  }

  function initCarousel(root){
    var track = root.querySelector('.mlcp-track');
    var cards = root.querySelectorAll('.mlcp-card');
    var prev  = root.querySelector('.mlcp-prev');
    var next  = root.querySelector('.mlcp-next');
    if (!track || !cards.length) return;

    var index     = 0;
    var timer     = null;
    var loopTimer = null;
    var retryCount = 0;
    var MAX_RETRIES = 8;

    function maxIndex(){
      return Math.max(0, cards.length - visibleCount(root));
    }

    function update(){
      applyMobileMetrics(root);
      var max = maxIndex();
      if (index > max) index = max;
      if (index < 0)   index = 0;
      var step = cardStep(root, cards);
      if (step > 0){
        track.style.transform = 'translateX(' + (index * step * -1) + 'px)';
      }
      trackVisibleSlides(root, index);
    }

    function stop(){
      if (timer)     { window.clearTimeout(timer);     timer     = null; }
      if (loopTimer) { window.clearTimeout(loopTimer); loopTimer = null; }
    }

    function scheduleNext(speed){
      stop();
      var max = maxIndex();
      if (index >= max){
        loopTimer = window.setTimeout(function(){
          index = 0;
          update();
          scheduleNext(speed);
        }, 5000);
      } else {
        timer = window.setTimeout(function(){
          index++;
          update();
          scheduleNext(speed);
        }, Math.max(1000, speed));
      }
    }

    function start(){
      var autoplay = parseInt(root.dataset.autoplay      || '0',    10);
      var speed    = parseInt(root.dataset.autoplaySpeed || '4500', 10);
      stop();
      if (autoplay && cards.length > visibleCount(root)){
        scheduleNext(speed);
      }
    }

    function refresh(){
      update();
      start();
    }

    /**
     * tryBoot — retries up to MAX_RETRIES times until cardStep() > 0.
     * Handles deferred-visibility themes (AOS.js, Elementor animations,
     * jQuery themes that show content after page load, hidden tab panels, etc.)
     */
    function tryBoot(){
      var step = cardStep(root, cards);
      if (step > 0){
        // Dimensions available — boot normally
        retryCount = 0;
        refresh();
        return;
      }
      if (retryCount < MAX_RETRIES){
        retryCount++;
        // Exponential delay: 100ms, 200ms, 400ms, 800ms …
        var delay = Math.min(100 * Math.pow(2, retryCount - 1), 2000);
        window.setTimeout(function(){
          window.requestAnimationFrame(tryBoot);
        }, delay);
      }
      // After MAX_RETRIES: silently stop (carousel may genuinely be hidden)
    }

    /* nav buttons: stopPropagation to prevent theme event handlers
       from intercepting clicks (common in Elementor, Divi, Avada, etc.) */
    if (prev) {
      prev.addEventListener('click', function(e){
        e.stopPropagation();
        index = (index <= 0) ? maxIndex() : index - 1;
        update();
        start();
      });
    }

    if (next) {
      next.addEventListener('click', function(e){
        e.stopPropagation();
        index = (index >= maxIndex()) ? 0 : index + 1;
        update();
        start();
      });
    }

    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);

    // resize / layout change
    var resizeTimer = null;
    window.addEventListener('resize', function(){
      window.clearTimeout(resizeTimer);
      resizeTimer = window.setTimeout(refresh, 120);
    });

    if ('ResizeObserver' in window){
      var ro = new ResizeObserver(function(){
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(refresh, 120);
      });
      ro.observe(root);
      var vp = root.querySelector('.mlcp-viewport');
      if (vp) ro.observe(vp);
    }

    // img load may change layout
    root.querySelectorAll('img').forEach(function(img){
      if (!img.complete){
        img.addEventListener('load',  function(){ window.requestAnimationFrame(tryBoot); }, { once: true });
        img.addEventListener('error', function(){ window.requestAnimationFrame(tryBoot); }, { once: true });
      }
    });

    // Initial boot
    window.requestAnimationFrame(tryBoot);
  }

  function bootCarousels(){
    document.querySelectorAll('.mlcp-carousel').forEach(function(root){
      if (root.dataset.mlcpReady === '1') return;
      root.dataset.mlcpReady = '1';
      initCarousel(root);
    });
  }

  if (document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', bootCarousels, { once: true });
  } else {
    bootCarousels();
  }

  // Redundant load event handles themes that inject content after DOMContentLoaded
  window.addEventListener('load', function(){
    document.querySelectorAll('.mlcp-carousel').forEach(function(root){
      // Re-trigger tryBoot for any carousel that booted with step=0
      if (root.dataset.mlcpReady === '1') {
        var track = root.querySelector('.mlcp-track');
        var cards = root.querySelectorAll('.mlcp-card');
        if (track && cards.length && track.style.transform === 'translateX(0px)'){
          // Transform is 0 — may have been stuck; trigger a fresh refresh
          root.dataset.mlcpReady = '0';
        }
      }
    });
    bootCarousels();
  });

})();
