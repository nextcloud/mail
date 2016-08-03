/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

({
	baseUrl: 'js',
	mainConfigFile: 'js/require_config.js',
	name: 'app',
	out: 'js/mail.min.js',
	insertRequire: [
		'app',
		'notification'
	]
})
