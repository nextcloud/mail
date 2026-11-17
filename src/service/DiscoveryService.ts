/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { DiscoveryEvent } from '../models/discovery.ts'

import { getRequestToken } from '@nextcloud/auth'
import { generateUrl } from '@nextcloud/router'

export class DiscoveryError extends Error {
	readonly status: number | null

	constructor(message: string, status: number | null = null) {
		super(message)
		this.name = 'DiscoveryError'
		this.status = status
	}
}

export type DiscoveryEventHandler = (event: DiscoveryEvent) => void

/**
 * Discover the mail server configurations of an address
 *
 * The response is streamed as newline delimited JSON. Each event is passed
 * to the callback as soon as its line is complete.
 *
 * @param email the address to discover
 * @param onEvent receives every probe, candidate and complete event
 * @param signal aborts the discovery
 * @throws {DiscoveryError} if the request fails or the stream ends without an end event
 */
export async function discover(email: string, onEvent: DiscoveryEventHandler, signal?: AbortSignal): Promise<void> {
	const response = await fetch(generateUrl('/apps/mail/api/discovery'), {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
			requesttoken: getRequestToken() ?? '',
		},
		body: JSON.stringify({ email }),
		signal,
	})
	if (!response.ok || response.body === null) {
		throw new DiscoveryError(`Discovery failed with status ${response.status}`, response.status)
	}

	const reader = response.body.getReader()
	const decoder = new TextDecoder()
	let buffer = ''
	let ended = false
	let done = false
	while (!done) {
		const chunk = await reader.read()
		done = chunk.done
		buffer += decoder.decode(chunk.value, { stream: !done })

		const lines = buffer.split('\n')
		buffer = lines.pop() ?? ''
		for (const line of lines) {
			if (line.trim() === '') {
				continue
			}
			const event = JSON.parse(line) as DiscoveryEvent
			if (event.type === 'control') {
				ended = event.disposition === 'end'
				continue
			}
			onEvent(event)
		}
	}

	if (!ended) {
		throw new DiscoveryError('Discovery ended unexpectedly')
	}
}
