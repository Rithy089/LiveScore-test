# WordPress LiveScore Pro

A comprehensive WordPress plugin for displaying live sports scores with automated data crawling capabilities.

## Features

- **Live Sports Scores**: Real-time display of football/soccer match scores
- **Automated Data Crawling**: Fetches live scores every 5 minutes using Football-Data.org API
- **Responsive Design**: Modern, mobile-friendly interface
- **Multiple Display Options**: Shortcode support with customizable parameters
- **Admin Dashboard**: Easy configuration and monitoring
- **Error Handling**: Robust error handling with retry mechanisms
- **Auto-refresh**: Real-time updates without page reload
- **League Filtering**: Display scores for specific leagues

## Installation

1. Upload the plugin files to `/wp-content/plugins/wp-livescore-pro/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > LiveScore Pro to configure your API key

## Configuration

### API Setup

1. Register for a free API key at [Football-Data.org](https://www.football-data.org/client/register)
2. Navigate to Settings > LiveScore Pro in your WordPress admin
3. Enter your API key and save settings
4. The plugin will automatically test the connection

### Settings

- **API Key**: Your Football-Data.org API key
- **Refresh Interval**: How often to update scores (1-60 minutes)
- **Maximum Matches**: Number of matches to display (1-100)

## Usage

### Basic Shortcode

Display live scores on any post or page:

```
[livescore]
```

### Advanced Options

```
[livescore league="Premier League" limit="5" auto_refresh="true"]
```

#### Parameters

- `league`: Filter by specific league name (optional)
- `limit`: Number of matches to display (default: 10)
- `auto_refresh`: Enable auto-refresh (default: true)

### Examples

```
[livescore league="Champions League" limit="8"]
[livescore auto_refresh="false"]
[livescore limit="15"]
```

## Features in Detail

### Real-time Updates
- Automatic refresh every 5 minutes
- Manual refresh button
- Live countdown timer
- Pause when tab is not active

### Match Status Indicators
- **LIVE**: Matches currently in progress (red indicator with pulse animation)
- **FT**: Finished matches (green indicator)
- **Scheduled**: Upcoming matches (blue indicator)

### Responsive Design
- Mobile-first approach
- Tablet and desktop optimized
- Print-friendly styles
- Accessibility features

### Error Handling
- Connection timeout handling
- API rate limit management
- Retry mechanism with exponential backoff
- User-friendly error messages

## Technical Requirements

- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+
- Internet connection for API access
- Valid Football-Data.org API key

## File Structure

```
wp-livescore-pro/
├── wp-livescore-pro.php          # Main plugin file
├── admin/
│   └── admin-page.php             # Admin configuration page
├── templates/
│   └── livescore-display.php      # Frontend display template
├── assets/
│   ├── livescore.css              # Plugin styles
│   └── livescore.js               # Plugin JavaScript
└── README.md                      # Documentation
```

## Database Schema

The plugin creates a `wp_livescores` table with the following structure:

- `id`: Unique match ID
- `match_id`: External API match identifier
- `home_team`: Home team name
- `away_team`: Away team name
- `home_score`: Home team score
- `away_score`: Away team score
- `status`: Match status (live, finished, scheduled)
- `match_time`: Match date and time
- `league`: League/competition name
- `updated_at`: Last update timestamp

## API Integration

Uses the Football-Data.org API which provides:
- Free tier: 10 requests per minute
- Coverage: Major European leagues
- Real-time data updates
- Reliable service with 99.9% uptime

## Security Features

- Nonce verification for all AJAX requests
- Input sanitization and validation
- SQL injection prevention
- XSS protection
- WordPress capability checks

## Performance Optimization

- Efficient database queries
- Minimal HTTP requests
- Optimized JavaScript loading
- CSS minification ready
- Caching-friendly architecture

## Troubleshooting

### Common Issues

1. **No scores displaying**
   - Check API key configuration
   - Verify internet connection
   - Check plugin activation

2. **API connection errors**
   - Validate API key
   - Check rate limits
   - Verify firewall settings

3. **Styling issues**
   - Clear cache
   - Check theme compatibility
   - Verify CSS loading

### Debug Mode

Enable WordPress debug mode for detailed error logs:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Support

For support and feature requests, please check the plugin documentation or contact the developer.

## License

GPL v2 or later

## Changelog

### 1.0.0
- Initial release
- Live score display functionality
- API integration with Football-Data.org
- Admin configuration panel
- Responsive design
- Auto-refresh capabilities
- Error handling and retry mechanisms