/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import {Test} from '../../../sieve/Test.js'
import {randomId} from "../../../util/randomId";

describe('mail filter', () => {
	describe('subject', () => {
		it('match type is', () => {
			const test = new Test(1000)
			test.field = 'subject'
			test.operator = 'is'
			test.value = 'Spam Spam'

			const sieve = test.toSieve()
			expect(sieve.script).toBe(`header :is "subject" "Spam Spam"`)
			expect(sieve.extensions).toEqual([])
		})

		it('match type contains', () => {
			const test = new Test(1000)
			test.field = 'subject'
			test.operator = 'contains'
			test.value = 'Spam Spam'

			const sieve = test.toSieve()
			expect(sieve.script).toBe(`header :contains "subject" "Spam Spam"`)
			expect(sieve.extensions).toEqual([])
		})
	})

	describe('to', () => {
		it('match type is', () => {
			const test = new Test(1000)
			test.field = 'to'
			test.operator = 'is'
			test.value = 'bob@acme.org'

			const sieve = test.toSieve()
			expect(sieve.script).toBe(`address :is :all "to" "bob@acme.org"`)
			expect(sieve.extensions).toEqual([])
		})

		it('match type contains', () => {
			const test = new Test(1000)
			test.field = 'to'
			test.operator = 'contains'
			test.value = '@acme.org'

			const sieve = test.toSieve()
			expect(sieve.script).toBe(`address :contains :all "to" "@acme.org"`)
			expect(sieve.extensions).toEqual([])
		})
	})
})
