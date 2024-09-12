/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


import { defineStore } from 'pinia'
import mapStoreState from './mainStore/state.js'
import mapStoreGetters from './mainStore/getters.js'
import mapStoreActions from './mainStore/actions.js'

export default defineStore('main', {
	state: mapStoreState(),
	getters: {
		...mapStoreGetters(),
	},
	actions: {
		...mapStoreActions(),
	},
});
