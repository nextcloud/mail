/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export class Test {

	id
	field = ''
	operator = ''
	value = ''

	toSieve() {
		let script = ''
		const extensions = []

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
		const copy = new Test()
		copy.id = this.id
		copy.field = this.field
		copy.operator = this.operator
		copy.value = this.value
		return copy
	}

}
