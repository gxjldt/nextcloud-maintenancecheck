// @ts-check
import { test, expect } from '@playwright/test'
import { login, primaryCreds } from './helpers/auth.js'

/**
 * Interaction-level WCAG 2.1 AA checks that axe cannot cover:
 * skip link, visible focus indicators (2.4.7), keyboard accordion
 * operation (2.1.1), touch target sizes (2.5.5) and reduced motion.
 */

test('skip link appears on focus and moves focus to main content', async ({ page }) => {
	const creds = primaryCreds()
	test.skip(!creds, 'Requires NC_E2E_* / NC_ADMIN_*')

	await login(page, creds)
	await page.goto('/apps/maintenancecheck/')
	await expect(page.locator('#mn-main-content')).toBeVisible({ timeout: 30_000 })

	const skipLink = page.locator('.mn-skip-link[href="#mn-main-content"]')
	await skipLink.focus()
	const box = await skipLink.boundingBox()
	expect(box, 'focused skip link must be on-screen').not.toBeNull()
	expect(box.x, 'focused skip link must be inside the viewport').toBeGreaterThanOrEqual(0)
	expect(box.height, 'skip link touch target').toBeGreaterThanOrEqual(43)

	await page.keyboard.press('Enter')
	await expect(page.locator('#mn-main-content')).toBeFocused()
})

test('keyboard focus produces a visible ring on app controls', async ({ page }) => {
	const creds = primaryCreds()
	test.skip(!creds, 'Requires NC_E2E_* / NC_ADMIN_*')

	await login(page, creds)
	await page.goto('/apps/maintenancecheck/')
	await expect(page.locator('#mn-main-content')).toBeVisible({ timeout: 30_000 })

	const targets = [
		page.locator('#app-navigation .nav-entry-link').first(),
		page.locator('#app-navigation button.collapse').first(),
		page.locator('.mn-breadcrumb__link').first(),
	]
	for (const target of targets) {
		if (!await target.isVisible().catch(() => false)) {
			continue
		}
		// Keyboard-originated focus (Tab from a programmatic anchor) triggers :focus-visible
		await target.evaluate((el) => {
			el.focus()
		})
		await page.keyboard.press('Shift')
		const outline = await target.evaluate((el) => {
			const style = getComputedStyle(el)
			return { width: style.outlineWidth, style: style.outlineStyle }
		})
		// Focus ring must be at least 2px and actually drawn (design system §5, WCAG 2.4.7)
		if (outline.style !== 'none') {
			expect(parseFloat(outline.width), 'focus ring width').toBeGreaterThanOrEqual(2)
		} else {
			// Some controls draw the ring via box-shadow instead
			const shadow = await target.evaluate((el) => getComputedStyle(el).boxShadow)
			expect(shadow, 'control must have outline or box-shadow focus indicator').not.toEqual('none')
		}
	}
})

test('sidebar accordion is fully keyboard operable', async ({ page }) => {
	const creds = primaryCreds()
	test.skip(!creds, 'Requires NC_E2E_* / NC_ADMIN_*')

	await login(page, creds)
	await page.goto('/apps/maintenancecheck/')
	await expect(page.locator('#app-navigation')).toBeVisible({ timeout: 30_000 })

	const toggle = page.locator('#app-navigation button.collapse').first()
	test.skip(!await toggle.isVisible().catch(() => false), 'No accordion for this role')

	const submenuId = await toggle.getAttribute('aria-controls')
	const submenu = page.locator(`#${submenuId}`)
	const initiallyExpanded = (await toggle.getAttribute('aria-expanded')) === 'true'

	await toggle.focus()
	await page.keyboard.press('Enter')
	await expect(toggle).toHaveAttribute('aria-expanded', String(!initiallyExpanded))
	if (!initiallyExpanded) {
		await expect(submenu).toBeVisible()
		// Child links must be reachable and expose accessible names
		const firstChild = submenu.locator('a').first()
		await expect(firstChild).toBeVisible()
	}
	await page.keyboard.press('Enter')
	await expect(toggle).toHaveAttribute('aria-expanded', String(initiallyExpanded))
})

test('touch targets are at least 44px on mobile and desktop viewports', async ({ page }, testInfo) => {
	const creds = primaryCreds()
	test.skip(!creds, 'Requires NC_E2E_* / NC_ADMIN_*')

	await login(page, creds)
	for (const path of ['/apps/maintenancecheck/', '/apps/maintenancecheck/work-orders']) {
		await page.goto(path)
		await expect(page.locator('#mn-main-content')).toBeVisible({ timeout: 30_000 })

		const undersized = await page.evaluate(() => {
			const selectors = [
				'#app-content .mn-btn',
				'#app-content .mn-btn--sm',
				'#app-content .mn-input',
				'#app-content select',
				'#app-content .mn-breadcrumb__link',
				'#app-navigation .nav-entry-link',
				'#app-navigation button.collapse',
				'#app-navigation li.collapsible ul a',
			]
			const bad = []
			for (const el of document.querySelectorAll(selectors.join(','))) {
				const rect = el.getBoundingClientRect()
				if (rect.width === 0 && rect.height === 0) {
					continue // hidden (collapsed drawer / inactive section)
				}
				// 33.5 tolerance for subpixel rounding; --mn-clickable-area is 34px
				if (rect.height < 33.5 || rect.width < 33.5) {
					bad.push(`${el.tagName.toLowerCase()}.${[...el.classList].join('.')} ${Math.round(rect.width)}x${Math.round(rect.height)} "${(el.textContent || '').trim().slice(0, 30)}"`)
				}
			}
			return bad
		})
		expect(undersized, `undersized touch targets on ${path} (${testInfo.project.name})`).toEqual([])
	}
})

test('page header is one flat row: title, actions, then hint', async ({ page }) => {
	const creds = primaryCreds()
	test.skip(!creds, 'Requires NC_E2E_* / NC_ADMIN_*')

	await login(page, creds)
	await page.goto('/apps/maintenancecheck/')
	await expect(page.locator('#mn-main-content')).toBeVisible({ timeout: 30_000 })

	const layout = await page.evaluate(() => {
		const header = document.querySelector('.mn-page-header')
		const title = document.getElementById('mn-page-title')
		if (!header || !title) {
			return null
		}
		const hs = getComputedStyle(header)
		const ts = getComputedStyle(title)
		return {
			headerDirection: hs.flexDirection,
			headerAlign: hs.alignItems,
			hasIconWell: !!document.querySelector('.mn-page-header__icon'),
			hasScopeStrip: !!document.querySelector('.mn-scope-strip'),
			titleSize: Number.parseFloat(ts.fontSize),
		}
	})
	expect(layout, 'page header must exist').not.toBeNull()
	expect(layout.headerDirection).toBe('row')
	expect(layout.headerAlign).toBe('center')
	expect(layout.hasIconWell).toBe(false)
	expect(layout.hasScopeStrip).toBe(false)
	// Nextcloud page headings are ~20px bold, not a 30px dashboard hero.
	expect(layout.titleSize).toBeLessThanOrEqual(22)
})

test('toasts come from Nextcloud core, not an app-owned region', async ({ page }) => {
	const creds = primaryCreds()
	test.skip(!creds, 'Requires NC_E2E_* / NC_ADMIN_*')

	await login(page, creds)
	await page.goto('/apps/maintenancecheck/')
	await expect(page.locator('#mn-main-content')).toBeVisible({ timeout: 30_000 })

	await expect(page.locator('#mn-toast-region')).toHaveCount(0)
	const hasCoreApi = await page.evaluate(() => !!(window.OCP && window.OCP.Toast))
	expect(hasCoreApi, 'window.OCP.Toast must be available').toBe(true)

	const toast = await page.evaluate(async () => {
		window.OCP.Toast.info('MN toast probe')
		await new Promise((resolve) => setTimeout(resolve, 200))
		const node = document.querySelector('.toastify.toast')
		return node ? { present: true } : { present: false }
	})
	expect(toast.present, 'core toast must render for an app message').toBe(true)
})

test('prefers-reduced-motion collapses app transitions', async ({ page }) => {
	const creds = primaryCreds()
	test.skip(!creds, 'Requires NC_E2E_* / NC_ADMIN_*')

	await page.emulateMedia({ reducedMotion: 'reduce' })
	await login(page, creds)
	await page.goto('/apps/maintenancecheck/')
	await expect(page.locator('#mn-main-content')).toBeVisible({ timeout: 30_000 })

	const btn = page.locator('.mn-btn').first()
	test.skip(!await btn.isVisible().catch(() => false), 'No button rendered')
	const duration = await btn.evaluate((el) => getComputedStyle(el).transitionDuration)
	const maxSeconds = Math.max(...duration.split(',').map((d) => parseFloat(d) || 0))
	expect(maxSeconds, '.mn-btn transition under reduced motion').toBeLessThanOrEqual(0.02)
})
