/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { DroppableMailbox } from './droppable-mailbox.js'

let instance

export const DroppableMailboxDirective = {
	bind(el, binding, vnode) {
		instance = new DroppableMailbox(el, vnode.context, binding.value)
	},
	componentUpdated(el, binding) {
		instance.options = binding.value
		instance.update(el, instance)
	},
}

export default DroppableMailbox
