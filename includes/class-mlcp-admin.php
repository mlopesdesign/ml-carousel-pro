<?php
if (!defined('ABSPATH')) {
    exit;
}

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
        add_submenu_page(MLCP_MENU_SLUG, __('Itens', MLCP_TEXT_DOMAIN), __('Itens', MLCP_TEXT_DOMAIN), 'edit_posts', 'edit.php?post_type=' . MLCP_POST_TYPE);
        add_submenu_page(MLCP_MENU_SLUG, __('Grupos', MLCP_TEXT_DOMAIN), __('Grupos', MLCP_TEXT_DOMAIN), 'manage_categories', 'edit-tags.php?taxonomy=' . MLCP_GROUP_TAX . '&post_type=' . MLCP_POST_TYPE);
        add_submenu_page(MLCP_MENU_SLUG, __('Ordenação', MLCP_TEXT_DOMAIN), __('Ordenação', MLCP_TEXT_DOMAIN), 'edit_posts', 'mlcp-sort', array(__CLASS__, 'render_sort'));
        add_submenu_page(MLCP_MENU_SLUG, __('Shortcodes', MLCP_TEXT_DOMAIN), __('Shortcodes', MLCP_TEXT_DOMAIN), 'manage_options', 'mlcp-shortcodes', array(__CLASS__, 'render_shortcodes'));
        add_submenu_page(MLCP_MENU_SLUG, __('Configurações', MLCP_TEXT_DOMAIN), __('Configurações', MLCP_TEXT_DOMAIN), 'manage_options', 'mlcp-settings', array(__CLASS__, 'render_settings'));
        add_submenu_page(MLCP_MENU_SLUG, __('Licença e Planos', MLCP_TEXT_DOMAIN), __('Licença e Planos', MLCP_TEXT_DOMAIN), 'manage_options', 'mlcp-license', array(__CLASS__, 'render_license'));
        add_submenu_page(MLCP_MENU_SLUG, __('Analytics', MLCP_TEXT_DOMAIN), __('Analytics', MLCP_TEXT_DOMAIN), 'manage_options', 'mlcp-analytics', array(__CLASS__, 'render_analytics'));
    }
    public static function render_shared_admin_header() {
        $context = MLCP_Helpers::get_current_admin_context();
        if (empty($context['is_plugin'])) { return; }
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if (in_array($page, array(MLCP_MENU_SLUG, 'mlcp-sort', 'mlcp-shortcodes', 'mlcp-settings', 'mlcp-license', 'mlcp-analytics'), true)) { return; }
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
        $summary = MLCP_Helpers::get_analytics_summary();
        $items = get_posts(array('post_type' => MLCP_POST_TYPE, 'posts_per_page' => -1, 'post_status' => array('publish','future','draft','pending','private'), 'orderby' => array('menu_order' => 'ASC', 'date' => 'DESC')));
        include MLCP_PLUGIN_DIR . 'admin/views/analytics.php';
    }
    public static function render_license() {
        $manager = new MLCP_License_Manager();
        $summary = $manager->get_license_summary();
        $license_state = $summary['state']; $license_product_name = $summary['product_name'];
        $license_product_id = $summary['product_id']; $license_server_url = $summary['server_url'];
        $license_domain = $summary['domain']; $site_fingerprint = $summary['site_fingerprint'];
        $plan_label = $summary['plan_label']; $status_label = $summary['status_label'];
        include MLCP_PLUGIN_DIR . 'admin/views/license.php';
    }
    public static function handle_activate_license() { (new MLCP_License_Manager())->handle_activate_request(); }
    public static function handle_sync_license() { (new MLCP_License_Manager())->handle_sync_request(); }
    public static function handle_remove_license() { (new MLCP_License_Manager())->handle_remove_request(); }
    public static function render_sort() {
        $groups = get_terms(array('taxonomy' => MLCP_GROUP_TAX, 'hide_empty' => false));
        include MLCP_PLUGIN_DIR . 'admin/views/sort.php';
    }
    public static function handle_reset_analytics() {
        if (!current_user_can('manage_options')) { wp_die(esc_html__('Acesso negado.', MLCP_TEXT_DOMAIN)); }
        $item_id = isset($_GET['item_id']) ? (int) $_GET['item_id'] : 0;
        check_admin_referer($item_id > 0 ? 'mlcp_reset_analytics_' . $item_id : 'mlcp_reset_analytics_all');
        MLCP_Helpers::reset_analytics($item_id);
        wp_safe_redirect(add_query_arg(array('page' => 'mlcp-analytics', 'analytics_reset' => $item_id > 0 ? 'item' : 'all'), admin_url('admin.php')));
        exit;
    }
    public static function ajax_track_event() {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'mlcp_front_nonce')) { wp_send_json_error(array('message' => 'Invalid'), 403); }
        $post_id = isset($_POST['item_id']) ? (int) $_POST['item_id'] : 0;
        $event = isset($_POST['event']) ? sanitize_key(wp_unslash($_POST['event'])) : '';
        if (!MLCP_Helpers::increment_analytics($post_id, $event)) { wp_send_json_error(array('message' => 'Invalid event'), 400); }
        wp_send_json_success(array('ok' => true));
    }
    public static function ajax_load_sort_group() {
        check_ajax_referer('mlcp_admin_nonce', 'nonce');
        if (!current_user_can('edit_posts')) { wp_send_json_error(null, 403); }
        $group_id = isset($_POST['group_id']) ? (int) $_POST['group_id'] : 0;
        if (!$group_id) { wp_send_json_error(null, 400); }
        $posts = get_posts(array('post_type' => MLCP_POST_TYPE, 'posts_per_page' => -1, 'orderby' => array('menu_order' => 'ASC', 'date' => 'DESC'), 'tax_query' => array(array('taxonomy' => MLCP_GROUP_TAX, 'field' => 'term_id', 'terms' => $group_id))));
        ob_start();
        if ($posts) {
            echo '<ul class="mlcp-sort-list">';
            foreach ($posts as $post) {
                $img = MLCP_Helpers::get_image_url($post->ID, 'thumbnail');
                echo '<li class="mlcp-sort-item" data-post-id="' . esc_attr($post->ID) . '">';
                echo '<span class="mlcp-sort-handle">⋮⋮</span>';
                echo $img ? '<img src="' . esc_url($img) . '" alt="" />' : '<span class="mlcp-sort-placeholder">Sem imagem</span>';
                echo '<div class="mlcp-sort-content"><strong>' . esc_html(get_the_title($post->ID)) . '</strong></div>';
                echo '</li>';
            }
            echo '</ul>';
        } else { echo '<div class="mlcp-empty-state">Nenhum item.</div>'; }
        wp_send_json_success(array('html' => ob_get_clean()));
    }
    public static function ajax_save_sort_group() {
        check_ajax_referer('mlcp_admin_nonce', 'nonce');
        if (!current_user_can('edit_posts')) { wp_send_json_error(null, 403); }
        $order = isset($_POST['order']) ? (array) $_POST['order'] : array();
        foreach ($order as $index => $post_id) { wp_update_post(array('ID' => (int) $post_id, 'menu_order' => (int) $index)); }
        wp_send_json_success(array('message' => 'Saved'));
    }
    public static function save_settings() {
        if (!current_user_can('manage_options')) { wp_die('Denied'); }
        check_admin_referer('mlcp_save_settings');
        MLCP_Helpers::update_settings(array('default_width_value' => max(1,(int)($_POST['default_width_value']??1140)), 'default_width_unit' => MLCP_Helpers::normalize_size_unit(wp_unslash($_POST['default_width_unit']??'px')), 'default_lock_proportion' => !empty($_POST['default_lock_proportion'])?1:0, 'default_rounded_corners' => !empty($_POST['default_rounded_corners'])?1:0, 'default_desktop' => max(1,(int)($_POST['default_desktop']??3)), 'default_tablet' => max(1,(int)($_POST['default_tablet']??2)), 'default_mobile' => max(1,(int)($_POST['default_mobile']??1)), 'default_gap' => max(0,(int)($_POST['default_gap']??18)), 'default_autoplay' => !empty($_POST['default_autoplay'])?1:0, 'default_autoplay_speed' => max(1000,(int)($_POST['default_autoplay_speed']??4500)), 'default_arrows' => !empty($_POST['default_arrows'])?1:0, 'default_overlay_enabled' => !empty($_POST['default_overlay_enabled'])?1:0, 'default_overlay_opacity' => max(0,min(100,(int)($_POST['default_overlay_opacity']??42))), 'default_show_title' => !empty($_POST['default_show_title'])?1:0, 'default_show_subtitle' => !empty($_POST['default_show_subtitle'])?1:0));
        wp_safe_redirect(add_query_arg(array('page'=>'mlcp-settings','updated'=>1),admin_url('admin.php')));
        exit;
    }
    public static function item_columns($c){$n=array();foreach($c as $k=>$l){if($k==='date')continue;$n[$k]=$l;if($k==='title'){$n['mlcp_image']=__('Imagem',MLCP_TEXT_DOMAIN);$n['mlcp_subtitle']=__('Data/Subtítulo',MLCP_TEXT_DOMAIN);$n['mlcp_group']=__('Grupo',MLCP_TEXT_DOMAIN);$n['mlcp_order']=__('Ordem',MLCP_TEXT_DOMAIN);$n['mlcp_active']=__('Ativo',MLCP_TEXT_DOMAIN);$n['mlcp_expire']=__('Expira em',MLCP_TEXT_DOMAIN);}}return $n;}
    public static function render_item_columns($col,$pid){$m=MLCP_Helpers::get_item_meta($pid);if($col==='mlcp_image'){$i=MLCP_Helpers::get_image_url($pid,'thumbnail');echo $i?'<img src="'.esc_url($i).'" style="width:72px;height:48px;object-fit:cover;border-radius:8px;" alt="" />':'&mdash;';}elseif($col==='mlcp_subtitle'){echo $m['subtitle_display']?esc_html($m['subtitle_display']):'&mdash;';}elseif($col==='mlcp_group'){$t=get_the_terms($pid,MLCP_GROUP_TAX);echo($t&&!is_wp_error($t))?esc_html(implode(', ',wp_list_pluck($t,'name'))):'&mdash;';}elseif($col==='mlcp_order'){$p=get_post($pid);echo(int)$p->menu_order;}elseif($col==='mlcp_active'){echo!empty($m['active'])?'Sim':'Não';}elseif($col==='mlcp_expire'){if(empty($m['expire_at'])){echo'-';}elseif(MLCP_Helpers::is_item_expired($pid)){echo'<span style="color:#b32d2e;font-weight:600;">Expirado</span>';}else{echo esc_html(wp_date('d/m/Y H:i',(int)$m['expire_at'],wp_timezone()));}}}
    public static function sortable_item_columns($c){$c['mlcp_order']='menu_order';return $c;}
    public static function handle_admin_query($q){if(!is_admin()||!$q->is_main_query()||$q->get('post_type')!==MLCP_POST_TYPE)return;if(!$q->get('orderby'))$q->set('orderby',array('menu_order'=>'ASC','date'=>'DESC'));}
    public static function group_columns($c){$n=array();foreach($c as $k=>$l){$n[$k]=$l;if($k==='name'){$n['mlcp_shortcode']=__('Shortcode',MLCP_TEXT_DOMAIN);$n['mlcp_layout']=__('Layout',MLCP_TEXT_DOMAIN);}}return $n;}
    public static function render_group_columns($content,$col,$tid){$t=get_term($tid,MLCP_GROUP_TAX);$s=MLCP_Helpers::get_group_settings($tid);if($col==='mlcp_shortcode'){$sc=MLCP_Helpers::get_group_shortcode($t->slug);return'<div class="mlcp-inline-shortcode"><code>'.esc_html($sc).'</code><button type="button" class="button button-small mlcp-copy-shortcode" data-shortcode="'.esc_attr($sc).'">Copiar</button></div>';}if($col==='mlcp_layout'){return esc_html(sprintf('D:%d T:%d M:%d · %dpx × %dpx',$s['desktop'],$s['tablet'],$s['mobile'],$s['width_px'],$s['height_px']));}return $content;}
    public static function duplicate_row_action($a,$p){if(!$p||$p->post_type!==MLCP_POST_TYPE)return $a;if(!current_user_can('edit_post',$p->ID))return $a;$url=wp_nonce_url(admin_url('admin-post.php?action=mlcp_duplicate_item&post='.(int)$p->ID),'mlcp_duplicate_item_'.(int)$p->ID);$a['mlcp_duplicate']='<a href="'.esc_url($url).'">Duplicar</a>';return $a;}
    public static function handle_duplicate_item(){$pid=isset($_GET['post'])?(int)$_GET['post']:0;if(!$pid)wp_die('Invalid');if(!current_user_can('edit_post',$pid))wp_die('Denied');check_admin_referer('mlcp_duplicate_item_'.$pid);$p=get_post($pid,ARRAY_A);if(!$p||$p['post_type']!==MLCP_POST_TYPE)wp_die('Invalid');$np=$p;unset($np['ID'],$np['guid'],$np['post_date'],$np['post_date_gmt'],$np['post_modified'],$np['post_modified_gmt']);$np['post_status']='draft';$np['post_title']=$p['post_title']?$p['post_title'].' - Cópia':'Cópia do item';$np['post_name']='';$nid=wp_insert_post(wp_slash($np),true);if(is_wp_error($nid))wp_die(esc_html($nid->get_error_message()));foreach(get_post_meta($pid) as $mk=>$mvs){if(in_array($mk,array('_edit_lock','_edit_last','_wp_old_slug'),true))continue;delete_post_meta($nid,$mk);foreach((array)$mvs as $mv)add_post_meta($nid,$mk,$mv);}$terms=wp_get_object_terms($pid,MLCP_GROUP_TAX,array('fields'=>'ids'));if(!is_wp_error($terms)&&!empty($terms))wp_set_object_terms($nid,$terms,MLCP_GROUP_TAX,false);if(!empty($p['menu_order']))wp_update_post(array('ID'=>$nid,'menu_order'=>(int)$p['menu_order']));$ea=(int)get_post_meta($nid,'_mlcp_expire_at',true);MLCP_Meta_Boxes::sync_expiration($nid,$ea);wp_safe_redirect(get_edit_post_link($nid,'url'));exit;}
}
