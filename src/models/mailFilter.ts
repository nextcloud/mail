/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { randomId } from '../util/randomId'

export enum MailFilterOperator {
	All = 'allof',
	Any = 'anyof'
}

export enum MailFilterConditionField {
	From = 'from',
	Subject = 'subject',
	To = 'to'
}

export enum MailFilterConditionOperator {
	Contains = 'contains',
	Is = 'is',
	Matches = 'matches'
}

export class MailFilterCondition {

	public id: number
	public field: MailFilterConditionField
	public operator: MailFilterConditionOperator
	public values: string[]

	constructor(
	) {
		this.id = randomId()
	}

	hasValues(): boolean {
		return this.values.length > 0
	}

}

interface MailFilterAction {
	id: number,
	type: string,
}

export class MailFilterActionAddflag implements MailFilterAction {

	public id: number
	public type: string
	public flag: string

	constructor(
	) {
		this.id = randomId()
		this.type = 'addflag'
	}

}

export class MailFilterActionMailbox implements MailFilterAction {

	public id: number
	public type: string
	public mailbox: string

	constructor(
	) {
		this.id = randomId()
		this.type = 'fileinto'
	}

}

export class MailFilterActionStop implements MailFilterAction {

	public id: number
	public type: string
	constructor(
	) {
		this.id = randomId()
		this.type = 'stop'
	}

}

export class MailFilter {

	public id: number
	public name: string
	public enable: boolean = false
	public operator: MailFilterOperator
	public tests: MailFilterCondition[]
	public actions: MailFilterAction[]
	public priority: number = 0

	constructor(
	) {
		this.id = randomId()
	}

}
