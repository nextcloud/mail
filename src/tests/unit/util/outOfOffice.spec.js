/**
 * @copyright Copyright (c) 2022 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import {
	buildOutOfOfficeSieveScript, escapeStringForSieve,
	formatDateForSieve,
	parseOutOfOfficeState,
} from '../../../util/outOfOffice'

describe('outOfOffice', () => {
	describe('parseOutOfOfficeState', () => {
		it('should parse a sieve script containing an enabled vacation responder', () => {
			const script = readTestData('sieve-vacation-on.txt')
			const cleanedScript = readTestData('sieve-vacation-cleaned.txt')
			const expected = {
				sieveScript: cleanedScript,
				data: {
					version: 1,
					enabled: true,
					start: new Date('2022-09-02'),
					end: new Date('2022-09-08'),
					subject: 'On vacation',
					message: 'I\'m on vacation.',
				},
			}
			const actual = parseOutOfOfficeState(script)
			expect(actual).toEqual(expected)
		})

		it('should parse a sieve script containing a disabled vacation responder', () => {
			const script = readTestData('sieve-vacation-off.txt')
			const cleanedScript = readTestData('sieve-vacation-cleaned.txt')
			const expected = {
				sieveScript: cleanedScript,
				data: {
					version: 1,
					enabled: false,
					subject: 'On vacation',
					message: 'I\'m on vacation.',
				},
			}
			const actual = parseOutOfOfficeState(script)
			expect(actual).toEqual(expected)
		})

		it('should leave a foreign script untouched', () => {
			const script = readTestData('sieve-vacation-cleaned.txt')
			const expected = {
				sieveScript: script,
				data: undefined,
			}
			const actual = parseOutOfOfficeState(script)
			expect(actual).toEqual(expected)
		})
	})

	describe('buildOutOfOfficeSieveScript', () => {
		it('should build a correct sieve script when the vacation responder is enabled', () => {
			const script = readTestData('sieve-vacation-cleaned.txt')
			const expected = readTestData('sieve-vacation-on.txt')
			const actual = buildOutOfOfficeSieveScript(script, {
				enabled: true,
				start: new Date('2022-09-02'),
				end: new Date('2022-09-08'),
				subject: 'On vacation',
				message: 'I\'m on vacation.',
				allowedRecipients: [
					'Test Test <test@test.org>',
					'Test Alias <alias@test.org>',
				]
			})
			expect(actual).toEqual(expected)
		})

		it('should build a correct sieve script when the vacation responder is enabled and no end date is given', () => {
			const script = readTestData('sieve-vacation-cleaned.txt')
			const expected = readTestData('sieve-vacation-on-no-end-date.txt')
			const actual = buildOutOfOfficeSieveScript(script, {
				enabled: true,
				start: new Date('2022-09-02'),
				subject: 'On vacation',
				message: 'I\'m on vacation.',
				allowedRecipients: [
					'Test Test <test@test.org>',
					'Test Alias <alias@test.org>',
				]
			})
			expect(actual).toEqual(expected)
		})

		it('should build a correct sieve script when the vacation responder is enabled and the message contains special chars', () => {
			const script = readTestData('sieve-vacation-cleaned.txt')
			const expected = readTestData('sieve-vacation-on-special-chars-message.txt')
			const actual = buildOutOfOfficeSieveScript(script, {
				enabled: true,
				start: new Date('2022-09-02'),
				subject: 'On vacation',
				message: 'I\'m on vacation.\n"Hello, World!"\n\\ escaped backslash',
				allowedRecipients: [
					'Test Test <test@test.org>',
					'Test Alias <alias@test.org>',
				]
			})
			expect(actual).toEqual(expected)
		})

		it('should build a correct sieve script when the vacation responder is enabled and the subject contains special chars', () => {
			const script = readTestData('sieve-vacation-cleaned.txt')
			const expected = readTestData('sieve-vacation-on-special-chars-subject.txt')
			const actual = buildOutOfOfficeSieveScript(script, {
				enabled: true,
				start: new Date('2022-09-02'),
				subject: 'On vacation, \"Hello, World!\", \\ escaped backslash',
				message: 'I\'m on vacation.',
				allowedRecipients: [
					'Test Test <test@test.org>',
					'Test Alias <alias@test.org>',
				]
			})
			expect(actual).toEqual(expected)
		})

		it('should build a correct sieve script when the vacation responder is enabled and a subject placeholder is used', () => {
			const script = readTestData('sieve-vacation-cleaned.txt')
			const expected = readTestData('sieve-vacation-on-subject-placeholder.txt')
			const actual = buildOutOfOfficeSieveScript(script, {
				enabled: true,
				start: new Date('2022-09-02'),
				end: new Date('2022-09-08'),
				subject: 'Re: ${subject}',
				message: 'I\'m on vacation.',
				allowedRecipients: [
					'Test Test <test@test.org>',
					'Test Alias <alias@test.org>',
				]
			})
			expect(actual).toEqual(expected)
		})

		it('should build a correct sieve script when the vacation responder is disabled', () => {
			const script = readTestData('sieve-vacation-cleaned.txt')
			const expected = readTestData('sieve-vacation-off.txt')
			const actual = buildOutOfOfficeSieveScript(script, {
				enabled: false,
				subject: 'On vacation',
				message: 'I\'m on vacation.',
			})
			expect(actual).toEqual(expected)
		})
	})

	describe('formatDateForSieve', () => {
		it('should format a JS date instance according to YYYY-MM-DD', () => {
			const date = new Date('2022-09-02')
			const expected = '2022-09-02'
			const actual = formatDateForSieve(date)
			expect(actual).toEqual(expected)
		})
	})

	describe('escapeStringForSieve', () => {
		it('should escape double quotes', () => {
			const string = 'A string with "quotes"'
			const expected = 'A string with \\"quotes\\"'
			const actual = escapeStringForSieve(string)
			expect(actual).toEqual(expected)
		})

		it('should escape backslashes', () => {
			const string = 'A string with \\ backslashes'
			const expected = 'A string with \\\\ backslashes'
			const actual = escapeStringForSieve(string)
			expect(actual).toEqual(expected)
		})
	})
})
