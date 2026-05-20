(function(){
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

    lb.close.addEventListener('click', function(e){
      e.stopPropagation();
      closeLb();
    });
    overlay.addEventListener('click', function(e){
      if (e.target === overlay || e.target === overlay.querySelector('#mlcp-lb-inner')){
        closeLb();
      }
    });
    document.addEventListener('keydown', function(e){
      if ((e.key === 'Escape' || e.keyCode === 27) && overlay.classList.contains('mlcp-lb-open')){
        closeLb();
      }
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
     ============================================= */
  var mlcpViewed    = Object.create(null);
  var mlcpClickedAt = Object.create(null);

  function sendTrack(itemId, eventName){
    if (!itemId || !window.mlcpFront || !window.mlcpFront.ajaxUrl) return;
    var form = new FormData();
    form.append('action', 'mlcp_track_event');
    form.append('nonce',   window.mlcpFront.nonce || '');
    form.append('item_id', itemId);
    form.append('event',   eventName);
    if (navigator.sendBeacon){
      navigator.sendBeacon(window.mlcpFront.ajaxUrl, form);
      return;
    }
    fetch(window.mlcpFront.ajaxUrl, { method: 'POST', body: form, credentials: 'same-origin', keepalive: true }).catch(function(){});
  }

  function trackClick(el){
    var itemId = el && (el.getAttribute('data-mlcp-item-id') || (el.closest('.mlcp-card') && el.closest('.mlcp-card').getAttribute('data-mlcp-item-id')));
    if (!itemId) return;
    var now = Date.now();
    if (mlcpClickedAt[itemId] && now - mlcpClickedAt[itemId] < 650) return;
    mlcpClickedAt[itemId] = now;
    sendTrack(itemId, 'click');
  }

  document.addEventListener('click', function(e){
    var el = e.target.closest('[data-mlcp-track-click]');
    if (!el) return;
    trackClick(el);
  }, true);

  function trackVisibleSlides(root, index){
    var cards = root.querySelectorAll('.mlcp-card');
    var count = visibleCount(root);
    for (var i = index; i < Math.min(cards.length, index + count); i++){
      var card = cards[i];
      if (!card) continue;
      var itemId = card.getAttribute('data-mlcp-item-id');
      if (!itemId) continue;
      var key = root.id + ':' + itemId + ':' + index;
      if (mlcpViewed[key]) continue;
      mlcpViewed[key] = true;
      sendTrack(itemId, 'view');
    }
  }

  /* =============================================
     MLCP CAROUSEL ENGINE
     ============================================= */
  function isMobileLayout(root){
    var windowWidth = window.innerWidth || document.documentElement.clientWidth || 0;
    var rootWidth   = Math.round(root.getBoundingClientRect().width || 0);
    return windowWidth <= 640 || (rootWidth > 0 && rootWidth <= 640);
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
    var computed           = window.getComputedStyle(root);
    var configuredCardWidth = parseFloat(computed.getPropertyValue('--mlcp-card-width')) || 320;
    var rootWidth          = Math.round(root.getBoundingClientRect().width  || 0);
    var viewportWidth      = Math.round(viewport.getBoundingClientRect().width || 0);
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

  function initCarousel(root){
    var track = root.querySelector('.mlcp-track');
    var cards = root.querySelectorAll('.mlcp-card');
    var prev  = root.querySelector('.mlcp-prev');
    var next  = root.querySelector('.mlcp-next');
    if (!track || !cards.length) return;

    var index     = 0;
    var timer     = null;
    var loopTimer = null; // separate timer for the 5s pause at last slide

    function maxIndex(){
      return Math.max(0, cards.length - visibleCount(root));
    }

    function cardStep(){
      var first  = cards[0];
      var second = cards[1];
      var firstWidth = first.getBoundingClientRect().width || first.offsetWidth || 0;
      if (second){
        var step = second.offsetLeft - first.offsetLeft;
        if (step > 0) return step;
      }
      var styles = window.getComputedStyle(track);
      var gap    = parseFloat(styles.columnGap || styles.gap || 0) || 0;
      return Math.max(1, firstWidth + gap);
    }

    function update(){
      applyMobileMetrics(root);
      var max = maxIndex();
      if (index > max) index = max;
      if (index < 0)   index = 0;
      track.style.transform = 'translateX(' + (index * cardStep() * -1) + 'px)';
      trackVisibleSlides(root, index);

      // Update arrow visibility — show always, disable only when no loop
      // Since we loop, arrows are always functional — never disable them
      if (prev) prev.disabled = false;
      if (next) next.disabled = false;
    }

    function stop(){
      if (timer)     { window.clearTimeout(timer);     timer     = null; }
      if (loopTimer) { window.clearTimeout(loopTimer); loopTimer = null; }
    }

    function scheduleNext(speed){
      stop();
      var max = maxIndex();

      if (index >= max){
        // At last slide — wait 5 seconds then jump back to first
        loopTimer = window.setTimeout(function(){
          index = 0;
          update();
          scheduleNext(speed);
        }, 5000);
      } else {
        // Normal advance
        timer = window.setTimeout(function(){
          index++;
          update();
          scheduleNext(speed);
        }, Math.max(1000, speed));
      }
    }

    function start(){
      var autoplay = parseInt(root.dataset.autoplay     || '0',    10);
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

    // Arrows — navigate and restart autoplay from current position
    if (prev) prev.addEventListener('click', function(){
      index = (index <= 0) ? maxIndex() : index - 1;
      update();
      start();
    });

    if (next) next.addEventListener('click', function(){
      index = (index >= maxIndex()) ? 0 : index + 1;
      update();
      start();
    });

    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);
    window.addEventListener('resize', refresh);

    if ('ResizeObserver' in window){
      var ro = new ResizeObserver(refresh);
      ro.observe(root);
      ro.observe(track);
      var vp = root.querySelector('.mlcp-viewport');
      if (vp) ro.observe(vp);
    }

    root.querySelectorAll('img').forEach(function(img){
      if (!img.complete){
        img.addEventListener('load',  refresh, { once: true });
        img.addEventListener('error', refresh, { once: true });
      }
    });

    window.requestAnimationFrame(refresh);
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

  window.addEventListener('load', bootCarousels);
})();
