/**
 * External dependencies
 */
import { registerCheckoutBlock } from '@woocommerce/blocks-checkout';
/**
 * Internal dependencies
 */
import FrontendBlock from './block';
import metadata from './block.json';

registerCheckoutBlock({
	metadata,
	component: FrontendBlock,
});
