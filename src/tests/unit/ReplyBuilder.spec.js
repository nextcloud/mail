/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import {
	buildRecipients,
	buildReplyBody,
	buildReplySubject,
	isSameEmailAddress,
	selectReplyIdentity,
} from '../../ReplyBuilder.js'
import { html, plain } from '../../util/text.js'

describe('ReplyBuilder', () => {
	it('creates a reply body without any sender', () => {
		const body = plain('Newsletter\nhello\ncheers')

		const replyBodyTop = buildReplyBody(body)
		const replyBodyBottom = buildReplyBody(body, undefined, undefined, false)

		expect(replyBodyTop).toEqual(html('<p></p><p></p><div class="quote"><br>&gt; Newsletter<br>&gt; hello<br>&gt; cheers</div>'))
		expect(replyBodyBottom).toEqual(html('<div class="quote"><br>&gt; Newsletter<br>&gt; hello<br>&gt; cheers</div><p></p><p></p>'))
	})

	it('creates a reply body', () => {
		const body = plain('Newsletter\nhello')

		const replyBodyTop = buildReplyBody(
			body,
			{
				label: 'Test User',
				email: 'test@user.ru',
			},
			1541426237,
		)
		const replyBodyBottom = buildReplyBody(
			body,
			{
				label: 'Test User',
				email: 'test@user.ru',
			},
			1541426237,
			false,
		)

		expect(replyBodyTop.value.startsWith(html('<p></p><p></p><div class="quote">"Test User" test@user.ru – November 5, 2018 ').value)).toEqual(true)
		expect(replyBodyBottom.value.endsWith(html('<p></p><p></p>').value)).toEqual(true)
	})

	let envelope

	beforeEach(function() {
		envelope = {}
	})

	const createAddress = (addr) => {
		return {
			label: addr,
			email: addr,
		}
	}

	const assertSameAddressList = (l1, l2) => {
		const rawL1 = l1.map((a) => a.email)
		const rawL2 = l2.map((a) => a.email)
		rawL1.sort()
		rawL2.sort()
		expect(rawL1).toEqual(rawL2)
	}

	// b -> a to a -as b
	it('handles a one-on-one reply', () => {
		const a = createAddress('a@domain.tld')
		const b = createAddress('b@domain.tld')
		envelope.from = [b]
		envelope.to = [a]
		envelope.cc = []

		const reply = buildRecipients(envelope, a)

		assertSameAddressList(reply.from, [a])
		assertSameAddressList(reply.to, [b])
		assertSameAddressList(reply.cc, [])
	})

	it('uses From when Reply-To is empty', () => {
		const a = createAddress('a@domain.tld')
		const b = createAddress('b@domain.tld')
		envelope.from = [b]
		envelope.to = [a]
		envelope.cc = []

		const reply = buildRecipients(envelope, a, [])

		assertSameAddressList(reply.from, [a])
		assertSameAddressList(reply.to, [b])
		assertSameAddressList(reply.cc, [])
	})

	it('handles simple group reply', () => {
		const a = createAddress('a@domain.tld')
		const b = createAddress('b@domain.tld')
		const c = createAddress('c@domain.tld')
		envelope.from = [a]
		envelope.to = [b, c]
		envelope.cc = []

		const reply = buildRecipients(envelope, b)

		assertSameAddressList(reply.from, [b])
		assertSameAddressList(reply.to, [a, c])
		assertSameAddressList(reply.cc, [])
	})

	it('handles group reply with CC', () => {
		const a = createAddress('a@domain.tld')
		const b = createAddress('b@domain.tld')
		const c = createAddress('c@domain.tld')
		const d = createAddress('d@domain.tld')
		envelope.from = [a]
		envelope.to = [b, c]
		envelope.cc = [d]

		const reply = buildRecipients(envelope, b)

		assertSameAddressList(reply.from, [b])
		assertSameAddressList(reply.to, [a, c])
		assertSameAddressList(reply.cc, [d])
	})

	it('handles group reply of CC address', () => {
		const a = createAddress('a@domain.tld')
		const b = createAddress('b@domain.tld')
		const c = createAddress('c@domain.tld')
		const d = createAddress('d@domain.tld')
		envelope.from = [a]
		envelope.to = [b, c]
		envelope.cc = [d]

		const reply = buildRecipients(envelope, d)

		assertSameAddressList(reply.from, [d])
		assertSameAddressList(reply.to, [a, b, c])
		assertSameAddressList(reply.cc, [])
	})

	it('handles group reply of CC address with many CCs', () => {
		const a = createAddress('a@domain.tld')
		const b = createAddress('b@domain.tld')
		const c = createAddress('c@domain.tld')
		const d = createAddress('d@domain.tld')
		const e = createAddress('e@domain.tld')
		envelope.from = [a]
		envelope.to = [b, c]
		envelope.cc = [d, e]

		const reply = buildRecipients(envelope, e)

		assertSameAddressList(reply.from, [e])
		assertSameAddressList(reply.to, [a, b, c])
		assertSameAddressList(reply.cc, [d])
	})

	it('handles reply of message where the recipient is in the CC', () => {
		const ali = createAddress('ali@domain.tld')
		const bob = createAddress('bob@domain.tld')
		const me = createAddress('c@domain.tld')
		const dani = createAddress('d@domain.tld')

		envelope.from = [ali]
		envelope.to = [bob]
		envelope.cc = [me, dani]

		const reply = buildRecipients(envelope, me)

		assertSameAddressList(reply.from, [me])
		assertSameAddressList(reply.to, [ali, bob])
		assertSameAddressList(reply.cc, [dani])
	})

	it("handles jan's reply to nina's message to a mailing list", () => {
		const nina = createAddress('nina@nc.com')
		const list = createAddress('list@nc.com')
		const jan = createAddress('jan@nc.com')

		envelope.from = [nina]
		envelope.to = [list]
		envelope.cc = []

		const reply = buildRecipients(envelope, jan)

		assertSameAddressList(reply.from, [jan])
		assertSameAddressList(reply.to, [nina, list])
		assertSameAddressList(reply.cc, [])
	})

	it('removes original sender for recipients list when same as replier (self-sent email)', () => {
		const a = createAddress('a@domain.tld')
		const b = createAddress('b@domain.tld')
		envelope.from = [a]
		envelope.to = [a, b]
		envelope.cc = []

		const reply = buildRecipients(envelope, a)

		assertSameAddressList(reply.from, [a])
		assertSameAddressList(reply.to, [b])
		assertSameAddressList(reply.cc, [])
	})

	it('removes original sender for recipients list when same as replier (self-sent email) with many CC', () => {
		const a = createAddress('a@domain.tld')
		const b = createAddress('b@domain.tld')
		const c = createAddress('c@domain.tld')
		const d = createAddress('d@domain.tld')
		const e = createAddress('e@domain.tld')
		envelope.from = [a]
		envelope.to = [b, c]
		envelope.cc = [a, d, e]

		const reply = buildRecipients(envelope, a)

		assertSameAddressList(reply.from, [a])
		assertSameAddressList(reply.to, [b, c])
		assertSameAddressList(reply.cc, [d, e])
	})

	it('pure self-sent email', () => {
		const a = createAddress('a@domain.tld')
		envelope.from = [a]
		envelope.to = [a]
		envelope.cc = []

		const reply = buildRecipients(envelope, a)

		assertSameAddressList(reply.from, [a])
		assertSameAddressList(reply.to, [a])
		assertSameAddressList(reply.cc, [])
	})

	it('excludes only the selected identity from both To and Cc regardless of case', () => {
		const own = createAddress('work@example.com')
		const sender = createAddress('sender@example.com')
		const otherIdentity = createAddress('me@example.com')
		const message = {
			from: [sender],
			to: [createAddress(' Work@Example.com '), otherIdentity],
			cc: [createAddress('WORK@example.com'), sender],
		}

		const reply = buildRecipients(message, own)

		assertSameAddressList(reply.to, [otherIdentity, sender])
		assertSameAddressList(reply.cc, [sender])
	})

	it('keeps Cc-only follow-ups without adding the sending identity to To', () => {
		const own = createAddress('work@example.com')
		const recipient = createAddress('recipient@example.com')

		const reply = buildRecipients({ from: [own], to: [], cc: [recipient] }, own)

		assertSameAddressList(reply.to, [])
		assertSameAddressList(reply.cc, [recipient])
	})

	it('handles absent recipient lists', () => {
		const own = createAddress('work@example.com')
		const sender = createAddress('sender@example.com')

		const reply = buildRecipients({ from: [sender] }, own)

		assertSameAddressList(reply.to, [sender])
		assertSameAddressList(reply.cc, [])
	})

	it('keeps original follow-up recipients without adding From or Reply-To', () => {
		const own = createAddress('me@example.com')
		const alias = createAddress('work@example.com')
		const recipient = createAddress('recipient@example.com')
		const otherIdentity = createAddress('other@example.com')
		const message = { from: [alias], to: [own, recipient], cc: [own, otherIdentity] }

		const reply = buildRecipients(message, own, [createAddress('reply-elsewhere@example.com')], true)

		assertSameAddressList(reply.to, [recipient])
		assertSameAddressList(reply.cc, [otherIdentity])
	})

	it.each(['to', 'cc'])('keeps pure self-message follow-ups originally addressed in %s', (field) => {
		const own = createAddress('me@example.com')
		const message = { from: [own], to: [], cc: [], [field]: [own] }

		const reply = buildRecipients(message, own, undefined, true)

		assertSameAddressList(reply.to, [own])
		assertSameAddressList(reply.cc, [])
	})

	it('adds re: to a reply subject', () => {
		const orig = 'Hello'

		const replySubject = buildReplySubject(orig)

		expect(replySubject).toEqual('Re: Hello')
	})

	it("does not stack subject re:'s", () => {
		const orig = 'Re: Hello'

		const replySubject = buildReplySubject(orig)

		expect(replySubject).toEqual('Re: Hello')
	})
})

describe('reply identity selection', () => {
	const accounts = [
		{ id: 0, isUnified: true, emailAddress: 'unified@example.com' },
		{
			id: 1,
			emailAddress: 'me@example.com',
			name: 'Me',
			aliases: [
				{ id: 11, alias: 'work@example.com', name: 'Work' },
				{ id: 12, alias: 'second@example.com', name: 'Second' },
				{ id: 13, alias: 'me@example.com', name: 'Duplicate' },
			],
		},
		{
			id: 2,
			emailAddress: 'other@example.com',
			name: 'Other',
			aliases: [
				{ id: 21, alias: 'work@example.com', name: 'Other work' },
				{ id: 22, alias: 'shared@example.com', name: 'Shared' },
			],
		},
		{ id: 3, emailAddress: 'shared@example.com', name: 'Third' },
		{ id: 4, emailAddress: 'disabled@example.com', connectionStatus: false },
	]
	const addresses = (...emails) => emails.map((email) => ({ email }))
	const envelope = {
		accountId: 1,
		from: addresses('sender@example.com'),
		to: [],
		cc: [],
	}

	it.each([
		['primary address before its duplicate alias', { to: addresses('me@example.com') }, false, 1, null],
		['alias in To', { to: addresses('work@example.com') }, false, 1, 11],
		['alias in Cc', { cc: addresses('work@example.com') }, false, 1, 11],
		['another account primary', { to: addresses('other@example.com') }, false, 2, null],
		['another account alias', { to: addresses('shared@example.com') }, false, 2, 22],
		['To before containing account Cc', { to: addresses('shared@example.com'), cc: addresses('me@example.com') }, false, 2, 22],
		['containing account within To', { to: addresses('shared@example.com', 'work@example.com') }, false, 1, 11],
		['header order within containing account', { to: addresses('second@example.com', 'work@example.com') }, false, 1, 12],
		['alias before a different primary in header order', { to: addresses('work@example.com', 'me@example.com') }, false, 1, 11],
		['header order across other accounts', { to: addresses('other@example.com', 'shared@example.com') }, false, 2, null],
		['containing account for duplicate addresses', { accountId: 3, to: addresses('shared@example.com') }, false, 3, null],
		['configured account order for duplicate addresses', { to: addresses('shared@example.com') }, false, 2, 22],
		['sender for sent messages', { from: addresses('work@example.com'), to: addresses('me@example.com') }, true, 1, 11],
		['recipient for received self-mail', { from: addresses('work@example.com'), to: addresses('me@example.com') }, false, 1, null],
		['sender for archived outgoing messages', { from: addresses('work@example.com'), to: addresses('external@example.com') }, false, 1, 11],
	])('selects %s', (_, overrides, preferSender, accountId, aliasId) => {
		const identity = selectReplyIdentity({ ...envelope, ...overrides }, accounts, preferSender)

		expect(identity).toMatchObject({ accountId, aliasId })
	})

	it('returns the configured spelling and display name without changing its inputs', () => {
		const message = { ...envelope, to: addresses(' WORK@Example.com ') }
		const originalAccounts = JSON.stringify(accounts)
		const originalMessage = JSON.stringify(message)

		const identity = selectReplyIdentity(message, accounts)

		expect(identity).toEqual({ accountId: 1, aliasId: 11, email: 'work@example.com', label: 'Work' })
		expect(JSON.stringify(accounts)).toBe(originalAccounts)
		expect(JSON.stringify(message)).toBe(originalMessage)
	})

	it.each([
		['unknown address', { ...envelope, to: addresses('unknown@example.com') }],
		['disabled account', { ...envelope, to: addresses('disabled@example.com') }],
		['unified account', { ...envelope, to: addresses('unified@example.com') }],
		['Reply-To address', { ...envelope, replyTo: addresses('work@example.com') }],
		['absent lists', { accountId: 1 }],
	])('returns no match for %s', (_, message) => {
		expect(selectReplyIdentity(message, accounts)).toBeUndefined()
	})

	it.each([
		[' Work@Example.com ', 'work@example.com', true],
		['work+tag@example.com', 'work@example.com', false],
		['', '', false],
		[' ', ' ', false],
		[undefined, undefined, false],
		['work@example.com', undefined, false],
	])('compares %s and %s as %s', (left, right, matches) => {
		expect(isSameEmailAddress(left, right)).toBe(matches)
	})
})
