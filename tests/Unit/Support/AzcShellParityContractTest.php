<?php

declare(strict_types=1);

namespace OCA\MaintenanceCheck\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;

/** ArbeitszeitCheck shell parity contract for MaintenanceCheck. */
final class AzcShellParityContractTest extends TestCase
{
	private string $root;

	protected function setUp(): void
	{
		parent::setUp();
		$this->root = dirname(__DIR__, 3);
	}

	public function testShellFilesExist(): void
	{
		foreach ([
			'/css/common/tokens.css',
			'/css/common/app-layout.css',
			'/css/common/page-patterns.css',
			'/css/navigation.css',
			'/js/common/navigation.js',
			'/templates/common/page-start.php',
			'/templates/common/navigation.php',
			'/templates/common/page-end.php',
		] as $rel) {
			$this->assertFileExists($this->root . $rel, $rel);
		}
	}

	public function testNavigationUsesCollapsibleRegisterAndSettingsUnderpages(): void
	{
		$nav = (string)file_get_contents($this->root . '/templates/common/navigation.php');
		foreach (['app-navigation-personal', 'class="collapsible', 'class="collapse"', 'app-navigation-register', 'app-navigation-settings', 'nav-entry-name'] as $token) {
			$this->assertStringContainsString($token, $nav, $token);
		}
		// Bachus: Settings is nested under itself (Access…Support), never Administration.
		$this->assertStringNotContainsString('mn-admin-subnav', $nav);
		$this->assertStringContainsString("t('Access')", $nav);
		$this->assertStringContainsString("t('License & mobile')", $nav);
	}

	public function testPageChromeIsPlainNextcloudHeader(): void
	{
		$start = (string)file_get_contents($this->root . '/templates/common/page-start.php');
		foreach (['mn-breadcrumb', 'mn-page-header', 'mn-page-title', 'mn-page-actions', 'mn-live-region', 'mn-alert-region'] as $token) {
			$this->assertStringContainsString($token, $start, $token);
		}
		// Nextcloud page headings are a plain h1 plus its actions: no icon well,
		// no context strip, no second navigation control in the page body.
		$this->assertStringNotContainsString('mn-page-header__icon', $start);
		$this->assertStringNotContainsString('mn-scope-strip', $start);
		$this->assertStringNotContainsString('mn-nav-toggle', $start);
	}

	public function testAppLayoutUsesAzcFlexShell(): void
	{
		$layout = (string)file_get_contents($this->root . '/css/common/app-layout.css');
		$this->assertStringContainsString('#content.app-maintenancecheck', $layout);
		// Nextcloud core owns the navigation width (300px); the shell only stretches it.
		$this->assertMatchesRegularExpression('/flex:\s*0 0 auto/', $layout);
		$this->assertStringNotContainsString('flex: 0 0 280px', $layout);
		$this->assertStringNotContainsString('flex: 0 0 240px', $layout);
	}

	public function testNoPillRadiiRemainInAppCss(): void
	{
		$css = (string)file_get_contents($this->root . '/css/app.css');
		$this->assertStringNotContainsString('border-radius-pill', $css);
		$this->assertStringContainsString('AZ-PARITY-RADIUS-ENFORCER', $css);
		$this->assertFileExists($this->root . '/css/common/notification-surfaces.css');
	}

	public function testVisitsPageUsesFlatLiveToolbar(): void
	{
		$tpl = (string)file_get_contents($this->root . '/templates/visits.php');
		$this->assertStringContainsString('mn-visits-toolbar', $tpl);
		$this->assertStringContainsString('mn-filter-status-chips', $tpl);
		$this->assertStringContainsString('mn-filter-when-chips', $tpl);
		$this->assertStringContainsString('mn-list-section', $tpl);
		$this->assertStringContainsString('mn-sr-only', $tpl);
		$this->assertStringNotContainsString('mn-filter-panel', $tpl);
		$this->assertStringNotContainsString('mn-card__header', $tpl);
		$this->assertStringNotContainsString('mn-card__lead', $tpl);
		$this->assertStringNotContainsString('Apply filters', $tpl);
	}

	public function testListAndBoardTablesSitInFlatListSections(): void
	{
		foreach ([
			'templates/customers.php',
			'templates/equipment.php',
			'templates/work-orders.php',
			'templates/dispatch.php',
			'templates/tours.php',
			'templates/exceptions.php',
			'templates/catalogs.php',
			'templates/kpi.php',
			'templates/customer-detail.php',
			'templates/equipment-detail.php',
		] as $rel) {
			$tpl = (string)file_get_contents($this->root . '/' . $rel);
			$this->assertStringContainsString('mn-list-section', $tpl, $rel);
			$this->assertStringNotContainsString('mn-card--table-solo', $tpl, $rel);
			$this->assertStringNotContainsString('mn-card__header', $tpl, $rel);
			$this->assertStringNotContainsString('mn-card__lead', $tpl, $rel);
		}
		$css = (string)file_get_contents($this->root . '/css/app.css');
		$this->assertStringContainsString('.mn-table-toolbar', $css);
		$this->assertStringContainsString('.mn-exceptions-toolbar', $css);
	}

	/**
	 * Remaining cards keep one padding layer: a header band, then a body that
	 * pads itself — never a frame inside a frame.
	 */
	public function testStructuredCardsKeepSinglePaddingLayer(): void
	{
		$chrome = (string)file_get_contents($this->root . '/css/common/shell-chrome.css');
		$this->assertMatchesRegularExpression(
			'/#content\.app-maintenancecheck\s+#app-content\s+\.mn-card:has\(\.mn-card__header\)[\s\S]*?padding:\s*0/s',
			$chrome,
			'Structured cards must not double-pad (header band + body inset only)'
		);
		$this->assertMatchesRegularExpression(
			'/#content\.app-maintenancecheck\s+#app-content\s+\.mn-card__body--table\s*\{[^}]*padding:\s*var\(--mn-space-5/s',
			$chrome,
			'Default/headed table bodies keep space-5 inset'
		);
	}

	public function testListSectionIsFlatNotATableFrame(): void
	{
		$app = (string)file_get_contents($this->root . '/css/app.css');
		$this->assertMatchesRegularExpression(
			'/\.mn-list-section\s*>\s*\.mn-card__body--table[\s\S]*?padding:\s*0/s',
			$app,
			'list sections must be flush (the table itself is the surface)'
		);
		$this->assertMatchesRegularExpression(
			'/\.mn-list-section\s+\.mn-table-wrap[\s\S]*?border:\s*none/s',
			$app,
			'list sections must strip the wrap border to avoid box-in-a-box'
		);
		$chrome = (string)file_get_contents($this->root . '/css/common/shell-chrome.css');
		$this->assertMatchesRegularExpression(
			'/#content\.app-maintenancecheck\s+\.mn-table-wrap[\s\S]*?border:\s*0/s',
			$chrome,
			'Nextcloud tables sit on the page surface — the wrapper draws no box'
		);
	}

	public function testListToolbarsAreFlatRows(): void
	{
		$patterns = (string)file_get_contents($this->root . '/css/common/page-patterns.css');
		$this->assertMatchesRegularExpression(
			'/\.mn-toolbar\s*\{[^}]*align-items:\s*flex-end/s',
			$patterns,
			'List toolbars are a flat row (no panel card, no grid tracks)'
		);
		$this->assertStringNotContainsString('mn-filter-panel', $patterns);
		$this->assertStringNotContainsString('mn-filter-grid', $patterns);

		foreach ($this->filterListTemplates() as $rel) {
			$tpl = (string)file_get_contents($this->root . '/' . $rel);
			$this->assertStringNotContainsString('mn-filterbar', $tpl, $rel . ' must not use legacy boxed toolbar chrome');
			$this->assertStringNotContainsString('mn-filter-panel', $tpl, $rel . ' must not wrap filters in a panel card');
			$this->assertStringContainsString('class="mn-toolbar"', $tpl, $rel);
		}
	}

	/**
	 * @return list<string>
	 */
	private function filterListTemplates(): array
	{
		return [
			'templates/customers.php',
			'templates/equipment.php',
			'templates/work-orders.php',
		];
	}

	public function testListFiltersCarryNoCardChrome(): void
	{
		foreach ($this->filterListTemplates() as $rel) {
			$tpl = (string)file_get_contents($this->root . '/' . $rel);
			preg_match_all('/<form[^>]*\bmn-toolbar\b[^>]*>[\s\S]*?<\/form>/', $tpl, $blocks);
			$this->assertNotEmpty($blocks[0], $rel . ' missing flat toolbar');
			foreach ($blocks[0] as $i => $block) {
				$label = $rel . ' toolbar#' . $i;
				$this->assertStringNotContainsString('mn-card', $block, $label);
				$this->assertStringNotContainsString('mn-filter-panel', $block, $label);
			}
		}
	}

	public function testToastUsesAzcSemanticClasses(): void
	{
		$js = (string)file_get_contents($this->root . '/js/app.js');
		$this->assertStringContainsString("toast--' + azcKind", $js);
		$this->assertStringContainsString('modal-backdrop', $js);
		$this->assertMatchesRegularExpression("/azcKind === 'error'|kind === 'error'/", $js);
	}


	public function testNavigationLeavesActiveStateToCore(): void
	{
		$css = (string)file_get_contents($this->root . '/css/navigation.css');
		// Core paints the active row (solid --color-primary-element fill). The app
		// sheet must not re-declare row metrics, fills or chevrons.
		$this->assertStringNotContainsString('background-color: var(--color-primary-element)', $css);
		$this->assertStringNotContainsString('nav-item-inset', $css);
		$this->assertStringNotContainsString('linear-gradient', $css);
		$this->assertStringContainsString('nav-entry-icon', $css);
		$this->assertStringContainsString('nav-entry-name', $css);
	}

	public function testLayoutCssLeavesNavWidthToCore(): void
	{
		$layout = (string)file_get_contents($this->root . '/css/common/app-layout.css');
		// Core pins #app-navigation at 300px; the app must not narrow it per breakpoint.
		$this->assertStringNotContainsString('flex: 0 0 240px', $layout);
		$this->assertStringNotContainsString('flex: 0 0 280px', $layout);
		$this->assertMatchesRegularExpression('/flex:\s*0 0 auto/', $layout);
	}

	public function testShellHasNoArtificalContentMaxWidth(): void
	{
		$chrome = (string)file_get_contents($this->root . '/css/common/shell-chrome.css');
		$this->assertStringContainsString('max-width: none', $chrome);
		$app = (string)file_get_contents($this->root . '/css/app.css');
		$this->assertDoesNotMatchRegularExpression(
			'/#app-content-wrapper\.[a-z]+-shell[^{]*\{[^}]*max-width:\s*12[08]0px/s',
			$app
		);
	}


	public function testSkipLinkIsAbsolutelyPositionedOffscreen(): void
	{
		$chrome = (string)file_get_contents($this->root . '/css/common/shell-chrome.css');
		$this->assertStringContainsString('Skip links (AZ parity) — CRITICAL', $chrome);
		$this->assertMatchesRegularExpression(
			'/\.skip-link[^{]*\{[^}]*position:\s*absolute\s*!important/s',
			$chrome,
			'Nav skip-link MUST be absolute or it becomes a flex column and ruins layout'
		);
		$this->assertMatchesRegularExpression(
			'/\.skip-link[^{]*\{[^}]*left:\s*-9999px/s',
			$chrome
		);
		$nav = (string)file_get_contents($this->root . '/templates/common/navigation.php');
		$this->assertStringContainsString('skip-link', $nav);
		$this->assertStringContainsString('Skip to app navigation', $nav);
	}

	public function testTableChromeMatchesNextcloudListStyles(): void
	{
		$chrome = (string)file_get_contents($this->root . '/css/common/shell-chrome.css');
		$this->assertStringContainsString('Tables — Nextcloud list chrome', $chrome);
		$this->assertMatchesRegularExpression(
			'/th[^{]*\{[^}]*font-weight:\s*bold/s',
			$chrome
		);
		$this->assertMatchesRegularExpression(
			'/th[^{]*\{[^}]*padding:\s*var\(/s',
			$chrome
		);
		// Hairline separators on the page surface, no wrapper frame.
		$this->assertMatchesRegularExpression(
			'/td[^{]*\{[^}]*border-bottom:\s*1px solid var\(--color-border/s',
			$chrome
		);
		$this->assertMatchesRegularExpression(
			'/\.mn-table-wrap[\s\S]*?border:\s*0/s',
			$chrome
		);
		// Wrap must scroll — never clip overflow for sticky headers / mobile.
		$this->assertDoesNotMatchRegularExpression(
			'/\.mn-table-wrap[^{]*\{[^}]*overflow:\s*hidden/s',
			$chrome,
			'mn-table-wrap must use overflow-x:auto, not overflow:hidden'
		);
	}

	public function testListTemplatesUseFlatListSections(): void
	{
		foreach (['/templates/customers.php', '/templates/equipment.php', '/templates/visits.php', '/templates/work-orders.php'] as $rel) {
			$tpl = (string)file_get_contents($this->root . $rel);
			$this->assertStringContainsString('mn-list-section', $tpl, $rel);
			$this->assertStringNotContainsString('mn-card--table-solo', $tpl, $rel);
		}
	}

	public function testEveryCardHeaderUsesHeaderTextWrapper(): void
	{
		$files = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($this->root . '/templates', \FilesystemIterator::SKIP_DOTS)
		);
		foreach ($files as $file) {
			if (!$file->isFile() || $file->getExtension() !== 'php') {
				continue;
			}
			$tpl = (string)file_get_contents($file->getPathname());
			if (!str_contains($tpl, 'mn-card__header')) {
				continue;
			}
			preg_match_all('/<header class="mn-card__header[^"]*">([\s\S]*?)<\/header>/', $tpl, $blocks);
			foreach ($blocks[1] as $i => $inner) {
				$label = $file->getFilename() . ' header#' . $i;
				$this->assertStringContainsString('mn-card__header-text', $inner, $label);
			}
		}
	}

	public function testAppJsShipsTableOrCardsHelper(): void
	{
		$js = (string)file_get_contents($this->root . '/js/app.js');
		$this->assertStringContainsString('function tableOrCards(', $js);
		$this->assertStringContainsString('mn-table table table--hover mn-table--responsive', $js);
		$this->assertStringContainsString('tableOrCards: tableOrCards', $js);
		$this->assertStringContainsString('var loadSeq = 0', $js);
		$this->assertStringContainsString('function announceResults(', $js);
		$wo = (string)file_get_contents($this->root . '/js/work-order-pages.js');
		$this->assertStringContainsString('tableOrCards', $wo);
		$this->assertStringContainsString('mn-dispatch-job', $wo);
		$this->assertStringContainsString('var loadSeq = 0', $wo);
	}

	public function testCatalogsUseCardChromeNotBareSections(): void
	{
		$tpl = (string)file_get_contents($this->root . '/templates/catalogs.php');
		$this->assertStringContainsString('mn-list-section', $tpl);
		$this->assertStringContainsString('mn-table-toolbar', $tpl);
		$this->assertStringContainsString('mn-card__body--table', $tpl);
		$this->assertStringContainsString('mn-catalogs', $tpl);
		$this->assertStringContainsString('mn-catalogs-toolbar', $tpl);
		$this->assertStringContainsString('data-mn-catalog=', $tpl);
		$this->assertStringContainsString('mn-catalog-panel', $tpl);
		$this->assertStringNotContainsString('mn-catalogs__pair', $tpl);
		$this->assertStringNotContainsString('mn-section', $tpl);
		$this->assertStringNotContainsString('class="mn-columns"', $tpl);
		$this->assertStringNotContainsString('mn-card__header', $tpl);
		$this->assertStringNotContainsString('mn-card__lead', $tpl);
	}

	public function testDetailAndSettingsUseCardChromeWithoutDuplicateBreadcrumbs(): void
	{
		foreach (['customer-detail.php', 'equipment-detail.php'] as $file) {
			$tpl = (string)file_get_contents($this->root . '/templates/' . $file);
			$this->assertStringContainsString('mn-list-section', $tpl, $file);
			$this->assertStringNotContainsString('mn-section', $tpl, $file);
			$this->assertStringNotContainsString('<nav class="mn-breadcrumb"', $tpl, $file . ' must not duplicate shell breadcrumb');
		}
		$section = (string)file_get_contents($this->root . '/templates/settings-section.php');
		$this->assertStringContainsString('mn-settings', $section);
		$this->assertStringContainsString('parts/settings-subnav.php', $section);
		$this->assertStringNotContainsString('<nav class="mn-breadcrumb"', $section);
		$this->assertFileDoesNotExist($this->root . '/templates/settings.php');
		$access = (string)file_get_contents($this->root . '/templates/settings/access.php');
		$this->assertStringContainsString('mn-card', $access);
		$wo = (string)file_get_contents($this->root . '/templates/work-order-detail.php');
		$this->assertStringNotContainsString('<nav class="mn-breadcrumb"', $wo);
		$shell = (string)file_get_contents($this->root . '/templates/common/page-start.php');
		$this->assertStringContainsString("pageId === 'customer-detail'", $shell);
		$this->assertStringContainsString("pageId === 'equipment-detail'", $shell);
		$this->assertStringContainsString("pageId === 'work-order-detail'", $shell);
		$this->assertStringContainsString("str_starts_with(\$pageId, 'settings-')", $shell);
		$this->assertStringContainsString('mn-back-link', $shell);
		$this->assertStringContainsString('mn-back-settings', $shell);
	}

	public function testRequiredFieldsUseAriaRequired(): void
	{
		$js = (string)file_get_contents($this->root . '/js/app.js');
		$this->assertStringContainsString("input.setAttribute('aria-required', 'true')", $js);
		$this->assertStringContainsString("input.setAttribute('required', '')", $js);
		$this->assertStringContainsString('{label} (required)', $js);
		$this->assertStringNotContainsString('form-label--required mn-field__label--required', $js);
	}

	public function testDueBoardGuardsStaleLoads(): void
	{
		$js = (string)file_get_contents($this->root . '/js/app.js');
		$this->assertMatchesRegularExpression(
			'/function pageDue\(\)[\s\S]*?var loadSeq = 0;[\s\S]*?if \(seq !== loadSeq\)/',
			$js
		);
		$this->assertStringContainsString('function dueBucketTable(', $js);
		$this->assertStringNotContainsString('function visitCard(', $js);
		$this->assertStringNotContainsString('mn-visit-card', $js);
		$tpl = (string)file_get_contents($this->root . '/templates/due.php');
		$this->assertStringContainsString('mn-card__body--table', $tpl);
		$this->assertStringContainsString('data-bucket-list="overdue"', $tpl);
	}

	public function testOpsBoardsUseTableChrome(): void
	{
		$wo = (string)file_get_contents($this->root . '/js/work-order-pages.js');
		$this->assertStringContainsString("caption: tr('Exceptions')", $wo);
		$this->assertStringContainsString("caption: tr('Open work orders by status')", $wo);
		$this->assertStringContainsString("caption: tr('Stops')", $wo);
		$this->assertStringNotContainsString('mn-tour-stops', $wo);
		$this->assertDoesNotMatchRegularExpression("/class:\\s*'mn-list'/", $wo);
		foreach (['exceptions.php', 'tours.php', 'dispatch.php'] as $file) {
			$tpl = (string)file_get_contents($this->root . '/templates/' . $file);
			$this->assertStringContainsString('mn-card__body--table', $tpl, $file);
		}
	}

	public function testBadgesDefineNeutralAndUseWeight600(): void
	{
		$css = (string)file_get_contents($this->root . '/css/app.css');
		$this->assertStringContainsString('.mn-badge--neutral', $css);
		$this->assertMatchesRegularExpression(
			'/\.mn-badge\s*\{[^}]*font-weight:\s*600/s',
			$css
		);
		$this->assertStringContainsString('.mn-badge:not(:has(.mn-badge__dot)):not(:has(.mn-badge__icon))::before', $css);
	}


	public function testFormControlsCssIsImportedAndScoped(): void
	{
		$appCss = (string)file_get_contents($this->root . '/css/app.css');
		$this->assertStringContainsString('form-controls.css', $appCss);
		$path = $this->root . '/css/common/form-controls.css';
		$this->assertFileExists($path);
		$css = (string)file_get_contents($path);
		// Core control metrics: height from the clickable-area token, 2px
		// maxcontrast border, no custom focus glow.
		$this->assertStringContainsString('min-height: var(--mn-clickable-area', $css);
		$this->assertStringContainsString('border: 2px solid var(--mn-form-border)', $css);
		$this->assertStringContainsString('box-shadow: 0 0 0 2px', $css);
		$this->assertStringNotContainsString('box-shadow: 0 0 0 3px', $css);
		$this->assertStringContainsString('accent-color: var(--color-primary-element)', $css);
		$this->assertStringContainsString('input[type="checkbox"]', $css);
		$this->assertStringContainsString('select', $css);
		$this->assertStringContainsString('[role="combobox"]', $css);
		// Must stay scoped — never style NC global chrome
		$this->assertStringContainsString('#content.app-', $css);
		$this->assertDoesNotMatchRegularExpression('/^input\\s*,/m', $css);
	}


	public function testContentSurfacesMatchAzcCardsAndButtons(): void
	{
		$chrome = (string)file_get_contents($this->root . '/css/common/shell-chrome.css');
		$this->assertStringContainsString('content surfaces', $chrome);
		$this->assertStringContainsString('-section', $chrome);
		// Filter toolbars are flat rows now; no panel surface may come back.
		$this->assertStringNotContainsString('filter-panel', $chrome);
		$this->assertMatchesRegularExpression('/border-radius:\s*var\(--[a-z]+-radius-md,\s*12px\)/', $chrome);
		$app = (string)file_get_contents($this->root . '/css/app.css');
		$this->assertStringContainsString('AZ-PARITY-RADIUS-ENFORCER', $app);
		// Primary buttons must use md (12px) like azc-btn — not sm.
		$this->assertMatchesRegularExpression(
			'/\.button[^{]*\{[^}]*border-radius:\s*var\(--[a-z]+-radius-md,\s*12px\)\s*!important/s',
			$app
		);
		// Body-mounted dialogs must use lg (16px) — #content-scoped rules never apply.
		$this->assertMatchesRegularExpression(
			'/body\s*>\s*\.modal-backdrop\s*>\s*\.modal[^{]*\{[^}]*border-radius:\s*var\(--[a-z]+-radius-lg,\s*16px\)\s*!important/s',
			$app,
			'Dialogs mount on body; radius enforcer must cover modal-backdrop > .modal'
		);
	}

}
