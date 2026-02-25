/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getLabelAndAddress, parseEmailList } from '../../../util/emailAddress.js'

describe('getLabelAndAddress', () => {
	it('parses a plain email address', () => {
		expect(getLabelAndAddress('alice@example.com')).toEqual({
			label: 'alice@example.com',
			email: 'alice@example.com',
		})
	})

	it('parses an email with angle brackets', () => {
		expect(getLabelAndAddress('<alice@example.com>')).toEqual({
			label: 'alice@example.com',
			email: 'alice@example.com',
		})
	})

	it('parses a display name with angle bracket email', () => {
		expect(getLabelAndAddress('Alice Smith <alice@example.com>')).toEqual({
			label: 'Alice Smith',
			email: 'alice@example.com',
		})
	})

	it('handles uppercase email addresses (case-insensitive)', () => {
		expect(getLabelAndAddress('User@Example.COM')).toEqual({
			label: 'User@Example.COM',
			email: 'User@Example.COM',
		})
	})

	it('handles mixed case with display name', () => {
		expect(getLabelAndAddress('Alice <Alice@Example.COM>')).toEqual({
			label: 'Alice',
			email: 'Alice@Example.COM',
		})
	})

	it('returns null for invalid input', () => {
		expect(getLabelAndAddress('not an email')).toBeNull()
	})

	it('returns null for empty string', () => {
		expect(getLabelAndAddress('')).toBeNull()
	})

	it('does not include trailing delimiters in the email', () => {
		expect(getLabelAndAddress('alice@example.com,')).toEqual({
			label: 'alice@example.com',
			email: 'alice@example.com',
		})
	})

	it('does not include trailing semicolons in the email', () => {
		expect(getLabelAndAddress('alice@example.com;')).toEqual({
			label: 'alice@example.com',
			email: 'alice@example.com',
		})
	})

	it('handles email with subdomains', () => {
		expect(getLabelAndAddress('user@mail.example.co.uk')).toEqual({
			label: 'user@mail.example.co.uk',
			email: 'user@mail.example.co.uk',
		})
	})

	it('handles email with special characters in local part', () => {
		expect(getLabelAndAddress('user+tag@example.com')).toEqual({
			label: 'user+tag@example.com',
			email: 'user+tag@example.com',
		})
	})
})

describe('parseEmailList', () => {
	it('parses a single email', () => {
		expect(parseEmailList('alice@example.com')).toEqual([
			{ label: 'alice@example.com', email: 'alice@example.com' },
		])
	})

	it('parses comma-separated emails', () => {
		expect(parseEmailList('alice@example.com, bob@example.com')).toEqual([
			{ label: 'alice@example.com', email: 'alice@example.com' },
			{ label: 'bob@example.com', email: 'bob@example.com' },
		])
	})

	it('parses semicolon-separated emails', () => {
		expect(parseEmailList('alice@example.com; bob@example.com')).toEqual([
			{ label: 'alice@example.com', email: 'alice@example.com' },
			{ label: 'bob@example.com', email: 'bob@example.com' },
		])
	})

	it('parses space-separated emails', () => {
		expect(parseEmailList('alice@example.com bob@example.com')).toEqual([
			{ label: 'alice@example.com', email: 'alice@example.com' },
			{ label: 'bob@example.com', email: 'bob@example.com' },
		])
	})

	it('parses emails with display names', () => {
		expect(parseEmailList('Alice <alice@example.com>, Bob <bob@example.com>')).toEqual([
			{ label: 'Alice', email: 'alice@example.com' },
			{ label: 'Bob', email: 'bob@example.com' },
		])
	})

	it('handles uppercase email addresses', () => {
		expect(parseEmailList('User@Example.COM, Another@TEST.org')).toEqual([
			{ label: 'User@Example.COM', email: 'User@Example.COM' },
			{ label: 'Another@TEST.org', email: 'Another@TEST.org' },
		])
	})

	it('handles mixed delimiters', () => {
		expect(parseEmailList('a@example.com, b@example.com; c@example.com')).toEqual([
			{ label: 'a@example.com', email: 'a@example.com' },
			{ label: 'b@example.com', email: 'b@example.com' },
			{ label: 'c@example.com', email: 'c@example.com' },
		])
	})

	it('treats text before an email as a display name', () => {
		expect(parseEmailList('not-an-email, alice@example.com')).toEqual([
			{ label: 'not-an-email,', email: 'alice@example.com' },
		])
	})

	it('skips entries without any email address', () => {
		expect(parseEmailList('just-text')).toEqual([])
	})

	it('returns empty array for empty string', () => {
		expect(parseEmailList('')).toEqual([])
	})

	it('returns empty array for string without emails', () => {
		expect(parseEmailList('just some text without emails')).toEqual([])
	})

	it('handles trailing delimiters', () => {
		expect(parseEmailList('alice@example.com, bob@example.com,')).toEqual([
			{ label: 'alice@example.com', email: 'alice@example.com' },
			{ label: 'bob@example.com', email: 'bob@example.com' },
		])
	})

	it('handles multiple addresses with angle brackets and display names', () => {
		expect(parseEmailList('Alice Smith <alice@example.com>; Bob Jones <bob@example.com>')).toEqual([
			{ label: 'Alice Smith', email: 'alice@example.com' },
			{ label: 'Bob Jones', email: 'bob@example.com' },
		])
	})
})
