/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { DiscoveryCandidate, DiscoveryEndpoint } from '../../../models/discovery.ts'

import {
	candidateFromEvent,
	initialDiscoveryProgress,
	isUsableCandidate,
	orderCandidates,
	toImapServerConfig,
	toJmapServerConfig,
	updateDiscoveryProgress,
} from '../../../util/discovery.ts'

const imapEndpoint: DiscoveryEndpoint = { host: 'imap.tom.turbo', port: 993, security: 'ssl', username: 'tom@tom.turbo' }
const smtpEndpoint: DiscoveryEndpoint = { host: 'smtp.tom.turbo', port: 465, security: 'ssl', username: 'tom@tom.turbo' }
const jmapEndpoint: DiscoveryEndpoint = { host: 'jmap.tom.turbo', port: 443, security: 'ssl', username: 'tom@tom.turbo' }

const imapCandidate: DiscoveryCandidate = { protocol: 'imap', imap: { incoming: imapEndpoint, outgoing: smtpEndpoint } }
const jmapCandidate: DiscoveryCandidate = { protocol: 'jmap', jmap: { endpoint: jmapEndpoint, path: '/.well-known/jmap', authentication: ['password'] } }

describe('discovery', () => {
	it('tracks the source and result of each protocol', () => {
		let progress = initialDiscoveryProgress()

		progress = updateDiscoveryProgress(progress, { type: 'probe', protocol: 'imap', step: 'srv', status: 'running' })
		progress = updateDiscoveryProgress(progress, { type: 'complete', protocol: 'jmap', found: true })

		expect(progress).toEqual([
			{ protocol: 'jmap', source: null, complete: true, found: true },
			{ protocol: 'imap', source: 'srv', complete: false, found: false },
		])
	})

	it('ignores events without progress', () => {
		const progress = initialDiscoveryProgress()

		const updated = updateDiscoveryProgress(progress, { type: 'candidate', protocol: 'imap', source: 'srv', config: { imap: imapCandidate.imap } })

		expect(updated).toBe(progress)
	})

	it('builds candidates from candidate events', () => {
		expect(candidateFromEvent({ type: 'candidate', protocol: 'jmap', source: 'well-known', config: { jmap: jmapCandidate.jmap } })).toEqual(jmapCandidate)
		expect(candidateFromEvent({ type: 'candidate', protocol: 'imap', source: 'autoconfig', config: { imap: imapCandidate.imap } })).toEqual(imapCandidate)
	})

	it('prefers JMAP over IMAP', () => {
		expect(orderCandidates([imapCandidate, jmapCandidate])).toEqual([jmapCandidate, imapCandidate])
	})

	it('needs SMTP to use an IMAP candidate', () => {
		expect(isUsableCandidate(imapCandidate)).toBe(true)
		expect(isUsableCandidate({ protocol: 'imap', imap: { incoming: imapEndpoint, outgoing: null } })).toBe(false)
	})

	it('needs password authentication to use a JMAP candidate', () => {
		const withAuthentication = (authentication: ('oauth2' | 'password')[]): DiscoveryCandidate => ({ protocol: 'jmap', jmap: { ...jmapCandidate.jmap, authentication } })

		expect(isUsableCandidate(withAuthentication(['oauth2', 'password']))).toBe(true)
		expect(isUsableCandidate(withAuthentication([]))).toBe(true)
		expect(isUsableCandidate(withAuthentication(['oauth2']))).toBe(false)
	})

	it('fills the IMAP form from a candidate', () => {
		expect(toImapServerConfig(imapCandidate.imap, 'secret')).toEqual({
			imapHost: 'imap.tom.turbo',
			imapPassword: 'secret',
			imapPort: 993,
			imapSslMode: 'ssl',
			imapUser: 'tom@tom.turbo',
			smtpHost: 'smtp.tom.turbo',
			smtpPassword: 'secret',
			smtpPort: 465,
			smtpSslMode: 'ssl',
			smtpUser: 'tom@tom.turbo',
			syncSmtpCredentials: false,
		})
	})

	it('leaves SMTP open for input without an outgoing server', () => {
		const config = toImapServerConfig({ incoming: imapEndpoint, outgoing: null }, 'secret')

		expect(config.smtpHost).toBe('')
		expect(config.smtpUser).toBe('tom@tom.turbo')
		expect(config.syncSmtpCredentials).toBe(true)
	})

	it('fills the JMAP form from a candidate', () => {
		expect(toJmapServerConfig(jmapCandidate.jmap, 'secret')).toEqual({
			host: 'jmap.tom.turbo',
			password: 'secret',
			path: '/.well-known/jmap',
			port: 443,
			sslMode: 'yes',
			user: 'tom@tom.turbo',
		})
		expect(toJmapServerConfig({ ...jmapCandidate.jmap, endpoint: { ...jmapEndpoint, security: 'none', port: 80 } }, 'secret').sslMode).toBe('no')
	})
})
