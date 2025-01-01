import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { Edit } from './edit';
import metadata from './block.json';

registerBlockType(metadata, {
    attributes: {
        deliveryDate: {
            type: 'string', // Adjust the type as necessary
            default: '', // Default value for the delivery date attribute
        },
        // Other attributes...
    },
    icon: {
        src: (
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#000000">
                <path d="M13 0H3V2H13V0Z" fill="#000000" />
                <path d="M2 4H14V6H2V4Z" fill="#000000" />
                <path d="M1 8H15V15H1V8Z" fill="#000000" />
            </svg>
        ),
        foreground: '#874FB9', // Adjust the color as needed
    },
    edit: Edit,
});
