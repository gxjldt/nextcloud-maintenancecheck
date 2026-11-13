/**
 * App navigation behaviour — deliberately thin.
 *
 * Nextcloud core renders and styles plain `#app-navigation`:
 *   - `li.collapsible` + `button.collapse` + `open` is core's own collapse
 *     contract (see core/css/apps.css), the app only flips the state.
 *
 * No drawer: navigation.css keeps the sidebar in flow at every width, so the
 * mobile toggle core would need is not part of this shell.
 *
 * @license AGPL-3.0-or-later
 */
(function () {
	'use strict';

	function setupCollapse() {
		nav.querySelectorAll('li.collapsible > button.collapse').forEach((button) => {
			button.addEventListener('click', (event) => {
				event.preventDefault();
				event.stopPropagation();
				const group = button.closest('li.collapsible');
				if (!group) {
					return;
				}
				const open = !group.classList.contains('open');
				group.classList.toggle('open', open);
				button.setAttribute('aria-expanded', open ? 'true' : 'false');
			});
		});
	}

	function init() {
		if (!document.getElementById('app-navigation')) {
			return;
		}
		setupCollapse();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
