<?php
/**
 * Plugin Name: WP LiveScore Pro
 * Plugin URI: https://example.com/wp-livescore-pro
 * Description: A WordPress plugin for displaying live sports scores with data crawling capabilities.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: wp-livescore-pro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WP_LIVESCORE_PRO_VERSION', '1.0.0');
define('WP_LIVESCORE_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_LIVESCORE_PRO_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Main plugin class
class WP_LiveScore_Pro {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_get_live_scores', array($this, 'ajax_get_live_scores'));
        add_action('wp_ajax_nopriv_get_live_scores', array($this, 'ajax_get_live_scores'));
        add_action('wp_ajax_wp_livescore_manual_update', array($this, 'ajax_manual_update'));
        
        // Add shortcode
        add_shortcode('livescore', array($this, 'livescore_shortcode'));
        
        // Schedule cron job for data updates
        if (!wp_next_scheduled('wp_livescore_update_scores')) {
            wp_schedule_event(time(), 'every_five_minutes', 'wp_livescore_update_scores');
        }
        add_action('wp_livescore_update_scores', array($this, 'update_scores_cron'));
    }
    
    public function init() {
        $this->create_database_table();
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'wp-livescore-pro-js',
            WP_LIVESCORE_PRO_PLUGIN_URL . 'assets/livescore.js',
            array('jquery'),
            WP_LIVESCORE_PRO_VERSION,
            true
        );
        wp_enqueue_style(
            'wp-livescore-pro-css',
            WP_LIVESCORE_PRO_PLUGIN_URL . 'assets/livescore.css',
            array(),
            WP_LIVESCORE_PRO_VERSION
        );
        
        // Localize script for AJAX
        wp_localize_script('wp-livescore-pro-js', 'wp_livescore_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_livescore_nonce')
        ));
    }
    
    public function admin_menu() {
        add_options_page(
            'LiveScore Pro Settings',
            'LiveScore Pro',
            'manage_options',
            'wp-livescore-pro',
            array($this, 'admin_page')
        );
    }
    
    public function admin_page() {
        include WP_LIVESCORE_PRO_PLUGIN_PATH . 'admin/admin-page.php';
    }
    
    public function create_database_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'livescores';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            match_id varchar(100) NOT NULL,
            home_team varchar(100) NOT NULL,
            away_team varchar(100) NOT NULL,
            home_score int(3) DEFAULT 0,
            away_score int(3) DEFAULT 0,
            status varchar(50) DEFAULT 'scheduled',
            match_time datetime DEFAULT CURRENT_TIMESTAMP,
            league varchar(100),
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY match_id (match_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function livescore_shortcode($atts) {
        $atts = shortcode_atts(array(
            'league' => '',
            'limit' => 10,
            'auto_refresh' => 'true'
        ), $atts);
        
        ob_start();
        include WP_LIVESCORE_PRO_PLUGIN_PATH . 'templates/livescore-display.php';
        return ob_get_clean();
    }
    
    public function ajax_get_live_scores() {
        check_ajax_referer('wp_livescore_nonce', 'nonce');
        
        $league = sanitize_text_field($_POST['league'] ?? '');
        $scores = $this->get_live_scores($league);
        
        wp_send_json_success($scores);
    }
    
    public function ajax_manual_update() {
        check_ajax_referer('wp_livescore_manual_update', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $result = $this->crawl_live_scores();
        
        if ($result) {
            wp_send_json_success('Scores updated successfully');
        } else {
            wp_send_json_error('Failed to update scores. Please check your API configuration.');
        }
    }
    
    public function get_live_scores($league = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'livescores';
        $where_clause = "WHERE status IN ('live', 'finished', 'scheduled')";
        
        if (!empty($league)) {
            $where_clause .= $wpdb->prepare(" AND league = %s", $league);
        }
        
        $sql = "SELECT * FROM $table_name $where_clause ORDER BY match_time DESC LIMIT 20";
        
        return $wpdb->get_results($sql, ARRAY_A);
    }
    
    public function update_scores_cron() {
        $this->crawl_live_scores();
    }
    
    public function crawl_live_scores() {
        try {
            // Using Football-Data.org API as an example (free tier available)
            $api_key = get_option('wp_livescore_api_key', '');
            
            if (empty($api_key)) {
                error_log('WP LiveScore Pro: API key not configured');
                return false;
            }
            
            $response = wp_remote_get('https://api.football-data.org/v4/matches', array(
                'headers' => array(
                    'X-Auth-Token' => $api_key
                ),
                'timeout' => 30
            ));
            
            if (is_wp_error($response)) {
                error_log('WP LiveScore Pro: API request failed - ' . $response->get_error_message());
                return false;
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (!$data || !isset($data['matches'])) {
                error_log('WP LiveScore Pro: Invalid API response');
                return false;
            }
            
            $this->save_scores_to_database($data['matches']);
            
            return true;
            
        } catch (Exception $e) {
            error_log('WP LiveScore Pro: Exception in crawl_live_scores - ' . $e->getMessage());
            return false;
        }
    }
    
    private function save_scores_to_database($matches) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'livescores';
        
        foreach ($matches as $match) {
            try {
                $match_data = array(
                    'match_id' => sanitize_text_field($match['id']),
                    'home_team' => sanitize_text_field($match['homeTeam']['name']),
                    'away_team' => sanitize_text_field($match['awayTeam']['name']),
                    'home_score' => intval($match['score']['fullTime']['home'] ?? 0),
                    'away_score' => intval($match['score']['fullTime']['away'] ?? 0),
                    'status' => sanitize_text_field(strtolower($match['status'])),
                    'match_time' => sanitize_text_field($match['utcDate']),
                    'league' => sanitize_text_field($match['competition']['name'] ?? '')
                );
                
                // Insert or update
                $existing = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM $table_name WHERE match_id = %s",
                    $match_data['match_id']
                ));
                
                if ($existing) {
                    $wpdb->update(
                        $table_name,
                        $match_data,
                        array('match_id' => $match_data['match_id'])
                    );
                } else {
                    $wpdb->insert($table_name, $match_data);
                }
                
            } catch (Exception $e) {
                error_log('WP LiveScore Pro: Error saving match data - ' . $e->getMessage());
            }
        }
    }
}

// Custom cron schedule
add_filter('cron_schedules', function($schedules) {
    $schedules['every_five_minutes'] = array(
        'interval' => 300,
        'display' => 'Every 5 Minutes'
    );
    return $schedules;
});

// Initialize the plugin
new WP_LiveScore_Pro();

// Activation hook
register_activation_hook(__FILE__, function() {
    $plugin = new WP_LiveScore_Pro();
    $plugin->create_database_table();
    
    // Schedule the cron job
    if (!wp_next_scheduled('wp_livescore_update_scores')) {
        wp_schedule_event(time(), 'every_five_minutes', 'wp_livescore_update_scores');
    }
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('wp_livescore_update_scores');
});