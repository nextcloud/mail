var allTestFiles = [];
var TEST_REGEXP = /(spec|test)\.js$/i;

// Get a list of all the test files to include
Object.keys(window.__karma__.files).forEach(function(file) {
	if (TEST_REGEXP.test(file)) {
		// Normalize paths to RequireJS module names.
		// If you require sub-dependencies of test files to be loaded as-is (requiring file extension)
		// then do not normalize the paths
		var normalizedTestModule = file.replace(/^\/base\/js\/|\.js$/g, '');
		if (normalizedTestModule.substring(0, 'tests'.length) === 'tests') {
			allTestFiles.push(normalizedTestModule);
		}
	}
});

window.t = function(app, text) {
	if (app !== 'mail') {
		throw 'wrong app used to for translation';
	}
	return text;
};


OC = {
	Notification: {
		showTemporary: function() {

		}
	},
	generateUrl: function(url, params) {
		var props = [];
		for (var prop in params) {
			props.push(prop);
		}
		return '/base/' + props.reduce(function(url, paramName) {
			var param = params[paramName];
			return url.replace('{' + paramName + '}', param);
		}, url);
	},
	linkToRemote: function() {

	}
};

SearchProxy = {
	attach: function(search) {

	},
	filterProxy: function(query) {

	},
	setFilter: function(newFilter) {

	}
};

// jQuery module stubs
$.fn.tooltip = function() {

};

$.fn.autosize = function() {

};

$.fn.droppable = function() {

};

formatDate = function(arg) {
	return arg;
};

relative_modified_date = function(arg) {
	return arg;
};

require.config({
	// Karma serves files under /base, which is the basePath from your config file
	baseUrl: '/base/js',
	paths: {
		/*
		 * Libraries
		 */
		backbone: 'vendor/backbone/backbone',
		'backbone.radio': 'vendor/backbone.radio/build/backbone.radio',
		davclient: 'vendor/davclient.js/lib/client',
		domready: 'vendor/domReady/domReady',
		'es6-promise': 'vendor/es6-promise/es6-promise.min',
		handlebars: 'vendor/handlebars/handlebars',
		ical: 'vendor/ical.js/build/ical.min',
		marionette: 'vendor/backbone.marionette/lib/backbone.marionette',
		underscore: 'vendor/underscore/underscore',
		text: 'vendor/text/text',
		'jquery-ui': 'vendor/jquery-ui/ui/minified/jquery-ui.custom.min'
	},
	shim: {
		davclient: {
			exports: 'dav'
		},
		ical: {
			exports: 'ICAL'
		}
	},
	// dynamically load all test files
	deps: allTestFiles,
	// we have to kickoff jasmine, as it is asynchronous
	callback: window.__karma__.start
});
