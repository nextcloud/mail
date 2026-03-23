/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { DroppableMailbox } from './droppable-mailbox.js'

let instance

function onBind(el, binding) {
	instance = new DroppableMailbox(el, binding.value)
}

function onUpdate(el, binding) {
	instance.options = binding.value
	instance.update(el, instance)
}

export const DroppableMailboxDirective = {
	// Vue 2
	bind: onBind,
	componentUpdated: onUpdate,
	// Vue 3
	mounted: onBind,
	updated: onUpdate,
}

export default DroppableMailbox
