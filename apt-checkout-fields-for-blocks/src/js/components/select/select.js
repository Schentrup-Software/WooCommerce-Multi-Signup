/**
 * External dependencies
 */
import { Icon, chevronDown } from '@wordpress/icons';
import { useCallback, useId, useMemo, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { ValidationInputError } from '@woocommerce/blocks-components';
import { useDispatch, useSelect } from '@wordpress/data';
import { VALIDATION_STORE_KEY } from '@woocommerce/block-data';
import clsx from 'clsx';

export const Select = (props) => {
	const {
		onChange,
		options,
		label,
		value = '',
		className,
		size,
		errorId: incomingErrorId,
		required,
		errorMessage = __(
			'Please select a valid option',
			'checkout-fields-for-blocks'
		),
		help,
		placeholder,
		...restOfProps
	} = props;

	const selectOnChange = useCallback(
		(event) => {
			onChange(event.target.value);
		},
		[onChange]
	);

	const emptyOption = useMemo(
		() => ({
			value: '',
			label: placeholder ?? '',
			disabled: !!required,
		}),
		[placeholder, required]
	);

	const generatedId = useId();
	const inputId = restOfProps.id || `select-${generatedId}`;
	const errorId = incomingErrorId || inputId;

	const optionsWithEmpty = useMemo(() => {
		if (required && value) {
			return options;
		}
		return [emptyOption].concat(options);
	}, [required, value, emptyOption, options]);

	const { setValidationErrors, clearValidationError } =
		useDispatch(VALIDATION_STORE_KEY);

	const { error, validationErrorId } = useSelect((select) => {
		const store = select(VALIDATION_STORE_KEY);
		return {
			error: store.getValidationError(errorId),
			validationErrorId: store.getValidationErrorId(errorId),
		};
	});

	const hasError = error?.message && !error?.hidden;

	useEffect(() => {
		if (!required || value) {
			clearValidationError(errorId);
		} else {
			setValidationErrors({
				[errorId]: {
					message: errorMessage,
					hidden: true,
				},
			});
		}
		return () => {
			clearValidationError(errorId);
		};
	}, [
		clearValidationError,
		value,
		errorId,
		errorMessage,
		required,
		setValidationErrors,
	]);

	return (
		<div
			className={clsx(className, {
				'has-error': hasError,
			})}
		>
			<div className="wc-blocks-components-select">
				<div className="wc-blocks-components-select__container">
					<label
						htmlFor={inputId}
						className="wc-blocks-components-select__label"
					>
						{label}
					</label>
					<select
						className="wc-blocks-components-select__select"
						id={inputId}
						size={size !== undefined ? size : 1}
						onChange={selectOnChange}
						value={value}
						aria-invalid={hasError}
						aria-errormessage={validationErrorId}
						{...restOfProps}
					>
						{optionsWithEmpty.map((option) => (
							<option
								key={option.value}
								value={option.value}
								data-alternate-values={`[${option.label}]`}
								disabled={
									option.disabled !== undefined
										? option.disabled
										: false
								}
							>
								{option.label}
							</option>
						))}
					</select>
					<Icon
						className="wc-blocks-components-select__expand"
						icon={chevronDown}
					/>
				</div>
			</div>

			{help && <p className="help-text">{help}</p>}

			<ValidationInputError propertyName={errorId} />
		</div>
	);
};
