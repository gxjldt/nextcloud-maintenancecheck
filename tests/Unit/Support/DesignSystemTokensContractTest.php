<?php

declare(strict_types=1);

namespace OCA\MaintenanceCheck\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;

/**
 * Design-system checklist gates for MaintenanceCheck feature CSS.
 */
final class DesignSystemTokensContractTest extends TestCase
{
	private string $root;

	protected function setUp(): void
	{
		parent::setUp();
		$this->root = dirname(__DIR__, 3);
	}

	public function testTokensDefinePrintSurfaceAndSpacingScale(): void
	{
		$css = (string)file_get_contents($this->root . '/css/common/tokens.css');
		foreach ([
			'--mn-space-1:',
			'--mn-space-8:',
			'--mn-radius-sm:',
			'--mn-fs-2xl:',
			'--mn-print-surface:',
			'--mn-signature-ink:',
			'--mn-scrim:',
			'--mn-shadow-lg:',
			'--mn-signature-surface:',
			'--mn-overlay-top:',
			'--mn-danger-fill:',
			'--mn-danger-on-fill:',
			'--mn-danger-ink:',
		] as $token) {
			$this->assertStringContainsString($token, $css, $token);
		}
	}

	public function testFeatureCssHasNoBareHexFills(): void
	{
		$files = [
			'/css/app.css',
			'/css/common/shell-chrome.css',
			'/css/common/page-patterns.css',
			'/css/common/form-controls.css',
			'/css/common/dialogs.css',
			'/css/common/notification-surfaces.css',
			'/css/common/app-layout.css',
			'/css/navigation.css',
		];
		$offenders = [];
		foreach ($files as $rel) {
			$css = (string)file_get_contents($this->root . $rel);
			$lines = preg_split('/\R/', $css) ?: [];
			foreach ($lines as $i => $line) {
				if (str_contains($line, 'var(')) {
					continue;
				}
				// Intentional print/signature ink tokens live in tokens.css only
				if (preg_match('/(?:background|color|border(?:-color)?|fill|stroke)\s*:\s*#[0-9a-fA-F]{3,8}\b/', $line)) {
					$offenders[] = $rel . ':L' . ($i + 1) . ': ' . trim($line);
				}
			}
		}
		$this->assertSame([], $offenders, "Bare hex fills violate design-system theme-first rule:\n" . implode("\n", $offenders));
	}

	public function testFeatureCssHasNoRawBlackRgbaOverlays(): void
	{
		$css = (string)file_get_contents($this->root . '/css/app.css');
		$this->assertDoesNotMatchRegularExpression(
			'/rgba\(\s*0\s*,\s*0\s*,\s*0\s*,/',
			$css,
			'Overlays/shadows must use --mn-scrim / --mn-shadow-* tokens, not raw black rgba'
		);
	}

	public function testSignatureCanvasUsesThemeTokensNotInlineJsHex(): void
	{
		$css = (string)file_get_contents($this->root . '/css/app.css');
		$this->assertMatchesRegularExpression(
			'/\.mn-signature-canvas\s*\{[^}]*background:\s*var\(--mn-signature-surface/s',
			$css
		);
		$js = (string)file_get_contents($this->root . '/js/work-order-pages.js');
		$this->assertStringNotContainsString("background = '#fff'", $js);
		$this->assertStringNotContainsString("border = '1px solid var(--color-border, #888)'", $js);
		$this->assertStringNotContainsString("strokeStyle = '#111'", $js);
		$this->assertStringContainsString('--mn-signature-ink', $js);
		$this->assertStringContainsString('mn-signature-canvas', $js);
	}

	public function testFeatureCssAvoidsOffGridSpacingLiterals(): void
	{
		$files = [
			'/css/app.css',
			'/css/common/shell-chrome.css',
			'/css/common/page-patterns.css',
			'/css/common/notification-surfaces.css',
		];
		$offenders = [];
		foreach ($files as $rel) {
			$css = (string)file_get_contents($this->root . $rel);
			$lines = preg_split('/\R/', $css) ?: [];
			foreach ($lines as $i => $line) {
				if (!preg_match('/(?:margin|padding|gap|row-gap|column-gap)\s*:/', $line)) {
					continue;
				}
				if (preg_match('/\b(?:6|10|14|18|20)px\b/', $line)) {
					$offenders[] = $rel . ':L' . ($i + 1) . ': ' . trim($line);
				}
			}
		}
		$this->assertSame([], $offenders, "Off-grid spacing (6/10/14/18/20px) must use --mn-space-* tokens:\n" . implode("\n", $offenders));
	}

	public function testToastSurfaceIsOwnedByCore(): void
	{
		$css = (string)file_get_contents($this->root . '/css/app.css');
		// Core positions and themes toasts; the app only lays out an action payload.
		$this->assertStringNotContainsString('.mn-toast-region', $css);
		$this->assertStringNotContainsString('.mn-toast--', $css);
		$this->assertStringContainsString('.mn-toast__body', $css);
	}

	public function testPageHeaderGeometryOwnedByShellChrome(): void
	{
		$chrome = (string)file_get_contents($this->root . '/css/common/shell-chrome.css');
		// Nextcloud page header: one wrapping row (title + actions), with the
		// hint and session meta on their own lines. No icon well, no scope strip.
		$this->assertMatchesRegularExpression(
			'/\.mn-page-header\s*\{[^}]*display:\s*flex/s',
			$chrome
		);
		$this->assertMatchesRegularExpression(
			'/\.mn-page-header\s*\{[^}]*align-items:\s*center/s',
			$chrome
		);
		$this->assertMatchesRegularExpression(
			'/#app-content\.mn-app \.mn-page-header h1\s*\{[^}]*font-size:\s*20px/s',
			$chrome
		);
		$this->assertStringNotContainsString('mn-page-header__icon', $chrome);
		$this->assertStringNotContainsString('mn-scope-strip', $chrome);
	}

	public function testSmallButtonsMeetTouchTarget(): void
	{
		$chrome = (string)file_get_contents($this->root . '/css/common/shell-chrome.css');
		$this->assertDoesNotMatchRegularExpression(
			'/\.mn-btn--sm[^{]*\{[^}]*min-height:\s*36px/s',
			$chrome
		);
		$this->assertMatchesRegularExpression(
			'/\.mn-btn--sm\s*\{[^}]*min-height:\s*44px/s',
			$chrome
		);
	}

	public function testTableBodiesDefaultToInsetHeadedCardsUseSoloForLists(): void
	{
		$chrome = (string)file_get_contents($this->root . '/css/common/shell-chrome.css');
		$this->assertMatchesRegularExpression(
			'/\.mn-card__body--table\s*\{[^}]*padding:\s*var\(--mn-space-5/s',
			$chrome,
			'DESIGN-SYSTEM principle 14 / §3.7: headed/default table body keeps space-5 inset'
		);
		$app = (string)file_get_contents($this->root . '/css/app.css');
		$this->assertMatchesRegularExpression(
			'/\.mn-list-section\s+\.mn-table-wrap[\s\S]*?border:\s*none/s',
			$app,
			'list sections must strip the wrap border (one surface, not a frame)'
		);
	}

	public function testQrStickerUsesPrintSurfaceToken(): void
	{
		$css = (string)file_get_contents($this->root . '/css/app.css');
		$this->assertMatchesRegularExpression(
			'/\.mn-qr-sticker\s+svg[^{]*\{[^}]*background:\s*var\(--mn-print-surface/s',
			$css
		);
	}

	public function testVisitActionsDefaultToOverflowMenu(): void
	{
		$js = (string)file_get_contents($this->root . '/js/app.js');
		$this->assertStringContainsString('options.overflow === false', $js);
		$this->assertStringContainsString('granny/toddler', $js);
		$this->assertStringContainsString('MutationObserver', $js);
		$this->assertDoesNotMatchRegularExpression('/addEventListener\(\s*[\'"]DOMNodeRemoved[\'"]/', $js);
	}

	public function testDetailGridNeutralisesNextcloudCoreDtChrome(): void
	{
		$css = (string)file_get_contents($this->root . '/css/app.css');
		$this->assertMatchesRegularExpression(
			'/\.mn-detail__grid\s+dt[^{]*\{[^}]*text-align:\s*start/s',
			$css,
			'Detail grids must force text-align: start against core dt end-align',
		);
		$this->assertMatchesRegularExpression(
			'/\.mn-detail__grid\s+dt[^{]*\{[^}]*width:\s*auto/s',
			$css,
		);
	}
}
