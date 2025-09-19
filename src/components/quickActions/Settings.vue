<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="quick-actions-settings">
		<div v-if="quickActions.length === 0">
			{{ t('mail', 'No quick actions yet.') }}
		</div>
		<NcListItem v-for="action in quickActions" :key="action.id" :name="action?.name">
			<template #icon>
				<IconEmailFast :size="20" />
			</template>
			<template #actions>
				<NcActionButton :close-after-click="true" @click="openEditModal(action)">
					<template #icon>
						<IconEdit :size="20" />
					</template>
					{{ t('mail', 'Edit') }}
				</NcActionButton>
				<NcActionButton :close-after-click="true" variant="error" @click="deleteQuickAction(action.id)">
					<template #icon>
						<IconDelete :size="20" />
					</template>
					{{ t('mail', 'Delete') }}
				</NcActionButton>
			</template>
		</NcListItem>
		<NcButton class="add-quick-action" variant="primary" @click="openEditModal()">
			{{ t('mail', 'Add quick action') }}
		</NcButton>
		<NcModal v-if="editModal" :name="modalName" @close="closeEditModal">
			<h2 class="modal-name" v-text="modalName" />
			<div class="modal-content">
				<NcTextField :value.sync="localAction.name" :label="t('mail', 'Quick action name')" />
				<h3>{{ t('mail', 'Do the following actions') }}</h3>
				<Container @onDrop="onDrop">
					<Draggable v-for="item in actions"
						:key="item.id"
						class="modal-content__action"
						:drag-not-allowed="item.name === 'deleteThread' || item.name === 'moveThread'">
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
					<NcActionButton :close-after-click="true" @click="addQuickAction('markAsSpam')">
						<template #icon>
							<AlertOctagonIcon :size="20" />
						</template>
						{{ t('mail', 'Mark as spam') }}
					</NcActionButton>
					<NcActionButton :close-after-click="true" @click="addQuickAction('applyTag')">
						<template #icon>
							<TagIcon :size="20" />
						</template>
						{{ t('mail','Tag') }}
					</NcActionButton>
					<NcActionButton v-if="!deletionAndMovingDisabled" :close-after-click="true" @click="addQuickAction('moveThread')">
						<template #icon>
							<OpenInNewIcon :size="20" />
						</template>
						{{ t('mail', 'Move thread') }}
					</NcActionButton>
					<NcActionButton v-if="!deletionAndMovingDisabled" :close-after-click="true" @click="addQuickAction('deleteThread')">
						<template #icon>
							<IconDelete :size="20" />
						</template>
						{{ t('mail', 'Delete thread') }}
					</NcActionButton>
					<NcActionButton :close-after-click="true" @click="addQuickAction('markAsRead')">
						<template #icon>
							<EmailRead :size="20" />
						</template>
						{{ t('mail', 'Mark as read') }}
					</NcActionButton>
					<NcActionButton :close-after-click="true" @click="addQuickAction('markAsUnread')">
						<template #icon>
							<EmailUnread :size="20" />
						</template>
						{{ t('mail', 'Mark as unread') }}
					</NcActionButton>
					<NcActionButton :close-after-click="true" @click="addQuickAction('markAsImportant')">
						<template #icon>
							<ImportantIcon :size="20" />
						</template>
						{{ t('mail', 'Mark as important') }}
					</NcActionButton>
					<NcActionButton :close-after-click="true" @click="addQuickAction('markAsFavorite')">
						<template #icon>
							<IconFavorite :size="20" />
						</template>
						{{ t('mail', 'Mark as favorite') }}
					</NcActionButton>
				</NcActions>
				<NcButton :disabled="!canSave || loading"
					class="modal-content__save"
					variant="primary"
					@click="saveQuickAction">
					<template #icon>
						<NcLoadingIcon v-if="loading" :size="20" />
					</template>
					{{ t('mail', 'Save') }}
				</NcButton>
			</div>
		</NcModal>
	</div>
</template>

<script>
import { NcModal, NcListItem, NcActionButton, NcActions, NcButton, NcTextField, NcLoadingIcon } from '@nextcloud/vue'
import { Container, Draggable } from 'vue-dndrop'
import Action from './Action.vue'
import useMainStore from '../../store/mainStore.js'
import IconDelete from 'vue-material-design-icons/TrashCanOutline.vue'
import IconEdit from 'vue-material-design-icons/PencilOutline.vue'
import IconEmailFast from 'vue-material-design-icons/EmailFastOutline.vue'
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
import logger from '../../logger.js'

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
		IconEmailFast,
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
		NcLoadingIcon,
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
			loading: false,
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
		deletionAndMovingDisabled() {
			return this.actions.some(action => ['deleteThread', 'moveThread'].includes(action.name))
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
			}).catch((error) => {
				logger.error('Could not delete quick action', {
					error,
				})
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
			this.loading = false
			this.editModal = false
			this.localAction = { id: null, name: '' }
			this.actions = []
			this.highestOrder = 0
		},
		async saveQuickAction() {
			this.loading = true
			if (this.editMode) {
				let quickAction
				try {
					quickAction = await this.mainStore.patchQuickAction(this.localAction.id, this.localAction.name)
				} catch (error) {
					logger.error('Could not update action', {
						error,
					})
					showError(t('mail', 'Failed to update quick action'))
					return
				}
				for (const [index, action] of this.actions.entries()) {
					if (action?.id !== null && action?.id !== undefined) {
						await updateActionStep(action.id, action.name, action.order, action?.tagId, action?.mailboxId).catch((error) => {
							logger.error('Could not update quick action step', {
								error,
							})
							showError(t('mail', 'Failed to update step in quick action'))
						})
					} else {
						const createdStep = await createActionStep(action.name, action.order, quickAction.id, action?.tagId, action?.mailboxId)
						if (createdStep) {
							this.actions[index] = createdStep
						}
					}
				}
				showSuccess(t('mail', 'Quick action updated'))
			} else {
				let quickAction
				try {
					quickAction = await this.mainStore.createQuickAction(this.localAction.name, this.account.id)
				} catch (error) {
					logger.error('Could not create action', {
						error,
					})
					showError(t('mail', 'Failed to create quick action'))
					return
				}
				try {
					for (const action of this.actions) {
						await createActionStep(action.name, action.order, quickAction.id, action?.tagId, action?.mailboxId)
					}
				} catch (error) {
					logger.error('Could not add step to quick action', {
						error,
					})
					showError(t('mail', 'Failed to add steps to quick action'))
					this.closeEditModal()
				}
				showSuccess(t('mail', 'Quick action created'))
			}
			this.closeEditModal()
		},
		addQuickAction(name) {
			if (this.deletionAndMovingDisabled) {
				this.actions[this.actions.length - 1].order = ++this.highestOrder
				this.actions.push({ name, order: this.highestOrder - 1 })
			} else {
				this.actions.push({ name, order: ++this.highestOrder })
			}
			this.actions.sort((a, b) => a.order - b.order)
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
			if (this.deletionAndMovingDisabled && addedIndex === this.actions.length - 1) {
				return
			}
			const movedItem = this.actions[removedIndex]
			this.actions.splice(removedIndex, 1)
			this.actions.splice(addedIndex, 0, movedItem)
			this.actions = this.actions.map((action, index) => ({ ...action, order: index + 1 }))
		},
		async deleteAction(item) {
			if (this.editMode) {
				try {
					await deleteActionStep(item.id)
				} catch (error) {
					logger.error('Could not delete action step', {
						error,
					})
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
<style lang="scss" scoped>

.modal-content{
	padding: 30px;
	&__action{
		padding: 9px;
	}
	&__save {
		position: absolute;
		bottom: 10px;
		inset-inline-end: 10px;
	}
}

:deep(.v-select){
	display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-name{
	text-align: center;
}
@media only screen and (max-width: 512px) {
	// Ensure the modal name does not interfere with the close button
	.modal-name {
		text-align: start;
		margin-inline-end: var(--default-clickable-area);
	}
}
</style>
