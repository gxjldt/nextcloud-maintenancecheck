/**
 * Contract: toasts dedup on kind+text with timer reset — identical toasts must
 * not stack (design-system rule: toast hygiene).
 *
 * The toast surface itself is Nextcloud core's (window.OCP.Toast →
 * @nextcloud/dialogs). What this app still owes is dedup and dismissal timing,
 * so those two behaviours are what these assertions pin.
 */
import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'

const root = join(dirname(fileURLToPath(import.meta.url)), '../..')
const app = readFileSync(join(root, 'js/app.js'), 'utf8')

// Slice out the toast() body for scoped assertions.
const toastBody = (() => {
	const start = app.indexOf('function toast(message, type, action)')
	assert.ok(start > -1, 'toast() must exist')
	const end = app.indexOf('Dialogs (A6', start)
	assert.ok(end > start, 'toast() must precede the dialog section')
	return app.slice(start, end)
})()

test('toast() renders through Nextcloud core, not app-owned markup', () => {
	assert.match(app, /window\.OCP && window\.OCP\.Toast/)
	assert.match(toastBody, /api\.(error|success|warning|info)\(payload/)
	// No app-built toast node, no app-owned live region.
	assert.doesNotMatch(toastBody, /mn-toast toast--/)
	assert.doesNotMatch(toastBody, /getElementById\('mn-toast-region'\)/)
})

test('toast() dedups on kind+text and re-arms the timer instead of stacking', () => {
	assert.match(toastBody, /var dedupKey\s*=\s*kind/)
	assert.match(toastBody, /String\(message\)/)
	assert.match(toastBody, /var live = activeToasts\[dedupKey\]/)
	assert.match(toastBody, /window\.clearTimeout\(live\.timer\)/)
	assert.match(toastBody, /live\.timer = window\.setTimeout\(live\.dismiss, lifetime\)/)
	const hit = toastBody.indexOf('var live = activeToasts[dedupKey]')
	const retIdx = toastBody.indexOf('return;', hit)
	const showIdx = toastBody.indexOf('var instance =')
	assert.ok(retIdx > hit, 'dedup hit must return early')
	assert.ok(showIdx === -1 || retIdx < showIdx, 'dedup return must precede showing a new toast')
})

test('created toasts carry their dismiss handle for later resets', () => {
	assert.match(toastBody, /activeToasts\[dedupKey\] = entry/)
	assert.match(toastBody, /entry\.timer = window\.setTimeout\(entry\.dismiss, lifetime\)/)
	assert.match(toastBody, /delete activeToasts\[dedupKey\]/)
})
