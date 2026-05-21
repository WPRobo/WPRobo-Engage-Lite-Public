# WPRobo Engage Lite

Free WordPress plugin for converting visitors with popups, floating bars, and slide-ins.

## About

WPRobo Engage Lite helps you build and launch conversion campaigns without writing a single line of code.

## Features

- Unlimited popup, floating bar, and slide-in campaigns
- 10+ pre-built templates
- Smart display rules (page, post, category, tag, URL)
- Time delay, scroll depth, and exit-intent triggers
- Basic analytics (impressions and conversions)
- Lead management with export
- Third-party form embed (Mailchimp, ConvertKit, AWeber, and more)
- Campaign import/export as JSON
- Role-based access control

## Development Setup

### Prerequisites
- PHP 7.4 or higher
- Composer
- Node.js and npm
- WordPress 5.8 or higher

### Installation

1. **Install Dependencies:**
   ```bash
   composer install
   npm install
   ```

2. **Build Assets:**
   ```bash
   npm run build
   ```

3. **Development Mode (with live rebuild):**
   ```bash
   npm run watch
   ```

## Architecture

- **Namespace:** `WPRobo_Engage_Lite`
- **Text Domain:** `wprobo-engage-lite`
- **Prefix:** `wpr_` (functions), `WPROBO_ENGAGE_LITE_` (constants)
- **Tailwind Prefix:** `wpr-`
- **Main File:** `wpro-engage-lite.php`

## Directory Structure

```
lite/
├── assets/
│   ├── css/          # Compiled CSS (generated)
│   ├── js/           # JavaScript files
│   └── src/          # Source CSS (Tailwind)
├── src/
│   ├── Admin/        # Admin functionality
│   ├── Api/          # REST API endpoints
│   ├── Core/         # Core plugin classes
│   ├── Includes/     # Helper classes
│   └── Public/       # Frontend functionality
├── templates/        # Campaign templates
├── languages/        # Translation files
└── wpro-engage-lite.php # Main plugin file
```

## Coding Standards

Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/).

## Security

- All inputs sanitized
- All outputs escaped
- Nonce verification on forms
- Capability checks on admin actions
- Prepared SQL statements

## License

GPL-2.0-or-later
