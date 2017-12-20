/**
 * @author Jakob Sack <mail@jakobsack.de>
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

define(function() {
	'use strict';

	return function(options) {
		var noreply = false;

		var isToNoreply = function(recipient){
			var localFrom = recipient.email.substring(0, recipient.email.lastIndexOf('@'));
			if (localFrom == 'noreply') {
				noreply = true;
			}
		};
		this.to.forEach(isToNoreply);
		this.cc.forEach(isToNoreply);
		this.bcc.forEach(isToNoreply);

		if (noreply) {
			return options.fn(this);
		} else {
			return options.inverse(this);
		}
	};
});
