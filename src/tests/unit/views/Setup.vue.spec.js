/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createTestingPinia } from '@pinia/testing'
import { shallowMount } from '@vue/test-utils'
import Setup from '../../../views/Setup.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'

vi.mock('@nextcloud/initial-state', () => ({
	loadState: vi.fn((app, key, fallback) => fallback),
}))

describe('Setup', () => {
	let view

	function applyTheme(isDark, primaryElementColor) {
		document.body.style.setProperty('--background-invert-if-dark', isDark ? 'invert(100%)' : 'no')
		document.body.style.setProperty('--color-primary-element', primaryElementColor)
	}

	function mountSetup() {
		view = shallowMount(Setup, {
			global: {
				mixins: [Nextcloud],
				plugins: [createTestingPinia()],
			},
		})
		return view.find('.setup')
	}

	afterEach(() => {
		view.unmount()
		document.body.removeAttribute('style')
	})

	it('shows the light welcome image for the default light theme', () => {
		applyTheme(false, '#00679e')

		const setup = mountSetup()

		expect(setup.classes()).not.toContain('setup--themed')
		expect(setup.element.style.backgroundImage).toContain('welcome-connection-light.png')
	})

	it('shows the dark welcome image for the default dark theme', () => {
		applyTheme(true, '#0091f2')

		const setup = mountSetup()

		expect(setup.classes()).not.toContain('setup--themed')
		expect(setup.element.style.backgroundImage).toContain('welcome-connection-dark.png')
	})

	it('shows the gradient for a custom primary color in the light theme', () => {
		applyTheme(false, '#ff0000')

		const setup = mountSetup()

		expect(setup.classes()).toContain('setup--themed')
		expect(setup.element.style.backgroundImage).toBe('')
	})

	it('shows the gradient for a custom primary color in the dark theme', () => {
		applyTheme(true, '#ff0000')

		const setup = mountSetup()

		expect(setup.classes()).toContain('setup--themed')
		expect(setup.element.style.backgroundImage).toBe('')
	})
})
