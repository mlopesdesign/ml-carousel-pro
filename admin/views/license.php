<?php
if (!defined('ABSPATH')) {
    exit;
}

$notice = isset($_GET['mlcp_notice']) ? sanitize_text_field(wp_unslash($_GET['mlcp_notice'])) : '';
$notice_type = isset($_GET['mlcp_notice_type']) ? sanitize_key(wp_unslash($_GET['mlcp_notice_type'])) : 'success';
$tone_class = array('full' => 'is-green', 'trial' => 'is-blue', 'free' => 'is-amber');
$badge_class = $tone_class[$license['tone']] ?? 'is-blue';
$days_left = $license['days_left'];
$post_url = esc_url(admin_url('admin-post.php'));
?>
<div class="wrap mlcp-wrap">
    <?php echo MLCP_Helpers::render_admin_header('mlcp-license', 'ML Banner Pro', 'Licença pelo site tools.mlopesdesign.com.br: teste grátis de 30 dias por site, licença anual ou Pacote Agência.'); ?>

    <div class="mlcp-badges mlcp-badges-floating">
        <span class="mlcp-badge <?php echo esc_attr($badge_class); ?>"><?php echo esc_html($license['label']); ?></span>
    </div>

    <?php if ($notice) : ?>
        <div class="mlcp-toast-source" data-type="<?php echo esc_attr($notice_type === 'error' ? 'error' : 'success'); ?>" data-message="<?php echo esc_attr($notice); ?>" aria-hidden="true"></div>
    <?php endif; ?>

    <div class="mlcp-kpi-grid">
        <div class="mlcp-kpi-card"><span class="mlcp-kpi-label">Situação</span><strong class="mlcp-kpi-value"><?php echo esc_html($license['label']); ?></strong></div>
        <div class="mlcp-kpi-card"><span class="mlcp-kpi-label">Plano</span><strong class="mlcp-kpi-value"><?php echo esc_html($license['plan_label']); ?></strong></div>
        <div class="mlcp-kpi-card"><span class="mlcp-kpi-label">Válida até</span><strong class="mlcp-kpi-value"><?php echo esc_html($license['active'] && 'lifetime' !== strtolower($license['plan_label']) && $license['expires_label'] ? $license['expires_label'] : ($license['active'] ? 'Sem vencimento' : '—')); ?></strong></div>
        <div class="mlcp-kpi-card"><span class="mlcp-kpi-label">Dias restantes</span><strong class="mlcp-kpi-value"><?php echo esc_html($license['active'] && null !== $days_left && $license['expires_label'] ? (string) (int) $days_left : '—'); ?></strong></div>
    </div>

    <div class="mlcp-license-layout">
        <section class="mlcp-card-panel mlcp-license-card">
            <div class="mlcp-card-head">
                <h2>Licença</h2>
                <span class="mlcp-pill is-soft"><?php echo esc_html($license['domain']); ?></span>
            </div>
            <p class="mlcp-license-copy"><?php echo esc_html($license['message']); ?></p>

            <?php if ('license' === $license['mode'] && '' !== $license['key_masked']) : ?>
                <div class="mlcp-license-meta">
                    <div><span>Chave neste site</span><strong><?php echo esc_html($license['key_masked']); ?></strong></div>
                </div>
                <div class="mlcp-license-inline-actions">
                    <form method="post" action="<?php echo $post_url; ?>">
                        <?php wp_nonce_field('mlcp_sync_license'); ?>
                        <input type="hidden" name="action" value="mlcp_sync_license">
                        <input type="hidden" name="mlcp_op" value="check">
                        <button type="submit" class="button button-primary">Conferir agora</button>
                    </form>
                    <form method="post" action="<?php echo $post_url; ?>">
                        <?php wp_nonce_field('mlcp_remove_license'); ?>
                        <input type="hidden" name="action" value="mlcp_remove_license">
                        <button type="submit" class="button">Remover deste site</button>
                    </form>
                </div>
            <?php else : ?>
                <form method="post" action="<?php echo $post_url; ?>" class="mlcp-license-form">
                    <?php wp_nonce_field('mlcp_activate_license'); ?>
                    <input type="hidden" name="action" value="mlcp_activate_license">
                    <label class="mlcp-field mlcp-field-full">
                        <span>Chave da licença</span>
                        <input type="text" name="license_key" value="" placeholder="Cole aqui a sua chave" autocomplete="off" spellcheck="false">
                    </label>
                    <div class="mlcp-license-actions">
                        <button type="submit" class="button button-primary">Ativar licença</button>
                    </div>
                </form>
                <div class="mlcp-license-inline-actions">
                    <?php if ($license['can_start_trial']) : ?>
                        <form method="post" action="<?php echo $post_url; ?>">
                            <?php wp_nonce_field('mlcp_sync_license'); ?>
                            <input type="hidden" name="action" value="mlcp_sync_license">
                            <input type="hidden" name="mlcp_op" value="trial">
                            <button type="submit" class="button">Começar teste grátis de 30 dias</button>
                        </form>
                    <?php elseif ($license['is_trial']) : ?>
                        <form method="post" action="<?php echo $post_url; ?>">
                            <?php wp_nonce_field('mlcp_sync_license'); ?>
                            <input type="hidden" name="action" value="mlcp_sync_license">
                            <input type="hidden" name="mlcp_op" value="check">
                            <button type="submit" class="button">Conferir agora</button>
                        </form>
                    <?php endif; ?>
                    <a class="button" href="<?php echo esc_url(MLCP_License_Manager::BUY_URL); ?>" target="_blank" rel="noopener">Comprar licença</a>
                    <a class="button" href="<?php echo esc_url(MLCP_License_Manager::ACCOUNT_URL); ?>" target="_blank" rel="noopener">Minha conta</a>
                </div>
            <?php endif; ?>
        </section>

        <section class="mlcp-card-panel mlcp-license-plans">
            <div class="mlcp-card-head">
                <h2>Como funciona</h2>
                <span class="mlcp-pill is-soft">tools.mlopesdesign.com.br</span>
            </div>
            <table class="widefat striped mlcp-plan-table">
                <tbody>
                    <tr><td><strong>Teste grátis</strong></td><td>30 dias com tudo liberado, uma vez por site. Reinstalar não reinicia.</td></tr>
                    <tr><td><strong>Licença anual</strong></td><td>Vale em 1 site. Para usar em outro, remova deste site.</td></tr>
                    <tr><td><strong>Pacote Agência</strong></td><td>Uma chave para todos os plugins ML em 5, 10 ou 50 sites.</td></tr>
                    <tr><td><strong>Sem licença</strong></td><td>Os banners publicados continuam no ar. Só o painel fica bloqueado para criar, editar e apagar.</td></tr>
                </tbody>
            </table>
            <p class="mlcp-license-note">A licença é conferida uma vez por dia. Sem internet, o site continua liberado por até 7 dias.</p>
        </section>
        <section class="mlcp-card-panel">
            <div class="mlcp-card-head">
                <h2>Atualizações via GitHub</h2>
                <span class="mlcp-pill is-dark">GitHub Updater</span>
            </div>
            <?php
            $diag = MLCP_GitHub_Updater::get_diagnostics();
            ?>
            <table class="widefat striped">
                <tbody>
                    <tr><td><strong>Repositório configurado</strong></td><td><a href="<?php echo esc_url( $diag['repo'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $diag['repo'] ); ?></a></td></tr>
                    <tr><td><strong>Versão instalada</strong></td><td><?php echo esc_html( $diag['current_version'] ); ?></td></tr>
                    <tr><td><strong>Versão no GitHub</strong></td><td><?php echo esc_html( $diag['remote_version'] ); ?></td></tr>
                    <tr><td><strong>Atualização disponível</strong></td><td><?php echo esc_html( $diag['update_available'] ); ?></td></tr>
                    <tr><td><strong>Status da fonte</strong></td><td><?php echo esc_html( $diag['source_status'] ); ?></td></tr>
                    <tr><td><strong>Token</strong></td><td><?php echo esc_html( $diag['token_set'] ); ?></td></tr>
                    <tr><td><strong>ZIP detectado no release</strong></td><td><?php echo esc_html( $diag['zip_detected'] ); ?></td></tr>
                </tbody>
            </table>
            <p class="mlcp-license-note">As atualizações vêm do repositório público de releases; nenhum token é necessário.</p>
        </section>

    </div>
</div>
