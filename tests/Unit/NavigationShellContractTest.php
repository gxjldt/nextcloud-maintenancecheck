<?php

declare(strict_types=1);

namespace OCA\MaintenanceCheck\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * The app navigation must be plain Nextcloud navigation.
 *
 * Core owns the metrics, hover/active colours, chevron and the mobile drawer;
 * the app only emits core's markup contract plus the inline SVG glyph hook.
 * Regression guard: re-introducing an app-specific drawer or collapse widget
 * means a second, divergent navigation model is back.
 */
class NavigationShellContractTest extends TestCase {
	private string $root;

	protected function setUp(): void {
		parent::setUp();
		$this->root = dirname(__DIR__, 2);
	}

	private function navigationTemplate(): string {
		return (string)file_get_contents($this->root . '/templates/common/navigation.php');
	}

	public function testTemplateEmitsCoreNavigationStructure(): void {
		$src = $this->navigationTemplate();
		self::assertStringContainsString('id="app-navigation"', $src);
		self::assertStringContainsString('app-navigation-personal', $src);
		self::assertStringContainsString('class="nav-entry-link"', $src);
		self::assertStringContainsString('nav-entry-name', $src);
	}

	public function testTemplateUsesCoreCollapseAndDrawerContracts(): void {
		$src = $this->navigationTemplate();
		// Core collapse: li.collapsible + button.collapse + the `open` state class.
		self::assertStringContainsString('class="collapsible', $src);
		self::assertStringContainsString('class="collapse"', $src);
		// Core drawer: core's own #app-navigation-toggle sits in the app shell.
		self::assertStringContainsString('id="app-navigation-toggle"', $src);
		self::assertStringNotContainsString('mn-nav--open', $src);
		self::assertStringNotContainsString('nav-parent-toggle', $src);
	}

	public function testNavigationRegistersAppScriptAndDropsMobileDrawerScript(): void {
		$src = $this->navigationTemplate();
		self::assertMatchesRegularExpression(
			'/^\s*Util::addScript\(\s*\'maintenancecheck\'\s*,\s*\'common\/navigation\'\s*\)\s*;/m',
			$src,
			'common/navigation must stay registered for the collapse + drawer state flips'
		);
		self::assertStringNotContainsString("'common/mobile-nav'", $src);
		self::assertFileDoesNotExist($this->root . '/js/common/mobile-nav.js');
		self::assertFileDoesNotExist($this->root . '/css/common/mobile-nav.css');
	}

	public function testAppCssDoesNotImportAMobileDrawerSheet(): void {
		$css = (string)file_get_contents($this->root . '/css/app.css');
		self::assertStringNotContainsString('mobile-nav.css', $css);
	}
}
