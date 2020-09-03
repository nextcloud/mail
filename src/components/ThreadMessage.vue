<!--
  - @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
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
	<router-link :to="route"
		class="icon-mail">
		<div class="left">
			<div class="sender">
				{{ message.from[0].label }}
			</div>
			<div class="preview">
				<!-- TODO: instead of subject it should be shown the first line of the message #2666 -->
				{{ message.subject }}
			</div>
		</div>
		<div class="right">
			<div><Moment :timestamp="message.dateInt" /></div>
		</div>
	</router-link>
</template>

<script>
import Moment from './Moment'

export default {
	name: 'ThreadMessage',
	components: { Moment },
	props: {
		message: {
			required: true,
			type: Object,
		},
	},
	computed: {
		route() {
			return {
				name: 'message',
				params: {
					mailboxId: this.message.mailboxId,
					threadId: this.message.databaseId,
				},
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.icon-mail {
	background-image: var(--icon-mail-000);
	background-position: 0 center;

	display: flex;
	flex-direction: row;
	justify-content: space-between;
	align-items: center;

	border-bottom: 1px solid var(--color-primary-light);
	padding-left: 30px;
	margin-bottom: 15px;
	horiz-align: center;
	opacity: 0.7;

	&:hover {
		opacity: 1;
	}

	.sender {
		font-weight: bold;
	}
}
</style>
