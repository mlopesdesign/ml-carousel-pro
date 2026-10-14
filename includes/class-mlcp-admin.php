<?php
if (!defined('ABSPATH')) {
    exit;
}

if ( class_exists( 'MLCP_Admin' ) ) { return; }

class MLCP_Admin {
    public static function register_menu() {
        add_menu_page(
            __('ML Banner Pro', MLCP_TEXT_DOMAIN),
            __('ML Banner Pro', MLCP_TEXT_DOMAIN),
            'manage_options',
            MLCP_MENU_SLUG,
            array(__CLASS__, 'render_dashboard'),
            'dashicons-images-alt2',
            58
        );

        add_submenu_page(MLCP_MENU_SLUG, __('Dashboard', MLCP_TEXT_DOMAIN), __('Dashboard', MLCP_TEXT_DOMAIN), 'manage_options', MLCP_MENU_SLUG, array(__CLASS__, 'render_dashboard'));
        add_submenu_page(MLCP_MENU_SLUG, __('Itens', MLCP_TEXT_DOMAIN), __('Itens', MLCP_TEXT_DOMAIN), 'edit_posts', 'edit.php?post_type=' . MLCP_POST_TYPE . '&post_status=publish');
        add_submenu_page(MLCP_MENU_SLUG, __('Grupos', MLCP_TEXT_DOMAIN), __('Grupos', MLCP_TEXT_DOMAIN), 'manage_categories', 'edit-tags.php?taxonomy=' . MLCP_GROUP_TAX . '&post_type=' . MLCP_POST_TYPE);
        add_submenu_page(MLCP_MENU_SLUG, __('Shortcodes', MLCP_TEXT_DOMAIN), __('Shortcodes', MLCP_TEXT_DOMAIN), 'manage_options', 'mlcp-shortcodes', array(__CLASS__, 'render_shortcodes'));
        add_submenu_page(MLCP_MENU_SLUG, __('Configurações', MLCP_TEXT_DOMAIN), __('Configurações', MLCP_TEXT_DOMAIN), 'manage_options', 'mlcp-settings', array(__CLASS__, 'render_settings'));
        add_submenu_page(MLCP_MENU_SLUG, __('Licença e Planos', MLCP_TEXT_DOMAIN), __('Licença e Planos', MLCP_TEXT_DOMAIN), 'manage_options', 'mlcp-license', array(__CLASS__, 'render_license'));
        add_submenu_page(MLCP_MENU_SLUG, __('Analytics', MLCP_TEXT_DOMAIN), __('Analytics', MLCP_TEXT_DOMAIN), 'manage_options', 'mlcp-analytics', array(__CLASS__, 'render_analytics'));
    }

    public static function render_shared_admin_header() {
        $context = MLCP_Helpers::get_current_admin_context();
        if (empty($context['is_plugin'])) {
            return;
        }

        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if (in_array($page, array(MLCP_MENU_SLUG, 'mlcp-sort', 'mlcp-shortcodes', 'mlcp-settings', 'mlcp-license', 'mlcp-analytics'), true)) {
            return;
        }

        echo '<div class="mlcp-wrap mlcp-wrap-shared">';
        echo MLCP_Helpers::render_admin_header($context['active'], $context['title'], $context['subtitle']);
        echo '</div>';
    }

    public static function render_dashboard() {
        $groups = get_terms(array('taxonomy' => MLCP_GROUP_TAX, 'hide_empty' => false));
        $items  = wp_count_posts(MLCP_POST_TYPE);
        include MLCP_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    public static function render_shortcodes() {
        $groups = get_terms(array('taxonomy' => MLCP_GROUP_TAX, 'hide_empty' => false));
        include MLCP_PLUGIN_DIR . 'admin/views/shortcodes.php';
    }

    public static function render_settings() {
        $settings = MLCP_Helpers::get_settings();
        include MLCP_PLUGIN_DIR . 'admin/views/settings.php';
    }

    public static function render_analytics() {
        $summary = MLCP_Analytics_Service::get_summary();
        $items   = get_posts(array(
            'post_type'      => MLCP_POST_TYPE,
            'posts_per_page' => -1,
            'post_status'    => array('publish', 'future', 'draft', 'pending', 'private'),
            'orderby'        => array('menu_order' => 'ASC', 'date' => 'DESC'),
        ));
        include MLCP_PLUGIN_DIR . 'admin/views/analytics.php';
    }

    public static function render_license() {
        $manager              = new MLCP_License_Manager();
        $summary              = $manager->get_license_summary();
        $license_state        = $summary['state'];
        $license_product_name = $summary['product_name'];
        $license_product_id   = $summary['product_id'];
        $license_server_url   = $summary['server_url'];
        $license_domain       = $summary['domain'];
        $site_fingerprint     = $summary['site_fingerprint'];
        $plan_label           = $summary['plan_label'];
        $status_label         = $summary['status_label'];
        include MLCP_PLUGIN_DIR . 'admin/views/license.php';
    }

    public static function handle_activate_license() {
        (new MLCP_License_Manager())->handle_activate_request();
    }

    public static function handle_sync_license() {
        (new MLCP_License_Manager())->handle_sync_request();
    }

    public static function handle_remove_license() {
        (new MLCP_License_Manager())->handle_remove_request();
    }

    public static function render_sort() {
        $groups = get_terms(array('taxonomy' => MLCP_GROUP_TAX, 'hide_empty' => false));
        include MLCP_PLUGIN_DIR . 'admin/views/sort.php';
    }

    public static function save_settings() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Acesso negado.', MLCP_TEXT_DOMAIN));
        }

        check_admin_referer('mlcp_save_settings');

        $settings = array(
            'default_width_value'     => max(1, (int) ($_POST['default_width_value'] ?? 1140)),
            'default_width_unit'      => MLCP_Helpers::normalize_size_unit(wp_unslash($_POST['default_width_unit'] ?? 'px')),
            'default_lock_proportion' => !empty($_POST['default_lock_proportion']) ? 1 : 0,
            'default_rounded_corners' => !empty($_POST['default_rounded_corners']) ? 1 : 0,
            'default_desktop'         => max(1, (int) ($_POST['default_desktop'] ?? 3)),
            'default_tablet'          => max(1, (int) ($_POST['default_tablet'] ?? 2)),
            'default_mobile'          => max(1, (int) ($_POST['default_mobile'] ?? 1)),
            'default_gap'             => max(0, (int) ($_POST['default_gap'] ?? 18)),
            'default_autoplay'        => !empty($_POST['default_autoplay']) ? 1 : 0,
            'default_autoplay_speed'  => max(1000, (int) ($_POST['default_autoplay_speed'] ?? 4500)),
            'default_arrows'          => !empty($_POST['default_arrows']) ? 1 : 0,
            'default_overlay_enabled' => !empty($_POST['default_overlay_enabled']) ? 1 : 0,
            'default_overlay_opacity' => max(0, min(100, (int) ($_POST['default_overlay_opacity'] ?? 42))),
            'default_show_title'      => !empty($_POST['default_show_title']) ? 1 : 0,
            'default_show_subtitle'   => !empty($_POST['default_show_subtitle']) ? 1 : 0,
        );

        MLCP_Helpers::update_settings($settings);

        wp_safe_redirect(add_query_arg(array('page' => 'mlcp-settings', 'updated' => 1), admin_url('admin.php')));
        exit;
    }

    public static function item_columns($columns) {
        $new = array();
        foreach ($columns as $key => $label) {
            if ($key === 'date') {
                continue;
            }
            $new[$key] = $label;
            if ($key === 'title') {
                $new['mlcp_image']    = __('Imagem', MLCP_TEXT_DOMAIN);
                $new['mlcp_subtitle'] = __('Data/Subtítulo', MLCP_TEXT_DOMAIN);
                $new['mlcp_group']    = __('Grupo', MLCP_TEXT_DOMAIN);
                $new['mlcp_order']    = __('Ordem', MLCP_TEXT_DOMAIN);
                $new['mlcp_active']   = __('Ativo', MLCP_TEXT_DOMAIN);
                $new['mlcp_expire']   = __('Expira em', MLCP_TEXT_DOMAIN);
            }
        }
        return $new;
    }

    public static function render_item_columns($column, $post_id) {
        $meta = MLCP_Helpers::get_item_meta($post_id);

        if ($column === 'mlcp_image') {
            $img = MLCP_Helpers::get_image_url($post_id, 'thumbnail');
            echo $img ? '<img src="' . esc_url($img) . '" style="width:72px;height:48px;object-fit:cover;border-radius:8px;" alt="" />' : '&mdash;';
        } elseif ($column === 'mlcp_subtitle') {
            echo $meta['subtitle_display'] ? esc_html($meta['subtitle_display']) : '&mdash;';
        } elseif ($column === 'mlcp_group') {
            $terms = get_the_terms($post_id, MLCP_GROUP_TAX);
            if ($terms && !is_wp_error($terms)) {
                echo esc_html(implode(', ', wp_list_pluck($terms, 'name')));
            } else {
                echo '&mdash;';
            }
        } elseif ($column === 'mlcp_order') {
            $post = get_post($post_id);
            if (!$post || $post->post_status !== 'publish') {
                echo '&mdash;';
                return;
            }

            echo '<div class="mlcp-order-control">';
            echo '<span class="mlcp-order-position">' . esc_html((string) ((int) $post->menu_order + 1)) . '</span>';
            echo '</div>';
        } elseif ($column === 'mlcp_active') {
            echo !empty($meta['active']) ? esc_html__('Sim', MLCP_TEXT_DOMAIN) : esc_html__('Não', MLCP_TEXT_DOMAIN);
        } elseif ($column === 'mlcp_expire') {
            if (empty($meta['expire_at'])) {
                echo '-';
            } elseif (MLCP_Helpers::is_item_expired($post_id)) {
                echo '<span style="color:#b32d2e;font-weight:600;">' . esc_html__('Expirado', MLCP_TEXT_DOMAIN) . '</span>';
            } else {
                echo esc_html(wp_date('d/m/Y H:i', (int) $meta['expire_at'], wp_timezone()));
            }
        }
    }

    public static function duplicate_row_action($actions, $post) {
        if (!$post || $post->post_type !== MLCP_POST_TYPE) {
            return $actions;
        }

        if (!current_user_can('edit_post', $post->ID)) {
            return $actions;
        }

        $url = wp_nonce_url(
            admin_url('admin-post.php?action=mlcp_duplicate_item&post=' . (int) $post->ID),
            'mlcp_duplicate_item_' . (int) $post->ID
        );

        $actions['mlcp_duplicate'] = '<a href="' . esc_url($url) . '">' . esc_html__('Duplicar', MLCP_TEXT_DOMAIN) . '</a>';
        return $actions;
    }

    public static function handle_duplicate_item() {
        $post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
        if (!$post_id) {
            wp_die(esc_html__('Item inválido.', MLCP_TEXT_DOMAIN));
        }

        if (!current_user_can('edit_post', $post_id)) {
            wp_die(esc_html__('Acesso negado.', MLCP_TEXT_DOMAIN));
        }

        check_admin_referer('mlcp_duplicate_item_' . $post_id);

        $post = get_post($post_id, ARRAY_A);
        if (!$post || $post['post_type'] !== MLCP_POST_TYPE) {
            wp_die(esc_html__('Item inválido.', MLCP_TEXT_DOMAIN));
        }

        $new_post               = $post;
        unset($new_post['ID'], $new_post['guid'], $new_post['post_date'], $new_post['post_date_gmt'], $new_post['post_modified'], $new_post['post_modified_gmt']);
        $new_post['post_status'] = 'draft';
        $new_post['post_title']  = $post['post_title'] ? $post['post_title'] . ' - ' . __('Cópia', MLCP_TEXT_DOMAIN) : __('Cópia do item', MLCP_TEXT_DOMAIN);
        $new_post['post_name']   = '';

        $new_id = wp_insert_post(wp_slash($new_post), true);
        if (is_wp_error($new_id)) {
            wp_die(esc_html($new_id->get_error_message()));
        }

        $all_meta = get_post_meta($post_id);
        foreach ($all_meta as $meta_key => $meta_values) {
            if (in_array($meta_key, array('_edit_lock', '_edit_last', '_wp_old_slug'), true)) {
                continue;
            }

            delete_post_meta($new_id, $meta_key);
            if (!is_array($meta_values)) {
                update_post_meta($new_id, $meta_key, $meta_values);
                continue;
            }

            foreach ($meta_values as $meta_value) {
                add_post_meta($new_id, $meta_key, $meta_value);
            }
        }

        $terms = wp_get_object_terms($post_id, MLCP_GROUP_TAX, array('fields' => 'ids'));
        if (!is_wp_error($terms) && !empty($terms)) {
            wp_set_object_terms($new_id, $terms, MLCP_GROUP_TAX, false);
        }

        if (!empty($post['menu_order'])) {
            wp_update_post(array(
                'ID'         => $new_id,
                'menu_order' => (int) $post['menu_order'],
            ));
        }

        $expire_at = (int) get_post_meta($new_id, '_mlcp_expire_at', true);
        MLCP_Meta_Boxes::sync_expiration($new_id, $expire_at);

        wp_safe_redirect(get_edit_post_link($new_id, 'url'));
        exit;
    }

    public static function handle_move_item() {
        $post_id   = isset($_GET['post']) ? (int) $_GET['post'] : 0;
        $direction = isset($_GET['direction']) ? sanitize_key(wp_unslash($_GET['direction'])) : '';

        if (!$post_id || !in_array($direction, array('up', 'down'), true)) {
            wp_die(esc_html__('Movimentação inválida.', MLCP_TEXT_DOMAIN));
        }

        if (!current_user_can('edit_post', $post_id)) {
            wp_die(esc_html__('Acesso negado.', MLCP_TEXT_DOMAIN));
        }

        check_admin_referer('mlcp_move_item_' . $post_id);

        $post = get_post($post_id);
        if (!$post || $post->post_type !== MLCP_POST_TYPE || $post->post_status !== 'publish') {
            wp_die(esc_html__('Item inválido.', MLCP_TEXT_DOMAIN));
        }

        $ids = get_posts(array(
            'post_type'      => MLCP_POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'orderby'        => array('menu_order' => 'ASC', 'date' => 'DESC'),
        ));
        $ids = array_map('intval', $ids);

        $index  = array_search($post_id, $ids, true);
        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if ($index !== false && isset($ids[$target])) {
            $other        = $ids[$target];
            $ids[$target] = $post_id;
            $ids[$index]  = $other;

            foreach ($ids as $menu_order => $id) {
                wp_update_post(array(
                    'ID'         => (int) $id,
                    'menu_order' => (int) $menu_order,
                ));
            }
        }

        $redirect = wp_get_referer();
        if (!$redirect || strpos($redirect, admin_url()) !== 0) {
            $redirect = admin_url('edit.php?post_type=' . MLCP_POST_TYPE . '&post_status=publish');
        }

        wp_safe_redirect($redirect);
        exit;
    }

    public static function always_show_trash_view($views) {
        unset($views['all']);
        $counts = wp_count_posts(MLCP_POST_TYPE);
        $count  = isset($counts->trash) ? (int) $counts->trash : 0;
        $url    = add_query_arg(array(
            'post_type'   => MLCP_POST_TYPE,
            'post_status' => 'trash',
        ), admin_url('edit.php'));
        $class  = isset($_GET['post_status']) && sanitize_key(wp_unslash($_GET['post_status'])) === 'trash'
            ? ' class="current" aria-current="page"'
            : '';

        $views['trash'] = sprintf(
            '<a href="%1$s"%2$s>Lixeira <span class="count">(%3$s)</span></a>',
            esc_url($url),
            $class,
            number_format_i18n($count)
        );

        return $views;
    }

    public static function handle_admin_query($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        if ($query->get('post_type') !== MLCP_POST_TYPE) {
            return;
        }

        $orderby = $query->get('orderby');
        if (!$orderby) {
            $query->set('orderby', array('menu_order' => 'ASC', 'date' => 'DESC'));
        }
    }

    public static function group_columns($columns) {
        $new = array();
        foreach ($columns as $key => $label) {
            $new[$key] = $label;
            if ($key === 'name') {
                $new['mlcp_shortcode'] = __('Shortcode', MLCP_TEXT_DOMAIN);
                $new['mlcp_layout']    = __('Layout', MLCP_TEXT_DOMAIN);
            }
        }
        return $new;
    }

    public static function render_group_columns($content, $column, $term_id) {
        $term     = get_term($term_id, MLCP_GROUP_TAX);
        $settings = MLCP_Helpers::get_group_settings($term_id);

        if ($column === 'mlcp_shortcode') {
            $shortcode = MLCP_Helpers::get_group_shortcode($term->slug);
            return '<div class="mlcp-inline-shortcode"><code>' . esc_html($shortcode) . '</code><button type="button" class="button button-small mlcp-copy-shortcode" data-shortcode="' . esc_attr($shortcode) . '">Copiar</button></div>';
        }

        if ($column === 'mlcp_layout') {
            return esc_html(sprintf('D:%d T:%d M:%d · %dpx × %dpx%s', $settings['desktop'], $settings['tablet'], $settings['mobile'], $settings['width_px'], $settings['height_px'], !empty($settings['lock_proportion']) ? ' · proporção travada' : ''));
        }

        return $content;
    }

    public static function handle_reset_analytics() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Acesso negado.', MLCP_TEXT_DOMAIN));
        }

        $item_id = isset($_GET['item_id']) ? (int) $_GET['item_id'] : 0;
        check_admin_referer($item_id > 0 ? 'mlcp_reset_analytics_' . $item_id : 'mlcp_reset_analytics_all');

        MLCP_Analytics_Service::reset_all( (int) $item_id );

        wp_safe_redirect(add_query_arg(array(
            'page'            => 'mlcp-analytics',
            'analytics_reset' => $item_id > 0 ? 'item' : 'all',
        ), admin_url('admin.php')));
        exit;
    }

    public static function ajax_track_event() {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'mlcp_front_nonce')) {
            wp_send_json_error(array('message' => __('Requisição inválida.', MLCP_TEXT_DOMAIN)), 403);
        }

        $post_id = isset($_POST['item_id']) ? (int) $_POST['item_id'] : 0;
        $event   = isset($_POST['event']) ? sanitize_key(wp_unslash($_POST['event'])) : '';

        if (!MLCP_Helpers::increment_analytics($post_id, $event)) {
            wp_send_json_error(array('message' => __('Evento inválido.', MLCP_TEXT_DOMAIN)), 400);
        }

        wp_send_json_success(array('ok' => true));
    }

    public static function ajax_load_sort_group() {
        check_ajax_referer('mlcp_admin_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Acesso negado.', MLCP_TEXT_DOMAIN)), 403);
        }

        $group_id = isset($_POST['group_id']) ? (int) $_POST['group_id'] : 0;
        if (!$group_id) {
            wp_send_json_error(array('message' => __('Grupo inválido.', MLCP_TEXT_DOMAIN)), 400);
        }

        $posts = get_posts(array(
            'post_type'      => MLCP_POST_TYPE,
            'posts_per_page' => -1,
            'orderby'        => array('menu_order' => 'ASC', 'date' => 'DESC'),
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => '_mlcp_active',
                    'value'   => '1',
                    'compare' => '=',
                ),
                array(
                    'relation' => 'OR',
                    array(
                        'key'     => '_mlcp_expire_at',
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'key'     => '_mlcp_expire_at',
                        'value'   => current_time('timestamp', true),
                        'compare' => '>',
                        'type'    => 'NUMERIC',
                    ),
                ),
            ),
            'tax_query' => array(
                array(
                    'taxonomy' => MLCP_GROUP_TAX,
                    'field'    => 'term_id',
                    'terms'    => $group_id,
                ),
            ),
        ));

        ob_start();
        if ($posts) {
            echo '<ul class="mlcp-sort-list">';
            foreach ($posts as $post) {
                $img = MLCP_Helpers::get_image_url($post->ID, 'thumbnail');
                echo '<li class="mlcp-sort-item" data-post-id="' . esc_attr($post->ID) . '">';
                echo '<span class="mlcp-sort-handle">⋮⋮</span>';
                echo $img ? '<img src="' . esc_url($img) . '" alt="" />' : '<span class="mlcp-sort-placeholder">Sem imagem</span>';
                echo '<div class="mlcp-sort-content"><strong>' . esc_html(get_the_title($post->ID)) . '</strong><span>' . esc_html((string) MLCP_Helpers::get_item_meta($post->ID)['subtitle_display']) . '</span></div>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<div class="mlcp-empty-state">Nenhum item neste grupo.</div>';
        }

        wp_send_json_success(array('html' => ob_get_clean()));
    }

    public static function ajax_save_sort_group() {
        check_ajax_referer('mlcp_admin_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Acesso negado.', MLCP_TEXT_DOMAIN)), 403);
        }

        $order = isset($_POST['order']) ? (array) $_POST['order'] : array();
        foreach ($order as $index => $post_id) {
            wp_update_post(array(
                'ID'         => (int) $post_id,
                'menu_order' => (int) $index,
            ));
        }

        wp_send_json_success(array('message' => __('Ordenação salva.', MLCP_TEXT_DOMAIN)));
    }

    public static function ajax_save_items_order() {
        check_ajax_referer('mlcp_admin_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Acesso negado.', MLCP_TEXT_DOMAIN)), 403);
        }

        $submitted = isset($_POST['order']) ? array_values(array_unique(array_map('absint', (array) $_POST['order']))) : array();
        if (count($submitted) < 2) {
            wp_send_json_error(array('message' => __('Ordenação inválida.', MLCP_TEXT_DOMAIN)), 400);
        }

        foreach ($submitted as $post_id) {
            $post = get_post($post_id);
            if (!$post || $post->post_type !== MLCP_POST_TYPE || $post->post_status !== 'publish' || !current_user_can('edit_post', $post_id)) {
                wp_send_json_error(array('message' => __('Item inválido ou sem permissão.', MLCP_TEXT_DOMAIN)), 403);
            }
        }

        // Replace only the visible items in the complete sequence. This keeps
        // pagination and filtered list screens safe without disturbing hidden rows.
        $all_ids = get_posts(array(
            'post_type'      => MLCP_POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'orderby'        => array('menu_order' => 'ASC', 'date' => 'DESC'),
        ));
        $submitted_lookup = array_fill_keys($submitted, true);
        $cursor = 0;

        foreach ($all_ids as $index => $post_id) {
            $post_id = (int) $post_id;
            if (isset($submitted_lookup[$post_id])) {
                $all_ids[$index] = $submitted[$cursor++];
            }
        }

        if ($cursor !== count($submitted)) {
            wp_send_json_error(array('message' => __('A lista mudou. Recarregue a página e tente novamente.', MLCP_TEXT_DOMAIN)), 409);
        }

        foreach ($all_ids as $menu_order => $post_id) {
            $result = wp_update_post(array(
                'ID'         => (int) $post_id,
                'menu_order' => (int) $menu_order,
            ), true);
            if (is_wp_error($result)) {
                wp_send_json_error(array('message' => __('Não foi possível salvar a ordenação.', MLCP_TEXT_DOMAIN)), 500);
            }
        }

        wp_send_json_success(array('message' => __('Ordenação salva com sucesso.', MLCP_TEXT_DOMAIN)));
    }
}
