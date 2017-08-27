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

module.exports = function(account) {
	var hash = md5(account);
	var hue = null;
	if (typeof hash.toHsl === 'function') {
		var hsl = hash.toHsl();
		hue = Math.round(hsl[0] / 40) * 40;
		return new Handlebars.SafeString('hsl(' + hue + ', ' + hsl[1] + '%, ' + hsl[2] + '%)');
	} else {
		var maxRange = parseInt('ffffffffffffffffffffffffffffffff', 16);
		hue = parseInt(hash, 16) / maxRange * 256;
		return new Handlebars.SafeString('hsl(' + hue + ', 90%, 65%)');
	}
};