<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<IconPlus v-if="icon === 'icon-add'" :size="20" />
	<IconAddressBook v-else-if="icon === 'icon-address-book'" :size="20" />
	<IconEmail v-else-if="icon === 'icon-mail'" :size="20" />
	<IconPhone v-else-if="icon === 'icon-phone'" :size="20" />
	<IconDelete v-else-if="icon === 'icon-delete'" :size="20" />
	<IconPencil v-else-if="icon === 'icon-rename'" :size="20" />
	<IconMapMaker v-else-if="icon === 'icon-address'" :size="20" />
	<IconMultiple v-else-if="icon === 'icon-contacts-dark'" :size="20" />
	<IconWeb v-else-if="icon === 'icon-public' || icon === 'icon-timezone'" :size="20" />
	<IconCakeVariant v-else-if="icon === 'icon-calendar-dark'" :size="20" />
	<IconMessageReplyText v-else-if="icon === 'icon-comment'" :size="20" />
	<IconHeart v-else-if="icon === 'icon-social'" :size="20" />
	<IconAccountSupervisor v-else-if="icon === 'icon-group' || icon === 'icon-manager'" :size="20" />
	<IconAccount v-else-if="icon === 'icon-gender' || icon === 'icon-user'" :size="20" />
	<IconCalendarHeartOutline v-else-if="icon === 'icon-anniversary'" :size="20" />
	<IconCoffin v-else-if="icon === 'icon-death-day'" :size="20" />
	<IconBadgeAccountHorizontal v-else-if="icon === 'icon-detailed-name'" :size="20" />
	<IconCloud v-else-if="icon === 'icon-federated-cloud-id'" :size="20" />
	<IconMessageProcessing v-else-if="icon === 'icon-instant-message'" :size="20" />
	<IconMap v-else-if="icon === 'icon-location'" :size="20" />
	<IconText v-else-if="icon === 'icon-note'" :size="20" />
	<IconTranslateVariant v-else-if="icon === 'icon-spoken-lang'" :size="20" />
	<IconAccountBoxMultiple v-else-if="icon === 'icon-related-contact'" :size="20" />
	<IconAccountSwitch v-else-if="icon === 'icon-relation-to-you'" :size="20" />
</template>

<script>
import IconPlus from 'vue-material-design-icons/Plus.vue'
import IconAddressBook from 'vue-material-design-icons/AccountMultiple.vue'
import IconEmail from 'vue-material-design-icons/Email.vue'
import IconPhone from 'vue-material-design-icons/Phone.vue'
import IconDelete from 'vue-material-design-icons/Delete.vue'
import IconAccount from 'vue-material-design-icons/Account.vue'
import IconPencil from 'vue-material-design-icons/Pencil.vue'
import IconMapMaker from 'vue-material-design-icons/MapMarker.vue'
import IconMultiple from 'vue-material-design-icons/AccountMultiple.vue'
import IconWeb from 'vue-material-design-icons/Web.vue'
import IconCakeVariant from 'vue-material-design-icons/CakeVariant.vue'
import IconMessageReplyText from 'vue-material-design-icons/MessageReplyText.vue'
import IconHeart from 'vue-material-design-icons/Heart.vue'
import IconAccountSupervisor from 'vue-material-design-icons/AccountSupervisor.vue'
import IconCalendarHeartOutline from 'vue-material-design-icons/CalendarHeartOutline.vue'
import IconCoffin from 'vue-material-design-icons/Coffin.vue'
import IconBadgeAccountHorizontal from 'vue-material-design-icons/BadgeAccountHorizontal.vue'
import IconCloud from 'vue-material-design-icons/Cloud.vue'
import IconMessageProcessing from 'vue-material-design-icons/MessageProcessing.vue'
import IconMap from 'vue-material-design-icons/Map.vue'
import IconText from 'vue-material-design-icons/Text.vue'
import IconTranslateVariant from 'vue-material-design-icons/TranslateVariant.vue'
import IconAccountBoxMultiple from 'vue-material-design-icons/AccountBoxMultiple.vue'
import IconAccountSwitch from 'vue-material-design-icons/AccountSwitch.vue'
export default {
	name: 'RecipientPropertyTitleIcon',
	components: {
		IconPlus,
		IconAddressBook,
		IconEmail,
		IconPhone,
		IconDelete,
		IconPencil,
		IconMapMaker,
		IconMultiple,
		IconWeb,
		IconCakeVariant,
		IconMessageReplyText,
		IconHeart,
		IconAccountSupervisor,
		IconAccount,
		IconCalendarHeartOutline,
		IconCoffin,
		IconBadgeAccountHorizontal,
		IconCloud,
		IconMessageProcessing,
		IconMap,
		IconText,
		IconTranslateVariant,
		IconAccountBoxMultiple,
		IconAccountSwitch,
	},
	props: {
		icon: {
			type: String,
			default: '',
			required: false,
		},
	},
}
</script>
<style lang="scss" scoped>
.material-design-icon {
	opacity: 0.8;
}
</style>
