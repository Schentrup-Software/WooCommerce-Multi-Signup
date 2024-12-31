<?php

namespace WPDesk\CBFields\Collection;

use CBFieldsVendor\Doctrine\Common\Collections\ArrayCollection;

/**
 * Extension data as a collection with string keys.
 *
 * @extends ArrayCollection<string, mixed>
 */
class ExtensionDataBag extends ArrayCollection {

	/**
	 * Returns the parameter as a string.
	 *
	 * @param string $key
	 * @return string
	 */
	public function get_string( string $key ): string {
		$value = $this->get( $key );
		if ( ! \is_scalar( $value ) && ! $value instanceof \Stringable ) {
			return '';
		}

		return (string) $value;
	}
}
