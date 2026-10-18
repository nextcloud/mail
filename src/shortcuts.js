/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * `srcKey` values handled in `Mailbox.vue#handleShortcut`; values are vue3-shortkey key arrays.
 * Gmail's r/a/u are not bound because they clash with refresh/archive/unread here.
 */
export const SHORTCUTS = {
	del: ['del'],
	arch: ['a'],
	flag: ['s'],
	next: ['arrowright'],
	prev: ['arrowleft'],
	refresh: ['r'],
	unseen: ['u'],

	// Backspace is the Mac "delete" key
	delAlt: ['shift', '#'],
	delBackspace: ['backspace'],

	archAlt: ['e'],
	unseenAlt: ['shift', 'u'],

	nextAlt: ['j'],
	prevAlt: ['k'],
	open: ['o'],
	select: ['x'],
	compose: ['c'],
	reply: ['shift', 'r'],
	replyAll: ['shift', 'a'],
	forward: ['f'],
	search: ['/'],
	junk: ['shift', '!'],
	important: ['='],
	help: ['shift', '?'],
	helpAlt: ['?'],
}

const NO_SHORTCUTS = {}

export const TYPING_SELECTORS = ['input', 'textarea', '[contenteditable]:not([contenteditable="false"])']

/**
 * @return {boolean} whether the user disabled keyboard shortcuts in the accessibility settings
 */
function shortcutsDisabled() {
	return !!window.OCP?.Accessibility?.disableKeyboardShortcuts?.()
}

/**
 * @return {object} vue3-shortkey binding map
 */
export function resolveShortcuts() {
	if (shortcutsDisabled()) {
		return NO_SHORTCUTS
	}

	return SHORTCUTS
}

/**
 * @param {(app: string, text: string) => string} t translation function
 * @return {Array<{action: string, label: string}>} ordered action list
 */
export function shortcutActions(t) {
	return [
		{ action: 'compose', label: t('mail', 'Compose new message') },
		{ action: 'reply', label: t('mail', 'Reply') },
		{ action: 'replyAll', label: t('mail', 'Reply all') },
		{ action: 'forward', label: t('mail', 'Forward') },
		{ action: 'next', label: t('mail', 'Next message') },
		{ action: 'nextAlt', label: t('mail', 'Next message') },
		{ action: 'prev', label: t('mail', 'Previous message') },
		{ action: 'prevAlt', label: t('mail', 'Previous message') },
		{ action: 'arch', label: t('mail', 'Archive') },
		{ action: 'archAlt', label: t('mail', 'Archive') },
		{ action: 'del', label: t('mail', 'Delete') },
		{ action: 'delAlt', label: t('mail', 'Delete') },
		{ action: 'delBackspace', label: t('mail', 'Delete') },
		{ action: 'flag', label: t('mail', 'Star') },
		{ action: 'select', label: t('mail', 'Select message') },
		{ action: 'open', label: t('mail', 'Open message') },
		{ action: 'search', label: t('mail', 'Search') },
		{ action: 'unseen', label: t('mail', 'Mark unread') },
		{ action: 'unseenAlt', label: t('mail', 'Mark unread') },
		{ action: 'junk', label: t('mail', 'Mark as spam') },
		{ action: 'important', label: t('mail', 'Toggle important') },
		{ action: 'refresh', label: t('mail', 'Refresh') },
		{ action: 'help', label: t('mail', 'Show keyboard shortcuts') },
		{ action: 'helpAlt', label: t('mail', 'Show keyboard shortcuts') },
	]
}

/**
 * @param {string[]} keys vue3-shortkey key array
 * @return {string} NcHotkey hotkey string
 */
export function formatHotkey(keys) {
	const NAMES = {
		arrowright: 'ArrowRight',
		arrowleft: 'ArrowLeft',
		arrowup: 'ArrowUp',
		arrowdown: 'ArrowDown',
		del: 'Delete',
		backspace: 'Backspace',
		shift: 'Shift',
		ctrl: 'Control',
		alt: 'Alt',
	}

	return keys
		.map((key) => NAMES[key] ?? key)
		.join(' ')
}

/** Gmail-style "g then key" chords mapped to the mailbox specialRole to open. */
export const SHORTCUT_JUMPS = {
	i: 'inbox',
	t: 'sent',
	d: 'drafts',
	a: 'archive',
	s: 'starred',
	b: 'snoozed',
	k: 'trash',
	j: 'junk',
}

/**
 * @return {boolean} whether the g-chords are active
 */
export function hasJumps() {
	return !shortcutsDisabled()
}

/**
 * @return {boolean} true when shortcuts may act
 */
export function shortcutsAllowed() {
	const el = document.activeElement
	if (!el) {
		return true
	}

	return !el.matches(TYPING_SELECTORS.join(', '))
}

/**
 * @param {(app: string, text: string) => string} t translation function
 * @param {string} firstKey key pressed first
 * @param {string} secondKey key pressed second
 * @return {Array<{type: 'key', key: string}|{type: 'text', text: string}>} parts in display order
 */
export function keySequenceParts(t, firstKey, secondKey) {
	const KEYS = { '{firstKey}': firstKey, '{secondKey}': secondKey }

	return t('mail', '{firstKey} then {secondKey}')
		.split(/(\{firstKey\}|\{secondKey\})/)
		.filter((part) => part !== '')
		.map((part) => Object.hasOwn(KEYS, part)
			? { type: 'key', key: KEYS[part] }
			: { type: 'text', text: part })
}

/**
 * @param {(app: string, text: string) => string} t translation function
 * @return {Array<{action: string, label: string, parts: Array<{type: 'key', key: string}|{type: 'text', text: string}>}>} rows
 */
export function jumpActions(t) {
	const LABELS = {
		i: t('mail', 'Go to inbox'),
		t: t('mail', 'Go to sent'),
		d: t('mail', 'Go to drafts'),
		a: t('mail', 'Go to archive'),
		s: t('mail', 'Go to starred'),
		b: t('mail', 'Go to snoozed'),
		k: t('mail', 'Go to trash'),
		j: t('mail', 'Go to spam'),
	}

	return Object.entries(SHORTCUT_JUMPS)
		.map(([key]) => ({
			action: 'jump-' + key,
			label: LABELS[key],
			parts: keySequenceParts(t, 'g', key),
		}))
}

// Listen on window capture: vue3-shortkey registers document capture listeners at module load and its
// `.once` bindings fire on keyup, so both keydown and keyup of chord keys must be swallowed before it.
let jumpsEnabled = false
let chordArmed = false
let chordTimer
const swallowKeys = new Set()

const CHORD_TIMEOUT = 1500

/**
 * @param {boolean} enabled whether the g-chords should act
 */
export function setJumpsEnabled(enabled) {
	jumpsEnabled = enabled
}

/** Dispatches a `mail:jump` window event carrying the target specialRole. */
export function installJumpChords() {
	window.addEventListener('keydown', (event) => {
		if (!jumpsEnabled || event.ctrlKey || event.metaKey || event.altKey || !shortcutsAllowed()) {
			return
		}

		const key = event.key.toLowerCase()

		if (chordArmed) {
			chordArmed = false
			clearTimeout(chordTimer)

			const role = SHORTCUT_JUMPS[key]
			if (!role) {
				return
			}

			swallowKeys.add(key)
			event.preventDefault()
			event.stopImmediatePropagation()
			window.dispatchEvent(new CustomEvent('mail:jump', { detail: role }))
			return
		}

		if (key === 'g') {
			swallowKeys.add('g')
			event.preventDefault()
			event.stopImmediatePropagation()
			chordArmed = true
			clearTimeout(chordTimer)
			chordTimer = setTimeout(() => {
				chordArmed = false
			}, CHORD_TIMEOUT)
		}
	}, true)

	window.addEventListener('keyup', (event) => {
		const key = event.key.toLowerCase()
		if (!swallowKeys.has(key)) {
			return
		}

		swallowKeys.delete(key)
		event.preventDefault()
		event.stopImmediatePropagation()
	}, true)
}
