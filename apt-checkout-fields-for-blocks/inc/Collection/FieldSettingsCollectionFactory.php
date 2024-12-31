<?php
namespace WPDesk\CBFields\Collection;

/**
 * Settings collection factory (or maybe singleton).
 */
class FieldSettingsCollectionFactory {

	/**
	 * @var FieldSettingsCollection|null
	 */
	private $collection = null;

	public function get_collection(): FieldSettingsCollection {
		if ( $this->collection === null ) {
			$this->collection = new FieldSettingsCollection();
		}
		return $this->collection;
	}
}
