/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { JmapServerConfig } from '../../../models/accountServerConfig.ts'

import { shallowMount } from '@vue/test-utils'
import AccountFormJmap from '../../../components/AccountFormJmap.vue'

describe('AccountFormJmap', () => {
	const config: JmapServerConfig = {
		host: 'jmap.tom.turbo',
		password: '',
		path: '',
		port: 443,
		sslMode: 'yes',
		user: 'tom',
	}

	const mount = (props = {}) => shallowMount(AccountFormJmap, {
		props: {
			modelValue: config,
			...props,
		},
	})

	const typeServerUrl = (view: ReturnType<typeof mount>, value: string) => {
		view.findComponent('#man-jmap-url').vm.$emit('update:modelValue', value)
	}

	it('emits a new config when a field changes', () => {
		const view = mount()

		view.findComponent('#man-jmap-user').vm.$emit('update:modelValue', 'jerry')

		expect(view.emitted('update:modelValue')).toEqual([[{ ...config, user: 'jerry' }]])
	})

	it.each([
		['https://jmap.tom.turbo', { ...config, host: 'jmap.tom.turbo', path: '' }],
		['https://jmap.tom.turbo/.well-known/jmap', { ...config, path: '/.well-known/jmap' }],
		['http://jmap.tom.turbo:8080/jmap/session', { ...config, sslMode: 'no', port: 8080, path: '/jmap/session' }],
		['http://jmap.tom.turbo:80', { ...config, sslMode: 'no', port: 80 }],
		['https://[2001:db8::1]/', { ...config, host: '[2001:db8::1]' }],
		['https://jmap.tom.turbo:443', { ...config, host: 'jmap.tom.turbo' }],
		['jmap.tom.turbo', config],
		[' jmap.tom.turbo/jmap ', { ...config, path: '/jmap' }],
		['jmap.tom.turbo:8443', { ...config, port: 8443 }],
	])('splits the server URL %s into the location fields', (url, expected) => {
		const view = mount()

		typeServerUrl(view, url)

		expect(view.emitted('update:modelValue')).toEqual([[expected]])
	})

	it.each(['ftp://jmap.tom.turbo', 'jmap tom.turbo', 'https://'])('clears the host and shows an error for %s', async (url) => {
		const view = mount()

		typeServerUrl(view, url)
		await view.vm.$nextTick()

		expect(view.emitted('update:modelValue')).toEqual([[{ ...config, host: '', path: '', port: 443, sslMode: 'yes' }]])
		expect(view.findComponent('#man-jmap-url').props('error')).toBe(true)
		expect(view.findComponent('#man-jmap-url').props('helperText')).toBe('Enter a server name or an http(s) address')
	})

	it('shows no error for an empty server URL', async () => {
		const view = mount()

		typeServerUrl(view, '')
		await view.vm.$nextTick()

		expect(view.findComponent('#man-jmap-url').props('error')).toBe(false)
		expect(view.findComponent('#man-jmap-url').props('helperText')).toBe('For example mail.example.com or https://mail.example.com/.well-known/jmap')
	})

	it.each([
		[config, 'https://jmap.tom.turbo'],
		[{ ...config, path: '/.well-known/jmap' }, 'https://jmap.tom.turbo/.well-known/jmap'],
		[{ ...config, sslMode: 'no', port: 8080, path: '/jmap' }, 'http://jmap.tom.turbo:8080/jmap'],
		[{ ...config, host: '' }, ''],
	] as [JmapServerConfig, string][])('shows the stored settings as one URL', (modelValue, url) => {
		const view = mount({ modelValue })

		expect(view.findComponent('#man-jmap-url').props('modelValue')).toBe(url)
	})

	it('keeps the typed text while it matches the settings', async () => {
		const view = mount()

		typeServerUrl(view, 'jmap.tom.turbo/jmap')
		await view.setProps({ modelValue: { ...config, path: '/jmap' } })

		expect(view.findComponent('#man-jmap-url').props('modelValue')).toBe('jmap.tom.turbo/jmap')
	})

	it('shows settings replaced by the parent', async () => {
		const view = mount()

		await view.setProps({ modelValue: { ...config, host: 'other.tom.turbo', path: '/jmap' } })

		expect(view.findComponent('#man-jmap-url').props('modelValue')).toBe('https://other.tom.turbo/jmap')
	})

	it('makes the password optional when editing', () => {
		const view = mount({ passwordOptional: true })

		const password = view.findComponent('#man-jmap-password')

		expect(password.props('label')).toBe('Leave blank to not change password')
		expect(password.vm.$attrs.required).toBe(false)
	})
})
