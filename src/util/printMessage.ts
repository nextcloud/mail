/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { t } from '@nextcloud/l10n'
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
 * Id of the print-only notice a browser-initiated print prints instead of the
 * thread, see `buildBrowserPrintNotice`.
 */
export const BROWSER_PRINT_NOTICE_ID = 'mail-browser-print-notice'

/**
 * Id of the frame the print document is staged in, see `printMessages`. The
 * browser print stylesheet knows it so that it does not hide the frame out from
 * under a print of our own, should a browser format the app document for print
 * media while the frame's own document is being printed.
 */
export const PRINT_FRAME_ID = 'mail-print-frame'

const PRINT_PAGE_MARGIN = '15mm'

/**
 * Width the print document is staged at while its frame sits off-screen: A4
 * minus the page margins.
 *
 * Nothing is measured at this width. The messages are part of the print
 * document's own flow and are laid out again for whatever paper the print
 * dialog is set to — including a paper the reader picks after the dialog is
 * already open. This only keeps the staged rendering roughly page-shaped.
 */
export const PRINT_CONTENT_WIDTH = `calc(210mm - 2 * ${PRINT_PAGE_MARGIN})`

/** Height the print document is staged at: one A4 page. */
export const PRINT_CONTENT_HEIGHT = '297mm'

/**
 * Off-screen staging for the print frame. It is parked out of sight rather than
 * hidden with `display: none`, because a frame that is not displayed has no
 * rendering of its own and browsers have long printed such a frame blank.
 */
export const PRINT_STAGING_STYLE = `position: fixed; top: 0; inset-inline-start: -9999px; width: ${PRINT_CONTENT_WIDTH};`

/**
 * Page setup shared by every print rendering: margins and a black-on-white
 * base, because the app's own (possibly dark) theme must not bleed into a
 * printed page.
 *
 * The print styles spell their values out instead of using the Nextcloud CSS
 * variables: those do not reach the standalone print document, and a theme
 * color would print a dark theme's white text onto white paper.
 */
const PRINT_PAGE_STYLE = `
	@page { margin: ${PRINT_PAGE_MARGIN}; }
	html { color: #000; }
	body { margin: 0; font-family: sans-serif; color: #000; }
`

/**
 * Layout of the printed messages: the spacing between the messages of a thread,
 * the block each message body is rendered into, and keeping a message header
 * attached to the start of its content.
 *
 * A message body lives in a shadow root, which an email's CSS cannot reach out
 * of — but the inherited properties of the host reach in, so the host pins the
 * text color rather than letting a dark theme's white text through.
 */
const PRINT_MESSAGE_STYLE = `
	.print-message + .print-message { margin-top: 24px; }
	.print-message-content {
		display: block;
		color: #000;
	}
	.print-message-header {
		break-inside: avoid;
		break-after: avoid;
	}
`

/**
 * Looks of a message's print header, as inline styles.
 *
 * A header is rendered next to its message rather than inside its shadow root,
 * so that the (untrusted) email CSS cannot restyle it, that is what produced
 * the oversized "big letters" header in the old implementation. Nothing else
 * can reach it either — it only ever goes into the standalone print document,
 * which brings no stylesheet but `PRINT_DOCUMENT_STYLE` — so plain inline
 * declarations are enough and no `!important` is needed.
 */
const PRINT_HEADER_STYLES = {
	header: 'margin: 0 0 16px 0;',
	subject: 'font-size: 18px; font-weight: bold; margin: 0 0 8px 0;',
	line: 'font-size: 13px; font-weight: normal; line-height: 1.4; margin: 0;',
	label: 'font-weight: bold;',
}

/** Declarations every part of the browser print notice repeats, see `PRINT_NOTICE_STYLES`. */
const PRINT_NOTICE_BASE_STYLE = 'color: #000 !important; font-family: sans-serif !important;'

/**
 * Looks of the browser print notice, as inline styles.
 *
 * Unlike a message header the notice sits in the app's own document, where the
 * app stylesheet would otherwise style it. Inline declarations outrank every
 * declaration a stylesheet can make, `!important` ones included, but only for
 * the element they sit on, so every part of the notice carries its own.
 */
const PRINT_NOTICE_STYLES = {
	title: `${PRINT_NOTICE_BASE_STYLE} font-size: 18px !important; font-weight: bold !important; margin: 0 0 8px 0 !important;`,
	text: `${PRINT_NOTICE_BASE_STYLE} font-size: 13px !important; font-weight: normal !important; line-height: 1.4 !important; margin: 0 !important;`,
}

/**
 * Styles a plain text message needs to print the way it is shown. The app's own
 * stylesheet does not reach into a shadow root, and a plain text body relies on
 * it for `pre-wrap`: without it, the message's indentation and its runs of
 * blank lines collapse.
 */
const PLAIN_TEXT_MESSAGE_STYLE = `
	.print-message-text {
		white-space: pre-wrap;
		font-family: sans-serif;
	}
`

/**
 * Base stylesheet for the standalone print document that the "Print message"
 * action and Ctrl/Cmd+P render into.
 */
export const PRINT_DOCUMENT_STYLE = `${PRINT_PAGE_STYLE}${PRINT_MESSAGE_STYLE}`

/**
 * Stylesheet for the notice a browser-initiated print prints, see
 * `buildBrowserPrintNotice`. The notice exists for print media only, and takes
 * the place of the app, which is hidden for the duration — the print frame
 * excepted, so that a print of our own is never hidden out from under itself.
 *
 * The app's own (possibly dark) background is painted over, because a reader
 * who prints backgrounds would otherwise get the notice on a dark page.
 */
export const BROWSER_PRINT_STYLE = `
	#${BROWSER_PRINT_NOTICE_ID} { display: none; }
	@media print {
		body > *:not(#${BROWSER_PRINT_NOTICE_ID}):not(#${PRINT_FRAME_ID}) { display: none !important; }
		#${BROWSER_PRINT_NOTICE_ID} { display: block !important; }
		html, body { background: #fff !important; }
		${PRINT_PAGE_STYLE}
	}
`

function formatRecipients(recipients?: PrintRecipient[]): string {
	return (recipients ?? [])
		.map(({ label, email }) => (label ? `${label} <${email}>` : email))
		.join(', ')
}

/**
 * Build a single, self-contained print header for a message: Subject, From,
 * To, Cc, Bcc and Date/time. Empty fields are omitted so a plain message
 * doesn't show blank lines.
 *
 * All values are inserted as text, never as HTML, so a crafted subject or
 * display name can only ever appear as literal text. The header takes its looks
 * from inline styles (see `PRINT_HEADER_STYLES`), out of reach of the email's
 * own CSS.
 *
 * @param doc document to create the header elements in
 * @param envelope the envelope/message to build a header for
 */
export function buildMessageHeader(doc: Document, envelope: PrintEnvelope): HTMLElement {
	const header = doc.createElement('div')
	header.className = 'print-message-header'
	header.style.cssText = PRINT_HEADER_STYLES.header

	const subject = doc.createElement('div')
	subject.className = 'print-message-header__subject'
	subject.style.cssText = PRINT_HEADER_STYLES.subject
	subject.textContent = envelope.subject?.trim() ? envelope.subject : t('mail', 'No subject')
	header.appendChild(subject)

	const addLine = (label: string, value: string): void => {
		if (!value) {
			return
		}
		const line = doc.createElement('div')
		line.className = 'print-message-header__line'
		line.style.cssText = PRINT_HEADER_STYLES.line
		const name = doc.createElement('span')
		name.className = 'print-message-header__label'
		name.style.cssText = PRINT_HEADER_STYLES.label
		name.textContent = `${label} `
		line.appendChild(name)
		line.appendChild(doc.createTextNode(value))
		header.appendChild(line)
	}

	addLine(t('mail', 'From:'), formatRecipients(envelope.from))
	addLine(t('mail', 'To:'), formatRecipients(envelope.to))
	addLine(t('mail', 'Cc:'), formatRecipients(envelope.cc))
	addLine(t('mail', 'Bcc:'), formatRecipients(envelope.bcc))
	addLine(t('mail', 'Date:'), envelope.dateInt ? formatDateTimeFromUnix(envelope.dateInt) : '')

	return header
}

/** Headline of the browser print notice. */
function browserPrintTitle(): string {
	return t('mail', 'Printing from the browser menu is not supported')
}

/**
 * The way to print that is supported, naming the platform's own shortcut —
 * Ctrl+P and Cmd+P are intercepted by the app and print the thread themselves,
 * so the reader is being pointed at a shortcut, not away from one.
 */
function browserPrintHint(): string {
	return t('mail', 'To print this email, press {shortcut}, or use the "Print message" action in the message menu.', {
		shortcut: isAppleDevice() ? 'Cmd+P' : 'Ctrl+P',
	})
}

/**
 * Build the notice that a print started from the browser itself (its menu, or
 * right-click → Print) prints in place of the thread.
 *
 * Only this one way of printing is turned away, and only because it cannot be
 * served: it always renders the top-level document, it cannot be cancelled from
 * `beforeprint`, and there is no way to point it at a document of ours. Nor can
 * the app page stand in for that document — a message is rendered in a frame,
 * which is a single box that the printer cuts off at the end of a page instead
 * of breaking it across pages, and putting the email markup in the page
 * directly would take it out of the sandboxed frame that confines it. Ctrl/Cmd+P
 * is intercepted long before any of this and prints normally.
 *
 * The notice lives in the page for as long as a thread is open and is shown for
 * print media only — no `beforeprint` handling is needed to put it up, and a
 * print the browser never announces is covered too. It brings its own
 * stylesheet, so that removing the notice takes the stylesheet along.
 *
 * @param doc document to create the notice in
 */
export function buildBrowserPrintNotice(doc: Document): HTMLElement {
	const notice = doc.createElement('div')
	notice.id = BROWSER_PRINT_NOTICE_ID

	const style = doc.createElement('style')
	style.textContent = BROWSER_PRINT_STYLE
	notice.appendChild(style)

	const title = doc.createElement('div')
	title.className = 'print-notice__title'
	title.style.cssText = PRINT_NOTICE_STYLES.title
	title.textContent = browserPrintTitle()
	notice.appendChild(title)

	const text = doc.createElement('div')
	text.className = 'print-notice__text'
	text.style.cssText = PRINT_NOTICE_STYLES.text
	text.textContent = browserPrintHint()
	notice.appendChild(text)

	return notice
}

/** Attributes that carry a single URL relative to the message's own document. */
const URL_ATTRIBUTES = ['src', 'href', 'poster', 'background']

/** Attribute that carries a comma-separated list of such URLs. */
const SRCSET_ATTRIBUTE = 'srcset'

/** Selector for every element carrying one of the attributes above. */
const URL_SELECTOR = [...URL_ATTRIBUTES, SRCSET_ATTRIBUTE].map((attribute) => `[${attribute}]`).join(', ')

/**
 * Resolve a possibly relative URL against a base, leaving anything that is not
 * a URL at all untouched.
 *
 * @param value the attribute value to resolve
 * @param baseUrl the URL to resolve it against
 */
function resolveUrl(value: string, baseUrl: string): string {
	try {
		return new URL(value, baseUrl).href
	} catch {
		return value
	}
}

/**
 * Rewrite every relative URL below `root` to an absolute one.
 *
 * A message is rendered from an endpoint of its own, so its relative URLs
 * resolve against that endpoint rather than against the app. A frame could be
 * told so with a `<base>`; a shadow root cannot — it has no base of its own and
 * inherits the one of the document it sits in — so the URLs are resolved here
 * while the copy still belongs to the message's document.
 *
 * @param root the copied message to rewrite in place
 * @param baseUrl the message document's base URL
 */
function absolutizeUrls(root: Element, baseUrl: string): void {
	root.querySelectorAll(URL_SELECTOR).forEach((node) => {
		for (const attribute of URL_ATTRIBUTES) {
			const value = node.getAttribute(attribute)
			if (value) {
				node.setAttribute(attribute, resolveUrl(value, baseUrl))
			}
		}

		const srcset = node.getAttribute(SRCSET_ATTRIBUTE)
		if (srcset) {
			node.setAttribute(SRCSET_ATTRIBUTE, srcset.split(',').map((candidate) => {
				const [url, ...descriptors] = candidate.trim().split(/\s+/)
				return url ? [resolveUrl(url, baseUrl), ...descriptors].join(' ') : candidate
			}).join(', '))
		}
	})
}

/**
 * Copy a rendered HTML message for printing. The message frame's *current*
 * document is taken, not its source, so that images the reader unblocked with
 * "Show images temporarily" are printed the way they are shown.
 *
 * The markup is already server-sanitized. `<script>` and the iframe-resizer
 * marker are stripped as a safeguard; the document this is rendered into must
 * additionally be unable to run scripts, because unlike the live message frame
 * it is not protected by the backend's Content-Security-Policy.
 *
 * The whole `<html>` element is kept, `<head>` and `<body>` included, so that
 * the `html` and `body` selectors an email's stylesheet uses keep matching once
 * the message no longer has a document of its own. A `<base>` the message
 * carries is dropped instead — it has no effect outside a document's head, and
 * `absolutizeUrls` has already applied it.
 *
 * @param sourceDocument the message frame's own document
 */
export function buildMessageContent(sourceDocument: Document): HTMLElement {
	const html = sourceDocument.documentElement.cloneNode(true) as HTMLElement
	html.querySelectorAll('script, base, [data-iframe-size]').forEach((node) => node.remove())
	absolutizeUrls(html, sourceDocument.baseURI)

	return html
}

/**
 * Add a shadow host for one message body to `parent`.
 *
 * @param parent element to add the host to
 */
function attachMessageShadow(parent: HTMLElement): ShadowRoot {
	const host = parent.ownerDocument.createElement('div')
	host.className = 'print-message-content'
	parent.appendChild(host)

	return host.attachShadow({ mode: 'open' })
}

/**
 * Render an HTML message into a shadow root of its own inside `parent`.
 *
 * Every message needs a tree of its own because an email's CSS is global to the
 * tree it lives in: in a shared print document the `<style>` blocks of one
 * message would restyle every other message of the thread. A shadow root
 * contains that CSS the way a frame did, but — unlike a frame, which is a
 * single box of a fixed size that neither breaks nor grows — its content is
 * part of the print document's own flow. That is what lets a long message break
 * across pages and lay itself out again when the reader picks another paper
 * size or orientation in the already open print dialog.
 *
 * @param parent element to render the message into
 * @param sourceDocument the message frame's own document
 */
export function renderHtmlMessage(parent: HTMLElement, sourceDocument: Document): HTMLElement {
	const shadow = attachMessageShadow(parent)
	shadow.appendChild(parent.ownerDocument.importNode(buildMessageContent(sourceDocument), true))

	return shadow.host as HTMLElement
}

/**
 * Render a plain text message into a shadow root of its own inside `parent`.
 *
 * A plain text body carries no CSS of its own and could not restyle anything,
 * but it is rendered the same way as an HTML one so that both print paths hand
 * the browser the same markup — and so that the app's stylesheet, which the
 * live page brings along for a native browser print, cannot reach it either.
 * What the body does need of that stylesheet, `PLAIN_TEXT_MESSAGE_STYLE`
 * replaces.
 *
 * @param parent element to render the message into
 * @param sourceElement the rendered message body in the app document
 */
export function renderPlainTextMessage(parent: HTMLElement, sourceElement: Element): HTMLElement {
	const doc = parent.ownerDocument
	const shadow = attachMessageShadow(parent)

	const style = doc.createElement('style')
	style.textContent = PLAIN_TEXT_MESSAGE_STYLE
	shadow.appendChild(style)

	const content = doc.importNode(sourceElement, true) as HTMLElement
	content.removeAttribute('id')
	content.className = 'print-message-text'
	shadow.appendChild(content)

	return shadow.host as HTMLElement
}

/**
 * Collect every `<img>` below a root, shadow roots included: `querySelectorAll`
 * stops at a shadow boundary, and every message body sits behind one.
 *
 * @param root root to look for images in
 */
function collectImages(root: ParentNode): HTMLImageElement[] {
	const images = Array.from(root.querySelectorAll<HTMLImageElement>('img'))

	root.querySelectorAll('*').forEach((element) => {
		if (element.shadowRoot) {
			images.push(...collectImages(element.shadowRoot))
		}
	})

	return images
}

/**
 * How long `waitForImages` waits for the images of a print rendering before it
 * prints without them.
 */
export const IMAGE_LOAD_TIMEOUT = 10000

/**
 * Wait until every <img> in a root has finished loading (or errored). Freshly
 * rendered content needs at least one tick to load/decode its images, even when
 * they're cached, so printing right away can produce blank images.
 *
 * A remote image that neither loads nor errors — one served by a host that
 * simply never answers — would hold this back for as long as the browser keeps
 * the request open, so the wait gives up eventually and prints what is there.
 *
 * @param root root to look for images in
 * @param timeout how long to wait before printing without the pending images
 * @return whether every image settled, or `false` if the wait gave up on some
 */
export async function waitForImages(root: ParentNode, timeout: number = IMAGE_LOAD_TIMEOUT): Promise<boolean> {
	const pending = collectImages(root).filter((img) => !img.complete)
	if (pending.length === 0) {
		return true
	}

	let expire: ReturnType<typeof setTimeout>
	const expired = new Promise<boolean>((resolve) => {
		expire = setTimeout(() => resolve(false), timeout)
	})

	const settled = await Promise.race([
		Promise.all(pending.map((img) => new Promise<void>((resolve) => {
			img.addEventListener('load', () => resolve(), { once: true })
			img.addEventListener('error', () => resolve(), { once: true })
		}))).then(() => true),
		expired,
	])

	clearTimeout(expire!)

	return settled
}

/**
 * Whether the current platform is macOS, where the conventional shortcut
 * modifier is Cmd (`metaKey`) rather than Ctrl.
 */
export function isAppleDevice(): boolean {
	const nav = navigator as Navigator & { userAgentData?: { platform?: string } }
	return /mac/i.test(nav.userAgentData?.platform ?? nav.platform ?? '')
}

/**
 * Whether a keydown event is the platform's "print" shortcut: Cmd+P on macOS,
 * Ctrl+P everywhere else. Binding the platform-appropriate modifier avoids
 * hijacking native bindings — on macOS Ctrl+P moves the cursor up a line in text
 * fields, so we must not swallow it there.
 *
 * The physical key is matched rather than the character it produces: `key` is
 * `'P'` with CapsLock or Shift held, and whatever the reader's layout puts
 * there in a non-Latin one, while `code` is the same key the browser itself
 * binds its print shortcut to.
 *
 * @param event the keydown event to test
 * @param appleDevice whether the platform is macOS (defaults to auto-detection)
 */
export function isPrintShortcut(event: KeyboardEvent, appleDevice: boolean = isAppleDevice()): boolean {
	if (event.code !== 'KeyP') {
		return false
	}
	return appleDevice ? event.metaKey : event.ctrlKey
}
