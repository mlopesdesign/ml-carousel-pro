<?php
if (!defined('ABSPATH')) {
    exit;
}

class MLCP_Helpers {
    public static function get_settings() {
        $defaults = array(
            'default_width_value' => 1140,
            'default_width_unit' => 'px',
            'default_lock_proportion' => 0,
            'default_rounded_corners' => 1,
            'default_desktop' => 3,
            'default_tablet' => 2,
            'default_mobile' => 1,
            'default_gap' => 18,
            'default_autoplay' => 1,
            'default_autoplay_speed' => 4500,
            'default_arrows' => 1,
            'default_overlay_opacity' => '0.42',
            'default_show_title' => 1,
            'default_show_subtitle' => 1,
            'default_overlay_enabled' => 1,
            'default_overlay_opacity' => 42,
            'license_server_url' => 'https://license.mlopesdesign.com.br',
            'license_product_id' => 'ml-carousel-pro',
            'license_product_name' => 'ML Banner Pro',
        );

        $saved = get_option('mlcp_settings', array());
        if (!is_array($saved)) {
            $saved = array();
        }

        return wp_parse_args($saved, $defaults);
    }

    public static function update_settings($data) {
        $current = get_option('mlcp_settings', array());
        if (!is_array($current)) {
            $current = array();
        }

        $data = is_array($data) ? $data : array();
        update_option('mlcp_settings', array_merge($current, $data));
    }

    public static function get_group_defaults() {
        $settings = self::get_settings();

        return array(
            'width_px' => 320,
            'height_px' => 320,
            'lock_proportion' => (int) $settings['default_lock_proportion'],
            'rounded_corners' => (int) $settings['default_rounded_corners'],
            'desktop' => max(1, (int) $settings['default_desktop']),
            'tablet' => max(1, (int) $settings['default_tablet']),
            'mobile' => max(1, (int) $settings['default_mobile']),
            'gap' => max(0, (int) $settings['default_gap']),
            'autoplay' => (int) $settings['default_autoplay'],
            'autoplay_speed' => max(1000, (int) $settings['default_autoplay_speed']),
            'arrows' => (int) $settings['default_arrows'],
            'overlay_opacity' => (string) $settings['default_overlay_opacity'],
            'show_title' => (int) $settings['default_show_title'],
            'show_subtitle' => (int) $settings['default_show_subtitle'],
            'overlay_enabled' => (int) ($settings['default_overlay_enabled'] ?? 1),
            'overlay_opacity' => (int) ($settings['default_overlay_opacity'] ?? 42),
        );
    }

    public static function get_group_settings($term_id) {
        $defaults = self::get_group_defaults();

        foreach ($defaults as $key => $value) {
            $meta = get_term_meta($term_id, 'mlcp_' . $key, true);
            if ($meta !== '' && $meta !== null) {
                $defaults[$key] = $meta;
            }
        }

        $defaults['width_px'] = max(1, (int) $defaults['width_px']);
        $defaults['height_px'] = max(1, (int) $defaults['height_px']);
        $defaults['lock_proportion'] = (int) !empty($defaults['lock_proportion']);
        $defaults['rounded_corners'] = (int) !empty($defaults['rounded_corners']);
        $defaults['desktop'] = max(1, (int) $defaults['desktop']);
        $defaults['tablet'] = max(1, (int) $defaults['tablet']);
        $defaults['mobile'] = max(1, (int) $defaults['mobile']);
        $defaults['gap'] = max(0, (int) $defaults['gap']);
        $defaults['autoplay'] = (int) !empty($defaults['autoplay']);
        $defaults['arrows'] = (int) !empty($defaults['arrows']);
        $defaults['autoplay_speed'] = max(1000, (int) $defaults['autoplay_speed']);
        $defaults['show_title'] = (int) !empty($defaults['show_title']);
        $defaults['show_subtitle'] = (int) !empty($defaults['show_subtitle']);
        $defaults['overlay_enabled'] = (int) !empty($defaults['overlay_enabled']);
        $defaults['overlay_opacity'] = max(0, min(100, (int) $defaults['overlay_opacity']));

        return $defaults;
    }

    public static function get_group_shortcode($slug) {
        return '[ml_carousel group="' . sanitize_title($slug) . '"]';
    }

    public static function normalize_size_unit($value, $default = 'px') {
        $value = strtolower(trim((string) $value));
        return in_array($value, array('px', '%'), true) ? $value : $default;
    }

    public static function admin_tabs() {
        return array(
            MLCP_MENU_SLUG => array('label' => __('Dashboard', MLCP_TEXT_DOMAIN), 'url' => self::admin_url(MLCP_MENU_SLUG)),
            'edit.php?post_type=' . MLCP_POST_TYPE => array('label' => __('Itens', MLCP_TEXT_DOMAIN), 'url' => admin_url('edit.php?post_type=' . MLCP_POST_TYPE)),
            'post-new.php?post_type=' . MLCP_POST_TYPE => array('label' => __('Novo item', MLCP_TEXT_DOMAIN), 'url' => admin_url('post-new.php?post_type=' . MLCP_POST_TYPE)),
            'edit-tags.php?taxonomy=' . MLCP_GROUP_TAX . '&post_type=' . MLCP_POST_TYPE => array('label' => __('Grupos', MLCP_TEXT_DOMAIN), 'url' => admin_url('edit-tags.php?taxonomy=' . MLCP_GROUP_TAX . '&post_type=' . MLCP_POST_TYPE)),
            'mlcp-sort' => array('label' => __('Ordenação', MLCP_TEXT_DOMAIN), 'url' => self::admin_url('mlcp-sort')),
            'mlcp-shortcodes' => array('label' => __('Shortcodes', MLCP_TEXT_DOMAIN), 'url' => self::admin_url('mlcp-shortcodes')),
            'mlcp-settings' => array('label' => __('Configurações', MLCP_TEXT_DOMAIN), 'url' => self::admin_url('mlcp-settings')),
            'mlcp-license' => array('label' => __('Licença e Planos', MLCP_TEXT_DOMAIN), 'url' => self::admin_url('mlcp-license')),
            'mlcp-analytics' => array('label' => __('Analytics', MLCP_TEXT_DOMAIN), 'url' => self::admin_url('mlcp-analytics')),
        );
    }

    public static function render_admin_header($active = MLCP_MENU_SLUG, $title = 'ML Banner Pro', $subtitle = '') {
        $tabs = self::admin_tabs();
        ob_start();
        ?>
        <div class="mlcp-shell-header">
            <div>
                <div class="mlcp-eyebrow"><?php echo esc_html__('Painel profissional', MLCP_TEXT_DOMAIN); ?></div>
                <h1><?php echo esc_html($title); ?> <span class="mlcp-version">v<?php echo esc_html(MLCP_VERSION); ?></span></h1>
                <?php if ($subtitle !== '') : ?>
                    <p class="mlcp-subtitle"><?php echo esc_html($subtitle); ?></p>
                <?php endif; ?>
            </div>
            <div class="mlcp-badges">
                <span class="mlcp-badge is-dark">GitHub Ready</span>
                <span class="mlcp-badge is-blue">PT-BR / EN / ES</span>
            </div>
        </div>
        <nav class="mlcp-tabs">
            <?php foreach ($tabs as $slug => $tab) : ?>
                <a href="<?php echo esc_url($tab['url']); ?>" class="mlcp-tab <?php echo $slug === $active ? 'is-active' : ''; ?>"><?php echo esc_html($tab['label']); ?></a>
            <?php endforeach; ?>
        </nav>
        <?php
        return ob_get_clean();
    }

    public static function normalize_bool($value, $default = 0) {
        if ($value === '' || $value === null) {
            return (int) $default;
        }
        return in_array(strtolower((string) $value), array('1', 'true', 'yes', 'sim', 'on'), true) ? 1 : 0;
    }

    public static function admin_url($slug) {
        return admin_url('admin.php?page=' . $slug);
    }

    public static function clean_css_size($value, $fallback) {
        $value = trim((string) $value);
        if ($value === '') {
            return $fallback;
        }
        if (preg_match('/^\d+(\.\d+)?(px|%|vh|vw|rem|em)$/', $value)) {
            return $value;
        }
        return $fallback;
    }

    public static function clean_px($value, $fallback) {
        $value = (int) $value;
        if ($value <= 0) {
            return max(1, (int) $fallback);
        }
        return $value;
    }

    public static function get_item_meta($post_id) {
        $date = (string) get_post_meta($post_id, '_mlcp_date', true);
        $subtitle = (string) get_post_meta($post_id, '_mlcp_subtitle', true);
        $combined = trim(implode(' • ', array_filter(array($date, $subtitle), static function ($value) {
            return $value !== '';
        })));

        return array(
            'image_id' => (int) get_post_meta($post_id, '_mlcp_image_id', true),
            'image_url' => (string) get_post_meta($post_id, '_mlcp_image_url', true),
            'date' => $date,
            'subtitle' => $subtitle,
            'subtitle_display' => $combined,
            'link' => (string) get_post_meta($post_id, '_mlcp_link', true),
            'new_tab' => (int) get_post_meta($post_id, '_mlcp_new_tab', true),
            'active' => (int) get_post_meta($post_id, '_mlcp_active', true),
            'expire_at' => (int) get_post_meta($post_id, '_mlcp_expire_at', true),
        );
    }

    public static function parse_local_datetime($value) {
        $value = trim((string) $value);
        if ($value === '') {
            return 0;
        }

        $timezone = wp_timezone();
        $date = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value, $timezone);
        if (!$date) {
            return 0;
        }

        return (int) $date->getTimestamp();
    }

    public static function format_local_datetime($timestamp) {
        $timestamp = (int) $timestamp;
        if ($timestamp <= 0) {
            return '';
        }

        return wp_date('Y-m-d\TH:i', $timestamp, wp_timezone());
    }

    public static function is_item_expired($post_id, $now = null) {
        $expire_at = (int) get_post_meta($post_id, '_mlcp_expire_at', true);
        if ($expire_at <= 0) {
            return false;
        }

        if ($now === null) {
            $now = current_time('timestamp', true);
        }

        return $expire_at <= (int) $now;
    }

    public static function get_image_url($post_id, $size = 'large') {
        $meta = self::get_item_meta($post_id);
        if (!empty($meta['image_id'])) {
            $img = wp_get_attachment_image_url($meta['image_id'], $size);
            if ($img) {
                return $img;
            }
        }
        return $meta['image_url'];
    }

    public static function get_current_admin_context() {
        if (!is_admin() || !function_exists('get_current_screen')) {
            return array('is_plugin' => false, 'active' => '', 'title' => '', 'subtitle' => '');
        }

        $screen = get_current_screen();
        if (!$screen) {
            return array('is_plugin' => false, 'active' => '', 'title' => '', 'subtitle' => '');
        }

        $base = (string) $screen->base;
        $post_type = (string) $screen->post_type;
        $taxonomy = (string) $screen->taxonomy;
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';

        if ($page === MLCP_MENU_SLUG) {
            return array('is_plugin' => true, 'active' => MLCP_MENU_SLUG, 'title' => 'ML Banner Pro', 'subtitle' => 'Visão geral do produto.');
        }
        if ($page === 'mlcp-sort') {
            return array('is_plugin' => true, 'active' => 'mlcp-sort', 'title' => 'ML Banner Pro', 'subtitle' => 'Ordenação manual por grupo.');
        }
        if ($page === 'mlcp-shortcodes') {
            return array('is_plugin' => true, 'active' => 'mlcp-shortcodes', 'title' => 'ML Banner Pro', 'subtitle' => 'Shortcodes prontos para copiar e colar.');
        }
        if ($page === 'mlcp-settings') {
            return array('is_plugin' => true, 'active' => 'mlcp-settings', 'title' => 'ML Banner Pro', 'subtitle' => 'Configurações globais do plugin.');
        }
        if ($page === 'mlcp-license') {
            return array('is_plugin' => true, 'active' => 'mlcp-license', 'title' => 'ML Banner Pro', 'subtitle' => 'Licenciamento conectado ao ML License Hub com Trial, Free, Full e Vitalício no mesmo fluxo comercial.');
        }
        if ($page === 'mlcp-analytics') {
            return array('is_plugin' => true, 'active' => 'mlcp-analytics', 'title' => 'ML Banner Pro', 'subtitle' => 'Desempenho dos banners: visualizações, cliques e CTR.');
        }
        if ($post_type === MLCP_POST_TYPE && $base === 'edit') {
            return array('is_plugin' => true, 'active' => 'edit.php?post_type=' . MLCP_POST_TYPE, 'title' => 'ML Banner Pro', 'subtitle' => 'Gerencie os itens do carrossel.');
        }
        if ($post_type === MLCP_POST_TYPE && in_array($base, array('post', 'post-new'), true)) {
            return array('is_plugin' => true, 'active' => 'post-new.php?post_type=' . MLCP_POST_TYPE, 'title' => 'ML Banner Pro', 'subtitle' => 'Cadastro e edição de itens.');
        }
        if ($taxonomy === MLCP_GROUP_TAX) {
            return array('is_plugin' => true, 'active' => 'edit-tags.php?taxonomy=' . MLCP_GROUP_TAX . '&post_type=' . MLCP_POST_TYPE, 'title' => 'ML Banner Pro', 'subtitle' => 'Grupos com shortcode próprio e configurações independentes.');
        }

        return array('is_plugin' => false, 'active' => '', 'title' => '', 'subtitle' => '');
    }

    public static function get_item_analytics($post_id) {
        $post_id = (int) $post_id;
        return array(
            'views' => max(0, (int) get_post_meta($post_id, '_mlcp_views', true)),
            'clicks' => max(0, (int) get_post_meta($post_id, '_mlcp_clicks', true)),
            'last_activity' => max(0, (int) get_post_meta($post_id, '_mlcp_last_activity', true)),
        );
    }

    public static function get_item_ctr($views, $clicks) {
        $views = max(0, (int) $views);
        $clicks = max(0, (int) $clicks);
        if ($views <= 0) {
            return '0%';
        }
        $ctr = ($clicks / $views) * 100;
        return rtrim(rtrim(number_format_i18n($ctr, 1), '0'), ',.') . '%';
    }

    public static function get_analytics_summary() {
        $posts = get_posts(array(
            'post_type' => MLCP_POST_TYPE,
            'posts_per_page' => -1,
            'post_status' => array('publish', 'future', 'draft', 'pending', 'private'),
            'fields' => 'ids',
        ));

        $summary = array('views' => 0, 'clicks' => 0, 'items' => 0);
        foreach ($posts as $post_id) {
            $analytics = self::get_item_analytics($post_id);
            $summary['views'] += (int) $analytics['views'];
            $summary['clicks'] += (int) $analytics['clicks'];
            $summary['items']++;
        }
        $summary['ctr'] = self::get_item_ctr($summary['views'], $summary['clicks']);
        return $summary;
    }

    public static function increment_analytics($post_id, $event) {
        $post_id = (int) $post_id;
        $event = sanitize_key((string) $event);

        if ($post_id <= 0 || !in_array($event, array('view', 'click'), true)) {
            return false;
        }

        if (get_post_type($post_id) !== MLCP_POST_TYPE) {
            return false;
        }

        $key = $event === 'click' ? '_mlcp_clicks' : '_mlcp_views';
        $current = max(0, (int) get_post_meta($post_id, $key, true));
        update_post_meta($post_id, $key, $current + 1);
        update_post_meta($post_id, '_mlcp_last_activity', current_time('timestamp', true));
        return true;
    }

    public static function reset_analytics($post_id = 0) {
        $post_id = (int) $post_id;
        if ($post_id > 0) {
            delete_post_meta($post_id, '_mlcp_views');
            delete_post_meta($post_id, '_mlcp_clicks');
            delete_post_meta($post_id, '_mlcp_last_activity');
            return;
        }

        $posts = get_posts(array(
            'post_type' => MLCP_POST_TYPE,
            'posts_per_page' => -1,
            'post_status' => 'any',
            'fields' => 'ids',
        ));

        foreach ($posts as $item_id) {
            delete_post_meta((int) $item_id, '_mlcp_views');
            delete_post_meta((int) $item_id, '_mlcp_clicks');
            delete_post_meta((int) $item_id, '_mlcp_last_activity');
        }
    }

    /**
     * One-time migration: copy legacy wp_options analytics into post_meta.
     *
     * Legacy builds stored: mlcp_analytics (or ml_banner_analytics)
     *   schema: array( $post_id => array('views'=>int, 'clicks'=>int, 'last'=>int) )
     *
     * Current build stores per-item post_meta:
     *   _mlcp_views | _mlcp_clicks | _mlcp_last_activity
     *
     * Safe rules:
     *  - Runs only once (guarded by mlcp_migrated_v108)
     *  - Never deletes source options
     *  - Uses max() — never overwrites a higher existing value
     *  - Skips posts that are not MLCP_POST_TYPE
     */
    public static function migrate_legacy_analytics() {
        if ( get_option( 'mlcp_migrated_v108' ) ) {
            return;
        }

        // Read from both possible legacy keys; mlcp_analytics takes priority
        $src_a = get_option( 'mlcp_analytics',      array() );
        $src_b = get_option( 'ml_banner_analytics', array() );

        $src_a = is_array( $src_a ) ? $src_a : array();
        $src_b = is_array( $src_b ) ? $src_b : array();

        // b is base, a overrides on conflict
        $merged = array_merge( $src_b, $src_a );

        foreach ( $merged as $post_id => $data ) {
            $post_id = (int) $post_id;
            if ( $post_id <= 0 ) {
                continue;
            }
            if ( MLCP_POST_TYPE !== get_post_type( $post_id ) ) {
                continue;
            }

            $data = is_array( $data ) ? $data : array();

            $leg_views  = max( 0, (int) ( $data['views']  ?? 0 ) );
            $leg_clicks = max( 0, (int) ( $data['clicks'] ?? 0 ) );
            $leg_last   = max( 0, (int) ( $data['last']   ?? 0 ) );

            // Current values already stored in post_meta (may be 0 or higher)
            $cur_views  = max( 0, (int) get_post_meta( $post_id, '_mlcp_views',         true ) );
            $cur_clicks = max( 0, (int) get_post_meta( $post_id, '_mlcp_clicks',        true ) );
            $cur_last   = max( 0, (int) get_post_meta( $post_id, '_mlcp_last_activity', true ) );

            $new_views  = max( $cur_views,  $leg_views  );
            $new_clicks = max( $cur_clicks, $leg_clicks );
            $new_last   = max( $cur_last,   $leg_last   );

            // Only write if there is something to add
            if ( $new_views > $cur_views ) {
                update_post_meta( $post_id, '_mlcp_views', $new_views );
            }
            if ( $new_clicks > $cur_clicks ) {
                update_post_meta( $post_id, '_mlcp_clicks', $new_clicks );
            }
            if ( $new_last > $cur_last ) {
                update_post_meta( $post_id, '_mlcp_last_activity', $new_last );
            }
        }

        // Mark done — source options intentionally preserved
        update_option( 'mlcp_migrated_v108', 1, false );
    }

}
