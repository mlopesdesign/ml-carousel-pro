jQuery(function($){
  let mediaFrame = null;

  function showToast(message, type) {
    let $toast = $('#mlcp-copy-toast');
    if (!$toast.length) {
      $toast = $('<div id="mlcp-copy-toast" class="mlcp-copy-toast" aria-hidden="true"></div>').appendTo('body');
    }
    $toast.removeClass('is-error is-success').addClass(type === 'error' ? 'is-error' : 'is-success').text(message).addClass('is-visible').attr('aria-hidden', 'false');
    window.clearTimeout($toast.data('timer'));
    const timer = window.setTimeout(function(){
      $toast.removeClass('is-visible').attr('aria-hidden', 'true');
    }, 5000);
    $toast.data('timer', timer);
  }

  async function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
      await navigator.clipboard.writeText(text);
      return true;
    }

    const $temp = $('<textarea readonly></textarea>').css({position:'absolute',left:'-9999px',top:'0'}).val(text).appendTo('body');
    $temp[0].select();
    const ok = document.execCommand('copy');
    $temp.remove();
    return ok;
  }

  $(document).on('click', '.mlcp-copy-shortcode', async function(e){
    e.preventDefault();
    const shortcode = $(this).data('shortcode') || '';
    if (!shortcode) return;

    try {
      const ok = await copyText(shortcode);
      showToast(ok ? 'Shortcode copiado.' : 'Não foi possível copiar.', ok ? 'success' : 'error');
    } catch (err) {
      showToast('Não foi possível copiar.', 'error');
    }
  });

  $(document).on('click', '#mlcp_choose_image', function(e){
    e.preventDefault();

    if (mediaFrame) {
      mediaFrame.open();
      return;
    }

    mediaFrame = wp.media({
      title: mlcpAdmin.chooseImage,
      button: { text: mlcpAdmin.useImage },
      multiple: false
    });

    mediaFrame.on('select', function(){
      const attachment = mediaFrame.state().get('selection').first().toJSON();
      $('#mlcp_image_id').val(attachment.id || '');
      $('#mlcp_image_url').val(attachment.url || '');
      $('#mlcp_preview_image').attr('src', attachment.url || '').show();
    });

    mediaFrame.open();
  });

  $(document).on('click', '#mlcp_remove_image', function(e){
    e.preventDefault();
    $('#mlcp_image_id').val('');
    $('#mlcp_image_url').val('');
    $('#mlcp_preview_image').attr('src', '').hide();
  });

  $(document).on('input', '#mlcp_image_url', function(){
    const url = $(this).val();
    if (url) {
      $('#mlcp_preview_image').attr('src', url).show();
    }
  });

  function makeSortable() {
    $('.mlcp-sort-list').sortable({
      handle: '.mlcp-sort-handle'
    });
  }


  const $noticeSource = $('.mlcp-toast-source').first();
  if ($noticeSource.length) {
    const message = $noticeSource.data('message') || '';
    const type = $noticeSource.data('type') || 'success';
    if (message) {
      showToast(message, type);
      if (window.history && window.history.replaceState) {
        const url = new URL(window.location.href);
        url.searchParams.delete('mlcp_notice');
        url.searchParams.delete('mlcp_notice_type');
        window.history.replaceState({}, document.title, url.toString());
      }
    }
  }

  $('#mlcp-load-sort').on('click', function(){
    const groupId = $('#mlcp-sort-group').val();
    if (!groupId) {
      showToast(mlcpAdmin.selectGroupFirst, 'error');
      return;
    }

    const $container = $('#mlcp-sort-container');
    $container.html('<div class="mlcp-empty-state">Carregando...</div>');

    $.post(mlcpAdmin.ajaxUrl, {
      action: 'mlcp_load_sort_group',
      nonce: mlcpAdmin.nonce,
      group_id: groupId
    }).done(function(resp){
      if (resp && resp.success) {
        $container.html(resp.data.html);
        makeSortable();
      } else {
        $container.html('<div class="mlcp-empty-state">Falha ao carregar.</div>');
      }
    }).fail(function(){
      $container.html('<div class="mlcp-empty-state">Falha ao carregar.</div>');
    });
  });

  $('#mlcp-save-sort').on('click', function(){
    const order = [];
    $('.mlcp-sort-item').each(function(){
      order.push($(this).data('post-id'));
    });

    $.post(mlcpAdmin.ajaxUrl, {
      action: 'mlcp_save_sort_group',
      nonce: mlcpAdmin.nonce,
      order: order
    }).done(function(resp){
      if (resp && resp.success) {
        showToast(mlcpAdmin.saveSuccess, 'success');
      } else {
        showToast(mlcpAdmin.saveError, 'error');
      }
    }).fail(function(){
      showToast(mlcpAdmin.saveError);
    });
  });
});
