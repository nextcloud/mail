/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'

export default defineStore('mailFilter', {
	state: () => {
		return {
			filters: [
				{
					accountId: 1,
					id: 'filter1000',
					name: 'Filter 1',
					enable: true,
					operator: 'allof',
					tests: [
						{
							id: 'filter1000-test1',
							field: 'subject',
							operator: 'contains',
							value: 'Hello Hello',
						},
						{
							id: 'filter1000-test2',
							field: 'to',
							operator: 'is',
							value: 'bob@acme.org',
						},
					],
					actions: [
						{
							id: 'filter1000-action1',
							type: 'addflag',
							flag: 'Important',
						},
						{
							id: 'filter1000-action2',
							type: 'keep',
						},
					],
				},
				{
					accountId: 1,
					id: 'filter2000',
					name: 'Filter 2',
					enable: true,
					operator: 'allof',
					tests: [
						{
							id: 'filter2000-test3',
							field: 'subject',
							operator: 'contains',
							value: 'Hello Hello',
						},
						{
							id: 'filter2000-test2',
							field: 'to',
							operator: 'is',
							value: 'bob@acme.org',
						},
					],
					actions: [
						{
							id: 'filter2000-action1',
							type: 'addflag',
							flag: 'Important',
						},
						{
							id: 'filter2000-action2',
							type: 'keep',
						},
					],
				},
			],
		}
	},
	getters: {
		getFiltersByAccountId: (state) => (accountId) => state.filters.filter(filter => filter.accountId === accountId),
	},
	actions: {

	},
})
