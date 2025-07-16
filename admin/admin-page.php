<?php
if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['submit'])) {
    if (wp_verify_nonce($_POST['wp_livescore_nonce'], 'wp_livescore_settings')) {
        update_option('wp_livescore_api_key', sanitize_text_field($_POST['api_key']));
        update_option('wp_livescore_refresh_interval', intval($_POST['refresh_interval']));
        update_option('wp_livescore_max_matches', intval($_POST['max_matches']));
        
        echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
    }
}

// Get current settings
$api_key = get_option('wp_livescore_api_key', '');
$refresh_interval = get_option('wp_livescore_refresh_interval', 5);
$max_matches = get_option('wp_livescore_max_matches', 20);

// Test API connection
$api_status = 'Not tested';
if (!empty($api_key)) {
    $test_response = wp_remote_get('https://api.football-data.org/v4/competitions', array(
        'headers' => array('X-Auth-Token' => $api_key),
        'timeout' => 10
    ));
    
    if (!is_wp_error($test_response) && wp_remote_retrieve_response_code($test_response) === 200) {
        $api_status = '<span style="color: green;">✓ Connected</span>';
    } else {
        $api_status = '<span style="color: red;">✗ Failed</span>';
    }
}
?>

<div class="wrap">
    <h1>LiveScore Pro Settings</h1>
    
    <div class="card" style="max-width: 800px;">
        <h2>Configuration</h2>
        
        <form method="post" action="">
            <?php wp_nonce_field('wp_livescore_settings', 'wp_livescore_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="api_key">Football-Data.org API Key</label>
                    </th>
                    <td>
                        <input type="text" id="api_key" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
                        <p class="description">
                            Get your free API key from <a href="https://www.football-data.org/client/register" target="_blank">Football-Data.org</a>
                        </p>
                        <p><strong>API Status:</strong> <?php echo $api_status; ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="refresh_interval">Refresh Interval (minutes)</label>
                    </th>
                    <td>
                        <input type="number" id="refresh_interval" name="refresh_interval" value="<?php echo esc_attr($refresh_interval); ?>" min="1" max="60" />
                        <p class="description">How often to update scores from the API (minimum 1 minute)</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="max_matches">Maximum Matches to Display</label>
                    </th>
                    <td>
                        <input type="number" id="max_matches" name="max_matches" value="<?php echo esc_attr($max_matches); ?>" min="1" max="100" />
                        <p class="description">Maximum number of matches to show in the widget</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
    </div>
    
    <div class="card" style="max-width: 800px; margin-top: 20px;">
        <h2>Usage Instructions</h2>
        <p>Use the following shortcode to display live scores on your posts or pages:</p>
        <code>[livescore]</code>
        
        <h3>Shortcode Parameters:</h3>
        <ul>
            <li><strong>league</strong>: Filter by specific league (optional)</li>
            <li><strong>limit</strong>: Number of matches to display (default: 10)</li>
            <li><strong>auto_refresh</strong>: Enable auto-refresh (default: true)</li>
        </ul>
        
        <h3>Examples:</h3>
        <code>[livescore league="Premier League" limit="5"]</code><br>
        <code>[livescore auto_refresh="false"]</code>
    </div>
    
    <div class="card" style="max-width: 800px; margin-top: 20px;">
        <h2>Manual Data Update</h2>
        <p>Click the button below to manually fetch the latest scores:</p>
        <button type="button" id="manual-update" class="button button-secondary">Update Scores Now</button>
        <div id="update-result" style="margin-top: 10px;"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#manual-update').click(function() {
        var button = $(this);
        var result = $('#update-result');
        
        button.prop('disabled', true).text('Updating...');
        result.html('');
        
        $.post(ajaxurl, {
            action: 'wp_livescore_manual_update',
            nonce: '<?php echo wp_create_nonce('wp_livescore_manual_update'); ?>'
        }, function(response) {
            if (response.success) {
                result.html('<div class="notice notice-success inline"><p>Scores updated successfully!</p></div>');
            } else {
                result.html('<div class="notice notice-error inline"><p>Update failed: ' + response.data + '</p></div>');
            }
        }).always(function() {
            button.prop('disabled', false).text('Update Scores Now');
        });
    });
});
</script>