/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { formatDateTimeFromUnix } from '../../../util/formatDateTime.js'
import { buildHtmlMessageContent, buildMessageHeader, waitForImages } from '../../../util/printMessage.ts'

describe('printMessage', () => {
	describe('buildMessageHeader', () => {
		const envelope = {
			subject: 'Hello there',
			from: [{ label: 'Alice', email: 'alice@example.com' }],
			to: [{ label: 'Bob', email: 'bob@example.com' }],
			cc: [],
			bcc: [],
			dateInt: 1700000000,
		}

		it('renders the subject first', () => {
			const header = buildMessageHeader(document, envelope)

			expect(header.firstChild.textContent).toBe('Hello there')
		})

		it('falls back to "No subject" when the subject is empty', () => {
			const header = buildMessageHeader(document, { ...envelope, subject: '' })

			expect(header.firstChild.textContent).toBe('No subject')
		})

		it('gives the subject an explicit inline font size so email CSS cannot enlarge it', () => {
			const header = buildMessageHeader(document, envelope)

			expect(header.firstChild.style.fontSize).not.toBe('')
		})

		it('lists From, To and Date in that order', () => {
			const header = buildMessageHeader(document, envelope)

			const lines = Array.from(header.querySelectorAll('.print-message-header > div'))
				.slice(1)
				.map((line) => line.textContent)
			expect(lines).toEqual([
				'From: Alice <alice@example.com>',
				'To: Bob <bob@example.com>',
				`Date: ${formatDateTimeFromUnix(envelope.dateInt)}`,
			])
		})

		it('omits Cc and Bcc lines when there are no such recipients', () => {
			const header = buildMessageHeader(document, envelope)

			const lines = Array.from(header.querySelectorAll('div')).map((line) => line.textContent)
			expect(lines.some((line) => line.startsWith('Cc:'))).toBe(false)
			expect(lines.some((line) => line.startsWith('Bcc:'))).toBe(false)
		})

		it('includes Cc and Bcc lines when present, with all recipients', () => {
			const header = buildMessageHeader(document, {
				...envelope,
				cc: [{ label: 'Carol', email: 'carol@example.com' }],
				bcc: [{ label: 'Dave', email: 'dave@example.com' }, { label: '', email: 'eve@example.com' }],
			})

			const lines = Array.from(header.querySelectorAll('div')).map((line) => line.textContent)
			expect(lines).toContain('Cc: Carol <carol@example.com>')
			expect(lines).toContain('Bcc: Dave <dave@example.com>, eve@example.com')
		})

		it('omits the To line when there are no recipients', () => {
			const header = buildMessageHeader(document, { ...envelope, to: [] })

			const lines = Array.from(header.querySelectorAll('div')).map((line) => line.textContent)
			expect(lines.some((line) => line.startsWith('To:'))).toBe(false)
		})

		it('renders a crafted subject as literal text, never as markup', () => {
			const header = buildMessageHeader(document, {
				...envelope,
				subject: '<img src=x onerror=alert(1)>',
			})

			expect(header.firstChild.textContent).toBe('<img src=x onerror=alert(1)>')
			expect(header.querySelector('img')).toBeNull()
		})

		it('renders a crafted sender name as literal text, never as markup', () => {
			const header = buildMessageHeader(document, {
				...envelope,
				from: [{ label: '<script>alert(1)</script>', email: 'x@example.com' }],
			})

			expect(header.querySelector('script')).toBeNull()
			expect(header.textContent).toContain('<script>alert(1)</script> <x@example.com>')
		})
	})

	describe('buildHtmlMessageContent', () => {
		it('keeps the message body and its styles so it does not fall back to unstyled defaults', () => {
			const sourceDocument = new DOMParser().parseFromString(
				'<html><head><style>p { color: red; }</style></head><body><p>hello</p></body></html>',
				'text/html',
			)

			const wrapper = buildHtmlMessageContent(document, sourceDocument)

			expect(wrapper.querySelector('style').textContent).toContain('color: red')
			expect(wrapper.querySelector('p').textContent).toBe('hello')
		})

		it('keeps a style block that the backend injected at the start of the body', () => {
			const sourceDocument = new DOMParser().parseFromString(
				'<html><head></head><body><style>p { color: green; }</style><p>hi</p></body></html>',
				'text/html',
			)

			const wrapper = buildHtmlMessageContent(document, sourceDocument)

			expect(wrapper.textContent).toContain('hi')
			expect(wrapper.querySelector('style').textContent).toContain('color: green')
		})

		it('strips scripts and the iframe-resizer marker from the printed content', () => {
			const sourceDocument = new DOMParser().parseFromString(
				'<html><head></head><body><p>hi</p><script>alert(1)</script><div data-iframe-size></div></body></html>',
				'text/html',
			)

			const wrapper = buildHtmlMessageContent(document, sourceDocument)

			expect(wrapper.querySelector('script')).toBeNull()
			expect(wrapper.querySelector('[data-iframe-size]')).toBeNull()
			expect(wrapper.querySelector('p').textContent).toBe('hi')
		})
	})

	describe('waitForImages', () => {
		it('resolves immediately when there are no images', async () => {
			const container = document.createElement('div')

			await expect(waitForImages(container)).resolves.toBeUndefined()
		})

		it('resolves immediately when every image is already complete', async () => {
			const container = document.createElement('div')
			const img = document.createElement('img')
			Object.defineProperty(img, 'complete', { value: true })
			container.appendChild(img)

			await expect(waitForImages(container)).resolves.toBeUndefined()
		})

		it('waits for pending images to load or error before resolving', async () => {
			const container = document.createElement('div')
			const loadingImg = document.createElement('img')
			Object.defineProperty(loadingImg, 'complete', { value: false })
			const erroringImg = document.createElement('img')
			Object.defineProperty(erroringImg, 'complete', { value: false })
			container.appendChild(loadingImg)
			container.appendChild(erroringImg)

			let resolved = false
			waitForImages(container).then(() => {
				resolved = true
			})

			await Promise.resolve()
			expect(resolved).toBe(false)

			loadingImg.dispatchEvent(new Event('load'))
			await Promise.resolve()
			expect(resolved).toBe(false)

			erroringImg.dispatchEvent(new Event('error'))
			await new Promise((resolve) => setTimeout(resolve))
			expect(resolved).toBe(true)
		})
	})
})
