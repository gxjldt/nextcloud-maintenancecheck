// @ts-check
import { test, expect } from '@playwright/test'
import AxeBuilder from '@axe-core/playwright'
import { login, primaryCreds } from './helpers/auth.js'

/**
 * List toolbars are flat rows: the first field starts at the content edge
 * (never shoved right by legacy boxed chrome) and the row itself carries no
 * card surface.
 */
test('Work order toolbar is left-aligned and axe clean', async ({ page }) => {
	const creds = primaryCreds()
	test.skip(!creds, 'Requires NC_E2E_* or NC_ADMIN_*')

	await login(page, creds)
	await page.goto('/apps/maintenancecheck/work-orders')
	await expect(page.locator('#mn-wo-filters')).toBeVisible({ timeout: 30_000 })

	const metrics = await page.evaluate(() => {
		const form = document.getElementById('mn-wo-filters')
		const fields = form?.querySelector('.mn-toolbar__fields')
		const first = fields?.children?.[0]
		if (!form || !fields || !first) {
			return { ok: false, reason: 'missing nodes' }
		}
		const formCs = getComputedStyle(form)
		const formR = form.getBoundingClientRect()
		const firstR = first.getBoundingClientRect()
		return {
			ok: true,
			alignItems: formCs.alignItems,
			flexDirection: formCs.flexDirection,
			hasLegacyFilterbar: form.classList.contains('mn-filterbar'),
			hasCardChrome: formCs.backgroundColor !== 'rgba(0, 0, 0, 0)' || formCs.borderTopWidth !== '0px',
			delta: Math.abs(firstR.left - formR.left),
			firstLeft: firstR.left,
			expectedLeft: formR.left,
		}
	})

	expect(metrics.ok, metrics.reason || 'nodes').toBeTruthy()
	expect(metrics.hasLegacyFilterbar, 'form must not carry legacy mn-filterbar').toBe(false)
	expect(metrics.hasCardChrome, 'toolbar must be a flat row, not a card').toBe(false)
	expect(metrics.alignItems).toBe('flex-end')
	expect(metrics.flexDirection).toBe('row')
	expect(metrics.delta, `toolbar starts ${metrics.delta}px off the content edge`).toBeLessThanOrEqual(2)

	const axe = await new AxeBuilder({ page })
		.include('#mn-main-content')
		.withTags(['wcag2a', 'wcag2aa', 'wcag21aa'])
		.exclude('.toastify')
		.analyze()
	expect(axe.violations, JSON.stringify(axe.violations, null, 2)).toEqual([])
})
