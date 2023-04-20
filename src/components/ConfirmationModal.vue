<!--
  - @copyright 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
	<NcModal class="confirm-modal" @close="cancel">
		<div class="confirm-modal">
			<h2>{{ title }}</h2>
			<slot />
			<div class="confirm-modal__buttons">
				<NcButton type="tertiary" @click="cancel">
					{{ t('mail', 'Cancel') }}
				</NcButton>
				<NcButton :href="confirmUrl"
					:rel="confirmUrl ? 'noopener noreferrer' : false"
					:target="confirmUrl ? '_blank' : false"
					type="primary"
					@click="confirm">
					{{ confirmText }}
				</NcButton>
			</div>
		</div>
	</NcModal>
</template>

<script>

import { NcButton, NcModal } from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'ConfirmationModal',
	components: {
		NcButton,
		NcModal,
	},
	props: {
		title: {
			type: String,
			required: true,
		},
		confirmText: {
			type: String,
			default: t('mail', 'Confirm'),
		},
		confirmUrl: {
			type: String,
			default: undefined,
		},
	},
	methods: {
		confirm() {
			this.$emit('confirm')
		},
		cancel() {
			this.$emit('cancel')
		},
	},
}
</script>

<style lang="scss" scoped>
.confirm-modal {
	padding: 20px;

	&__buttons {
		display: flex;
		justify-content: space-between;
	}
}
</style>
