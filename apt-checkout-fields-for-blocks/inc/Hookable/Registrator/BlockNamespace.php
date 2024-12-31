<?php

namespace WPDesk\CBFields\Hookable\Registrator;

use CBFieldsVendor\WPDesk\PluginBuilder\Plugin\Hookable;

/**
 * Register blocks namespace for data attributes.
 * This is needed to render the block in the frontend.
 */
class BlockNamespace implements Hookable {

	/**
	 * @var string
	 */
	private $namespace;

	public function __construct( string $plugin_namespace ) {
		$this->namespace = $plugin_namespace;
	}

	public function hooks(): void {
		add_filter( '__experimental_woocommerce_blocks_add_data_attributes_to_namespace', $this );
	}

	/**
	 * @param string[] $allowed_namespaces List of namespaces.
	 * @return string[]
	 */
	public function __invoke( $allowed_namespaces ) {
		$allowed_namespaces[] = $this->namespace;
		return $allowed_namespaces;
	}
}
