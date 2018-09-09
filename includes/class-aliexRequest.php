<?php
namespace com\plugish\aliex;

use DiDom\Document;
use DiDom\Element;
use Exception;

class AliexRequest {

	/**
	 * The URL to be scraping.
	 * @var string
	 */
	public $url;

	/**
	 * Used throughout this object.
	 * @var Document
	 */
	private $dom;

	/**
	 * A copy of the request for this object.
	 * @var array|\WP_Error
	 */
	private $request;

	/**
	 * Builds the request and sets up basic properties.
	 *
	 * @param string $url
	 *
	 * @throws Exception
	 */
	public function __construct( string $url ) {
		// Set the property, just in case it needs to be access later.
		$this->url = $url;

		// Silence any domdocument errors since we don't control the HTML.
		libxml_use_internal_errors( true );

		// Grab the transient if you can.
		$trans_key = 'aliex_' . md5( $url );
		$response  = get_transient( $trans_key );
		if ( ! empty( $response ) ) {
			$this->request = $response;
			$this->dom     = new Document( $response );
			return;
		}

		// Fake the user agent, since Aliexpress locks down local devs.
		$request = wp_remote_get( $this->url, [
			'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36',
		] );

		if ( is_wp_error( $request ) ) {
			throw new Exception( $request->get_error_message() );
		}

		if ( 200 !== wp_remote_retrieve_response_code( $request ) ) {
			throw new Exception( 'Failed to get a valid response code from the request.' );
		}

		$this->request = wp_remote_retrieve_body( $request );
		$this->dom     = new Document( $this->request );

		set_transient( $trans_key, $this->request, 6 * HOUR_IN_SECONDS );
	}

	/**
	 * Gets the product images ( full size ) for a product request.
	 * @return array
	 */
	public function get_product_images() : array {
		preg_match( '/imageBigViewURL=\[\n(.*?)\r?\n?\];/si', $this->request, $matches );

		if ( empty( $matches[1] ) ) {
			return [];
		}

		$matched_urls = array_map( 'trim', explode( ',', str_replace( '"', '', $matches[1] ) ) );
		if ( empty( $matched_urls ) ) {
			return [];
		}

		return $matched_urls;
	}

	/**
	 * Gets the main product image from the javascript variable.
	 * @return string
	 */
	public function get_main_product_image() : string {
		preg_match( '/mainBigPic = "(.*?)";/si', $this->request, $matches );
		if ( empty( $matches[1] ) ) {
			return '';
		}

		return (string) $matches[1];
	}

	/**
	 * Gets the total available stock for the requested product, encompasses all SKUs.
	 * @return int
	 */
	public function total_stock_available() : int {
		preg_match( '/totalAvailQuantity=([0-9]+);/', $this->request, $matches );
		if ( empty( $matches[1] ) ) {
			return 0;
		}
		return intval( $matches[1] );
	}

	/**
	 * Returns the JSON decoded array of SKU data from the skuProducts javascript variable.
	 * @return array|bool an array on success, false on failure.
	 */
	public function get_js_sku_data() : array {
		preg_match( '/skuProducts=\[(.*?)\];/si', $this->request, $matches );
		if ( empty( $matches[1] ) ) {
			return false;
		}

		return json_decode( sprintf( '[%s]', $matches[1] ), true );
	}

	public function parse_variations_html() : array {
		$sku_sets = $this->dom->find( '#j-product-info-sku' );
		$sku_data = [];
		for ( $i = 0; $i < count( $sku_sets ); $i++ ) {
			// Drop it into a var so it can be looped.
			$sku_set = $sku_sets[ $i ];

			// Get the variation label.
			$label = $sku_set->find( '.p-item-title' )[0]->text();
			$label = trim( str_replace( ':', '', $label ) );

			// Get the error now.
			$msg_error = trim( $sku_set->find( '.sku-msg-error' )[0]->text() );

			// Get all sku props
			$sku_props = $sku_set->find( '.sku-attr-list' );

			// Get each individual SKU data set.
			$sku_prop_id = $sku_props[0]->attr( 'data-sku-prop-id' );

			$sku_data[] = compact( 'sku_prop_id', 'label', 'msg_error' );
		}

		wp_die( print_r( $sku_data, 1 ) );
	}
}
