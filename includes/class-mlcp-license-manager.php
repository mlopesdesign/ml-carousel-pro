<?php
/**
 * Licença do ML Banner Pro.
 *
 * 1.13.0: a licença passa a ser emitida e conferida pelo site tools.mlopesdesign.com.br
 * (módulo ML License Hub, cliente ML_License_Client_V2). O servidor antigo
 * (license.mlopesdesign.com.br) não é mais consultado. Regras do padrão ML:
 * 1 licença = 1 site (o Pacote Agência vale em vários sites); teste grátis de 30 dias
 * por site; sem teste/licença válidos, o painel fica travado para criar/editar/apagar —
 * os banners publicados continuam no ar e nenhum dado é apagado.
 *
 * Quem já usava o plugin antes da 1.13.0 recebe o teste grátis automaticamente na
 * primeira visita ao painel (o site não trava de surpresa após a atualização).
 *
 * A option antiga (mlcp_license_state) é preservada (não é mais usada).
 *
 * @package ML_Carousel_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

// O cliente de licença é carregado ANTES da guarda abaixo: o PHP declara a classe deste
// arquivo já na compilação, então class_exists() é sempre verdadeiro aqui e o return
// impediria o carregamento do cliente (erro fatal quando nenhum outro plugin ML o carregou).
require_once __DIR__ . '/class-ml-license-client.php';

if ( class_exists( 'MLCP_License_Manager' ) ) { return; }


class MLCP_License_Manager {
    const API_BASE    = 'https://tools.mlopesdesign.com.br/wp-json/mllh/v1';
    const PUBLIC_KEY  = 'VGZQp2X3RJMuDmQBlIEXS4fqsZD1CnFlIJgfVtY/pHo=';
    const PRODUCT_ID  = 'ml-carousel-pro';
    const BUY_URL     = 'https://tools.mlopesdesign.com.br/precos/';
    const ACCOUNT_URL = 'https://tools.mlopesdesign.com.br/minha-conta/';
    const AUTO_TRIAL  = 'mlcp_auto_trial_tried';

    /** @var ML_License_Client_V2|null */
    private static $client = null;

    public static function client() {
        if (null === self::$client) {
            self::$client = new ML_License_Client_V2(array(
                'api_base'   => self::API_BASE,
                'product'    => self::PRODUCT_ID,
                'public_key' => self::PUBLIC_KEY,
                'version'    => MLCP_VERSION,
            ));
        }
        return self::$client;
    }

    /** Teste ou licença válidos neste site. */
    public static function is_active() {
        return self::client()->is_premium();
    }

    /** @var bool|null Resultado guardado durante a requisição (zera quando a licença muda). */
    private static $locked = null;

    /** Painel travado (sem teste nem licença). */
    public static function is_locked() {
        if (null === self::$locked) {
            self::$locked = !self::is_active();
        }
        return self::$locked;
    }

    public static function forget() {
        self::$locked = null;
    }

    /* ------------------------------------------------------------------ ganchos */

    public static function boot() {
        self::client(); // agenda a renovação diária
        add_action('admin_init', array(__CLASS__, 'maybe_auto_trial'), 5);
        add_action('current_screen', array(__CLASS__, 'guard_screens'));
        add_filter('map_meta_cap', array(__CLASS__, 'lock_caps'), 10, 4);
        add_filter('pre_insert_term', array(__CLASS__, 'lock_new_group'), 10, 2);
        add_action('admin_notices', array(__CLASS__, 'locked_notice'));
        $opt = self::client()->option_name();
        foreach (array('added_option', 'updated_option', 'deleted_option') as $hook) {
            add_action($hook, function ($name) use ($opt) {
                if ($name === $opt) {
                    MLCP_License_Manager::forget();
                }
            });
        }
    }

    /** Primeira visita ao painel: começa o teste grátis sozinho (uma tentativa por site). */
    public static function maybe_auto_trial() {
        if (wp_doing_ajax() || !current_user_can('manage_options') || get_option(self::AUTO_TRIAL)) {
            return;
        }
        update_option(self::AUTO_TRIAL, time(), false);
        $state = self::client()->state();
        if (empty($state['active']) && 'none' === $state['mode'] && empty($state['trial_used'])) {
            self::client()->start_trial();
        }
    }

    /** Sem licença: não abre as telas de criar/editar banner e grupo. */
    public static function guard_screens($screen) {
        if (!$screen || !self::is_locked()) {
            return;
        }
        $editing_item  = MLCP_POST_TYPE === $screen->post_type && 'post' === $screen->base;
        $editing_group = MLCP_GROUP_TAX === $screen->taxonomy && 'term' === $screen->base;
        if ($editing_item || $editing_group) {
            self::redirect_with_notice('error', __('Ative a licença (ou comece o teste grátis) para criar e editar banners. Os banners publicados continuam no ar.', MLCP_TEXT_DOMAIN));
        }
    }

    /** Sem licença: banners e grupos ficam só para leitura (lista continua visível). */
    public static function lock_caps($caps, $cap, $user_id, $args) {
        $post_caps = array('edit_post', 'delete_post', 'publish_post');
        $term_caps = array('edit_term', 'delete_term');
        if (!in_array($cap, $post_caps, true) && !in_array($cap, $term_caps, true)) {
            return $caps;
        }
        $id = isset($args[0]) ? (int) $args[0] : 0;
        if (!$id) {
            return $caps;
        }
        if (in_array($cap, $post_caps, true)) {
            if (get_post_type($id) !== MLCP_POST_TYPE) {
                return $caps;
            }
        } else {
            $term = get_term($id);
            if (!$term || is_wp_error($term) || $term->taxonomy !== MLCP_GROUP_TAX) {
                return $caps;
            }
        }
        return self::is_locked() ? array('do_not_allow') : $caps;
    }

    public static function lock_new_group($term, $taxonomy) {
        if (MLCP_GROUP_TAX === $taxonomy && self::is_locked()) {
            return new WP_Error('mlcp_locked', __('Ative a licença do ML Banner Pro para criar grupos.', MLCP_TEXT_DOMAIN));
        }
        return $term;
    }

    /** Para ações de gravação do próprio painel (configurações, ordem, duplicar, mover). */
    public static function guard_write($ajax = false) {
        if (!self::is_locked()) {
            return;
        }
        $msg = __('Ative a licença (ou comece o teste grátis) para alterar o ML Banner Pro. Os banners publicados continuam no ar.', MLCP_TEXT_DOMAIN);
        if ($ajax) {
            wp_send_json_error(array('message' => $msg), 403);
        }
        self::redirect_with_notice('error', $msg);
    }

    public static function locked_notice() {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || !current_user_can('manage_options') || !self::is_locked()) {
            return;
        }
        $ours = MLCP_POST_TYPE === $screen->post_type || MLCP_GROUP_TAX === $screen->taxonomy || false !== strpos((string) $screen->id, 'mlcp');
        if (!$ours || false !== strpos((string) $screen->id, 'mlcp-license')) {
            return;
        }
        printf(
            '<div class="notice notice-warning"><p><strong>ML Banner Pro:</strong> %s <a href="%s">%s</a></p></div>',
            esc_html__('sem licença ativa — o painel está só para leitura. Os banners publicados continuam no ar.', MLCP_TEXT_DOMAIN),
            esc_url(MLCP_Helpers::admin_url('mlcp-license')),
            esc_html__('Ativar licença', MLCP_TEXT_DOMAIN)
        );
    }

    /* ------------------------------------------------------------------ tela */

    /** Estado pronto para a tela. */
    public function get_license_summary() {
        $state  = self::client()->state();
        $active = !empty($state['active']);
        $mode   = (string) ($state['mode'] ?? 'none');
        $plan   = strtolower((string) ($state['plan'] ?? ''));
        $days   = $state['days_left'] ?? null;

        $plans = array(
            'trial'    => 'Teste grátis',
            'monthly'  => 'Mensal',
            'annual'   => 'Anual',
            'yearly'   => 'Anual',
            'lifetime' => 'Vitalícia',
            'manual'   => 'Cortesia',
            'courtesy' => 'Cortesia',
        );
        $plan_label = $plans[$plan] ?? ('' !== $plan ? ucfirst($plan) : '—');

        if ($active && 'trial' === $mode) {
            $label   = 'Teste';
            $tone    = 'trial';
            $message = null === $days ? 'Teste grátis ativo neste site.' : sprintf(1 === (int) $days ? 'Teste grátis ativo: falta %d dia.' : 'Teste grátis ativo: faltam %d dias.', (int) $days);
        } elseif ($active) {
            $label   = 'Ativa';
            $tone    = 'full';
            $message = 'Licença ativa neste site.';
        } else {
            $label = 'Sem licença';
            $tone  = 'free';
            if ('trial_expired' === ($state['status'] ?? '')) {
                $message = 'O teste grátis terminou. Ative uma licença para voltar a editar os banners. Os banners publicados continuam no ar.';
            } elseif ('' !== (string) ($state['message'] ?? '')) {
                $message = (string) $state['message'];
            } else {
                $message = 'Comece o teste grátis de 30 dias ou ative a sua licença.';
            }
        }

        $expires_label = '';
        if (!empty($state['expires_at'])) {
            $ts = strtotime((string) $state['expires_at']);
            if ($ts) {
                $expires_label = wp_date(get_option('date_format', 'd/m/Y'), $ts);
            }
        }

        return array(
            'active'          => $active,
            'mode'            => $mode,
            'is_trial'        => $active && 'trial' === $mode,
            'label'           => $label,
            'tone'            => $tone,
            'message'         => $message,
            'plan_label'      => $plan_label,
            'expires_label'   => $expires_label,
            'days_left'       => $days,
            'key_masked'      => (string) ($state['key_masked'] ?? ''),
            'can_start_trial' => !$active && empty($state['trial_used']) && 'license' !== $mode,
            'domain'          => (string) wp_parse_url(home_url(), PHP_URL_HOST),
        );
    }

    /* ------------------------------------------------------------------ ações */

    public function handle_activate_request() {
        $this->guard_admin_request('mlcp_activate_license');
        $key    = isset($_POST['license_key']) ? sanitize_text_field(wp_unslash($_POST['license_key'])) : '';
        $result = self::client()->activate($key);
        if (is_wp_error($result)) {
            self::redirect_with_notice('error', $result->get_error_message());
        }
        self::redirect_with_notice('success', __('Licença ativada neste site.', MLCP_TEXT_DOMAIN));
    }

    /** Começa o teste grátis ou confere a licença agora. */
    public function handle_sync_request() {
        $this->guard_admin_request('mlcp_sync_license');
        $op     = isset($_POST['mlcp_op']) ? sanitize_key(wp_unslash($_POST['mlcp_op'])) : 'check';
        $result = 'trial' === $op ? self::client()->start_trial() : self::client()->refresh();
        if (is_wp_error($result)) {
            self::redirect_with_notice('error', $result->get_error_message());
        }
        self::redirect_with_notice('success', 'trial' === $op ? __('Teste grátis de 30 dias ativo neste site.', MLCP_TEXT_DOMAIN) : __('Licença conferida.', MLCP_TEXT_DOMAIN));
    }

    public function handle_remove_request() {
        $this->guard_admin_request('mlcp_remove_license');
        self::client()->deactivate();
        self::redirect_with_notice('success', __('Licença removida deste site. A chave pode ser usada em outro site.', MLCP_TEXT_DOMAIN));
    }

    private function guard_admin_request($nonce_action) {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Permissão insuficiente.', MLCP_TEXT_DOMAIN));
        }
        check_admin_referer($nonce_action);
    }

    public static function redirect_with_notice($type, $message) {
        $url = add_query_arg(array(
            'page'             => 'mlcp-license',
            'mlcp_notice_type' => rawurlencode($type),
            'mlcp_notice'      => rawurlencode($message),
        ), admin_url('admin.php'));
        wp_safe_redirect($url);
        exit;
    }
}
