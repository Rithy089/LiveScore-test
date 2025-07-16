<?php
namespace WPLivescorePro;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPLSP_Admin {
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    public function add_menu() {
        add_menu_page(
            __( 'WP Livescore Pro', 'wp-livescore-pro' ),
            __( 'WP Livescore Pro', 'wp-livescore-pro' ),
            'manage_options',
            'wplsp-settings',
            [ $this, 'settings_page' ],
            'dashicons-chart-bar',
            56
        );
    }

    public function register_settings() {
        // Register settings here
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'WP Livescore Pro Settings', 'wp-livescore-pro' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'wplsp_settings_group' );
                do_settings_sections( 'wplsp-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}