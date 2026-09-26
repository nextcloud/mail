/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { formatDateTimeFromUnix } from '../../../util/formatDateTime.js'
import {
	BROWSER_PRINT_NOTICE_ID,
	BROWSER_PRINT_STYLE,
	buildBrowserPrintNotice,
	buildMessageContent,
	buildMessageHeader,
	isPrintShortcut,
	PRINT_DOCUMENT_STYLE,
	PRINT_FRAME_ID,
	renderHtmlMessage,
	renderPlainTextMessage,
	waitForImages,
} from '../../../util/printMessage.ts'

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

		it('falls back to "No subject" when the subject is only whitespace', () => {
			const header = buildMessageHeader(document, { ...envelope, subject: '   \t\n' })

			expect(header.firstChild.textContent).toBe('No subject')
		})

		it('falls back to "No subject" when the subject is missing', () => {
			const header = buildMessageHeader(document, { ...envelope, subject: undefined })

			expect(header.firstChild.textContent).toBe('No subject')
		})

		it('gives the subject its own class', () => {
			const header = buildMessageHeader(document, envelope)

			expect(header.firstChild.className).toBe('print-message-header__subject')
		})

		it('brings its own looks, so it does not depend on a stylesheet reaching it', () => {
			const header = buildMessageHeader(document, envelope)

			expect(header.style.margin).not.toBe('')
			expect(header.firstChild.style.fontSize).not.toBe('')
			expect(header.querySelector('.print-message-header__line').style.fontSize).not.toBe('')
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

			expect(header.innerHTML).not.toContain('Cc:')
			expect(header.innerHTML).not.toContain('Bcc:')
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

			expect(header.innerHTML).not.toContain('To:')
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

	describe('buildMessageContent', () => {
		const parse = (html) => new DOMParser().parseFromString(html, 'text/html')

		it('keeps the message body and its styles so it does not fall back to unstyled defaults', () => {
			const source = parse('<html><head><style>p { color: red; }</style></head><body><p>hello</p></body></html>')

			const content = buildMessageContent(source)

			expect(content.querySelector('style').textContent).toContain('color: red')
			expect(content.querySelector('p').textContent).toBe('hello')
		})

		it('keeps head and body, so the selectors an email styles them with keep matching', () => {
			const source = parse('<html><head><style>body { color: red; }</style></head><body><p>hello</p></body></html>')

			const content = buildMessageContent(source)

			expect(content.tagName).toBe('HTML')
			expect(content.querySelector('head')).not.toBeNull()
			expect(content.querySelector('body')).not.toBeNull()
		})

		it('keeps a style block that the backend injected at the start of the body', () => {
			const source = parse('<html><head></head><body><style>p { color: green; }</style><p>hi</p></body></html>')

			const content = buildMessageContent(source)

			expect(content.textContent).toContain('hi')
			expect(content.querySelector('style').textContent).toContain('color: green')
		})

		it('strips scripts and the iframe-resizer marker from the printed content', () => {
			const source = parse('<html><head></head><body><p>hi</p><script>alert(1)</script><div data-iframe-size></div></body></html>')

			const content = buildMessageContent(source)

			expect(content.querySelector('script')).toBeNull()
			expect(content.querySelector('[data-iframe-size]')).toBeNull()
			expect(content.querySelector('p').textContent).toBe('hi')
		})

		it('prints the images the reader unblocked, not the blocked originals', () => {
			const source = parse('<html><head></head><body><img data-original-src="/cat.png"></body></html>')
			source.querySelector('img').setAttribute('src', '/cat.png')

			const content = buildMessageContent(source)

			expect(content.querySelector('img').getAttribute('src')).toContain('/cat.png')
		})

		it('resolves relative urls against the message, not against the app', () => {
			const source = parse('<html><head></head><body><img src="cat.png"><a href="/inbox">back</a></body></html>')
			Object.defineProperty(source, 'baseURI', { value: 'https://cloud.example.com/apps/mail/message/1/html' })

			const content = buildMessageContent(source)

			expect(content.querySelector('img').getAttribute('src')).toBe('https://cloud.example.com/apps/mail/message/1/cat.png')
			expect(content.querySelector('a').getAttribute('href')).toBe('https://cloud.example.com/inbox')
		})

		it('resolves every candidate of a srcset', () => {
			const source = parse('<html><head></head><body><img srcset="cat.png 1x, cat@2x.png 2x"></body></html>')
			Object.defineProperty(source, 'baseURI', { value: 'https://cloud.example.com/apps/mail/message/1/html' })

			const content = buildMessageContent(source)

			expect(content.querySelector('img').getAttribute('srcset')).toBe('https://cloud.example.com/apps/mail/message/1/cat.png 1x, https://cloud.example.com/apps/mail/message/1/cat@2x.png 2x')
		})

		it('leaves an url that is not relative to anything alone', () => {
			const source = parse('<html><head></head><body><img src="cid:part1@example.com"><a href="mailto:alice@example.com">mail</a></body></html>')
			Object.defineProperty(source, 'baseURI', { value: 'https://cloud.example.com/apps/mail/message/1/html' })

			const content = buildMessageContent(source)

			expect(content.querySelector('img').getAttribute('src')).toBe('cid:part1@example.com')
			expect(content.querySelector('a').getAttribute('href')).toBe('mailto:alice@example.com')
		})

		it('applies a base the message carries and drops it, because it has no effect in a shadow root', () => {
			const source = parse('<html><head><base href="https://cloud.example.com/original/"></head><body><img src="cat.png"></body></html>')

			const content = buildMessageContent(source)

			expect(content.querySelector('base')).toBeNull()
			expect(content.querySelector('img').getAttribute('src')).toBe('https://cloud.example.com/original/cat.png')
		})

		it('does not touch the live message document', () => {
			const source = parse('<html><head><base href="https://cloud.example.com/original/"></head><body><p>hi</p><script>alert(1)</script><img src="cat.png"></body></html>')

			buildMessageContent(source)

			expect(source.querySelector('script')).not.toBeNull()
			expect(source.querySelector('base')).not.toBeNull()
			expect(source.querySelector('img').getAttribute('src')).toBe('cat.png')
		})
	})

	describe('renderHtmlMessage', () => {
		let parent

		beforeEach(() => {
			parent = document.createElement('div')
			document.body.appendChild(parent)
		})

		afterEach(() => {
			parent.remove()
		})

		const source = () => new DOMParser().parseFromString(
			'<html><head><style>p { color: red; }</style></head><body><p>hello</p></body></html>',
			'text/html',
		)

		it('renders the message into a shadow root of its own', () => {
			const host = renderHtmlMessage(parent, source())

			expect(host.className).toBe('print-message-content')
			expect(host.shadowRoot.querySelector('p').textContent).toBe('hello')
		})

		it('renders the message as part of the print document, so it can break across pages', () => {
			renderHtmlMessage(parent, source())

			expect(parent.querySelector('iframe')).toBeNull()
			expect(parent.firstElementChild.style.height).toBe('')
		})

		it('keeps a message stylesheet out of the printed document', () => {
			const host = renderHtmlMessage(parent, source())

			expect(parent.ownerDocument.querySelector('style')).toBeNull()
			expect(host.querySelector('style')).toBeNull()
		})

		it('keeps the messages of a thread from restyling each other', () => {
			const other = new DOMParser().parseFromString(
				'<html><head><style>p { color: blue; }</style></head><body><p>other</p></body></html>',
				'text/html',
			)

			const first = renderHtmlMessage(parent, source())
			const second = renderHtmlMessage(parent, other)

			expect(first.shadowRoot.querySelectorAll('style')).toHaveLength(1)
			expect(first.shadowRoot.querySelector('style').textContent).toContain('color: red')
			expect(second.shadowRoot.querySelectorAll('style')).toHaveLength(1)
			expect(second.shadowRoot.querySelector('style').textContent).toContain('color: blue')
		})

		it('keeps a print header next to it out of reach of the message styles', () => {
			const header = buildMessageHeader(document, { subject: 'Grazie da Italo!', from: [], to: [] })
			const message = document.createElement('div')
			message.appendChild(header)
			parent.appendChild(message)

			const host = renderHtmlMessage(message, source())

			expect(host.shadowRoot.querySelector('.print-message-header')).toBeNull()
			expect(message.firstElementChild).toBe(header)
		})
	})

	describe('renderPlainTextMessage', () => {
		let parent

		beforeEach(() => {
			parent = document.createElement('div')
			document.body.appendChild(parent)
		})

		afterEach(() => {
			parent.remove()
		})

		const source = () => {
			const container = document.createElement('div')
			container.id = 'message-container'
			container.innerHTML = 'hello<br />  indented'
			return container
		}

		it('renders the message into a shadow root of its own', () => {
			const host = renderPlainTextMessage(parent, source())

			expect(host.className).toBe('print-message-content')
			expect(host.shadowRoot.textContent).toContain('indented')
		})

		it('brings the whitespace handling the app stylesheet cannot reach into the shadow root', () => {
			const host = renderPlainTextMessage(parent, source())

			expect(host.shadowRoot.querySelector('style').textContent).toContain('white-space: pre-wrap')
			expect(host.shadowRoot.querySelector('.print-message-text')).not.toBeNull()
		})

		it('drops the id, which only ever identified the live message body', () => {
			const host = renderPlainTextMessage(parent, source())

			expect(host.shadowRoot.querySelector('#message-container')).toBeNull()
		})

		it('does not touch the live message body', () => {
			const container = source()

			renderPlainTextMessage(parent, container)

			expect(container.id).toBe('message-container')
			expect(container.parentNode).toBeNull()
		})
	})

	describe('isPrintShortcut', () => {
		it('matches Ctrl+P on non-Apple platforms', () => {
			expect(isPrintShortcut({ code: 'KeyP', key: 'p', ctrlKey: true, metaKey: false }, false)).toBe(true)
		})

		it('ignores Cmd+P on non-Apple platforms', () => {
			expect(isPrintShortcut({ code: 'KeyP', key: 'p', ctrlKey: false, metaKey: true }, false)).toBe(false)
		})

		it('matches Cmd+P on Apple platforms', () => {
			expect(isPrintShortcut({ code: 'KeyP', key: 'p', ctrlKey: false, metaKey: true }, true)).toBe(true)
		})

		it('ignores Ctrl+P on Apple platforms so native bindings keep working', () => {
			expect(isPrintShortcut({ code: 'KeyP', key: 'p', ctrlKey: true, metaKey: false }, true)).toBe(false)
		})

		it('matches the P key whatever character it produces', () => {
			expect(isPrintShortcut({ code: 'KeyP', key: 'P', ctrlKey: true, metaKey: false }, false)).toBe(true)
			expect(isPrintShortcut({ code: 'KeyP', key: 'з', ctrlKey: true, metaKey: false }, false)).toBe(true)
		})

		it('ignores another key that produces a p', () => {
			expect(isPrintShortcut({ code: 'KeyZ', key: 'p', ctrlKey: true, metaKey: false }, false)).toBe(false)
		})

		it('ignores the modifier on its own', () => {
			expect(isPrintShortcut({ code: 'KeyA', key: 'a', ctrlKey: true, metaKey: false }, false)).toBe(false)
			expect(isPrintShortcut({ code: 'KeyA', key: 'a', ctrlKey: false, metaKey: true }, true)).toBe(false)
		})
	})

	describe('buildBrowserPrintNotice', () => {
		const printRules = () => BROWSER_PRINT_STYLE.slice(BROWSER_PRINT_STYLE.indexOf('@media print'))

		it('turns away the browser menu only, not the shortcut', () => {
			const notice = buildBrowserPrintNotice(document)

			expect(notice.textContent).toContain('Printing from the browser menu is not supported')
		})

		it('points at the shortcut and the action, both of which do print', () => {
			const notice = buildBrowserPrintNotice(document)

			expect(notice.textContent).toContain('Ctrl+P')
			expect(notice.textContent).toContain('Print message')
		})

		it('carries no message, so no email markup reaches the app document', () => {
			const notice = buildBrowserPrintNotice(document)

			expect(notice.querySelector('.print-message')).toBeNull()
			expect(notice.querySelector('.print-message-content')).toBeNull()
		})

		it('brings its own stylesheet so it survives being removed again', () => {
			const notice = buildBrowserPrintNotice(document)

			expect(notice.querySelector('style').textContent).toContain('@media print')
		})

		it('pins its looks with inline styles, which the app stylesheet cannot outrank', () => {
			const notice = buildBrowserPrintNotice(document)

			expect(notice.querySelector('.print-notice__title').getAttribute('style')).toContain('!important')
			expect(notice.querySelector('.print-notice__text').getAttribute('style')).toContain('!important')
		})

		it('takes the place of the app while printing', () => {
			expect(printRules()).toContain(`body > *:not(#${BROWSER_PRINT_NOTICE_ID}):not(#${PRINT_FRAME_ID}) { display: none !important; }`)
			expect(printRules()).toContain(`#${BROWSER_PRINT_NOTICE_ID} { display: block !important; }`)
		})

		it('leaves the print frame alone, so a print of our own is not hidden from itself', () => {
			expect(printRules()).toContain(`:not(#${PRINT_FRAME_ID})`)
		})

		it('prints on white, so the app theme does not darken the page', () => {
			expect(printRules()).toContain('html, body { background: #fff !important; }')
		})

		it('stays out of the app on screen', () => {
			const screenRules = BROWSER_PRINT_STYLE.slice(0, BROWSER_PRINT_STYLE.indexOf('@media print'))

			expect(screenRules).toContain(`#${BROWSER_PRINT_NOTICE_ID} { display: none; }`)
		})

		it('applies the same page setup as the print document', () => {
			expect(printRules()).toContain('@page { margin: 15mm; }')
			expect(PRINT_DOCUMENT_STYLE).toContain('@page { margin: 15mm; }')
		})
	})

	describe('waitForImages', () => {
		it('resolves immediately when there are no images', async () => {
			const container = document.createElement('div')

			await expect(waitForImages(container)).resolves.toBe(true)
		})

		it('resolves immediately when every image is already complete', async () => {
			const container = document.createElement('div')
			const img = document.createElement('img')
			Object.defineProperty(img, 'complete', { value: true })
			container.appendChild(img)

			await expect(waitForImages(container)).resolves.toBe(true)
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

		it('waits for the images of a message behind a shadow boundary', async () => {
			const container = document.createElement('div')
			const host = document.createElement('div')
			container.appendChild(host)
			const img = document.createElement('img')
			Object.defineProperty(img, 'complete', { value: false })
			host.attachShadow({ mode: 'open' }).appendChild(img)

			let resolved = false
			waitForImages(container).then(() => {
				resolved = true
			})

			await Promise.resolve()
			expect(resolved).toBe(false)

			img.dispatchEvent(new Event('load'))
			await new Promise((resolve) => setTimeout(resolve))
			expect(resolved).toBe(true)
		})

		it('gives up on an image that never loads nor errors', async () => {
			const container = document.createElement('div')
			const img = document.createElement('img')
			Object.defineProperty(img, 'complete', { value: false })
			container.appendChild(img)

			let resolved = false
			waitForImages(container, 10).then(() => {
				resolved = true
			})

			await Promise.resolve()
			expect(resolved).toBe(false)

			await new Promise((resolve) => setTimeout(resolve, 20))
			expect(resolved).toBe(true)
		})

		it('says whether it gave up, so the caller can report an incomplete print', async () => {
			const container = document.createElement('div')
			const img = document.createElement('img')
			Object.defineProperty(img, 'complete', { value: false })
			container.appendChild(img)

			await expect(waitForImages(container, 10)).resolves.toBe(false)
		})
	})
})
