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
				if (filters) {
					state.filters = filters.map((filter) => {
						filter.id = randomId()
						filter.tests.map((test) => {
							test.id = randomId()
							if (!test.hasOwnProperty('values')) {
								test.values = [test.value]
							}
							return test
						})
						filter.actions.map((action) => {
							action.id = randomId()
							return action
						})
						if (!filter.hasOwnProperty('priority')) {
							filter.priority = 0
						}
						return filter
					})
				}
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
