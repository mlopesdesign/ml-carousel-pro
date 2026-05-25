<tr class="form-field">
    <th scope="row"><label for="mlcp_width_px">Largura travada do banner (px)</label></th>
    <td><input type="number" min="1" name="mlcp_width_px" id="mlcp_width_px" value="<?php echo esc_attr($settings['width_px']); ?>" /><p class="description">Essa medida controla o tamanho real do banner no desktop.</p></td>
</tr>
<tr class="form-field">
    <th scope="row"><label for="mlcp_height_px">Altura travada do banner (px)</label></th>
    <td><input type="number" min="1" name="mlcp_height_px" id="mlcp_height_px" value="<?php echo esc_attr($settings['height_px']); ?>" /></td>
</tr>
<tr class="form-field">
    <th scope="row">Proporção</th>
    <td><label><input type="checkbox" name="mlcp_lock_proportion" value="1" <?php checked($settings['lock_proportion'], 1); ?> /> Trancar proporção dos banners</label><p class="description">Quando ativo, a altura acompanha a largura do banner com base na proporção largura ÷ altura.</p></td>
</tr>
<tr class="form-field">
    <th scope="row"><label for="mlcp_card_bg_color">Cor de fundo do banner</label></th>
    <td>
        <input type="text"
               name="mlcp_card_bg_color"
               id="mlcp_card_bg_color"
               value="<?php echo esc_attr($settings['card_bg_color']); ?>"
               class="mlcp-color-picker"
               data-default-color="" />
        <p class="description">Cor exibida quando a imagem não preenche o frame (modo Contain) ou quando não há imagem. Deixe em branco para transparente.</p>
    </td>
</tr>
<tr class="form-field">
    <th scope="row"><label for="mlcp_card_margin">Margem interna do banner (px)</label></th>
    <td>
        <input type="number" min="0" max="200" step="1"
               name="mlcp_card_margin"
               id="mlcp_card_margin"
               value="<?php echo esc_attr($settings['card_margin']); ?>" />
        <p class="description">Espaço interno entre a borda do card e a imagem. 0 = sem margem (padrão).</p>
    </td>
</tr>
<tr class="form-field">
    <th scope="row"><label for="mlcp_image_fit">Ajuste da imagem</label></th>
    <td>
        <select name="mlcp_image_fit" id="mlcp_image_fit">
            <option value="cover" <?php selected($settings['image_fit'], 'cover'); ?>>Cover — preenche e corta</option>
            <option value="contain" <?php selected($settings['image_fit'], 'contain'); ?>>Contain — exibe inteira sem cortar</option>
        </select>
        <p class="description">Use <strong>Contain</strong> quando as imagens do grupo têm proporções diferentes e não podem ser cortadas.</p>
    </td>
</tr>
<tr class="form-field">
    <th scope="row">Cantos</th>
    <td><label><input type="checkbox" name="mlcp_rounded_corners" value="1" <?php checked($settings['rounded_corners'], 1); ?> /> Usar cantos arredondados</label></td>
</tr>
<tr class="form-field">
    <th scope="row"><label for="mlcp_desktop">Itens no desktop</label></th>
    <td><input type="number" min="1" name="mlcp_desktop" id="mlcp_desktop" value="<?php echo esc_attr($settings['desktop']); ?>" /></td>
</tr>
<tr class="form-field">
    <th scope="row"><label for="mlcp_tablet">Itens no tablet</label></th>
    <td><input type="number" min="1" name="mlcp_tablet" id="mlcp_tablet" value="<?php echo esc_attr($settings['tablet']); ?>" /></td>
</tr>
<tr class="form-field">
    <th scope="row"><label for="mlcp_mobile">Itens no mobile</label></th>
    <td><input type="number" min="1" name="mlcp_mobile" id="mlcp_mobile" value="<?php echo esc_attr($settings['mobile']); ?>" /></td>
</tr>
<tr class="form-field">
    <th scope="row"><label for="mlcp_gap">Gap entre cards</label></th>
    <td><input type="number" min="0" name="mlcp_gap" id="mlcp_gap" value="<?php echo esc_attr($settings['gap']); ?>" /></td>
</tr>
<tr class="form-field">
    <th scope="row"><label for="mlcp_autoplay_speed">Autoplay speed (ms)</label></th>
    <td><input type="number" min="1000" step="100" name="mlcp_autoplay_speed" id="mlcp_autoplay_speed" value="<?php echo esc_attr($settings['autoplay_speed']); ?>" /></td>
</tr>
<tr class="form-field">
    <th scope="row">Sobreamento</th>
    <td><label><input type="checkbox" name="mlcp_overlay_enabled" value="1" <?php checked($settings['overlay_enabled'], 1); ?> /> Ativar sobreamento</label></td>
</tr>
<tr class="form-field">
    <th scope="row"><label for="mlcp_overlay_opacity">Percentual do sobreamento (%)</label></th>
    <td><input type="number" min="0" max="100" step="1" name="mlcp_overlay_opacity" id="mlcp_overlay_opacity" value="<?php echo esc_attr($settings['overlay_opacity']); ?>" /><p class="description">0 = sem sobreamento. 100 = sobreamento máximo.</p></td>
</tr>
<tr class="form-field">
    <th scope="row">Autoplay</th>
    <td><label><input type="checkbox" name="mlcp_autoplay" value="1" <?php checked($settings['autoplay'], 1); ?> /> Ativar</label></td>
</tr>
<tr class="form-field">
    <th scope="row">Setas</th>
    <td><label><input type="checkbox" name="mlcp_arrows" value="1" <?php checked($settings['arrows'], 1); ?> /> Mostrar</label></td>
</tr>
<tr class="form-field">
    <th scope="row">Título</th>
    <td><label><input type="checkbox" name="mlcp_show_title" value="1" <?php checked($settings['show_title'], 1); ?> /> Mostrar</label></td>
</tr>
<tr class="form-field">
    <th scope="row">Subtítulo</th>
    <td><label><input type="checkbox" name="mlcp_show_subtitle" value="1" <?php checked($settings['show_subtitle'], 1); ?> /> Mostrar</label></td>
</tr>
