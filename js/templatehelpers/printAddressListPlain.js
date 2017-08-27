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

module.exports = function(addressList) {
	var str = _.reduce(addressList, function(memo, value, index) {
		if (index !== 0) {
			memo += ', ';
		}
		var label = value.label
			.replace(/(^"|"$)/g, '')
			.replace(/(^'|'$)/g, '');
		label = Handlebars.Utils.escapeExpression(label);
		var email = Handlebars.Utils.escapeExpression(value.email);
		if (label === email) {
			return memo + email;
		} else {
			return memo + '"' + label + '" <' + email + '>';
		}
	}, '');
	return str;
};