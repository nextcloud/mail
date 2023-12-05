/**
 * @copyright Copyright (c) 2022 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import Vue from 'vue'

export default {
	addMessage(state, { message }) {
		const existing = state.messages[message.id] ?? {}
		Vue.set(state.messages, message.id, Object.assign({}, existing, message))
		// Add the message only if it's new
		if (state.messageList.indexOf(message.id) === -1) {
			state.messageList.unshift(message.id)
		}
	},
	deleteMessage(state, { id }) {
		state.messageList = state.messageList.filter(i => i !== id)
		Vue.delete(state.messages, id)
	},
	stopMessage(state, { message }) {
		Vue.delete(message, 'sendAt')
	},
	updateMessage(state, { message }) {
		const existing = state.messages[message.id]
		Vue.set(state.messages, message.id, Object.assign({}, existing, message))
	},
}
