<?php

declare(strict_types=1);

/**
 * Customer list with S13 search and S7 pagination — AZ filter-panel parity.
 *
 * @var array $_
 * @var \OCP\IL10N $l
 */

require __DIR__ . '/common/page-start.php';
?>
<?php
$qsId = 'mn-customers-quickstart';
$qsKey = 'customers_quickstart_v1';
$qsLead = $l->t('Start with customers — every unit and plan belongs to one.');
$qsSteps = [
	[
		'title' => $l->t('1. Create a customer'),
		'body' => $l->t('Use New customer. Add a name (and number if you use one). Sites are optional addresses under the same customer.'),
	],
	[
		'title' => $l->t('2. Open the customer'),
		'body' => $l->t('Click a row to add equipment, sites and plans for that organisation.'),
	],
	[
		'title' => $l->t('3. Keep the register tidy'),
		'body' => $l->t('Deactivate customers you no longer service — history stays readable.'),
	],
];
require __DIR__ . '/parts/page-quickstart.php';
?>
<form id="mn-customer-filters" class="mn-toolbar" role="search" aria-label="<?php p($l->t('Search customers')); ?>" onsubmit="return false;">
	<div class="mn-filter-field">
		<label class="mn-filter-field__label" for="mn-customer-search"><?php p($l->t('Search')); ?></label>
		<div class="mn-filter-field__control">
			<input type="search" id="mn-customer-search" class="mn-input"
				placeholder="<?php p($l->t('Search by name, number or city …')); ?>"
				autocomplete="off">
		</div>
	</div>
</form>
<section class="mn-list-section" aria-labelledby="mn-customer-list-title">
	<h2 id="mn-customer-list-title" class="mn-sr-only"><?php p($l->t('Customers')); ?></h2>
	<div id="mn-customer-list" class="mn-listing" aria-busy="true"></div>
	<nav id="mn-customer-pagination" class="mn-pagination" aria-label="<?php p($l->t('Customer pages')); ?>"></nav>
</section>
<?php require __DIR__ . '/common/page-end.php'; ?>
