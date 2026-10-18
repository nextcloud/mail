/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { formatHotkey, hasJumps, installJumpChords, jumpActions, keySequenceParts, resolveShortcuts, setJumpsEnabled, SHORTCUT_JUMPS, shortcutActions, SHORTCUTS, shortcutsAllowed } from '../../shortcuts.js'

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
			'archAlt',
			'flag',
			'select',
			'next',
			'nextAlt',
			'prev',
			'prevAlt',
			'open',
			'search',
			'refresh',
			'unseen',
			'unseenAlt',
			'compose',
			'reply',
			'replyAll',
			'forward',
			'junk',
			'important',
			'help',
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

		for (const expected of ['del', 'arch', 'flag', 'next', 'prev', 'refresh', 'unseen']) {
			expect(actions).toContain(expected)
		}

		for (const expected of ['delAlt', 'delBackspace', 'archAlt', 'unseenAlt', 'nextAlt', 'prevAlt', 'select', 'open', 'compose', 'reply', 'replyAll', 'forward', 'search', 'junk', 'important', 'help']) {
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

describe('bindings', () => {
	it('keeps the existing bindings', () => {
		expect(SHORTCUTS.del).toEqual(['del'])
		expect(SHORTCUTS.arch).toEqual(['a'])
		expect(SHORTCUTS.flag).toEqual(['s'])
		expect(SHORTCUTS.next).toEqual(['arrowright'])
		expect(SHORTCUTS.prev).toEqual(['arrowleft'])
		expect(SHORTCUTS.refresh).toEqual(['r'])
		expect(SHORTCUTS.unseen).toEqual(['u'])
	})

	it('adds the Gmail keys next to them', () => {
		expect(SHORTCUTS.compose).toEqual(['c'])
		expect(SHORTCUTS.reply).toEqual(['shift', 'r'])
		expect(SHORTCUTS.replyAll).toEqual(['shift', 'a'])
		expect(SHORTCUTS.forward).toEqual(['f'])
		expect(SHORTCUTS.archAlt).toEqual(['e'])
		expect(SHORTCUTS.nextAlt).toEqual(['j'])
		expect(SHORTCUTS.prevAlt).toEqual(['k'])
		expect(SHORTCUTS.open).toEqual(['o'])
		expect(SHORTCUTS.unseenAlt).toEqual(['shift', 'u'])
		expect(SHORTCUTS.select).toEqual(['x'])
		expect(SHORTCUTS.search).toEqual(['/'])
		expect(SHORTCUTS.junk).toEqual(['shift', '!'])
		expect(SHORTCUTS.important).toEqual(['='])
		expect(SHORTCUTS.help).toEqual(['shift', '?'])
	})

	it('adds delete keys without changing the existing one', () => {
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
			expect(row.keys).toHaveLength(2)
			expect(row.keys[0]).toBe('g')
			expect(row.label.length).toBeGreaterThan(0)
		}
	})

	it('gives every jump chord row parts holding the g key and the jump key', () => {
		const rows = jumpActions(t)

		for (const row of rows) {
			const keys = row.parts.filter((part) => part.type === 'key').map((part) => part.key)
			expect(keys).toEqual(row.keys)
		}
	})

	it('splits the default translation into keys and connective text', () => {
		const parts = keySequenceParts(t, 'g', 'i')

		expect(parts).toEqual([
			{ type: 'key', key: 'g' },
			{ type: 'text', text: ' then ' },
			{ type: 'key', key: 'i' },
		])
	})

	it('honours a translation that reorders the placeholders', () => {
		const reordered = () => '{secondKey} nach {firstKey}'

		const parts = keySequenceParts(reordered, 'g', 'i')

		expect(parts).toEqual([
			{ type: 'key', key: 'i' },
			{ type: 'text', text: ' nach ' },
			{ type: 'key', key: 'g' },
		])
	})

	it('drops empty text parts', () => {
		const adjacent = () => '{firstKey}{secondKey}'

		const parts = keySequenceParts(adjacent, 'g', 'i')

		expect(parts).toEqual([
			{ type: 'key', key: 'g' },
			{ type: 'key', key: 'i' },
		])
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
		// keyup, so g a also triggered whatever `a` is bound to (archive).
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
		// runs archive as well as jumping. Capture goes window -> document,
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
