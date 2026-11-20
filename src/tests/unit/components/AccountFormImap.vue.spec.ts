/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ImapServerConfig } from '../../../models/accountServerConfig.ts'

import { shallowMount } from '@vue/test-utils'
import AccountFormImap from '../../../components/AccountFormImap.vue'

describe('AccountFormImap', () => {
	const config: ImapServerConfig = {
		imapHost: 'mail.tom.turbo',
		imapPassword: 'secret',
		imapPort: 993,
		imapSslMode: 'ssl',
		imapUser: 'tom',
		smtpHost: '',
		smtpPassword: '',
		smtpPort: 587,
		smtpSslMode: 'tls',
		smtpUser: '',
		syncSmtpCredentials: true,
	}

	const mount = (props = {}) => shallowMount(AccountFormImap, {
		props: {
			modelValue: config,
			...props,
		},
	})

	it('emits a new config when a field changes', () => {
		const view = mount()

		view.findComponent('#man-imap-user').vm.$emit('update:modelValue', 'tom@tom.turbo')

		expect(view.emitted('update:modelValue')).toEqual([[{ ...config, imapUser: 'tom@tom.turbo' }]])
	})

	it('switches the IMAP and SMTP ports with the security mode', () => {
		const view = mount()

		view.findComponent('#man-imap-sec-tls').vm.$emit('update:modelValue', 'tls')
		view.findComponent('#man-smtp-sec-ssl').vm.$emit('update:modelValue', 'ssl')

		expect(view.emitted('update:modelValue')).toEqual([
			[{ ...config, imapSslMode: 'tls', imapPort: 143 }],
			[{ ...config, smtpSslMode: 'ssl', smtpPort: 465 }],
		])
		expect(view.emitted('change')).toHaveLength(2)
	})

	it('copies the IMAP credentials to SMTP', () => {
		const view = mount()

		view.findComponent('#man-imap-host').vm.$emit('change')

		expect(view.emitted('update:modelValue')).toEqual([[{
			...config,
			smtpHost: 'mail.tom.turbo',
			smtpPassword: 'secret',
			smtpUser: 'tom',
		}]])
	})

	it('stops copying credentials once SMTP is edited', async () => {
		const view = mount()

		view.findComponent('#man-smtp-host').vm.$emit('change')
		await view.setProps({ modelValue: { ...config, syncSmtpCredentials: false } })
		view.findComponent('#man-imap-host').vm.$emit('change')

		expect(view.emitted('update:modelValue')).toEqual([[{ ...config, syncSmtpCredentials: false }]])
		expect(view.emitted('change')).toHaveLength(2)
	})

	it('hides the password fields', () => {
		const view = mount({ hidePasswords: true })

		expect(view.find('#man-imap-password').exists()).toBe(false)
		expect(view.find('#man-smtp-password').exists()).toBe(false)
	})
})
