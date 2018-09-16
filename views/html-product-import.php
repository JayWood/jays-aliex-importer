<?php
/**
 * Admin View: Product Import
 *
 * @package com/plugish/aliex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap woocommerce">
	<h1><?php esc_html_e( 'Aliexpress URL Import', 'jays-aliex-importer' ); ?></h1>
	<!-- Had to keep the classes for the exporter, saves on styling -->
	<div class="woocommerce-exporter-wrapper">
		<form class="woocommerce-exporter">
			<?php wp_nonce_field( 'jays-aliex-security' ); ?>
			<input type="hidden" name="action" value="jays-aliex-process-import" />
			<header>
				<span class="spinner is-active"></span>
				<h2><?php esc_html_e( 'Import URL', 'jays-aliex-importer' ); ?></h2>
				<p><?php esc_html_e( 'Enter a single URL below to import a product from Aliexpress.', 'jays-aliex-importer' ); ?></p>
			</header>
			<section>
				<table class="form-table jays-aliex-importer-options">
					<tbody>
					<tr>
						<td>
							<input type="text" id="jays-aliex-url" name="jays-aliex-url" style="width:100%;" placeholder="<?php esc_attr_e( 'https://...', 'jays-aliex-importer' ); ?>">
						</td>
					</tr>
					</tbody>
				</table>
				<progress class="jays-aliex-importer-progress" max="100" value="0"></progress>
			</section>
			<div class="wc-actions">
				<button type="submit" class="jays-aliex-importer-button button button-primary" value="<?php esc_attr_e( 'Import', 'jays-aliex-importer' ); ?>"><?php esc_html_e( 'Import', 'jays-aliex-importer' ); ?></button>
			</div>
		</form>
	</div>
</div>
