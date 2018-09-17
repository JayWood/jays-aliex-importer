<?php
namespace com\plugish\aliex;

use WC_Product;
use WC_Product_Variable;

require_once 'class-aliex-request.php';

class WooCommerce_Importer {

	/**
	 * An array of product data, saves from traversing the dom multiple times.
	 */
	private $product_data;

	/**
	 * Instance of the request object.
	 * @var Aliex_Request
	 */
	private $scraper;

	public function import_product( string $url ) {
		$url = esc_url_raw( $url );

		try {
			$this->scraper = new Aliex_Request( $url );
		} catch ( \Exception $e ) {
			wp_send_json_error( [ 'msg' => $e->getMessage() ] );
		}

		$product_data = $this->get_product_data();
		$step         = 1;
		$is_variable  = ! empty( $product_data['variations'] );
		$product      = $is_variable ? $this->create_variable_product( $product_data ) : $this->create_simple_product( $product_data );

		if ( ! empty( $product ) ) {
			wp_send_json_success( compact( 'product', 'is_variable', 'step' ) );
		}

		wp_send_json_error( [ 'msg' => esc_attr__( 'Failed to import product.', 'jays-aliex-importer' ) ] );
	}

	/**
	 * @param int $product_id
	 */
	public function import_variations( int $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			wp_send_json_error( [ 'msg' => esc_attr__( 'Failed to import product variations.', 'jays-aliex-importer' ) ] );
		}

		wp_send_json_success( [
			'edit_link' => esc_js( get_edit_post_link( $product_id ) ),
		] );
	}

	/**
	 * Creates a variable product.
	 * @param array $product_data The product data to consume.
	 * @return int Product ID, 0 if failure.
	 */
	private function create_variable_product( array $product_data = [] ) : int {
		$product      = new WC_Product_Variable();
		$product_data = $product_data ?: $this->get_product_data();
		$product_meta = $this->get_product_meta();

		try {
			$product->set_sku( 'jax-' . $product_meta['ID'] );
		} catch( \Exception $e ) {
			return 0;
		}

		// Create meta for later processing.
		$meta = array_merge( [
			'_jays_aliex_original_url'         => esc_url_raw( $this->scraper->url ),
			'_jays_aliex_original_images'      => $this->get_product_images(),
			'_jays_aliex_suggested_categories' => $product_data['breadcrumb'],
			'_jays_aliex_attributes'           => $product_data['attributes'],
			'_jays_aliex_variations'           => $product_data['variations'],
		], $product_meta );

		$product->set_description( $product_data['public_description'] );
		$product->set_name( $product_data['product_name'] );
		$product->set_regular_price( $this->calculate_markup( $product_data['price_data'], 'max_price' ) );
		$product->set_status( 'draft' );
		$product->set_total_sales( $this->calculate_total_sales( $product_meta['orders_total'] ) );
		$product->set_meta_data( $meta );

		$product_id = $product->save() ?: 0;
		if ( ! empty( $product_id ) ) {
			foreach ( $meta as $k => $v ) {
				update_post_meta( $product_id, $k, $v );
			}
		}

		return $product_id;
	}

	/**
	 * Inflates the base sales based on a filter.
	 * @param int $base_sales
	 *
	 * @return int
	 */
	public function calculate_total_sales( int $base_sales ) : int {
		$inflate = apply_filters( 'jays_aliex_product_sales_increase', '30%', $base_sales );
		if ( false !== strpos( $inflate, '%' ) ) {
			$markup_percent = intval( $inflate ); // will strip off the percent.
			return number_format( floatval( ( ( $markup_percent * 0.01 ) * $base_sales ) + $base_sales ), 0 );
		}

		return intval( $base_sales + intval( $inflate ) );
	}

	/**
	 * Calculates the markup on a few key points.
	 *
	 * Also has a few filters to tie into later down the road. For example, so we can calculate shipping into the markup.
	 *
	 * @param array $prices
	 * @param string $key
	 *
	 * @return float
	 */
	public function calculate_markup( array $prices, string $key ) : float {
		$price = $prices[ $key ];
		if ( empty( $price ) ) {
			return 0;
		}

		$price = apply_filters( 'jays_aliex_base_price', $price, $prices );

		$markup_value = apply_filters( 'jays_aliex_markup', '30%', $prices );
		if ( false !== strpos( $markup_value, '%' ) ) {
			$markup_percent = intval( $markup_value ); // will strip off the percent.
			return number_format( floatval( ( ( $markup_percent * 0.01 ) * $price ) + $price ), 2 );
		}

		return number_format( floatval( $price ) + floatval( $markup_value ), 2 );
	}

	/**
	 * Creates a simple product.
	 * @param array $product_data The product data to consume.
	 * @return int Product ID, 0 if failure.
	 */
	private function create_simple_product( array $product_data = [] ) : int {
		$product_data = $product_data ?: $this->get_product_data();
		return 0;
	}

	/**
	 * Gets product images from the result.
	 * @return array
	 */
	private function get_product_images() : array {
		return array_merge(
			$this->scraper->get_main_product_image(),
			$this->scraper->get_product_images()
		);
	}

	/**
	 * Get data to be stored in meta.
	 * @return array
	 */
	private function get_product_meta() : array {
		return array_merge(
			$this->scraper->get_product_id(),
			$this->scraper->get_store_info(),
			$this->scraper->get_orders_total(),
			$this->scraper->get_shipping_details()
		);
	}

	/**
	 * Get data to be stored at the product level.
	 * @return array
	 */
	private function get_product_data() : array {
		if ( ! $this->product_data ) {
			$this->product_data = array_merge(
				$this->scraper->get_breadcrumb(),
				$this->scraper->get_name(),
				$this->scraper->get_visible_price_data(),
				$this->scraper->get_product_attributes(),
				$this->scraper->get_product_variations(),
				$this->scraper->get_packaging_details(),
				$this->scraper->get_public_description()
			);
		}

		return $this->product_data;
	}

	public static function testing() {
		try {
			$scraper = new Aliex_Request( 'https://www.aliexpress.com/item/Ghost-evil-Skull-skeleton-Hand-CZ-Ring-European-and-American-Punk-style-Motor-Biker-Men-Ring/32874190860.html' );
		} catch ( \Exception $e ) {
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
