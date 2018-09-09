<?php
/*
 * Plugin Name: Jay's Aliexpress Importer
 * Plugin URI: https://www.plugish.com
 * Description: Another Aliexpress Importer for WooCommerce, but one that doesn't suck or require you to 'register'.
 * Author: JayWood
 * Version: 0.0.1a
 * Author URI: https://www.plugish.com
 * License: GPLv2
 * Text Domain: jays-aliex-importer
 * Domain Path: /languages/
 */
namespace plugish\com\aliex;

/**
 * Gets the minimum required PHP version for this plugin.
 * @return string
 */
function get_minimum_php_version() {
	return '7.0.0';
}

/**
 * Compares currently installed PHP version with minimum requirements.
 * @return bool
 */
function is_valid_php_ver() {
	return 0 <= version_compare( PHP_VERSION, get_minimum_php_version() );
}

/**
 * Sends an admin notice and deactivates the plugin if minimum PHP requiremtns aren't met.
 */
function notice_failed_php_check() {
	if ( is_valid_php_ver() ) {
		return;
	}

	?>
	<div class="notice notice-error">
		<p><?php printf( esc_html__( 'Looks like your on PHP version %s. The Jay\'s Aliexpress Importer plugin requires %s and has been deactivated.', 'jays-aliex-importer' ), PHP_VERSION, get_minimum_php_version() ); ?></p>
	</div>
	<?php
	deactivate_plugins( plugin_basename( __FILE__ ) );
}
add_action( 'admin_notices', __NAMESPACE__ . '\notice_failed_php_check' );

if ( is_valid_php_ver() ) {
    require_once 'vendor/autoload.php';
	require_once 'init.php';
}
