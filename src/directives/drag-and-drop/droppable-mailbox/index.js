import { DroppableMailbox } from './droppable-mailbox'

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
