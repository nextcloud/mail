<template>
	<div id="content" class="mail">
		<app-navigation :menu="menu"/>
	</div>
</template>

<script>
	import AppNavigation from "../components/core/appNavigation";

	export default {
		name: 'home',
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
							router: {},
							utils: {
								counter: 2
							}
						},
						{
							id: 1,
							key: 1,
							text: 'email1@domain.com',
							bullet: '#ee2629',
							router: {}
						},
						{
							id: 'folder1',
							key: 'folder1',
							text: 'Inbox',
							icon: 'icon-inbox',
							router: {},
							utils: {
								counter: 2
							}
						},
						{
							id: 'folder2',
							key: 'folder2',
							text: 'Favorites',
							icon: 'icon-flagged',
							router: {},
							utils: {
								counter: 2
							}
						},
						{
							id: 'folder3',
							key: 'folder3',
							text: 'Drafts',
							icon: 'icon-drafts',
							router: {},
							utils: {
								counter: 1
							}
						},
						{
							id: 'folder4',
							key: 'folder4',
							text: 'Sent',
							icon: 'icon-sent',
							router: {},
							utils: {
								counter: 0
							}
						},
						{
							id: 'folder5',
							key: 'folder5',
							text: 'Show all',
							router: {}
						},
						{
							id: 2,
							key: 2,
							text: 'email2@domain.com',
							bullet: '#81ee53',
							router: {}
						},
						{
							id: 'folder2',
							key: 'folder2',
							text: 'Inbox',
							icon: 'icon-inbox',
							router: {},
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
