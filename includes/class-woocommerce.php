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
	}

	/**
	 * Registers assets like CSS, JS and other components.
	 */
	public function register_assets() {
		wp_register_script( 'jays_aliex_main', get_plugin_url() . 'scripts/main.js', array( 'jquery' ), self::SCRIPTS_VER, true );
		wp_localize_script( 'jays_aliex_main', 'jays_aliex_i10n', [
			'ui' => [
				'btn_import_now' => esc_html__( 'Import Aliexpress URL', 'jays-aliex-importer' ),
			],
		] );
	}

	/**
	 * Loads registered components based on requirements.
	 */
	public function enqueue_assets() {
		wp_enqueue_script( 'jays_aliex_main' );
	}

}
add_action( 'plugins_loaded', [ WooCommerce::get_instance(), 'hooks' ] );