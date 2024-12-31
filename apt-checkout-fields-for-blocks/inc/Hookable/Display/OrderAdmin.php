<?php

namespace WPDesk\CBFields\Hookable\Display;

use CBFieldsVendor\WPDesk\PluginBuilder\Plugin\Hookable;

/**
 * Display fields on admin order page.
 */
class OrderAdmin extends DisplayBase implements Hookable {

	const PAGE_ID = 'orderAdmin';

	public function hooks(): void {
		\add_action( 'woocommerce_admin_order_data_after_shipping_address', $this );
	}

	/**
	 * @param \WC_Order $order
	 */
	public function __invoke( $order ): void {
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
