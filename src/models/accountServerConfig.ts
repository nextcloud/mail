/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export const PROTOCOL_IMAP = 'imap'
export const PROTOCOL_JMAP = 'jmap'

export type ImapSslMode = 'none' | 'ssl' | 'tls'

export interface ImapServerConfig {
	imapHost: string
	imapPassword: string
	imapPort: number | string
	imapSslMode: ImapSslMode
	imapUser: string
	smtpHost: string
	smtpPassword: string
	smtpPort: number | string
	smtpSslMode: ImapSslMode
	smtpUser: string
	syncSmtpCredentials: boolean
}

export type JmapSslMode = 'yes' | 'no'

export interface JmapServerConfig {
	host: string
	password: string
	path: string
	port: number | string
	sslMode: JmapSslMode
	user: string
}
