/**
 * App navigation behaviour — deliberately thin.
 *
 * Nextcloud core renders and styles plain `#app-navigation`:
 *   - `li.collapsible` + `button.collapse` + `open` is core's own collapse
 *     contract (see core/css/apps.css), the app only flips the state;
 *   - `body.snapjs-left` + core's `#app-navigation-toggle` is core's mobile
 *     drawer contract (see core/css/mobile.css), the app only flips the class.
 *
 * No roving tabindex, no custom drawer markup: links keep the browser's normal
 * tab order, which is what Nextcloud's own navigation does.
 *
 * @license AGPL-3.0-or-later
 */
(function () {
	'use strict';

	const DESKTOP_MQ = '(min-width: 1024px)';

	let toggleButton = null;
	let nav = null;

	function isDesktop() {
		return window.matchMedia(DESKTOP_MQ).matches;
	}

	function setDrawerOpen(open) {
		document.body.classList.toggle('snapjs-left', open);
		if (toggleButton) {
			toggleButton.setAttribute('aria-expanded', open ? 'true' : 'false');
			const openLabel = toggleButton.getAttribute('data-aria-label-open');
			const closeLabel = toggleButton.getAttribute('data-aria-label-close');
			if (open && closeLabel) {
				toggleButton.setAttribute('aria-label', closeLabel);
			} else if (!open && openLabel) {
				toggleButton.setAttribute('aria-label', openLabel);
			}
		}
	}

	function isDrawerOpen() {
		return document.body.classList.contains('snapjs-left');
	}

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

	function setupDrawer() {
		if (!toggleButton) {
			return;
		}
		toggleButton.addEventListener('click', () => {
			setDrawerOpen(!isDrawerOpen());
		});

		// Following a link closes the drawer, like core's mobile navigation.
		nav.addEventListener('click', (event) => {
			const link = event.target.closest('a[href]');
			if (link && !isDesktop()) {
				setDrawerOpen(false);
			}
		});

		document.addEventListener('keydown', (event) => {
			if (event.key === 'Escape' && isDrawerOpen()) {
				setDrawerOpen(false);
				toggleButton.focus();
			}
		});

		// Leaving the mobile breakpoint must not strand the drawer open.
		const desktop = window.matchMedia(DESKTOP_MQ);
		const onChange = (event) => {
			if (event.matches) {
				setDrawerOpen(false);
			}
		};
		if (typeof desktop.addEventListener === 'function') {
			desktop.addEventListener('change', onChange);
		} else if (typeof desktop.addListener === 'function') {
			desktop.addListener(onChange);
		}
	}

	function init() {
		nav = document.getElementById('app-navigation');
		if (!nav) {
			return;
		}
		toggleButton = document.getElementById('app-navigation-toggle');
		setupCollapse();
		setupDrawer();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
