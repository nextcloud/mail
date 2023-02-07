<template>
	<Modal
		size="large"
		:title="t('mail', 'Keyboard shortcut')"
		@close="$emit('close')">
		<h2>{{ t('mail', 'Keyboard shortcuts') }}</h2>

		<p>{{ t('mail', 'Speed up your Mail experience with these quick shortcuts.') }}</p>

		<dl>
			<div>
				<dt><kbd>C</kbd></dt>
				<dd>{{ t('mail', 'Compose new message') }}</dd>
			</div>
			<div>
				<dt><kbd>←</kbd></dt>
				<dd>{{ t('mail', 'Newer message') }}</dd>
			</div>
			<div>
				<dt><kbd>→</kbd></dt>
				<dd>{{ t('mail', 'Older message') }}</dd>
			</div>

			<div>
				<dt><kbd>S</kbd></dt>
				<dd>{{ t('mail', 'Toggle star') }}</dd>
			</div>
			<div>
				<dt><kbd>U</kbd></dt>
				<dd>{{ t('mail', 'Toggle unread') }}</dd>
			</div>
			<div>
				<dt><kbd>A</kbd></dt>
				<dd>{{ t('mail', 'Archive') }}</dd>
			</div>
			<div>
				<dt><kbd>Del</kbd></dt>
				<dd>{{ t('mail', 'Delete') }}</dd>
			</div>

			<div>
				<dt><kbd>Ctrl</kbd> + <kbd>F</kbd></dt>
				<dd>{{ t('mail', 'Search') }}</dd>
			</div>
			<div>
				<dt><kbd>Ctrl</kbd> + <kbd>Enter</kbd></dt>
				<dd>{{ t('mail', 'Send') }}</dd>
			</div>
			<div>
				<dt><kbd>R</kbd></dt>
				<dd>{{ t('mail', 'Refresh') }}</dd>
			</div>
		</dl>
	</Modal>
</template>

<script>
import { NcModal as Modal } from '@nextcloud/vue'

export default {
	name: 'KeyboardShortcuts',
	components: {
		Modal,
	},
}
</script>

<style lang="scss" scoped>
:deep(.modal-wrapper .modal-container) {
	display: block;
	overflow: scroll;
	transition: transform 300ms ease;
	border-radius: var(--border-radius-large);
	box-shadow: 0 0 40px rgba(0,0,0,0.2);
	padding: 30px 40px 20px;
}
dt {
	width: auto;
	text-align: left;
}

</style>
