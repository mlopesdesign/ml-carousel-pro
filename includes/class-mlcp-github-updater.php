<?php
/**
 * MLCP GitHub Updater
 *
 * Hooks into WordPress plugin update flow using GitHub Releases as source.
 * Fonte: repositorio publico mlopesdesign/ml-carousel-pro-releases (somente ZIPs).
 * Nenhum token e necessario.
 *
 * @package ML_Carousel_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( class_exists( 'MLCP_GitHub_Updater' ) ) { return; }

class MLCP_GitHub_Updater {

    const GITHUB_USER    = 'mlopesdesign';
    // Repositorio PUBLICO so com os ZIPs (o codigo fica em repositorio privado).
    const GITHUB_REPO    = 'ml-carousel-pro-releases';
    const PLUGIN_SLUG    = 'ml-carousel-pro';
    const PLUGIN_FILE    = 'ml-carousel-pro/ml-carousel-pro.php';
    const TRANSIENT_KEY  = 'mlcp_github_release_cache';
    const CACHE_TTL      = 21600; // 6 horas
    const CACHE_FAIL_TTL = 900;   // 15 minutos em falha

    private static $instance = null;

    /* ------------------------------------------------------------------ */
    /* Bootstrap                                                            */
    /* ------------------------------------------------------------------ */

    public static function init() {
        // Painel, WP-Cron (atualizacao automatica) e WP-CLI.
        $is_cli = defined( 'WP_CLI' ) && WP_CLI;
        if ( ! is_admin() && ! wp_doing_cron() && ! $is_cli ) {
            return null;
        }
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->clear_legacy_caches_on_version_change();

        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
        add_filter( 'plugins_api',                           array( $this, 'plugin_info'      ), 20, 3 );
        add_filter( 'upgrader_source_selection',             array( $this, 'fix_source_dir'   ), 10, 4 );
    }

    /**
     * Clear old duplicated updater caches once per installed version.
     * Older builds loaded a second updater with transient ml_updater_{md5(slug)}.
     */
    private function clear_legacy_caches_on_version_change() {
        $option_key = 'mlcp_updater_cache_version';
        $stored     = get_option( $option_key, '' );

        if ( $stored === MLCP_VERSION ) {
            return;
        }

        delete_transient( self::TRANSIENT_KEY );
        delete_transient( 'ml_updater_' . md5( self::PLUGIN_SLUG ) );
        delete_site_transient( 'update_plugins' );
        update_option( $option_key, MLCP_VERSION, false );
    }

    /* ------------------------------------------------------------------ */
    /* GitHub API                                                           */
    /* ------------------------------------------------------------------ */

    private function api_url() {
        return sprintf(
            'https://api.github.com/repos/%s/%s/releases/latest',
            self::GITHUB_USER,
            self::GITHUB_REPO
        );
    }

    private function request_args() {
        return array(
            'timeout'    => 15,
            'user-agent' => 'ML-Banner-Pro-Updater/' . MLCP_VERSION . '; WordPress/' . get_bloginfo( 'version' ),
            'headers'    => array(
                'Accept' => 'application/vnd.github.v3+json',
            ),
        );
    }

    public function fetch_latest_release( $force = false ) {
        if ( is_admin() && isset( $_GET['force-check'] ) ) {
            $force = true;
        }

        if ( ! $force ) {
            $cached = get_transient( self::TRANSIENT_KEY );
            if ( is_array( $cached ) ) {
                return $cached;
            }
            if ( $cached === 'error' ) {
                return null; // Falha recente: respeita a janela antes de consultar de novo.
            }
        }

        $response = wp_remote_get( $this->api_url(), $this->request_args() );
        $body     = null;
        if ( ! is_wp_error( $response ) && (int) wp_remote_retrieve_response_code( $response ) === 200 ) {
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
        }

        if ( ! is_array( $body ) || empty( $body['tag_name'] ) ) {
            // Falha: nao apaga um resultado bom ainda valido (consulta forcada que falhou).
            if ( ! is_array( get_transient( self::TRANSIENT_KEY ) ) ) {
                set_transient( self::TRANSIENT_KEY, 'error', self::CACHE_FAIL_TTL );
            }
            return null;
        }

        // Release ainda sem o ZIP exato (asset em upload): cache curto para nao atrasar a oferta.
        $ttl = $this->release_zip_url( $body ) !== '' ? self::CACHE_TTL : self::CACHE_FAIL_TTL;
        set_transient( self::TRANSIENT_KEY, $body, $ttl );
        return $body;
    }

    private function release_version( $release ) {
        if ( ! is_array( $release ) || empty( $release['tag_name'] ) ) {
            return '';
        }
        return ltrim( (string) $release['tag_name'], 'v' );
    }

    /**
     * Somente o asset exato ml-carousel-pro-v{versao}.zip da propria release.
     * Sem "qualquer ZIP" e sem zipball (estrutura de pasta errada).
     */
    private function release_zip_url( $release ) {
        if ( ! is_array( $release ) || empty( $release['assets'] ) || ! is_array( $release['assets'] ) ) {
            return '';
        }
        $expected = self::PLUGIN_SLUG . '-v' . $this->release_version( $release ) . '.zip';
        foreach ( $release['assets'] as $asset ) {
            if ( isset( $asset['name'], $asset['browser_download_url'] ) && $asset['name'] === $expected ) {
                return (string) $asset['browser_download_url'];
            }
        }
        return '';
    }

    private function icons() {
        return array(
            '1x'      => MLCP_PLUGIN_URL . 'assets/images/icon-128x128.png',
            '2x'      => MLCP_PLUGIN_URL . 'assets/images/icon-256x256.png',
            'default' => MLCP_PLUGIN_URL . 'assets/images/icon-256x256.png',
        );
    }

    /* ------------------------------------------------------------------ */
    /* WordPress update hooks                                               */
    /* ------------------------------------------------------------------ */

    public function check_for_update( $transient ) {
        if ( ! is_object( $transient ) || empty( $transient->checked ) ) {
            return $transient;
        }

        $release = $this->fetch_latest_release();
        if ( ! $release ) {
            return $transient;
        }

        $remote_version = $this->release_version( $release );
        if ( $remote_version === '' ) {
            return $transient;
        }
        $zip_url = $this->release_zip_url( $release );

        $obj               = new stdClass();
        $obj->id           = self::GITHUB_USER . '/' . self::GITHUB_REPO;
        $obj->slug         = self::PLUGIN_SLUG;
        $obj->plugin       = self::PLUGIN_FILE;
        $obj->new_version  = $remote_version;
        $obj->url          = 'https://github.com/' . self::GITHUB_USER . '/' . self::GITHUB_REPO;
        $obj->package      = $zip_url;
        $obj->icons        = $this->icons();
        $obj->banners      = array();
        $obj->banners_rtl  = array();
        $obj->requires     = '6.0';
        $obj->requires_php = '7.4';
        $obj->tested       = '6.8';
        $obj->compatibility = new stdClass();
        $obj->upgrade_notice = '';

        if ( $zip_url !== '' && version_compare( $remote_version, MLCP_VERSION, '>' ) ) {
            if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
                $transient->response = array();
            }
            $transient->response[ self::PLUGIN_FILE ] = $obj;
            if ( isset( $transient->no_update ) && is_array( $transient->no_update ) ) {
                unset( $transient->no_update[ self::PLUGIN_FILE ] );
            }
        } else {
            // Sem versao nova (ou sem o ZIP exato): no_update, para o WP mostrar o estado certo
            // e o controle de atualizacao automatica.
            $obj->new_version = MLCP_VERSION;
            $obj->package     = '';
            if ( ! isset( $transient->no_update ) || ! is_array( $transient->no_update ) ) {
                $transient->no_update = array();
            }
            $transient->no_update[ self::PLUGIN_FILE ] = $obj;
            if ( isset( $transient->response ) && is_array( $transient->response ) ) {
                unset( $transient->response[ self::PLUGIN_FILE ] );
            }
        }

        return $transient;
    }

    public function plugin_info( $result, $action, $args ) {
        if ( $action !== 'plugin_information' ) {
            return $result;
        }
        if ( ! isset( $args->slug ) || $args->slug !== self::PLUGIN_SLUG ) {
            return $result;
        }

        $release = $this->fetch_latest_release();
        if ( ! $release ) {
            return $result;
        }

        $remote_version = $this->release_version( $release );
        $zip_url        = $this->release_zip_url( $release );

        $info                = new stdClass();
        $info->name          = 'ML Banner Pro';
        $info->slug          = self::PLUGIN_SLUG;
        $info->version       = $remote_version;
        $info->author        = '<a href="https://mlopesdesign.com.br">Marcio Lopes</a>';
        $info->author_profile = 'https://mlopesdesign.com.br';
        $info->homepage      = 'https://github.com/' . self::GITHUB_USER . '/' . self::GITHUB_REPO;
        $info->requires      = '6.0';
        $info->requires_php  = '7.4';
        $info->tested        = '6.8';
        $info->download_link = $zip_url;
        $info->trunk         = $zip_url;
        $info->last_updated  = $release['published_at'] ?? '';
        $info->icons         = $this->icons();
        $info->sections      = array(
            'description' => 'ML Banner Pro — carrossel profissional com analytics, grupos e shortcodes para WordPress.',
            'changelog'   => ! empty( $release['body'] ) ? wp_kses_post( $release['body'] ) : 'Ver ' . esc_url( 'https://github.com/' . self::GITHUB_USER . '/' . self::GITHUB_REPO . '/releases' ),
        );

        return $info;
    }

    /**
     * Garante a pasta ml-carousel-pro/ ao instalar (o ZIP oficial ja vem certo).
     */
    public function fix_source_dir( $source, $remote_source, $upgrader, $hook_extra ) {
        if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== self::PLUGIN_FILE ) {
            return $source;
        }

        global $wp_filesystem;

        if ( ! $wp_filesystem ) {
            return $source;
        }

        $intended = trailingslashit( $remote_source ) . self::PLUGIN_SLUG . '/';

        if ( trailingslashit( $source ) === $intended ) {
            return $source;
        }

        if ( $wp_filesystem->exists( $source ) && $wp_filesystem->move( $source, $intended ) ) {
            return $intended;
        }

        return $source;
    }

    /* ------------------------------------------------------------------ */
    /* Admin diagnostics                                                    */
    /* ------------------------------------------------------------------ */

    public static function get_diagnostics() {
        $self    = self::init();
        // Usa o cache (6 h); "?force-check" na URL forca uma consulta nova.
        $release = $self ? $self->fetch_latest_release() : null;

        $remote_version = $self ? $self->release_version( $release ) : '';
        $zip_url        = ( $self && $release ) ? $self->release_zip_url( $release ) : '';

        return array(
            'repo'             => 'https://github.com/' . self::GITHUB_USER . '/' . self::GITHUB_REPO,
            'current_version'  => MLCP_VERSION,
            'remote_version'   => $remote_version !== '' ? $remote_version : '—',
            'update_available' => ( $remote_version !== '' && $zip_url !== '' && version_compare( $remote_version, MLCP_VERSION, '>' ) ) ? 'Sim' : 'Não',
            'source_status'    => $release ? 'GitHub Release detectado ✓' : 'Sem resposta da API — verifique a conectividade',
            'token_set'        => 'Não necessário (repositório público de releases)',
            'zip_detected'     => $zip_url !== '' ? 'Sim' : 'Não',
            'zip_url'          => $zip_url !== '' ? 'asset detectado' : '—',
        );
    }
}
