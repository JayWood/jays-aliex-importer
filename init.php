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

/**
 * Quick helper to include a view set.
 * @param $view
 */
function get_view( $view ) {
	include 'views/' . $view;
}

require_once 'includes/class-woocommerce-core.php';
require_once 'includes/class-woocommerce-importer.php';
