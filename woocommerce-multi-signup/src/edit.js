import { ValidatedTextInput } from '@woocommerce/blocks-checkout';
import {
    useBlockProps,
} from '@wordpress/block-editor';


import { __ } from '@wordpress/i18n';


export const Edit = ({ attributes, setAttributes }) => {
    const blockProps = useBlockProps();
    return (
        <div {...blockProps}>
            <div style={{ padding: "20px", border: "1px solid #ddd", borderRadius: "4px" }}>
                <h3
                    style={{ margin: "0 0 12px 0", fontSize: "1em" }}
                    class={"wc-block-components-title"}
                >
                    Student Info
                </h3>
                <p>
                    This is where the student inputs will go. It will appear if the user tries to buy more than one of the products.
                </p>
            </div>
        </div>
    );
};
