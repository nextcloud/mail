/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 *
 */

define(function(require) {
	'use strict';

	var $ = require('jquery');
	var OC = require('OC');
	var IssueTemplateBuilder = require('nextcloud_issuetemplate_builder');

	function isDebugMode() {
		return $('#debug-mode').val() === 'true';
	}

	function flattenError(error) {
		var text = '';
		if (error.type) {
			text += error.type + ': ';
		}
		text += error.message;
		text += '\n';
		if (error.trace) {
			text += flattenTrace(error.trace);
		}
		if (error.stack) {
			text += error.stack;
		}
		return text;
	}

	function flattenTrace(trace) {
		return trace.reduce(function(acc, entry) {
			var text = '';
			if (entry.class) {
				text += '  at ' + entry.class + '::' + entry.function;
			} else {
				text += '  at ' + entry.function;
			}
			if (entry.file) {
				text += '\n     ' + entry.file + ', line ' + entry.line;
			}
			return acc + text + '\n';
		}, '');
	}

	function report(error) {
		console.error(error);
		var message = error.message || 'An unkown error occurred.';
		if (!message.endsWith('.')) {
			message += '.';
		}
		var debug = isDebugMode();

		var $notification = $('<div>');
		var $message = $('<a>');
		var $row = null;
		$message.text('Error: ' + message);
		if (debug) {
			var builder = new IssueTemplateBuilder();
			var template = builder.addEmptyStepsToReproduce()
					.addExpectedActualBehaviour()
					.addLogs('Server error', flattenError(error))
					.render();

			$message.append(' Report a bug.');
			var url = 'https://github.com/nextcloud/mail/issues/new' +
					'?title=' + encodeURIComponent(message) +
					'&body=' + encodeURIComponent(template);
			$message.attr('href', url);
			$message.attr('target', '_blank');
			$message.attr('rel', 'noopener');
			$message.click(function() {
				OC.Notification.hide($row);
			});
		}
		$notification.append($message);

		$row = OC.Notification.showTemporary($notification, {
			isHTML: true
		});
	}

	return {
		report: report
	};
});
