<?php

namespace WPDesk\CBFields\Hookable;

use WPDesk\CBFields\Collection\ExtensionDataBag;
use CBFieldsVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use WPDesk\CBFields\Collection\FieldSettingsCollectionFactory;

/**
 * Saves custom checkout fields data to order meta.
 */
class OrderSaver implements Hookable {

	/**
	 * @var string
	 */
	private $namespace;

	/**
	 * @var FieldSettingsCollectionFactory
	 */
	private $settings_factory;

	public function __construct( FieldSettingsCollectionFactory $settings_factory, string $plugin_namespace ) {
		$this->settings_factory = $settings_factory;
		$this->namespace        = $plugin_namespace;
	}

	public function hooks() {
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', $this, 10, 2 );
	}

	/**
	 * @param \WC_Order $order
	 * @param mixed $request
	 */
	public function __invoke( $order, $request ): void {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		if ( ! $request instanceof \WP_REST_Request ) {
			return;
		}

		if ( ! isset( $request['extensions'][ $this->namespace ] ) ) {
			return;
		}

		$extension_data = new ExtensionDataBag( $request['extensions'][ $this->namespace ] );

		$settings = $this->settings_factory->get_collection();
		foreach ( $settings as $field_settings ) {
			$meta_name = $field_settings->get_meta_name();
			if ( $meta_name === '' ) {
				continue;
			}
			$order->update_meta_data(
				$meta_name,
				\wc_clean( $extension_data->get_string( $meta_name ) )
			);
		}

		$order->save();
	}
}
