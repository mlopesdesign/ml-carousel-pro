<div class="form-field">
    <label for="mlcp_width_px">Largura travada do banner (px)</label>
    <input type="number" min="1" name="mlcp_width_px" id="mlcp_width_px" value="<?php echo esc_attr($settings['width_px']); ?>" />
    <p class="description">Essa medida controla o tamanho real do banner no desktop.</p>
</div>
<div class="form-field">
    <label for="mlcp_height_px">Altura travada do banner (px)</label>
    <input type="number" min="1" name="mlcp_height_px" id="mlcp_height_px" value="<?php echo esc_attr($settings['height_px']); ?>" />
</div>
<div class="form-field">
    <label><input type="checkbox" name="mlcp_lock_proportion" value="1" <?php checked($settings['lock_proportion'], 1); ?> /> Trancar proporção dos banners</label>
    <p class="description">Quando ativo, a altura acompanha a largura do banner com base na proporção largura ÷ altura.</p>
</div>
<div class="form-field">
    <label><input type="checkbox" name="mlcp_rounded_corners" value="1" <?php checked($settings['rounded_corners'], 1); ?> /> Usar cantos arredondados</label>
</div>
<div class="form-field">
    <label for="mlcp_desktop">Itens no desktop</label>
    <input type="number" min="1" name="mlcp_desktop" id="mlcp_desktop" value="<?php echo esc_attr($settings['desktop']); ?>" />
</div>
<div class="form-field">
    <label for="mlcp_tablet">Itens no tablet</label>
    <input type="number" min="1" name="mlcp_tablet" id="mlcp_tablet" value="<?php echo esc_attr($settings['tablet']); ?>" />
</div>
<div class="form-field">
    <label for="mlcp_mobile">Itens no mobile</label>
    <input type="number" min="1" name="mlcp_mobile" id="mlcp_mobile" value="<?php echo esc_attr($settings['mobile']); ?>" />
</div>
<div class="form-field">
    <label for="mlcp_gap">Gap entre cards</label>
    <input type="number" min="0" name="mlcp_gap" id="mlcp_gap" value="<?php echo esc_attr($settings['gap']); ?>" />
</div>
<div class="form-field">
    <label for="mlcp_autoplay_speed">Autoplay speed (ms)</label>
    <input type="number" min="1000" step="100" name="mlcp_autoplay_speed" id="mlcp_autoplay_speed" value="<?php echo esc_attr($settings['autoplay_speed']); ?>" />
</div>
<div class="form-field">
    <label><input type="checkbox" name="mlcp_overlay_enabled" value="1" <?php checked($settings['overlay_enabled'], 1); ?> /> Ativar sobreamento</label>
</div>
<div class="form-field">
    <label for="mlcp_overlay_opacity">Percentual do sobreamento (%)</label>
    <input type="number" min="0" max="100" step="1" name="mlcp_overlay_opacity" id="mlcp_overlay_opacity" value="<?php echo esc_attr($settings['overlay_opacity']); ?>" />
    <p class="description">0 = sem sobreamento. 100 = sobreamento máximo.</p>
</div>
<div class="form-field">
    <label><input type="checkbox" name="mlcp_autoplay" value="1" <?php checked($settings['autoplay'], 1); ?> /> Autoplay</label>
</div>
<div class="form-field">
    <label><input type="checkbox" name="mlcp_arrows" value="1" <?php checked($settings['arrows'], 1); ?> /> Mostrar setas</label>
</div>
<div class="form-field">
    <label><input type="checkbox" name="mlcp_show_title" value="1" <?php checked($settings['show_title'], 1); ?> /> Mostrar título</label>
</div>
<div class="form-field">
    <label><input type="checkbox" name="mlcp_show_subtitle" value="1" <?php checked($settings['show_subtitle'], 1); ?> /> Mostrar subtítulo</label>
</div>
