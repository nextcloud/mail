/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import AccountFormDiscoveryProgress from '../../../components/AccountFormDiscoveryProgress.vue'

describe('AccountFormDiscoveryProgress', () => {
	it('shows the source being checked and the result of each protocol', () => {
		const view = shallowMount(AccountFormDiscoveryProgress, {
			props: {
				progress: [
					{ protocol: 'jmap', source: 'well-known', complete: false, found: false },
					{ protocol: 'imap', source: 'srv', complete: true, found: true },
				],
			},
		})

		const entries = view.findAll('li')

		expect(entries).toHaveLength(2)
		expect(entries[0].text()).toContain('JMAP')
		expect(entries[0].text()).toContain('Checking well-known address')
		expect(entries[0].classes()).toContain('account-form-discovery-progress__entry--checking')
		expect(entries[1].text()).toContain('IMAP')
		expect(entries[1].text()).toContain('Found via DNS records')
		expect(entries[1].classes()).toContain('account-form-discovery-progress__entry--found')
	})

	it('shows protocols that found nothing', () => {
		const view = shallowMount(AccountFormDiscoveryProgress, {
			props: {
				progress: [{ protocol: 'jmap', source: 'well-known', complete: true, found: false }],
			},
		})

		const entry = view.find('li')

		expect(entry.text()).toContain('Not found')
		expect(entry.classes()).toContain('account-form-discovery-progress__entry--missing')
	})

	it('shows protocols that have not started yet', () => {
		const view = shallowMount(AccountFormDiscoveryProgress, {
			props: {
				progress: [{ protocol: 'imap', source: null, complete: false, found: false }],
			},
		})

		expect(view.find('li').text()).toContain('Waiting')
	})

	it('shows the email address and the current step', () => {
		const view = shallowMount(AccountFormDiscoveryProgress, {
			props: {
				progress: [],
				email: 'tom@tom.turbo',
				message: 'Testing authentication',
			},
		})

		expect(view.find('.account-form-discovery-progress__email').text()).toBe('tom@tom.turbo')
		expect(view.find('.account-form-discovery-progress__message').text()).toBe('Testing authentication')
	})

	it('hides the email address and step when not given', () => {
		const view = shallowMount(AccountFormDiscoveryProgress, {
			props: { progress: [] },
		})

		expect(view.find('.account-form-discovery-progress__email').exists()).toBe(false)
		expect(view.find('.account-form-discovery-progress__message').exists()).toBe(false)
	})

	describe('protocol choice', () => {
		const progress = [
			{ protocol: 'jmap' as const, source: 'well-known' as const, complete: true, found: true },
			{ protocol: 'imap' as const, source: 'autoconfig' as const, complete: true, found: true },
		]

		it('preselects and recommends the first choice', () => {
			const view = shallowMount(AccountFormDiscoveryProgress, {
				props: { progress, choices: ['jmap', 'imap'] },
			})

			const radios = view.findAll('input[type="radio"]')
			expect(radios).toHaveLength(2)
			expect((radios[0].element as HTMLInputElement).checked).toBe(true)
			expect(view.findAll('li')[0].text()).toContain('Recommended')
			expect(view.findAll('li')[1].text()).not.toContain('Recommended')
		})

		it('emits the selected protocol on continue', async () => {
			const view = shallowMount(AccountFormDiscoveryProgress, {
				props: { progress, choices: ['jmap', 'imap'] },
			})

			await view.findAll('input[type="radio"]')[1].setValue(true)
			await view.findComponent({ name: 'NcButton' }).vm.$emit('click')

			expect(view.emitted('choose')).toEqual([['imap']])
		})

		it('has nothing to choose without choices', () => {
			const view = shallowMount(AccountFormDiscoveryProgress, {
				props: { progress },
			})

			expect(view.find('input[type="radio"]').exists()).toBe(false)
			expect(view.findComponent({ name: 'NcButton' }).exists()).toBe(false)
		})
	})
})
