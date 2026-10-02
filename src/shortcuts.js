/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Keyboard shortcuts.
 *
 * The keys of this map are the `srcKey` values handled in
 * `Mailbox.vue#handleShortcut`. Only actions that have a handler can be bound.
 *
 * Values are `vue-shortkey` key arrays; an array with several entries means
 * those keys are pressed together.
 *
 * The bindings follow Gmail, because keyboard shortcuts are a product of
 * convention and most people arriving here come from there.
 *
 * Two deliberate departures from Gmail:
 *
 * - `refresh` is on Shift+R. In Gmail plain `r` is *reply*, so leaving refresh
 *   there is the single most confusing collision for anyone arriving from
 *   Gmail: they press `r` expecting a composer and silently resync the mailbox
 *   instead.
 * - Delete stays on Del, with Gmail's `#` as an alternative, because `#` is
 *   Shift+3 only on some keyboard layouts.
 * - Backspace also deletes, because the key labelled "delete" on Mac
 *   keyboards sends Backspace, not Delete, and most mail apps treat the two
 *   as interchangeable.
 */
export const SHORTCUTS = {
	del: ['del'],
	delAlt: ['shift', '#'],
	delBackspace: ['backspace'],
	arch: ['e'],
	flag: ['s'],
	select: ['x'],
	next: ['j'],
	prev: ['k'],
	open: ['o'],
	openAlt: ['enter'],
	back: ['u'],
	search: ['/'],
	compose: ['c'],
	reply: ['r'],
	replyAll: ['a'],
	forward: ['f'],
	junk: ['shift', '!'],
	important: ['='],
	help: ['shift', '?'],
	helpAlt: ['?'],
	refresh: ['shift', 'r'],
	unseen: ['shift', 'u'],
}

/** Registers nothing. Shared so the empty map keeps a stable identity. */
const NO_SHORTCUTS = {}

/**
 * The active bindings.
 *
 * Returns no bindings at all when the user has disabled keyboard shortcuts in
 * the Nextcloud accessibility settings.
 *
 * @return {object} vue-shortkey binding map
 */
export function resolveShortcuts() {
	if (window.OCP?.Accessibility?.disableKeyboardShortcuts?.()) {
		return NO_SHORTCUTS
	}

	return SHORTCUTS
}

/**
 * Human readable label for each bindable action, in the order they should be
 * listed in the app settings shortcut section.
 *
 * @param {(app: string, text: string) => string} t translation function, injected so this module stays
 *   free of Nextcloud imports and remains trivially testable
 * @return {Array<{action: string, label: string}>} ordered action list
 */
export function shortcutActions(t) {
	return [
		{ action: 'compose', label: t('mail', 'Compose new message') },
		{ action: 'reply', label: t('mail', 'Reply') },
		{ action: 'replyAll', label: t('mail', 'Reply all') },
		{ action: 'forward', label: t('mail', 'Forward') },
		{ action: 'next', label: t('mail', 'Next message') },
		{ action: 'prev', label: t('mail', 'Previous message') },
		{ action: 'arch', label: t('mail', 'Archive') },
		{ action: 'del', label: t('mail', 'Delete') },
		{ action: 'flag', label: t('mail', 'Star') },
		{ action: 'select', label: t('mail', 'Select message') },
		{ action: 'open', label: t('mail', 'Open message') },
		{ action: 'openAlt', label: t('mail', 'Open message') },
		{ action: 'back', label: t('mail', 'Back to list') },
		{ action: 'search', label: t('mail', 'Search') },
		{ action: 'unseen', label: t('mail', 'Mark unread') },
		{ action: 'junk', label: t('mail', 'Mark as spam') },
		{ action: 'important', label: t('mail', 'Toggle important') },
		{ action: 'delAlt', label: t('mail', 'Delete') },
		{ action: 'delBackspace', label: t('mail', 'Delete') },
		{ action: 'refresh', label: t('mail', 'Refresh') },
		{ action: 'help', label: t('mail', 'Show keyboard shortcuts') },
		{ action: 'helpAlt', label: t('mail', 'Show keyboard shortcuts') },
	]
}

/**
 * Format a binding for NcHotkey, which expects space separated key names
 * (e.g. "Control Alt 1", "ArrowLeft", "Delete").
 *
 * @param {string[]} keys vue-shortkey key array
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

/**
 * Gmail-style "jump" chords: press g, then a second key, to switch mailbox.
 *
 * vue-shortkey has no concept of key sequences, so these are handled by a
 * dedicated listener rather than the binding map. Values are the mailbox
 * specialRole to navigate to.
 */
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
	return !window.OCP?.Accessibility?.disableKeyboardShortcuts?.()
}

/**
 * Whether a keystroke should be treated as a shortcut rather than typing.
 *
 * Mirrors the `prevent` list handed to vue-shortkey so the chord handler and
 * the binding map agree on what counts as "the user is typing".
 *
 * @return {boolean} true when shortcuts may act
 */
export function shortcutsAllowed() {
	const el = document.activeElement
	if (!el) {
		return true
	}

	return !el.matches('input, textarea, [contenteditable]:not([contenteditable="false"])')
}

/**
 * Rows describing the jump chords for the settings shortcut list.
 *
 * These are not part of the vue-shortkey binding map - they are key sequences
 * handled separately - so they have to be described explicitly rather than
 * derived from the binding map.
 *
 * @param {(app: string, text: string) => string} t translation function
 * @return {Array<{action: string, label: string, hotkey: string}>} rows
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
		.filter(([key]) => LABELS[key])
		.map(([key]) => ({
			action: 'jump-' + key,
			label: LABELS[key],
			hotkey: 'g ' + t('mail', 'then') + ' ' + key,
		}))
}

/*
 * Jump chords listen on `window`, not `document`.
 *
 * vue-shortkey registers its document-level capture listeners at MODULE LOAD,
 * not when the plugin is installed, so import hoisting means it always wins the
 * registration race. Its bindings also use the `.once` modifier and therefore
 * fire on KEYUP, so consuming only the keydown of a chord's second key still
 * let `g a` run whatever `a` is bound to.
 *
 * Capture propagates window -> document, so a window capture listener runs
 * first whatever the import order, and stopImmediatePropagation there keeps the
 * event away from vue-shortkey entirely.
 */

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

/**
 * Dispatches a `mail:jump` window event carrying the target specialRole.
 * Install once at startup.
 */
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
		// A set, not a single slot: 'g' and the second key are both in flight,
		// and releasing them in either order must not leak a keyup to
		// vue-shortkey.
		const key = event.key.toLowerCase()
		if (!swallowKeys.has(key)) {
			return
		}

		swallowKeys.delete(key)
		event.preventDefault()
		event.stopImmediatePropagation()
	}, true)
}
