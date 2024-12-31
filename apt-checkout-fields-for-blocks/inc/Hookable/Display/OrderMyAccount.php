<?php

namespace WPDesk\CBFields\Hookable\Display;

use CBFieldsVendor\WPDesk\PluginBuilder\Plugin\Hookable;

/**
 * Display fields on my account single order page.
 */
class OrderMyAccount extends DisplayBase implements Hookable {

	const PAGE_ID = 'orderMyAccount';

	public function hooks(): void {
		\add_action( 'woocommerce_view_order', $this );
	}

	/**
	 * @param int $order_id
	 */
	public function __invoke( $order_id ): void {
		$order = \wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$additional_fields = $this->meta_resolver->get_fields_meta( $order, self::PAGE_ID );
		if ( count( $additional_fields ) === 0 ) {
			return;
		}

		$this->renderer->output_render(
			'order-meta-fields',
			[
				'additional_fields' => $additional_fields,
			]
		);
	}
}
