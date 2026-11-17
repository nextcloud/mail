/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export type DiscoveryProtocol = 'imap' | 'jmap'

export type DiscoverySource = 'autoconfig' | 'guess' | 'srv' | 'well-known'

export type DiscoveryProbeStatus = 'found' | 'none' | 'running'

export interface DiscoveryEndpoint {
	host: string
	port: number
	security: 'none' | 'ssl' | 'tls'
	username: string
}

export interface ImapDiscoveryConfig {
	incoming: DiscoveryEndpoint
	outgoing: DiscoveryEndpoint | null
}

export interface JmapDiscoveryConfig {
	authentication: ('oauth2' | 'password')[]
	endpoint: DiscoveryEndpoint
	path: string
}

export type DiscoveryEvent
	= | { disposition: 'end' | 'start', type: 'control' }
		| { protocol: DiscoveryProtocol, status: DiscoveryProbeStatus, step: DiscoverySource, type: 'probe' }
		| { config: { imap: ImapDiscoveryConfig }, protocol: 'imap', source: DiscoverySource, type: 'candidate' }
		| { config: { jmap: JmapDiscoveryConfig }, protocol: 'jmap', source: DiscoverySource, type: 'candidate' }
		| { found: boolean, protocol: DiscoveryProtocol, type: 'complete' }

export type DiscoveryCandidate
	= | { imap: ImapDiscoveryConfig, protocol: 'imap' }
		| { jmap: JmapDiscoveryConfig, protocol: 'jmap' }

export interface DiscoveryProgress {
	complete: boolean
	found: boolean
	protocol: DiscoveryProtocol
	source: DiscoverySource | null
}
