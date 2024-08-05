/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { randomId } from '../util/randomId'
import { Test } from './Test'
import { Action } from './Action'
import logger from '../logger'

export class Filter {

	id
	name = ''
	operator = 'allof'
	tests = []
	actions = []
	active = true

	copy() {
		const copy = new Filter()
		copy.id = this.id
		copy.name = this.name
		copy.operator = this.operator
		copy.tests = this.tests.map((test) => test.copy())
		copy.actions = this.actions.map((action) => action.copy())
		return copy
	}

}
