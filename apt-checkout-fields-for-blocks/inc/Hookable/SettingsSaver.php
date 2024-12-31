<?php

namespace WPDesk\CBFields\Hookable;

use CBFieldsVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use WPDesk\CBFields\Collection\FieldSettingsCollectionFactory;

/**
 * Saves block settings in the options table.
 */
class SettingsSaver implements Hookable {

	/**
	 * @var FieldSettingsCollectionFactory
	 */
	private $field_settings_collection_factory;

	public function __construct( FieldSettingsCollectionFactory $field_settings_collection_factory ) {
		$this->field_settings_collection_factory = $field_settings_collection_factory;
	}

	public function hooks() {
		add_action( 'save_post', $this );
	}

	/**
	 * Save block settings in the options table.
	 *
	 * @param int $post_id The ID of the post being saved.
	 * @return void
	 */
	public function __invoke( $post_id ) {
		if ( \wc_get_page_id( 'checkout' ) !== $post_id ) {
			return;
		}

		$post_content = \get_post_field( 'post_content', $post_id );
		$blocks       = \parse_blocks( $post_content );
		$field_data   = $this->recursive_block_search( $blocks );

		$field_settings_collection = $this->field_settings_collection_factory->get_collection();
		$field_settings_collection->set_raw_settings( $field_data );
		$field_settings_collection->update();
	}

	/**
	 * Recursively search for input-text blocks and extract attributes.
	 *
	 * @param array<array<string, mixed>> $blocks The blocks to search through.
	 * @return array<string, mixed> An array of found field attributes.
	 */
	private function recursive_block_search( array $blocks ): array {
		$field_data = [];
		foreach ( $blocks as $block ) {
			if ( strpos( $block['blockName'], 'checkout-fields-for-blocks/' ) === 0 ) {
				if ( isset( $block['attrs']['metaName'] ) ) {
					$meta_name                = $block['attrs']['metaName'];
					$field_data[ $meta_name ] = $block['attrs'];
				}
			}
			if ( is_array( $block['innerBlocks'] ) && count( $block['innerBlocks'] ) > 0 ) {
				$field_data = array_merge( $field_data, $this->recursive_block_search( $block['innerBlocks'] ) );
			}
		}
		return $field_data;
	}
}
