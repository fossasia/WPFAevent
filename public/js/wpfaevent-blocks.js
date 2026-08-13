(function (blocks, element, i18n, serverSideRender) {
	'use strict';

	const el = element.createElement;
	const __ = i18n.__;
	const ServerSideRender = serverSideRender;
	const templates = window.wpfaeventBlocks || [];

	templates.forEach(function (template) {
		blocks.registerBlockType(template.name, {
			apiVersion: 2,
			title: template.title,
			icon: 'calendar-alt',
			category: 'widgets',
			description: __('Display WPFA event content.', 'wpfaevent'),
			supports: {
				align: ['wide', 'full'],
			},
			edit() {
				return el(
					'div',
					{ className: 'wpfaevent-block-preview' },
					ServerSideRender
						? el(ServerSideRender, { block: template.name })
						: template.title
				);
			},
			save() {
				return null;
			},
		});
	});
})(
	window.wp.blocks,
	window.wp.element,
	window.wp.i18n,
	window.wp.serverSideRender
);
