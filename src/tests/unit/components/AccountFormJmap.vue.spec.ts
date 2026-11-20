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

	it('emits a new config when a field changes', () => {
		const view = mount()

		view.findComponent('#man-jmap-path').vm.$emit('update:modelValue', '/jmap/session')

		expect(view.emitted('update:modelValue')).toEqual([[{ ...config, path: '/jmap/session' }]])
	})

	it('switches the port with the security mode', () => {
		const view = mount()

		view.findComponent('#man-jmap-sec-none').vm.$emit('update:modelValue', 'no')

		expect(view.emitted('update:modelValue')).toEqual([[{ ...config, sslMode: 'no', port: 80 }]])
		expect(view.emitted('change')).toHaveLength(1)
	})

	it('makes the password optional when editing', () => {
		const view = mount({ passwordOptional: true })

		const password = view.findComponent('#man-jmap-password')

		expect(password.props('label')).toBe('Leave blank to not change password')
		expect(password.vm.$attrs.required).toBe(false)
	})
})
