<?php
namespace WPDesk\CBFields\Collection;

/**
 * Field settings collection.
 */
class FieldSettings {

	private const FIELD_ID            = 'fieldId';
	private const FIELD_NAME          = 'fieldName';
	private const META_NAME           = 'metaName';
	private const LABEL               = 'label';
	private const DEFAULT_VALUE       = 'defaultValue';
	private const HELP_TEXT           = 'helpText';
	private const REQUIRED            = 'required';
	private const VALIDATION_SETTINGS = 'validationSettings';
	private const DISPLAY             = 'display';
	private const OPTIONS             = 'options';

	/**
	 * @var array<string, mixed>
	 */
	private $data;

	/**
	 * @param array<string, mixed> $data
	 */
	public function __construct( array $data ) {
		$this->data = $data;
	}

	public function get_field_id(): string {
		return $this->data[ self::FIELD_ID ] ?? '';
	}

	public function get_field_name(): string {
		return $this->data[ self::FIELD_NAME ] ?? '';
	}

	public function get_meta_name(): string {
		return $this->data[ self::META_NAME ] ?? '';
	}

	public function get_label(): string {
		return $this->data[ self::LABEL ] ?? '';
	}

	public function get_default_value(): string {
		return $this->data[ self::DEFAULT_VALUE ] ?? '';
	}

	public function get_help_text(): string {
		return $this->data[ self::HELP_TEXT ] ?? '';
	}

	public function is_required(): bool {
		return $this->data[ self::REQUIRED ] ?? false;
	}

	/**
	 * @return array<int, array{label: string, value: string}>
	 */
	public function get_options(): array {
		return $this->data[ self::OPTIONS ] ?? [];
	}

	public function has_options(): bool {
		return count( $this->get_options() ) > 0;
	}

	public function get_option_label_by_value( string $value ): string {
		$options = $this->get_options();
		$labels  = array_column( $options, 'label', 'value' );

		return $labels[ $value ] ?? '';
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_validation_settings(): array {
		return $this->data[ self::VALIDATION_SETTINGS ] ?? [];
	}

	public function is_displayable( string $display_id ): bool {
		return filter_var( $this->data[ self::DISPLAY ][ $display_id ] ?? false, FILTER_VALIDATE_BOOLEAN );
	}
}
