<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div>
		<div v-if="quickActions.length === 0">
			{{ t('mail', 'No quick actions yet.') }}
		</div>
		<NcListItem v-for="action in quickActions" :key="action.id" :name="action?.name">
			<template #icon>
				<IconBolt :size="20" />
			</template>
			<template #actions>
				<NcActionButton @click="openEditModal(action)">
					<template #icon>
						<IconEdit :size="20" />
					</template>
					Edit
				</NcActionButton>
				<NcActionButton variant="error" @click="deleteQuickAction(action.id)">
					<template #icon>
						<IconDelete :size="20" />
					</template>
					Delete
				</NcActionButton>
			</template>
		</NcListItem>
		<NcButton class="add-quick-action" variant="primary" @click="openEditModal()">
			{{ t('mail', 'Add quick action') }}
		</NcButton>
		<NcModal v-if="editModal" :name="modalName" @close="closeEditModal">
			<div class="modal-content">
				<NcTextField :value.sync="localAction.name" :label="t('mail', 'Quick action name')" />
				<Container @onDrop="onDrop">
					<Draggable v-for="item in actions" :key="item.id">
						<Action :action="item"
							:account="account"
							@update="(payload) => updateAction(payload,item)"
							@delete="deleteAction(item)" />
					</Draggable>
				</Container>
				<NcActions :menu-name="t('mail', 'Add another action')">
					<template #icon>
						<PlusIcon :size="20" />
					</template>
					<NcActionButton @click="addQuickAction('markAsSpam')">
						<template #icon>
							<AlertOctagonIcon :size="20" />
						</template>
						Mark as spam
					</NcActionButton>
					<NcActionButton @click="addQuickAction('applyTag')">
						<template #icon>
							<TagIcon :size="20" />
						</template>
						Tag
					</NcActionButton>
					<NcActionButton @click="addQuickAction('moveThread')">
						<template #icon>
							<OpenInNewIcon :size="20" />
						</template>
						Move thread
					</NcActionButton>
					<NcActionButton @click="addQuickAction('deleteThread')">
						<template #icon>
							<IconDelete :size="20" />
						</template>
						delete thread
					</NcActionButton>
					<NcActionButton @click="addQuickAction('markAsRead')">
						<template #icon>
							<EmailRead :size="20" />
						</template>
						Mark as read
					</NcActionButton>
					<NcActionButton @click="addQuickAction('markAsUnread')">
						<template #icon>
							<EmailUnread :size="20" />
						</template>
						Mark as unread
					</NcActionButton>
					<NcActionButton @click="addQuickAction('markAsImportant')">
						<template #icon>
							<ImportantIcon :size="20" />
						</template>
						Mark as important
					</NcActionButton>
					<NcActionButton @click="addQuickAction('markAsFavorite')">
						<template #icon>
							<IconFavorite :size="20" />
						</template>
						Mark as favorite
					</NcActionButton>
				</NcActions>
				<NcButton :disabled="!canSave" @click="saveQuickAction">
					{{ t('mail', 'Save') }}
				</NcButton>
			</div>
		</NcModal>
	</div>
</template>

<script>
import { NcModal, NcListItem, NcActionButton, NcActions, NcButton, NcTextField } from '@nextcloud/vue'
import { Container, Draggable } from 'vue-dndrop'
import Action from './Action.vue'
import useMainStore from '../../store/mainStore.js'
import IconDelete from 'vue-material-design-icons/TrashCanOutline.vue'
import IconEdit from 'vue-material-design-icons/PencilOutline.vue'
import IconBolt from 'vue-material-design-icons/Bolt.vue'
import EmailUnread from 'vue-material-design-icons/EmailOutline.vue'
import AlertOctagonIcon from 'vue-material-design-icons/AlertOctagonOutline.vue'
import ImportantIcon from 'vue-material-design-icons/LabelVariant.vue'
import EmailRead from 'vue-material-design-icons/EmailOpenOutline.vue'
import TagIcon from 'vue-material-design-icons/TagOutline.vue'
import IconFavorite from 'vue-material-design-icons/Star.vue'
import OpenInNewIcon from 'vue-material-design-icons/OpenInNew.vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import { findAllStepsForAction, createActionStep, updateActionStep, deleteActionStep } from '../../service/QuickActionsService.js'
import { showError, showSuccess } from '@nextcloud/dialogs'
export default {
	name: 'Settings',
	components: {
		NcListItem,
		NcModal,
		NcActionButton,
		NcButton,
		NcTextField,
		NcActions,
		IconDelete,
		IconEdit,
		Action,
		IconBolt,
		Container,
		Draggable,
		AlertOctagonIcon,
		TagIcon,
		OpenInNewIcon,
		ImportantIcon,
		EmailRead,
		EmailUnread,
		IconFavorite,
		PlusIcon,
	},
	props: {
		account: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			editModal: false,
			localAction: { id: null, name: '' },
			editMode: false,
			actions: [],
			highestOrder: 0,
		}
	},
	computed: {
		mainStore() {
			return useMainStore()
		},
		quickActions() {
			return this.mainStore.getQuickActions().filter(action => action.accountId === this.account.id)
		},
		modalName() {
			return this.editMode ? this.t('mail', 'Edit quick action') : this.t('mail', 'Add quick action')
		},
		canSave() {
			return this.actions.length > 0 && this.localAction.name.trim().length > 0 && this.actions.every(action => {
				if (action.name === 'moveThread' && (!action.mailboxId || action.mailboxId === null)) {
					return false
				}
				if (action.name === 'applyTag' && (!action.tagId || action.tagId === null)) {
					return false
				}
				return true
			})
		},
	},
	methods: {
		async deleteQuickAction(id) {
			await this.mainStore.deleteQuickAction(id).then(() => {
				showSuccess(t('mail', 'Quick action deleted'))
			}).catch(() => {
				showError(t('mail', 'Failed to delete quick action'))
			})
		},
		async openEditModal(action) {
			if (!action) {
				this.editMode = false
				this.localAction = { id: null, name: '' }
				this.actions = []
			} else {
				this.localAction = { ...action }
				this.actions = await findAllStepsForAction(action.id)
				this.highestOrder = Math.max(...this.actions.map(a => a.order), 0)
				this.editMode = true
			}
			this.editModal = true
		},
		closeEditModal() {
			this.editModal = false
			this.localAction = { id: null, name: '' }
			this.actions = []
			this.highestOrder = 0
		},
		async saveQuickAction() {
			if (this.editMode) {
				let quickAction
				try {
					quickAction = await this.mainStore.patchQuickAction(this.localAction.id, this.localAction.name)
				} catch (e) {
					showError(t('mail', 'Failed to update quick action'))
					return
				}
				for (const [index, action] of this.actions.entries()) {
					if (action?.id !== null && action?.id !== undefined) {
						await updateActionStep(action.id, action.name, action.order, action?.tagId, action?.mailboxId).catch(() => {
							showError(t('mail', 'Failed to update step in quick action'))
						})
					} else {
						const createdStep = await createActionStep(action.name, action.order, quickAction.id, action?.tagId, action?.mailboxId)
						if (createdStep) {
							this.actions[index] = createdStep
						}
					}
				}
			} else {
				let quickAction
				try {
					quickAction = await this.mainStore.createQuickAction(this.localAction.name, this.account.id)
				} catch (e) {
					showError(t('mail', 'Failed to create quick action'))
					return
				}
				try {
					for (const action of this.actions) {
						await createActionStep(action.name, action.order, quickAction.id, action?.tagId, action?.mailboxId)
					}
				} catch (e) {
					showError(t('mail', 'Failed to add steps to quick action'))
					this.closeEditModal()
				}
			}
			this.closeEditModal()
		},
		addQuickAction(name) {
			this.actions.push({ name, order: ++this.highestOrder })
		},
		updateAction({ id, type }, item) {
			const index = this.actions.findIndex((action) => action.order === item.order)
			if (index === -1) {
				return
			}
			const updated = { ...this.actions[index] }
			if (type === 'applyTag') {
				updated.tagId = id
			} else if (type === 'moveThread') {
				updated.mailboxId = id
			}
			this.actions.splice(index, 1, updated)
		},
		onDrop(e) {
			const { removedIndex, addedIndex } = e
			const movedItem = this.actions[removedIndex]
			this.actions.splice(removedIndex, 1)
			this.actions.splice(addedIndex, 0, movedItem)
			this.actions = this.actions.map((action, index) => ({ ...action, order: index + 1 }))
		},
		async deleteAction(item) {
			if (!this.editMode) {
				try {
					await deleteActionStep(item.id)
				} catch (e) {
					showError(t('mail', 'Failed to delete action step'))
					return
				}
			}
			this.actions = this.actions.filter(action => action.order !== item.order).map((action, index) => ({ ...action, order: index + 1 }))
			this.highestOrder = Math.max(...this.actions.map(a => a.order), 0)
		},
	},
}
</script>
<style scoped>
.modal-content{
	padding: 30px;
}

</style>
