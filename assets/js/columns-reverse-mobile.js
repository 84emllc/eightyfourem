/**
 * Columns Reverse on Mobile
 * Adds a toggle to the Columns block to reverse column order on mobile devices
 *
 * @package EightyFourEM
 */

( function ( wp ) {
	'use strict';

	var addFilter = wp.hooks.addFilter;
	var createHigherOrderComponent = wp.compose.createHigherOrderComponent;
	var createElement = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var ToggleControl = wp.components.ToggleControl;
	var __ = wp.i18n.__;

	/**
	 * Add reverseMobile attribute to core/columns block
	 */
	function addReverseMobileAttribute( settings, name ) {
		if ( name !== 'core/columns' ) {
			return settings;
		}

		return Object.assign( {}, settings, {
			attributes: Object.assign( {}, settings.attributes, {
				reverseMobile: {
					type: 'boolean',
					default: false,
				},
			} ),
		} );
	}

	addFilter(
		'blocks.registerBlockType',
		'eightyfourem/columns-reverse-mobile-attribute',
		addReverseMobileAttribute
	);

	/**
	 * Add toggle control to Columns block inspector
	 */
	var withReverseMobileControl = createHigherOrderComponent( function ( BlockEdit ) {
		return function ( props ) {
			if ( props.name !== 'core/columns' ) {
				return createElement( BlockEdit, props );
			}

			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var reverseMobile = attributes.reverseMobile;

			return createElement(
				Fragment,
				null,
				createElement( BlockEdit, props ),
				createElement(
					InspectorControls,
					null,
					createElement(
						PanelBody,
						null,
						createElement( ToggleControl, {
							label: __( 'Reverse on mobile', 'eightyfourem' ),
							help: __( 'Reverse column order when stacked on mobile devices.', 'eightyfourem' ),
							checked: !! reverseMobile,
							onChange: function ( value ) {
								setAttributes( { reverseMobile: value } );
							},
						} )
					)
				)
			);
		};
	}, 'withReverseMobileControl' );

	addFilter(
		'editor.BlockEdit',
		'eightyfourem/columns-reverse-mobile-control',
		withReverseMobileControl
	);

	/**
	 * Add CSS class to saved block content
	 */
	function addReverseMobileClass( extraProps, blockType, attributes ) {
		if ( blockType.name !== 'core/columns' ) {
			return extraProps;
		}

		if ( attributes.reverseMobile ) {
			extraProps.className = extraProps.className
				? extraProps.className + ' is-reverse-on-mobile'
				: 'is-reverse-on-mobile';
		}

		return extraProps;
	}

	addFilter(
		'blocks.getSaveContent.extraProps',
		'eightyfourem/columns-reverse-mobile-class',
		addReverseMobileClass
	);

	/**
	 * Add CSS class in editor preview
	 */
	var withReverseMobileEditorClass = createHigherOrderComponent( function ( BlockListBlock ) {
		return function ( props ) {
			if ( props.name !== 'core/columns' ) {
				return createElement( BlockListBlock, props );
			}

			var attributes = props.attributes;
			var reverseMobile = attributes.reverseMobile;

			if ( ! reverseMobile ) {
				return createElement( BlockListBlock, props );
			}

			var newProps = Object.assign( {}, props, {
				className: props.className
					? props.className + ' is-reverse-on-mobile'
					: 'is-reverse-on-mobile',
			} );

			return createElement( BlockListBlock, newProps );
		};
	}, 'withReverseMobileEditorClass' );

	addFilter(
		'editor.BlockListBlock',
		'eightyfourem/columns-reverse-mobile-editor-class',
		withReverseMobileEditorClass
	);

} )( window.wp );
