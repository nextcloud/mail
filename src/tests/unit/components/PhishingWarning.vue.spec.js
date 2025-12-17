/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createLocalVue, shallowMount } from '@vue/test-utils'
import PhishingWarning from '../../../components/PhishingWarning.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'

const localVue = createLocalVue()
localVue.mixin(Nextcloud)

describe('PhishingWarning', () => {
	it('Should only show messages from positive checks', async () => {
		const view = shallowMount(PhishingWarning, {
			propsData: {
				phishingData: [
					{
						isPhishing: false,
						message: 'Lorem ipsum',
					},
					{
						isPhishing: true,
						message: 'Ipsum lorem',
					},
				],
			},
			localVue,
		})

		expect(view.text()).not.toContain('Lorem ipsum')
		expect(view.text()).toContain('Ipsum lorem')
	})

	it('Should show the messages of multiple positive checks', async () => {
		const view = shallowMount(PhishingWarning, {
			propsData: {
				phishingData: [{
					isPhishing: true,
					message: 'Lorem ipsum',
				},
				{
					isPhishing: true,
					message: 'Ipsum lorem',
				}],
			},
			localVue,
		})

		expect(view.text()).toContain('Lorem ipsum')
		expect(view.text()).toContain('Ipsum lorem')
	})

	it('Should display the option to expand the list of suspicious links', async () => {
		const view = shallowMount(PhishingWarning, {
			propsData: {
				phishingData: [{
					isPhishing: true,
					message: 'Lorem ipsum',
					type: 'Link',
					additionalData: [
						{
							href: 'http://lorem.ipsum/',
							linkText: 'Stet clita kasd gubergren',
						},
						{
							href: 'http://lorem2.ipsum/',
							linkText: 'At vero eos et',
						},
					],
				}],
			},
			localVue,
		})

		expect(view.text()).toContain('Show suspicious links')
	})

	it('Should hide the list of suspicious links by default', async () => {
		const view = shallowMount(PhishingWarning, {
			propsData: {
				phishingData: [{
					isPhishing: true,
					message: 'Lorem ipsum',
					type: 'Link',
					additionalData: [
						{
							href: 'http://lorem.ipsum/',
							linkText: 'Stet clita kasd gubergren',
						},
						{
							href: 'http://lorem2.ipsum/',
							linkText: 'At vero eos et',
						},
					],
				}],
			},
			localVue,
		})

		expect(view.text()).not.toContain('href: http://lorem.ipsum/')
		expect(view.text()).not.toContain('link text: Stet clita kasd gubergren')
		expect(view.text()).not.toContain('href: http://lorem2.ipsum/')
		expect(view.text()).not.toContain('At vero eos et')
	})

	it('Should show a list of suspicious links when requested', async () => {
		const view = shallowMount(PhishingWarning, {
			propsData: {
				phishingData: [{
					isPhishing: true,
					message: 'Lorem ipsum',
					type: 'Link',
					additionalData: [
						{
							href: 'http://lorem.ipsum/',
							linkText: 'Stet clita kasd gubergren',
						},
						{
							href: 'http://lorem2.ipsum/',
							linkText: 'At vero eos et',
						},
					],
				}],
			},
			data() {
				return { showMore: true }
			},
			localVue,
		})

		expect(view.text()).toContain('href: http://lorem.ipsum/')
		expect(view.text()).toContain('link text: Stet clita kasd gubergren')
		expect(view.text()).toContain('href: http://lorem2.ipsum/')
		expect(view.text()).toContain('At vero eos et')
	})

	it('Should display the option to collapse the list of suspicious links', async () => {
		const view = shallowMount(PhishingWarning, {
			propsData: {
				phishingData: [{
					isPhishing: true,
					message: 'Lorem ipsum',
					type: 'Link',
					additionalData: [
						{
							href: 'http://lorem.ipsum/',
							linkText: 'Stet clita kasd gubergren',
						},
						{
							href: 'http://lorem2.ipsum/',
							linkText: 'At vero eos et',
						},
					],
				}],
			},
			data() {
				return { showMore: true }
			},
			localVue,
		})

		expect(view.text()).toContain('Hide suspicious links')
	})
})
