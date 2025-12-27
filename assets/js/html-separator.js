(function() {
    const { registerBlockType } = wp.blocks;
    const { useBlockProps } = wp.blockEditor;
    const { createElement, RawHTML } = wp.element;

    // The "code" icon SVG used by all UAGB separators on this site.
    const CODE_ICON_SVG = '<svg xmlns="https://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M414.8 40.79L286.8 488.8C281.9 505.8 264.2 515.6 247.2 510.8C230.2 505.9 220.4 488.2 225.2 471.2L353.2 23.21C358.1 6.216 375.8-3.624 392.8 1.232C409.8 6.087 419.6 23.8 414.8 40.79H414.8zM518.6 121.4L630.6 233.4C643.1 245.9 643.1 266.1 630.6 278.6L518.6 390.6C506.1 403.1 485.9 403.1 473.4 390.6C460.9 378.1 460.9 357.9 473.4 345.4L562.7 256L473.4 166.6C460.9 154.1 460.9 133.9 473.4 121.4C485.9 108.9 506.1 108.9 518.6 121.4V121.4zM166.6 166.6L77.25 256L166.6 345.4C179.1 357.9 179.1 378.1 166.6 390.6C154.1 403.1 133.9 403.1 121.4 390.6L9.372 278.6C-3.124 266.1-3.124 245.9 9.372 233.4L121.4 121.4C133.9 108.9 154.1 108.9 166.6 121.4C179.1 133.9 179.1 154.1 166.6 166.6V166.6z"></path></svg>';

    registerBlockType( 'uagb/separator', {
        apiVersion: 3,
        title: 'HTML Separator',
        icon: 'minus',
        category: 'design',
        supports: {
            html: false,
            reusable: false,
        },
        attributes: {
            block_id: { type: 'string', default: '' },
            elementType: { type: 'string', default: 'icon' },
            separatorIcon: { type: 'string', default: 'code' },
        },
        edit: function( { attributes } ) {
            const blockProps = useBlockProps( {
                className: 'wp-block-uagb-separator wp-block-uagb-separator--icon',
                style: { textAlign: 'center' },
            } );
            const lineStyle = {
                flexGrow: 1,
                height: 0,
                borderTop: '1px solid #333',
            };
            return createElement(
                'div',
                blockProps,
                createElement(
                    'div',
                    {
                        className: 'wp-block-uagb-separator__inner',
                        style: { display: 'flex', alignItems: 'center', justifyContent: 'center' },
                    },
                    createElement( 'div', { style: lineStyle } ),
                    createElement(
                        'div',
                        { className: 'wp-block-uagb-separator-element', style: { width: '30px', height: '30px', margin: '0 10px' } },
                        createElement( RawHTML, null, CODE_ICON_SVG )
                    ),
                    createElement( 'div', { style: lineStyle } )
                )
            );
        },
        save: function( { attributes } ) {
            const { block_id } = attributes;
            return createElement(
                'div',
                { className: `wp-block-uagb-separator uagb-block-${ block_id } wp-block-uagb-separator--icon` },
                createElement(
                    'div',
                    { className: 'wp-block-uagb-separator__inner', style: '--my-background-image:' },
                    createElement(
                        'div',
                        { className: 'wp-block-uagb-separator-element' },
                        createElement( RawHTML, null, CODE_ICON_SVG )
                    )
                )
            );
        },
        deprecated: [
            {
                attributes: {
                    block_id: { type: 'string', default: '' },
                    elementType: { type: 'string', default: 'icon' },
                    separatorIcon: { type: 'string', default: 'code' },
                },
                save: function( { attributes } ) {
                    const { block_id } = attributes;
                    return createElement(
                        'div',
                        { className: `wp-block-uagb-separator uagb-block-${ block_id } wp-block-uagb-separator--icon` },
                        createElement(
                            'div',
                            { className: 'wp-block-uagb-separator__inner', style: '--my-background-image:' },
                            createElement(
                                'div',
                                { className: 'wp-block-uagb-separator-element' },
                                createElement( RawHTML, null, CODE_ICON_SVG )
                            )
                        )
                    );
                },
            },
        ],
    } );
})();
