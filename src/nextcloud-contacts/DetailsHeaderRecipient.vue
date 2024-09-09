<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<!-- contact header -->
	<header class="contact-header" :style="cssStyle">
		<div class="contact-header__no-wrap">
			<div class="contact-header__avatar">
				<slot name="avatar" :avatar-size="avatarSize" />
			</div>

			<!-- fullname, org, title -->
			<div class="contact-header__infos">
				<h2 class="contact-header__infos-title">
					<slot name="title" />
				</h2>
			</div>
		</div>
	</header>
</template>

<script>
export default {
	name: 'DetailsHeaderRecipient',

	data() {
		return {
			avatarSize: 75,
		}
	},

	computed: {
		cssStyle() {
			return {
				'--avatar-size': this.avatarSize + 'px',
			}
		},
	},
}
</script>

<style lang="scss" scoped>

// Header with avatar, name, position, actions...
.contact-header {
	display: flex;
	align-items: center;
	padding: 0 20px;

	@media (max-width: 1024px) {
		// Top padding of 44px is already included in AppContent by default on mobile
		&__no-wrap {
			width: 100%;
		}
		&__actions .header-menu {
			margin-left: auto;
		}
		&__avatar {
			width: 150px !important;
		}
	}

	&__no-wrap {
		display: flex;
		align-items: center;
		min-width: 0;
	}

	// AVATAR
	&__avatar {
		// Global single column layout
		display: flex;
		flex: 0 1 auto;
		justify-content: flex-end;
		min-width: 0; // Has to be zero unless we implement wrapping
	}

	// ORG-TITLE-NAME
	&__infos {
		flex-direction: column;

		// Global single column layout
		display: flex;
		flex: 0 1 auto;
		min-width: 0; // Has to be zero unless we implement wrapping

		&-title,
		&-subtitle {
			display: flex;
			flex-wrap: wrap;
			margin: 0;
		}

		&__quick-actions {
			padding: 5px 0;
		}

		:deep(input) {
			flex: 1 auto;
		}

		&-title :deep(input) {
			font-weight: bold;
		}
	}
}
</style>
