<?php

declare(strict_types=1);

/**
 * Equipment register across all customers — AZ filter-panel parity.
 *
 * @var array $_
 * @var \OCP\IL10N $l
 */

require __DIR__ . '/common/page-start.php';
?>
<?php
$qsId = 'mn-equipment-quickstart';
$qsKey = 'equipment_quickstart_v1';
$qsLead = $l->t('Search across every unit — create new equipment on a customer page.');
$qsSteps = [
	[
		'title' => $l->t('1. Find a unit'),
		'body' => $l->t('Search by label, manufacturer, model or serial.'),
	],
	[
		'title' => $l->t('2. Open the unit'),
		'body' => $l->t('Plans, meters, inspections and visit history live on the detail page.'),
	],
	[
		'title' => $l->t('3. New units start on a customer'),
		'body' => $l->t('Use Register → Customers, open a customer, then add equipment there.'),
		'ctaLabel' => $l->t('Open Customers'),
		'ctaLink' => 'customers',
	],
];
require __DIR__ . '/parts/page-quickstart.php';
?>
<form id="mn-equipment-filters" class="mn-toolbar" role="search" aria-label="<?php p($l->t('Search equipment')); ?>" onsubmit="return false;">
	<div class="mn-filter-field">
		<label class="mn-filter-field__label" for="mn-equipment-search"><?php p($l->t('Search')); ?></label>
		<div class="mn-filter-field__control">
			<input type="search" id="mn-equipment-search" class="mn-input"
				placeholder="<?php p($l->t('Search by label, manufacturer, model or serial …')); ?>"
				autocomplete="off">
		</div>
	</div>
</form>
<section class="mn-list-section" aria-labelledby="mn-equipment-list-title">
	<h2 id="mn-equipment-list-title" class="mn-sr-only"><?php p($l->t('Equipment')); ?></h2>
	<div id="mn-equipment-list" class="mn-listing" aria-busy="true"></div>
	<nav id="mn-equipment-pagination" class="mn-pagination" aria-label="<?php p($l->t('Equipment pages')); ?>"></nav>
</section>
<?php require __DIR__ . '/common/page-end.php'; ?>
