<div class="wrap mlcp-wrap">
    <?php echo MLCP_Helpers::render_admin_header('mlcp-settings', 'ML Banner Pro', 'Configurações globais do plugin.'); ?>

    <form class="mlcp-form-grid" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('mlcp_save_settings'); ?>
        <input type="hidden" name="action" value="mlcp_save_settings" />

        <div class="mlcp-card-panel">
            <h2>Bloco do carrossel</h2>
            <label>Largura do bloco padrão
                <div class="mlcp-inline-inputs">
                    <input type="number" min="1" name="default_width_value" value="<?php echo esc_attr($settings['default_width_value']); ?>" />
                    <select name="default_width_unit">
                        <option value="px" <?php selected($settings['default_width_unit'], 'px'); ?>>px</option>
                        <option value="%" <?php selected($settings['default_width_unit'], '%'); ?>>%</option>
                    </select>
                </div>
            </label>
            <p class="description">Essa configuração controla a largura visível do bloco no site. A medida real do banner fica no grupo.</p>

            <label>Itens no desktop
                <input type="number" min="1" name="default_desktop" value="<?php echo esc_attr($settings['default_desktop']); ?>" />
            </label>
            <label>Itens no tablet
                <input type="number" min="1" name="default_tablet" value="<?php echo esc_attr($settings['default_tablet']); ?>" />
            </label>
            <label>Itens no mobile
                <input type="number" min="1" name="default_mobile" value="<?php echo esc_attr($settings['default_mobile']); ?>" />
            </label>
            <label>Gap entre cards
                <input type="number" min="0" name="default_gap" value="<?php echo esc_attr($settings['default_gap']); ?>" />
            </label>
        </div>

        <div class="mlcp-card-panel">
            <h2>Comportamento padrão</h2>
            <label>Autoplay speed (ms)
                <input type="number" min="1000" step="100" name="default_autoplay_speed" value="<?php echo esc_attr($settings['default_autoplay_speed']); ?>" />
            </label>
            <label>Opacidade do overlay
                <input type="text" name="default_overlay_opacity" value="<?php echo esc_attr($settings['default_overlay_opacity']); ?>" />
            </label>
            <label class="mlcp-check-row"><input type="checkbox" name="default_autoplay" value="1" <?php checked($settings['default_autoplay'], 1); ?> /> Ativar autoplay por padrão</label>
            <label class="mlcp-check-row"><input type="checkbox" name="default_arrows" value="1" <?php checked($settings['default_arrows'], 1); ?> /> Mostrar setas por padrão</label>
            <label class="mlcp-check-row"><input type="checkbox" name="default_lock_proportion" value="1" <?php checked($settings['default_lock_proportion'], 1); ?> /> Trancar proporção por padrão</label>
            <label class="mlcp-check-row"><input type="checkbox" name="default_rounded_corners" value="1" <?php checked($settings['default_rounded_corners'], 1); ?> /> Usar cantos arredondados por padrão</label>
            <label class="mlcp-check-row"><input type="checkbox" name="default_show_title" value="1" <?php checked($settings['default_show_title'], 1); ?> /> Mostrar título por padrão</label>
            <label class="mlcp-check-row"><input type="checkbox" name="default_show_subtitle" value="1" <?php checked($settings['default_show_subtitle'], 1); ?> /> Mostrar subtítulo por padrão</label>
        </div>

        <div class="mlcp-form-actions">
            <button type="submit" class="button button-primary button-hero">Salvar configurações</button>
        </div>
    </form>
</div>
