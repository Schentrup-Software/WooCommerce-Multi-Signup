/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect, useMemo } from '@wordpress/element';
import { CheckboxControl } from '@woocommerce/blocks-components';
import { useDispatch, useSelect } from '@wordpress/data';
import { VALIDATION_STORE_KEY } from '@woocommerce/block-data';

const FrontendBlock = (props) => {
	const {
		fieldId,
		metaName,
		label,
		className,
		defaultValue,
		isChecked,
		validationSettings: validationSettingsRaw,
		checkoutExtensionData,
	} = props;
	const { setExtensionData } = checkoutExtensionData;
	const [checked, setChecked] = useState(!!isChecked);

	const validationSettings = useMemo(
		() => JSON.parse(validationSettingsRaw || '{}'),
		[validationSettingsRaw]
	);

	const validationErrorId = 'checkbox-' + fieldId;
	const { setValidationErrors, clearValidationError } =
		useDispatch(VALIDATION_STORE_KEY);

	const error = useSelect((select) => {
		return select(VALIDATION_STORE_KEY).getValidationError(
			validationErrorId
		);
	});
	const hasError = !!(error?.message && !error?.hidden);

	useEffect(() => {
		if (checked || !validationSettings.required) {
			clearValidationError(validationErrorId);
		} else {
			setValidationErrors({
				[validationErrorId]: {
					message: __(
						'This field is required.',
						'checkout-fields-for-blocks'
					),
					hidden: true,
				},
			});
		}
		return () => {
			clearValidationError(validationErrorId);
		};
	}, [checked, validationErrorId, clearValidationError, setValidationErrors]);

	useEffect(() => {
		if (checked) {
			setExtensionData('checkout-fields-for-blocks', metaName, defaultValue);
		} else {
			setExtensionData('checkout-fields-for-blocks', metaName, '');
		}
	}, [checked, setExtensionData, metaName, defaultValue]);

	return (
		<div className={className}>
			<CheckboxControl
				id={fieldId}
				name={metaName}
				checked={checked}
				onChange={() => setChecked((v) => !v)}
				hasError={hasError}
			>
				<span
					dangerouslySetInnerHTML={{
						__html: label,
					}}
				/>
			</CheckboxControl>
		</div>
	);
};

export default FrontendBlock;
