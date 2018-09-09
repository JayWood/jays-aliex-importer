<?php
namespace com\plugish\aliex;

require_once 'class-aliexRequest.php';

class Scraper {

	public static $instance = null;

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function hooks() {
		add_action( 'admin_init', [ $this, 'testing' ] );
	}

	public function testing() {
		try {
			$scraper = new AliexRequest( 'https://www.aliexpress.com/store/product/Ghost-evil-Skull-skeleton-Hand-CZ-Ring-European-and-American-Punk-style-Motor-Biker-Men-Ring/1899928_32874190860.html' );
		} catch ( \Exception $e ) {
			return;
		}

		$scraper->get_js_sku_data();
	}

}
add_action( 'plugins_loaded', [ Scraper::get_instance(), 'hooks' ] );
