/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export enum MailFilterOperator {
	All = 'allof',
	Any = 'anyof'
}

export enum MailFilterTestField {
	Subject = 'subject',
	From = 'from',
	To = 'to'
}

export enum MailFilterTestOperator {
	Is = 'is',
	Contains = 'contains',
	Matches = 'matches'
}

export interface MailFilterTest {
	id: number,
	field: MailFilterTestField,
	operator: MailFilterTestOperator,
	values: string[]
}

interface MailFilterAction {
	id: number,
	type: string | null,
}

export interface MailFilterActionAddflag extends MailFilterAction {
	flag: string
}

export interface MailFilterActionMailbox extends MailFilterAction {
	mailbox: string
}

export interface MailFilterActionStop extends MailFilterAction {
}

export default class MailFilter {

	public id: number
	public name: string
	public enable: boolean
	public operator: MailFilterOperator
	public tests: MailFilterTest[]
	public actions: MailFilterAction[]
	public priority: number

	constructor(
		id: number,
		name: string,
		enable: boolean,
		operator: MailFilterOperator,
		tests: MailFilterTest[],
		actions: MailFilterAction[],
		priority: number,
	) {
		this.id = id
		this.name = name
		this.enable = enable
		this.operator = operator
		this.tests = tests
		this.actions = actions
		this.priority = priority
	}

}
