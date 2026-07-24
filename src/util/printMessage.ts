/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { translate as t } from '@nextcloud/l10n'
import { formatDateTimeFromUnix } from './formatDateTime.js'

export interface PrintRecipient {
	email: string
	label?: string
}

export interface PrintEnvelope {
	bcc?: PrintRecipient[]
	cc?: PrintRecipient[]
	dateInt?: number
	from?: PrintRecipient[]
	subject?: string
	to?: PrintRecipient[]
}

/**
 * Base stylesheet for the standalone print document. Kept intentionally
 * small: the message bodies bring their own (isolated) CSS, this only lays
 * out the page margins, the spacing between messages of a thread, and keeps
 * a message header attached to the start of its content.
 */
export const PRINT_DOCUMENT_STYLE = `
	@page { margin: 15mm; }
	html { color: #000; }
	body { margin: 0; font-family: sans-serif; color: #000; }
	.print-message + .print-message { margin-top: 24px; }
	.print-message-header { break-inside: avoid; break-after: avoid; }
`

function formatRecipients(recipients?: PrintRecipient[]): string {
	return (recipients ?? [])
		.map(({ label, email }) => (label ? `${label} <${email}>` : email))
		.join(', ')
}

/**
 * Build a single, self-contained print header for a message: Subject, From,
 * To, Cc, Bcc and Date/time. Cc/Bcc lines are omitted when empty so a plain
 * message doesn't show blank fields.
 *
 * All values are inserted via `textContent`/`createTextNode`, never as HTML,
 * so a crafted subject or display name (e.g. `<img onerror=…>`) can only ever
 * appear as literal text and cannot inject markup into the print document.
 *
 * The header is styled with inline `!important` styles on purpose: it lives
 * in the same document as the (untrusted) email HTML, whose global CSS would
 * otherwise be able to restyle it — that is what caused the oversized "big
 * letters" header in the old implementation. Inline `!important` wins even
 * over the email's own `!important` rules, so the header always renders
 * predictably.
 *
 * @param doc document to create the header elements in
 * @param envelope the envelope/message to build a header for
 */
export function buildMessageHeader(doc: Document, envelope: PrintEnvelope): HTMLElement {
	const header = doc.createElement('div')
	header.className = 'print-message-header'
	header.style.cssText = 'margin: 0 0 16px 0 !important; color: #000 !important; font-family: sans-serif !important;'

	const subject = doc.createElement('div')
	subject.style.cssText = 'font-size: 18px !important; font-weight: bold !important; margin: 0 0 8px 0 !important; color: #000 !important; font-family: sans-serif !important;'
	subject.textContent = envelope.subject || t('mail', 'No subject')
	header.appendChild(subject)

	const addLine = (label: string, value: string): void => {
		if (!value) {
			return
		}
		const line = doc.createElement('div')
		line.style.cssText = 'font-size: 13px !important; font-weight: normal !important; line-height: 1.4 !important; margin: 0 !important; color: #000 !important; font-family: sans-serif !important;'
		const name = doc.createElement('span')
		name.style.cssText = 'font-weight: bold !important;'
		name.textContent = `${label} `
		line.appendChild(name)
		line.appendChild(doc.createTextNode(value))
		header.appendChild(line)
	}

	addLine(`${t('mail', 'From')}:`, formatRecipients(envelope.from))
	addLine(t('mail', 'To:'), formatRecipients(envelope.to))
	addLine(t('mail', 'Cc:'), formatRecipients(envelope.cc))
	addLine(t('mail', 'Bcc:'), formatRecipients(envelope.bcc))
	addLine(`${t('mail', 'Date')}:`, envelope.dateInt ? formatDateTimeFromUnix(envelope.dateInt) : '')

	return header
}

/**
 * Extract a rendered HTML message's printable content from its iframe
 * document. The message body carries its own `<style>` block (Nextcloud
 * injects the sanitized email CSS at the start of the body) as well as any
 * `<style>`/`<link>` in the head, so all of them are copied — otherwise the
 * content would fall back to unstyled browser defaults.
 *
 * The body HTML is copied verbatim from the already server-sanitized message
 * iframe. `<script>` and the iframe-resizer marker are stripped as a
 * safeguard; the print document that this content is placed into must itself
 * be sandboxed without script execution (see `Thread.printMessages`), because
 * unlike the live message iframe it is not protected by the backend's
 * Content-Security-Policy.
 *
 * The content is returned as a normal (light DOM) element so that long
 * emails paginate across print pages. It must only ever be attached to the
 * document while printing and removed again afterwards, because the copied
 * email CSS is global and would otherwise affect the rest of the app.
 *
 * @param doc document to create the wrapper element in
 * @param sourceDocument the message iframe's own document
 */
export function buildHtmlMessageContent(doc: Document, sourceDocument: Document): HTMLElement {
	const wrapper = doc.createElement('div')
	wrapper.className = 'print-message-content'

	sourceDocument.head?.querySelectorAll('style, link[rel="stylesheet"]').forEach((node) => {
		wrapper.appendChild(doc.importNode(node, true))
	})

	const body = doc.createElement('div')
	body.innerHTML = sourceDocument.body?.innerHTML ?? ''
	body.querySelectorAll('script, [data-iframe-size]').forEach((node) => node.remove())
	wrapper.appendChild(body)

	return wrapper
}

/**
 * Wait until every <img> in a root has finished loading (or errored). Newly
 * assigned innerHTML needs at least one tick to load/decode images, even
 * when they're cached, so printing right away can produce blank images.
 *
 * @param root root to look for images in
 */
export function waitForImages(root: ParentNode): Promise<void> {
	const pending = Array.from(root.querySelectorAll<HTMLImageElement>('img')).filter((img) => !img.complete)

	if (pending.length === 0) {
		return Promise.resolve()
	}

	return new Promise<void>((resolve) => {
		let remaining = pending.length
		const onSettle = (): void => {
			remaining -= 1
			if (remaining === 0) {
				resolve()
			}
		}
		pending.forEach((img) => {
			img.addEventListener('load', onSettle, { once: true })
			img.addEventListener('error', onSettle, { once: true })
		})
	})
}
