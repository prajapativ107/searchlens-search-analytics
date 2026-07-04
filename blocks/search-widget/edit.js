/* global wp */
( function () {
	'use strict';

	var el                = wp.element.createElement;
	var Fragment          = wp.element.Fragment;
	var blockEditor       = wp.blockEditor || wp.editor;
	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps     = blockEditor.useBlockProps;
	var PanelBody         = wp.components.PanelBody;
	var ToggleControl     = wp.components.ToggleControl;
	var SelectControl     = wp.components.SelectControl;
	var __                = wp.i18n.__;

	var iconMarkup = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg"><path fill="currentColor" d="M10.5 4a6.5 6.5 0 1 0 4.02 11.62l4.43 4.43 1.41-1.41-4.43-4.43A6.5 6.5 0 0 0 10.5 4Zm0 2a4.5 4.5 0 1 1 0 9 4.5 4.5 0 0 1 0-9Z"/></svg>';

	function Edit( props ) {
		var attributes    = props.attributes;
		var setAttributes = props.setAttributes;
		var blockProps    = useBlockProps(
			{
				className: 'searchlens-search-widget searchlens-search-widget--editor',
			}
		);

		var controls = el(
			InspectorControls,
			null,
			el(
				PanelBody,
				{ title: __( 'Search Settings', 'searchlens-search-analytics' ) },
				el(
					SelectControl,
					{
						label: __( 'Open Mode', 'searchlens-search-analytics' ),
						value: attributes.openMode,
						options: [
						{ label: __( 'Dropdown', 'searchlens-search-analytics' ), value: 'dropdown' },
						{ label: __( 'Modal', 'searchlens-search-analytics' ), value: 'modal' },
						{ label: __( 'Slide Down', 'searchlens-search-analytics' ), value: 'slide-down' },
						],
						onChange: function ( value ) {
							setAttributes( { openMode: value } );
						},
					}
				),
				el(
					ToggleControl,
					{
						label: __( 'Show Label', 'searchlens-search-analytics' ),
						checked: attributes.showLabel,
						onChange: function ( value ) {
							setAttributes( { showLabel: value } );
						},
					}
				)
			)
		);

		var preview = el(
			'div',
			blockProps,
			el(
				'button',
				{ type: 'button', className: 'searchlens-search-toggle', 'aria-expanded': 'true' },
				el(
					'span',
					{
						className: 'searchlens-search-toggle-icon',
						'aria-hidden': 'true',
						dangerouslySetInnerHTML: { __html: iconMarkup },
					}
				),
				attributes.showLabel ? el( 'span', { className : 'searchlens-search-toggle-label' }, __( 'Search', 'searchlens-search-analytics' ) ) : null
			),
			el(
				'div',
				{ className: 'searchlens-search-popup' },
				el(
					'div',
					{ className: 'searchlens-search-panel' },
					el(
						'div',
						{ className: 'searchlens-search-preview' },
						__( 'SearchLens Search Form Preview', 'searchlens-search-analytics' )
					)
				)
			)
		);

		return el( Fragment, null, controls, preview );
	}

	wp.blocks.registerBlockType(
		'searchlens/search-widget',
		{
			edit: Edit,
			save: function () {
				return null;
			},
		}
	);
}() );