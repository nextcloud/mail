/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { formatHotkey, hasJumps, installJumpChords, jumpActions, resolveShortcuts, setJumpsEnabled, SHORTCUT_JUMPS, shortcutActions, SHORTCUTS, shortcutsAllowed } from '../../shortcuts.js'

describe('shortcuts', () => {
	afterEach(() => {
		delete window.OCP
	})

	it('resolves the bindings', () => {
		expect(resolveShortcuts()).toBe(SHORTCUTS)
	})

	it('registers nothing when shortcuts are disabled for accessibility', () => {
		window.OCP = { Accessibility: { disableKeyboardShortcuts: () => true } }

		expect(resolveShortcuts()).toEqual({})
	})

	it('binds only actions that have a handler', () => {
		// Mailbox.vue#handleShortcut only switches on these.
		const handled = [
			'del',
			'delAlt',
			'delBackspace',
			'arch',
			'flag',
			'select',
			'next',
			'prev',
			'open',
			'back',
			'search',
			'refresh',
			'unseen',
			'compose',
			'reply',
			'replyAll',
			'forward',
			'junk',
			'important',
			'help',
			'openAlt',
			'helpAlt',
		]

		for (const action of Object.keys(SHORTCUTS)) {
			expect(handled, `binds unknown action ${action}`).toContain(action)
		}
	})
})

describe('shortcut help', () => {
	const t = (app, text) => text

	it('lists the bound actions', () => {
		const actions = shortcutActions(t)
			.filter(({ action }) => SHORTCUTS[action])
			.map((e) => e.action)

		for (const expected of ['next', 'prev', 'arch', 'del', 'flag', 'unseen', 'refresh']) {
			expect(actions).toContain(expected)
		}
	})

	it('has a label for every binding', () => {
		const labelled = shortcutActions(t).map((e) => e.action)

		for (const action of Object.keys(SHORTCUTS)) {
			expect(labelled, `binds ${action} with no label`).toContain(action)
		}
	})

	it('formats key combinations for NcHotkey', () => {
		expect(formatHotkey(['shift', 'r'])).toBe('Shift r')
		expect(formatHotkey(['arrowleft'])).toBe('ArrowLeft')
		expect(formatHotkey(['del'])).toBe('Delete')
		expect(formatHotkey(['e'])).toBe('e')
	})
})

describe('jump chords', () => {
	afterEach(() => {
		delete window.OCP
	})

	it('enables jumps by default', () => {
		expect(hasJumps()).toBe(true)
	})

	it('disables jumps when shortcuts are off for accessibility', () => {
		window.OCP = { Accessibility: { disableKeyboardShortcuts: () => true } }

		expect(hasJumps()).toBe(false)
	})

	it('maps Gmail jump keys to mailbox roles', () => {
		expect(SHORTCUT_JUMPS.s).toBe('starred')
		expect(SHORTCUT_JUMPS.b).toBe('snoozed')
		expect(SHORTCUT_JUMPS.i).toBe('inbox')
		expect(SHORTCUT_JUMPS.t).toBe('sent')
		expect(SHORTCUT_JUMPS.d).toBe('drafts')
		expect(SHORTCUT_JUMPS.a).toBe('archive')
	})

	it('treats typing contexts as off-limits', () => {
		const input = document.createElement('input')
		document.body.appendChild(input)
		input.focus()
		expect(shortcutsAllowed()).toBe(false)
		input.remove()
	})

	it('allows shortcuts when focus is on a plain container', () => {
		const div = document.createElement('div')
		div.tabIndex = 0
		document.body.appendChild(div)
		div.focus()
		expect(shortcutsAllowed()).toBe(true)
		div.remove()
	})
})

describe('gmail parity', () => {
	it('binds the Gmail keys for the actions Mail can perform', () => {
		expect(SHORTCUTS.compose).toEqual(['c'])
		expect(SHORTCUTS.reply).toEqual(['r'])
		expect(SHORTCUTS.replyAll).toEqual(['a'])
		expect(SHORTCUTS.forward).toEqual(['f'])
		expect(SHORTCUTS.arch).toEqual(['e'])
		expect(SHORTCUTS.next).toEqual(['j'])
		expect(SHORTCUTS.prev).toEqual(['k'])
		expect(SHORTCUTS.open).toEqual(['o'])
		expect(SHORTCUTS.back).toEqual(['u'])
		expect(SHORTCUTS.select).toEqual(['x'])
		expect(SHORTCUTS.search).toEqual(['/'])
		expect(SHORTCUTS.help).toEqual(['shift', '?'])
	})

	it('keeps delete off a bare single key', () => {
		// Destructive actions should not be one keystroke away.
		expect(SHORTCUTS.del).toEqual(['del'])
		expect(SHORTCUTS.delAlt).toEqual(['shift', '#'])
	})

	it('also deletes on Backspace, since Mac keyboards label it "delete"', () => {
		expect(SHORTCUTS.delBackspace).toEqual(['backspace'])
	})

	it('does not reuse a key for two actions', () => {
		const seen = new Set()

		for (const keys of Object.values(SHORTCUTS)) {
			const combo = [...keys].sort().join('+')
			expect(seen.has(combo), `binds ${combo} twice`).toBe(false)
			seen.add(combo)
		}
	})
})

describe('settings shortcut list', () => {
	const t = (app, text) => text

	it('describes every jump chord', () => {
		const rows = jumpActions(t)

		expect(rows).toHaveLength(Object.keys(SHORTCUT_JUMPS).length)
		for (const row of rows) {
			expect(row.hotkey).toMatch(/^g then [a-z]$/)
			expect(row.label.length).toBeGreaterThan(0)
		}
	})
})

describe('jump chord key handling', () => {
	beforeAll(() => {
		installJumpChords()
	})

	beforeEach(() => {
		setJumpsEnabled(true)
	})

	afterEach(() => {
		setJumpsEnabled(false)
	})

	const press = (key, type = 'keydown') => {
		const event = new KeyboardEvent(type, { key, bubbles: true, cancelable: true })
		document.dispatchEvent(event)
		return event
	}

	it('dispatches mail:jump for g then a', () => {
		const seen = []
		const listener = (e) => seen.push(e.detail)
		window.addEventListener('mail:jump', listener)

		press('g')
		press('a')

		window.removeEventListener('mail:jump', listener)
		expect(seen).toEqual(['archive'])
	})

	it('swallows the keyup of a consumed chord key', () => {
		// Regression: vue-shortkey bindings use .once and therefore fire on
		// keyup, so g a also triggered whatever `a` is bound to (reply all).
		press('g')
		press('a')
		const keyup = press('a', 'keyup')

		expect(keyup.defaultPrevented).toBe(true)
	})

	it('ignores the chord when jumps are disabled', () => {
		setJumpsEnabled(false)
		const seen = []
		const listener = (e) => seen.push(e.detail)
		window.addEventListener('mail:jump', listener)

		press('g')
		press('a')

		window.removeEventListener('mail:jump', listener)
		expect(seen).toEqual([])
	})

	it('does not consume a second key that is not a jump target', () => {
		press('g')
		const other = press('z')

		expect(other.defaultPrevented).toBe(false)
	})
})

describe('chord listener ordering', () => {
	beforeAll(() => {
		setJumpsEnabled(true)
	})

	afterAll(() => {
		setJumpsEnabled(false)
	})

	it('keeps consumed chord keys away from document listeners', () => {
		// vue-shortkey registers its listeners on document at module load, and
		// its bindings fire on keyup. If a consumed chord key reaches it, `g a`
		// runs reply-all as well as jumping. Capture goes window -> document,
		// so the chord handler must intercept before this listener sees it.
		const leaked = []
		const spy = (e) => leaked.push(e.type + ':' + e.key)
		document.addEventListener('keydown', spy, true)
		document.addEventListener('keyup', spy, true)

		// Dispatch on body, as a real keypress does, so the event actually
		// propagates window -> document -> body.
		for (const [key, type] of [['g', 'keydown'], ['a', 'keydown'], ['a', 'keyup'], ['g', 'keyup']]) {
			document.body.dispatchEvent(new KeyboardEvent(type, { key, bubbles: true, cancelable: true }))
		}

		document.removeEventListener('keydown', spy, true)
		document.removeEventListener('keyup', spy, true)
		expect(leaked).toEqual([])
	})
})
