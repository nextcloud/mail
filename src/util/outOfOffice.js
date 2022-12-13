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

const MARKER = '### Nextcloud Mail: Vacation Responder ### DON\'T EDIT ###'
const DATA_MARKER = '# DATA: '
const VERSION = 1

// Parser states
const PARSER_COPY = 0
const PARSER_SKIP = 1

export class OOOParserError extends Error {}

/**
 * Parse embedded out of office state from the given sieve script.
 * Return the original sieve script without any out of office data/script.
 *
 * @param {string} sieveScript
 * @return {{data: object|undefined, sieveScript: string}}
 */
export function parseOutOfOfficeState(sieveScript) {
	const lines = sieveScript.split(/\r?\n/)

	const out = []
	let data

	let state = PARSER_COPY
	let nextState = state

	for (const line of lines) {
		switch (state) {
		case PARSER_COPY:
			if (line.startsWith(MARKER)) {
				nextState = PARSER_SKIP
				break
			}
			out.push(line)
			break
		case PARSER_SKIP:
			if (line.startsWith(MARKER)) {
				nextState = PARSER_COPY
			} else if (line.startsWith(DATA_MARKER)) {
				const json = line.slice(DATA_MARKER.length)
				data = JSON.parse(json)
				if (data.start) data.start = new Date(data.start)
				if (data.end) data.end = new Date(data.end)
			}
			break
		default:
			throw new OOOParserError('Reached an invalid state')
		}
		state = nextState
	}

	return {
		sieveScript: out.join('\n'),
		data,
	}
}

/**
 * Embed vacation responder action and out of office state into the given sieve script.
 *
 * @param {string} sieveScript
 * @param {object} data
 * @param {bool} data.enabled
 * @param {Date=} data.start First day (inclusive)
 * @param {Date=} data.end Last day (inclusive)
 * @param {string} data.subject
 * @param {string} data.message
 * @param {string[]=} data.allowedRecipients Only respond if recipient of incoming mail is in this list. Format: `Test Test <test@test.com>`
 * @return {string} Sieve script
 */
export function buildOutOfOfficeSieveScript(sieveScript, {
	enabled,
	start,
	end,
	subject,
	message,
	allowedRecipients,
}) {
	// State to be embedded in the sieve script
	const data = {
		version: VERSION,
		enabled,
		start: start ? formatDateForSieve(start) : undefined,
		end: end ? formatDateForSieve(end) : undefined,
		subject,
		message,
	}

	// Save only state if vacation responder is disabled
	if (!enabled) {
		delete data.start
		delete data.end
		return [
			sieveScript,
			MARKER,
			DATA_MARKER + JSON.stringify(data),
			MARKER,
		].join('\n')
	}

	// Build if condition for start and end dates
	let condition
	if (end) {
		condition = `allof(currentdate :value "ge" "date" "${formatDateForSieve(start)}", currentdate :value "le" "date" "${formatDateForSieve(end)}")`
	} else {
		condition = `currentdate :value "ge" "date" "${formatDateForSieve(start)}"`
	}

	// Build vacation command
	const vacation = [
		'vacation',
		':days 4',
		`:subject "${escapeStringForSieve(subject)}"`,
	]

	if (allowedRecipients?.length) {
		const formattedRecipients = allowedRecipients.map(recipient => `"${recipient}"`).join(', ')
		vacation.push(`:addresses [${formattedRecipients}]`)
	}

	vacation.push(`"${escapeStringForSieve(message)}"`)

	// Build sieve script
	/* eslint-disable no-template-curly-in-string */
	const subjectSection = [
		'set "subject" "";',
		'if header :matches "subject" "*" {',
		'\tset "subject" "${1}";',
		'}',
	]
	const hasSubjectPlaceholder
		= subject.indexOf('${subject}') !== -1 || message.indexOf('${subject}') !== -1
	/* eslint-enable no-template-curly-in-string */

	const requireSection = [
		MARKER,
		'require "date";',
		'require "relational";',
		'require "vacation";',
	]
	if (hasSubjectPlaceholder) {
		requireSection.push('require "variables";')
	}
	requireSection.push(MARKER)

	const vacationSection = [
		MARKER,
		DATA_MARKER + JSON.stringify(data),
	]
	if (hasSubjectPlaceholder) {
		vacationSection.push(...subjectSection)
	}
	vacationSection.push(
		`if ${condition} {`,
		`\t${vacation.join(' ')};`,
		'}',
		MARKER,
	)

	return [
		...requireSection,
		sieveScript,
		...vacationSection,
	].join('\n')
}

/**
 * Format a JavaScript date object to use with the sieve :vacation action.
 *
 * @param {Date} date JavaScript date object
 * @return {string} YYYY-MM-DD
 */
export function formatDateForSieve(date) {
	const year = date.getFullYear().toString().padStart(4, '0')
	const month = (date.getMonth() + 1).toString().padStart(2, '0')
	const day = date.getDate().toString().padStart(2, '0')
	return `${year}-${month}-${day}`
}

/**
 * Escape a string for use in a sieve script.
 * The string has to be surrounded by double quotes (`"`) manually.
 *
 * @param {string} string String to escape
 * @return {string} Escaped string
 */
export function escapeStringForSieve(string) {
	return string
		.replaceAll(/\\/g, '\\\\')
		.replaceAll(/"/g, '\\"')
}
