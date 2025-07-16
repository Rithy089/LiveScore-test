<?php
namespace WPLivescorePro;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_Livescore_Pro {
    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }

    private function define_constants() {
        define( 'WPLSP_PLUGIN_PATH', plugin_dir_path( dirname( __FILE__ ) ) );
        define( 'WPLSP_PLUGIN_URL', plugin_dir_url( dirname( __FILE__ ) ) );
        define( 'WPLSP_VERSION', '1.0.0' );
    }

    private function includes() {
        require_once WPLSP_PLUGIN_PATH . 'public/class-wplsp-public.php';
        require_once WPLSP_PLUGIN_PATH . 'admin/class-wplsp-admin.php';
    }

    private function init_hooks() {
        register_activation_hook( WPLSP_PLUGIN_PATH . 'wp-livescore-pro.php', [ $this, 'activate' ] );
        register_deactivation_hook( WPLSP_PLUGIN_PATH . 'wp-livescore-pro.php', [ $this, 'deactivate' ] );
        add_action( 'init', [ $this, 'load_textdomain' ] );
        if ( is_admin() ) {
            new WPLSP_Admin();
        } else {
            new WPLSP_Public();
        }
    }

    public function activate() {
        // Activation logic
    }

    public function deactivate() {
        // Deactivation logic
    }

    public function load_textdomain() {
        load_plugin_textdomain( 'wp-livescore-pro', false, dirname( plugin_basename( __FILE__ ) ) . '/../languages/' );
    }
}