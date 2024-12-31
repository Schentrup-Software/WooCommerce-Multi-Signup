<?php

namespace WPDesk\CBFields\Hookable\Registrator;

use CBFieldsVendor\WPDesk\PluginBuilder\Plugin\Hookable;

/**
 * Register categories for our blocks.
 */
class BlockCategories implements Hookable {

	/**
	 * @var string
	 */
	private $namespace;

	public function __construct( string $plugin_namespace ) {
		$this->namespace = $plugin_namespace;
	}

	public function hooks(): void {
		add_filter( 'block_categories_all', $this );
	}

	/**
	 * @param array<array{title: string, slug: string}> $categories
	 * @return array<array{title: string, slug: string}> $categories
	 */
	public function __invoke( $categories ): array {
		if ( ! is_array( $categories ) ) {
			return $categories;
		}

		return array_merge(
			$categories,
			[
				[
					'slug'  => $this->namespace,
					'title' => __( 'Checkout Block Fields', 'checkout-fields-for-blocks' ),
				],
			]
		);
	}
}
