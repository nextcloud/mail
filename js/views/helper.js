/* global getScrollBarWidth */

/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst
 */

define(function(require) {
	'use strict';

	var $ = require('jquery');
	var _ = require('underscore');

	// TODO: get rid of global functions
	// adjust controls/header bar width
	window.adjustControlsWidth = function() {
		if ($('#mail-message-header').length) {
			var controlsWidth;
			if ($(window).width() > 768) {
				controlsWidth =
					$('#content').width() -
					$('#app-navigation').width() -
					$('#mail-messages').width() -
					getScrollBarWidth();
			}
			$('#mail-message-header').css('width', controlsWidth);
			$('#mail-message-header').css('min-width', controlsWidth);
		}
	};

	$(window).resize(_.debounce(window.adjustControlsWidth, 250));
	// END TODO
});
