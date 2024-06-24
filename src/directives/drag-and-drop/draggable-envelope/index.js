/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { DraggableEnvelope } from './draggable-envelope.js'

let instances = []

export const DraggableEnvelopeDirective = {
	bind(el, binding, vnode) {
		const instance = new DraggableEnvelope(el, vnode.context, binding.value)
		instances.push(instance)
	},
	componentUpdated(el, binding) {
		const options = binding.value
		setTimeout(() => {
			instances.forEach(instance => {
				instance.options.selectedEnvelopes = options.selectedEnvelopes
				instance.update(el, instance)
			})
		})
	},
	unbind(el) {
		instances = instances.filter((instance) => instance.el !== el)
	},
}

export default DraggableEnvelope
