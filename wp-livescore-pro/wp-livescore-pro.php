<?php
/**
 * Plugin Name: WP Livescore Pro
 * Description: Displays live football scores with data aggregation from external sources.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: wp-livescore-pro
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Autoload or require main class
require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-livescore-pro.php';

function wp_livescore_pro_init() {
    return WP_Livescore_Pro::get_instance();
}

wp_livescore_pro_init();