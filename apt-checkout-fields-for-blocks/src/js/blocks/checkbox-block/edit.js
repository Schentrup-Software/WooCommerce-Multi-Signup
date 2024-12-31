/**
 * External dependencies
 */
import {
	InspectorControls,
	useBlockProps,
	RichText,
} from '@wordpress/block-editor';
import { CheckboxControl } from '@woocommerce/blocks-components';
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getParentBlockName } from '../../utils/block';
import {
	GeneralPanel,
	ValidationPanel,
	DisplayPanel,
} from '../../components/panels';
import './style.scss';

export const Edit = ({ attributes, setAttributes, clientId }) => {
	const { fieldId, label, isChecked, display, validationSettings } =
		attributes;

	const blockProps = useBlockProps();
	const parentBlockName = getParentBlockName(clientId);

	useEffect(() => {
		if (!fieldId) {
			setAttributes({ fieldId: clientId });
			setAttributes({
				display: {
					orderConfirmation: true,
					orderAdmin: true,
					orderMyAccount: true,
					orderEmail: true,
				},
			});
			setAttributes({
				defaultValue: __('Yes', 'checkout-fields-for-blocks'),
			});
		}
		setAttributes({ parentBlock: parentBlockName });
	}, [clientId, fieldId, parentBlockName, display, setAttributes]);

	return (
		<>
			<InspectorControls>
				<GeneralPanel
					attributes={attributes}
					setAttributes={setAttributes}
				/>

				<ValidationPanel
					validationSettings={validationSettings}
					setValidationSettings={(newValidationSettings) =>
						setAttributes({
							validationSettings: newValidationSettings,
						})
					}
				/>

				<DisplayPanel
					display={display}
					onChange={(newDisplay) =>
						setAttributes({ display: newDisplay })
					}
				/>
			</InspectorControls>

			<div {...blockProps}>
				<CheckboxControl
					id={fieldId}
					checked={!!isChecked}
					disabled={false}
					onChange={(v) => setAttributes({ isChecked: v })}
				/>
				<RichText
					tagName="div"
					value={label}
					onChange={(newLabel) => setAttributes({ label: newLabel })}
					placeholder={__(
						'Enter label',
						'checkout-fields-for-blocks'
					)}
				/>
			</div>
		</>
	);
};
