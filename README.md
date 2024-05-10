# ECM Forum plugin for WordPress

The ECM Forum plugin is a custom forum solution for WordPress, designed specifically by Cristian Ibanez for East Coast Marketers. It offers robust features to manage forums, including user interactions, posts, likes, and messaging.

## Features

### Script and Style Management
- Enqueues necessary JavaScript and CSS to enhance forum interaction.
- Includes TinyMCE from a CDN for rich text editing.

### Database Integration
- Creates custom tables for forums, likes/dislikes, messages, and friendships upon activation.
- Uses WordPress's `dbDelta` function to ensure compatibility and performance.

### Security Features
- Nonce verification for AJAX calls ensures secure operations.
- Prevents direct access to plugin files using the standard WordPress `ABSPATH` check.

### User Interaction
- Implements AJAX for a smooth user experience without reloading the page.
- Localizes scripts to pass PHP values to JavaScript, enhancing the responsiveness and security of AJAX interactions.

### Cleanup and Maintenance
- Scheduled tasks for daily message cleanup.
- Proper cleanup of settings and scheduled events upon deactivation.

### Templating
- Custom templates for forum pages and single forum post pages.
- Registers and assigns custom page templates dynamically.

## Installation

1. Download the plugin.
2. Upload the plugin to your WordPress website through the WordPress admin panel.
3. Activate the plugin through the 'Plugins' menu in WordPress.

## Usage

- Navigate to the WordPress admin panel to configure the settings if necessary.
- Users can interact with the forum through the frontend where the forum pages are automatically integrated.
- Admins can monitor and manage the forum via the WordPress backend and custom tables created in the database.

## Dependencies

This plugin relies on jQuery and TinyMCE provided via CDN for some of its features.

## Hooks and Actions

- **Activation Hook**: Sets up the database tables and scheduled tasks.
- **Deactivation Hook**: Cleans up the options and unschedules tasks to avoid leaving unused data in your WordPress setup.

## Shortcodes

- `[ecm_forum_posts_ticker]`: Displays a ticker of the latest forum posts, enhancing the dynamic content on your site.

## License

This plugin is licensed under the GPL-3.0 license.

## Author

Developed by Cristian Ibanez at East Coast Marketers. For more information, visit [East Coast Marketers](https://eastcoastmarketers.ca).

