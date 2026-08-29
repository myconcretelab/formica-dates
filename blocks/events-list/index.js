( function ( blocks, blockEditor, components, element, i18n, serverSideRender ) {
	'use strict';

	const { registerBlockType } = blocks;
	const { InspectorControls, useBlockProps } = blockEditor;
	const { PanelBody, RangeControl, SelectControl, ToggleControl } = components;
	const { createElement: el, Fragment } = element;
	const { __ } = i18n;
	const ServerSideRender = serverSideRender;

	registerBlockType( 'formica/events-list', {
		edit: function ( props ) {
			const attributes = props.attributes;
			const setAttributes = props.setAttributes;
			const blockProps = useBlockProps( { className: 'formica-dates-editor' } );
			const displaysStages = 'stages' === attributes.contentType;

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Affichage du programme', 'formica-dates' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Contenu', 'formica-dates' ),
							value: attributes.contentType,
							options: [
								{ label: __( 'Spectacles', 'formica-dates' ), value: 'events' },
								{ label: __( 'Stages', 'formica-dates' ), value: 'stages' },
							],
							onChange: function ( contentType ) { setAttributes( { contentType: contentType } ); },
						} ),
						el( SelectControl, {
							label: __( 'Période', 'formica-dates' ),
							value: attributes.mode,
							options: [
								{ label: __( 'Dates à venir', 'formica-dates' ), value: 'upcoming' },
								{ label: __( 'Dates passées', 'formica-dates' ), value: 'past' },
							],
							onChange: function ( mode ) { setAttributes( { mode: mode } ); },
						} ),
						el( SelectControl, {
							label: __( 'Présentation', 'formica-dates' ),
							value: attributes.layout,
							options: [
								{ label: __( 'Détaillée', 'formica-dates' ), value: 'detailed' },
								{ label: __( 'Compacte', 'formica-dates' ), value: 'compact' },
							],
							onChange: function ( layout ) { setAttributes( { layout: layout } ); },
						} ),
						el( RangeControl, {
							label: displaysStages ? __( 'Nombre de stages', 'formica-dates' ) : __( 'Nombre de spectacles', 'formica-dates' ),
							value: attributes.postsToShow,
							min: 1,
							max: 50,
							onChange: function ( postsToShow ) { setAttributes( { postsToShow: postsToShow } ); },
						} ),
						el( ToggleControl, {
							label: displaysStages ? __( 'Afficher l’inscription', 'formica-dates' ) : __( 'Afficher la réservation', 'formica-dates' ),
							checked: attributes.showBooking,
							disabled: 'compact' === attributes.layout,
							onChange: function ( showBooking ) { setAttributes( { showBooking: showBooking } ); },
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: 'formica/events-list',
						attributes: attributes,
					} )
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )(
	window.wp.blocks,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.element,
	window.wp.i18n,
	window.wp.serverSideRender
);
