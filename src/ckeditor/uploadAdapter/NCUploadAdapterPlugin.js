/**
 * @author Cyrille Bollu <cyr.debian@bollu.be>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

import Plugin from '@ckeditor/ckeditor5-core/src/plugin'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

// The NCUploadAdapter is responsible to upload inserted inline images to the server and return
// a proper result
class NCUploadAdapter {

	constructor(loader) {
		this.loader = loader
	}

	// The upload() method reads the file's content using FileReader and once done sent it
	// to the server using axios.
	upload() {
		return this.loader.file
			.then(file => {
				return new Promise((resolve, reject) => {
					var reader = new FileReader() 
					reader.onload = () => {
						const data = new FormData()
						data.append('upload', reader.result)
						axios({
							url: generateUrl('/apps/mail/api/messages/image'), 
							method: 'post',
							data,
							headers: {
								'Content-Type': 'multipart/form-data;',
							},
						}).then((resp) => {
							if (resp.data.result === 'success') {
								resolve({ default: response.data.url })
							} else {
								reject(resp.data.message)
							}
						}).catch((resp) => {
							reject('Upload failed')
						})
						resolve(reader.result)
					}
					reader.readAsBinaryString(file)
				})
			})
	}

	abort() {

	}

}

// Defines the NCUploadAdapterPlugin that allows adding inline image in the editor 
export default class NCUploadAdapterPlugin extends Plugin {

	init() {
		this.editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
			return new NCUploadAdapter(loader)
		}
	}
}
