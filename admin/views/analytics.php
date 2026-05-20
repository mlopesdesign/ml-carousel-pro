<div class="wrap mlcp-wrap">
    <?php echo MLCP_Helpers::render_admin_header('mlcp-analytics', 'ML Banner Pro', 'Desempenho dos banners: visualizações, cliques e CTR.'); ?>

    <?php if (!empty($_GET['analytics_reset'])) : ?>
        <div class="mlcp-copy-toast is-visible is-success" role="status">Contadores zerados com sucesso.</div>
    <?php endif; ?>

    <div class="mlcp-analytics-summary">
        <div class="mlcp-kpi-card">
            <span class="mlcp-kpi-label">Views totais</span>
            <strong class="mlcp-kpi-value"><?php echo esc_html(number_format_i18n((int) $summary['views'])); ?></strong>
        </div>
        <div class="mlcp-kpi-card">
            <span class="mlcp-kpi-label">Cliques totais</span>
            <strong class="mlcp-kpi-value"><?php echo esc_html(number_format_i18n((int) $summary['clicks'])); ?></strong>
        </div>
        <div class="mlcp-kpi-card">
            <span class="mlcp-kpi-label">CTR geral</span>
            <strong class="mlcp-kpi-value"><?php echo esc_html($summary['ctr']); ?></strong>
        </div>
        <div class="mlcp-kpi-card">
            <span class="mlcp-kpi-label">Banners cadastrados</span>
            <strong class="mlcp-kpi-value"><?php echo esc_html(number_format_i18n((int) $summary['items'])); ?></strong>
        </div>
    </div>

    <div class="mlcp-card-panel mlcp-analytics-panel">
        <div class="mlcp-card-head">
            <div>
                <h2>Desempenho dos banners</h2>
                <p class="mlcp-license-copy">Acompanhe visualizações, cliques e taxa de clique por item.</p>
            </div>
            <a class="button mlcp-danger-button"
               href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=mlcp_reset_analytics'), 'mlcp_reset_analytics_all')); ?>"
               onclick="return confirm('Zerar todos os contadores de views e cliques?');">Zerar todos os contadores</a>
        </div>

        <?php
        /* ── status filter ── */
        $allowed_statuses = array('publish', 'draft', 'private', 'pending', 'future');
        $current_status   = isset($_GET['mlcp_status']) ? sanitize_key($_GET['mlcp_status']) : 'publish';
        if (!in_array($current_status, $allowed_statuses, true)) {
            $current_status = 'publish';
        }

        $status_labels = array(
            'publish' => 'Publicados',
            'draft'   => 'Rascunhos',
            'private' => 'Privados',
            'pending' => 'Pendentes',
            'future'  => 'Agendados',
            'all'     => 'Todos',
        );

        $base_url = admin_url('admin.php?page=mlcp-analytics');
        ?>

        <!-- Status filter tabs -->
        <div class="mlcp-analytics-filter" style="margin-bottom:16px;display:flex;gap:6px;flex-wrap:wrap;">
            <?php foreach ($status_labels as $slug => $label) :
                $is_active = ($current_status === $slug);
                $url = $slug === 'all' ? $base_url : add_query_arg('mlcp_status', $slug, $base_url);
            ?>
                <a href="<?php echo esc_url($url); ?>"
                   class="button<?php echo $is_active ? ' button-primary' : ''; ?>">
                    <?php echo esc_html($label); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php
        /* ── filter items by selected status ── */
        $query_statuses = ($current_status === 'all')
            ? array('publish', 'draft', 'private', 'pending', 'future')
            : array($current_status);

        $filtered_items = array_filter($items, function($item) use ($query_statuses) {
            return in_array($item->post_status, $query_statuses, true);
        });
        ?>

        <?php if (!empty($filtered_items)) : ?>
            <div class="mlcp-analytics-list">
                <?php foreach ($filtered_items as $item) :
                    $analytics  = MLCP_Helpers::get_item_analytics($item->ID);
                    $views      = (int) $analytics['views'];
                    $clicks     = (int) $analytics['clicks'];
                    $ctr        = MLCP_Helpers::get_item_ctr($views, $clicks);
                    $img        = MLCP_Helpers::get_image_url($item->ID, 'thumbnail');
                    $terms      = get_the_terms($item->ID, MLCP_GROUP_TAX);
                    $group_names = ($terms && !is_wp_error($terms)) ? implode(', ', wp_list_pluck($terms, 'name')) : '—';
                    $last       = !empty($analytics['last_activity']) ? wp_date('d/m/Y H:i', (int) $analytics['last_activity'], wp_timezone()) : '—';
                    $reset_url  = wp_nonce_url(admin_url('admin-post.php?action=mlcp_reset_analytics&item_id=' . (int) $item->ID), 'mlcp_reset_analytics_' . (int) $item->ID);
                    $status_label = $status_labels[$item->post_status] ?? $item->post_status;
                ?>
                    <article class="mlcp-analytics-item">
                        <div class="mlcp-analytics-thumb">
                            <?php if ($img) : ?>
                                <img src="<?php echo esc_url($img); ?>" alt="" />
                            <?php else : ?>
                                <span>Sem imagem</span>
                            <?php endif; ?>
                        </div>
                        <div class="mlcp-analytics-main">
                            <h3><a href="<?php echo esc_url(get_edit_post_link($item->ID)); ?>"><?php echo esc_html(get_the_title($item->ID)); ?></a></h3>
                            <div class="mlcp-analytics-meta">
                                <span>ID #<?php echo esc_html((string) $item->ID); ?></span>
                                <span>Grupo: <?php echo esc_html($group_names); ?></span>
                                <?php if ($current_status === 'all') : ?>
                                    <span class="mlcp-status-badge mlcp-status-<?php echo esc_attr($item->post_status); ?>"><?php echo esc_html($status_label); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mlcp-analytics-number"><span>Views</span><strong><?php echo esc_html(number_format_i18n($views)); ?></strong></div>
                        <div class="mlcp-analytics-number"><span>Cliques</span><strong><?php echo esc_html(number_format_i18n($clicks)); ?></strong></div>
                        <div class="mlcp-analytics-number"><span>CTR</span><strong><?php echo esc_html($ctr); ?></strong></div>
                        <div class="mlcp-analytics-number mlcp-analytics-last"><span>Última atividade</span><strong><?php echo esc_html($last); ?></strong></div>
                        <div class="mlcp-analytics-actions">
                            <a class="button" href="<?php echo esc_url($reset_url); ?>" onclick="return confirm('Zerar os contadores deste banner?');">Reset</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="mlcp-empty-state">Nenhum banner encontrado com este status.</div>
        <?php endif; ?>
    </div>
</div>
