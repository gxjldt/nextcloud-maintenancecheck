// @ts-check
import { test, expect } from '@playwright/test'
import AxeBuilder from '@axe-core/playwright'
import { login, primaryCreds } from './helpers/auth.js'

/**
 * Nextcloud list convention: the table sits directly on the page surface.
 * No card frame, no soft-band header glued onto the thead, no lead paragraph
 * between the page title and the list. Filter controls stay in a flat toolbar.
 */
const listPages = [
	{
		path: '/apps/maintenancecheck/visits',
		listTitle: '#mn-visit-list-title',
		list: '#mn-visit-list',
		requireFilter: false,
		srOnlyTitle: true,
	},
	{
		path: '/apps/maintenancecheck/customers',
		listTitle: '#mn-customer-list-title',
		list: '#mn-customer-list',
		requireFilter: true,
		srOnlyTitle: true,
	},
	{
		path: '/apps/maintenancecheck/equipment',
		listTitle: '#mn-equipment-list-title',
		list: '#mn-equipment-list',
		requireFilter: true,
		srOnlyTitle: true,
	},
	{
		path: '/apps/maintenancecheck/work-orders',
		listTitle: '#mn-wo-list-title',
		list: '#mn-wo-list',
		requireFilter: true,
		srOnlyTitle: true,
	},
	{
		path: '/apps/maintenancecheck/dispatch',
		listTitle: '#mn-dispatch-title',
		list: '#mn-dispatch-board',
		requireFilter: false,
		srOnlyTitle: true,
	},
	{
		path: '/apps/maintenancecheck/tours',
		listTitle: '#mn-tours-title',
		list: '#mn-tours-board',
		requireFilter: false,
		srOnlyTitle: true,
	},
	{
		path: '/apps/maintenancecheck/exceptions',
		listTitle: '#mn-exceptions-title',
		list: '#mn-exceptions-board',
		requireFilter: false,
		srOnlyTitle: true,
	},
	{
		path: '/apps/maintenancecheck/kpi',
		listTitle: '#mn-kpi-title',
		list: '#mn-kpi-snapshot',
		requireFilter: false,
		srOnlyTitle: false,
	},
	{
		path: '/apps/maintenancecheck/catalogs',
		listTitle: '#mn-equip-types-title',
		list: '#mn-equip-types',
		requireFilter: false,
		srOnlyTitle: false,
	},
]

for (const route of listPages) {
	test(`flat list section: ${route.path}`, async ({ page }) => {
		const creds = primaryCreds()
		test.skip(!creds, 'Requires NC_E2E_* or NC_ADMIN_* credentials')

		await login(page, creds)
		await page.goto(route.path)

		const main = page.locator('#mn-main-content')
		await expect(main, `expected MN shell at ${route.path}`).toBeVisible({ timeout: 30_000 })
		await expect(page.locator(route.listTitle)).toBeAttached({ timeout: 30_000 })

		const section = page.locator(route.listTitle).locator(
			'xpath=ancestor::*[contains(concat(" ", normalize-space(@class), " "), " mn-list-section ")][1]',
		)
		await expect(section, 'list must sit in a flat .mn-list-section').toHaveCount(1)
		await expect(section.locator(':scope > .mn-card__header')).toHaveCount(0)
		await expect(section.locator('.mn-card__lead')).toHaveCount(0)

		const frame = await section.evaluate((el) => {
			const cs = getComputedStyle(el)
			return {
				padding: cs.paddingTop,
				border: cs.borderTopWidth,
				background: cs.backgroundColor,
			}
		})
		expect(Number.parseFloat(frame.padding), 'list section must be flush').toBe(0)
		expect(Number.parseFloat(frame.border), 'list section must not draw a card frame').toBe(0)
		expect(frame.background).toBe('rgba(0, 0, 0, 0)')

		// Inner table wrapper must not draw a second box either.
		const wrap = section.locator('.mn-table-wrap, .table-container').first()
		if (await wrap.count()) {
			const border = await wrap.evaluate((el) => getComputedStyle(el).borderTopWidth)
			expect(Number.parseFloat(border), 'table wrap must not draw a box').toBe(0)
		}

		if (route.srOnlyTitle) {
			await expect(page.locator(route.listTitle)).toHaveClass(/mn-sr-only/)
		}

		if (route.requireFilter) {
			const toolbar = main.locator('.mn-toolbar').first()
			await expect(toolbar, 'filters live in a flat toolbar').toBeVisible()
			const chrome = await toolbar.evaluate((el) => {
				const cs = getComputedStyle(el)
				return { border: cs.borderTopWidth, background: cs.backgroundColor }
			})
			expect(Number.parseFloat(chrome.border), 'toolbar must not be a card').toBe(0)
			expect(chrome.background).toBe('rgba(0, 0, 0, 0)')
		}

		const list = page.locator(route.list)
		await expect(list).toBeAttached({ timeout: 30_000 })
		if (await list.count()) {
			const busy = await list.first().getAttribute('aria-busy')
			if (busy === 'true') {
				await expect(list.first()).not.toHaveAttribute('aria-busy', 'true', { timeout: 45_000 })
			}
		}

		const axe = await new AxeBuilder({ page })
			.include('#mn-main-content')
			.withTags(['wcag2a', 'wcag2aa', 'wcag21aa'])
			.exclude('.toastify')
			.analyze()
		expect(axe.violations, JSON.stringify(axe.violations, null, 2)).toEqual([])
	})
}
