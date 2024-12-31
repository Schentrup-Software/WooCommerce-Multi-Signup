import { ValidatedTextInput } from '@woocommerce/blocks-checkout';
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import './editor.scss';


export const Edit = ({ attributes, setAttributes }) => {
    const blockProps = useBlockProps();
    return (
        <div {...blockProps}>
            <div className={'example-fields'}>
                <ValidatedTextInput
                    id="gift_message"
                    type="text"
                    required={false}
                    className={'gift-message'}
                    label={
                        'Gift Message'
                    }
                    value={''}
                />
            </div>
        </div>
    );
};
