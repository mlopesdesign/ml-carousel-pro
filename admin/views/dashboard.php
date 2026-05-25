<div class="wrap mlcp-wrap">
    <?php echo MLCP_Helpers::render_admin_header(MLCP_MENU_SLUG, 'ML Banner Pro', 'Carrossel profissional com grupos, ordenação manual e shortcodes seguros para templates como Nicepage.'); ?>

    <div class="mlcp-cards">
        <div class="mlcp-card-panel">
            <h2>Resumo operacional</h2>
            <div class="mlcp-stats">
                <div><strong><?php echo isset($items->publish) ? (int) $items->publish : 0; ?></strong><span>Itens publicados</span></div>
                <div><strong><?php echo is_array($groups) ? count($groups) : 0; ?></strong><span>Grupos criados</span></div>
            </div>
        </div>
    </div>
</div>
