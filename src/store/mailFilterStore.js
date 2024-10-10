/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import * as MailFilterService from '../service/MailFilterService.js'
import { randomId } from '../util/randomId.js'

export default defineStore('mailFilter', {
	state: () => {
		return {
			filters: [],
		}
	},
	actions: {
		async fetch(accountId) {
			await this.$patch(async (state) => {
				const filters = await MailFilterService.getFilters(accountId)
				state.filters = filters.map((filter) => {
					filter.id = randomId()
					filter.tests.map((test) => {
						test.id = randomId()
						return test
					})
					filter.actions.map((action) => {
						action.id = randomId()
						return action
					})
					return filter
				})
			})
		},
		async update(accountId) {
			let filters = structuredClone(this.filters)
			filters = filters.map((filter) => {
				delete filter.id
				filter.tests.map((test) => {
					delete test.id
					return test
				})
				filter.actions.map((action) => {
					delete action.id
					return action
				})
				return filter
			})

			await MailFilterService.updateFilters(accountId, filters)
		},
	},
})
