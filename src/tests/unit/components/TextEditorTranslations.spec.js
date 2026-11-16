/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import mitt from 'mitt'
import { vi } from 'vitest'
import TextEditor from '../../../components/TextEditor.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'

const state = vi.hoisted(() => ({ language: 'en' }))

vi.mock('@nextcloud/l10n', async (importOriginal) => ({
	...(await importOriginal()),
	getLanguage: () => state.language,
}))

vi.mock('../../../../node_modules/ckeditor5/dist/translations/de-ch.js', () => ({ default: undefined }))

function mountEditor() {
	return shallowMount(TextEditor, {
		global: {
			mixins: [Nextcloud],
		},
		props: {
			modelValue: 'bonjour',
			bus: mitt(),
		},
	})
}

describe('TextEditor translations', () => {
	it('loads the full CKEditor dictionary for the user language', async () => {
		state.language = 'de'

		const wrapper = mountEditor()
		await vi.waitFor(() => expect(wrapper.vm.ready).toBe(true))

		expect(wrapper.vm.config.language).toBe('de')
		expect(wrapper.vm.config.translations[0].de.dictionary.Bold).toBe('Fett')
	})

	it('falls back to the language part of a regional locale', async () => {
		state.language = 'de-AT'

		const wrapper = mountEditor()
		await vi.waitFor(() => expect(wrapper.vm.ready).toBe(true))

		expect(wrapper.vm.config.language).toBe('de')
		expect(wrapper.vm.config.translations[0].de.dictionary.Bold).toBe('Fett')
	})

	it('falls back to the next candidate if a translation file has no default export', async () => {
		state.language = 'de-CH'

		const wrapper = mountEditor()
		await vi.waitFor(() => expect(wrapper.vm.ready).toBe(true))

		expect(wrapper.vm.config.language).toBe('de')
		expect(wrapper.vm.config.translations[0].de.dictionary.Bold).toBe('Fett')
	})

	it('does not load translations for English', async () => {
		state.language = 'en'

		const wrapper = mountEditor()
		await vi.waitFor(() => expect(wrapper.vm.ready).toBe(true))

		expect(wrapper.vm.config.language).toBe('en')
		expect(wrapper.vm.config.translations).toBeUndefined()
	})
})
