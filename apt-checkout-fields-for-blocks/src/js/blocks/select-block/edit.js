/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { TextControl, TextareaControl } from '@wordpress/components';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	GeneralPanel,
	OptionsPanel,
	ValidationPanel,
	DisplayPanel,
} from '../../components/panels';
import { Select } from '../../components/select/select';
import { getParentBlockName } from '../../utils/block';
import './style.scss';

export const Edit = ({ attributes, setAttributes, clientId }) => {
	const {
		label,
		fieldId,
		defaultValue,
		options,
		validationSettings,
		display,
		helpText,
		placeholder,
	} = attributes;

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
		}
		setAttributes({ parentBlock: parentBlockName });
	}, [clientId, fieldId, parentBlockName, setAttributes]);

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

					<TextControl
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

				<OptionsPanel
					options={options || []}
					onChange={(newOptions) =>
						setAttributes({ options: newOptions })
					}
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
				<Select
					id={fieldId}
					label={label}
					placeholder={placeholder}
					value={defaultValue || ''}
					options={options}
					onChange={(newDefaultValue) =>
						setAttributes({ defaultValue: newDefaultValue })
					}
					errorId={`${fieldId}-error`}
					errorMessage={__(
						'Please select a valid option',
						'checkout-fields-for-blocks'
					)}
				/>
			</div>
		</>
	);
};
