import { ValidatedTextInput } from '@woocommerce/blocks-checkout';
import {
    useBlockProps,
} from '@wordpress/block-editor';


import { __ } from '@wordpress/i18n';


export const Edit = ({ attributes, setAttributes }) => {
    const blockProps = useBlockProps();
    return (
        <div {...blockProps}>
            <div className={'example-fields'}>
                <ValidatedTextInput
                    id="student_data"
                    type="text"
                    required={false}
                    className={'student_data'}
                    label={
                        'Student Data example'
                    }
                    value={''}
                />
            </div>
        </div>
    );
};
