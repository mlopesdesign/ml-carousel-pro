<?php
if (!defined('ABSPATH')) {
    exit;
}

class MLCP_Install {
    public static function activate() {
        MLCP_Post_Types::register();
        MLCP_Taxonomies::register();
        flush_rewrite_rules();
    }
}
