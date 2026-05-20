<div class="wrap mlcp-wrap">
    <?php echo MLCP_Helpers::render_admin_header(MLCP_MENU_SLUG, 'ML Banner Pro', 'Carrossel profissional com grupos, ordenação manual e shortcodes seguros para templates como Nicepage.'); ?>

    <div class="mlcp-cards">
        <div class="mlcp-card-panel">
            <h2>Resumo operacional</h2>
            <div class="mlcp-stats">
                <div><strong><?php echo isset($items->publish) ? (int) $items->publish : 0; ?></strong><span>Itens publicados</span></div>
                <div><strong><?php echo is_array($groups) ? count($groups) : 0; ?></strong><span>Grupos criados</span></div>
            </div>
            <div class="mlcp-note-box">Use grupos para separar campanhas, destaques, eventos ou blocos independentes do site.</div>
        </div>

        <div class="mlcp-card-panel">
            <h2>Fluxo recomendado</h2>
            <ol class="mlcp-list">
                <li>Crie um grupo com o layout desejado.</li>
                <li>Cadastre os itens do carrossel.</li>
                <li>Associe cada item ao grupo correto.</li>
                <li>Defina a ordem manual na aba Ordenação.</li>
                <li>Copie o shortcode e aplique na página final.</li>
            </ol>
        </div>

        <div class="mlcp-card-panel">
            <h2>Atalhos rápidos</h2>
            <p><a class="button button-primary mlcp-btn-block" href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=' . MLCP_GROUP_TAX . '&post_type=' . MLCP_POST_TYPE)); ?>">Gerenciar grupos</a></p>
            <p><a class="button mlcp-btn-block" href="<?php echo esc_url(admin_url('post-new.php?post_type=' . MLCP_POST_TYPE)); ?>">Adicionar item</a></p>
            <p><a class="button mlcp-btn-block" href="<?php echo esc_url(MLCP_Helpers::admin_url('mlcp-sort')); ?>">Ordenar itens</a></p>
        </div>
    </div>
</div>
