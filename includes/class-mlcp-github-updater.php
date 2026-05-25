<?php
/**
 * MLCP GitHub Updater
 *
 * Hooks into WordPress plugin update flow using GitHub Releases as source.
 * Works with public and private repositories.
 *
 * Token configuration (private repos — add to wp-config.php):
 *   define( 'MLMD_GITHUB_TOKEN_ML_CAROUSEL_PRO', 'ghp_your_token_here' );
 *
 * Or via filter:
 *   add_filter( 'mlmd_github_token_ml_carousel_pro', fn() => 'ghp_your_token' );
 *
 * Token is NEVER stored or displayed in wp-admin.
 *
 * @package ML_Carousel_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MLCP_GitHub_Updater {

    const GITHUB_USER    = 'mlopesdesign';
    const GITHUB_REPO    = 'ml-carousel-pro';
    const PLUGIN_SLUG    = 'ml-carousel-pro';
    const PLUGIN_FILE    = 'ml-carousel-pro/ml-carousel-pro.php';
    const TRANSIENT_KEY  = 'mlcp_github_release_cache';
    const CACHE_TTL      = 21600; // 6 hours
    const CACHE_FAIL_TTL = 1800;  // 30 min on failure

    private static $instance = null;

    /* ------------------------------------------------------------------ */
    /* Bootstrap — must run on admin_init only                             */
    /* ------------------------------------------------------------------ */

    public static function init() {
        if ( ! is_admin() ) {
            return;
        }
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
        add_filter( 'plugins_api',                           array( $this, 'plugin_info'      ), 20, 3 );
        add_filter( 'upgrader_source_selection',             array( $this, 'fix_source_dir'   ), 10, 4 );
        add_filter( 'http_request_args',                     array( $this, 'maybe_inject_auth'), 10, 2 );
    }

    /* ------------------------------------------------------------------ */
    /* Token                                                                */
    /* ------------------------------------------------------------------ */

    private function get_token() {
        $token = '';
        if ( defined( 'MLMD_GITHUB_TOKEN_ML_CAROUSEL_PRO' ) ) {
            $token = MLMD_GITHUB_TOKEN_ML_CAROUSEL_PRO;
        }
        $token = apply_filters( 'mlmd_github_token_ml_carousel_pro', $token );
        return is_string( $token ) ? trim( $token ) : '';
    }

    private function has_token() {
        return $this->get_token() !== '';
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
        $args = array(
            'timeout'    => 15,
            'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ),
            'headers'    => array(
                'Accept' => 'application/vnd.github.v3+json',
            ),
        );
        $token = $this->get_token();
        if ( $token !== '' ) {
            $args['headers']['Authorization'] = 'Bearer ' . $token;
        }
        return $args;
    }

    public function fetch_latest_release( $force = false ) {
        if ( ! $force ) {
            $cached = get_transient( self::TRANSIENT_KEY );
            if ( $cached !== false ) {
                return is_array( $cached ) ? $cached : null;
            }
        }

        $response = wp_remote_get( $this->api_url(), $this->request_args() );

        if ( is_wp_error( $response ) ) {
            set_transient( self::TRANSIENT_KEY, 'error', self::CACHE_FAIL_TTL );
            return null;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            set_transient( self::TRANSIENT_KEY, 'error', self::CACHE_FAIL_TTL );
            return null;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $body ) || empty( $body['tag_name'] ) ) {
            set_transient( self::TRANSIENT_KEY, 'error', self::CACHE_FAIL_TTL );
            return null;
        }

        set_transient( self::TRANSIENT_KEY, $body, self::CACHE_TTL );
        return $body;
    }

    private function release_version( $release ) {
        if ( ! is_array( $release ) || empty( $release['tag_name'] ) ) {
            return '';
        }
        return ltrim( $release['tag_name'], 'v' );
    }

    private function release_zip_url( $release ) {
        if ( ! is_array( $release ) ) {
            return '';
        }

        // 1. Prefer named asset ml-carousel-pro-*.zip (attached by GitHub Actions)
        if ( ! empty( $release['assets'] ) && is_array( $release['assets'] ) ) {
            foreach ( $release['assets'] as $asset ) {
                if (
                    ! empty( $asset['name'] ) &&
                    ! empty( $asset['browser_download_url'] ) &&
                    substr( $asset['name'], -4 ) === '.zip' &&
                    strpos( $asset['name'], 'ml-carousel-pro' ) !== false
                ) {
                    // For private repos: use API download URL with auth instead of browser URL
                    if ( $this->has_token() && ! empty( $asset['url'] ) ) {
                        return $asset['url']; // API URL — we inject auth header in maybe_inject_auth
                    }
                    return $asset['browser_download_url'];
                }
            }
            // 2. Any ZIP asset
            foreach ( $release['assets'] as $asset ) {
                if ( ! empty( $asset['browser_download_url'] ) && substr( $asset['name'] ?? '', -4 ) === '.zip' ) {
                    if ( $this->has_token() && ! empty( $asset['url'] ) ) {
                        return $asset['url'];
                    }
                    return $asset['browser_download_url'];
                }
            }
        }

        // 3. Fallback: GitHub source zipball
        return ! empty( $release['zipball_url'] ) ? $release['zipball_url'] : '';
    }

    /* ------------------------------------------------------------------ */
    /* WordPress update hooks                                               */
    /* ------------------------------------------------------------------ */

    public function check_for_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $release = $this->fetch_latest_release();
        if ( ! $release ) {
            return $transient;
        }

        $remote_version  = $this->release_version( $release );
        $current_version = MLCP_VERSION;

        if ( $remote_version === '' ) {
            return $transient;
        }

        if ( version_compare( $remote_version, $current_version, '>' ) ) {
            $zip_url = $this->release_zip_url( $release );

            $obj               = new stdClass();
            $obj->id           = self::GITHUB_USER . '/' . self::GITHUB_REPO;
            $obj->slug         = self::PLUGIN_SLUG;
            $obj->plugin       = self::PLUGIN_FILE;
            $obj->new_version  = $remote_version;
            $obj->url          = 'https://github.com/' . self::GITHUB_USER . '/' . self::GITHUB_REPO;
            $obj->package      = $zip_url;
            $obj->icons        = array();
            $obj->banners      = array();
            $obj->banners_rtl  = array();
            $obj->requires     = '6.0';
            $obj->requires_php = '7.4';
            $obj->tested       = '6.7';
            $obj->upgrade_notice = '';

            $transient->response[ self::PLUGIN_FILE ] = $obj;
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
        $info->tested        = '6.7';
        $info->download_link = $zip_url;
        $info->trunk         = $zip_url;
        $info->last_updated  = $release['published_at'] ?? '';
        $info->sections      = array(
            'description' => 'ML Banner Pro — carrossel profissional com analytics, grupos e shortcodes para WordPress.',
            'changelog'   => ! empty( $release['body'] ) ? wp_kses_post( $release['body'] ) : 'Ver ' . esc_url( 'https://github.com/' . self::GITHUB_USER . '/' . self::GITHUB_REPO . '/releases' ),
        );

        return $info;
    }

    /**
     * Fix extracted folder name so WP installs over ml-carousel-pro/
     * and not a random "mlopesdesign-ml-carousel-pro-abc123/" folder.
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
            return $source; // Already correct (our ZIP has the right root)
        }

        if ( $wp_filesystem->exists( $source ) && $wp_filesystem->move( $source, $intended ) ) {
            return $intended;
        }

        return $source;
    }

    /**
     * Inject Authorization header for private repo asset downloads.
     * GitHub API asset URLs require auth; browser_download_url redirects to S3.
     */
    public function maybe_inject_auth( $args, $url ) {
        if ( strpos( $url, 'api.github.com/repos/' . self::GITHUB_USER . '/' . self::GITHUB_REPO ) !== false ) {
            $token = $this->get_token();
            if ( $token !== '' ) {
                $args['headers']['Authorization'] = 'Bearer ' . $token;
                $args['headers']['Accept']        = 'application/octet-stream';
            }
        }
        return $args;
    }

    /* ------------------------------------------------------------------ */
    /* Admin diagnostics                                                    */
    /* ------------------------------------------------------------------ */

    public static function get_diagnostics() {
        $self    = self::init();
        // Force fresh fetch for diagnostics page (bypass cache)
        $release = $self ? $self->fetch_latest_release( true ) : null;

        $remote_version = $self ? $self->release_version( $release ) : '—';
        $zip_url        = ( $self && $release ) ? $self->release_zip_url( $release ) : '';

        return array(
            'repo'             => 'https://github.com/' . self::GITHUB_USER . '/' . self::GITHUB_REPO,
            'current_version'  => MLCP_VERSION,
            'remote_version'   => $remote_version !== '' ? $remote_version : '—',
            'update_available' => ( $remote_version !== '' && version_compare( $remote_version, MLCP_VERSION, '>' ) ) ? 'Sim' : 'Não',
            'source_status'    => $release ? 'GitHub Release detectado ✓' : 'Sem resposta da API — verifique token e conectividade',
            'token_set'        => ( $self && $self->has_token() ) ? 'Sim' : 'Não',
            'zip_detected'     => $zip_url !== '' ? 'Sim' : 'Não',
            'zip_url'          => $zip_url !== '' ? 'asset detectado' : '—',
        );
    }
}
