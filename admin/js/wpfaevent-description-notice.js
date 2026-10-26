/**
 * Shows the Eventyay description sync status as a block editor notice.
 *
 * The notice sits at the top of the editing area and offers "Restore from
 * Eventyay", which uses the nonce-protected admin-post restore URL.
 *
 * @param {Object} wp     WordPress script globals.
 * @param {Object} config Notice settings localized by the event meta box class.
 */
(function (wp, config) {
	'use strict';

	if (!wp || !wp.data || !config || !config.note || !config.restoreUrl) {
		return;
	}

	const i18n = config.i18n || {};

	const hasUnsavedChanges = function () {
		const editor = wp.data.select('core/editor');

		return !!(
			editor &&
			editor.isEditedPostDirty &&
			editor.isEditedPostDirty()
		);
	};

	const restore = function () {
		const message = hasUnsavedChanges()
			? i18n.confirmUnsaved
			: i18n.confirm;

		// eslint-disable-next-line no-alert -- Same confirmation as the meta box.
		if (window.confirm(message)) {
			window.location.assign(config.restoreUrl);
		}
	};

	wp.data.dispatch('core/notices').createNotice('warning', config.note, {
		id: 'wpfaevent-eventyay-description',
		isDismissible: true,
		actions: [
			{
				label: i18n.restore,
				onClick: restore,
			},
		],
	});
})(window.wp, window.wpfaeventDescriptionNotice);
