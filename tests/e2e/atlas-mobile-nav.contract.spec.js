// @ts-check
/**
 * Mobile navigation contract — plain Nextcloud navigation.
 *
 * Under 1024px Nextcloud core positions #app-navigation off-canvas and expects
 * the app to flip `body.snapjs-left`; #app-navigation-toggle is core's own
 * 44px button (core/css/mobile.css). The app no longer ships a parallel drawer.
 */
import { test, expect } from '@playwright/test'
import { login, primaryCreds } from './helpers/auth.js'

test('MOBILE_NAV_CONTRACT core toggle opens the drawer', async ({ page }) => {
	const creds = primaryCreds()
	test.skip(!creds, 'Requires NC_E2E_* or NC_ADMIN_* credentials')
	await page.setViewportSize({ width: 375, height: 812 })
	await login(page, creds)
	await page.goto('/apps/maintenancecheck/')

	const toggle = page.locator('#app-navigation-toggle')
	await expect(toggle).toBeVisible({ timeout: 30_000 })
	await expect(toggle).toHaveAttribute('aria-expanded', 'false')

	await toggle.click()
	await expect(page.locator('body')).toHaveClass(/snapjs-left/)
	await expect(toggle).toHaveAttribute('aria-expanded', 'true')

	// Drawer must sit on-canvas and must not create horizontal scrolling.
	const box = await page.locator('#app-navigation').boundingBox()
	expect(box).not.toBeNull()
	expect(box.x).toBe(0)
	const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth)
	expect(overflow).toBeLessThanOrEqual(1)

	await page.keyboard.press('Escape')
	await expect(page.locator('body')).not.toHaveClass(/snapjs-left/)
})
