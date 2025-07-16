<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get the plugin instance
global $wpdb;
$table_name = $wpdb->prefix . 'livescores';

// Get parameters from shortcode
$league = isset($atts['league']) ? sanitize_text_field($atts['league']) : '';
$limit = isset($atts['limit']) ? intval($atts['limit']) : 10;
$auto_refresh = isset($atts['auto_refresh']) ? $atts['auto_refresh'] === 'true' : true;

// Build query
$where_clause = "WHERE status IN ('live', 'finished', 'scheduled')";
if (!empty($league)) {
    $where_clause .= $wpdb->prepare(" AND league = %s", $league);
}

$sql = "SELECT * FROM $table_name $where_clause ORDER BY 
        CASE 
            WHEN status = 'live' THEN 1
            WHEN status = 'scheduled' THEN 2
            WHEN status = 'finished' THEN 3
            ELSE 4
        END,
        match_time DESC 
        LIMIT %d";

$matches = $wpdb->get_results($wpdb->prepare($sql, $limit), ARRAY_A);

// Generate unique ID for this widget instance
$widget_id = 'livescore-' . uniqid();
?>

<div id="<?php echo $widget_id; ?>" class="wp-livescore-container" data-auto-refresh="<?php echo $auto_refresh ? 'true' : 'false'; ?>" data-league="<?php echo esc_attr($league); ?>">
    <div class="livescore-header">
        <h3>
            <?php echo !empty($league) ? esc_html($league) . ' - ' : ''; ?>Live Scores
            <span class="refresh-indicator" style="display: none;">🔄</span>
        </h3>
        <div class="livescore-controls">
            <button class="livescore-refresh-btn" type="button">Refresh</button>
            <?php if ($auto_refresh): ?>
                <span class="auto-refresh-status">Auto-refresh: ON</span>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="livescore-matches">
        <?php if (empty($matches)): ?>
            <div class="no-matches">
                <p>No matches available at the moment.</p>
                <?php if (empty(get_option('wp_livescore_api_key'))): ?>
                    <p><em>Please configure your API key in the plugin settings.</em></p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($matches as $match): ?>
                <div class="match-card status-<?php echo esc_attr($match['status']); ?>">
                    <div class="match-header">
                        <span class="league-name"><?php echo esc_html($match['league']); ?></span>
                        <span class="match-status status-<?php echo esc_attr($match['status']); ?>">
                            <?php 
                            switch ($match['status']) {
                                case 'live':
                                    echo 'LIVE';
                                    break;
                                case 'finished':
                                    echo 'FT';
                                    break;
                                case 'scheduled':
                                    echo 'Scheduled';
                                    break;
                                default:
                                    echo ucfirst($match['status']);
                            }
                            ?>
                        </span>
                    </div>
                    
                    <div class="match-teams">
                        <div class="team home-team">
                            <span class="team-name"><?php echo esc_html($match['home_team']); ?></span>
                            <span class="team-score"><?php echo esc_html($match['home_score']); ?></span>
                        </div>
                        
                        <div class="match-separator">
                            <span class="vs">VS</span>
                        </div>
                        
                        <div class="team away-team">
                            <span class="team-score"><?php echo esc_html($match['away_score']); ?></span>
                            <span class="team-name"><?php echo esc_html($match['away_team']); ?></span>
                        </div>
                    </div>
                    
                    <div class="match-time">
                        <?php 
                        $match_time = new DateTime($match['match_time']);
                        if ($match['status'] === 'scheduled') {
                            echo $match_time->format('M j, Y g:i A');
                        } else {
                            echo 'Updated: ' . human_time_diff(strtotime($match['updated_at']), current_time('timestamp')) . ' ago';
                        }
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="livescore-footer">
        <small>
            Last updated: <span class="last-updated"><?php echo current_time('g:i A'); ?></span>
            <?php if ($auto_refresh): ?>
                | Next update in: <span class="next-update-countdown">5:00</span>
            <?php endif; ?>
        </small>
    </div>
</div>

<?php if ($auto_refresh): ?>
<script>
jQuery(document).ready(function($) {
    var container = $('#<?php echo $widget_id; ?>');
    var refreshInterval = 300000; // 5 minutes
    var countdownInterval;
    var timeLeft = 300; // 5 minutes in seconds
    
    function updateCountdown() {
        var minutes = Math.floor(timeLeft / 60);
        var seconds = timeLeft % 60;
        container.find('.next-update-countdown').text(
            minutes + ':' + (seconds < 10 ? '0' : '') + seconds
        );
        
        if (timeLeft <= 0) {
            refreshScores();
            timeLeft = 300;
        } else {
            timeLeft--;
        }
    }
    
    function refreshScores() {
        var indicator = container.find('.refresh-indicator');
        indicator.show();
        
        $.post(wp_livescore_ajax.ajax_url, {
            action: 'get_live_scores',
            nonce: wp_livescore_ajax.nonce,
            league: container.data('league')
        }, function(response) {
            if (response.success) {
                // Update the matches display
                updateMatchesDisplay(response.data);
                container.find('.last-updated').text(new Date().toLocaleTimeString());
            }
        }).always(function() {
            indicator.hide();
        });
    }
    
    function updateMatchesDisplay(matches) {
        // This is a simplified update - in production you might want more sophisticated DOM updates
        location.reload();
    }
    
    // Manual refresh button
    container.find('.livescore-refresh-btn').click(function() {
        refreshScores();
        timeLeft = 300; // Reset countdown
    });
    
    // Start countdown
    if (container.data('auto-refresh') === true) {
        countdownInterval = setInterval(updateCountdown, 1000);
        updateCountdown(); // Initial call
    }
});
</script>
<?php endif; ?>