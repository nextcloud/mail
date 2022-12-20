<template>
	<div class="search-messages">
		<input
			v-model="query"
			type="text"
			class="search-messages--input"
			:placeholder="t('mail', 'Search in mailbox')">
		<NcButton v-if="filterChanged"
			class="search-messages--close"
			@click="resetFilter()">
			<template #icon>
				<Close :size="24" />
			</template>
		</NcButton>

		<span
			v-if="filterChanged"
			class="filter-changed" />

		<NcActions>
			<NcActionButton @click="moreSearchActions = true">
				<template #icon>
					<Tune :size="24" />
				</template>
				{{ t("mail", "Search parameters") }}
			</NcActionButton>
		</NcActions>
		<NcModal
			v-if="moreSearchActions"
			:title="t('mail', 'Search parameters')"
			class="search-modal"
			@close="closeSearchModal">
			<h2 class="modal-title">
				{{ t('mail', 'Search in mailbox') }}
			</h2>
			<div class="modal-inner--content">
				<div class="modal-inner--field">
					<label class="modal-inner--label" for="fromId">
						{{ t('mail','Search term') }}
					</label>
					<div class="modal-inner--container">
						<input
							v-model="modalQuery"
							type="text"
							class="search-input"
							:placeholder="t('mail', 'Search in mailbox')">
					</div>
				</div>
				<div class="modal-inner--field">
					<label class="modal-inner--label" for="fromId">
						{{ t("mail", "Date") }}
					</label>
					<div class="modal-inner--container range">
						<div class="modal-inner-inline">
							<NcDatetimePicker
								v-model="startDate"
								type="date"
								:placeholder="t('mail', 'Pick a start date')"
								confirm />
						</div>
						<div class="modal-inner-inline">
							<NcDatetimePicker
								v-model="endDate"
								type="date"
								:disabled="startDate === null"
								:placeholder="t('mail', 'Pick an end date')"
								confirm />
						</div>
					</div>
				</div>
				<div class="modal-inner--field">
					<label class="modal-inner--label" for="fromId">
						{{ t("mail", "From") }}
					</label>
					<div class="modal-inner--container">
						<NcMultiselect
							id="fromId"
							v-model="searchInFrom"
							label="label"
							track-by="email"
							:options="autocompleteRecipients"
							:value="searchInFrom"
							:placeholder="t('mail', 'Select sender')"
							:multiple="true"
							:taggable="true"
							:close-on-select="true"
							:show-no-options="false"
							:preserve-search="true"
							:max="1"
							@tag="addTag($event,'from')"
							@search-change="searchRecipients($event)" />
					</div>
				</div>

				<div class="modal-inner--field">
					<label class="modal-inner--label" for="toId">
						{{ t('mail', 'To') }}
					</label>
					<div class="modal-inner--container">
						<NcMultiselect
							id="toId"
							v-model="searchInTo"
							label="label"
							track-by="email"
							:options="autocompleteRecipients"
							:value="searchInTo"
							:placeholder="t('mail', 'Select recipient')"
							:multiple="true"
							:taggable="true"
							:close-on-select="true"
							:show-no-options="false"
							:preserve-search="true"
							:max="1"
							@tag="addTag($event,'to')"
							@search-change="searchRecipients($event)" />
					</div>
				</div>

				<div class="modal-inner--field">
					<label class="modal-inner--label" for="ccId">
						{{ t('mail', 'Cc') }}
					</label>
					<div class="modal-inner--container">
						<NcMultiselect
							id="ccId"
							v-model="searchInCc"
							label="label"
							track-by="email"
							:options="autocompleteRecipients"
							:value="searchInCc"
							:placeholder="t('mail', 'Select cc recipient')"
							:multiple="true"
							:taggable="true"
							:close-on-select="true"
							:show-no-options="false"
							:preserve-search="true"
							:max="1"
							@tag="addTag($event,'cc')"
							@search-change="searchRecipients($event)" />
					</div>
				</div>

				<div class="modal-inner--field">
					<label class="modal-inner--label" for="bccId">
						{{ t('mail', 'Bcc') }}
					</label>
					<div class="modal-inner--container">
						<NcMultiselect
							id="bccId"
							v-model="searchInBcc"
							label="label"
							track-by="email"
							:options="autocompleteRecipients"
							:value="searchInCc"
							:placeholder="t('mail', 'Select bcc recipient')"
							:multiple="true"
							:taggable="true"
							:close-on-select="true"
							:show-no-options="false"
							:preserve-search="true"
							:max="1"
							@tag="addTag($event,'bcc')"
							@search-change="searchRecipients($event)" />
					</div>
				</div>

				<div v-if="tags.length > 0" class="modal-inner--field">
					<label for="tagsId">
						{{ t('mail', 'Tags') }}
					</label>
					<div class="modal-inner--container">
						<NcMultiselect
							v-if="tags.length > 0"
							id="tagsId"
							v-model="selectedTags"
							class="multiselect-search-tags"
							:options="tags"
							label="displayName"
							:value="selectedTags"
							:placeholder="t('mail', 'Select tags')"
							track-by="displayName"
							:multiple="true"
							:auto-limit="false"
							:close-on-select="false">
							<template #tag="{ option }">
								<div class="tag-group__search">
									<div
										class="tag-group__bg"
										:style="
											'background-color:' +
												(option.color !== '#fff'
													? option.color
													: '#333')" />
									<div
										class="tag-group__label"
										:style="'color:' + option.color">
										{{ option.displayName }}
									</div>
								</div>
							</template>
							<template #option="{ option }">
								{{ option.displayName }}
							</template>
						</NcMultiselect>
					</div>
				</div>

				<div class="modal-inner--field">
					<label class="modal-inner--label" for="fromId">
						{{ t('mail', 'Marked as') }}
					</label>
					<div class="modal-inner--container marked-as">
						<div class="modal-inner-inline">
							<NcCheckboxRadioSwitch
								:checked.sync="searchFlags"
								value="is_important"
								name="flags[]"
								type="checkbox">
								{{ t('mail', 'Important') }}
							</NcCheckboxRadioSwitch>
						</div>
						<div class="modal-inner-inline">
							<NcCheckboxRadioSwitch
								:checked.sync="searchFlags"
								value="starred"
								name="flags[]"
								type="checkbox">
								{{ t('mail', 'Favorite') }}
							</NcCheckboxRadioSwitch>
						</div>
						<div class="modal-inner-inline">
							<NcCheckboxRadioSwitch
								:checked.sync="searchFlags"
								value="unread"
								name="flags[]"
								type="checkbox">
								{{ t('mail', 'Unread') }}
							</NcCheckboxRadioSwitch>
						</div>
						<div class="modal-inner-inline">
							<NcCheckboxRadioSwitch
								:checked.sync="searchFlags"
								value="attachments"
								name="flags[]"
								type="checkbox">
								{{ t('mail', 'Has attachments') }}
							</NcCheckboxRadioSwitch>
						</div>
					</div>
				</div>

				<div class="modal-inner-field--right">
					<NcButton
						class="button-reset-filter"
						@click="resetFilter()">
						<template #icon>
							<Close :size="24" />
						</template>
						{{ t('mail', 'Clear') }}
					</NcButton>
					<NcButton
						type="primary"
						@click="closeSearchModal()">
						<template #icon>
							<Magnify :size="24" />
						</template>
						{{ t('mail', 'Search') }}
					</NcButton>
				</div>
			</div>
		</NcModal>
	</div>
</template>

<script>
import moment from '@nextcloud/moment'

import NcModal from '@nextcloud/vue/dist/Components/NcModal'
import NcMultiselect from '@nextcloud/vue/dist/Components/NcMultiselect'
import NcDatetimePicker from '@nextcloud/vue/dist/Components/NcDatetimePicker'
import NcActions from '@nextcloud/vue/dist/Components/NcActions'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton'
import NcButton from '@nextcloud/vue/dist/Components/NcButton'
import NcCheckboxRadioSwitch
	from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch'
import Tune from 'vue-material-design-icons/Tune'
import Close from 'vue-material-design-icons/Close'
import Magnify from 'vue-material-design-icons/Magnify'

import debouncePromise from 'debounce-promise'
import { findRecipient } from '../service/AutocompleteService'
import uniqBy from 'lodash/fp/uniqBy'

const debouncedSearch = debouncePromise(findRecipient, 500)

export default {
	name: 'SearchMessages',
	components: {
		NcModal,
		NcMultiselect,
		NcDatetimePicker,
		NcActions,
		NcActionButton,
		NcButton,
		NcCheckboxRadioSwitch,
		Tune,
		Close,
		Magnify,
	},
	props: {
		mailbox: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			modalQuery: '',
			query: '',
			debouncedSearchQuery: debouncePromise(this.sendQueryEvent, 700),
			autocompleteRecipients: [],
			selectedTags: [],
			moreSearchActions: false,
			searchInFrom: null,
			searchInTo: null,
			searchInCc: null,
			searchInBcc: null,
			searchInSubject: true,
			searchInMessageBody: false,
			searchFlags: [],
			hasAttachments: false,
			startDate: null,
			endDate: null,
		}
	},
	computed: {
		tags() {
			return this.$store.getters.getTags
		},
		filterChanged() {
			return Object.entries(this.filterData).filter(([key, val]) => {
				return val !== ''
			}).length > 0
		},
		filterData() {
			return {
				to: this.searchInTo !== null && this.searchInTo.length > 0 ? this.searchInTo[0].email : '',
				from: this.searchInFrom !== null && this.searchInFrom.length > 0 ? this.searchInFrom[0].email : '',
				cc: this.searchInCc !== null && this.searchInCc.length > 0 ? this.searchInCc[0].email : '',
				bcc: this.searchInBcc !== null && this.searchInBcc.length > 0 ? this.searchInBcc[0].email : '',
				subject: this.searchInSubject && this.query.length > 1 ? this.query : '',
				tags: this.selectedTags.length > 0 ? this.selectedTags.map(item => item.id) : '',
				flags: this.searchFlags.length > 0 ? this.searchFlags.map(item => item) : '',
				start: this.prepareStart(),
				end: this.prepareEnd(),
				attachments: this.hasAttachments ? this.hasAttachments.toString() : '',
			}
		},
		searchQuery() {
			let _search = ''
			Object.entries(this.filterData).filter(([key, val]) => {
				if (val !== '' && val !== null) {
					if (val.indexOf(' ') !== -1) {
						val = val.replace(/ /g, '+')
					}
					_search += `${key}:${val} `
				}
				return val
			})

			return _search.trim()
		},
	},
	watch: {
		query() {
			if (this.query !== this.modalQuery) {
				this.modalQuery = this.query
			}
			this.debouncedSearchQuery()
		},
	},
	methods: {
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
			this.$nextTick(() => {
				if (this.query !== this.modalQuery) {
					this.query = this.modalQuery
				} else {
					this.sendQueryEvent()
				}
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
					this.autocompleteRecipients.concat(results)
				)
			})
		},
		resetFilter() {
			const prevQuery = this.query
			this.query = ''
			this.selectedTags = []
			this.moreSearchActions = false
			this.searchInFrom = null
			this.searchInTo = null
			this.searchInCc = null
			this.searchInBcc = null
			this.searchInSubject = true
			this.searchInMessageBody = false
			this.searchFlags = []
			this.startDate = null
			this.endDate = null
			// Need if there is only tag filter or recipients filter
			if (prevQuery === '') {
				this.sendQueryEvent()
			}
		},
		addTag(tag, type) {
			const _tag = [{
				label: tag,
				email: tag,
			}]
			switch (type) {
			case 'to':
				this.searchInTo = _tag
				break
			case 'from':
				this.searchInFrom = _tag
				break
			case 'cc':
				this.searchInCc = _tag
				break
			case 'bcc':
				this.searchInBcc = _tag
				break
			}
		},
	},
}
</script>

<style lang="scss">
.search-messages {
	min-height: 52px;
	margin: 3px 0 0 52px;
	border-right: 1px solid var(--color-border);
	position: relative;
	display: flex;
	align-items: center;
	//important info icon overlaps it while scrolling
	z-index: 1;

	input {
		width: calc(100% - 45px);
		margin: 4px 6px;
	}

	.action-item--single {
		position: absolute;
		top: 3px;
		right: -2px;
		border-radius: var(--border-radius-pill);
		border: none;
		background: none;
		transition: 0.4s;
	}

	.action-item--single:hover {
		transition: 0.4s;
		background: var(--color-primary-element);
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
	left: 0;
	right: 0;
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

.search-modal .modal-container {
	min-height: auto;
	overflow: visible !important;
	position: relative;
	display: flex !important;
	flex-direction: column;

	.modal-title {
		padding: 16px 30px 0 30px;
		position: relative;
		/* needs while modal-container__close not set */
		display: inline-block;
	}

	.modal-inner--content {
		padding: 16px 0 36px 0;
		overflow-y: scroll;
		width: calc(100% - 2px);

		.marked-as .modal-inner-inline {
			display: inline-block;
			width: 32%;

			&:last-child {
				width: 100%;
			}
		}
		.range {
			display: flex;
			flex-wrap: wrap;

			.modal-inner-inline {
				width: calc(50% - 5px);
				&:first-child {
					margin-right: 5px;
				}
				&:last-child {
					margin-left: 5px;
				}
			}
		}
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

		.multiselect {
			width: 100%;
		}
	}
}

.modal-wrapper--normal .modal-container {
	position: relative
}

.button-vue.search-messages--close.button-vue--icon-only {
	position: absolute;
	width: auto;
	height: auto;
	z-index: 5;
	right: 45px;
	box-shadow: none !important;
	background: transparent !important;
	border: none !important;
	padding: 0 !important;
	top: 5px
}

.button-reset-filter {
	margin-right: 10px;
}

.filter-changed {
	width: 6px;
	height: 6px;
	background: var(--color-error);
	position: absolute;
	z-index: 10;
	right: 9px;
	border-radius: 50%;
	top: 10px;
}
.mx-datepicker {
	width:100%;
}
</style>
