/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Luc Calaresu <dev@calaresu.com>
 *
 * ownCloud - Mail
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
 * along with this program.	If not, see <http://www.gnu.org/licenses/>
 *
 */

define(function(require) {
	'use strict';

	var Attachment = require('models/attachment');

	var LocalAttachment = Attachment.extend({
		defaults: {
			progress: 0,
			uploadStatus: 0  /* 0=pending, 1=ongoing, 2=error, 3=success */
		},
		initialize: function() {
			Attachment.prototype.initialize.call(this);
		},
		onProgress: function(evt) {
			if (evt.lengthComputable) {
				this.set('uploadStatus', 1);
				this.set('progress', evt.loaded / evt.total);
			}
		}
	});

	return LocalAttachment;
});
