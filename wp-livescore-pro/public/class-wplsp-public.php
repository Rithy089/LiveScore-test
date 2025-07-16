<?php
namespace WPLivescorePro;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPLSP_Public {
    public function __construct() {
        add_shortcode( 'wp_livescore', [ $this, 'render_scoreboard' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    public function enqueue_assets() {
        wp_enqueue_style( 'wplsp-public', WPLSP_PLUGIN_URL . 'assets/css/public.css', [], WPLSP_VERSION );
        wp_enqueue_script( 'wplsp-public', WPLSP_PLUGIN_URL . 'assets/js/public.js', [ 'jquery' ], WPLSP_VERSION, true );
        wp_localize_script( 'wplsp-public', 'wplsp_ajax', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'wplsp_nonce' ),
        ] );
    }

    public function render_scoreboard( $atts ) {
        ob_start();
        ?>
        <div id="wplsp-scoreboard">
            <!-- Scoreboard will be rendered here by JS -->
            <div class="wplsp-loading-spinner"></div>
        </div>
        <?php
        return ob_get_clean();
    }
}