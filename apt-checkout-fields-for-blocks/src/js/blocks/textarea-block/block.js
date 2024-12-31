/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect, useMemo, useCallback } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { VALIDATION_STORE_KEY } from '@woocommerce/block-data';
import { Label, ValidationInputError } from '@woocommerce/blocks-components';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { runValidations } from '../../validations/validations';
import { Textarea } from '../../components/textarea/textarea';

const FrontendBlock = ({
	fieldId,
	fieldName,
	metaName,
	label,
	placeholder,
	className,
	defaultValue,
	validationSettings: validationSettingsRaw,
	helpText,
	checkoutExtensionData,
}) => {
	const { setExtensionData } = checkoutExtensionData;
	const [inputValue, setInputValue] = useState(defaultValue || '');
	// True on mount.
	const [isPristine, setIsPristine] = useState(true);
	const validationErrorId = `textarea-${fieldId}`;

	const validationSettings = useMemo(() => {
		return JSON.parse(validationSettingsRaw || '{}');
	}, [validationSettingsRaw]);

	const { setValidationErrors, hideValidationError, clearValidationError } =
		useDispatch(VALIDATION_STORE_KEY);

	const error = useSelect((select) => {
		return select(VALIDATION_STORE_KEY).getValidationError(
			validationErrorId
		);
	});

	const hasError = error?.message && !error?.hidden;

	const validateInput = useCallback(
		(errorsHidden = true) => {
			clearValidationError(validationErrorId);

			const errorMessage = runValidations(inputValue, validationSettings);
			if (errorMessage) {
				setValidationErrors({
					[validationErrorId]: {
						message: errorMessage,
						hidden: errorsHidden,
					},
				});
			}
		},
		[
			clearValidationError,
			validationErrorId,
			setValidationErrors,
			inputValue,
			validationSettings,
		]
	);

	/**
	 * Validation on mount.
	 * Errors are hidden until blur.
	 */
	useEffect(() => {
		if (!isPristine) {
			return;
		}

		setIsPristine(false);

		validateInput(true);
	}, [isPristine, setIsPristine, validateInput]);

	useEffect(() => {
		setExtensionData('checkout-fields-for-blocks', metaName, inputValue);
	}, [setExtensionData, metaName, inputValue]);

	return (
		<div
			className={clsx(className, {
				'has-error': hasError,
			})}
		>
			<Label
				label={label}
				screenReaderLabel={label}
				wrapperElement="label"
				wrapperProps={{
					htmlFor: fieldId,
				}}
				htmlFor={fieldId}
			/>
			<Textarea
				id={fieldId}
				value={inputValue ? inputValue : ''}
				placeholder={placeholder}
				name={fieldName}
				onChange={(newValue) => {
					// Hide errors while typing.
					hideValidationError(validationErrorId);

					validateInput(true);

					setInputValue(newValue);
				}}
				onBlur={() => validateInput(false)}
				aria-invalid={hasError}
				aria-errormessage={validationErrorId}
			/>

			{helpText && <p className="help-text">{helpText}</p>}

			{hasError && (
				<ValidationInputError
					errorMessage={error?.message}
					propertyName={validationErrorId}
				/>
			)}
		</div>
	);
};

export default FrontendBlock;
