/*
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/* global expect */

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

import {
	buildReplyBody,
	buildRecipients,
	buildReplySubject,
} from '../../ReplyBuilder'

describe('ReplyBuilder', () => {

	it('creates a reply body without any sender', () => {
		const body = 'Newsletter\nhello\ncheers'
		const expectedReply = '\n\n\n> Newsletter\n> hello\n> cheers'

		const replyBody = buildReplyBody(body)

		expect(replyBody).to.equal(expectedReply)
	})

	it('creates a reply body', () => {
		const body = 'Newsletter\nhello'
		const expectedReply = '\n\n"Test User" <test@user.ru> – November 5, 2018 '

		const replyBody = buildReplyBody(
			body,
			{
				label: 'Test User',
				email: 'test@user.ru',
			},
			1541426237,
		)

		expect(replyBody.startsWith(expectedReply)).to.be.true
	})

	let envelope

	beforeEach(function () {
		envelope = {}
	})

	const createAddress = addr => {
		return {
			label: addr,
			email: addr
		}
	}

	const assertSameAddressList = (l1, l2) => {
		const rawL1 = l1.map(a => a.email)
		const rawL2 = l2.map(a => a.email)
		rawL1.sort()
		rawL2.sort()
		expect(rawL1).to.deep.equal(rawL2)
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

	it('handles simple group reply', () => {
		const a = createAddress('a@domain.tld')
		const b = createAddress('b@domain.tld')
		const c = createAddress('c@domain.tld')
		envelope.from = [a]
		envelope.to = [b, c]
		envelope.cc = []

		var reply = buildRecipients(envelope, b)

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

	it('handles jan\'s reply to nina\'s mesage to a mailing list', () => {
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

	it('adds re: to a reply subject', () => {
		const orig = 'Hello'

		const replySubject = buildReplySubject(orig)

		expect(replySubject).to.equal('RE: Hello')
	})

	it('does not stack subject re:\'s', () => {
		const orig = 'Re: Hello'

		const replySubject = buildReplySubject(orig)

		expect(replySubject).to.equal('Re: Hello')
	})

})

