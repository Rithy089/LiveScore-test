/**
 * WordPress LiveScore Pro - JavaScript
 */

(function($) {
    'use strict';
    
    // Plugin namespace
    window.WPLiveScorePro = {
        
        // Configuration
        config: {
            refreshInterval: 300000, // 5 minutes
            countdownUpdateInterval: 1000, // 1 second
            fadeSpeed: 300,
            maxRetries: 3
        },
        
        // Active widgets
        widgets: {},
        
        // Initialize the plugin
        init: function() {
            this.bindEvents();
            this.initializeWidgets();
            this.handleVisibilityChange();
        },
        
        // Bind global events
        bindEvents: function() {
            var self = this;
            
            // Handle manual refresh buttons
            $(document).on('click', '.livescore-refresh-btn', function(e) {
                e.preventDefault();
                var container = $(this).closest('.wp-livescore-container');
                self.refreshWidget(container.attr('id'));
            });
            
            // Handle window resize for responsive updates
            $(window).on('resize', this.debounce(function() {
                self.handleResize();
            }, 250));
            
            // Handle page visibility change (pause/resume when tab is not active)
            $(document).on('visibilitychange', function() {
                self.handleVisibilityChange();
            });
        },
        
        // Initialize all livescore widgets on the page
        initializeWidgets: function() {
            var self = this;
            
            $('.wp-livescore-container').each(function() {
                var $container = $(this);
                var widgetId = $container.attr('id');
                
                if (widgetId && !self.widgets[widgetId]) {
                    self.createWidget(widgetId, $container);
                }
            });
        },
        
        // Create a new widget instance
        createWidget: function(widgetId, $container) {
            var self = this;
            var autoRefresh = $container.data('auto-refresh') === true;
            
            this.widgets[widgetId] = {
                container: $container,
                autoRefresh: autoRefresh,
                timeLeft: 300, // 5 minutes
                countdownTimer: null,
                refreshTimer: null,
                retryCount: 0,
                isVisible: true,
                league: $container.data('league') || ''
            };
            
            if (autoRefresh) {
                this.startAutoRefresh(widgetId);
            }
            
            // Add loading state management
            this.setupLoadingStates($container);
            
            // Initialize accessibility features
            this.setupAccessibility($container);
        },
        
        // Start auto-refresh for a widget
        startAutoRefresh: function(widgetId) {
            var self = this;
            var widget = this.widgets[widgetId];
            
            if (!widget || !widget.autoRefresh) return;
            
            // Clear existing timers
            this.stopAutoRefresh(widgetId);
            
            // Start countdown timer
            widget.countdownTimer = setInterval(function() {
                self.updateCountdown(widgetId);
            }, this.config.countdownUpdateInterval);
            
            // Initial countdown update
            this.updateCountdown(widgetId);
        },
        
        // Stop auto-refresh for a widget
        stopAutoRefresh: function(widgetId) {
            var widget = this.widgets[widgetId];
            
            if (!widget) return;
            
            if (widget.countdownTimer) {
                clearInterval(widget.countdownTimer);
                widget.countdownTimer = null;
            }
            
            if (widget.refreshTimer) {
                clearTimeout(widget.refreshTimer);
                widget.refreshTimer = null;
            }
        },
        
        // Update countdown display and trigger refresh when needed
        updateCountdown: function(widgetId) {
            var self = this;
            var widget = this.widgets[widgetId];
            
            if (!widget || !widget.isVisible) return;
            
            var minutes = Math.floor(widget.timeLeft / 60);
            var seconds = widget.timeLeft % 60;
            var display = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
            
            widget.container.find('.next-update-countdown').text(display);
            
            if (widget.timeLeft <= 0) {
                this.refreshWidget(widgetId);
                widget.timeLeft = 300; // Reset to 5 minutes
            } else {
                widget.timeLeft--;
            }
        },
        
        // Refresh widget data
        refreshWidget: function(widgetId) {
            var self = this;
            var widget = this.widgets[widgetId];
            
            if (!widget) return;
            
            var $container = widget.container;
            var $indicator = $container.find('.refresh-indicator');
            
            // Show loading state
            $container.addClass('loading');
            $indicator.show();
            
            // Disable refresh button temporarily
            $container.find('.livescore-refresh-btn').prop('disabled', true);
            
            $.ajax({
                url: wp_livescore_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_live_scores',
                    nonce: wp_livescore_ajax.nonce,
                    league: widget.league
                },
                timeout: 30000
            })
            .done(function(response) {
                if (response.success) {
                    self.updateMatchesDisplay(widgetId, response.data);
                    self.updateLastRefreshTime($container);
                    widget.retryCount = 0; // Reset retry count on success
                } else {
                    self.handleRefreshError(widgetId, response.data || 'Unknown error');
                }
            })
            .fail(function(xhr, status, error) {
                self.handleRefreshError(widgetId, error || 'Network error');
            })
            .always(function() {
                // Hide loading state
                $container.removeClass('loading');
                $indicator.hide();
                $container.find('.livescore-refresh-btn').prop('disabled', false);
            });
        },
        
        // Update matches display with new data
        updateMatchesDisplay: function(widgetId, matches) {
            var widget = this.widgets[widgetId];
            if (!widget) return;
            
            var $container = widget.container;
            var $matchesContainer = $container.find('.livescore-matches');
            
            if (!matches || matches.length === 0) {
                $matchesContainer.html('<div class="no-matches"><p>No matches available at the moment.</p></div>');
                return;
            }
            
            var html = '';
            matches.forEach(function(match) {
                html += this.buildMatchHtml(match);
            }.bind(this));
            
            // Smooth update with fade effect
            $matchesContainer.fadeOut(this.config.fadeSpeed / 2, function() {
                $(this).html(html).fadeIn(self.config.fadeSpeed / 2);
            });
        },
        
        // Build HTML for a single match
        buildMatchHtml: function(match) {
            var statusClass = 'status-' + match.status;
            var statusText = this.getStatusText(match.status);
            var matchTime = this.formatMatchTime(match);
            
            return `
                <div class="match-card ${statusClass}">
                    <div class="match-header">
                        <span class="league-name">${this.escapeHtml(match.league)}</span>
                        <span class="match-status ${statusClass}">${statusText}</span>
                    </div>
                    
                    <div class="match-teams">
                        <div class="team home-team">
                            <span class="team-name">${this.escapeHtml(match.home_team)}</span>
                            <span class="team-score">${match.home_score}</span>
                        </div>
                        
                        <div class="match-separator">
                            <span class="vs">VS</span>
                        </div>
                        
                        <div class="team away-team">
                            <span class="team-score">${match.away_score}</span>
                            <span class="team-name">${this.escapeHtml(match.away_team)}</span>
                        </div>
                    </div>
                    
                    <div class="match-time">${matchTime}</div>
                </div>
            `;
        },
        
        // Get human-readable status text
        getStatusText: function(status) {
            var statusMap = {
                'live': 'LIVE',
                'finished': 'FT',
                'scheduled': 'Scheduled',
                'postponed': 'Postponed',
                'cancelled': 'Cancelled'
            };
            
            return statusMap[status] || status.toUpperCase();
        },
        
        // Format match time for display
        formatMatchTime: function(match) {
            if (match.status === 'scheduled') {
                return new Date(match.match_time).toLocaleDateString() + ' ' + 
                       new Date(match.match_time).toLocaleTimeString();
            } else {
                var updatedTime = new Date(match.updated_at);
                var now = new Date();
                var diffMinutes = Math.floor((now - updatedTime) / 60000);
                
                if (diffMinutes < 1) {
                    return 'Updated: Just now';
                } else if (diffMinutes < 60) {
                    return `Updated: ${diffMinutes} minute${diffMinutes === 1 ? '' : 's'} ago`;
                } else {
                    var diffHours = Math.floor(diffMinutes / 60);
                    return `Updated: ${diffHours} hour${diffHours === 1 ? '' : 's'} ago`;
                }
            }
        },
        
        // Handle refresh errors
        handleRefreshError: function(widgetId, error) {
            var widget = this.widgets[widgetId];
            if (!widget) return;
            
            widget.retryCount++;
            
            console.warn('LiveScore refresh failed:', error);
            
            // Show user-friendly error message
            var $container = widget.container;
            var $errorDiv = $container.find('.refresh-error');
            
            if ($errorDiv.length === 0) {
                $errorDiv = $('<div class="refresh-error" style="background: #ffe6e6; color: #d63031; padding: 10px; margin: 10px 20px; border-radius: 4px; font-size: 12px;"></div>');
                $container.find('.livescore-matches').before($errorDiv);
            }
            
            if (widget.retryCount < this.config.maxRetries) {
                $errorDiv.html(`Failed to update scores. Retrying in 30 seconds... (${widget.retryCount}/${this.config.maxRetries})`);
                
                // Retry after 30 seconds
                setTimeout(function() {
                    this.refreshWidget(widgetId);
                }.bind(this), 30000);
            } else {
                $errorDiv.html('Failed to update scores. Please check your internet connection and try refreshing manually.');
            }
            
            // Hide error message after 10 seconds
            setTimeout(function() {
                $errorDiv.fadeOut();
            }, 10000);
        },
        
        // Update last refresh time
        updateLastRefreshTime: function($container) {
            var now = new Date();
            var timeString = now.toLocaleTimeString();
            $container.find('.last-updated').text(timeString);
            
            // Remove any error messages
            $container.find('.refresh-error').fadeOut();
        },
        
        // Handle page visibility changes
        handleVisibilityChange: function() {
            var isVisible = !document.hidden;
            
            Object.keys(this.widgets).forEach(function(widgetId) {
                var widget = this.widgets[widgetId];
                widget.isVisible = isVisible;
                
                if (isVisible && widget.autoRefresh) {
                    this.startAutoRefresh(widgetId);
                } else if (!isVisible) {
                    this.stopAutoRefresh(widgetId);
                }
            }.bind(this));
        },
        
        // Handle window resize
        handleResize: function() {
            // Update responsive layouts if needed
            $('.wp-livescore-container').each(function() {
                var $container = $(this);
                // Add any responsive adjustments here
            });
        },
        
        // Setup loading states
        setupLoadingStates: function($container) {
            // Add loading indicators and states
            $container.find('.livescore-refresh-btn').attr('data-loading-text', 'Refreshing...');
        },
        
        // Setup accessibility features
        setupAccessibility: function($container) {
            // Add ARIA labels and keyboard navigation
            $container.find('.livescore-refresh-btn').attr('aria-label', 'Refresh live scores');
            $container.attr('role', 'region').attr('aria-label', 'Live sports scores');
        },
        
        // Utility: Escape HTML
        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
        
        // Utility: Debounce function
        debounce: function(func, wait) {
            var timeout;
            return function executedFunction() {
                var later = function() {
                    clearTimeout(timeout);
                    func.apply(this, arguments);
                }.bind(this);
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        WPLiveScorePro.init();
    });
    
    // Re-initialize widgets when new content is loaded (for AJAX page loads)
    $(document).on('wp-livescore-reinit', function() {
        WPLiveScorePro.initializeWidgets();
    });
    
})(jQuery);