import dragEventBus from '../util/dragEventBus'
import defer from 'lodash/defer'

export class DraggableEnvelope {

	constructor(el, componentInstance, options) {
		this.el = el
		this.options = options
		this.registerListeners.bind(this)(el)
		this.setInitialAttributes()
	}

	setInitialAttributes() {
		this.el.setAttribute('draggable', 'true')
		this.el.classList.add('draggable-envelope')
	}

	update(el, instance) {
		this.options = instance.options
	}

	registerListeners(el) {
		el.addEventListener('dragstart', this.onDragStart.bind(this))
		el.addEventListener('dragend', this.onDragEnd.bind(this))
	}

	removeListeners(el) {
		el.removeEventListener('dragstart', this.onDragStart)
		el.removeEventListener('dragend', this.onDragEnd)
	}

	onDragStart(event) {
		if (!this.options.isDraggable) {
			return
		}
		const { accountId, mailboxId, selectedEnvelopes } = this.options

		event.dataTransfer.clearData()
		event.dataTransfer.effectAllowed = 'move'

		const envelopes = []
		if (selectedEnvelopes.length) {
			// handle dragged selection mode items
			selectedEnvelopes.forEach((envelope, index) => {
				envelopes.push({
					accountId,
					mailboxId,
					databaseId: envelope.databaseId,
					draggableLabel: `${envelope.subject} (${envelope.from[0].label})`,
				})
			})
		} else {
			// handle single dragged item
			const { databaseId, draggableLabel } = this.options
			envelopes.push({ accountId, mailboxId, databaseId, draggableLabel })
		}

		event.dataTransfer.setData('text/plain', JSON.stringify(envelopes))
		this.attachGhost({ event, envelopes })

		dragEventBus.$emit('drag-start', {
			accountId,
			mailboxId,
			itemCount: envelopes.length,
		})
	}

	onDragEnd(event) {
		dragEventBus.$emit('drag-end', { accountId: this.options.accountId })
	}

	attachGhost({ event, envelopes }) {
		const baseClass = 'draggable-envelope-ghost'
		const dragNode = document.createElement('div')
		dragNode.classList.add(baseClass)

		const counterNode = document.createElement('span')
		counterNode.classList.add(`${baseClass}--counter`)
		const textCountNode = document.createTextNode(envelopes.length)
		counterNode.appendChild(textCountNode)
		dragNode.appendChild(counterNode)

		const labelWrapperNode = document.createElement('div')
		labelWrapperNode.classList.add(`${baseClass}--label-wrapper`)

		envelopes.forEach(envelope => {
			const labelNode = document.createElement('div')
			labelNode.classList.add(`${baseClass}--label-wrapper--label`)
			const textLabelNode = document.createTextNode(envelope.draggableLabel)
			labelNode.appendChild(textLabelNode)
			labelWrapperNode.appendChild(labelNode)
		})

		dragNode.appendChild(labelWrapperNode)
		document.body.appendChild(dragNode)

		event.dataTransfer.setDragImage(dragNode, 0, 15)

		// the item can be removed immediately, because the
		// browser will take a "screenshot" of the dragImage
		// upon initialization to be used while dragging
		defer(() => {
			document.body.removeChild(dragNode)
		})
	}

}
