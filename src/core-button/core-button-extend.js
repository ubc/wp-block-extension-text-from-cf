const { createHigherOrderComponent } = wp.compose;
const { Fragment, useEffect, useState, useRef } = wp.element;
const { InspectorControls } = wp.blockEditor;
const { PanelBody, ToggleControl, PanelRow, TextControl, SelectControl } = wp.components;
const { isURL } = wp.url;

function addAdditionalAttribute( settings, name ) {
    if ( 'core/button' !== name ) {
        return settings;
    }

    return {
        ...settings,
        attributes: {
            ...settings.attributes,
            CFToText_enable: {
                type: 'boolean',
                default: false
            },
            CFToText_key: {
                type: 'string',
                default: ''
            }
        },
        usesContext: [ 'postId' ]
    }
}

wp.hooks.addFilter(
    'blocks.registerBlockType',
    'ubc/extension/text-from-cf/button/add-attributes',
    addAdditionalAttribute
);

/**
 * Add additional controls to core/post-template block.
 */
const withInspectorControls = createHigherOrderComponent( ( BlockEdit ) => {

    return ( props ) => {
        const { name, attributes, setAttributes, context } = props;
        const { CFToText_enable, CFToText_key } = attributes;
        const { postId } = context;
        const [ metaKeys, setMetaKeys ] = useState([]);
    
        if( 'core/button' !== name ) {
            return <BlockEdit { ...props } />;
        }

        useEffect(() => {
            const metaKeys = async() => {
    
                const data = new FormData();
    
                data.append( 'action', 'wp_text_from_cf_get_meta_keys' );
                data.append( 'nonce', wp_text_from_cf.nonce );
            
                const response = await fetch( ajaxurl, {
                  method: "POST",
                  credentials: 'same-origin',
                  body: data
                } );
                const responseJson = await response.json();
                
                if( responseJson.success ) {
                    setMetaKeys( responseJson.data );
                }
            };
    
            metaKeys();
        }, []);

        useEffect(() => {
            if ( false === CFToText_enable ) {
                return;
            }

            setCFValue();
        }, [ CFToText_key, CFToText_enable ]);

        const setCFValue = async() => {
            const data = new FormData();
    
            data.append( 'action', 'text_to_cf_get_custom_field_value' );
            data.append( 'meta_key', CFToText_key );
            data.append( 'post_id', postId );
            data.append( 'nonce', wp_text_from_cf.nonce );
        
            const response = await fetch( ajaxurl, {
              method: "POST",
              credentials: 'same-origin',
              body: data
            } );
            const responseJson = await response.json();
            
            if( false === responseJson.success ) {
                return;
            }

            setAttributes({
                text: responseJson.data
            });
        }

        return (
            <Fragment>
                <BlockEdit { ...props } />
                <InspectorControls>
                    <PanelBody title="Text Settings" initialOpen={ true }>
                        <ToggleControl
                            label="Enable text to custom field"
                            checked={ CFToText_enable }
                            onChange={ () => {
                                setAttributes({
                                    CFToText_enable: ! CFToText_enable,
                                });
                            } }
                        />
                        { CFToText_enable ? <SelectControl
                            label="Text to Custom Field"
                            value={ CFToText_key }
                            options={ metaKeys.map(key => {
                                return {
                                    label: key,
                                    value: key
                                };
                            }) }
                            onChange={ ( newMetaKey ) => {
                                setAttributes({
                                    CFToText_key: newMetaKey
                                });
                            } }
                            __nextHasNoMarginBottom
                        />
                        : '' }
                    </PanelBody>
                </InspectorControls>
            </Fragment>
        );
    };
}, 'withInspectorControl' );

wp.hooks.addFilter(
    'editor.BlockEdit',
    'ubc/extension/text-from-cf/button/add-controls',
    withInspectorControls
);