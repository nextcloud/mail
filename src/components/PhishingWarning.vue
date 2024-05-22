<!--
  - @copyright 2024 Hamza Mahjoubi <hamza.mahjoubi221@proton.me>
  -
  - @author 2024 Hamza Mahjoubi <hamza.mahjoubi221@proton.me>
  -
  - @license AGPL-3.0-or-later
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
	<div class="warning">
		<div class="warning__title">
			<IconAlertOutline :size="20" :title="t('mail', 'Phishing email')" />
			This email might be a phishing attempt
		</div>
		<ul v-for="(warning, key) in warnings" :key="key" class="warning__list">
			<li>{{ warning.message }}</li>
		</ul>
		<div v-if="hasLinkWarnings" class="warning__links">
			<NcButton class="warning__links__button" type="Tertiary" @click="showMore = !showMore">
				{{ showMore? t('mail','hide suspicious links') :t('mail','Show suspicious links') }}
			</NcButton>
			<div v-if="showMore">
				<ul v-for="(link,index) in warnings.links.links" :key="index" class="warning__list">
					<li><b>href: </b>{{ link.href }} : <b>{{ t('mail','link text') }}</b> {{ link.linkText }} </li>
				</ul>
			</div>
		</div>
	</div>
</template>
<script>
import { NcButton } from '@nextcloud/vue'
import IconAlertOutline from 'vue-material-design-icons/AlertOutline.vue'

export default {

	name: 'PhishingWarning',
	components: {
		NcButton,
		IconAlertOutline,
	},
	props: {
		phishingData: {
			required: true,
			type: Object,
		},
	},
	data() {
		return {
			showMore: false,
		}
	},
	computed: {
		warnings() {
			const result = {}
			for (const key in this.phishingData) {
				if (this.phishingData[key].check === false) {
					result[key] = this.phishingData[key]
				}
			}
			return result
		},
		hasLinkWarnings() {
			return Object.keys(this.warnings).includes('links')
		},
	},

}

</script>
<style lang="scss" scoped>
.warning {
	background-color:var(--ck-color-base-error);
    border-radius: var(--border-radius-rounded);
    width: 100%;
    text-align: left;
    padding: 15px;
    margin-bottom: 10px;
	&__title {
		display: flex;
	}
	&__list {
		list-style-position: inside;
		list-style-type: disc;
	}
    &__links {
      margin-top: 10px;
		&__button{
			margin-bottom: 10px;
		}
    }
}
</style>
