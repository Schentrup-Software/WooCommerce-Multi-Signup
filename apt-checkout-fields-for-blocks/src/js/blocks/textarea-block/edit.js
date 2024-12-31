/**
 * External dependencies
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { TextareaControl } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Label } from '@woocommerce/blocks-components';

/**
 * Internal dependencies
 */
import { getParentBlockName } from '../../utils/block';
import {
	GeneralPanel,
	ValidationPanel,
	DisplayPanel,
} from '../../components/panels';
import { Textarea } from '../../components/textarea/textarea';
import './style.scss';

export const Edit = ({ attributes, setAttributes, clientId }) => {
	const {
		fieldId,
		label,
		placeholder,
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
		defaultValue,
		setAttributes,
	]);

	return (
		<>
			<InspectorControls>
				<GeneralPanel
					attributes={attributes}
					setAttributes={setAttributes}
				>
					<TextareaControl
						label={__('Label', 'checkout-fields-for-blocks')}
						value={label}
						onChange={(newLabel) =>
							setAttributes({ label: newLabel })
						}
					/>

					<TextareaControl
						label={__('Placeholder', 'checkout-fields-for-blocks')}
						value={placeholder}
						onChange={(newPlaceholder) =>
							setAttributes({ placeholder: newPlaceholder })
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
				<Label
					label={ label }
					screenReaderLabel={ label }
					wrapperElement="label"
					wrapperProps={ {
						htmlFor: fieldId,
					} }
					htmlFor={ fieldId }
				/>
				<Textarea
					id={fieldId}
					value={defaultValue}
					onChange={(newValue) => setAttributes({ defaultValue: newValue })}
					help={helpText}
					placeholder={placeholder}
				/>

			</div>
		</>
	);
};
