( function( blocks, element, i18n, serverSideRender ) {
	'use strict';

	var el = element.createElement;
	var __ = i18n.__;
	var ServerSideRender = serverSideRender;
	var templates = window.wpfaeventBlocks || [];

	templates.forEach(
		function( template ) {
			blocks.registerBlockType(
				template.name,
				{
					apiVersion: 2,
					title: template.title,
					icon: 'calendar-alt',
					category: 'widgets',
					description: __( 'Display WPFA event content.', 'wpfaevent' ),
					edit: function() {
						return el(
							'div',
							{ className: 'wpfaevent-block-preview' },
							ServerSideRender ? el( ServerSideRender, { block: template.name } ) : template.title
						);
					},
					save: function() {
						return null;
					},
				}
			);
		}
	);
} )( window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.serverSideRender );
