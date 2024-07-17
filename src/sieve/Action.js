/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export class Action {

	id
	type

	toSieve() {
		let script = ''
		const extensions = ['imap4flags']

		if (this.field === 'subject') {
			script = `header :${this.operator} "subject" "${this.value}"`
		}

		if (this.field === 'to') {
			script = `address :${this.operator} :all "to" "${this.value}"`
		}

		return {
			script,
			extensions,
		}
	}

	copy() {
		const copy = new Action()
		copy.id = this.id
		copy.type = this.type

		if (this.type === 'fileinto') {
			copy.mailbox = this.mailbox ?? ''
		}

		if (this.type === 'addflag') {
			copy.flag = this.flag ?? ''
		}

		copy.flag = this.flag
		return copy
	}

}
