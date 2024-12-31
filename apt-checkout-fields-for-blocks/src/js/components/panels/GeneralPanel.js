import { __ } from '@wordpress/i18n';
import { PanelBody, TextControl } from '@wordpress/components';
import { generateSlug } from '../../utils/string';

export const GeneralPanel = ({ attributes, setAttributes, children }) => {
	const { fieldName, metaName } = attributes;
	return (
		<PanelBody title={__('General Settings', 'checkout-fields-for-blocks')}>
			<TextControl
				label={__('Field name', 'checkout-fields-for-blocks')}
				value={fieldName}
				onChange={(newFieldName) => {
					setAttributes({
						fieldName: newFieldName,
						metaName: '_meta_' + generateSlug(newFieldName),
					});
				}}
			/>

			<TextControl
				label={__('Meta name', 'checkout-fields-for-blocks')}
				value={metaName}
			/>
			{children}
		</PanelBody>
	);
};
