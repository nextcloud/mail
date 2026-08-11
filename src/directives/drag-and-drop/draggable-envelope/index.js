/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { DraggableEnvelope } from './draggable-envelope.js'

let instances = []

function onBind(el, binding) {
	const instance = new DraggableEnvelope(el, binding.value)
	instances.push(instance)
}

function onUpdate(el, binding) {
	const options = binding.value
	setTimeout(() => {
		instances.forEach((instance) => {
			instance.options.selectedEnvelopes = options.selectedEnvelopes
			instance.update(el, instance)
		})
	})
}

function onUnbind(el) {
	instances = instances.filter((instance) => instance.el !== el)
}

export const DraggableEnvelopeDirective = {
	// Vue 2
	bind: onBind,
	componentUpdated: onUpdate,
	unbind: onUnbind,
	// Vue 3
	mounted: onBind,
	updated: onUpdate,
	unmounted: onUnbind,
}

export default DraggableEnvelope
