/*
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require('jsdom-global')()
const chai = require('chai')
const sinonChai = require('sinon-chai')

chai.use(sinonChai)
global.expect = chai.expect
// https://github.com/vuejs/vue-test-utils/issues/936
// better fix for "TypeError: Super expression must either be null or
// a function" than pinning an old version of prettier.
//
// https://github.com/vuejs/vue-cli/issues/2128#issuecomment-453109575
window.Date = Date

// Fix for jsdom https://github.com/developit/preact/issues/444
global.SVGElement = global.Element

global.OC = {
	getLocale: () => 'en',
	L10N: {
		translate: (app, string) => {
			if (app !== 'mail') {
				throw new Error('tried to translate a string for an app other than Mail')
			}
			return string
		},
	},
	isUserAdmin: () => false,
}
