<template>
	<li class="composer-attachment" :class="{'composer-attachment--with-error' : attachment.error }">
		<div class="attachment-preview">
			<img v-if="attachment.imageBlobURL !== false" :src="attachment.imageBlobURL" class="attachment-preview-image">
			<img v-else-if="attachment.hasPreview" :src="previewURL" class="attachment-preview-image">
			<img v-else :src="getIcon" class="attachment-preview-image">
			<span v-if="attachment.type === 'cloud'" class="cloud-attachment-icon">
				<Cloud :size="16" />
			</span>
		</div>
		<div class="attachment-inner">
			<span class="new-message-attachment-name">
				{{ attachment.displayName ? attachment.displayName : attachment.fileName }}
			</span>
			<span v-if="!attachment.finished" class="attachments-upload-progress">
				<span class="attachments-upload-progress--bar" :style="&quot;width:&quot; + attachment.percent + &quot;%&quot;" />
			</span>
			<span v-else class="new-message-attachment-size">{{ attachment.sizeString }}</span>
		</div>
		<button @click="onDelete(attachment)">
			<Close :size="24" />
		</button>
	</li>
</template>

<script>
import { generateUrl } from '@nextcloud/router'
import Close from 'vue-material-design-icons/Close'
import Cloud from 'vue-material-design-icons/Cloud'

export default {
	name: 'ComposerAttachment',
	components: {
		Close,
		Cloud,
	},
	props: {
		bus: {
			type: Object,
			required: true,
		},
		attachment: {
			type: Object,
			required: true,
		},
		uploading: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
			progress: 0,
			sizeString: '',
			finished: false,
		}
	},
	computed: {
		previewURL() {
			if (this.attachment.hasPreview && this.attachment.id > 0) {
				return generateUrl(`/core/preview?fileId=${this.attachment.id}&x=100&y=100&a=0`)
			}
			return ''
		},
		getIcon() {
			return OC.MimeType.getIconUrl(this.attachment.fileType)
		},
		extension() {
			return this.attachment.fileName.split('.').pop()
		},
	},
	methods: {
		onDelete(attachment) {
			this.$emit('on-delete-attachment', attachment)
		},
	},

}
</script>

<style lang="scss" scoped>

.composer-attachment {
	width: calc(50% - 20px);
    box-sizing: border-box;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin: 10px;
    flex-wrap: wrap;

	&--with-error {
		color:red;
		opacity: 0.5;
	}

	.cloud-attachment-icon {
		position:absolute;
		z-index: 2;
		right: 2px;
		top: 2px;
		color: rgba(0, 0, 0, 1);
	}

	.attachment-preview {
		display: inline-flex;
		flex-wrap: wrap;
		width: 50px;
		height:50px;
		overflow: hidden;
		border-radius: 3px;
		align-items: center;
		justify-content: center;
		position: relative;

		img {
			display: block;
			min-width: 50px;
			min-height: 50px;
			max-width: 72px;
			max-height: 72px;
			position: absolute;
		}

		span {
			color: rgba(0,0,0,0.3);
			font-size: 13px;
			text-transform: uppercase;
			font-weight: bold;
		}

	}

	button {
		padding: 0;
		background: transparent;
		border: none;
		margin: 6px -2px 0 0;
	}
}

.attachments-upload-progress {
	display: block;
	height: 5px;
	width: 100%;
	position: relative;
	border-radius: 5px;
	background: var(--color-background-dark);
	margin-top: 7px;

	.attachments-upload-progress--bar {
		height: 5px;
		background: var(--color-primary-element-light);
		position: absolute;
		z-index: 1;
		left: 0;
		border-radius: 5px;
	}
}

.attachments-upload-progress > div {
	padding-left: 3px;
}

.new-message-attachments-action {
	display: inline-block;
	vertical-align: middle;
	padding: 18px;
	opacity: 0.5;
}

.attachment-inner {
	display: flex;
    flex-wrap: wrap;
	width: calc(100% - 90px);
	position: relative;

}

/* attachment filenames */
.new-message-attachment-name {
	text-overflow: ellipsis;
	overflow: hidden;
	white-space:nowrap;
	margin-bottom: 3px;
}

.new-message-attachment-size {
	color: #6a6a6a;
	width: 100%;
}

/* Colour the filename with a different color during attachment upload */
.new-message-attachment-name.upload-ongoing {
	color: #0082c9;
}

/* Colour the filename in red if the attachment upload failed */
.new-message-attachment-name.upload-warning {
	color: #d2322d;
}

/* Red ProgressBar for failed attachment uploads */
.new-message-attachment-name.upload-warning .ui-progressbar-value {
	border: 1px solid #e9322d;
	background: #e9322d;
}
</style>
