/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { applyTrustedSenderChange, emailDomain, isSameTrustedSender, isTrustedSenderListed } from '../../../util/trustedSender.js'

describe('trustedSender', () => {
	const alice = { id: 1, email: 'alice@example.test', type: 'individual' }
	const exampleDomain = { id: 2, email: 'example.test', type: 'domain' }

	describe('isSameTrustedSender', () => {
		it('matches the same address and type, whatever the id', () => {
			expect(isSameTrustedSender(alice, { email: 'alice@example.test', type: 'individual' })).toBe(true)
		})

		it('tells individuals and domains apart', () => {
			expect(isSameTrustedSender(exampleDomain, { email: 'example.test', type: 'individual' })).toBe(false)
		})

		it('tells different addresses apart', () => {
			expect(isSameTrustedSender(alice, { email: 'bob@example.test', type: 'individual' })).toBe(false)
		})
	})

	describe('isTrustedSenderListed', () => {
		it('finds a listed sender, whatever the id', () => {
			expect(isTrustedSenderListed([alice, exampleDomain], { email: 'example.test', type: 'domain' })).toBe(true)
		})

		it('doesn\'t find a sender of another type', () => {
			expect(isTrustedSenderListed([alice, exampleDomain], { email: 'example.test', type: 'individual' })).toBe(false)
		})
	})

	describe('emailDomain', () => {
		it('takes the domain after the last at sign', () => {
			expect(emailDomain('bob@example.test@other.test')).toBe('other.test')
		})

		it('has no domain without an at sign', () => {
			expect(emailDomain('undisclosed-recipients')).toBeNull()
		})
	})

	describe('applyTrustedSenderChange', () => {
		it('adds a trusted sender to a new list', () => {
			const trustedSenders = [alice]

			const changed = applyTrustedSenderChange(trustedSenders, { trustedSender: exampleDomain, trust: true })

			expect(changed).toEqual([alice, exampleDomain])
			expect(trustedSenders).toEqual([alice])
		})

		it('removes a sender that is no longer trusted from a new list', () => {
			const trustedSenders = [alice, exampleDomain]

			const changed = applyTrustedSenderChange(trustedSenders, { trustedSender: { email: 'alice@example.test', type: 'individual' }, trust: false })

			expect(changed).toEqual([exampleDomain])
			expect(trustedSenders).toEqual([alice, exampleDomain])
		})

		it('returns the given list when trusting a listed sender', () => {
			const trustedSenders = [alice]

			expect(applyTrustedSenderChange(trustedSenders, { trustedSender: { email: 'alice@example.test', type: 'individual' }, trust: true })).toBe(trustedSenders)
		})

		it('returns the given list when removing a sender that is not listed', () => {
			const trustedSenders = [alice]

			expect(applyTrustedSenderChange(trustedSenders, { trustedSender: exampleDomain, trust: false })).toBe(trustedSenders)
		})
	})
})
