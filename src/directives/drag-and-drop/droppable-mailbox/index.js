/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { DroppableMailbox } from './droppable-mailbox.js'

let instances = []

function onBind(el, binding) {
	const instance = new DroppableMailbox(el, binding.value)
	instances.push(instance)
}

function onUpdate(el, binding) {
	const instance = instances.find((instance) => instance.el === el)
	if (instance) {
		instance.options = binding.value
	}
}

function onUnbind(el) {
	const instance = instances.find((instance) => instance.el === el)
	if (instance) {
		instance.removeListeners(el)
	}
	instances = instances.filter((instance) => instance.el !== el)
}

export const DroppableMailboxDirective = {
	// Vue 2
	bind: onBind,
	componentUpdated: onUpdate,
	unbind: onUnbind,
	// Vue 3
	mounted: onBind,
	updated: onUpdate,
	unmounted: onUnbind,
}

export default DroppableMailbox
