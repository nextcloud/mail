/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import IssueTemplateBuilder from 'nextcloud_issuetemplate_builder'

const flattenError = (error) => {
	let text = ''
	if (error.type) {
		text += error.type + ': '
	}
	text += error.message
	text += '\n'
	if (error.trace) {
		text += flattenTrace(error.trace)
	}
	if (error.stack) {
		text += error.stack
	}
	return text
}

const flattenTrace = (trace) => {
	return trace.reduce(function(acc, entry) {
		let text = ''
		if (entry.class) {
			text += '  at ' + entry.class + '::' + entry.function
		} else {
			text += '  at ' + entry.function
		}
		if (entry.file) {
			text += '\n     ' + entry.file + ', line ' + entry.line
		}
		return acc + text + '\n'
	}, '')
}

export const getReportUrl = (error) => {
	console.error(error)
	let message = error.message || 'An unkown error occurred.'
	if (!message.endsWith('.')) {
		message += '.'
	}
	const builder = new IssueTemplateBuilder()
	const template = builder
		.addEmptyStepsToReproduce()
		.addExpectedActualBehaviour()
		.addLogs('Error', flattenError(error))
		.render()

	return (
		'https://github.com/nextcloud/mail/issues/new'
		+ '?title='
		+ encodeURIComponent(message)
		+ '&body='
		+ encodeURIComponent(template)
	)
}
