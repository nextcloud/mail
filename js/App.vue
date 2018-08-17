<!--
 - @copyright Copyright (c) 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 -
 - @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
 - along with this program. If not, see <http://www.gnu.org/licenses/>.
 -
 -->

<template>
	<div id="content" class="app-">
		<app-navigation :menu="menu"/>
	</div>
</template>

<script>
	import AppNavigation from "./components/core/appNavigation";

	export default {
		name: 'app',
		components: {AppNavigation},
		computed: {
			menu () {
				return {
					id: 'accounts-list',
					new: {
						'id': 'mail_new_message',
						text: t('mail', 'New message'),
						icon: 'icon-add',
						action: this.newMessage
					},
					items: [
						{
							id: -1,
							key: -1,
							text: 'All inboxes',
							icon: 'icon-inbox',
							router: {

							},
							utils: {
								counter: 2
							}
						},
						{
							id: 1,
							key: 1,
							text: 'email1@domain.com',
							bullet: '#ee2629',
							router: {

							}
						},
						{
							id: 'folder1',
							key: 'folder1',
							text: 'Inbox',
							icon: 'icon-inbox',
							router: {

							},
							utils: {
								counter: 2
							}
						},
						{
							id: 'folder2',
							key: 'folder2',
							text: 'Favorites',
							icon: 'icon-flagged',
							router: {

							},
							utils: {
								counter: 2
							}
						},
						{
							id: 'folder3',
							key: 'folder3',
							text: 'Drafts',
							icon: 'icon-drafts',
							router: {

							},
							utils: {
								counter: 1
							}
						},
						{
							id: 'folder4',
							key: 'folder4',
							text: 'Sent',
							icon: 'icon-sent',
							router: {

							},
							utils: {
								counter: 0
							}
						},
						{
							id: 'folder5',
							key: 'folder5',
							text: 'Show all',
							router: {

							}
						},
						{
							id: 2,
							key: 2,
							text: 'email2@domain.com',
							bullet: '#81ee53',
							router: {

							}
						},
						{
							id: 'folder2',
							key: 'folder2',
							text: 'Inbox',
							icon: 'icon-inbox',
							router: {

							},
							utils: {
								counter: 0
							}
						}
					]
				}
			}
		},
		methods: {
			newMessage () {
				console.info('New message clicked');
			}
		}
	}
</script>

<style lang="scss">
</style>
