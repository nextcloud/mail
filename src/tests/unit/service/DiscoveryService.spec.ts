/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { DiscoveryEvent } from '../../../models/discovery.ts'

import { discover, DiscoveryError } from '../../../service/DiscoveryService.ts'

vi.mock('@nextcloud/auth', () => ({
	getRequestToken: () => 'token',
}))

vi.mock('@nextcloud/router', () => ({
	generateUrl: (url: string) => `/index.php${url}`,
}))

function streamResponse(chunks: string[], status = 200) {
	const encoder = new TextEncoder()
	let index = 0
	return {
		ok: status >= 200 && status < 300,
		status,
		body: {
			getReader: () => ({
				read: async () => {
					if (index >= chunks.length) {
						return { done: true, value: undefined }
					}
					return { done: false, value: encoder.encode(chunks[index++]) }
				},
			}),
		},
	}
}

const probe = { type: 'probe', protocol: 'jmap', step: 'srv', status: 'running' }
const complete = { type: 'complete', protocol: 'jmap', found: false }

describe('DiscoveryService', () => {
	let fetchMock: ReturnType<typeof vi.fn>

	beforeEach(() => {
		fetchMock = vi.fn()
		vi.stubGlobal('fetch', fetchMock)
	})

	afterEach(() => {
		vi.unstubAllGlobals()
	})

	it('posts the address with the request token', async () => {
		fetchMock.mockResolvedValue(streamResponse(['{"type":"control","disposition":"start"}\n{"type":"control","disposition":"end"}\n']))
		const signal = new AbortController().signal

		await discover('tom@tom.turbo', () => {}, signal)

		expect(fetchMock).toHaveBeenCalledWith('/index.php/apps/mail/api/discovery', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				requesttoken: 'token',
			},
			body: JSON.stringify({ email: 'tom@tom.turbo' }),
			signal,
		})
	})

	it('passes events to the handler as their lines complete', async () => {
		const lines = [
			JSON.stringify({ type: 'control', disposition: 'start' }),
			JSON.stringify(probe),
			JSON.stringify(complete),
			JSON.stringify({ type: 'control', disposition: 'end' }),
		].join('\n') + '\n'
		fetchMock.mockResolvedValue(streamResponse([lines.slice(0, 50), lines.slice(50, 90), lines.slice(90)]))
		const events: DiscoveryEvent[] = []

		await discover('tom@tom.turbo', (event) => events.push(event))

		expect(events).toEqual([probe, complete])
	})

	it('fails when the stream ends without an end event', async () => {
		fetchMock.mockResolvedValue(streamResponse([`{"type":"control","disposition":"start"}\n${JSON.stringify(probe)}\n`]))
		const events: DiscoveryEvent[] = []

		await expect(discover('tom@tom.turbo', (event) => events.push(event))).rejects.toThrow(DiscoveryError)
		expect(events).toEqual([probe])
	})

	it('fails with the status of an error response', async () => {
		fetchMock.mockResolvedValue(streamResponse([], 429))

		const error = await discover('tom@tom.turbo', () => {}).catch((e) => e)

		expect(error).toBeInstanceOf(DiscoveryError)
		expect(error.status).toBe(429)
	})
})
