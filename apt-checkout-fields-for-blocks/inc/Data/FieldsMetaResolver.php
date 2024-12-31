<?php

namespace WPDesk\CBFields\Data;

use WC_Order;
use WPDesk\CBFields\Collection\FieldSettings;
use WPDesk\CBFields\Exceptions\FieldMetaException;
use WPDesk\CBFields\Collection\FieldSettingsCollectionFactory;

/**
 * Resolves fields meta data.
 */
class FieldsMetaResolver {

	/**
	 * @var FieldSettingsCollectionFactory
	 */
	private $settings_factory;

	public function __construct( FieldSettingsCollectionFactory $field_settings_settings_factory ) {
		$this->settings_factory = $field_settings_settings_factory;
	}

	/**
	 * @param WC_Order $order
	 * @param string $page_id
	 *
	 * @return array<string, array{label: string, value: string}>
	 */
	public function get_fields_meta( WC_Order $order, string $page_id ): array {
		return $this->settings_factory->get_collection()->reduce(
			function ( $data, FieldSettings $field_settings ) use ( $order, $page_id ) {
				if ( $field_settings->is_displayable( $page_id ) ) {
					try {
						$data[ $field_settings->get_meta_name() ] = $this->get_field_meta( $field_settings, $order );
					} catch ( FieldMetaException $e ) { // phpcs:ignore
						// ignore.
					}
				}

				return $data;
			},
			[]
		);
	}

	/**
	 * @param FieldSettings $field_settings
	 * @param WC_Order $order
	 *
	 * @throws FieldMetaException
	 * @return array{label: string, value: string}
	 */
	private function get_field_meta( FieldSettings $field_settings, WC_Order $order ): array {
		$meta_name = $field_settings->get_meta_name();
		if ( '' === $meta_name ) {
			throw new FieldMetaException( 'Field meta name is not set.' );
		}

		$field_name = $field_settings->get_field_name();
		if ( '' === $field_name ) {
			throw new FieldMetaException( 'Field name is not set.' );
		}

		$field_value = $order->get_meta( $meta_name );

		// FIXME: maybe use more precise check, like field type or something.
		if ( $field_settings->has_options() ) {
			$field_value = $field_settings->get_option_label_by_value( $field_value );
		}

		if ( '' === $field_value ) {
			throw new FieldMetaException( 'Empty field value.' );
		}

		return [
			'label' => $field_name,
			'value' => $field_value,
		];
	}
}
