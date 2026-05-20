<div class="mlcp-item-grid">
    <div class="mlcp-field mlcp-field-full">
        <label>Imagem</label>
        <div class="mlcp-image-picker">
            <input type="hidden" name="mlcp_image_id" id="mlcp_image_id" value="<?php echo esc_attr($meta['image_id']); ?>" />
            <input type="text" name="mlcp_image_url" id="mlcp_image_url" value="<?php echo esc_attr($meta['image_url']); ?>" placeholder="https://..." />
            <button type="button" class="button" id="mlcp_choose_image">Biblioteca</button>
            <button type="button" class="button" id="mlcp_remove_image">Limpar</button>
        </div>
        <div class="mlcp-preview">
            <?php if ($meta['image_url']) : ?>
                <img id="mlcp_preview_image" src="<?php echo esc_url($meta['image_url']); ?>" alt="" />
            <?php else : ?>
                <img id="mlcp_preview_image" src="" alt="" style="display:none;" />
            <?php endif; ?>
        </div>
    </div>

    <div class="mlcp-field">
        <label for="mlcp_date">Data</label>
        <input type="text" name="mlcp_date" id="mlcp_date" value="<?php echo esc_attr($meta['date']); ?>" />
    </div>

    <div class="mlcp-field">
        <label for="mlcp_subtitle">Subtítulo</label>
        <input type="text" name="mlcp_subtitle" id="mlcp_subtitle" value="<?php echo esc_attr($meta['subtitle']); ?>" />
    </div>

    <div class="mlcp-field">
        <label for="mlcp_link">Link</label>
        <input type="url" name="mlcp_link" id="mlcp_link" value="<?php echo esc_attr($meta['link']); ?>" />
    </div>

    <div class="mlcp-field mlcp-field-compact">
        <label for="menu_order">Ordem manual</label>
        <input type="number" name="menu_order" id="menu_order" min="0" max="99" step="1" value="<?php echo esc_attr((int) $post->menu_order); ?>" />
    </div>
</div>
