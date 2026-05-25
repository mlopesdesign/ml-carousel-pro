<div class="wrap mlcp-wrap">
    <?php echo MLCP_Helpers::render_admin_header('mlcp-sort', 'ML Banner Pro', 'Defina a ordem manual de cada grupo sem depender do template.'); ?>
    <h1>Ordenação dos grupos</h1>
    <div class="mlcp-card-panel">
        <h2>Selecione o grupo</h2>
        <div class="mlcp-sort-toolbar">
            <select id="mlcp-sort-group">
                <option value="">Selecione um grupo</option>
                <?php if ($groups && !is_wp_error($groups)) : ?>
                    <?php foreach ($groups as $group) : ?>
                        <option value="<?php echo esc_attr($group->term_id); ?>"><?php echo esc_html($group->name); ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <button type="button" class="button" id="mlcp-load-sort">Carregar</button>
            <button type="button" class="button button-primary" id="mlcp-save-sort">Salvar ordenação</button>
        </div>
        <div id="mlcp-sort-container" class="mlcp-sort-container">
            <div class="mlcp-empty-state">Selecione um grupo para carregar os itens.</div>
        </div>
    </div>
</div>
