<template>
	<AppContent app-name="mail">
		<Navigation slot="navigation" />
		<template slot="content">
			<div class="app-content-details">
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
			</div>
		</template>
	</AppContent>
</template>

<script>
import {AppContent} from 'nextcloud-vue'

import Navigation from '../components/Navigation'

export default {
	name: 'KeyboardShortcuts',
	components: {
		AppContent,
		Navigation,
	},
}
</script>

<style></style>
