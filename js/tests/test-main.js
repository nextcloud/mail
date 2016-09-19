var allTestFiles = [];
var allModules = [];
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
	} else {
		var excluded = ['OC', 'autoredirect', 'searchproxy', 'app'];
		var normalizedModule = file.replace(/^\/base\/js\/|\.js$/g, '');
		if (normalizedModule.substring(0, '/base/js/'.length) === '/base/js/') {
			normalizedModule = normalizedModule.substring('/base/js/'.length);
		}
		if (normalizedModule.substring(0, 'vendor'.length) === 'vendor') {
			return;
		}
		if (normalizedModule.substring(0, 'templates'.length) === 'templates') {
			return;
		}
		if (normalizedModule.substring(0, '/base/node_modules'.length) === '/base/node_modules') {
			return;
		}
		if (excluded.indexOf(normalizedModule) !== -1) {
			return;
		}
		allModules.push(normalizedModule);
	}
});


OC = {
	Notification: {
		showTemporary: function() {

		}
	},
	generateUrl: function(url) {
		return url;
	},
	linkToRemote: function() {

	}
};

require.config({
	// Karma serves files under /base, which is the basePath from your config file
	baseUrl: '/base/js',
	paths: {
		/**
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
		text: 'vendor/text/text'
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
	deps: allTestFiles.concat(allModules),
	// we have to kickoff jasmine, as it is asynchronous
	callback: window.__karma__.start
});
