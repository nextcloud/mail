/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export default {
	computed: {
		fileInfos() {
			return this.attachments.map((attachment) => ({
				filename: attachment.downloadUrl,
				source: attachment.downloadUrl,
				basename: attachment.fileName,
				mime: attachment.mime,
				etag: 'fixme',
				hasPreview: false,
				fileid: parseInt(attachment.id, 10),
			}))
		},

		previewableFileInfos() {
			return this.fileInfos.filter((fileInfo) => (fileInfo.mime.startsWith('image/')
				|| fileInfo.mime.startsWith('video/')
				|| fileInfo.mime.startsWith('audio/')
				|| fileInfo.mime === 'application/pdf')
			&& OCA.Viewer.mimetypes.includes(fileInfo.mime))
		},
	},
	methods: {
		canPreview(fileInfo) {
			return this.previewableFileInfos.includes(fileInfo)
		},
		showViewer(fileInfo) {
			if (!this.canPreview(fileInfo)) {
				return
			}

			OCA.Viewer.open({
				fileInfo,
				list: this.previewableFileInfos,
			})
		},

	},
}
