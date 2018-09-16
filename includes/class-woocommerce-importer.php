<?php
namespace com\plugish\aliex;

require_once 'class-aliex-request.php';

class WooCommerce_Importer {

	public function import( string $url ) {

		wp_send_json_error( 'Operation Failed' );
	}

	public function testing() {
		try {
			$scraper = new Aliex_Request( 'https://www.aliexpress.com/item/Ghost-evil-Skull-skeleton-Hand-CZ-Ring-European-and-American-Punk-style-Motor-Biker-Men-Ring/32874190860.html' );
		} catch ( \Exception $e ) {
			return;
		}

		if ( ! isset( $_GET['x'] ) ) {
			return;
		}

		wp_send_json( array_merge(
			$scraper->get_product_id(),
			$scraper->get_name(),
			$scraper->get_store_info(),
			$scraper->get_orders_total(),
			$scraper->get_breadcrumb(),
			$scraper->get_shipping_details(),
			$scraper->get_visible_price_data(),
			$scraper->get_item_specifics(),
			$scraper->get_main_product_image(),
			$scraper->get_product_images(),
			$scraper->get_product_attributes(),
			$scraper->get_product_variations(),
			$scraper->get_packaging_details()
		) );
	}

}
