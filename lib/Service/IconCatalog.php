<?php

declare(strict_types=1);

namespace OCA\MaintenanceCheck\Service;

use OCA\MaintenanceCheck\Support\IconCatalog as SupportIconCatalog;

/**
 * Service-namespace bridge to the canonical icon catalogue.
 *
 * Kept as a Service-namespace alias because the shared partial pack resolves the
 * catalogue as {AppNamespace}\Service\IconCatalog. The real catalogue lives in
 * Support — this facade delegates.
 */
final class IconCatalog
{
	private function __construct()
	{
	}

	public static function has(string $name): bool
	{
		return in_array($name, SupportIconCatalog::names(), true);
	}

	public static function render(string $name, ?string $extraClass = null): string
	{
		return SupportIconCatalog::render($name, $extraClass);
	}
}
