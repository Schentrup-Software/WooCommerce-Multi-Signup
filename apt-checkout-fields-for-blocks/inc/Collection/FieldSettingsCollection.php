<?php

namespace WPDesk\CBFields\Collection;

use Traversable;
use IteratorAggregate;

/**
 * Collection of settings for all plugin fields.
 *
 * @implements IteratorAggregate<string, FieldSettings>
 */
class FieldSettingsCollection implements IteratorAggregate {

	/**
	 * @var array<string, array<string, mixed>>
	 */
	private $raw_settings;

	private const SETTINGS_KEY = 'wpdesk_cbfields_settings';

	public function __construct() {
		$this->raw_settings = \get_option( self::SETTINGS_KEY, [] );
	}

	/**
	 * @param array<string, array<string, mixed>> $raw_settings.
	 */
	public function set_raw_settings( array $raw_settings ): void {
		$this->raw_settings = $raw_settings;
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public function get_raw_settings(): array {
		return $this->raw_settings;
	}

	/**
	 * @return Traversable<string, FieldSettings>
	 */
	public function getIterator(): Traversable {
		foreach ( $this->raw_settings as $key => $raw_settings ) {
			yield $key => new FieldSettings( $raw_settings );
		}
	}

	public function get_by_meta_key( string $key ): ?FieldSettings {
		if ( isset( $this->raw_settings[ $key ] ) ) {
			return new FieldSettings( $this->raw_settings[ $key ] );
		}
		return null;
	}

	public function update(): bool {
		return \update_option( self::SETTINGS_KEY, $this->get_raw_settings() );
	}

	/**
	 * Applies a callback function to reduce the collection to a single value.
	 *
	 * @param callable $callback The callback function.
	 * @param mixed $initial The initial value for the reduction.
	 *
	 * @return mixed The result of the reduction.
	 */
	public function reduce( callable $callback, $initial = null ) {
		return array_reduce(
			iterator_to_array( $this->getIterator() ),
			$callback,
			$initial
		);
	}
}
