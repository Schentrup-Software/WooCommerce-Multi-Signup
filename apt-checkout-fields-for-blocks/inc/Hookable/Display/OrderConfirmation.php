<?php

namespace WPDesk\CBFields\Hookable\Display;

use CBFieldsVendor\WPDesk\PluginBuilder\Plugin\Hookable;

/**
 * Display fields on order confirmation page.
 */
class OrderConfirmation extends DisplayBase implements Hookable {

	const PAGE_ID = 'orderConfirmation';

	public function hooks(): void {
		\add_action( 'woocommerce_thankyou', $this, 10, 1 );
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
