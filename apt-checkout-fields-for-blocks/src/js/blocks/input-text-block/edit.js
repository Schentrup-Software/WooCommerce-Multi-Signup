/**
 * External dependencies
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { TextControl, TextareaControl } from '@wordpress/components';
import { ValidatedTextInput } from '@woocommerce/blocks-checkout';
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

export const Edit = ({ attributes, setAttributes, clientId }) => {
	const {
		fieldId,
		label,
		defaultValue,
		helpText,
		inputType,
		validationSettings,
		display,
	} = attributes;
	const parentBlockName = getParentBlockName(clientId);
	const blockProps = useBlockProps();

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
		}
		setAttributes({ parentBlock: parentBlockName });
	}, [
		clientId,
		fieldId,
		parentBlockName,
		inputType,
		setAttributes,
	]);

	return (
		<>
			<InspectorControls>
				<GeneralPanel
					attributes={attributes}
					setAttributes={setAttributes}
				>
					<TextControl
						label={__('Label', 'checkout-fields-for-blocks')}
						value={label}
						onChange={(newLabel) =>
							setAttributes({ label: newLabel })
						}
					/>

					<TextareaControl
						label={__('Help text', 'checkout-fields-for-blocks')}
						value={helpText}
						onChange={(newHelpText) =>
							setAttributes({ helpText: newHelpText })
						}
					/>
				</GeneralPanel>

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
				<ValidatedTextInput
					id={fieldId}
					type={inputType}
					required={false}
					label={label}
					value={defaultValue}
					onChange={(v) => setAttributes({ defaultValue: v })}
				/>
			</div>
		</>
	);
};
