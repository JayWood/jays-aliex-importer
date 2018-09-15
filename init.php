<?php
namespace com\plugish\aliex;

/**
 * Gets the plugin directory URL.
 *
 * Just a utility function for loading assets, saves from typing the same thing throughout time.
 *
 * @return string
 */
function get_plugin_url() : string {
	return plugin_dir_url( __FILE__ );
}

require_once 'includes/class-scraper.php';
require_once 'includes/class-woocommerce.php';