/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createLocalVue, shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import TranslationModal from '../../../components/TranslationModal.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import useMainStore from '../../../store/mainStore.js'

const { getLanguage } = vi.hoisted(() => ({ getLanguage: vi.fn() }))

vi.mock('@nextcloud/l10n', async () => ({
	...await vi.importActual('@nextcloud/l10n'),
	getLanguage,
}))

const localVue = createLocalVue()

localVue.mixin(Nextcloud)

describe('TranslationModal', () => {
	const language = (value) => ({ id: value, value, name: value })

	const mountModal = ({ input = [], output = [], detectedForeignLanguage = null } = {}) => {
		const store = useMainStore()
		store.translationInputLanguages = input.map(language)
		store.translationOutputLanguages = output.map(language)

		return shallowMount(TranslationModal, {
			propsData: {
				message: 'Bonjour tout le monde',
				richParameters: {},
				detectedForeignLanguage,
			},
			localVue,
		})
	}

	beforeEach(() => {
		setActivePinia(createPinia())
		getLanguage.mockReturnValue('en')
	})

	describe('target language', () => {
		it('picks the interface language', () => {
			getLanguage.mockReturnValue('de')

			const view = mountModal({ output: ['en', 'de'] })

			expect(view.vm.selectedTo?.value).toBe('de')
		})

		it('matches the interface hyphen against the provider underscore', () => {
			getLanguage.mockReturnValue('pt-BR')

			const view = mountModal({ output: ['pt_PT', 'pt_BR'] })

			expect(view.vm.selectedTo?.value).toBe('pt_BR')
		})

		it('settles for the primary language when the provider offers no regional variant', () => {
			getLanguage.mockReturnValue('pt_BR')

			const view = mountModal({ output: ['en', 'pt'] })

			expect(view.vm.selectedTo?.value).toBe('pt')
		})

		it('prefers the exact match over the primary language', () => {
			getLanguage.mockReturnValue('pt-BR')

			const view = mountModal({ output: ['pt', 'pt_BR'] })

			expect(view.vm.selectedTo?.value).toBe('pt_BR')
		})

		it('selects nothing when the provider does not offer the language at all', () => {
			getLanguage.mockReturnValue('de')

			const view = mountModal({ output: ['en', 'fr'] })

			expect(view.vm.selectedTo).toBeNull()
		})
	})

	describe('source language', () => {
		it('lets the provider detect the language when it can', () => {
			const view = mountModal({ input: ['detect_language', 'fr'], detectedForeignLanguage: 'fr' })

			expect(view.vm.selectedFrom?.value).toBe('detect_language')
		})

		it('falls back to the detected language', () => {
			const view = mountModal({ input: ['en', 'fr'], detectedForeignLanguage: 'fr' })

			expect(view.vm.selectedFrom?.value).toBe('fr')
		})

		it('matches a regional variant of the detected language', () => {
			const view = mountModal({ input: ['en', 'pt_BR'], detectedForeignLanguage: 'pt' })

			expect(view.vm.selectedFrom?.value).toBe('pt_BR')
		})

		it('selects nothing when there is no detected language', () => {
			const view = mountModal({ input: ['en', 'fr'] })

			expect(view.vm.selectedFrom).toBeNull()
		})
	})
})
