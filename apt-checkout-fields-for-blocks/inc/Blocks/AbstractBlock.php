<?php

namespace WPDesk\CBFields\Blocks;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

/**
 * Base class for a checkout block integration.
 */
abstract class AbstractBlock implements IntegrationInterface {

	/**
	 * @var string
	 **/
	protected $plugin_dir;

	/**
	 * @var string
	 **/
	protected $block_relative_path;

	protected const BLOCKS_PATH               = 'build/js/blocks';
	protected const BLOCK_DEFINITION_FILENAME = 'block.json';

	public function __construct( string $plugin_dir ) {
		$this->plugin_dir          = $plugin_dir;
		$this->block_relative_path = self::BLOCKS_PATH . '/' . $this->get_name();
	}

	/**
	 * @return void
	 */
	public function initialize() {
		$this->register_block();
		$this->register_block_frontend_script();
		$this->register_block_editor_script();
	}

	abstract public function get_name();

	public function get_script_handles() {
		return [ $this->get_name() . '-frontend' ];
	}

	public function get_editor_script_handles() {
		return [ $this->get_name() . '-index' ];
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_script_data() {
		return [];
	}

	protected function register_block(): void {
		\register_block_type_from_metadata( $this->plugin_dir . '/' . $this->block_relative_path . '/' . self::BLOCK_DEFINITION_FILENAME );
	}

	protected function register_block_frontend_script(): void {
		$this->register_block_script( 'frontend' );
	}

	protected function register_block_editor_script(): void {
		$this->register_block_script( 'index' );
	}

	protected function register_block_script( string $script_name ): void {
		$script_asset_path = $this->plugin_dir . '/' . $this->block_relative_path . '/' . $script_name . '.asset.php';

		$script_asset = file_exists( $script_asset_path )
			? require $script_asset_path
			: [
				'dependencies' => [],
				'version'      => '1.0.0',
			];

		\wp_register_script(
			$this->get_name() . '-' . $script_name,
			\plugins_url( $this->block_relative_path . '/' . $script_name . '.js', $this->plugin_dir . '/checkout-fields-for-blocks.php' ),
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
	}
}
