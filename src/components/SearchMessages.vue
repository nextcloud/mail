<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="search-messages">
		<div class="search-messages__input">
			<input v-model="query"
				type="text"
				class="search-messages--input"
				:placeholder="t('mail', 'Search in folder')"
				:aria-label="t('mail', 'Search in folder')"
				@focus="showButtons = true"
				@blur="hideButtonsWithDelay">
			<NcButton type="tertiary"
				:aria-label="t('mail', 'Open search modal')"
				class="search-messages--filter"
				@click="moreSearchActions = true">
				<template #icon>
					<FilterVariantIcon :size="20" />
				</template>
			</NcButton>
			<NcButton v-if="filterChanged"
				:aria-label="t('mail', 'Close')"
				class="search-messages--close"
				@click="resetFilter()">
				<template #icon>
					<Close :size="24" />
				</template>
			</NcButton>

			<span v-if="filterChanged"
				class="filter-changed" />

			<NcDialog v-if="moreSearchActions"
				:name="t('mail', 'Search parameters')"
				size="normal"
				class="search-modal"
				:buttons="dialogButtons"
				@closing="closeSearchModal">
				<div class="modal-inner--content">
					<div class="modal-inner--field">
						<label class="modal-inner--label" for="subjectId">
							{{ t('mail','Subject') }}
						</label>
						<div class="modal-inner--container">
							<input id="subjectId"
								v-model="searchInSubject"
								type="text"
								class="search-input"
								:placeholder="t('mail', 'Search subject')">
						</div>
					</div>
					<div class="modal-inner--field">
						<label class="modal-inner--label" for="bodyId">
							{{ t('mail','Body') }}
						</label>
						<div class="modal-inner--container">
							<input id="bodyId"
								v-model="searchInMessageBody"
								type="text"
								class="search-input"
								:placeholder="t('mail', 'Search body')">
						</div>
					</div>
					<div class="modal-inner--field">
						<label class="modal-inner--label" for="fromId">
							{{ t('mail', 'Date') }}
						</label>
						<div class="modal-inner--container range">
							<div class="modal-inner-inline">
								<NcDateTimePickerNative v-model="startDate"
									type="date"
									:label="t('mail', 'Pick a start date')"
									confirm />
							</div>
							<div class="modal-inner-inline">
								<NcDateTimePickerNative v-model="endDate"
									type="date"
									:disabled="startDate === null"
									:label="t('mail', 'Pick an end date')"
									confirm />
							</div>
						</div>
					</div>
					<div class="modal-inner--field">
						<label class="modal-inner--label" for="fromId">
							{{ t('mail', 'From') }}
						</label>
						<div class="modal-inner--container">
							<NcSelect id="fromId"
								class="modal-inner--container__select"
								label="label"
								track-by="email"
								:options="autocompleteRecipients"
								:value="searchInFrom"
								:placeholder="t('mail', 'Select senders')"
								:aria-label-combobox="t('mail', 'Select senders')"
								:multiple="true"
								:taggable="true"
								:close-on-select="true"
								:show-no-options="false"
								:preserve-search="true"
								:max="1"
								@option:selecting="addTag($event,'from')"
								@option:deselecting="removeTag($event,'from')"
								@search="searchRecipients($event)" />
						</div>
					</div>

					<div class="modal-inner--field">
						<label class="modal-inner--label" for="toId">
							{{ t('mail', 'To') }}
						</label>
						<div class="modal-inner--container">
							<NcSelect id="toId"
								class="modal-inner--container__select"
								label="label"
								track-by="email"
								:options="autocompleteRecipients"
								:value="searchInTo"
								:placeholder="t('mail', 'Select recipients')"
								:aria-label-combobox="t('mail', 'Select recipients')"
								:multiple="true"
								:taggable="true"
								:close-on-select="true"
								:show-no-options="false"
								:preserve-search="true"
								@option:selecting="addTag($event,'to')"
								@option:deselecting="removeTag($event,'to')"
								@search="searchRecipients($event)" />
						</div>
					</div>

					<div class="modal-inner--field">
						<label class="modal-inner--label" for="ccId">
							{{ t('mail', 'Cc') }}
						</label>
						<div class="modal-inner--container">
							<NcSelect id="ccId"
								class="modal-inner--container__select"
								label="label"
								track-by="email"
								:options="autocompleteRecipients"
								:value="searchInCc"
								:placeholder="t('mail', 'Select CC recipients')"
								:aria-label-combobox="t('mail', 'Select CC recipients')"
								:multiple="true"
								:taggable="true"
								:close-on-select="true"
								:show-no-options="false"
								:preserve-search="true"
								@option:selecting="addTag($event,'cc')"
								@option:deselecting="removeTag($event,'cc')"
								@search="searchRecipients($event)" />
						</div>
					</div>

					<div class="modal-inner--field">
						<label class="modal-inner--label" for="bccId">
							{{ t('mail', 'Bcc') }}
						</label>
						<div class="modal-inner--container">
							<NcSelect id="bccId"
								class="modal-inner--container__select"
								label="label"
								track-by="email"
								:options="autocompleteRecipients"
								:value="searchInBcc"
								:placeholder="t('mail', 'Select BCC recipients')"
								:aria-label-combobox="t('mail', 'Select BCC recipients')"
								:multiple="true"
								:taggable="true"
								:close-on-select="true"
								:show-no-options="false"
								:preserve-search="true"
								@option:selecting="addTag($event,'bcc')"
								@option:deselecting="removeTag($event,'bcc')"
								@search="searchRecipients($event)" />
						</div>
					</div>

					<div v-if="tags.length > 0" class="modal-inner--field">
						<label for="tagsId">
							{{ t('mail', 'Tags') }}
						</label>
						<div class="modal-inner--container">
							<NcSelect v-if="tags.length > 0"
								id="tagsId"
								v-model="selectedTags"
								class="multiselect-search-tags "
								:options="tags"
								label="displayName"
								:value="selectedTags"
								:placeholder="t('mail', 'Select tags')"
								:aria-label-combobox="t('mail', 'Select tags')"
								track-by="displayName"
								:multiple="true"
								:auto-limit="false"
								:close-on-select="false">
								<template #selected-option="option">
									<div class="tag-group__search">
										<div class="tag-group__bg"
											:style="
												'background-color:' +
													(option.color !== '#fff'
														? option.color
														: '#333')" />
										<div class="tag-group__label"
											:style="'color:' + option.color">
											{{ option.displayName }}
										</div>
									</div>
								</template>
								<template #option="option">
									{{ option.displayName }}
								</template>
							</NcSelect>
						</div>
					</div>

					<div class="modal-inner--field">
						<label class="modal-inner--label" for="fromId">
							{{ t('mail', 'Marked as') }}
						</label>
						<div class="modal-inner--container marked-as">
							<div class="modal-inner-inline">
								<NcCheckboxRadioSwitch :checked.sync="searchFlags"
									value="is_important"
									name="flags[]"
									type="checkbox">
									{{ t('mail', 'Important') }}
								</NcCheckboxRadioSwitch>
							</div>
							<div class="modal-inner-inline">
								<NcCheckboxRadioSwitch :checked.sync="searchFlags"
									value="starred"
									name="flags[]"
									type="checkbox">
									{{ t('mail', 'Favorite') }}
								</NcCheckboxRadioSwitch>
							</div>
							<div class="modal-inner-inline">
								<NcCheckboxRadioSwitch :checked.sync="searchFlags"
									value="unread"
									name="flags[]"
									type="checkbox">
									{{ t('mail', 'Unread') }}
								</NcCheckboxRadioSwitch>
							</div>
							<div class="modal-inner-inline">
								<NcCheckboxRadioSwitch :checked.sync="searchFlags"
									value="attachments"
									name="flags[]"
									type="checkbox">
									{{ t('mail', 'Has attachments') }}
								</NcCheckboxRadioSwitch>
							</div>
							<div class="modal-inner-inline">
								<NcCheckboxRadioSwitch :checked.sync="mentionsMe">
									{{ t('mail', 'Mentions me') }}
								</NcCheckboxRadioSwitch>
							</div>
						</div>
					</div>
				</div>
			</NcDialog>
		</div>
		<!-- Filter buttons -->
		<div v-if="showButtons" class="filter-buttons">
			<NcButton type="secondary"
				class="shortcut"
				:aria-label="t('mail', 'Has attachment')"
				:title="t('mail', 'Has attachment')"
				:pressed="hasAttachmentActive"
				@update:pressed="hasAttachmentActive = !hasAttachmentActive"
				@click="toggleGetAttachments">
				{{ t('mail', 'Has attachment') }}
			</NcButton>
			<NcButton type="secondary"
				class="shortcut"
				:pressed="hasLast7daysActive"
				:aria-label="t('mail', 'Last 7 days')"
				:title="t('mail', 'Last 7 days')"
				@update:pressed="hasLast7daysActive = !hasLast7daysActive"
				@click="toggleLastWeekFilter">
				{{ t('mail', 'Last 7 days') }}
			</NcButton>
			<NcButton type="secondary"
				class="shortcut"
				:pressed="hasFromMeActive"
				:aria-label="t('mail', 'From me')"
				:title="t('mail', 'From me')"
				@update:pressed="hasFromMeActive = !hasFromMeActive"
				@click="toggleCurrentUser">
				{{ t('mail', 'From me') }}
			</NcButton>
		</div>
	</div>
</template>

<script>
import moment from '@nextcloud/moment'

import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcDateTimePickerNative from '@nextcloud/vue/components/NcDateTimePickerNative'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch
	from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import FilterVariantIcon from 'vue-material-design-icons/FilterVariant.vue'
import Close from 'vue-material-design-icons/Close.vue'
import IconClose from '@mdi/svg/svg/close.svg'
import IconMagnify from '@mdi/svg/svg/magnify.svg'
import { translate as t } from '@nextcloud/l10n'

import debouncePromise from 'debounce-promise'
import { findRecipient } from '../service/AutocompleteService.js'
import uniqBy from 'lodash/fp/uniqBy.js'
import { hiddenTags } from './tags.js'
import { mapStores } from 'pinia'
import useMainStore from '../store/mainStore.js'

const debouncedSearch = debouncePromise(findRecipient, 500)

export default {
	name: 'SearchMessages',
	components: {
		NcDialog,
		NcSelect,
		NcDateTimePickerNative,
		NcButton,
		NcCheckboxRadioSwitch,
		FilterVariantIcon,
		Close,
	},
	props: {
		mailbox: {
			type: Object,
			required: true,
		},
		accountId: {
			type: Number,
			required: true,
		},
	},
	data() {
		return {
			showButtons: false,
			match: 'allof',
			query: '',
			debouncedSearchQuery: debouncePromise(this.sendQueryEvent, 700),
			autocompleteRecipients: [],
			selectedTags: [],
			moreSearchActions: false,
			searchInFrom: [],
			searchInTo: [],
			searchInCc: [],
			searchInBcc: [],
			searchInSubject: null,
			searchInMessageBody: null,
			searchFlags: [],
			mentionsMe: false,
			hasAttachmentActive: false,
			hasLast7daysActive: false,
			hasFromMeActive: false,
			startDate: null,
			endDate: null,
			dialogButtons: [
				{
					label: t('mail', 'Clear'),
					callback: () => this.resetFilter(),
					type: 'secondary',
					icon: IconClose,
				},
				{
					label: t('mail', 'Search'),
					callback: () => this.closeSearchModal(),
					type: 'primary',
					icon: IconMagnify,
				},
			],
		}
	},
	computed: {
		...mapStores(useMainStore),
		tags() {
			return this.mainStore.getTags.filter((tag) => !(tag.displayName.toLowerCase() in hiddenTags)).sort((a, b) => {
				if (a.isDefaultTag && !b.isDefaultTag) {
					return -1
				}
				if (b.isDefaultTag && !a.isDefaultTag) {
					return 1
				}
				if (a.isDefaultTag && b.isDefaultTag) {
					if (a.displayName < b.displayName) {
						return 1
					}
					return -1
				}
				return a.displayName.localeCompare(b.displayName)
			})
		},
		filterChanged() {
			return Object.entries(this.filterData).filter(([key, val]) => {
				return val !== '' && val !== null && val.length > 0
			}).length > 0
		},
		searchBody() {
			return this.mainStore.getAccount(this.accountId)?.searchBody || (this.mailbox.databaseId === 'priority' && this.mainStore.getPreference('search-priority-body', 'false') === 'true')
		},
		account() {
			return this.mainStore.getAccount(this.accountId)
		},
		filterData() {
			return {
				to: this.searchInTo.length > 0 ? this.searchInTo.map(address => address.email) : null,
				from: this.searchInFrom.length > 0 ? this.searchInFrom.map(address => address.email) : null,
				cc: this.searchInCc.length > 0 ? this.searchInCc.map(address => address.email) : null,
				bcc: this.searchInBcc.length > 0 ? this.searchInBcc.map(address => address.email) : null,
				subject: this.searchInSubject !== null && this.searchInSubject.length > 1 ? this.searchInSubject : '',
				body: this.searchInMessageBody !== null && this.searchInMessageBody.length > 1 ? this.searchInMessageBody : '',
				tags: this.selectedTags.length > 0 ? this.selectedTags.map(item => item.id) : '',
				flags: this.searchFlags.length > 0 ? this.searchFlags.map(item => item) : '',
				mentions: this.mentionsMe,
				start: this.prepareStart(),
				end: this.prepareEnd(),
			}
		},
		searchQuery() {
			let _search = ''
			Object.entries(this.filterData).filter(([key, val]) => {
				if (['to', 'from', 'cc', 'bcc'].includes(key)) {
					val?.forEach((address) => {
						_search += `${key}:${encodeURI(address)} `
					})
				} else if (key === 'body') {
					val.split(' ').forEach((word) => {
						if (word !== '' && val !== null) {
							_search += `${key}:${encodeURI(word)} `
						}
					})
				} else if (val !== '' && val !== null) {
					_search += `${key}:${encodeURI(val)} `
				}
				return val
			})
			_search += `match:${encodeURI(this.match)} `

			return _search.trim()
		},
	},
	watch: {
		query() {
			if (this.query.length === 0) {
				return
			}

			this.match = 'anyof'
			this.searchInMessageBody = this.searchBody ? this.query : null
			this.searchInSubject = this.query
			this.searchInFrom = [{ email: this.query, label: this.query }]
			this.searchInTo = [{ email: this.query, label: this.query }]
			this.debouncedSearchQuery()
		},
	},
	methods: {
		hideButtonsWithDelay() {
			setTimeout(() => {
				this.showButtons = false
			}, 100)
		},
		toggleGetAttachments() {
			if (this.hasAttachmentActive) {
				this.searchFlags.push('attachments')
			} else {
				this.searchFlags = this.searchFlags.filter((flag) => flag !== 'attachments')
			}
			this.$nextTick(() => {
				this.sendQueryEvent()
			})
		},
		toggleCurrentUser() {
			if (this.hasFromMeActive) {
				this.searchInFrom = [{
					email: this.account.emailAddress,
					label: this.account.emailAddress,
				}]
			} else {
				this.searchInFrom = null
			}
			this.$nextTick(() => {
				this.sendQueryEvent()
			})
		},
		toggleLastWeekFilter() {
			if (this.hasLast7daysActive) {
				const endDate = new Date()
				const startDate = new Date()
				startDate.setDate(startDate.getDate() - 7)

				this.startDate = startDate
				this.endDate = endDate
			} else {
				this.startDate = null
				this.endDate = null
			}
			this.$nextTick(() => {
				this.sendQueryEvent()
			})
		},
		prepareStart() {
			if (this.startDate !== null) {
				if (this.endDate !== null && this.startDate > this.endDate) {
					this.endDate = this.startDate
				}
				return moment(this.startDate).unix().toString()
			}
			return ''
		},
		prepareEnd() {
			return this.endDate !== null ? moment(this.endDate).add(1, 'days').unix().toString() : ''
		},
		closeSearchModal() {
			this.moreSearchActions = false
			this.match = 'allof'
			this.$nextTick(() => {
				this.sendQueryEvent()
			})
		},
		sendQueryEvent() {
			this.$emit('search-changed', this.searchQuery)
		},
		searchRecipients(term) {
			if (term === undefined || term === '') {
				return
			}
			debouncedSearch(term).then(results => {
				this.autocompleteRecipients = uniqBy('email')(
					this.autocompleteRecipients.concat(results),
				)
			})
		},
		resetFilter() {
			this.match = 'allof'
			this.query = ''
			this.selectedTags = []
			this.moreSearchActions = false
			this.searchInFrom = []
			this.searchInTo = []
			this.searchInCc = []
			this.searchInBcc = []
			this.searchInSubject = null
			this.searchInMessageBody = null
			this.searchFlags = []
			this.startDate = null
			this.endDate = null
			this.mentionsMe = false
			this.sendQueryEvent()
		},
		addTag(tag, type) {
			if (typeof tag === 'string') {
				tag = { email: tag, label: tag }
			}
			switch (type) {
			case 'to':
				this.searchInTo.push(tag)
				break
			case 'from':
				this.searchInFrom.push(tag)
				break
			case 'cc':
				this.searchInCc.push(tag)
				break
			case 'bcc':
				this.searchInBcc.push(tag)
				break
			}
		},
		removeTag(tag, type) {
			switch (type) {
			case 'to':
				this.searchInTo = this.removeAddress(tag, this.searchInTo)
				break
			case 'from':
				this.searchInFrom = this.removeAddress(tag, this.searchInFrom)
				break
			case 'cc':
				this.searchInCc = this.removeAddress(tag, this.searchInCc)
				break
			case 'bcc':
				this.searchInBcc = this.removeAddress(tag, this.searchInBcc)
				break
			}
		},
		removeAddress(tag, addresses) {
			return addresses.filter((address) => address.email !== tag.email)
		},
	},
}
</script>

<style lang="scss">
.search-messages {
	border-bottom: 1px solid var(--color-border);
	position: sticky;
	top: 0;
	z-index: 10;
	height: 52px;
	background-color: var(--color-main-background);
	&__input {
		min-height: 52px;
		margin-inline-start: calc(var(--app-navigation-padding) * 2 + var(--default-clickable-area));
		padding-inline-end: 3px; /* matches .app-content-list */
		position: relative;
		display: flex;
		align-items: center;
		//important info icon overlaps it while scrolling
		z-index: 1;

		input {
			flex-grow: 1;
		}

		.action-item--single {
			border: none;
			background: none;
			transition: 0.4s;
		}

		.action-item--single:hover {
			transition: 0.4s;
			background: var(--color-primary-element);
		}
	}
}

.search-input {
	width: 100%;
}

.checkbox-radio-switch__label {
	background: none !important;
	padding: 0 !important;
	margin: 0 !important;
}

.tag-group__search {
	box-sizing: border-box;
	position: relative;
	margin: 3px 3px;
	padding: 0 6px;
}

.tag-group__bg {
	position: absolute;
	inset-inline: 0;
	bottom: 0;
	top: 0;
	opacity: 0.4;
	border-radius: 14px;
	z-index: 1;
}

.tag-group__label {
	font-weight: bold;
	font-size: 12px;
	position: relative;
	z-index: 2;
}

.search-modal {
	.modal-inner--content {
		padding: 16px 0 36px 0;
		overflow-y: scroll;
		width: calc(100% - 2px);

		.marked-as .modal-inner-inline {
			display: inline-block;
			width: 32%;

		}
		.range {
			display: flex;
			flex-wrap: wrap;

			.modal-inner-inline {
				width: calc(50% - 5px);
				&:first-child {
					margin-inline-end: 5px;
				}
				&:last-child {
					margin-inline-start: 5px;
				}
			}
		}
	}
}
@media (max-width: 420px) {
	.modal-inner--container {
		width: 100%;
		flex-wrap: nowrap;
		flex-direction: column;
	}
}
@media (max-width: 420px) {
	.search-modal .modal-inner--content .range {
		flex-direction: row;
	}
}

.multiselect-search-tags {
	width: 100%;
}

.multiselect-search-tags .multiselect__tags .multiselect__tags-wrap {
	flex-wrap: wrap !important;
}

.modal-inner-field--right {
	display: flex;
	align-items: center;
	justify-content: flex-end;
	padding: 0 33px;
	margin-top: 15px;
}

.modal-inner--field {
	display: flex;
	align-items: center;
	flex-wrap: wrap;
	justify-content: space-between;
	margin-bottom: 15px;
	padding: 0 12px 0 30px;

	.checkbox-radio-switch {
		margin: 0 8px 0 0;
	}

	& > label {
		font-weight: bold;
		width: 120px;
	}

	.modal-inner--container {
		width: calc(100% - 120px);
		display: flex;
		flex-wrap: wrap;

		.select {
			width: 100%;
		}
	}
}

.modal-wrapper--normal .modal-container {
	position: relative
}

.button-vue.search-messages--filter.button-vue--icon-only {
	position: absolute;
	width: auto;
	height: auto;
	z-index: 5;
	inset-inline-end: 7px; /* same spacing to the input border as top/bottom */
	inset-inline-start: auto;
	box-shadow: none !important;
	background: transparent !important;
	border: none !important;
	padding: 0 !important;
}

.button-vue.search-messages--close.button-vue--icon-only {
	position: absolute;
	width: auto;
	height: auto;
	z-index: 5;
	inset-inline-end: 35px;
	inset-inline-start: auto;
	box-shadow: none !important;
	background: transparent !important;
	border: none !important;
	padding: 0 !important;
}

.button-reset-filter {
	margin-inline-end: 10px;
}

.filter-changed {
	width: 6px;
	height: 6px;
	background: var(--color-error);
	position: absolute;
	z-index: 10;
	inset-inline-end: 12px;
	border-radius: 50%;
	top: 12px;
}

.mx-datepicker {
	width:100%;
}

.filter-buttons {
	display: flex;
	justify-content: center;
	align-items: center;
	flex-wrap: nowrap;
	gap: 4px;
	overflow: hidden;
	text-overflow: ellipsis;
	padding: 0 5px 5px 5px;
}
</style>
