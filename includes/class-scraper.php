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
			$scraper = new AliexRequest( 'https://www.aliexpress.com/item/Fashion-Hollow-Couple-Wrist-Watch-Skeleton-Leather-Strap-Quartz-Watch-Clocks-Reloj-Hombre-Relogio-Feminino-Masculino/32885073625.html' );
		} catch ( \Exception $e ) {
			return;
		}

		$scraper->get_sku_products();
	}

}
add_action( 'plugins_loaded', [ Scraper::get_instance(), 'hooks' ] );
