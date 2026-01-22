<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<span class="file-icon" :style="{ color: `var(${color})` }">
		<component :is="iconComponent" class="file-icon__svg" :size="16" />
	</span>
</template>

<script>
import DocumentOutlineIcon from 'vue-material-design-icons/FileDocumentOutline.vue'
import MusicOutlineIcon from 'vue-material-design-icons/FileMusicOutline.vue'
import FileOutlineIcon from 'vue-material-design-icons/FileOutline.vue'
import FilePdfBox from 'vue-material-design-icons/FilePdfBox.vue'
import VideoOutlineIcon from 'vue-material-design-icons/FileVideoOutline.vue'
import ImageOutlineIcon from 'vue-material-design-icons/ImageOutline.vue'
import { FILE_EXTENSIONS_PRESENTATION, FILE_EXTENSIONS_SPREADSHEET, FILE_EXTENSIONS_WORD_PROCESSING } from '../../store/constants.js'

export default {
	name: 'FileIcon',
	props: {
		fileName: {
			type: String,
			required: true,
		},

		mimeType: {
			type: String,
			required: true,
		},
	},

	data() {
		return {
			extension: '',
			icon: null,
			color: '--color-text-maxcontrast',
		}
	},

	computed: {
		iconName() {
			const type = this.mimeType.split('/')[0].toLowerCase()
			if (this.extension === 'pdf') {
				return 'pdf'
			}
			if ([...FILE_EXTENSIONS_WORD_PROCESSING, 'txt', 'md'].includes(this.extension)) {
				return 'document'
			}
			if (type === 'image') {
				return 'image'
			}
			if (type === 'video') {
				return 'video'
			}
			if (type === 'audio') {
				return 'music'
			}
			return 'file'
		},

		iconComponent() {
			const map = {
				file: FileOutlineIcon,
				image: ImageOutlineIcon,
				video: VideoOutlineIcon,
				music: MusicOutlineIcon,
				document: DocumentOutlineIcon,
				pdf: FilePdfBox,
			}
			return map[this.iconName] || FileOutlineIcon
		},
	},

	mounted() {
		this.extension = this.fileName.split('.').pop().toLowerCase()
		this.setColor()
	},

	methods: {
		setColor() {
			if (FILE_EXTENSIONS_WORD_PROCESSING.includes(this.extension)) {
				this.color = '--color-info-text'
			} else if (FILE_EXTENSIONS_SPREADSHEET.includes(this.extension)) {
				this.color = '--color-border-success'
			} else if (FILE_EXTENSIONS_PRESENTATION.includes(this.extension)) {
				this.color = '--color-favorite'
			} else if (this.extension === 'pdf') {
				this.color = '--color-text-error'
			}
		},
	},
}
</script>

<style scoped>
.file-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 16px;
    height: 16px;
	border-radius: 4px;
	padding: 0 4px;
}
</style>
