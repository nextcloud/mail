/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Luc Calaresu <dev@calaresu.com>
 */

define(function(require) {
	'use strict';

	var Attachment = require('models/attachment');

	var LocalAttachment = Attachment.extend({

		defaults: {
			progress: 0,
			uploadStatus: 0,  /* 0=pending, 1=ongoing, 2=error, 3=success */
			isLocal: true
		},

		/**
		 * @param {Event} evt
		 * @returns {undefined}
		 */
		onProgress: function(evt) {
			if (evt.lengthComputable) {
				this.set('uploadStatus', 1);
				this.set('progress', evt.loaded / evt.total);
			}
		}
	});

	return LocalAttachment;
});
