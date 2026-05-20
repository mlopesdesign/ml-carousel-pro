<?php
if (!defined('ABSPATH')) {
    exit;
}

$notice = isset($_GET['mlcp_notice']) ? sanitize_text_field(wp_unslash($_GET['mlcp_notice'])) : '';
$notice_type = isset($_GET['mlcp_notice_type']) ? sanitize_key(wp_unslash($_GET['mlcp_notice_type'])) : 'success';
$days_left = $license_state['days_left'] !== '' && $license_state['days_left'] !== null ? (int) $license_state['days_left'] : null;
$license_source = !empty($license_state['license_source']) ? $license_state['license_source'] : 'free';
$premium_enabled = !empty($license_state['premium']);
$badge_class = $premium_enabled ? 'is-green' : 'is-blue';
?>
<div class="wrap mlcp-wrap">
    <?php echo MLCP_Helpers::render_admin_header('mlcp-license', 'ML Banner Pro', 'Licenciamento conectado ao ML License Hub com Trial, Free, Full e Vitalício no mesmo fluxo comercial.'); ?>

    <div class="mlcp-badges mlcp-badges-floating">
        <span class="mlcp-badge <?php echo esc_attr($badge_class); ?>"><?php echo esc_html($plan_label); ?></span>
    </div>

    <?php if ($notice) : ?>
        <div class="mlcp-toast-source" data-type="<?php echo esc_attr($notice_type === 'error' ? 'error' : 'success'); ?>" data-message="<?php echo esc_attr($notice); ?>" aria-hidden="true"></div>
    <?php endif; ?>

    <div class="mlcp-kpi-grid">
        <div class="mlcp-kpi-card"><span class="mlcp-kpi-label">Plano atual</span><strong class="mlcp-kpi-value"><?php echo esc_html($plan_label); ?></strong></div>
        <div class="mlcp-kpi-card"><span class="mlcp-kpi-label">Status</span><strong class="mlcp-kpi-value"><?php echo esc_html($status_label); ?></strong></div>
        <div class="mlcp-kpi-card"><span class="mlcp-kpi-label">Premium</span><strong class="mlcp-kpi-value"><?php echo esc_html($premium_enabled ? 'Liberado' : 'Free'); ?></strong></div>
        <div class="mlcp-kpi-card"><span class="mlcp-kpi-label">Dias restantes</span><strong class="mlcp-kpi-value"><?php echo esc_html($days_left !== null ? $days_left : '—'); ?></strong></div>
    </div>

    <div class="mlcp-license-layout">
        <section class="mlcp-card-panel mlcp-license-card">
            <div class="mlcp-card-head">
                <h2>Licença</h2>
                <span class="mlcp-pill is-soft"><?php echo esc_html($status_label); ?></span>
            </div>
            <p class="mlcp-license-copy">Ative uma licença Full ou Vitalícia, ou sincronize o site com o Hub para receber Trial de 30 dias e depois degradar com elegância para Free.</p>

            <div class="mlcp-license-meta">
                <div><span>Produto</span><strong><?php echo esc_html(!empty($license_state['product_name']) ? $license_state['product_name'] : $license_product_name); ?></strong></div>
                <div><span>Product ID</span><strong><?php echo esc_html($license_product_id); ?></strong></div>
                <div><span>Domínio atual</span><strong><?php echo esc_html($license_domain); ?></strong></div>
                <div><span>Site fingerprint</span><strong style="word-break:break-all"><?php echo esc_html($site_fingerprint); ?></strong></div>
                <div><span>Origem</span><strong><?php echo esc_html($license_source); ?></strong></div>
                <div><span>Mensagem</span><strong><?php echo esc_html($license_state['message']); ?></strong></div>
                <div><span>Última validação</span><strong><?php echo esc_html($license_state['last_validated_at'] ? $license_state['last_validated_at'] : '—'); ?></strong></div>
                <div><span>Expira em</span><strong><?php echo esc_html($license_state['expires_at'] ? $license_state['expires_at'] : '—'); ?></strong></div>
            </div>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="mlcp-license-form">
                <?php wp_nonce_field('mlcp_activate_license'); ?>
                <input type="hidden" name="action" value="mlcp_activate_license">
                <div class="mlcp-field-grid mlcp-field-grid-2">
                    <label class="mlcp-field">
                        <span>Servidor de licença</span>
                        <input type="url" name="license_server_url" value="<?php echo esc_attr($license_server_url); ?>" placeholder="https://license.mlopesdesign.com.br/api/license.php">
                    </label>
                    <label class="mlcp-field">
                        <span>Product ID</span>
                        <input type="text" name="license_product_id" value="<?php echo esc_attr($license_product_id); ?>" placeholder="ml-carousel-pro">
                    </label>
                </div>

                <label class="mlcp-field mlcp-field-full">
                    <span>Serial / License Key</span>
                    <input type="text" name="license_key" value="<?php echo esc_attr($license_state['license_key']); ?>" placeholder="MLI-XXXXX-XXXXX-XXXXX-XXXXX">
                </label>

                <div class="mlcp-license-actions">
                    <button type="submit" class="button button-primary">Ativar licença</button>
                </div>
            </form>

            <div class="mlcp-license-inline-actions">
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('mlcp_sync_license'); ?>
                    <input type="hidden" name="action" value="mlcp_sync_license">
                    <input type="hidden" name="license_server_url" value="<?php echo esc_attr($license_server_url); ?>">
                    <input type="hidden" name="license_product_id" value="<?php echo esc_attr($license_product_id); ?>">
                    <input type="hidden" name="license_key" value="<?php echo esc_attr($license_state['license_key']); ?>">
                    <button type="submit" class="button"><?php echo esc_html($license_state['license_key'] !== '' ? 'Validar agora' : 'Iniciar / sincronizar Trial'); ?></button>
                </form>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('mlcp_remove_license'); ?>
                    <input type="hidden" name="action" value="mlcp_remove_license">
                    <button type="submit" class="button">Remover licença deste site</button>
                </form>
            </div>
        </section>

        <section class="mlcp-card-panel mlcp-license-plans">
            <div class="mlcp-card-head">
                <h2>Planos</h2>
                <span class="mlcp-pill is-soft">Free / Trial / Full / Vitalício</span>
            </div>
            <table class="widefat striped mlcp-plan-table">
                <thead><tr><th>Recurso</th><th>Free</th><th>Trial</th><th>Full / Vitalício</th></tr></thead>
                <tbody>
                    <tr><td>Itens e grupos do carrossel</td><td>Sim</td><td>Sim</td><td>Sim</td></tr>
                    <tr><td>Shortcodes e ordenação</td><td>Sim</td><td>Sim</td><td>Sim</td></tr>
                    <tr><td>Janela Full de avaliação</td><td>Não</td><td>30 dias</td><td>—</td></tr>
                    <tr><td>Premium liberado</td><td>Não</td><td>Sim</td><td>Sim</td></tr>
                    <tr><td>Degradação elegante para Free</td><td>—</td><td>Sim</td><td>—</td></tr>
                </tbody>
            </table>
            <p class="mlcp-license-note">O Trial é controlado no servidor de licença. Reinstalar o plugin não concede um novo período no mesmo site.</p>
        </section>
    </div>
</div>
