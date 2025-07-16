# WP Livescore Pro

A WordPress plugin to display live football scores with data aggregation from external sources (Sofascore, Soccerway).

## Features
- Live football scoreboard via shortcode `[wp_livescore]`
- Responsive, mobile-friendly UI
- Filter, search, and sort matches
- Auto-refresh scores (AJAX)
- Admin settings for data source, refresh rate, leagues, and more
- Dashboard overview and error log
- Modular, secure, and performant codebase

## Installation
1. Upload the `wp-livescore-pro` folder to your WordPress `/wp-content/plugins/` directory.
2. Activate the plugin via the WordPress Plugins menu.
3. Configure settings in **WP Livescore Pro** under the admin menu.

## Usage
- Use the `[wp_livescore]` shortcode to display the scoreboard anywhere.
- Adjust settings for data source, refresh interval, and leagues in the admin panel.

## Development
- All code follows WordPress Coding Standards.
- Modular structure: `includes/`, `admin/`, `public/`, `assets/`

## Data Sources
- Sofascore.com (JSON API)
- Soccerway.com (HTML parsing)

## Security & Performance
- Nonces for AJAX and forms
- Input sanitization and output escaping
- Caching and efficient data fetching

## License
GPLv2 or later