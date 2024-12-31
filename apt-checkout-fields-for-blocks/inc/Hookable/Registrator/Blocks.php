<?php

namespace WPDesk\CBFields\Hookable\Registrator;

use WPDesk\CBFields\Blocks\SelectBlock;
use WPDesk\CBFields\Blocks\CheckboxBlock;
use WPDesk\CBFields\Blocks\InputUrlBlock;
use WPDesk\CBFields\Blocks\TextareaBlock;
use WPDesk\CBFields\Blocks\InputTextBlock;
use WPDesk\CBFields\Blocks\InputEmailBlock;
use WPDesk\CBFields\Blocks\InputNumberBlock;
use CBFieldsVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;

/**
 * Register plugin checkout blocks.
 */
class Blocks implements Hookable {

	/**
	 * @var string
	 */
	private $plugin_dir;

	public function __construct( string $plugin_dir ) {
		$this->plugin_dir = $plugin_dir;
	}

	public function hooks(): void {
		add_action( 'woocommerce_blocks_checkout_block_registration', $this );
	}

	/**
	 * @param IntegrationRegistry $integration_registry
	 */
	public function __invoke( $integration_registry ): void {
		if ( ! $integration_registry instanceof IntegrationRegistry ) {
			return;
		}

		$integration_registry->register( new InputTextBlock( $this->plugin_dir ) );
		$integration_registry->register( new InputEmailBlock( $this->plugin_dir ) );
		$integration_registry->register( new InputNumberBlock( $this->plugin_dir ) );
		$integration_registry->register( new InputUrlBlock( $this->plugin_dir ) );
		$integration_registry->register( new CheckboxBlock( $this->plugin_dir ) );
		$integration_registry->register( new SelectBlock( $this->plugin_dir ) );
		$integration_registry->register( new TextareaBlock( $this->plugin_dir ) );
	}
}
