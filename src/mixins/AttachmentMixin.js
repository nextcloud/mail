/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const PDF_MIME = 'application/pdf'
const PDF_HANDLER_ID = 'pdf'

/**
 * Mail attachments have no Nextcloud file id, so office handlers that hijack
 * application/pdf (Nextcloud Office, ONLYOFFICE) cannot open them and bail out
 * with an empty WOPI config. Only files_pdfviewer works off a plain URL.
 *
 * @return {boolean} whether files_pdfviewer is registered in the viewer
 */
function hasPdfViewer() {
	return (OCA?.Viewer?.availableHandlers ?? []).some((handler) => handler.id === PDF_HANDLER_ID)
}

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
			}))
		},

		previewableFileInfos() {
			if (!OCA?.Viewer) {
				return []
			}

			return this.fileInfos.filter((fileInfo) => (fileInfo.mime.startsWith('image/')
				|| fileInfo.mime.startsWith('video/')
				|| fileInfo.mime.startsWith('audio/')
				|| (fileInfo.mime === PDF_MIME && hasPdfViewer()))
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

			const options = {
				fileInfo,
				list: this.previewableFileInfos,
			}

			if (fileInfo.mime === PDF_MIME && OCA.Viewer.openWith) {
				OCA.Viewer.openWith(PDF_HANDLER_ID, options)
				return
			}

			OCA.Viewer.open(options)
		},

	},
}
