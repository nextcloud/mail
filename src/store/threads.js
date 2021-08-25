import { PRIORITY_INBOX_ID, UNIFIED_INBOX_ID } from './constants'
import Vue from 'vue'
import { normalizedEnvelopeListId } from './normalization'
import orderBy from 'lodash/fp/orderBy'

const state = {
	lists: {
		[UNIFIED_INBOX_ID]: {},
		[PRIORITY_INBOX_ID]: {},
	},
}

const mutations = {
	addAccount(state, account) {
		const mailboxes = account.mailboxes || []
		mailboxes.map(mailboxId => Vue.set(state.lists, mailboxId, {}))
	},
	addMailbox(state, { mailbox }) {
		Vue.set(state.lists, mailbox.databaseId, {})
	},
	addThread(state, { mailboxId, query, envelope }) {
		const listId = normalizedEnvelopeListId(query)
		const list = state.lists[mailboxId][listId] || []

		const index = list.findIndex(
			(item) => item.threadRootId === envelope.threadRootId)
		const item = {
			threadRootId: envelope.threadRootId,
			messageId: envelope.databaseId,
			dateInt: envelope.dateInt,
		}

		if (index === -1) {
			list.push(item)
		} else if (list[index].messageId < item.messageId) {
			list[index] = item
		}

		list.sort(orderBy('dateInt', 'desc'))
		Vue.set(state.lists[mailboxId], listId, list)
	},
	removeThread(state, { mailboxId, envelopeId }) {
		const removeEnvelopeById = (item) => item.messageId !== envelopeId
		const lists = state.lists[mailboxId]

		for (let list of lists) {
			list = list.filter(removeEnvelopeById)
		}

		Vue.set(state.lists, mailboxId, lists)
	},
}

const actions = {}

const getters = {
	getThreads: (state, getters, rootState) => (mailboxId, query) => {
		const listId = normalizedEnvelopeListId(query)
		const list = state.lists[mailboxId][listId] || []

		return list.map((item) => rootState.envelopes[item.messageId])
	},
	isMessageMounted: (state, getters) => (mailboxId, messageId) => {
		const envelope = getters.getEnvelope(messageId)
		if (envelope === undefined) {
			return false
		}

		const hasThreadRootId = (item) => item.threadRootId === envelope.threadRootId
		const lists = state.lists[mailboxId]

		for (const list of lists) {
			if (list.some(hasThreadRootId)) {
				return true
			}
		}

		return false
	},
}

export default { state, mutations, actions, getters }
