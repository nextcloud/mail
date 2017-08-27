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

	//duplicate getScrollBarWidth function from core js.js
	//TODO: remove once OC 8.0 support has been dropped
	window.getScrollBarWidth = window.getScrollBarWidth || function() {
		var inner = document.createElement('p');
		inner.style.width = '100%';
		inner.style.height = '200px';

		var outer = document.createElement('div');
		outer.style.position = 'absolute';
		outer.style.top = '0px';
		outer.style.left = '0px';
		outer.style.visibility = 'hidden';
		outer.style.width = '200px';
		outer.style.height = '150px';
		outer.style.overflow = 'hidden';
		outer.appendChild(inner);

		document.body.appendChild(outer);
		var w1 = inner.offsetWidth;
		outer.style.overflow = 'scroll';
		var w2 = inner.offsetWidth;
		if (w1 === w2) {
			w2 = outer.clientWidth;
		}

		document.body.removeChild(outer);

		return (w1 - w2);
	};
	//END TODO

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
