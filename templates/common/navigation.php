<?php

declare(strict_types=1);

use OCA\MaintenanceCheck\Support\IconCatalog;
use OCP\Util;

/**
 * App navigation — plain Nextcloud navigation markup.
 *
 * Row metrics, hover, active and collapse states all come from Nextcloud core
 * (#app-navigation:not(.vue) + .app-navigation-personal in core/css/apps.css).
 * This template emits structure only; css/navigation.css keeps the inline icon
 * glyph alignment and nothing else.
 *
 * @var \OCP\IL10N $l
 * @var string $pageId
 * @var bool $isAppAdmin
 * @var bool $isOffice
 * @var string $urlsJson
 */

Util::addScript('maintenancecheck', 'common/navigation');
// Soft keyboard: keep focused notes/inputs above the IME on phones.
Util::addScript('maintenancecheck', 'common/keep-focused-visible');

$navUrls = json_decode($urlsJson, true)['pages'] ?? [];

$activeNavId = match ($pageId) {
	'customer-detail' => 'customers',
	'equipment-detail' => 'equipment',
	'work-order-detail' => 'work-orders',
	default => str_starts_with($pageId, 'settings-') ? 'settings' : $pageId,
};

$isDue = $activeNavId === 'due';
$isVisits = $activeNavId === 'visits';
$isWorkOrders = $activeNavId === 'work-orders';
$isDispatch = $activeNavId === 'dispatch';
$isTours = $activeNavId === 'tours';
$isKpi = $activeNavId === 'kpi';
$isExceptions = $activeNavId === 'exceptions';
$isCustomers = $activeNavId === 'customers';
$isEquipment = $activeNavId === 'equipment';
$isCatalogs = $activeNavId === 'catalogs';
$isSettings = $activeNavId === 'settings';
$activeSettingsSection = str_starts_with($pageId, 'settings-')
	? substr($pageId, strlen('settings-'))
	: '';
$settingsSectionNav = is_array($navUrls['settingsSections'] ?? null) ? $navUrls['settingsSections'] : [];
$isPlanning = $isDispatch || $isTours || $isKpi || $isExceptions;
$isRegister = $isCustomers || $isEquipment || $isCatalogs;

$navIcon = static fn (string $name): string => IconCatalog::render($name, 'nav-entry-icon-svg');

/** First-level entry that links straight to a page. */
$navEntry = static function (string $href, string $label, string $hint, string $icon, bool $active) use ($navIcon): void {
	?>
	<li<?php if ($active): ?> class="active"<?php endif; ?>>
		<a href="<?php p($href); ?>"
			class="nav-entry-link"
			title="<?php p($hint); ?>"
			<?php if ($active): ?>aria-current="page"<?php endif; ?>>
			<span class="nav-entry-icon" aria-hidden="true"><?php print_unescaped($navIcon($icon)); ?></span>
			<span class="nav-entry-name"><?php p($label); ?></span>
		</a>
	</li>
	<?php
};

/** Second-level entry — Nextcloud core indents these, so no icon. */
$navSubEntry = static function (string $href, string $label, string $hint, bool $active): void {
	?>
	<li<?php if ($active): ?> class="active"<?php endif; ?>>
		<a href="<?php p($href); ?>"
			class="nav-entry-link"
			title="<?php p($hint); ?>"
			<?php if ($active): ?>aria-current="page"<?php endif; ?>>
			<span class="nav-entry-name"><?php p($label); ?></span>
		</a>
	</li>
	<?php
};

/**
 * Collapsible group — core renders the chevron itself: it draws the triangle on
 * `li.collapsible::before` and hides `> ul` unless the `li` carries `open`.
 * `button.collapse` is core's hit area for that state, so the app adds no
 * styling of its own.
 */
$navGroup = static function (string $id, string $href, string $label, string $hint, string $icon, bool $open, callable $children) use ($navIcon): void {
	?>
	<li class="collapsible<?php if ($open): ?> open<?php endif; ?>">
		<a href="<?php p($href); ?>" class="nav-entry-link" title="<?php p($hint); ?>">
			<span class="nav-entry-icon" aria-hidden="true"><?php print_unescaped($navIcon($icon)); ?></span>
			<span class="nav-entry-name"><?php p($label); ?></span>
		</a>
		<button type="button"
			class="collapse"
			aria-expanded="<?php p($open ? 'true' : 'false'); ?>"
			aria-controls="<?php p($id); ?>"
			aria-label="<?php p($label); ?>"></button>
		<ul id="<?php p($id); ?>">
			<?php $children(); ?>
		</ul>
	</li>
	<?php
};
?>
<div id="maintenancecheck-app" class="maintenancecheck-app">
	<a href="#app-navigation" class="skip-link mn-skip-link--nav"><?php p($l->t('Skip to app navigation')); ?></a>

	<nav id="app-navigation"
		class="app-navigation-personal"
		aria-label="<?php p($l->t('Main navigation')); ?>">
		<ul>
			<?php
			$navEntry(
				(string)($navUrls['due'] ?? '#'),
				$l->t('Due board'),
				$l->t('Due board: What needs a visit'),
				'layout-grid',
				$isDue
			);
			$navEntry(
				(string)($navUrls['visits'] ?? '#'),
				$l->t('Visits'),
				$l->t('Visits: History and filters'),
				'calendar-check',
				$isVisits
			);
			$navEntry(
				(string)($navUrls['workOrders'] ?? '#'),
				$l->t('Work orders'),
				$l->t('Work orders: Planned and corrective work'),
				'clipboard-list',
				$isWorkOrders
			);

			if ($isOffice || $isAppAdmin) {
				$navGroup(
					'app-navigation-planning',
					(string)($navUrls['dispatch'] ?? ($navUrls['tours'] ?? '#')),
					$l->t('Planning'),
					$l->t('Assign jobs and day tours'),
					'kanban',
					$isPlanning,
					static function () use ($navSubEntry, $navUrls, $l, $isDispatch, $isTours, $isExceptions, $isKpi): void {
						$navSubEntry((string)($navUrls['dispatch'] ?? '#'), $l->t('Dispatch'), $l->t('Dispatch: Assign open work orders'), $isDispatch);
						$navSubEntry((string)($navUrls['tours'] ?? '#'), $l->t('Day tours'), $l->t('Day tours: Stop-by-stop plans'), $isTours);
						$navSubEntry((string)($navUrls['exceptions'] ?? '#'), $l->t('Exceptions'), $l->t('Exceptions: Blocked and overdue work'), $isExceptions);
						$navSubEntry((string)($navUrls['kpi'] ?? '#'), $l->t('Ops KPI'), $l->t('Ops KPI: Compliance and overdue snapshot'), $isKpi);
					}
				);
			} else {
				$navEntry(
					(string)($navUrls['tours'] ?? '#'),
					$l->t('My tour'),
					$l->t('Your stops today'),
					'route',
					$isTours
				);
			}

			$navGroup(
				'app-navigation-register',
				(string)($navUrls['customers'] ?? '#'),
				$l->t('Register'),
				$l->t('Customers, equipment, catalogs'),
				'list-checks',
				$isRegister,
				static function () use ($navSubEntry, $navUrls, $l, $isCustomers, $isEquipment, $isCatalogs): void {
					$navSubEntry((string)($navUrls['customers'] ?? '#'), $l->t('Customers'), $l->t('Customers: Sites you service'), $isCustomers);
					$navSubEntry((string)($navUrls['equipment'] ?? '#'), $l->t('Equipment'), $l->t('Equipment: Units you maintain'), $isEquipment);
					$navSubEntry((string)($navUrls['catalogs'] ?? '#'), $l->t('Catalogs'), $l->t('Catalogs: Types and intervals'), $isCatalogs);
				}
			);

			if ($isAppAdmin) {
				$navGroup(
					'app-navigation-settings',
					(string)($navUrls['settings'] ?? '#'),
					$l->t('Settings'),
					$l->t('Access and license'),
					'settings',
					$isSettings,
					static function () use ($navSubEntry, $settingsSectionNav, $l, $activeSettingsSection): void {
						$settingsLabels = [
							'access' => [$l->t('Access'), $l->t('Who may open the app')],
							'roles' => [$l->t('Roles'), $l->t('Office vs technicians')],
							'inventory' => [$l->t('Inventory stock issue'), $l->t('Stock on finish')],
							'policies' => [$l->t('Work policies'), $l->t('Checklist and capacity rules')],
							'capacity' => [$l->t('Daily capacity'), $l->t('Minutes per technician')],
							'license' => [$l->t('License & mobile'), $l->t('Keys and seats')],
							'support' => [$l->t('Support us'), $l->t('Sponsoring and help')],
						];
						foreach ($settingsLabels as $sid => $pair) {
							$navSubEntry(
								(string)($settingsSectionNav[$sid] ?? '#'),
								$pair[0],
								$pair[1],
								$activeSettingsSection === $sid
							);
						}
					}
				);
			}
			?>
		</ul>
	</nav>
