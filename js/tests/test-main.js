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
	filePath: function(app, type, path) {
		return type + '/' + path;
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

$.fn.droppable = function() {

};

$.fn.imageplaceholder = function() {

};

formatDate = function(arg) {
	return arg;
};

relative_modified_date = function(arg) {
	return arg;
};
