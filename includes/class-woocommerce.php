<?php

namespace com\plugish\aliex;

class WooCommerce {

	/**
	 * Version for assets.
	 *
	 * Bump this to break cache systems.
	 */
	const SCRIPTS_VER = '0.1';

	/**
	 * Instance of the current class.
	 * @var WooCommerce
	 */
	public static $instance = null;

	/**
	 * A list of importers
	 * @var array
	 */
	private $importers = [];

	protected function __construct() {
		$this->importers['jays_aliex_importer'] = array(
			'menu'       => 'edit.php?post_type=product',
			'name'       => __( 'Aliexpress URL Import', 'jays-aliex-importer' ),
			'capability' => 'import',
			'callback'   => [ $this, 'product_importer' ],
		);
	}

	/**
	 * Gets the instance of the current class.
	 * @return WooCommerce
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Simple WordPress and WooCommerce hooks here, nothing special.
	 */
	public function hooks() {
		add_action( 'admin_init', [ $this, 'register_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_menu', [ $this, 'add_importer_menu' ] );
		add_action( 'admin_head', [ $this, 'hide_menus' ] );
		add_filter( 'woocommerce_screen_ids', [ $this, 'add_screen_id' ] );
	}

	/**
	 * Adds our screen ID to the list of possible screens, allowing WC to load styles and scripts.
	 * @param array $ids
	 *
	 * @return array
	 */
	public function add_screen_id( array $ids ) : array {
		$ids[] = 'product_page_jays_aliex_importer';
		return $ids;
	}

	/**
	 * Return true if WooCommerce imports are allowed for current user, false otherwise.
	 *
	 * @return bool Whether current user can perform imports.
	 */
	protected function import_allowed() {
		return current_user_can( 'edit_products' ) && current_user_can( 'import' );
	}

	/**
	 * Registers assets like CSS, JS and other components.
	 */
	public function register_assets() {
		wp_register_script( 'jays_aliex_main', get_plugin_url() . 'scripts/main.js', array( 'jquery' ), self::SCRIPTS_VER, true );

		$page_urls = [];
		foreach ( $this->importers as $k => $v ) {
			$page_urls[ $k ] = add_query_arg( [
				'page' => $k,
			], admin_url() . $v['menu'] );
		}

		wp_localize_script( 'jays_aliex_main', 'jays_aliex_i10n', [
			'ui'        => [
				'btn_import_now' => esc_html__( 'Import Aliexpress URL', 'jays-aliex-importer' ),
			],
			'page_urls' => $page_urls,
		] );
	}

	/**
	 * Loads registered components based on requirements.
	 */
	public function enqueue_assets() {
		wp_enqueue_script( 'jays_aliex_main' );
	}

	/**
	 * Adds menu items for the custom importers.
	 *
	 * Pretty much a 1-to-1 copy of the WC_Admin_Importers::add_to_menus()
	 */
	public function add_importer_menu() {
		foreach ( $this->importers as $id => $importer ) {
			add_submenu_page( $importer['menu'], $importer['name'], $importer['name'], $importer['capability'], $id, $importer['callback'] );
		}
	}

	public function product_importer() {
		include_once 'import/html-product-import.php';
	}

	/**
	 * Hides menu items from view, so the pages exist, but menus do not.
	 *
	 * Pretty much a 1-to-1 copy of WC_Admin_Importers::hide_from_menu()
	 */
	public function hide_menus() {
		global $submenu;

		foreach ( $this->importers as $id => $importer ) {
			if ( isset( $submenu[ $importer['menu'] ] ) ) {
				foreach ( $submenu[ $importer['menu'] ] as $key => $menu ) {
					if ( $id === $menu[2] ) {
						unset( $submenu[ $importer['menu'] ][ $key ] );
					}
				}
			}
		}
	}

}
add_action( 'plugins_loaded', [ WooCommerce::get_instance(), 'hooks' ] );
