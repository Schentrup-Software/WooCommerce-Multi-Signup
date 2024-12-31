/**
 * External dependencies
 */
import { registerCheckoutBlock } from '@woocommerce/blocks-checkout';
/**
 * Internal dependencies
 */
import FrontendBlock from '../input-text-block/block';
import metadata from './block.json';

registerCheckoutBlock({
	metadata,
	component: (props) =>
		FrontendBlock({
			...props,
			inputType: 'number',
		}),
});
