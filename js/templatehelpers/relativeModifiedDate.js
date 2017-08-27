/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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


define(function(require) {
	'use strict';

	return function(dateInt) {
		var lastModified = new Date(dateInt * 1000);
		var lastModifiedTime = Math.round(lastModified.getTime() / 1000);
		// jscs:disable requireCamelCaseOrUpperCaseIdentifiers
		return relative_modified_date(lastModifiedTime);
		// jscs:enable requireCamelCaseOrUpperCaseIdentifiers
	};
});
