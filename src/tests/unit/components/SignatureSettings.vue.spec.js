/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createLocalVue, shallowMount } from '@vue/test-utils'
import SignatureSettings from '../../../components/SignatureSettings.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'

const localVue = createLocalVue()

localVue.mixin(Nextcloud)

describe('SignatureSettings', () => {
	it('Show warning for large signatures', () => {
		const wrapper = shallowMount(SignatureSettings, {
			localVue,
			propsData: {
				account: {
					aliases: [],
					signature: String('<p>Lorem ipsum</p>').repeat(120000),
				},
			},
		})

		expect(wrapper.vm.isLargeSignature).toBeTruthy()
	})

	it.each([
		['richtext', '<p>Lorem ipsum</p>', true],
		['plaintext', '<p>Lorem ipsum</p>', false],
		['plaintext', '<p>Lorem <img src="cid:logo"> ipsum</p>', true],
	])('uses the %s writing mode for %s', (editorMode, signature, html) => {
		const wrapper = shallowMount(SignatureSettings, {
			localVue,
			propsData: {
				account: {
					aliases: [],
					editorMode,
					signature,
				},
			},
		})

		expect(wrapper.findComponent({ name: 'TextEditor' }).props('html')).toBe(html)
	})

	it.each([
		['richtext', '<p>Lorem <img src="cid:logo"> ipsum</p>', false],
		['plaintext', '<p>Lorem ipsum</p>', false],
		['plaintext', '<p>Lorem <img src="cid:logo"> ipsum</p>', true],
	])('warns about the overridden %s mode for %s', (editorMode, signature, warns) => {
		const wrapper = shallowMount(SignatureSettings, {
			localVue,
			propsData: {
				account: {
					aliases: [],
					editorMode,
					signature,
				},
			},
		})

		expect(wrapper.vm.overridesPlainText).toBe(warns)
	})

	it.each([
		['the image is deleted', '<p>Lorem ipsum</p>'],
		['the signature is deleted', null],
	])('drops the warning once %s', async (_, signature) => {
		const wrapper = shallowMount(SignatureSettings, {
			localVue,
			propsData: {
				account: {
					aliases: [],
					editorMode: 'plaintext',
					signature: '<p>Lorem <img src="cid:logo"> ipsum</p>',
				},
			},
		})

		wrapper.vm.signature = signature
		await wrapper.vm.$nextTick()

		expect(wrapper.vm.overridesPlainText).toBe(false)
	})

	it('keeps the editor when the image is deleted while editing', async () => {
		const wrapper = shallowMount(SignatureSettings, {
			localVue,
			propsData: {
				account: {
					aliases: [],
					editorMode: 'plaintext',
					signature: '<p>Lorem <img src="cid:logo"> ipsum</p>',
				},
			},
		})

		wrapper.vm.signature = '<p>Lorem ipsum</p>'
		await wrapper.vm.$nextTick()

		expect(wrapper.findComponent({ name: 'TextEditor' }).props('html')).toBe(true)
	})
})
