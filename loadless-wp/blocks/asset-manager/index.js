/**
 * Gutenberg Block Editor Script for LoadLess WP Asset Manager
 *
 * @package LoadLessWP
 * @since 1.0.0
 */

( function( wp ) {
    const { registerBlockType } = wp.blocks;
    const { useSelect } = wp.data;
    const { InspectorControls, RichText } = wp.blockEditor;
    const { PanelBody, ToggleControl, SelectControl } = wp.components;
    const { __ } = wp.i18n;

    registerBlockType( 'loadless-wp/asset-manager', {
        edit: function( { attributes, setAttributes } ) {
            const { postId, showLink } = attributes;

            const currentPostId = useSelect( ( select ) => {
                return select( 'core/editor' )?.getCurrentPostId() || 0;
            }, [] );

            const displayPostId = postId > 0 ? postId : currentPostId;

            return [
                <InspectorControls key="inspector">
                    <PanelBody title={ __( 'Asset Manager Settings', 'loadless-wp' ) }>
                        <ToggleControl
                            label={ __( 'Show Admin Link', 'loadless-wp' ) }
                            checked={ showLink }
                            onChange={ ( value ) => setAttributes( { showLink: value } ) }
                        />
                    </PanelBody>
                </InspectorControls>,
                <div key="editor" className="loadless-wp-block-editor">
                    <h3>{ __( 'LoadLess WP Asset Manager', 'loadless-wp' ) }</h3>
                    <p>
                        { displayPostId > 0
                            ? sprintf(
                                  __( 'Displaying assets for Post ID: %d', 'loadless-wp' ),
                                  displayPostId
                              )
                            : __( 'No post ID available.', 'loadless-wp' )
                        }
                    </p>
                    <p className="loadless-wp-preview-note">
                        { __( 'Preview shows disabled assets count. View the published page to see actual output.', 'loadless-wp' ) }
                    </p>
                </div>
            ];
        },

        save: function() {
            // Dynamic block - return null.
            return null;
        }
    } );
} )( window.wp );
