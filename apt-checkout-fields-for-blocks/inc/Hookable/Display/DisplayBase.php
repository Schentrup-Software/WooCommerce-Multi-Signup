<?php

namespace WPDesk\CBFields\Hookable\Display;

use WPDesk\CBFields\Data\FieldsMetaResolver;
use CBFieldsVendor\WPDesk\View\Renderer\Renderer;

/**
 * Base class for display hooks.
 */
class DisplayBase {

	/**
	 * @var Renderer
	 */
	protected $renderer;

	/**
	 * @var FieldsMetaResolver
	 */
	protected $meta_resolver;

	public function __construct( FieldsMetaResolver $meta_resolver, Renderer $renderer ) {
		$this->meta_resolver = $meta_resolver;
		$this->renderer      = $renderer;
	}
}
