/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ImapServerConfig, JmapServerConfig } from '../models/accountServerConfig.ts'
import type {
	DiscoveryCandidate,
	DiscoveryEvent,
	DiscoveryProgress,
	DiscoveryProtocol,
	ImapDiscoveryConfig,
	JmapDiscoveryConfig,
} from '../models/discovery.ts'

const DISCOVERY_PROTOCOLS: DiscoveryProtocol[] = ['jmap', 'imap']

/**
 * Progress of each protocol before the first event arrives
 */
export function initialDiscoveryProgress(): DiscoveryProgress[] {
	const progress: DiscoveryProgress[] = []
	for (const protocol of DISCOVERY_PROTOCOLS) {
		progress.push({ protocol, source: null, complete: false, found: false })
	}
	return progress
}

/**
 * Apply a discovery event to the progress of its protocol
 *
 * @param progress the current progress
 * @param event the received event
 */
export function updateDiscoveryProgress(progress: DiscoveryProgress[], event: DiscoveryEvent): DiscoveryProgress[] {
	if (event.type !== 'probe' && event.type !== 'complete') {
		return progress
	}

	const updated: DiscoveryProgress[] = []
	for (const entry of progress) {
		if (entry.protocol !== event.protocol) {
			updated.push(entry)
		} else if (event.type === 'probe') {
			updated.push({ ...entry, source: event.step })
		} else {
			updated.push({ ...entry, complete: true, found: event.found })
		}
	}
	return updated
}

/**
 * @param event a candidate event
 */
export function candidateFromEvent(event: Extract<DiscoveryEvent, { type: 'candidate' }>): DiscoveryCandidate {
	if (event.protocol === 'jmap') {
		return { protocol: 'jmap', jmap: event.config.jmap }
	}
	return { protocol: 'imap', imap: event.config.imap }
}

/**
 * Order candidates by preference, JMAP before IMAP
 *
 * @param candidates the discovered candidates
 */
export function orderCandidates(candidates: DiscoveryCandidate[]): DiscoveryCandidate[] {
	const ordered: DiscoveryCandidate[] = []
	for (const protocol of DISCOVERY_PROTOCOLS) {
		for (const candidate of candidates) {
			if (candidate.protocol === protocol) {
				ordered.push(candidate)
			}
		}
	}
	return ordered
}

/**
 * Whether an account can be created from the candidate without further input
 *
 * IMAP needs an SMTP server, and JMAP must accept password authentication.
 *
 * @param candidate a discovered candidate
 */
export function isUsableCandidate(candidate: DiscoveryCandidate): boolean {
	if (candidate.protocol === 'imap') {
		return candidate.imap.outgoing !== null
	}
	const authentication = candidate.jmap.authentication
	return authentication.length === 0 || authentication.includes('password')
}

/**
 * @param imap the discovered IMAP configuration
 * @param password the password entered by the user
 */
export function toImapServerConfig(imap: ImapDiscoveryConfig, password: string): ImapServerConfig {
	const { incoming, outgoing } = imap
	return {
		imapHost: incoming.host,
		imapPassword: password,
		imapPort: incoming.port,
		imapSslMode: incoming.security,
		imapUser: incoming.username,
		smtpHost: outgoing?.host ?? '',
		smtpPassword: password,
		smtpPort: outgoing?.port ?? 587,
		smtpSslMode: outgoing?.security ?? 'tls',
		smtpUser: outgoing?.username ?? incoming.username,
		syncSmtpCredentials: outgoing === null,
	}
}

/**
 * @param jmap the discovered JMAP configuration
 * @param password the password entered by the user
 */
export function toJmapServerConfig(jmap: JmapDiscoveryConfig, password: string): JmapServerConfig {
	const { endpoint, path } = jmap
	return {
		host: endpoint.host,
		password,
		path,
		port: endpoint.port,
		sslMode: endpoint.security === 'ssl' ? 'yes' : 'no',
		user: endpoint.username,
	}
}
