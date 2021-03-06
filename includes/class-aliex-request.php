<?php
namespace com\plugish\aliex;

use DiDom\Document;
use Exception;

class Aliex_Request {

	/**
	 * The URL to the freight server.
	 */
	const FREIGHT_URL = 'https://freight.aliexpress.com/ajaxFreightCalculateService.htm';

	/**
	 * The URL to be scraping.
	 * @var string
	 */
	public $url;

	/**
	 * The product ID, used in a lot of calls.
	 * @var string
	 */
	public $product_id;

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
	 * The public description result from the HTTP request, stored so it's not queried more than once.
	 * @var array
	 */
	public $public_description;

	/**
	 * Holds a request for shipping detail results, so it's not queried twice or more.
	 * @var array
	 */
	public $shipping_details;

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

		// Grab the transient if you can.
		$trans_key = 'aliex_' . md5( $url );
		$response  = get_transient( $trans_key );
		if ( ! empty( $response ) ) {
			$this->request = $response;
			$this->dom     = new Document( $response );
			return;
		}
		$this->request = $this->remote_get( $this->url );
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
			return [ 'product_images' => [] ];
		}

		$matched_urls = array_map( 'trim', explode( ',', str_replace( '"', '', $matches[1] ) ) );
		if ( empty( $matched_urls ) ) {
			return [ 'product_images' => [] ];
		}

		return [ 'product_images' => array_map( 'esc_url_raw', $matched_urls ) ];
	}

	/**
	 * Gets the main product image from the javascript variable.
	 * @return array
	 */
	public function get_main_product_image() : array {
		preg_match( '/mainBigPic = "(.*?)";/si', $this->request, $matches );
		if ( empty( $matches[1] ) ) {
			return [ 'main_product_image' => '' ];
		}

		return [ 'main_product_image' => esc_url_raw( $matches[1] ) ];
	}

	/**
	 * Gets the total available stock for the requested product, encompasses all SKUs.
	 * @return array
	 */
	public function total_stock_available() : array {
		preg_match( '/totalAvailQuantity=([0-9]+);/', $this->request, $matches );
		if ( empty( $matches[1] ) ) {
			return [ 'total_stock_available' => 0 ];
		}
		return [ 'total_stock_available' => intval( $matches[1] ) ];
	}

	/**
	 * Returns the JSON decoded array of SKU data from the skuProducts javascript variable.
	 * @return array|bool an array on success, false on failure.
	 */
	public function get_product_variations() : array {
		preg_match( '/skuProducts=\[(.*?)\];/si', $this->request, $matches );
		if ( empty( $matches[1] ) ) {
			return [ 'variations' => [] ];
		}

		return [ 'variations' => json_decode( sprintf( '[%s]', $matches[1] ), true ) ];
	}

	/**
	 * Gets the endpoint for the public product description CDN.
	 * @return string
	 */
	private function get_description_endpoint() : string {
		preg_match( '/detailDesc=\"(.*?)\";/si', $this->request, $matches );
		if ( empty( $matches[1] ) ) {
			return '';
		}

		return str_replace( '"', '', $matches[1] );
	}

	/**
	 * Gets the public description text that can be parsed.
	 *
	 * This method does call for a second HTTP request, this is because the data is stored on an external server and
	 * the request for the server is stored in the HTML body of the main site.
	 *
	 * @return array
	 */
	public function get_public_description() : array {
		if ( isset( $this->public_description ) && ! is_null( $this->public_description ) ) {
			return [ 'public_description' => $this->public_description ];
		}

		$desc_url = $this->get_description_endpoint();
		if ( empty( $desc_url ) ) {
			return [ 'public_description' => '' ];
		}

		try {
			$this->public_description = $this->remote_get( $desc_url );
		} catch ( Exception $e ) {
			return [ 'public_description' => '' ];
		}

		return [ 'public_description' => $this->public_description ];
	}

	/**
	 * Parses the SKU HTML sections and creates a human readable ( somewhat ) array of data.
	 * @return array
	 */
	public function get_product_attributes() : array {
		$sku_wrapper = $this->dom->find( '#j-product-info-sku' );
		if ( 1 > count( $sku_wrapper ) ) {
			return [
				'attributes' => [],
			];
		}

		$sku_sets = $sku_wrapper[0]->find( '.p-property-item' );
		$sku_data = [];
		for ( $i = 0; $i < count( $sku_sets ); $i++ ) { // @codingStandardsIgnoreLine Count is lightweight
			// Drop it into a var so it can be looped.
			$sku_set = $sku_sets[ $i ];

			// Get the variation label.
			$label = $sku_set->find( '.p-item-title' )[0]->text();
			$label = trim( str_replace( ':', '', $label ) );

			// Get the error now.
			$msg_error = trim( $sku_set->find( '.sku-msg-error' )[0]->text() );

			// Get all sku props
			$sku_props = $sku_set->find( '.sku-attr-list' );

			// Get the sku prop ID, for later use.
			$sku_prop_id = $sku_props[0]->attr( 'data-sku-prop-id' );

			$skus         = [];
			$sku_children = $sku_props[0]->find( 'li' );
			for ( $y = 0; $y < count( $sku_children ); $y++ ) { // @codingStandardsIgnoreLine Count is lightweight
				// Saves typing later.
				$child = $sku_children[ $y ];

				// Get the anchor object.
				$anchor = $child->find( 'a[^data-role=sku]' );
				if ( 1 > count( $anchor ) ) {
					continue;
				}

				// Get the sku properties from the anchor.
				$id = $anchor[0]->getAttribute( 'data-sku-id' );

				// @TODO: Seems to be auto-updated somehow through the JS.
				$spm_anchor_id = $anchor[0]->getAttribute( 'data-spm-anchor-id' );

				$image = $anchor[0]->find( 'img' );
				if ( 1 > count( $image ) ) {
					// This isn't an image-based SKU.
					$sku_label = trim( $anchor[0]->text() );
				} else {
					// This is an image-based SKU, return the image URL and additional data.
					$sku_label = $image[0]->getAttribute( 'title' );

					// @TODO: Seems to be auto-updated somehow through the JS.
					$img_spm_anchor_id = $image[0]->getAttribute( 'data-spm-anchor-id' );
					$src               = $image[0]->getAttribute( 'src' );
					$big_pic           = $image[0]->getAttribute( 'bigpic' );
				}

				$sku = compact( 'id', 'sku_label', 'spm_anchor_id' );
				if ( 1 <= count( $image ) ) {
					$sku['image'] = compact( 'src', 'big_pic', 'img_spm_anchor_id' );
				}

				$skus[] = $sku;
			}

			$sku_data[] = compact( 'sku_prop_id', 'label', 'msg_error', 'skus' );
		}
		return [ 'attributes' => $sku_data ];
	}

	/**
	 * @param string $url
	 *
	 * @return string
	 * @throws Exception
	 */
	protected function remote_get( string $url ) : string {
		// Fake the user agent, since Aliexpress locks down local devs.
		$request = wp_remote_get( $url, [
			'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36',
		] );

		if ( is_wp_error( $request ) ) {
			throw new Exception( $request->get_error_message() );
		}

		if ( 200 !== wp_remote_retrieve_response_code( $request ) ) {
			throw new Exception( 'Failed to get a valid response code from the request.' );
		}

		return wp_remote_retrieve_body( $request );
	}

	/**
	 * Gets a list of elements from the packaging list on the site.
	 * @return array|\DiDom\Element[]|\DOMElement[]
	 */
	private function get_packing_items() {
		$product_pane = $this->dom->find( '#j-product-desc' );
		if ( ! $product_pane ) {
			return [];
		}

		$packaging_list = $product_pane[0]->find( '.product-packaging-list' );
		if ( ! $packaging_list ) {
			return [];
		}

		$items = $packaging_list[0]->find( 'li.packaging-item' );
		if ( ! $items ) {
			return [];
		}

		return $items;
	}

	/**
	 * Gets the packaging details for a product.
	 * @return array
	 */
	public function get_packaging_details() : array {
		$items = $this->get_packing_items();
		if ( empty( $items ) ) {
			return [];
		}

		$out = [];
		foreach ( $items as $item ) {
			$item_type = $item->find( '.packaging-title' );
			$value     = $item->find( '.packaging-des' );
			if ( ! $item_type || ! $value ) {
				continue;
			}

			$label = str_replace( ':', '', sanitize_text_field( $item_type[0]->text() ) );
			$slug  = sanitize_title( $label );

			switch ( $slug ) {
				case 'package-weight':
					$value = [
						'weight' => floatval( $value[0]->getAttribute( 'rel' ) ),
						'unit'   => apply_filters( 'jays_aliex_get_default_shipping_unit', 'kg', $value ),
					];
					break;
				case 'package-size':
					$value = [
						'size' => array_combine( [ 'length', 'width', 'height' ], array_map( 'intval', explode( '|', $value[0]->getAttribute( 'rel' ) ) ) ),
						'unit' => apply_filters( 'jays_aliex_get_default_size_unit', 'cm', $value ),
					];
					break;
				default:
					$value = $value[0]->text();
					break;
			}

			$out[ $slug ] = $value;
		}

		return [ 'packaging-details' => $out ];
	}

	public function get_item_specifics() : array {
		$element = $this->dom->find( 'div.ui-box.product-property-main:not(#photo-from-buyer)' );
		if ( ! $element ) {
			return [ 'item_specifics' => [] ];
		}

		$label      = $element[0]->find( '.ui-box-title' );
		$attributes = $element[0]->find( 'li.property-item' );
		if ( ! $label || ! $attributes ) {
			return [ 'item_specifics' => [] ];
		}

		$item_specifics = [];
		foreach ( $attributes as $attribute ) {
			$title = $attribute->find( '.propery-title' );
			$value = $attribute->find( '.propery-des' );

			if ( ! $title || ! $value ) {
				continue;
			}

			$title = sanitize_text_field( trim( str_replace( ':', '', $title[0]->text() ) ) );
			$value = sanitize_text_field( $value[0]->text() );

			$item_specifics[ sanitize_title( $title ) ] = compact( 'title', 'value' );
		}

		return [
			'item_specifics' => [
				'label' => sanitize_text_field( $label[0]->text() ),
				'items' => $item_specifics,
			],
		];
	}

	/**
	 * Gets the product title.
	 * @return array
	 */
	public function get_name() : array {
		$name = $this->dom->find( 'h1.product-name' );
		if ( ! $name ) {
			return [ 'product_name' => '' ];
		}

		return [ 'product_name' => sanitize_text_field( $name[0]->text() ) ];
	}

	/**
	 * Gets the store name and url from the product page.
	 * @return array
	 */
	public function get_store_info() : array {
		$store_element = $this->dom->find( '.shop-name' );
		if ( ! $store_element ) {
			return [ 'store' => [] ];
		}

		$anchor = $store_element[0]->find( 'a' );
		$url    = $anchor[0]->getAttribute( 'href' );
		if ( 0 === stripos( $url, '//' ) ) {
			$url = 'https:' . $url;
		}

		return [
			'store' => [
				'name' => sanitize_text_field( $anchor[0]->text() ),
				'url'  => esc_url_raw( $url ),
			],
		];
	}

	/**
	 * Returns the value of the total orders.
	 * @return array
	 */
	public function get_orders_total() : array {
		$element = $this->dom->find( '#j-order-num' );
		if ( ! $element ) {
			return [
				'orders_total' => 0,
			];
		}

		$value = explode( ' ', $element[0]->text() );
		if ( ! $value || empty( $value[0] ) ) {
			return [
				'orders_total' => 0,
			];
		}

		return [
			'orders_total' => intval( $value[0] ),
		];
	}

	/**
	 * Gets the minimum retail price for the product.
	 * @return float
	 */
	private function get_min_price() : float {
		preg_match( '/runParams\.minPrice="(.*?)";/s', $this->request, $matches );
		if ( empty( $matches[1] ) ) {
			return 0.0;
		}
		return floatval( $matches[1] );
	}

	/**
	 * Gets the max retail price for the product.
	 * @return float
	 */
	private function get_max_price() : float {
		preg_match( '/runParams\.maxPrice="(.*?)";/s', $this->request, $matches );
		if ( empty( $matches[1] ) ) {
			return 0.0;
		}
		return floatval( $matches[1] );
	}
	/**
	 * Gets the max retail price for product after discount.
	 * @return float
	 */
	private function get_disc_max_price() : float {
		preg_match( '/runParams\.actMaxPrice="(.*?)";/s', $this->request, $matches );
		if ( empty( $matches[1] ) ) {
			return 0.0;
		}
		return floatval( $matches[1] );
	}
	/**
	 * Gets the minimum retail price for product after discount.
	 * @return float
	 */
	private function get_disc_min_price() : float {
		preg_match( '/runParams\.actMinPrice="(.*?)";/s', $this->request, $matches );
		if ( empty( $matches[1] ) ) {
			return 0.0;
		}
		return floatval( $matches[1] );
	}

	/**
	 * Gets the currency code for the product, defaults to USD.
	 * @return string
	 */
	private function get_currency_code() : string {
		preg_match( '/runParams\.baseCurrencyCode="(.*?)";/s', $this->request, $matches );
		if ( empty( $matches[1] ) ) {
			return 'USD';
		}
		return sanitize_text_field( $matches[1] );
	}

	/**
	 * Gets the discount percentage, without the symbol, for the product.
	 * @return float
	 */
	private function get_discount_percentage() : float {
		preg_match( '/runParams\.discount="(.*?)";/si', $this->request, $matches );
		if ( empty( $matches[1] ) ) {
			return 0.0;
		}
		return floatval( $matches[1] );
	}

	/**
	 * Gets the visible price data on the screen for the customer.
	 * @return array
	 */
	public function get_visible_price_data() : array {
		return [
			'price_data' => [
				'min_price'          => $this->get_min_price(),
				'max_price'          => $this->get_max_price(),
				'min_discount_price' => $this->get_disc_min_price(),
				'max_discount_price' => $this->get_disc_max_price(),
				'currency_code'      => $this->get_currency_code(),
				'discount_percent'   => $this->get_discount_percentage(),
			],
		];
	}

	/**
	 * Gets the Product ID for the product itself, caches it in the object.
	 * @return array
	 */
	public function get_product_id() : array {
		if ( ! empty( $this->product_id ) ) {
			return [ 'ID' => $this->product_id ];
		}

		preg_match( '/runParams\.productId="(.*?)";/s', $this->request, $matches );
		if ( empty( $matches[1] ) ) {
			return [ 'ID' => '' ];
		}

		$this->product_id = $matches[1];

		return [ 'ID' => $matches[1] ];
	}

	/**
	 * Gets the shipping detail JSON object, converts it to an array.
	 * @return array
	 */
	public function get_shipping_details() : array {

		// Return the cached data first.
		if ( ! empty( $this->shipping_details ) ) {
			return $this->shipping_details;
		}

		$product_id = $this->get_product_id();
		if ( empty( $product_id['ID'] ) ) {
			return [ 'shipping_details' => [] ];
		}

		$defaults = apply_filters( 'jays_aliex_get_default_freight_params', [
			'productid'    => $product_id['ID'],
			'country'      => 'US',
			'province'     => '',
			'city'         => '',
			'count'        => '1',
			'f'            => 'd',
			'currencyCode' => 'USD',
			'abVersion'    => '1',
		] );

		try {
			$request = $this->remote_get( add_query_arg( $defaults, self::FREIGHT_URL ) );
		} catch ( Exception $e ) {
			return [ 'shipping_details' => [] ];
		}

		// The request wraps the content in parenthesis, remove them.
		$request = ltrim( rtrim( $request, ')' ), '(' );

		$this->shipping_details = [ 'shipping_details' => json_decode( $request, true ) ];

		return $this->shipping_details;
	}

	/**
	 * Gets the full breadcrumb in an array.
	 * @return array
	 */
	public function get_breadcrumb() : array {

		$default = [ 'breadcrumb' => [] ];

		$element_set = $this->dom->find( 'div.ui-breadcrumb' );
		if ( ! $element_set ) {
			return $default;
		}

		$anchors = $element_set[0]->find( 'a' );
		if ( ! $anchors ) {
			return $default;
		}

		$out = [];
		foreach ( $anchors as $anchor ) {
			$href = $anchor->getAttribute( 'href' );
			if ( 0 === stripos( $href, '//' ) ) {
				$href = 'https:' . $href;
			}

			$out[] = [
				'label' => sanitize_text_field( $anchor->text() ),
				'href'  => esc_url_raw( $href )
			];
		}

		return [ 'breadcrumb' => $out ];
	}
}
