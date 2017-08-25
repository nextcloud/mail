/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 24);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Radio = __webpack_require__(1);
	var AccountCollection = __webpack_require__(37);

	var state = {};

	var accounts = new AccountCollection();
	var currentAccount = null;
	var currentFolder = null;
	var currentMessage = null;
	var currentMessageSubject = null;
	var currentMessageBody = '';

	Object.defineProperties(state, {
		accounts: {
			get: function() {
				return accounts;
			},
			set: function(acc) {
				accounts = acc;
			}
		},
		currentAccount: {
			get: function() {
				return currentAccount;
			},
			set: function(account) {
				currentAccount = account;
			}
		},
		currentFolder: {
			get: function() {
				return currentFolder;
			},
			set: function(newFolder) {
				var oldFolder = currentFolder;
				currentFolder = newFolder;
				if (newFolder !== oldFolder) {
					Radio.ui.trigger('folder:changed');
				}
			}
		},
		currentMessage: {
			get: function() {
				return currentMessage;
			},
			set: function(newMessage) {
				currentMessage = newMessage;
			}
		},
		currentMessageSubject: {
			get: function() {
				return currentMessageSubject;
			},
			set: function(subject) {
				currentMessageSubject = subject;
			}
		},
		currentMessageBody: {
			get: function() {
				return currentMessageBody;
			},
			set: function(body) {
				currentMessageBody = body;
			}
		}
	});

	return state;
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 1 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2016
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var _ = __webpack_require__(3);
	var Radio = __webpack_require__(14);

	var channelNames = [
		'account',
		'aliases',
		'attachment',
		'folder',
		'dav',
		'keyboard',
		'message',
		'navigation',
		'notification',
		'state',
		'sync',
		'ui'
	];

	var channels = {};
	_.each(channelNames, function(channelName) {
		channels[channelName] = Radio.channel(channelName);
		// Uncomment the following line for debugging
		// Radio.tuneIn(channelName);
	});

	return channels;
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 2 */
/***/ (function(module, exports, __webpack_require__) {

// MarionetteJS (Backbone.Marionette)
// ----------------------------------
// v3.1.0
//
// Copyright (c)2016 Derick Bailey, Muted Solutions, LLC.
// Distributed under MIT license
//
// http://marionettejs.com


(function (global, factory) {
	 true ? module.exports = factory(__webpack_require__(6), __webpack_require__(3), __webpack_require__(14)) :
	typeof define === 'function' && define.amd ? define(['backbone', 'underscore', 'backbone.radio'], factory) :
	(global.Marionette = global['Mn'] = factory(global.Backbone,global._,global.Backbone.Radio));
}(this, function (Backbone,_,Radio) { 'use strict';

	Backbone = 'default' in Backbone ? Backbone['default'] : Backbone;
	_ = 'default' in _ ? _['default'] : _;
	Radio = 'default' in Radio ? Radio['default'] : Radio;

	var version = "3.1.0";

	//Internal utility for creating context style global utils
	var proxy = function proxy(method) {
	  return function (context) {
	    for (var _len = arguments.length, args = Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
	      args[_key - 1] = arguments[_key];
	    }

	    return method.apply(context, args);
	  };
	};

	// Borrow the Backbone `extend` method so we can use it as needed
	var extend = Backbone.Model.extend;

	var deprecate = function deprecate(message, test) {
	  if (_.isObject(message)) {
	    message = message.prev + ' is going to be removed in the future. ' + 'Please use ' + message.next + ' instead.' + (message.url ? ' See: ' + message.url : '');
	  }

	  if (!Marionette.DEV_MODE) {
	    return;
	  }

	  if ((test === undefined || !test) && !deprecate._cache[message]) {
	    deprecate._warn('Deprecation warning: ' + message);
	    deprecate._cache[message] = true;
	  }
	};

	deprecate._console = typeof console !== 'undefined' ? console : {};
	deprecate._warn = function () {
	  var warn = deprecate._console.warn || deprecate._console.log || _.noop;
	  return warn.apply(deprecate._console, arguments);
	};
	deprecate._cache = {};

	// Determine if `el` is a child of the document
	var isNodeAttached = function isNodeAttached(el) {
	  return Backbone.$.contains(document.documentElement, el);
	};

	// Merge `keys` from `options` onto `this`
	var mergeOptions = function mergeOptions(options, keys) {
	  var _this = this;

	  if (!options) {
	    return;
	  }

	  _.each(keys, function (key) {
	    var option = options[key];
	    if (option !== undefined) {
	      _this[key] = option;
	    }
	  });
	};

	// Marionette.getOption
	// --------------------

	// Retrieve an object, function or other value from the
	// object or its `options`, with `options` taking precedence.
	var getOption = function getOption(optionName) {
	  if (!optionName) {
	    return;
	  }
	  if (this.options && this.options[optionName] !== undefined) {
	    return this.options[optionName];
	  } else {
	    return this[optionName];
	  }
	};

	// Marionette.normalizeMethods
	// ----------------------

	// Pass in a mapping of events => functions or function names
	// and return a mapping of events => functions
	var normalizeMethods = function normalizeMethods(hash) {
	  var _this = this;

	  return _.reduce(hash, function (normalizedHash, method, name) {
	    if (!_.isFunction(method)) {
	      method = _this[method];
	    }
	    if (method) {
	      normalizedHash[name] = method;
	    }
	    return normalizedHash;
	  }, {});
	};

	// split the event name on the ":"
	var splitter = /(^|:)(\w)/gi;

	// take the event section ("section1:section2:section3")
	// and turn it in to uppercase name onSection1Section2Section3
	function getEventName(match, prefix, eventName) {
	  return eventName.toUpperCase();
	}

	var getOnMethodName = _.memoize(function (event) {
	  return 'on' + event.replace(splitter, getEventName);
	});

	// Trigger an event and/or a corresponding method name. Examples:
	//
	// `this.triggerMethod("foo")` will trigger the "foo" event and
	// call the "onFoo" method.
	//
	// `this.triggerMethod("foo:bar")` will trigger the "foo:bar" event and
	// call the "onFooBar" method.
	function triggerMethod(event) {
	  for (var _len = arguments.length, args = Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
	    args[_key - 1] = arguments[_key];
	  }

	  // get the method name from the event name
	  var methodName = getOnMethodName(event);
	  var method = getOption.call(this, methodName);
	  var result = void 0;

	  // call the onMethodName if it exists
	  if (_.isFunction(method)) {
	    // pass all args, except the event name
	    result = method.apply(this, args);
	  }

	  // trigger the event
	  this.trigger.apply(this, arguments);

	  return result;
	}

	// triggerMethodOn invokes triggerMethod on a specific context
	//
	// e.g. `Marionette.triggerMethodOn(view, 'show')`
	// will trigger a "show" event or invoke onShow the view.
	function triggerMethodOn(context) {
	  for (var _len2 = arguments.length, args = Array(_len2 > 1 ? _len2 - 1 : 0), _key2 = 1; _key2 < _len2; _key2++) {
	    args[_key2 - 1] = arguments[_key2];
	  }

	  if (_.isFunction(context.triggerMethod)) {
	    return context.triggerMethod.apply(context, args);
	  }

	  return triggerMethod.apply(context, args);
	}

	// Trigger method on children unless a pure Backbone.View
	function triggerMethodChildren(view, event, shouldTrigger) {
	  if (!view._getImmediateChildren) {
	    return;
	  }
	  _.each(view._getImmediateChildren(), function (child) {
	    if (!shouldTrigger(child)) {
	      return;
	    }
	    triggerMethodOn(child, event, child);
	  });
	}

	function shouldTriggerAttach(view) {
	  return !view._isAttached;
	}

	function shouldAttach(view) {
	  if (!shouldTriggerAttach(view)) {
	    return false;
	  }
	  view._isAttached = true;
	  return true;
	}

	function shouldTriggerDetach(view) {
	  return view._isAttached;
	}

	function shouldDetach(view) {
	  if (!shouldTriggerDetach(view)) {
	    return false;
	  }
	  view._isAttached = false;
	  return true;
	}

	function triggerDOMRefresh(view) {
	  if (view._isAttached && view._isRendered) {
	    triggerMethodOn(view, 'dom:refresh', view);
	  }
	}

	function handleBeforeAttach() {
	  triggerMethodChildren(this, 'before:attach', shouldTriggerAttach);
	}

	function handleAttach() {
	  triggerMethodChildren(this, 'attach', shouldAttach);
	  triggerDOMRefresh(this);
	}

	function handleBeforeDetach() {
	  triggerMethodChildren(this, 'before:detach', shouldTriggerDetach);
	}

	function handleDetach() {
	  triggerMethodChildren(this, 'detach', shouldDetach);
	}

	function handleRender() {
	  triggerDOMRefresh(this);
	}

	// Monitor a view's state, propagating attach/detach events to children and firing dom:refresh
	// whenever a rendered view is attached or an attached view is rendered.
	function monitorViewEvents(view) {
	  if (view._areViewEventsMonitored) {
	    return;
	  }

	  view._areViewEventsMonitored = true;

	  view.on({
	    'before:attach': handleBeforeAttach,
	    'attach': handleAttach,
	    'before:detach': handleBeforeDetach,
	    'detach': handleDetach,
	    'render': handleRender
	  });
	}

	var errorProps = ['description', 'fileName', 'lineNumber', 'name', 'message', 'number'];

	var MarionetteError = extend.call(Error, {
	  urlRoot: 'http://marionettejs.com/docs/v' + version + '/',

	  constructor: function constructor(message, options) {
	    if (_.isObject(message)) {
	      options = message;
	      message = options.message;
	    } else if (!options) {
	      options = {};
	    }

	    var error = Error.call(this, message);
	    _.extend(this, _.pick(error, errorProps), _.pick(options, errorProps));

	    this.captureStackTrace();

	    if (options.url) {
	      this.url = this.urlRoot + options.url;
	    }
	  },
	  captureStackTrace: function captureStackTrace() {
	    if (Error.captureStackTrace) {
	      Error.captureStackTrace(this, MarionetteError);
	    }
	  },
	  toString: function toString() {
	    return this.name + ': ' + this.message + (this.url ? ' See: ' + this.url : '');
	  }
	});

	MarionetteError.extend = extend;

	// Bind/unbind the event to handlers specified as a string of
	// handler names on the target object
	function bindFromStrings(target, entity, evt, methods, actionName) {
	  var methodNames = methods.split(/\s+/);

	  _.each(methodNames, function (methodName) {
	    var method = target[methodName];
	    if (!method) {
	      throw new MarionetteError('Method "' + methodName + '" was configured as an event handler, but does not exist.');
	    }

	    target[actionName](entity, evt, method);
	  });
	}

	// generic looping function
	function iterateEvents(target, entity, bindings, actionName) {
	  if (!entity || !bindings) {
	    return;
	  }

	  // type-check bindings
	  if (!_.isObject(bindings)) {
	    throw new MarionetteError({
	      message: 'Bindings must be an object.',
	      url: 'marionette.functions.html#marionettebindevents'
	    });
	  }

	  // iterate the bindings and bind/unbind them
	  _.each(bindings, function (method, evt) {

	    // allow for a list of method names as a string
	    if (_.isString(method)) {
	      bindFromStrings(target, entity, evt, method, actionName);
	      return;
	    }

	    target[actionName](entity, evt, method);
	  });
	}

	function bindEvents(entity, bindings) {
	  iterateEvents(this, entity, bindings, 'listenTo');
	  return this;
	}

	function unbindEvents(entity, bindings) {
	  iterateEvents(this, entity, bindings, 'stopListening');
	  return this;
	}

	function iterateReplies(target, channel, bindings, actionName) {
	  if (!channel || !bindings) {
	    return;
	  }

	  // type-check bindings
	  if (!_.isObject(bindings)) {
	    throw new MarionetteError({
	      message: 'Bindings must be an object.',
	      url: 'marionette.functions.html#marionettebindrequests'
	    });
	  }

	  var normalizedRadioRequests = normalizeMethods.call(target, bindings);

	  channel[actionName](normalizedRadioRequests, target);
	}

	function bindRequests(channel, bindings) {
	  iterateReplies(this, channel, bindings, 'reply');
	  return this;
	}

	function unbindRequests(channel, bindings) {
	  iterateReplies(this, channel, bindings, 'stopReplying');
	  return this;
	}

	// Internal utility for setting options consistently across Mn
	var setOptions = function setOptions() {
	  for (var _len = arguments.length, args = Array(_len), _key = 0; _key < _len; _key++) {
	    args[_key] = arguments[_key];
	  }

	  this.options = _.extend.apply(_, [{}, _.result(this, 'options')].concat(args));
	};

	var CommonMixin = {

	  // Imports the "normalizeMethods" to transform hashes of
	  // events=>function references/names to a hash of events=>function references
	  normalizeMethods: normalizeMethods,

	  _setOptions: setOptions,

	  // A handy way to merge passed-in options onto the instance
	  mergeOptions: mergeOptions,

	  // Enable getting options from this or this.options by name.
	  getOption: getOption,

	  // Enable binding view's events from another entity.
	  bindEvents: bindEvents,

	  // Enable unbinding view's events from another entity.
	  unbindEvents: unbindEvents
	};

	// MixinOptions
	// - channelName
	// - radioEvents
	// - radioRequests

	var RadioMixin = {
	  _initRadio: function _initRadio() {
	    var channelName = _.result(this, 'channelName');

	    if (!channelName) {
	      return;
	    }

	    /* istanbul ignore next */
	    if (!Radio) {
	      throw new MarionetteError({
	        name: 'BackboneRadioMissing',
	        message: 'The dependency "backbone.radio" is missing.'
	      });
	    }

	    var channel = this._channel = Radio.channel(channelName);

	    var radioEvents = _.result(this, 'radioEvents');
	    this.bindEvents(channel, radioEvents);

	    var radioRequests = _.result(this, 'radioRequests');
	    this.bindRequests(channel, radioRequests);

	    this.on('destroy', this._destroyRadio);
	  },
	  _destroyRadio: function _destroyRadio() {
	    this._channel.stopReplying(null, null, this);
	  },
	  getChannel: function getChannel() {
	    return this._channel;
	  },


	  // Proxy `bindEvents`
	  bindEvents: bindEvents,

	  // Proxy `unbindEvents`
	  unbindEvents: unbindEvents,

	  // Proxy `bindRequests`
	  bindRequests: bindRequests,

	  // Proxy `unbindRequests`
	  unbindRequests: unbindRequests

	};

	var ClassOptions = ['channelName', 'radioEvents', 'radioRequests'];

	// A Base Class that other Classes should descend from.
	// Object borrows many conventions and utilities from Backbone.
	var MarionetteObject = function MarionetteObject(options) {
	  this._setOptions(options);
	  this.mergeOptions(options, ClassOptions);
	  this.cid = _.uniqueId(this.cidPrefix);
	  this._initRadio();
	  this.initialize.apply(this, arguments);
	};

	MarionetteObject.extend = extend;

	// Object Methods
	// --------------

	// Ensure it can trigger events with Backbone.Events
	_.extend(MarionetteObject.prototype, Backbone.Events, CommonMixin, RadioMixin, {
	  cidPrefix: 'mno',

	  // for parity with Marionette.AbstractView lifecyle
	  _isDestroyed: false,

	  isDestroyed: function isDestroyed() {
	    return this._isDestroyed;
	  },


	  //this is a noop method intended to be overridden by classes that extend from this base
	  initialize: function initialize() {},
	  destroy: function destroy() {
	    if (this._isDestroyed) {
	      return this;
	    }

	    for (var _len = arguments.length, args = Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }

	    this.triggerMethod.apply(this, ['before:destroy', this].concat(args));

	    this._isDestroyed = true;
	    this.triggerMethod.apply(this, ['destroy', this].concat(args));
	    this.stopListening();

	    return this;
	  },


	  triggerMethod: triggerMethod
	});

	// Manage templates stored in `<script>` blocks,
	// caching them for faster access.
	var TemplateCache = function TemplateCache(templateId) {
	  this.templateId = templateId;
	};

	// TemplateCache object-level methods. Manage the template
	// caches from these method calls instead of creating
	// your own TemplateCache instances
	_.extend(TemplateCache, {
	  templateCaches: {},

	  // Get the specified template by id. Either
	  // retrieves the cached version, or loads it
	  // from the DOM.
	  get: function get(templateId, options) {
	    var cachedTemplate = this.templateCaches[templateId];

	    if (!cachedTemplate) {
	      cachedTemplate = new TemplateCache(templateId);
	      this.templateCaches[templateId] = cachedTemplate;
	    }

	    return cachedTemplate.load(options);
	  },


	  // Clear templates from the cache. If no arguments
	  // are specified, clears all templates:
	  // `clear()`
	  //
	  // If arguments are specified, clears each of the
	  // specified templates from the cache:
	  // `clear("#t1", "#t2", "...")`
	  clear: function clear() {
	    var i = void 0;

	    for (var _len = arguments.length, args = Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }

	    var length = args.length;

	    if (length > 0) {
	      for (i = 0; i < length; i++) {
	        delete this.templateCaches[args[i]];
	      }
	    } else {
	      this.templateCaches = {};
	    }
	  }
	});

	// TemplateCache instance methods, allowing each
	// template cache object to manage its own state
	// and know whether or not it has been loaded
	_.extend(TemplateCache.prototype, {

	  // Internal method to load the template
	  load: function load(options) {
	    // Guard clause to prevent loading this template more than once
	    if (this.compiledTemplate) {
	      return this.compiledTemplate;
	    }

	    // Load the template and compile it
	    var template = this.loadTemplate(this.templateId, options);
	    this.compiledTemplate = this.compileTemplate(template, options);

	    return this.compiledTemplate;
	  },


	  // Load a template from the DOM, by default. Override
	  // this method to provide your own template retrieval
	  // For asynchronous loading with AMD/RequireJS, consider
	  // using a template-loader plugin as described here:
	  // https://github.com/marionettejs/backbone.marionette/wiki/Using-marionette-with-requirejs
	  loadTemplate: function loadTemplate(templateId, options) {
	    var $template = Backbone.$(templateId);

	    if (!$template.length) {
	      throw new MarionetteError({
	        name: 'NoTemplateError',
	        message: 'Could not find template: "' + templateId + '"'
	      });
	    }
	    return $template.html();
	  },


	  // Pre-compile the template before caching it. Override
	  // this method if you do not need to pre-compile a template
	  // (JST / RequireJS for example) or if you want to change
	  // the template engine used (Handebars, etc).
	  compileTemplate: function compileTemplate(rawTemplate, options) {
	    return _.template(rawTemplate, options);
	  }
	});

	var _invoke = _.invokeMap || _.invoke;

	function _toConsumableArray(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } else { return Array.from(arr); } }

	// MixinOptions
	// - behaviors

	// Takes care of getting the behavior class
	// given options and a key.
	// If a user passes in options.behaviorClass
	// default to using that.
	// If a user passes in a Behavior Class directly, use that
	// Otherwise delegate the lookup to the users `behaviorsLookup` implementation.
	function getBehaviorClass(options, key) {
	  if (options.behaviorClass) {
	    return options.behaviorClass;
	    //treat functions as a Behavior constructor
	  } else if (_.isFunction(options)) {
	    return options;
	  }

	  // behaviorsLookup can be either a flat object or a method
	  if (_.isFunction(Marionette.Behaviors.behaviorsLookup)) {
	    return Marionette.Behaviors.behaviorsLookup(options, key)[key];
	  }

	  return Marionette.Behaviors.behaviorsLookup[key];
	}

	// Iterate over the behaviors object, for each behavior
	// instantiate it and get its grouped behaviors.
	// This accepts a list of behaviors in either an object or array form
	function parseBehaviors(view, behaviors) {
	  return _.chain(behaviors).map(function (options, key) {
	    var BehaviorClass = getBehaviorClass(options, key);
	    //if we're passed a class directly instead of an object
	    var _options = options === BehaviorClass ? {} : options;
	    var behavior = new BehaviorClass(_options, view);
	    var nestedBehaviors = parseBehaviors(view, _.result(behavior, 'behaviors'));

	    return [behavior].concat(nestedBehaviors);
	  }).flatten().value();
	}

	var BehaviorsMixin = {
	  _initBehaviors: function _initBehaviors() {
	    var behaviors = _.result(this, 'behaviors');

	    // Behaviors defined on a view can be a flat object literal
	    // or it can be a function that returns an object.
	    this._behaviors = _.isObject(behaviors) ? parseBehaviors(this, behaviors) : {};
	  },
	  _getBehaviorTriggers: function _getBehaviorTriggers() {
	    var triggers = _invoke(this._behaviors, 'getTriggers');
	    return _.extend.apply(_, [{}].concat(_toConsumableArray(triggers)));
	  },
	  _getBehaviorEvents: function _getBehaviorEvents() {
	    var events = _invoke(this._behaviors, 'getEvents');
	    return _.extend.apply(_, [{}].concat(_toConsumableArray(events)));
	  },


	  // proxy behavior $el to the view's $el.
	  _proxyBehaviorViewProperties: function _proxyBehaviorViewProperties() {
	    _invoke(this._behaviors, 'proxyViewProperties');
	  },


	  // delegate modelEvents and collectionEvents
	  _delegateBehaviorEntityEvents: function _delegateBehaviorEntityEvents() {
	    _invoke(this._behaviors, 'delegateEntityEvents');
	  },


	  // undelegate modelEvents and collectionEvents
	  _undelegateBehaviorEntityEvents: function _undelegateBehaviorEntityEvents() {
	    _invoke(this._behaviors, 'undelegateEntityEvents');
	  },
	  _destroyBehaviors: function _destroyBehaviors(args) {
	    // Call destroy on each behavior after
	    // destroying the view.
	    // This unbinds event listeners
	    // that behaviors have registered for.
	    _invoke.apply(undefined, [this._behaviors, 'destroy'].concat(_toConsumableArray(args)));
	  },
	  _bindBehaviorUIElements: function _bindBehaviorUIElements() {
	    _invoke(this._behaviors, 'bindUIElements');
	  },
	  _unbindBehaviorUIElements: function _unbindBehaviorUIElements() {
	    _invoke(this._behaviors, 'unbindUIElements');
	  },
	  _triggerEventOnBehaviors: function _triggerEventOnBehaviors() {
	    var behaviors = this._behaviors;
	    // Use good ol' for as this is a very hot function
	    for (var i = 0, length = behaviors && behaviors.length; i < length; i++) {
	      triggerMethod.apply(behaviors[i], arguments);
	    }
	  }
	};

	// MixinOptions
	// - collectionEvents
	// - modelEvents

	var DelegateEntityEventsMixin = {
	  // Handle `modelEvents`, and `collectionEvents` configuration
	  _delegateEntityEvents: function _delegateEntityEvents(model, collection) {
	    this._undelegateEntityEvents(model, collection);

	    var modelEvents = _.result(this, 'modelEvents');
	    bindEvents.call(this, model, modelEvents);

	    var collectionEvents = _.result(this, 'collectionEvents');
	    bindEvents.call(this, collection, collectionEvents);
	  },
	  _undelegateEntityEvents: function _undelegateEntityEvents(model, collection) {
	    var modelEvents = _.result(this, 'modelEvents');
	    unbindEvents.call(this, model, modelEvents);

	    var collectionEvents = _.result(this, 'collectionEvents');
	    unbindEvents.call(this, collection, collectionEvents);
	  }
	};

	// Borrow event splitter from Backbone
	var delegateEventSplitter = /^(\S+)\s*(.*)$/;

	function uniqueName(eventName, selector) {
	  return [eventName + _.uniqueId('.evt'), selector].join(' ');
	}

	// Set event name to be namespaced using a unique index
	// to generate a non colliding event namespace
	// http://api.jquery.com/event.namespace/
	var getUniqueEventName = function getUniqueEventName(eventName) {
	  var match = eventName.match(delegateEventSplitter);
	  return uniqueName(match[1], match[2]);
	};

	// Internal method to create an event handler for a given `triggerDef` like
	// 'click:foo'
	function buildViewTrigger(view, triggerDef) {
	  if (_.isString(triggerDef)) {
	    triggerDef = { event: triggerDef };
	  }

	  var eventName = triggerDef.event;
	  var shouldPreventDefault = triggerDef.preventDefault !== false;
	  var shouldStopPropagation = triggerDef.stopPropagation !== false;

	  return function (e) {
	    if (shouldPreventDefault) {
	      e.preventDefault();
	    }

	    if (shouldStopPropagation) {
	      e.stopPropagation();
	    }

	    view.triggerMethod(eventName, view);
	  };
	}

	var TriggersMixin = {

	  // Configure `triggers` to forward DOM events to view
	  // events. `triggers: {"click .foo": "do:foo"}`
	  _getViewTriggers: function _getViewTriggers(view, triggers) {
	    // Configure the triggers, prevent default
	    // action and stop propagation of DOM events
	    return _.reduce(triggers, function (events, value, key) {
	      key = getUniqueEventName(key);
	      events[key] = buildViewTrigger(view, value);
	      return events;
	    }, {});
	  }
	};

	// allows for the use of the @ui. syntax within
	// a given key for triggers and events
	// swaps the @ui with the associated selector.
	// Returns a new, non-mutated, parsed events hash.
	var _normalizeUIKeys = function _normalizeUIKeys(hash, ui) {
	  return _.reduce(hash, function (memo, val, key) {
	    var normalizedKey = _normalizeUIString(key, ui);
	    memo[normalizedKey] = val;
	    return memo;
	  }, {});
	};

	// utility method for parsing @ui. syntax strings
	// into associated selector
	var _normalizeUIString = function _normalizeUIString(uiString, ui) {
	  return uiString.replace(/@ui\.[a-zA-Z-_$0-9]*/g, function (r) {
	    return ui[r.slice(4)];
	  });
	};

	// allows for the use of the @ui. syntax within
	// a given value for regions
	// swaps the @ui with the associated selector
	var _normalizeUIValues = function _normalizeUIValues(hash, ui, properties) {
	  _.each(hash, function (val, key) {
	    if (_.isString(val)) {
	      hash[key] = _normalizeUIString(val, ui);
	    } else if (_.isObject(val) && _.isArray(properties)) {
	      _.extend(val, _normalizeUIValues(_.pick(val, properties), ui));
	      /* Value is an object, and we got an array of embedded property names to normalize. */
	      _.each(properties, function (property) {
	        var propertyVal = val[property];
	        if (_.isString(propertyVal)) {
	          val[property] = _normalizeUIString(propertyVal, ui);
	        }
	      });
	    }
	  });
	  return hash;
	};

	var UIMixin = {

	  // normalize the keys of passed hash with the views `ui` selectors.
	  // `{"@ui.foo": "bar"}`
	  normalizeUIKeys: function normalizeUIKeys(hash) {
	    var uiBindings = this._getUIBindings();
	    return _normalizeUIKeys(hash, uiBindings);
	  },


	  // normalize the passed string with the views `ui` selectors.
	  // `"@ui.bar"`
	  normalizeUIString: function normalizeUIString(uiString) {
	    var uiBindings = this._getUIBindings();
	    return _normalizeUIString(uiString, uiBindings);
	  },


	  // normalize the values of passed hash with the views `ui` selectors.
	  // `{foo: "@ui.bar"}`
	  normalizeUIValues: function normalizeUIValues(hash, properties) {
	    var uiBindings = this._getUIBindings();
	    return _normalizeUIValues(hash, uiBindings, properties);
	  },
	  _getUIBindings: function _getUIBindings() {
	    var uiBindings = _.result(this, '_uiBindings');
	    var ui = _.result(this, 'ui');
	    return uiBindings || ui;
	  },


	  // This method binds the elements specified in the "ui" hash inside the view's code with
	  // the associated jQuery selectors.
	  _bindUIElements: function _bindUIElements() {
	    var _this = this;

	    if (!this.ui) {
	      return;
	    }

	    // store the ui hash in _uiBindings so they can be reset later
	    // and so re-rendering the view will be able to find the bindings
	    if (!this._uiBindings) {
	      this._uiBindings = this.ui;
	    }

	    // get the bindings result, as a function or otherwise
	    var bindings = _.result(this, '_uiBindings');

	    // empty the ui so we don't have anything to start with
	    this._ui = {};

	    // bind each of the selectors
	    _.each(bindings, function (selector, key) {
	      _this._ui[key] = _this.$(selector);
	    });

	    this.ui = this._ui;
	  },
	  _unbindUIElements: function _unbindUIElements() {
	    var _this2 = this;

	    if (!this.ui || !this._uiBindings) {
	      return;
	    }

	    // delete all of the existing ui bindings
	    _.each(this.ui, function ($el, name) {
	      delete _this2.ui[name];
	    });

	    // reset the ui element to the original bindings configuration
	    this.ui = this._uiBindings;
	    delete this._uiBindings;
	    delete this._ui;
	  },
	  _getUI: function _getUI(name) {
	    return this._ui[name];
	  }
	};

	// MixinOptions
	// - behaviors
	// - childViewEventPrefix
	// - childViewEvents
	// - childViewTriggers
	// - collectionEvents
	// - modelEvents
	// - triggers
	// - ui


	var ViewMixin = {
	  supportsRenderLifecycle: true,
	  supportsDestroyLifecycle: true,

	  _isDestroyed: false,

	  isDestroyed: function isDestroyed() {
	    return !!this._isDestroyed;
	  },


	  _isRendered: false,

	  isRendered: function isRendered() {
	    return !!this._isRendered;
	  },


	  _isAttached: false,

	  isAttached: function isAttached() {
	    return !!this._isAttached;
	  },


	  // Overriding Backbone.View's `delegateEvents` to handle
	  // `events` and `triggers`
	  delegateEvents: function delegateEvents(eventsArg) {

	    this._proxyBehaviorViewProperties();
	    this._buildEventProxies();

	    var viewEvents = this._getEvents(eventsArg);

	    if (typeof eventsArg === 'undefined') {
	      this.events = viewEvents;
	    }

	    var combinedEvents = _.extend({}, this._getBehaviorEvents(), viewEvents, this._getBehaviorTriggers(), this.getTriggers());

	    Backbone.View.prototype.delegateEvents.call(this, combinedEvents);

	    return this;
	  },
	  _getEvents: function _getEvents(eventsArg) {
	    var events = eventsArg || this.events;

	    if (_.isFunction(events)) {
	      return this.normalizeUIKeys(events.call(this));
	    }

	    return this.normalizeUIKeys(events);
	  },


	  // Configure `triggers` to forward DOM events to view
	  // events. `triggers: {"click .foo": "do:foo"}`
	  getTriggers: function getTriggers() {
	    if (!this.triggers) {
	      return;
	    }

	    // Allow `triggers` to be configured as a function
	    var triggers = this.normalizeUIKeys(_.result(this, 'triggers'));

	    // Configure the triggers, prevent default
	    // action and stop propagation of DOM events
	    return this._getViewTriggers(this, triggers);
	  },


	  // Handle `modelEvents`, and `collectionEvents` configuration
	  delegateEntityEvents: function delegateEntityEvents() {
	    this._delegateEntityEvents(this.model, this.collection);

	    // bind each behaviors model and collection events
	    this._delegateBehaviorEntityEvents();

	    return this;
	  },


	  // Handle unbinding `modelEvents`, and `collectionEvents` configuration
	  undelegateEntityEvents: function undelegateEntityEvents() {
	    this._undelegateEntityEvents(this.model, this.collection);

	    // unbind each behaviors model and collection events
	    this._undelegateBehaviorEntityEvents();

	    return this;
	  },


	  // Internal helper method to verify whether the view hasn't been destroyed
	  _ensureViewIsIntact: function _ensureViewIsIntact() {
	    if (this._isDestroyed) {
	      throw new MarionetteError({
	        name: 'ViewDestroyedError',
	        message: 'View (cid: "' + this.cid + '") has already been destroyed and cannot be used.'
	      });
	    }
	  },


	  // Handle destroying the view and its children.
	  destroy: function destroy() {
	    if (this._isDestroyed) {
	      return this;
	    }
	    var shouldTriggerDetach = !!this._isAttached;

	    for (var _len = arguments.length, args = Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }

	    this.triggerMethod.apply(this, ['before:destroy', this].concat(args));
	    if (shouldTriggerDetach) {
	      this.triggerMethod('before:detach', this);
	    }

	    // unbind UI elements
	    this.unbindUIElements();

	    // remove the view from the DOM
	    // https://github.com/jashkenas/backbone/blob/1.2.3/backbone.js#L1235
	    this._removeElement();

	    if (shouldTriggerDetach) {
	      this._isAttached = false;
	      this.triggerMethod('detach', this);
	    }

	    // remove children after the remove to prevent extra paints
	    this._removeChildren();

	    this._destroyBehaviors(args);

	    this._isDestroyed = true;
	    this._isRendered = false;
	    this.triggerMethod.apply(this, ['destroy', this].concat(args));

	    this.stopListening();

	    return this;
	  },
	  bindUIElements: function bindUIElements() {
	    this._bindUIElements();
	    this._bindBehaviorUIElements();

	    return this;
	  },


	  // This method unbinds the elements specified in the "ui" hash
	  unbindUIElements: function unbindUIElements() {
	    this._unbindUIElements();
	    this._unbindBehaviorUIElements();

	    return this;
	  },
	  getUI: function getUI(name) {
	    this._ensureViewIsIntact();
	    return this._getUI(name);
	  },


	  // used as the prefix for child view events
	  // that are forwarded through the layoutview
	  childViewEventPrefix: 'childview',

	  // import the `triggerMethod` to trigger events with corresponding
	  // methods if the method exists
	  triggerMethod: function triggerMethod$$() {
	    var ret = triggerMethod.apply(this, arguments);

	    this._triggerEventOnBehaviors.apply(this, arguments);
	    this._triggerEventOnParentLayout.apply(this, arguments);

	    return ret;
	  },


	  // Cache `childViewEvents` and `childViewTriggers`
	  _buildEventProxies: function _buildEventProxies() {
	    this._childViewEvents = _.result(this, 'childViewEvents');
	    this._childViewTriggers = _.result(this, 'childViewTriggers');
	  },
	  _triggerEventOnParentLayout: function _triggerEventOnParentLayout() {
	    var layoutView = this._parentView();
	    if (!layoutView) {
	      return;
	    }

	    layoutView._childViewEventHandler.apply(layoutView, arguments);
	  },


	  // Walk the _parent tree until we find a view (if one exists).
	  // Returns the parent view hierarchically closest to this view.
	  _parentView: function _parentView() {
	    var parent = this._parent;

	    while (parent) {
	      if (parent instanceof View) {
	        return parent;
	      }
	      parent = parent._parent;
	    }
	  },
	  _childViewEventHandler: function _childViewEventHandler(eventName) {
	    var childViewEvents = this.normalizeMethods(this._childViewEvents);

	    // call collectionView childViewEvent if defined

	    for (var _len2 = arguments.length, args = Array(_len2 > 1 ? _len2 - 1 : 0), _key2 = 1; _key2 < _len2; _key2++) {
	      args[_key2 - 1] = arguments[_key2];
	    }

	    if (typeof childViewEvents !== 'undefined' && _.isFunction(childViewEvents[eventName])) {
	      childViewEvents[eventName].apply(this, args);
	    }

	    // use the parent view's proxyEvent handlers
	    var childViewTriggers = this._childViewTriggers;

	    // Call the event with the proxy name on the parent layout
	    if (childViewTriggers && _.isString(childViewTriggers[eventName])) {
	      this.triggerMethod.apply(this, [childViewTriggers[eventName]].concat(args));
	    }

	    var prefix = _.result(this, 'childViewEventPrefix');

	    if (prefix !== false) {
	      var childEventName = prefix + ':' + eventName;

	      this.triggerMethod.apply(this, [childEventName].concat(args));
	    }
	  }
	};

	_.extend(ViewMixin, BehaviorsMixin, CommonMixin, DelegateEntityEventsMixin, TriggersMixin, UIMixin);

	function destroyBackboneView(view) {
	  if (!view.supportsDestroyLifecycle) {
	    triggerMethodOn(view, 'before:destroy', view);
	  }

	  var shouldTriggerDetach = !!view._isAttached;

	  if (shouldTriggerDetach) {
	    triggerMethodOn(view, 'before:detach', view);
	  }

	  view.remove();

	  if (shouldTriggerDetach) {
	    view._isAttached = false;
	    triggerMethodOn(view, 'detach', view);
	  }

	  view._isDestroyed = true;

	  if (!view.supportsDestroyLifecycle) {
	    triggerMethodOn(view, 'destroy', view);
	  }
	}

	var ClassOptions$2 = ['allowMissingEl', 'parentEl', 'replaceElement'];

	var Region = MarionetteObject.extend({
	  cidPrefix: 'mnr',
	  replaceElement: false,
	  _isReplaced: false,

	  constructor: function constructor(options) {
	    this._setOptions(options);

	    this.mergeOptions(options, ClassOptions$2);

	    // getOption necessary because options.el may be passed as undefined
	    this._initEl = this.el = this.getOption('el');

	    // Handle when this.el is passed in as a $ wrapped element.
	    this.el = this.el instanceof Backbone.$ ? this.el[0] : this.el;

	    if (!this.el) {
	      throw new MarionetteError({
	        name: 'NoElError',
	        message: 'An "el" must be specified for a region.'
	      });
	    }

	    this.$el = this.getEl(this.el);
	    MarionetteObject.call(this, options);
	  },


	  // Displays a backbone view instance inside of the region. Handles calling the `render`
	  // method for you. Reads content directly from the `el` attribute. The `preventDestroy`
	  // option can be used to prevent a view from the old view being destroyed on show.
	  show: function show(view, options) {
	    if (!this._ensureElement(options)) {
	      return;
	    }
	    this._ensureView(view);
	    if (view === this.currentView) {
	      return this;
	    }

	    this.triggerMethod('before:show', this, view, options);

	    monitorViewEvents(view);

	    this.empty(options);

	    // We need to listen for if a view is destroyed in a way other than through the region.
	    // If this happens we need to remove the reference to the currentView since once a view
	    // has been destroyed we can not reuse it.
	    view.on('destroy', this._empty, this);

	    // Make this region the view's parent.
	    // It's important that this parent binding happens before rendering so that any events
	    // the child may trigger during render can also be triggered on the child's ancestor views.
	    view._parent = this;

	    this._renderView(view);

	    this._attachView(view, options);

	    this.triggerMethod('show', this, view, options);
	    return this;
	  },
	  _renderView: function _renderView(view) {
	    if (view._isRendered) {
	      return;
	    }

	    if (!view.supportsRenderLifecycle) {
	      triggerMethodOn(view, 'before:render', view);
	    }

	    view.render();

	    if (!view.supportsRenderLifecycle) {
	      view._isRendered = true;
	      triggerMethodOn(view, 'render', view);
	    }
	  },
	  _attachView: function _attachView(view) {
	    var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

	    var shouldTriggerAttach = !view._isAttached && isNodeAttached(this.el);
	    var shouldReplaceEl = typeof options.replaceElement === 'undefined' ? !!_.result(this, 'replaceElement') : !!options.replaceElement;

	    if (shouldTriggerAttach) {
	      triggerMethodOn(view, 'before:attach', view);
	    }

	    if (shouldReplaceEl) {
	      this._replaceEl(view);
	    } else {
	      this.attachHtml(view);
	    }

	    if (shouldTriggerAttach) {
	      view._isAttached = true;
	      triggerMethodOn(view, 'attach', view);
	    }

	    this.currentView = view;
	  },
	  _ensureElement: function _ensureElement() {
	    var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

	    if (!_.isObject(this.el)) {
	      this.$el = this.getEl(this.el);
	      this.el = this.$el[0];
	    }

	    if (!this.$el || this.$el.length === 0) {
	      var allowMissingEl = typeof options.allowMissingEl === 'undefined' ? !!_.result(this, 'allowMissingEl') : !!options.allowMissingEl;

	      if (allowMissingEl) {
	        return false;
	      } else {
	        throw new MarionetteError('An "el" must exist in DOM for this region ' + this.cid);
	      }
	    }
	    return true;
	  },
	  _ensureView: function _ensureView(view) {
	    if (!view) {
	      throw new MarionetteError({
	        name: 'ViewNotValid',
	        message: 'The view passed is undefined and therefore invalid. You must pass a view instance to show.'
	      });
	    }

	    if (view._isDestroyed) {
	      throw new MarionetteError({
	        name: 'ViewDestroyedError',
	        message: 'View (cid: "' + view.cid + '") has already been destroyed and cannot be used.'
	      });
	    }
	  },


	  // Override this method to change how the region finds the DOM element that it manages. Return
	  // a jQuery selector object scoped to a provided parent el or the document if none exists.
	  getEl: function getEl(el) {
	    return Backbone.$(el, _.result(this, 'parentEl'));
	  },
	  _replaceEl: function _replaceEl(view) {
	    // always restore the el to ensure the regions el is present before replacing
	    this._restoreEl();

	    var parent = this.el.parentNode;

	    parent.replaceChild(view.el, this.el);
	    this._isReplaced = true;
	  },


	  // Restore the region's element in the DOM.
	  _restoreEl: function _restoreEl() {
	    // There is nothing to replace
	    if (!this._isReplaced) {
	      return;
	    }

	    var view = this.currentView;

	    if (!view) {
	      return;
	    }

	    var parent = view.el.parentNode;

	    if (!parent) {
	      return;
	    }

	    parent.replaceChild(this.el, view.el);
	    this._isReplaced = false;
	  },


	  // Check to see if the region's el was replaced.
	  isReplaced: function isReplaced() {
	    return !!this._isReplaced;
	  },


	  // Override this method to change how the new view is appended to the `$el` that the
	  // region is managing
	  attachHtml: function attachHtml(view) {
	    this.el.appendChild(view.el);
	  },


	  // Destroy the current view, if there is one. If there is no current view, it does
	  // nothing and returns immediately.
	  empty: function empty() {
	    var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : { allowMissingEl: true };

	    var view = this.currentView;

	    // If there is no view in the region we should only detach current html
	    if (!view) {
	      if (this._ensureElement(options)) {
	        this.detachHtml();
	      }
	      return this;
	    }

	    var shouldDestroy = !options.preventDestroy;

	    if (!shouldDestroy) {
	      deprecate('The preventDestroy option is deprecated. Use Region#detachView');
	    }

	    this._empty(view, shouldDestroy);
	    return this;
	  },
	  _empty: function _empty(view, shouldDestroy) {
	    view.off('destroy', this._empty, this);
	    this.triggerMethod('before:empty', this, view);

	    this._restoreEl();

	    delete this.currentView;

	    if (!view._isDestroyed) {
	      this._removeView(view, shouldDestroy);
	      delete view._parent;
	    }

	    this.triggerMethod('empty', this, view);
	  },
	  _removeView: function _removeView(view, shouldDestroy) {
	    if (!shouldDestroy) {
	      this._detachView(view);
	      return;
	    }

	    if (view.destroy) {
	      view.destroy();
	    } else {
	      destroyBackboneView(view);
	    }
	  },
	  detachView: function detachView() {
	    var view = this.currentView;

	    if (!view) {
	      return;
	    }

	    this._empty(view);

	    return view;
	  },
	  _detachView: function _detachView(view) {
	    var shouldTriggerDetach = !!view._isAttached;
	    if (shouldTriggerDetach) {
	      triggerMethodOn(view, 'before:detach', view);
	    }

	    this.detachHtml();

	    if (shouldTriggerDetach) {
	      view._isAttached = false;
	      triggerMethodOn(view, 'detach', view);
	    }
	  },


	  // Override this method to change how the region detaches current content
	  detachHtml: function detachHtml() {
	    this.$el.contents().detach();
	  },


	  // Checks whether a view is currently present within the region. Returns `true` if there is
	  // and `false` if no view is present.
	  hasView: function hasView() {
	    return !!this.currentView;
	  },


	  // Reset the region by destroying any existing view and clearing out the cached `$el`.
	  // The next time a view is shown via this region, the region will re-query the DOM for
	  // the region's `el`.
	  reset: function reset(options) {
	    this.empty(options);

	    if (this.$el) {
	      this.el = this._initEl;
	    }

	    delete this.$el;
	    return this;
	  },
	  destroy: function destroy(options) {
	    this.reset(options);
	    return MarionetteObject.prototype.destroy.apply(this, arguments);
	  }
	});

	// return the region instance from the definition
	function buildRegion (definition, defaults) {
	  if (definition instanceof Region) {
	    return definition;
	  }

	  return buildRegionFromDefinition(definition, defaults);
	}

	function buildRegionFromDefinition(definition, defaults) {
	  var opts = _.extend({}, defaults);

	  if (_.isString(definition)) {
	    _.extend(opts, { el: definition });

	    return buildRegionFromObject(opts);
	  }

	  if (_.isFunction(definition)) {
	    _.extend(opts, { regionClass: definition });

	    return buildRegionFromObject(opts);
	  }

	  if (_.isObject(definition)) {
	    if (definition.selector) {
	      deprecate('The selector option on a Region definition object is deprecated. Use el to pass a selector string');
	    }

	    _.extend(opts, { el: definition.selector }, definition);

	    return buildRegionFromObject(opts);
	  }

	  throw new MarionetteError({
	    message: 'Improper region configuration type.',
	    url: 'marionette.region.html#region-configuration-types'
	  });
	}

	function buildRegionFromObject(definition) {
	  var RegionClass = definition.regionClass;

	  var options = _.omit(definition, 'regionClass');

	  return new RegionClass(options);
	}

	// MixinOptions
	// - regions
	// - regionClass

	var RegionsMixin = {
	  regionClass: Region,

	  // Internal method to initialize the regions that have been defined in a
	  // `regions` attribute on this View.
	  _initRegions: function _initRegions() {

	    // init regions hash
	    this.regions = this.regions || {};
	    this._regions = {};

	    this.addRegions(_.result(this, 'regions'));
	  },


	  // Internal method to re-initialize all of the regions by updating
	  // the `el` that they point to
	  _reInitRegions: function _reInitRegions() {
	    _invoke(this._regions, 'reset');
	  },


	  // Add a single region, by name, to the View
	  addRegion: function addRegion(name, definition) {
	    var regions = {};
	    regions[name] = definition;
	    return this.addRegions(regions)[name];
	  },


	  // Add multiple regions as a {name: definition, name2: def2} object literal
	  addRegions: function addRegions(regions) {
	    // If there's nothing to add, stop here.
	    if (_.isEmpty(regions)) {
	      return;
	    }

	    // Normalize region selectors hash to allow
	    // a user to use the @ui. syntax.
	    regions = this.normalizeUIValues(regions, ['selector', 'el']);

	    // Add the regions definitions to the regions property
	    this.regions = _.extend({}, this.regions, regions);

	    return this._addRegions(regions);
	  },


	  // internal method to build and add regions
	  _addRegions: function _addRegions(regionDefinitions) {
	    var _this = this;

	    var defaults = {
	      regionClass: this.regionClass,
	      parentEl: _.partial(_.result, this, 'el')
	    };

	    return _.reduce(regionDefinitions, function (regions, definition, name) {
	      regions[name] = buildRegion(definition, defaults);
	      _this._addRegion(regions[name], name);
	      return regions;
	    }, {});
	  },
	  _addRegion: function _addRegion(region, name) {
	    this.triggerMethod('before:add:region', this, name, region);

	    region._parent = this;

	    this._regions[name] = region;

	    this.triggerMethod('add:region', this, name, region);
	  },


	  // Remove a single region from the View, by name
	  removeRegion: function removeRegion(name) {
	    var region = this._regions[name];

	    this._removeRegion(region, name);

	    return region;
	  },


	  // Remove all regions from the View
	  removeRegions: function removeRegions() {
	    var regions = this.getRegions();

	    _.each(this._regions, _.bind(this._removeRegion, this));

	    return regions;
	  },
	  _removeRegion: function _removeRegion(region, name) {
	    this.triggerMethod('before:remove:region', this, name, region);

	    region.destroy();

	    delete this.regions[name];
	    delete this._regions[name];

	    this.triggerMethod('remove:region', this, name, region);
	  },


	  // Empty all regions in the region manager, but
	  // leave them attached
	  emptyRegions: function emptyRegions() {
	    var regions = this.getRegions();
	    _invoke(regions, 'empty');
	    return regions;
	  },


	  // Checks to see if view contains region
	  // Accepts the region name
	  // hasRegion('main')
	  hasRegion: function hasRegion(name) {
	    return !!this.getRegion(name);
	  },


	  // Provides access to regions
	  // Accepts the region name
	  // getRegion('main')
	  getRegion: function getRegion(name) {
	    return this._regions[name];
	  },


	  // Get all regions
	  getRegions: function getRegions() {
	    return _.clone(this._regions);
	  },
	  showChildView: function showChildView(name, view) {
	    var region = this.getRegion(name);

	    for (var _len = arguments.length, args = Array(_len > 2 ? _len - 2 : 0), _key = 2; _key < _len; _key++) {
	      args[_key - 2] = arguments[_key];
	    }

	    return region.show.apply(region, [view].concat(args));
	  },
	  detachChildView: function detachChildView(name) {
	    return this.getRegion(name).detachView();
	  },
	  getChildView: function getChildView(name) {
	    return this.getRegion(name).currentView;
	  }
	};

	// Render a template with data by passing in the template
	// selector and the data to render.
	var Renderer = {

	  // Render a template with data. The `template` parameter is
	  // passed to the `TemplateCache` object to retrieve the
	  // template function. Override this method to provide your own
	  // custom rendering and template handling for all of Marionette.
	  render: function render(template, data) {
	    if (!template) {
	      throw new MarionetteError({
	        name: 'TemplateNotFoundError',
	        message: 'Cannot render the template since its false, null or undefined.'
	      });
	    }

	    var templateFunc = _.isFunction(template) ? template : TemplateCache.get(template);

	    return templateFunc(data);
	  }
	};

	var ClassOptions$1 = ['behaviors', 'childViewEventPrefix', 'childViewEvents', 'childViewTriggers', 'collectionEvents', 'events', 'modelEvents', 'regionClass', 'regions', 'template', 'templateContext', 'triggers', 'ui'];

	// The standard view. Includes view events, automatic rendering
	// of Underscore templates, nested views, and more.
	var View = Backbone.View.extend({
	  constructor: function constructor(options) {
	    this.render = _.bind(this.render, this);

	    this._setOptions(options);

	    this.mergeOptions(options, ClassOptions$1);

	    monitorViewEvents(this);

	    this._initBehaviors();
	    this._initRegions();

	    var args = Array.prototype.slice.call(arguments);
	    args[0] = this.options;
	    Backbone.View.prototype.constructor.apply(this, args);

	    this.delegateEntityEvents();
	  },


	  // Serialize the view's model *or* collection, if
	  // it exists, for the template
	  serializeData: function serializeData() {
	    if (!this.model && !this.collection) {
	      return {};
	    }

	    // If we have a model, we serialize that
	    if (this.model) {
	      return this.serializeModel();
	    }

	    // Otherwise, we serialize the collection,
	    // making it available under the `items` property
	    return {
	      items: this.serializeCollection()
	    };
	  },


	  // Prepares the special `model` property of a view
	  // for being displayed in the template. By default
	  // we simply clone the attributes. Override this if
	  // you need a custom transformation for your view's model
	  serializeModel: function serializeModel() {
	    if (!this.model) {
	      return {};
	    }
	    return _.clone(this.model.attributes);
	  },


	  // Serialize a collection by cloning each of
	  // its model's attributes
	  serializeCollection: function serializeCollection() {
	    if (!this.collection) {
	      return {};
	    }
	    return this.collection.map(function (model) {
	      return _.clone(model.attributes);
	    });
	  },


	  // Overriding Backbone.View's `setElement` to handle
	  // if an el was previously defined. If so, the view might be
	  // rendered or attached on setElement.
	  setElement: function setElement() {
	    var hasEl = !!this.el;

	    Backbone.View.prototype.setElement.apply(this, arguments);

	    if (hasEl) {
	      this._isRendered = !!this.$el.length;
	      this._isAttached = isNodeAttached(this.el);
	    }

	    if (this._isRendered) {
	      this.bindUIElements();
	    }

	    return this;
	  },


	  // Render the view, defaulting to underscore.js templates.
	  // You can override this in your view definition to provide
	  // a very specific rendering for your view. In general, though,
	  // you should override the `Marionette.Renderer` object to
	  // change how Marionette renders views.
	  // Subsequent renders after the first will re-render all nested
	  // views.
	  render: function render() {
	    this._ensureViewIsIntact();

	    this.triggerMethod('before:render', this);

	    // If this is not the first render call, then we need to
	    // re-initialize the `el` for each region
	    if (this._isRendered) {
	      this._reInitRegions();
	    }

	    this._renderTemplate();
	    this.bindUIElements();

	    this._isRendered = true;
	    this.triggerMethod('render', this);

	    return this;
	  },


	  // Internal method to render the template with the serialized data
	  // and template context via the `Marionette.Renderer` object.
	  _renderTemplate: function _renderTemplate() {
	    var template = this.getTemplate();

	    // Allow template-less views
	    if (template === false) {
	      return;
	    }

	    // Add in entity data and template context
	    var data = this.mixinTemplateContext(this.serializeData());

	    // Render and add to el
	    var html = Renderer.render(template, data, this);
	    this.attachElContent(html);
	  },


	  // Get the template for this view
	  // instance. You can set a `template` attribute in the view
	  // definition or pass a `template: "whatever"` parameter in
	  // to the constructor options.
	  getTemplate: function getTemplate() {
	    return this.template;
	  },


	  // Mix in template context methods. Looks for a
	  // `templateContext` attribute, which can either be an
	  // object literal, or a function that returns an object
	  // literal. All methods and attributes from this object
	  // are copies to the object passed in.
	  mixinTemplateContext: function mixinTemplateContext() {
	    var target = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

	    var templateContext = _.result(this, 'templateContext');
	    return _.extend(target, templateContext);
	  },


	  // Attaches the content of a given view.
	  // This method can be overridden to optimize rendering,
	  // or to render in a non standard way.
	  //
	  // For example, using `innerHTML` instead of `$el.html`
	  //
	  // ```js
	  // attachElContent(html) {
	  //   this.el.innerHTML = html;
	  //   return this;
	  // }
	  // ```
	  attachElContent: function attachElContent(html) {
	    this.$el.html(html);

	    return this;
	  },


	  // called by ViewMixin destroy
	  _removeChildren: function _removeChildren() {
	    this.removeRegions();
	  },
	  _getImmediateChildren: function _getImmediateChildren() {
	    return _.chain(this.getRegions()).map('currentView').compact().value();
	  }
	});

	_.extend(View.prototype, ViewMixin, RegionsMixin);

	var methods = ['forEach', 'each', 'map', 'find', 'detect', 'filter', 'select', 'reject', 'every', 'all', 'some', 'any', 'include', 'contains', 'invoke', 'toArray', 'first', 'initial', 'rest', 'last', 'without', 'isEmpty', 'pluck', 'reduce'];

	var emulateCollection = function emulateCollection(object, listProperty) {
	  _.each(methods, function (method) {
	    object[method] = function () {
	      var list = _.values(_.result(this, listProperty));
	      var args = [list].concat(_.toArray(arguments));
	      return _[method].apply(_, args);
	    };
	  });
	};

	// Provide a container to store, retrieve and
	// shut down child views.
	var Container = function Container(views) {
	  this._views = {};
	  this._indexByModel = {};
	  this._indexByCustom = {};
	  this._updateLength();

	  _.each(views, _.bind(this.add, this));
	};

	emulateCollection(Container.prototype, '_views');

	// Container Methods
	// -----------------

	_.extend(Container.prototype, {

	  // Add a view to this container. Stores the view
	  // by `cid` and makes it searchable by the model
	  // cid (and model itself). Optionally specify
	  // a custom key to store an retrieve the view.
	  add: function add(view, customIndex) {
	    return this._add(view, customIndex)._updateLength();
	  },


	  // To be used when avoiding call _updateLength
	  // When you are done adding all your new views
	  // call _updateLength
	  _add: function _add(view, customIndex) {
	    var viewCid = view.cid;

	    // store the view
	    this._views[viewCid] = view;

	    // index it by model
	    if (view.model) {
	      this._indexByModel[view.model.cid] = viewCid;
	    }

	    // index by custom
	    if (customIndex) {
	      this._indexByCustom[customIndex] = viewCid;
	    }

	    return this;
	  },


	  // Find a view by the model that was attached to
	  // it. Uses the model's `cid` to find it.
	  findByModel: function findByModel(model) {
	    return this.findByModelCid(model.cid);
	  },


	  // Find a view by the `cid` of the model that was attached to
	  // it. Uses the model's `cid` to find the view `cid` and
	  // retrieve the view using it.
	  findByModelCid: function findByModelCid(modelCid) {
	    var viewCid = this._indexByModel[modelCid];
	    return this.findByCid(viewCid);
	  },


	  // Find a view by a custom indexer.
	  findByCustom: function findByCustom(index) {
	    var viewCid = this._indexByCustom[index];
	    return this.findByCid(viewCid);
	  },


	  // Find by index. This is not guaranteed to be a
	  // stable index.
	  findByIndex: function findByIndex(index) {
	    return _.values(this._views)[index];
	  },


	  // retrieve a view by its `cid` directly
	  findByCid: function findByCid(cid) {
	    return this._views[cid];
	  },


	  // Remove a view
	  remove: function remove(view) {
	    return this._remove(view)._updateLength();
	  },


	  // To be used when avoiding call _updateLength
	  // When you are done adding all your new views
	  // call _updateLength
	  _remove: function _remove(view) {
	    var viewCid = view.cid;

	    // delete model index
	    if (view.model) {
	      delete this._indexByModel[view.model.cid];
	    }

	    // delete custom index
	    _.some(this._indexByCustom, _.bind(function (cid, key) {
	      if (cid === viewCid) {
	        delete this._indexByCustom[key];
	        return true;
	      }
	    }, this));

	    // remove the view from the container
	    delete this._views[viewCid];

	    return this;
	  },


	  // Update the `.length` attribute on this container
	  _updateLength: function _updateLength() {
	    this.length = _.size(this._views);

	    return this;
	  }
	});

	var ClassOptions$3 = ['behaviors', 'childView', 'childViewEventPrefix', 'childViewEvents', 'childViewOptions', 'childViewTriggers', 'collectionEvents', 'events', 'filter', 'emptyView', 'emptyViewOptions', 'modelEvents', 'reorderOnSort', 'sort', 'triggers', 'ui', 'viewComparator'];

	// A view that iterates over a Backbone.Collection
	// and renders an individual child view for each model.
	var CollectionView = Backbone.View.extend({

	  // flag for maintaining the sorted order of the collection
	  sort: true,

	  // constructor
	  // option to pass `{sort: false}` to prevent the `CollectionView` from
	  // maintaining the sorted order of the collection.
	  // This will fallback onto appending childView's to the end.
	  //
	  // option to pass `{viewComparator: compFunction()}` to allow the `CollectionView`
	  // to use a custom sort order for the collection.
	  constructor: function constructor(options) {
	    this.render = _.bind(this.render, this);

	    this._setOptions(options);

	    this.mergeOptions(options, ClassOptions$3);

	    monitorViewEvents(this);

	    this._initBehaviors();
	    this.once('render', this._initialEvents);
	    this._initChildViewStorage();
	    this._bufferedChildren = [];

	    var args = Array.prototype.slice.call(arguments);
	    args[0] = this.options;
	    Backbone.View.prototype.constructor.apply(this, args);

	    this.delegateEntityEvents();
	  },


	  // Instead of inserting elements one by one into the page, it's much more performant to insert
	  // elements into a document fragment and then insert that document fragment into the page
	  _startBuffering: function _startBuffering() {
	    this._isBuffering = true;
	  },
	  _endBuffering: function _endBuffering() {
	    var shouldTriggerAttach = !!this._isAttached;
	    var triggerOnChildren = shouldTriggerAttach ? this._getImmediateChildren() : [];

	    this._isBuffering = false;

	    _.each(triggerOnChildren, function (child) {
	      triggerMethodOn(child, 'before:attach', child);
	    });

	    this.attachBuffer(this, this._createBuffer());

	    _.each(triggerOnChildren, function (child) {
	      child._isAttached = true;
	      triggerMethodOn(child, 'attach', child);
	    });

	    this._bufferedChildren = [];
	  },
	  _getImmediateChildren: function _getImmediateChildren() {
	    return _.values(this.children._views);
	  },


	  // Configured the initial events that the collection view binds to.
	  _initialEvents: function _initialEvents() {
	    if (this.collection) {
	      this.listenTo(this.collection, 'add', this._onCollectionAdd);
	      this.listenTo(this.collection, 'update', this._onCollectionUpdate);
	      this.listenTo(this.collection, 'reset', this.render);

	      if (this.sort) {
	        this.listenTo(this.collection, 'sort', this._sortViews);
	      }
	    }
	  },


	  // Handle a child added to the collection
	  _onCollectionAdd: function _onCollectionAdd(child, collection, opts) {
	    // `index` is present when adding with `at` since BB 1.2; indexOf fallback for < 1.2
	    var index = opts.at !== undefined && (opts.index || collection.indexOf(child));

	    // When filtered or when there is no initial index, calculate index.
	    if (this.filter || index === false) {
	      index = _.indexOf(this._filteredSortedModels(index), child);
	    }

	    if (this._shouldAddChild(child, index)) {
	      this._destroyEmptyView();
	      this._addChild(child, index);
	    }
	  },


	  // Handle collection update model removals
	  _onCollectionUpdate: function _onCollectionUpdate(collection, options) {
	    var changes = options.changes;
	    this._removeChildModels(changes.removed);
	  },


	  // Remove the child views and destroy them.
	  // This function also updates the indices of later views
	  // in the collection in order to keep the children in sync with the collection.
	  // "models" is an array of models and the corresponding views
	  // will be removed and destroyed from the CollectionView
	  _removeChildModels: function _removeChildModels(models) {
	    var _ref = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

	    var checkEmpty = _ref.checkEmpty;

	    var shouldCheckEmpty = checkEmpty !== false;

	    // Used to determine where to update the remaining
	    // sibling view indices after these views are removed.
	    var removedViews = this._getRemovedViews(models);

	    if (!removedViews.length) {
	      return;
	    }

	    this.children._updateLength();

	    // decrement the index of views after this one
	    this._updateIndices(removedViews, false);

	    if (shouldCheckEmpty) {
	      this._checkEmpty();
	    }
	  },


	  // Returns the views that will be used for re-indexing
	  // through CollectionView#_updateIndices.
	  _getRemovedViews: function _getRemovedViews(models) {
	    var _this = this;

	    // Returning a view means something was removed.
	    return _.reduce(models, function (removingViews, model) {
	      var view = _this.children.findByModel(model);

	      if (!view || view._isDestroyed) {
	        return removingViews;
	      }

	      _this._removeChildView(view);

	      removingViews.push(view);

	      return removingViews;
	    }, []);
	  },
	  _findGreatestIndexedView: function _findGreatestIndexedView(views) {

	    return _.reduce(views, function (greatestIndexedView, view) {
	      // Even if the index is `undefined`, a view will get returned.
	      if (!greatestIndexedView || greatestIndexedView._index < view._index) {
	        return view;
	      }

	      return greatestIndexedView;
	    }, undefined);
	  },
	  _removeChildView: function _removeChildView(view) {
	    this.triggerMethod('before:remove:child', this, view);

	    this.children._remove(view);
	    if (view.destroy) {
	      view.destroy();
	    } else {
	      destroyBackboneView(view);
	    }

	    delete view._parent;
	    this.stopListening(view);
	    this.triggerMethod('remove:child', this, view);
	  },


	  // Overriding Backbone.View's `setElement` to handle
	  // if an el was previously defined. If so, the view might be
	  // attached on setElement.
	  setElement: function setElement() {
	    var hasEl = !!this.el;

	    Backbone.View.prototype.setElement.apply(this, arguments);

	    if (hasEl) {
	      this._isAttached = isNodeAttached(this.el);
	    }

	    return this;
	  },


	  // Render children views. Override this method to provide your own implementation of a
	  // render function for the collection view.
	  render: function render() {
	    this._ensureViewIsIntact();
	    this.triggerMethod('before:render', this);
	    this._renderChildren();
	    this._isRendered = true;
	    this.triggerMethod('render', this);
	    return this;
	  },


	  // An efficient rendering used for filtering. Instead of modifying the whole DOM for the
	  // collection view, we are only adding or removing the related childrenViews.
	  setFilter: function setFilter(filter) {
	    var _ref2 = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

	    var preventRender = _ref2.preventRender;

	    var canBeRendered = this._isRendered && !this._isDestroyed;
	    var filterChanged = this.filter !== filter;
	    var shouldRender = canBeRendered && filterChanged && !preventRender;

	    if (shouldRender) {
	      var previousModels = this._filteredSortedModels();
	      this.filter = filter;
	      var models = this._filteredSortedModels();
	      this._applyModelDeltas(models, previousModels);
	    } else {
	      this.filter = filter;
	    }

	    return this;
	  },


	  // `removeFilter` is actually an alias for removing filters.
	  removeFilter: function removeFilter(options) {
	    return this.setFilter(null, options);
	  },


	  // Calculate and apply difference by cid between `models` and `previousModels`.
	  _applyModelDeltas: function _applyModelDeltas(models, previousModels) {
	    var _this2 = this;

	    var currentIds = {};
	    _.each(models, function (model, index) {
	      var addedChildNotExists = !_this2.children.findByModel(model);
	      if (addedChildNotExists) {
	        _this2._onCollectionAdd(model, _this2.collection, { at: index });
	      }
	      currentIds[model.cid] = true;
	    });

	    var removeModels = _.filter(previousModels, function (prevModel) {
	      return !currentIds[prevModel.cid] && _this2.children.findByModel(prevModel);
	    });

	    this._removeChildModels(removeModels);
	  },


	  // Reorder DOM after sorting. When your element's rendering do not use their index,
	  // you can pass reorderOnSort: true to only reorder the DOM after a sort instead of
	  // rendering all the collectionView.
	  reorder: function reorder() {
	    var _this3 = this;

	    var children = this.children;
	    var models = this._filteredSortedModels();

	    if (!models.length && this._showingEmptyView) {
	      return this;
	    }

	    var anyModelsAdded = _.some(models, function (model) {
	      return !children.findByModel(model);
	    });

	    // If there are any new models added due to filtering we need to add child views,
	    // so render as normal.
	    if (anyModelsAdded) {
	      this.render();
	    } else {
	      (function () {

	        var filteredOutModels = [];

	        // Get the DOM nodes in the same order as the models and
	        // find the model that were children before but aren't in this new order.
	        var elsToReorder = children.reduce(function (viewEls, view) {
	          var index = _.indexOf(models, view.model);

	          if (index === -1) {
	            filteredOutModels.push(view.model);
	            return viewEls;
	          }

	          view._index = index;

	          viewEls[index] = view.el;

	          return viewEls;
	        }, new Array(models.length));

	        _this3.triggerMethod('before:reorder', _this3);

	        // Since append moves elements that are already in the DOM, appending the elements
	        // will effectively reorder them.
	        _this3._appendReorderedChildren(elsToReorder);

	        // remove any views that have been filtered out
	        _this3._removeChildModels(filteredOutModels);

	        _this3.triggerMethod('reorder', _this3);
	      })();
	    }
	    return this;
	  },


	  // Render view after sorting. Override this method to change how the view renders
	  // after a `sort` on the collection.
	  resortView: function resortView() {
	    if (this.reorderOnSort) {
	      this.reorder();
	    } else {
	      this._renderChildren();
	    }
	    return this;
	  },


	  // Internal method. This checks for any changes in the order of the collection.
	  // If the index of any view doesn't match, it will render.
	  _sortViews: function _sortViews() {
	    var _this4 = this;

	    var models = this._filteredSortedModels();

	    // check for any changes in sort order of views
	    var orderChanged = _.find(models, function (item, index) {
	      var view = _this4.children.findByModel(item);
	      return !view || view._index !== index;
	    });

	    if (orderChanged) {
	      this.resortView();
	    }
	  },


	  // Internal reference to what index a `emptyView` is.
	  _emptyViewIndex: -1,

	  // Internal method. Separated so that CompositeView can append to the childViewContainer
	  // if necessary
	  _appendReorderedChildren: function _appendReorderedChildren(children) {
	    this.$el.append(children);
	  },


	  // Internal method. Separated so that CompositeView can have more control over events
	  // being triggered, around the rendering process
	  _renderChildren: function _renderChildren() {
	    if (this._isRendered) {
	      this._destroyEmptyView();
	      this._destroyChildren({ checkEmpty: false });
	    }

	    var models = this._filteredSortedModels();
	    if (this.isEmpty({ processedModels: models })) {
	      this._showEmptyView();
	    } else {
	      this.triggerMethod('before:render:children', this);
	      this._startBuffering();
	      this._showCollection(models);
	      this._endBuffering();
	      this.triggerMethod('render:children', this);
	    }
	  },
	  _createView: function _createView(model, index) {
	    var ChildView = this._getChildView(model);
	    var childViewOptions = this._getChildViewOptions(model, index);
	    var view = this.buildChildView(model, ChildView, childViewOptions);
	    return view;
	  },
	  _setupChildView: function _setupChildView(view, index) {
	    view._parent = this;

	    monitorViewEvents(view);

	    // set up the child view event forwarding
	    this._proxyChildEvents(view);

	    if (this.sort) {
	      view._index = index;
	    }
	  },


	  // Internal method to loop through collection and show each child view.
	  _showCollection: function _showCollection(models) {
	    _.each(models, _.bind(this._addChild, this));
	    this.children._updateLength();
	  },


	  // Allow the collection to be sorted by a custom view comparator
	  _filteredSortedModels: function _filteredSortedModels(addedAt) {
	    if (!this.collection || !this.collection.length) {
	      return [];
	    }

	    var viewComparator = this.getViewComparator();
	    var models = this.collection.models;
	    addedAt = Math.min(Math.max(addedAt, 0), models.length - 1);

	    if (viewComparator) {
	      var addedModel = void 0;
	      // Preserve `at` location, even for a sorted view
	      if (addedAt) {
	        addedModel = models[addedAt];
	        models = models.slice(0, addedAt).concat(models.slice(addedAt + 1));
	      }
	      models = this._sortModelsBy(models, viewComparator);
	      if (addedModel) {
	        models.splice(addedAt, 0, addedModel);
	      }
	    }

	    // Filter after sorting in case the filter uses the index
	    models = this._filterModels(models);

	    return models;
	  },
	  getViewComparator: function getViewComparator() {
	    return this.viewComparator;
	  },


	  // Filter an array of models, if a filter exists
	  _filterModels: function _filterModels(models) {
	    var _this5 = this;

	    if (this.filter) {
	      models = _.filter(models, function (model, index) {
	        return _this5._shouldAddChild(model, index);
	      });
	    }
	    return models;
	  },
	  _sortModelsBy: function _sortModelsBy(models, comparator) {
	    if (typeof comparator === 'string') {
	      return _.sortBy(models, function (model) {
	        return model.get(comparator);
	      });
	    } else if (comparator.length === 1) {
	      return _.sortBy(models, _.bind(comparator, this));
	    } else {
	      return models.sort(_.bind(comparator, this));
	    }
	  },


	  // Internal method to show an empty view in place of a collection of child views,
	  // when the collection is empty
	  _showEmptyView: function _showEmptyView() {
	    var EmptyView = this._getEmptyView();

	    if (EmptyView && !this._showingEmptyView) {
	      this._showingEmptyView = true;

	      var model = new Backbone.Model();
	      var emptyViewOptions = this.emptyViewOptions || this.childViewOptions;
	      if (_.isFunction(emptyViewOptions)) {
	        emptyViewOptions = emptyViewOptions.call(this, model, this._emptyViewIndex);
	      }

	      var view = this.buildChildView(model, EmptyView, emptyViewOptions);

	      this.triggerMethod('before:render:empty', this, view);
	      this.addChildView(view, 0);
	      this.triggerMethod('render:empty', this, view);
	    }
	  },


	  // Internal method to destroy an existing emptyView instance if one exists. Called when
	  // a collection view has been rendered empty, and then a child is added to the collection.
	  _destroyEmptyView: function _destroyEmptyView() {
	    if (this._showingEmptyView) {
	      this.triggerMethod('before:remove:empty', this);

	      this._destroyChildren();
	      delete this._showingEmptyView;

	      this.triggerMethod('remove:empty', this);
	    }
	  },


	  // Retrieve the empty view class
	  _getEmptyView: function _getEmptyView() {
	    var emptyView = this.emptyView;

	    if (!emptyView) {
	      return;
	    }

	    return this._getView(emptyView);
	  },


	  // Retrieve the `childView` class
	  // The `childView` property can be either a view class or a function that
	  // returns a view class. If it is a function, it will receive the model that
	  // will be passed to the view instance (created from the returned view class)
	  _getChildView: function _getChildView(child) {
	    var childView = this.childView;

	    if (!childView) {
	      throw new MarionetteError({
	        name: 'NoChildViewError',
	        message: 'A "childView" must be specified'
	      });
	    }

	    childView = this._getView(childView, child);

	    if (!childView) {
	      throw new MarionetteError({
	        name: 'InvalidChildViewError',
	        message: '"childView" must be a view class or a function that returns a view class'
	      });
	    }

	    return childView;
	  },


	  // First check if the `view` is a view class (the common case)
	  // Then check if it's a function (which we assume that returns a view class)
	  _getView: function _getView(view, child) {
	    if (view.prototype instanceof Backbone.View || view === Backbone.View) {
	      return view;
	    } else if (_.isFunction(view)) {
	      return view.call(this, child);
	    }
	  },


	  // Internal method for building and adding a child view
	  _addChild: function _addChild(child, index) {
	    var view = this._createView(child, index);
	    this.addChildView(view, index);

	    return view;
	  },
	  _getChildViewOptions: function _getChildViewOptions(child, index) {
	    if (_.isFunction(this.childViewOptions)) {
	      return this.childViewOptions(child, index);
	    }

	    return this.childViewOptions;
	  },


	  // Render the child's view and add it to the HTML for the collection view at a given index.
	  // This will also update the indices of later views in the collection in order to keep the
	  // children in sync with the collection.
	  addChildView: function addChildView(view, index) {
	    this.triggerMethod('before:add:child', this, view);
	    this._setupChildView(view, index);

	    // Store the child view itself so we can properly remove and/or destroy it later
	    if (this._isBuffering) {
	      // Add to children, but don't update children's length.
	      this.children._add(view);
	    } else {
	      // increment indices of views after this one
	      this._updateIndices(view, true);
	      this.children.add(view);
	    }

	    this._renderView(view);

	    this._attachView(view, index);

	    this.triggerMethod('add:child', this, view);

	    return view;
	  },


	  // Internal method. This decrements or increments the indices of views after the added/removed
	  // view to keep in sync with the collection.
	  _updateIndices: function _updateIndices(views, increment) {
	    if (!this.sort) {
	      return;
	    }

	    var view = _.isArray(views) ? this._findGreatestIndexedView(views) : views;

	    // update the indexes of views after this one
	    this.children.each(function (laterView) {
	      if (laterView._index >= view._index) {
	        laterView._index += increment ? 1 : -1;
	      }
	    });
	  },
	  _renderView: function _renderView(view) {
	    if (view._isRendered) {
	      return;
	    }

	    if (!view.supportsRenderLifecycle) {
	      triggerMethodOn(view, 'before:render', view);
	    }

	    view.render();

	    if (!view.supportsRenderLifecycle) {
	      view._isRendered = true;
	      triggerMethodOn(view, 'render', view);
	    }
	  },
	  _attachView: function _attachView(view, index) {
	    // Only trigger attach if already attached and not buffering,
	    // otherwise _endBuffering() or Region#show() handles this.
	    var shouldTriggerAttach = !view._isAttached && !this._isBuffering && this._isAttached;

	    if (shouldTriggerAttach) {
	      triggerMethodOn(view, 'before:attach', view);
	    }

	    this.attachHtml(this, view, index);

	    if (shouldTriggerAttach) {
	      view._isAttached = true;
	      triggerMethodOn(view, 'attach', view);
	    }
	  },


	  // Build a `childView` for a model in the collection.
	  buildChildView: function buildChildView(child, ChildViewClass, childViewOptions) {
	    var options = _.extend({ model: child }, childViewOptions);
	    return new ChildViewClass(options);
	  },


	  // Remove the child view and destroy it. This function also updates the indices of later views
	  // in the collection in order to keep the children in sync with the collection.
	  removeChildView: function removeChildView(view) {
	    if (!view || view._isDestroyed) {
	      return view;
	    }

	    this._removeChildView(view);
	    this.children._updateLength();
	    // decrement the index of views after this one
	    this._updateIndices(view, false);
	    return view;
	  },


	  // check if the collection is empty or optionally whether an array of pre-processed models is empty
	  isEmpty: function isEmpty(options) {
	    var models = void 0;
	    if (_.result(options, 'processedModels')) {
	      models = options.processedModels;
	    } else {
	      models = this.collection ? this.collection.models : [];
	      models = this._filterModels(models);
	    }
	    return models.length === 0;
	  },


	  // If empty, show the empty view
	  _checkEmpty: function _checkEmpty() {
	    if (this.isEmpty()) {
	      this._showEmptyView();
	    }
	  },


	  // You might need to override this if you've overridden attachHtml
	  attachBuffer: function attachBuffer(collectionView, buffer) {
	    collectionView.$el.append(buffer);
	  },


	  // Create a fragment buffer from the currently buffered children
	  _createBuffer: function _createBuffer() {
	    var elBuffer = document.createDocumentFragment();
	    _.each(this._bufferedChildren, function (b) {
	      elBuffer.appendChild(b.el);
	    });
	    return elBuffer;
	  },


	  // Append the HTML to the collection's `el`. Override this method to do something other
	  // than `.append`.
	  attachHtml: function attachHtml(collectionView, childView, index) {
	    if (collectionView._isBuffering) {
	      // buffering happens on reset events and initial renders
	      // in order to reduce the number of inserts into the
	      // document, which are expensive.
	      collectionView._bufferedChildren.splice(index, 0, childView);
	    } else {
	      // If we've already rendered the main collection, append
	      // the new child into the correct order if we need to. Otherwise
	      // append to the end.
	      if (!collectionView._insertBefore(childView, index)) {
	        collectionView._insertAfter(childView);
	      }
	    }
	  },


	  // Internal method. Check whether we need to insert the view into the correct position.
	  _insertBefore: function _insertBefore(childView, index) {
	    var currentView = void 0;
	    var findPosition = this.sort && index < this.children.length - 1;
	    if (findPosition) {
	      // Find the view after this one
	      currentView = this.children.find(function (view) {
	        return view._index === index + 1;
	      });
	    }

	    if (currentView) {
	      currentView.$el.before(childView.el);
	      return true;
	    }

	    return false;
	  },


	  // Internal method. Append a view to the end of the $el
	  _insertAfter: function _insertAfter(childView) {
	    this.$el.append(childView.el);
	  },


	  // Internal method to set up the `children` object for storing all of the child views
	  _initChildViewStorage: function _initChildViewStorage() {
	    this.children = new Container();
	  },


	  // called by ViewMixin destroy
	  _removeChildren: function _removeChildren() {
	    this._destroyChildren({ checkEmpty: false });
	  },


	  // Destroy the child views that this collection view is holding on to, if any
	  _destroyChildren: function _destroyChildren(options) {
	    if (!this.children.length) {
	      return;
	    }

	    this.triggerMethod('before:destroy:children', this);
	    var childModels = this.children.map('model');
	    this._removeChildModels(childModels, options);
	    this.triggerMethod('destroy:children', this);
	  },


	  // Return true if the given child should be shown. Return false otherwise.
	  // The filter will be passed (child, index, collection), where
	  //  'child' is the given model
	  //  'index' is the index of that model in the collection
	  //  'collection' is the collection referenced by this CollectionView
	  _shouldAddChild: function _shouldAddChild(child, index) {
	    var filter = this.filter;
	    return !_.isFunction(filter) || filter.call(this, child, index, this.collection);
	  },


	  // Set up the child view event forwarding. Uses a "childview:" prefix in front of all forwarded events.
	  _proxyChildEvents: function _proxyChildEvents(view) {
	    this.listenTo(view, 'all', this._childViewEventHandler);
	  }
	});

	_.extend(CollectionView.prototype, ViewMixin);

	var ClassOptions$4 = ['childViewContainer', 'template', 'templateContext'];

	// Used for rendering a branch-leaf, hierarchical structure.
	// Extends directly from CollectionView
	// @deprecated
	var CompositeView = CollectionView.extend({

	  // Setting up the inheritance chain which allows changes to
	  // Marionette.CollectionView.prototype.constructor which allows overriding
	  // option to pass '{sort: false}' to prevent the CompositeView from
	  // maintaining the sorted order of the collection.
	  // This will fallback onto appending childView's to the end.
	  constructor: function constructor(options) {
	    deprecate('CompositeView is deprecated. Convert to View at your earliest convenience');

	    this.mergeOptions(options, ClassOptions$4);

	    CollectionView.prototype.constructor.apply(this, arguments);
	  },


	  // Configured the initial events that the composite view
	  // binds to. Override this method to prevent the initial
	  // events, or to add your own initial events.
	  _initialEvents: function _initialEvents() {

	    // Bind only after composite view is rendered to avoid adding child views
	    // to nonexistent childViewContainer

	    if (this.collection) {
	      this.listenTo(this.collection, 'add', this._onCollectionAdd);
	      this.listenTo(this.collection, 'update', this._onCollectionUpdate);
	      this.listenTo(this.collection, 'reset', this.renderChildren);

	      if (this.sort) {
	        this.listenTo(this.collection, 'sort', this._sortViews);
	      }
	    }
	  },


	  // Retrieve the `childView` to be used when rendering each of
	  // the items in the collection. The default is to return
	  // `this.childView` or Marionette.CompositeView if no `childView`
	  // has been defined. As happens in CollectionView, `childView` can
	  // be a function (which should return a view class).
	  _getChildView: function _getChildView(child) {
	    var childView = this.childView;

	    // for CompositeView, if `childView` is not specified, we'll get the same
	    // composite view class rendered for each child in the collection
	    // then check if the `childView` is a view class (the common case)
	    // finally check if it's a function (which we assume that returns a view class)
	    if (!childView) {
	      return this.constructor;
	    }

	    childView = this._getView(childView, child);

	    if (!childView) {
	      throw new MarionetteError({
	        name: 'InvalidChildViewError',
	        message: '"childView" must be a view class or a function that returns a view class'
	      });
	    }

	    return childView;
	  },


	  // Return the serialized model
	  serializeData: function serializeData() {
	    return this.serializeModel();
	  },


	  // Renders the model and the collection.
	  render: function render() {
	    this._ensureViewIsIntact();
	    this._isRendering = true;
	    this.resetChildViewContainer();

	    this.triggerMethod('before:render', this);

	    this._renderTemplate();
	    this.bindUIElements();
	    this.renderChildren();

	    this._isRendering = false;
	    this._isRendered = true;
	    this.triggerMethod('render', this);
	    return this;
	  },
	  renderChildren: function renderChildren() {
	    if (this._isRendered || this._isRendering) {
	      CollectionView.prototype._renderChildren.call(this);
	    }
	  },


	  // You might need to override this if you've overridden attachHtml
	  attachBuffer: function attachBuffer(compositeView, buffer) {
	    var $container = this.getChildViewContainer(compositeView);
	    $container.append(buffer);
	  },


	  // Internal method. Append a view to the end of the $el.
	  // Overidden from CollectionView to ensure view is appended to
	  // childViewContainer
	  _insertAfter: function _insertAfter(childView) {
	    var $container = this.getChildViewContainer(this, childView);
	    $container.append(childView.el);
	  },


	  // Internal method. Append reordered childView'.
	  // Overidden from CollectionView to ensure reordered views
	  // are appended to childViewContainer
	  _appendReorderedChildren: function _appendReorderedChildren(children) {
	    var $container = this.getChildViewContainer(this);
	    $container.append(children);
	  },


	  // Internal method to ensure an `$childViewContainer` exists, for the
	  // `attachHtml` method to use.
	  getChildViewContainer: function getChildViewContainer(containerView, childView) {
	    if (!!containerView.$childViewContainer) {
	      return containerView.$childViewContainer;
	    }

	    var container = void 0;
	    var childViewContainer = containerView.childViewContainer;
	    if (childViewContainer) {

	      var selector = _.result(containerView, 'childViewContainer');

	      if (selector.charAt(0) === '@' && containerView.ui) {
	        container = containerView.ui[selector.substr(4)];
	      } else {
	        container = containerView.$(selector);
	      }

	      if (container.length <= 0) {
	        throw new MarionetteError({
	          name: 'ChildViewContainerMissingError',
	          message: 'The specified "childViewContainer" was not found: ' + containerView.childViewContainer
	        });
	      }
	    } else {
	      container = containerView.$el;
	    }

	    containerView.$childViewContainer = container;
	    return container;
	  },


	  // Internal method to reset the `$childViewContainer` on render
	  resetChildViewContainer: function resetChildViewContainer() {
	    if (this.$childViewContainer) {
	      this.$childViewContainer = undefined;
	    }
	  }
	});

	// To prevent duplication but allow the best View organization
	// Certain View methods are mixed directly into the deprecated CompositeView
	var MixinFromView = _.pick(View.prototype, 'serializeModel', 'getTemplate', '_renderTemplate', 'mixinTemplateContext', 'attachElContent');
	_.extend(CompositeView.prototype, MixinFromView);

	var ClassOptions$5 = ['collectionEvents', 'events', 'modelEvents', 'triggers', 'ui'];

	var Behavior = MarionetteObject.extend({
	  cidPrefix: 'mnb',

	  constructor: function constructor(options, view) {
	    // Setup reference to the view.
	    // this comes in handle when a behavior
	    // wants to directly talk up the chain
	    // to the view.
	    this.view = view;
	    this.defaults = _.clone(_.result(this, 'defaults', {}));
	    this._setOptions(this.defaults, options);
	    this.mergeOptions(this.options, ClassOptions$5);

	    // Construct an internal UI hash using
	    // the behaviors UI hash and then the view UI hash.
	    // This allows the user to use UI hash elements
	    // defined in the parent view as well as those
	    // defined in the given behavior.
	    // This order will help the reuse and share of a behavior
	    // between multiple views, while letting a view override a
	    // selector under an UI key.
	    this.ui = _.extend({}, _.result(this, 'ui'), _.result(view, 'ui'));

	    MarionetteObject.apply(this, arguments);
	  },


	  // proxy behavior $ method to the view
	  // this is useful for doing jquery DOM lookups
	  // scoped to behaviors view.
	  $: function $() {
	    return this.view.$.apply(this.view, arguments);
	  },


	  // Stops the behavior from listening to events.
	  // Overrides Object#destroy to prevent additional events from being triggered.
	  destroy: function destroy() {
	    this.stopListening();

	    return this;
	  },
	  proxyViewProperties: function proxyViewProperties() {
	    this.$el = this.view.$el;
	    this.el = this.view.el;

	    return this;
	  },
	  bindUIElements: function bindUIElements() {
	    this._bindUIElements();

	    return this;
	  },
	  unbindUIElements: function unbindUIElements() {
	    this._unbindUIElements();

	    return this;
	  },
	  getUI: function getUI(name) {
	    this.view._ensureViewIsIntact();
	    return this._getUI(name);
	  },


	  // Handle `modelEvents`, and `collectionEvents` configuration
	  delegateEntityEvents: function delegateEntityEvents() {
	    this._delegateEntityEvents(this.view.model, this.view.collection);

	    return this;
	  },
	  undelegateEntityEvents: function undelegateEntityEvents() {
	    this._undelegateEntityEvents(this.view.model, this.view.collection);

	    return this;
	  },
	  getEvents: function getEvents() {
	    var _this = this;

	    // Normalize behavior events hash to allow
	    // a user to use the @ui. syntax.
	    var behaviorEvents = this.normalizeUIKeys(_.result(this, 'events'));

	    // binds the handler to the behavior and builds a unique eventName
	    return _.reduce(behaviorEvents, function (events, behaviorHandler, key) {
	      if (!_.isFunction(behaviorHandler)) {
	        behaviorHandler = _this[behaviorHandler];
	      }
	      if (!behaviorHandler) {
	        return;
	      }
	      key = getUniqueEventName(key);
	      events[key] = _.bind(behaviorHandler, _this);
	      return events;
	    }, {});
	  },


	  // Internal method to build all trigger handlers for a given behavior
	  getTriggers: function getTriggers() {
	    if (!this.triggers) {
	      return;
	    }

	    // Normalize behavior triggers hash to allow
	    // a user to use the @ui. syntax.
	    var behaviorTriggers = this.normalizeUIKeys(_.result(this, 'triggers'));

	    return this._getViewTriggers(this.view, behaviorTriggers);
	  }
	});

	_.extend(Behavior.prototype, DelegateEntityEventsMixin, TriggersMixin, UIMixin);

	var ClassOptions$6 = ['region', 'regionClass'];

	// A container for a Marionette application.
	var Application = MarionetteObject.extend({
	  cidPrefix: 'mna',

	  constructor: function constructor(options) {
	    this._setOptions(options);

	    this.mergeOptions(options, ClassOptions$6);

	    this._initRegion();

	    MarionetteObject.prototype.constructor.apply(this, arguments);
	  },


	  regionClass: Region,

	  _initRegion: function _initRegion() {
	    var region = this.region;

	    if (!region) {
	      return;
	    }

	    var defaults = {
	      regionClass: this.regionClass
	    };

	    this._region = buildRegion(region, defaults);
	  },
	  getRegion: function getRegion() {
	    return this._region;
	  },
	  showView: function showView(view) {
	    var region = this.getRegion();

	    for (var _len = arguments.length, args = Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
	      args[_key - 1] = arguments[_key];
	    }

	    return region.show.apply(region, [view].concat(args));
	  },
	  getView: function getView() {
	    return this.getRegion().currentView;
	  },


	  // kick off all of the application's processes.
	  start: function start(options) {
	    this.triggerMethod('before:start', this, options);
	    this.triggerMethod('start', this, options);
	    return this;
	  }
	});

	var ClassOptions$7 = ['appRoutes', 'controller'];

	var AppRouter = Backbone.Router.extend({
	  constructor: function constructor(options) {
	    this._setOptions(options);

	    this.mergeOptions(options, ClassOptions$7);

	    Backbone.Router.apply(this, arguments);

	    var appRoutes = this.appRoutes;
	    var controller = this._getController();
	    this.processAppRoutes(controller, appRoutes);
	    this.on('route', this._processOnRoute, this);
	  },


	  // Similar to route method on a Backbone Router but
	  // method is called on the controller
	  appRoute: function appRoute(route, methodName) {
	    var controller = this._getController();
	    this._addAppRoute(controller, route, methodName);
	    return this;
	  },


	  // process the route event and trigger the onRoute
	  // method call, if it exists
	  _processOnRoute: function _processOnRoute(routeName, routeArgs) {
	    // make sure an onRoute before trying to call it
	    if (_.isFunction(this.onRoute)) {
	      // find the path that matches the current route
	      var routePath = _.invert(this.appRoutes)[routeName];
	      this.onRoute(routeName, routePath, routeArgs);
	    }
	  },


	  // Internal method to process the `appRoutes` for the
	  // router, and turn them in to routes that trigger the
	  // specified method on the specified `controller`.
	  processAppRoutes: function processAppRoutes(controller, appRoutes) {
	    var _this = this;

	    if (!appRoutes) {
	      return this;
	    }

	    var routeNames = _.keys(appRoutes).reverse(); // Backbone requires reverted order of routes

	    _.each(routeNames, function (route) {
	      _this._addAppRoute(controller, route, appRoutes[route]);
	    });

	    return this;
	  },
	  _getController: function _getController() {
	    return this.controller;
	  },
	  _addAppRoute: function _addAppRoute(controller, route, methodName) {
	    var method = controller[methodName];

	    if (!method) {
	      throw new MarionetteError('Method "' + methodName + '" was not found on the controller');
	    }

	    this.route(route, methodName, _.bind(method, controller));
	  },


	  triggerMethod: triggerMethod
	});

	_.extend(AppRouter.prototype, CommonMixin);

	// Placeholder method to be extended by the user.
	// The method should define the object that stores the behaviors.
	// i.e.
	//
	// ```js
	// Marionette.Behaviors.behaviorsLookup: function() {
	//   return App.Behaviors
	// }
	// ```
	function behaviorsLookup() {
	  throw new MarionetteError({
	    message: 'You must define where your behaviors are stored.',
	    url: 'marionette.behaviors.md#behaviorslookup'
	  });
	}

	// Add Feature flags here
	// e.g. 'class' => false
	var FEATURES = {};

	function isEnabled(name) {
	  return !!FEATURES[name];
	}

	function setEnabled(name, state) {
	  return FEATURES[name] = state;
	}

	var previousMarionette = Backbone.Marionette;
	var Marionette = Backbone.Marionette = {};

	// This allows you to run multiple instances of Marionette on the same
	// webapp. After loading the new version, call `noConflict()` to
	// get a reference to it. At the same time the old version will be
	// returned to Backbone.Marionette.
	Marionette.noConflict = function () {
	  Backbone.Marionette = previousMarionette;
	  return this;
	};

	// Utilities
	Marionette.bindEvents = proxy(bindEvents);
	Marionette.unbindEvents = proxy(unbindEvents);
	Marionette.bindRequests = proxy(bindRequests);
	Marionette.unbindRequests = proxy(unbindRequests);
	Marionette.mergeOptions = proxy(mergeOptions);
	Marionette.getOption = proxy(getOption);
	Marionette.normalizeMethods = proxy(normalizeMethods);
	Marionette.extend = extend;
	Marionette.isNodeAttached = isNodeAttached;
	Marionette.deprecate = deprecate;
	Marionette.triggerMethod = proxy(triggerMethod);
	Marionette.triggerMethodOn = triggerMethodOn;
	Marionette.isEnabled = isEnabled;
	Marionette.setEnabled = setEnabled;
	Marionette.monitorViewEvents = monitorViewEvents;

	Marionette.Behaviors = {};
	Marionette.Behaviors.behaviorsLookup = behaviorsLookup;

	// Classes
	Marionette.Application = Application;
	Marionette.AppRouter = AppRouter;
	Marionette.Renderer = Renderer;
	Marionette.TemplateCache = TemplateCache;
	Marionette.View = View;
	Marionette.CollectionView = CollectionView;
	Marionette.CompositeView = CompositeView;
	Marionette.Behavior = Behavior;
	Marionette.Region = Region;
	Marionette.Error = MarionetteError;
	Marionette.Object = MarionetteObject;

	// Configuration
	Marionette.DEV_MODE = false;
	Marionette.FEATURES = FEATURES;
	Marionette.VERSION = version;

	return Marionette;

}));

//# sourceMappingURL=backbone.marionette.js.map


/***/ }),
/* 3 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;//     Underscore.js 1.8.3
//     http://underscorejs.org
//     (c) 2009-2015 Jeremy Ashkenas, DocumentCloud and Investigative Reporters & Editors
//     Underscore may be freely distributed under the MIT license.

(function() {

  // Baseline setup
  // --------------

  // Establish the root object, `window` in the browser, or `exports` on the server.
  var root = this;

  // Save the previous value of the `_` variable.
  var previousUnderscore = root._;

  // Save bytes in the minified (but not gzipped) version:
  var ArrayProto = Array.prototype, ObjProto = Object.prototype, FuncProto = Function.prototype;

  // Create quick reference variables for speed access to core prototypes.
  var
    push             = ArrayProto.push,
    slice            = ArrayProto.slice,
    toString         = ObjProto.toString,
    hasOwnProperty   = ObjProto.hasOwnProperty;

  // All **ECMAScript 5** native function implementations that we hope to use
  // are declared here.
  var
    nativeIsArray      = Array.isArray,
    nativeKeys         = Object.keys,
    nativeBind         = FuncProto.bind,
    nativeCreate       = Object.create;

  // Naked function reference for surrogate-prototype-swapping.
  var Ctor = function(){};

  // Create a safe reference to the Underscore object for use below.
  var _ = function(obj) {
    if (obj instanceof _) return obj;
    if (!(this instanceof _)) return new _(obj);
    this._wrapped = obj;
  };

  // Export the Underscore object for **Node.js**, with
  // backwards-compatibility for the old `require()` API. If we're in
  // the browser, add `_` as a global object.
  if (true) {
    if (typeof module !== 'undefined' && module.exports) {
      exports = module.exports = _;
    }
    exports._ = _;
  } else {
    root._ = _;
  }

  // Current version.
  _.VERSION = '1.8.3';

  // Internal function that returns an efficient (for current engines) version
  // of the passed-in callback, to be repeatedly applied in other Underscore
  // functions.
  var optimizeCb = function(func, context, argCount) {
    if (context === void 0) return func;
    switch (argCount == null ? 3 : argCount) {
      case 1: return function(value) {
        return func.call(context, value);
      };
      case 2: return function(value, other) {
        return func.call(context, value, other);
      };
      case 3: return function(value, index, collection) {
        return func.call(context, value, index, collection);
      };
      case 4: return function(accumulator, value, index, collection) {
        return func.call(context, accumulator, value, index, collection);
      };
    }
    return function() {
      return func.apply(context, arguments);
    };
  };

  // A mostly-internal function to generate callbacks that can be applied
  // to each element in a collection, returning the desired result — either
  // identity, an arbitrary callback, a property matcher, or a property accessor.
  var cb = function(value, context, argCount) {
    if (value == null) return _.identity;
    if (_.isFunction(value)) return optimizeCb(value, context, argCount);
    if (_.isObject(value)) return _.matcher(value);
    return _.property(value);
  };
  _.iteratee = function(value, context) {
    return cb(value, context, Infinity);
  };

  // An internal function for creating assigner functions.
  var createAssigner = function(keysFunc, undefinedOnly) {
    return function(obj) {
      var length = arguments.length;
      if (length < 2 || obj == null) return obj;
      for (var index = 1; index < length; index++) {
        var source = arguments[index],
            keys = keysFunc(source),
            l = keys.length;
        for (var i = 0; i < l; i++) {
          var key = keys[i];
          if (!undefinedOnly || obj[key] === void 0) obj[key] = source[key];
        }
      }
      return obj;
    };
  };

  // An internal function for creating a new object that inherits from another.
  var baseCreate = function(prototype) {
    if (!_.isObject(prototype)) return {};
    if (nativeCreate) return nativeCreate(prototype);
    Ctor.prototype = prototype;
    var result = new Ctor;
    Ctor.prototype = null;
    return result;
  };

  var property = function(key) {
    return function(obj) {
      return obj == null ? void 0 : obj[key];
    };
  };

  // Helper for collection methods to determine whether a collection
  // should be iterated as an array or as an object
  // Related: http://people.mozilla.org/~jorendorff/es6-draft.html#sec-tolength
  // Avoids a very nasty iOS 8 JIT bug on ARM-64. #2094
  var MAX_ARRAY_INDEX = Math.pow(2, 53) - 1;
  var getLength = property('length');
  var isArrayLike = function(collection) {
    var length = getLength(collection);
    return typeof length == 'number' && length >= 0 && length <= MAX_ARRAY_INDEX;
  };

  // Collection Functions
  // --------------------

  // The cornerstone, an `each` implementation, aka `forEach`.
  // Handles raw objects in addition to array-likes. Treats all
  // sparse array-likes as if they were dense.
  _.each = _.forEach = function(obj, iteratee, context) {
    iteratee = optimizeCb(iteratee, context);
    var i, length;
    if (isArrayLike(obj)) {
      for (i = 0, length = obj.length; i < length; i++) {
        iteratee(obj[i], i, obj);
      }
    } else {
      var keys = _.keys(obj);
      for (i = 0, length = keys.length; i < length; i++) {
        iteratee(obj[keys[i]], keys[i], obj);
      }
    }
    return obj;
  };

  // Return the results of applying the iteratee to each element.
  _.map = _.collect = function(obj, iteratee, context) {
    iteratee = cb(iteratee, context);
    var keys = !isArrayLike(obj) && _.keys(obj),
        length = (keys || obj).length,
        results = Array(length);
    for (var index = 0; index < length; index++) {
      var currentKey = keys ? keys[index] : index;
      results[index] = iteratee(obj[currentKey], currentKey, obj);
    }
    return results;
  };

  // Create a reducing function iterating left or right.
  function createReduce(dir) {
    // Optimized iterator function as using arguments.length
    // in the main function will deoptimize the, see #1991.
    function iterator(obj, iteratee, memo, keys, index, length) {
      for (; index >= 0 && index < length; index += dir) {
        var currentKey = keys ? keys[index] : index;
        memo = iteratee(memo, obj[currentKey], currentKey, obj);
      }
      return memo;
    }

    return function(obj, iteratee, memo, context) {
      iteratee = optimizeCb(iteratee, context, 4);
      var keys = !isArrayLike(obj) && _.keys(obj),
          length = (keys || obj).length,
          index = dir > 0 ? 0 : length - 1;
      // Determine the initial value if none is provided.
      if (arguments.length < 3) {
        memo = obj[keys ? keys[index] : index];
        index += dir;
      }
      return iterator(obj, iteratee, memo, keys, index, length);
    };
  }

  // **Reduce** builds up a single result from a list of values, aka `inject`,
  // or `foldl`.
  _.reduce = _.foldl = _.inject = createReduce(1);

  // The right-associative version of reduce, also known as `foldr`.
  _.reduceRight = _.foldr = createReduce(-1);

  // Return the first value which passes a truth test. Aliased as `detect`.
  _.find = _.detect = function(obj, predicate, context) {
    var key;
    if (isArrayLike(obj)) {
      key = _.findIndex(obj, predicate, context);
    } else {
      key = _.findKey(obj, predicate, context);
    }
    if (key !== void 0 && key !== -1) return obj[key];
  };

  // Return all the elements that pass a truth test.
  // Aliased as `select`.
  _.filter = _.select = function(obj, predicate, context) {
    var results = [];
    predicate = cb(predicate, context);
    _.each(obj, function(value, index, list) {
      if (predicate(value, index, list)) results.push(value);
    });
    return results;
  };

  // Return all the elements for which a truth test fails.
  _.reject = function(obj, predicate, context) {
    return _.filter(obj, _.negate(cb(predicate)), context);
  };

  // Determine whether all of the elements match a truth test.
  // Aliased as `all`.
  _.every = _.all = function(obj, predicate, context) {
    predicate = cb(predicate, context);
    var keys = !isArrayLike(obj) && _.keys(obj),
        length = (keys || obj).length;
    for (var index = 0; index < length; index++) {
      var currentKey = keys ? keys[index] : index;
      if (!predicate(obj[currentKey], currentKey, obj)) return false;
    }
    return true;
  };

  // Determine if at least one element in the object matches a truth test.
  // Aliased as `any`.
  _.some = _.any = function(obj, predicate, context) {
    predicate = cb(predicate, context);
    var keys = !isArrayLike(obj) && _.keys(obj),
        length = (keys || obj).length;
    for (var index = 0; index < length; index++) {
      var currentKey = keys ? keys[index] : index;
      if (predicate(obj[currentKey], currentKey, obj)) return true;
    }
    return false;
  };

  // Determine if the array or object contains a given item (using `===`).
  // Aliased as `includes` and `include`.
  _.contains = _.includes = _.include = function(obj, item, fromIndex, guard) {
    if (!isArrayLike(obj)) obj = _.values(obj);
    if (typeof fromIndex != 'number' || guard) fromIndex = 0;
    return _.indexOf(obj, item, fromIndex) >= 0;
  };

  // Invoke a method (with arguments) on every item in a collection.
  _.invoke = function(obj, method) {
    var args = slice.call(arguments, 2);
    var isFunc = _.isFunction(method);
    return _.map(obj, function(value) {
      var func = isFunc ? method : value[method];
      return func == null ? func : func.apply(value, args);
    });
  };

  // Convenience version of a common use case of `map`: fetching a property.
  _.pluck = function(obj, key) {
    return _.map(obj, _.property(key));
  };

  // Convenience version of a common use case of `filter`: selecting only objects
  // containing specific `key:value` pairs.
  _.where = function(obj, attrs) {
    return _.filter(obj, _.matcher(attrs));
  };

  // Convenience version of a common use case of `find`: getting the first object
  // containing specific `key:value` pairs.
  _.findWhere = function(obj, attrs) {
    return _.find(obj, _.matcher(attrs));
  };

  // Return the maximum element (or element-based computation).
  _.max = function(obj, iteratee, context) {
    var result = -Infinity, lastComputed = -Infinity,
        value, computed;
    if (iteratee == null && obj != null) {
      obj = isArrayLike(obj) ? obj : _.values(obj);
      for (var i = 0, length = obj.length; i < length; i++) {
        value = obj[i];
        if (value > result) {
          result = value;
        }
      }
    } else {
      iteratee = cb(iteratee, context);
      _.each(obj, function(value, index, list) {
        computed = iteratee(value, index, list);
        if (computed > lastComputed || computed === -Infinity && result === -Infinity) {
          result = value;
          lastComputed = computed;
        }
      });
    }
    return result;
  };

  // Return the minimum element (or element-based computation).
  _.min = function(obj, iteratee, context) {
    var result = Infinity, lastComputed = Infinity,
        value, computed;
    if (iteratee == null && obj != null) {
      obj = isArrayLike(obj) ? obj : _.values(obj);
      for (var i = 0, length = obj.length; i < length; i++) {
        value = obj[i];
        if (value < result) {
          result = value;
        }
      }
    } else {
      iteratee = cb(iteratee, context);
      _.each(obj, function(value, index, list) {
        computed = iteratee(value, index, list);
        if (computed < lastComputed || computed === Infinity && result === Infinity) {
          result = value;
          lastComputed = computed;
        }
      });
    }
    return result;
  };

  // Shuffle a collection, using the modern version of the
  // [Fisher-Yates shuffle](http://en.wikipedia.org/wiki/Fisher–Yates_shuffle).
  _.shuffle = function(obj) {
    var set = isArrayLike(obj) ? obj : _.values(obj);
    var length = set.length;
    var shuffled = Array(length);
    for (var index = 0, rand; index < length; index++) {
      rand = _.random(0, index);
      if (rand !== index) shuffled[index] = shuffled[rand];
      shuffled[rand] = set[index];
    }
    return shuffled;
  };

  // Sample **n** random values from a collection.
  // If **n** is not specified, returns a single random element.
  // The internal `guard` argument allows it to work with `map`.
  _.sample = function(obj, n, guard) {
    if (n == null || guard) {
      if (!isArrayLike(obj)) obj = _.values(obj);
      return obj[_.random(obj.length - 1)];
    }
    return _.shuffle(obj).slice(0, Math.max(0, n));
  };

  // Sort the object's values by a criterion produced by an iteratee.
  _.sortBy = function(obj, iteratee, context) {
    iteratee = cb(iteratee, context);
    return _.pluck(_.map(obj, function(value, index, list) {
      return {
        value: value,
        index: index,
        criteria: iteratee(value, index, list)
      };
    }).sort(function(left, right) {
      var a = left.criteria;
      var b = right.criteria;
      if (a !== b) {
        if (a > b || a === void 0) return 1;
        if (a < b || b === void 0) return -1;
      }
      return left.index - right.index;
    }), 'value');
  };

  // An internal function used for aggregate "group by" operations.
  var group = function(behavior) {
    return function(obj, iteratee, context) {
      var result = {};
      iteratee = cb(iteratee, context);
      _.each(obj, function(value, index) {
        var key = iteratee(value, index, obj);
        behavior(result, value, key);
      });
      return result;
    };
  };

  // Groups the object's values by a criterion. Pass either a string attribute
  // to group by, or a function that returns the criterion.
  _.groupBy = group(function(result, value, key) {
    if (_.has(result, key)) result[key].push(value); else result[key] = [value];
  });

  // Indexes the object's values by a criterion, similar to `groupBy`, but for
  // when you know that your index values will be unique.
  _.indexBy = group(function(result, value, key) {
    result[key] = value;
  });

  // Counts instances of an object that group by a certain criterion. Pass
  // either a string attribute to count by, or a function that returns the
  // criterion.
  _.countBy = group(function(result, value, key) {
    if (_.has(result, key)) result[key]++; else result[key] = 1;
  });

  // Safely create a real, live array from anything iterable.
  _.toArray = function(obj) {
    if (!obj) return [];
    if (_.isArray(obj)) return slice.call(obj);
    if (isArrayLike(obj)) return _.map(obj, _.identity);
    return _.values(obj);
  };

  // Return the number of elements in an object.
  _.size = function(obj) {
    if (obj == null) return 0;
    return isArrayLike(obj) ? obj.length : _.keys(obj).length;
  };

  // Split a collection into two arrays: one whose elements all satisfy the given
  // predicate, and one whose elements all do not satisfy the predicate.
  _.partition = function(obj, predicate, context) {
    predicate = cb(predicate, context);
    var pass = [], fail = [];
    _.each(obj, function(value, key, obj) {
      (predicate(value, key, obj) ? pass : fail).push(value);
    });
    return [pass, fail];
  };

  // Array Functions
  // ---------------

  // Get the first element of an array. Passing **n** will return the first N
  // values in the array. Aliased as `head` and `take`. The **guard** check
  // allows it to work with `_.map`.
  _.first = _.head = _.take = function(array, n, guard) {
    if (array == null) return void 0;
    if (n == null || guard) return array[0];
    return _.initial(array, array.length - n);
  };

  // Returns everything but the last entry of the array. Especially useful on
  // the arguments object. Passing **n** will return all the values in
  // the array, excluding the last N.
  _.initial = function(array, n, guard) {
    return slice.call(array, 0, Math.max(0, array.length - (n == null || guard ? 1 : n)));
  };

  // Get the last element of an array. Passing **n** will return the last N
  // values in the array.
  _.last = function(array, n, guard) {
    if (array == null) return void 0;
    if (n == null || guard) return array[array.length - 1];
    return _.rest(array, Math.max(0, array.length - n));
  };

  // Returns everything but the first entry of the array. Aliased as `tail` and `drop`.
  // Especially useful on the arguments object. Passing an **n** will return
  // the rest N values in the array.
  _.rest = _.tail = _.drop = function(array, n, guard) {
    return slice.call(array, n == null || guard ? 1 : n);
  };

  // Trim out all falsy values from an array.
  _.compact = function(array) {
    return _.filter(array, _.identity);
  };

  // Internal implementation of a recursive `flatten` function.
  var flatten = function(input, shallow, strict, startIndex) {
    var output = [], idx = 0;
    for (var i = startIndex || 0, length = getLength(input); i < length; i++) {
      var value = input[i];
      if (isArrayLike(value) && (_.isArray(value) || _.isArguments(value))) {
        //flatten current level of array or arguments object
        if (!shallow) value = flatten(value, shallow, strict);
        var j = 0, len = value.length;
        output.length += len;
        while (j < len) {
          output[idx++] = value[j++];
        }
      } else if (!strict) {
        output[idx++] = value;
      }
    }
    return output;
  };

  // Flatten out an array, either recursively (by default), or just one level.
  _.flatten = function(array, shallow) {
    return flatten(array, shallow, false);
  };

  // Return a version of the array that does not contain the specified value(s).
  _.without = function(array) {
    return _.difference(array, slice.call(arguments, 1));
  };

  // Produce a duplicate-free version of the array. If the array has already
  // been sorted, you have the option of using a faster algorithm.
  // Aliased as `unique`.
  _.uniq = _.unique = function(array, isSorted, iteratee, context) {
    if (!_.isBoolean(isSorted)) {
      context = iteratee;
      iteratee = isSorted;
      isSorted = false;
    }
    if (iteratee != null) iteratee = cb(iteratee, context);
    var result = [];
    var seen = [];
    for (var i = 0, length = getLength(array); i < length; i++) {
      var value = array[i],
          computed = iteratee ? iteratee(value, i, array) : value;
      if (isSorted) {
        if (!i || seen !== computed) result.push(value);
        seen = computed;
      } else if (iteratee) {
        if (!_.contains(seen, computed)) {
          seen.push(computed);
          result.push(value);
        }
      } else if (!_.contains(result, value)) {
        result.push(value);
      }
    }
    return result;
  };

  // Produce an array that contains the union: each distinct element from all of
  // the passed-in arrays.
  _.union = function() {
    return _.uniq(flatten(arguments, true, true));
  };

  // Produce an array that contains every item shared between all the
  // passed-in arrays.
  _.intersection = function(array) {
    var result = [];
    var argsLength = arguments.length;
    for (var i = 0, length = getLength(array); i < length; i++) {
      var item = array[i];
      if (_.contains(result, item)) continue;
      for (var j = 1; j < argsLength; j++) {
        if (!_.contains(arguments[j], item)) break;
      }
      if (j === argsLength) result.push(item);
    }
    return result;
  };

  // Take the difference between one array and a number of other arrays.
  // Only the elements present in just the first array will remain.
  _.difference = function(array) {
    var rest = flatten(arguments, true, true, 1);
    return _.filter(array, function(value){
      return !_.contains(rest, value);
    });
  };

  // Zip together multiple lists into a single array -- elements that share
  // an index go together.
  _.zip = function() {
    return _.unzip(arguments);
  };

  // Complement of _.zip. Unzip accepts an array of arrays and groups
  // each array's elements on shared indices
  _.unzip = function(array) {
    var length = array && _.max(array, getLength).length || 0;
    var result = Array(length);

    for (var index = 0; index < length; index++) {
      result[index] = _.pluck(array, index);
    }
    return result;
  };

  // Converts lists into objects. Pass either a single array of `[key, value]`
  // pairs, or two parallel arrays of the same length -- one of keys, and one of
  // the corresponding values.
  _.object = function(list, values) {
    var result = {};
    for (var i = 0, length = getLength(list); i < length; i++) {
      if (values) {
        result[list[i]] = values[i];
      } else {
        result[list[i][0]] = list[i][1];
      }
    }
    return result;
  };

  // Generator function to create the findIndex and findLastIndex functions
  function createPredicateIndexFinder(dir) {
    return function(array, predicate, context) {
      predicate = cb(predicate, context);
      var length = getLength(array);
      var index = dir > 0 ? 0 : length - 1;
      for (; index >= 0 && index < length; index += dir) {
        if (predicate(array[index], index, array)) return index;
      }
      return -1;
    };
  }

  // Returns the first index on an array-like that passes a predicate test
  _.findIndex = createPredicateIndexFinder(1);
  _.findLastIndex = createPredicateIndexFinder(-1);

  // Use a comparator function to figure out the smallest index at which
  // an object should be inserted so as to maintain order. Uses binary search.
  _.sortedIndex = function(array, obj, iteratee, context) {
    iteratee = cb(iteratee, context, 1);
    var value = iteratee(obj);
    var low = 0, high = getLength(array);
    while (low < high) {
      var mid = Math.floor((low + high) / 2);
      if (iteratee(array[mid]) < value) low = mid + 1; else high = mid;
    }
    return low;
  };

  // Generator function to create the indexOf and lastIndexOf functions
  function createIndexFinder(dir, predicateFind, sortedIndex) {
    return function(array, item, idx) {
      var i = 0, length = getLength(array);
      if (typeof idx == 'number') {
        if (dir > 0) {
            i = idx >= 0 ? idx : Math.max(idx + length, i);
        } else {
            length = idx >= 0 ? Math.min(idx + 1, length) : idx + length + 1;
        }
      } else if (sortedIndex && idx && length) {
        idx = sortedIndex(array, item);
        return array[idx] === item ? idx : -1;
      }
      if (item !== item) {
        idx = predicateFind(slice.call(array, i, length), _.isNaN);
        return idx >= 0 ? idx + i : -1;
      }
      for (idx = dir > 0 ? i : length - 1; idx >= 0 && idx < length; idx += dir) {
        if (array[idx] === item) return idx;
      }
      return -1;
    };
  }

  // Return the position of the first occurrence of an item in an array,
  // or -1 if the item is not included in the array.
  // If the array is large and already in sort order, pass `true`
  // for **isSorted** to use binary search.
  _.indexOf = createIndexFinder(1, _.findIndex, _.sortedIndex);
  _.lastIndexOf = createIndexFinder(-1, _.findLastIndex);

  // Generate an integer Array containing an arithmetic progression. A port of
  // the native Python `range()` function. See
  // [the Python documentation](http://docs.python.org/library/functions.html#range).
  _.range = function(start, stop, step) {
    if (stop == null) {
      stop = start || 0;
      start = 0;
    }
    step = step || 1;

    var length = Math.max(Math.ceil((stop - start) / step), 0);
    var range = Array(length);

    for (var idx = 0; idx < length; idx++, start += step) {
      range[idx] = start;
    }

    return range;
  };

  // Function (ahem) Functions
  // ------------------

  // Determines whether to execute a function as a constructor
  // or a normal function with the provided arguments
  var executeBound = function(sourceFunc, boundFunc, context, callingContext, args) {
    if (!(callingContext instanceof boundFunc)) return sourceFunc.apply(context, args);
    var self = baseCreate(sourceFunc.prototype);
    var result = sourceFunc.apply(self, args);
    if (_.isObject(result)) return result;
    return self;
  };

  // Create a function bound to a given object (assigning `this`, and arguments,
  // optionally). Delegates to **ECMAScript 5**'s native `Function.bind` if
  // available.
  _.bind = function(func, context) {
    if (nativeBind && func.bind === nativeBind) return nativeBind.apply(func, slice.call(arguments, 1));
    if (!_.isFunction(func)) throw new TypeError('Bind must be called on a function');
    var args = slice.call(arguments, 2);
    var bound = function() {
      return executeBound(func, bound, context, this, args.concat(slice.call(arguments)));
    };
    return bound;
  };

  // Partially apply a function by creating a version that has had some of its
  // arguments pre-filled, without changing its dynamic `this` context. _ acts
  // as a placeholder, allowing any combination of arguments to be pre-filled.
  _.partial = function(func) {
    var boundArgs = slice.call(arguments, 1);
    var bound = function() {
      var position = 0, length = boundArgs.length;
      var args = Array(length);
      for (var i = 0; i < length; i++) {
        args[i] = boundArgs[i] === _ ? arguments[position++] : boundArgs[i];
      }
      while (position < arguments.length) args.push(arguments[position++]);
      return executeBound(func, bound, this, this, args);
    };
    return bound;
  };

  // Bind a number of an object's methods to that object. Remaining arguments
  // are the method names to be bound. Useful for ensuring that all callbacks
  // defined on an object belong to it.
  _.bindAll = function(obj) {
    var i, length = arguments.length, key;
    if (length <= 1) throw new Error('bindAll must be passed function names');
    for (i = 1; i < length; i++) {
      key = arguments[i];
      obj[key] = _.bind(obj[key], obj);
    }
    return obj;
  };

  // Memoize an expensive function by storing its results.
  _.memoize = function(func, hasher) {
    var memoize = function(key) {
      var cache = memoize.cache;
      var address = '' + (hasher ? hasher.apply(this, arguments) : key);
      if (!_.has(cache, address)) cache[address] = func.apply(this, arguments);
      return cache[address];
    };
    memoize.cache = {};
    return memoize;
  };

  // Delays a function for the given number of milliseconds, and then calls
  // it with the arguments supplied.
  _.delay = function(func, wait) {
    var args = slice.call(arguments, 2);
    return setTimeout(function(){
      return func.apply(null, args);
    }, wait);
  };

  // Defers a function, scheduling it to run after the current call stack has
  // cleared.
  _.defer = _.partial(_.delay, _, 1);

  // Returns a function, that, when invoked, will only be triggered at most once
  // during a given window of time. Normally, the throttled function will run
  // as much as it can, without ever going more than once per `wait` duration;
  // but if you'd like to disable the execution on the leading edge, pass
  // `{leading: false}`. To disable execution on the trailing edge, ditto.
  _.throttle = function(func, wait, options) {
    var context, args, result;
    var timeout = null;
    var previous = 0;
    if (!options) options = {};
    var later = function() {
      previous = options.leading === false ? 0 : _.now();
      timeout = null;
      result = func.apply(context, args);
      if (!timeout) context = args = null;
    };
    return function() {
      var now = _.now();
      if (!previous && options.leading === false) previous = now;
      var remaining = wait - (now - previous);
      context = this;
      args = arguments;
      if (remaining <= 0 || remaining > wait) {
        if (timeout) {
          clearTimeout(timeout);
          timeout = null;
        }
        previous = now;
        result = func.apply(context, args);
        if (!timeout) context = args = null;
      } else if (!timeout && options.trailing !== false) {
        timeout = setTimeout(later, remaining);
      }
      return result;
    };
  };

  // Returns a function, that, as long as it continues to be invoked, will not
  // be triggered. The function will be called after it stops being called for
  // N milliseconds. If `immediate` is passed, trigger the function on the
  // leading edge, instead of the trailing.
  _.debounce = function(func, wait, immediate) {
    var timeout, args, context, timestamp, result;

    var later = function() {
      var last = _.now() - timestamp;

      if (last < wait && last >= 0) {
        timeout = setTimeout(later, wait - last);
      } else {
        timeout = null;
        if (!immediate) {
          result = func.apply(context, args);
          if (!timeout) context = args = null;
        }
      }
    };

    return function() {
      context = this;
      args = arguments;
      timestamp = _.now();
      var callNow = immediate && !timeout;
      if (!timeout) timeout = setTimeout(later, wait);
      if (callNow) {
        result = func.apply(context, args);
        context = args = null;
      }

      return result;
    };
  };

  // Returns the first function passed as an argument to the second,
  // allowing you to adjust arguments, run code before and after, and
  // conditionally execute the original function.
  _.wrap = function(func, wrapper) {
    return _.partial(wrapper, func);
  };

  // Returns a negated version of the passed-in predicate.
  _.negate = function(predicate) {
    return function() {
      return !predicate.apply(this, arguments);
    };
  };

  // Returns a function that is the composition of a list of functions, each
  // consuming the return value of the function that follows.
  _.compose = function() {
    var args = arguments;
    var start = args.length - 1;
    return function() {
      var i = start;
      var result = args[start].apply(this, arguments);
      while (i--) result = args[i].call(this, result);
      return result;
    };
  };

  // Returns a function that will only be executed on and after the Nth call.
  _.after = function(times, func) {
    return function() {
      if (--times < 1) {
        return func.apply(this, arguments);
      }
    };
  };

  // Returns a function that will only be executed up to (but not including) the Nth call.
  _.before = function(times, func) {
    var memo;
    return function() {
      if (--times > 0) {
        memo = func.apply(this, arguments);
      }
      if (times <= 1) func = null;
      return memo;
    };
  };

  // Returns a function that will be executed at most one time, no matter how
  // often you call it. Useful for lazy initialization.
  _.once = _.partial(_.before, 2);

  // Object Functions
  // ----------------

  // Keys in IE < 9 that won't be iterated by `for key in ...` and thus missed.
  var hasEnumBug = !{toString: null}.propertyIsEnumerable('toString');
  var nonEnumerableProps = ['valueOf', 'isPrototypeOf', 'toString',
                      'propertyIsEnumerable', 'hasOwnProperty', 'toLocaleString'];

  function collectNonEnumProps(obj, keys) {
    var nonEnumIdx = nonEnumerableProps.length;
    var constructor = obj.constructor;
    var proto = (_.isFunction(constructor) && constructor.prototype) || ObjProto;

    // Constructor is a special case.
    var prop = 'constructor';
    if (_.has(obj, prop) && !_.contains(keys, prop)) keys.push(prop);

    while (nonEnumIdx--) {
      prop = nonEnumerableProps[nonEnumIdx];
      if (prop in obj && obj[prop] !== proto[prop] && !_.contains(keys, prop)) {
        keys.push(prop);
      }
    }
  }

  // Retrieve the names of an object's own properties.
  // Delegates to **ECMAScript 5**'s native `Object.keys`
  _.keys = function(obj) {
    if (!_.isObject(obj)) return [];
    if (nativeKeys) return nativeKeys(obj);
    var keys = [];
    for (var key in obj) if (_.has(obj, key)) keys.push(key);
    // Ahem, IE < 9.
    if (hasEnumBug) collectNonEnumProps(obj, keys);
    return keys;
  };

  // Retrieve all the property names of an object.
  _.allKeys = function(obj) {
    if (!_.isObject(obj)) return [];
    var keys = [];
    for (var key in obj) keys.push(key);
    // Ahem, IE < 9.
    if (hasEnumBug) collectNonEnumProps(obj, keys);
    return keys;
  };

  // Retrieve the values of an object's properties.
  _.values = function(obj) {
    var keys = _.keys(obj);
    var length = keys.length;
    var values = Array(length);
    for (var i = 0; i < length; i++) {
      values[i] = obj[keys[i]];
    }
    return values;
  };

  // Returns the results of applying the iteratee to each element of the object
  // In contrast to _.map it returns an object
  _.mapObject = function(obj, iteratee, context) {
    iteratee = cb(iteratee, context);
    var keys =  _.keys(obj),
          length = keys.length,
          results = {},
          currentKey;
      for (var index = 0; index < length; index++) {
        currentKey = keys[index];
        results[currentKey] = iteratee(obj[currentKey], currentKey, obj);
      }
      return results;
  };

  // Convert an object into a list of `[key, value]` pairs.
  _.pairs = function(obj) {
    var keys = _.keys(obj);
    var length = keys.length;
    var pairs = Array(length);
    for (var i = 0; i < length; i++) {
      pairs[i] = [keys[i], obj[keys[i]]];
    }
    return pairs;
  };

  // Invert the keys and values of an object. The values must be serializable.
  _.invert = function(obj) {
    var result = {};
    var keys = _.keys(obj);
    for (var i = 0, length = keys.length; i < length; i++) {
      result[obj[keys[i]]] = keys[i];
    }
    return result;
  };

  // Return a sorted list of the function names available on the object.
  // Aliased as `methods`
  _.functions = _.methods = function(obj) {
    var names = [];
    for (var key in obj) {
      if (_.isFunction(obj[key])) names.push(key);
    }
    return names.sort();
  };

  // Extend a given object with all the properties in passed-in object(s).
  _.extend = createAssigner(_.allKeys);

  // Assigns a given object with all the own properties in the passed-in object(s)
  // (https://developer.mozilla.org/docs/Web/JavaScript/Reference/Global_Objects/Object/assign)
  _.extendOwn = _.assign = createAssigner(_.keys);

  // Returns the first key on an object that passes a predicate test
  _.findKey = function(obj, predicate, context) {
    predicate = cb(predicate, context);
    var keys = _.keys(obj), key;
    for (var i = 0, length = keys.length; i < length; i++) {
      key = keys[i];
      if (predicate(obj[key], key, obj)) return key;
    }
  };

  // Return a copy of the object only containing the whitelisted properties.
  _.pick = function(object, oiteratee, context) {
    var result = {}, obj = object, iteratee, keys;
    if (obj == null) return result;
    if (_.isFunction(oiteratee)) {
      keys = _.allKeys(obj);
      iteratee = optimizeCb(oiteratee, context);
    } else {
      keys = flatten(arguments, false, false, 1);
      iteratee = function(value, key, obj) { return key in obj; };
      obj = Object(obj);
    }
    for (var i = 0, length = keys.length; i < length; i++) {
      var key = keys[i];
      var value = obj[key];
      if (iteratee(value, key, obj)) result[key] = value;
    }
    return result;
  };

   // Return a copy of the object without the blacklisted properties.
  _.omit = function(obj, iteratee, context) {
    if (_.isFunction(iteratee)) {
      iteratee = _.negate(iteratee);
    } else {
      var keys = _.map(flatten(arguments, false, false, 1), String);
      iteratee = function(value, key) {
        return !_.contains(keys, key);
      };
    }
    return _.pick(obj, iteratee, context);
  };

  // Fill in a given object with default properties.
  _.defaults = createAssigner(_.allKeys, true);

  // Creates an object that inherits from the given prototype object.
  // If additional properties are provided then they will be added to the
  // created object.
  _.create = function(prototype, props) {
    var result = baseCreate(prototype);
    if (props) _.extendOwn(result, props);
    return result;
  };

  // Create a (shallow-cloned) duplicate of an object.
  _.clone = function(obj) {
    if (!_.isObject(obj)) return obj;
    return _.isArray(obj) ? obj.slice() : _.extend({}, obj);
  };

  // Invokes interceptor with the obj, and then returns obj.
  // The primary purpose of this method is to "tap into" a method chain, in
  // order to perform operations on intermediate results within the chain.
  _.tap = function(obj, interceptor) {
    interceptor(obj);
    return obj;
  };

  // Returns whether an object has a given set of `key:value` pairs.
  _.isMatch = function(object, attrs) {
    var keys = _.keys(attrs), length = keys.length;
    if (object == null) return !length;
    var obj = Object(object);
    for (var i = 0; i < length; i++) {
      var key = keys[i];
      if (attrs[key] !== obj[key] || !(key in obj)) return false;
    }
    return true;
  };


  // Internal recursive comparison function for `isEqual`.
  var eq = function(a, b, aStack, bStack) {
    // Identical objects are equal. `0 === -0`, but they aren't identical.
    // See the [Harmony `egal` proposal](http://wiki.ecmascript.org/doku.php?id=harmony:egal).
    if (a === b) return a !== 0 || 1 / a === 1 / b;
    // A strict comparison is necessary because `null == undefined`.
    if (a == null || b == null) return a === b;
    // Unwrap any wrapped objects.
    if (a instanceof _) a = a._wrapped;
    if (b instanceof _) b = b._wrapped;
    // Compare `[[Class]]` names.
    var className = toString.call(a);
    if (className !== toString.call(b)) return false;
    switch (className) {
      // Strings, numbers, regular expressions, dates, and booleans are compared by value.
      case '[object RegExp]':
      // RegExps are coerced to strings for comparison (Note: '' + /a/i === '/a/i')
      case '[object String]':
        // Primitives and their corresponding object wrappers are equivalent; thus, `"5"` is
        // equivalent to `new String("5")`.
        return '' + a === '' + b;
      case '[object Number]':
        // `NaN`s are equivalent, but non-reflexive.
        // Object(NaN) is equivalent to NaN
        if (+a !== +a) return +b !== +b;
        // An `egal` comparison is performed for other numeric values.
        return +a === 0 ? 1 / +a === 1 / b : +a === +b;
      case '[object Date]':
      case '[object Boolean]':
        // Coerce dates and booleans to numeric primitive values. Dates are compared by their
        // millisecond representations. Note that invalid dates with millisecond representations
        // of `NaN` are not equivalent.
        return +a === +b;
    }

    var areArrays = className === '[object Array]';
    if (!areArrays) {
      if (typeof a != 'object' || typeof b != 'object') return false;

      // Objects with different constructors are not equivalent, but `Object`s or `Array`s
      // from different frames are.
      var aCtor = a.constructor, bCtor = b.constructor;
      if (aCtor !== bCtor && !(_.isFunction(aCtor) && aCtor instanceof aCtor &&
                               _.isFunction(bCtor) && bCtor instanceof bCtor)
                          && ('constructor' in a && 'constructor' in b)) {
        return false;
      }
    }
    // Assume equality for cyclic structures. The algorithm for detecting cyclic
    // structures is adapted from ES 5.1 section 15.12.3, abstract operation `JO`.

    // Initializing stack of traversed objects.
    // It's done here since we only need them for objects and arrays comparison.
    aStack = aStack || [];
    bStack = bStack || [];
    var length = aStack.length;
    while (length--) {
      // Linear search. Performance is inversely proportional to the number of
      // unique nested structures.
      if (aStack[length] === a) return bStack[length] === b;
    }

    // Add the first object to the stack of traversed objects.
    aStack.push(a);
    bStack.push(b);

    // Recursively compare objects and arrays.
    if (areArrays) {
      // Compare array lengths to determine if a deep comparison is necessary.
      length = a.length;
      if (length !== b.length) return false;
      // Deep compare the contents, ignoring non-numeric properties.
      while (length--) {
        if (!eq(a[length], b[length], aStack, bStack)) return false;
      }
    } else {
      // Deep compare objects.
      var keys = _.keys(a), key;
      length = keys.length;
      // Ensure that both objects contain the same number of properties before comparing deep equality.
      if (_.keys(b).length !== length) return false;
      while (length--) {
        // Deep compare each member
        key = keys[length];
        if (!(_.has(b, key) && eq(a[key], b[key], aStack, bStack))) return false;
      }
    }
    // Remove the first object from the stack of traversed objects.
    aStack.pop();
    bStack.pop();
    return true;
  };

  // Perform a deep comparison to check if two objects are equal.
  _.isEqual = function(a, b) {
    return eq(a, b);
  };

  // Is a given array, string, or object empty?
  // An "empty" object has no enumerable own-properties.
  _.isEmpty = function(obj) {
    if (obj == null) return true;
    if (isArrayLike(obj) && (_.isArray(obj) || _.isString(obj) || _.isArguments(obj))) return obj.length === 0;
    return _.keys(obj).length === 0;
  };

  // Is a given value a DOM element?
  _.isElement = function(obj) {
    return !!(obj && obj.nodeType === 1);
  };

  // Is a given value an array?
  // Delegates to ECMA5's native Array.isArray
  _.isArray = nativeIsArray || function(obj) {
    return toString.call(obj) === '[object Array]';
  };

  // Is a given variable an object?
  _.isObject = function(obj) {
    var type = typeof obj;
    return type === 'function' || type === 'object' && !!obj;
  };

  // Add some isType methods: isArguments, isFunction, isString, isNumber, isDate, isRegExp, isError.
  _.each(['Arguments', 'Function', 'String', 'Number', 'Date', 'RegExp', 'Error'], function(name) {
    _['is' + name] = function(obj) {
      return toString.call(obj) === '[object ' + name + ']';
    };
  });

  // Define a fallback version of the method in browsers (ahem, IE < 9), where
  // there isn't any inspectable "Arguments" type.
  if (!_.isArguments(arguments)) {
    _.isArguments = function(obj) {
      return _.has(obj, 'callee');
    };
  }

  // Optimize `isFunction` if appropriate. Work around some typeof bugs in old v8,
  // IE 11 (#1621), and in Safari 8 (#1929).
  if (typeof /./ != 'function' && typeof Int8Array != 'object') {
    _.isFunction = function(obj) {
      return typeof obj == 'function' || false;
    };
  }

  // Is a given object a finite number?
  _.isFinite = function(obj) {
    return isFinite(obj) && !isNaN(parseFloat(obj));
  };

  // Is the given value `NaN`? (NaN is the only number which does not equal itself).
  _.isNaN = function(obj) {
    return _.isNumber(obj) && obj !== +obj;
  };

  // Is a given value a boolean?
  _.isBoolean = function(obj) {
    return obj === true || obj === false || toString.call(obj) === '[object Boolean]';
  };

  // Is a given value equal to null?
  _.isNull = function(obj) {
    return obj === null;
  };

  // Is a given variable undefined?
  _.isUndefined = function(obj) {
    return obj === void 0;
  };

  // Shortcut function for checking if an object has a given property directly
  // on itself (in other words, not on a prototype).
  _.has = function(obj, key) {
    return obj != null && hasOwnProperty.call(obj, key);
  };

  // Utility Functions
  // -----------------

  // Run Underscore.js in *noConflict* mode, returning the `_` variable to its
  // previous owner. Returns a reference to the Underscore object.
  _.noConflict = function() {
    root._ = previousUnderscore;
    return this;
  };

  // Keep the identity function around for default iteratees.
  _.identity = function(value) {
    return value;
  };

  // Predicate-generating functions. Often useful outside of Underscore.
  _.constant = function(value) {
    return function() {
      return value;
    };
  };

  _.noop = function(){};

  _.property = property;

  // Generates a function for a given object that returns a given property.
  _.propertyOf = function(obj) {
    return obj == null ? function(){} : function(key) {
      return obj[key];
    };
  };

  // Returns a predicate for checking whether an object has a given set of
  // `key:value` pairs.
  _.matcher = _.matches = function(attrs) {
    attrs = _.extendOwn({}, attrs);
    return function(obj) {
      return _.isMatch(obj, attrs);
    };
  };

  // Run a function **n** times.
  _.times = function(n, iteratee, context) {
    var accum = Array(Math.max(0, n));
    iteratee = optimizeCb(iteratee, context, 1);
    for (var i = 0; i < n; i++) accum[i] = iteratee(i);
    return accum;
  };

  // Return a random integer between min and max (inclusive).
  _.random = function(min, max) {
    if (max == null) {
      max = min;
      min = 0;
    }
    return min + Math.floor(Math.random() * (max - min + 1));
  };

  // A (possibly faster) way to get the current timestamp as an integer.
  _.now = Date.now || function() {
    return new Date().getTime();
  };

   // List of HTML entities for escaping.
  var escapeMap = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#x27;',
    '`': '&#x60;'
  };
  var unescapeMap = _.invert(escapeMap);

  // Functions for escaping and unescaping strings to/from HTML interpolation.
  var createEscaper = function(map) {
    var escaper = function(match) {
      return map[match];
    };
    // Regexes for identifying a key that needs to be escaped
    var source = '(?:' + _.keys(map).join('|') + ')';
    var testRegexp = RegExp(source);
    var replaceRegexp = RegExp(source, 'g');
    return function(string) {
      string = string == null ? '' : '' + string;
      return testRegexp.test(string) ? string.replace(replaceRegexp, escaper) : string;
    };
  };
  _.escape = createEscaper(escapeMap);
  _.unescape = createEscaper(unescapeMap);

  // If the value of the named `property` is a function then invoke it with the
  // `object` as context; otherwise, return it.
  _.result = function(object, property, fallback) {
    var value = object == null ? void 0 : object[property];
    if (value === void 0) {
      value = fallback;
    }
    return _.isFunction(value) ? value.call(object) : value;
  };

  // Generate a unique integer id (unique within the entire client session).
  // Useful for temporary DOM ids.
  var idCounter = 0;
  _.uniqueId = function(prefix) {
    var id = ++idCounter + '';
    return prefix ? prefix + id : id;
  };

  // By default, Underscore uses ERB-style template delimiters, change the
  // following template settings to use alternative delimiters.
  _.templateSettings = {
    evaluate    : /<%([\s\S]+?)%>/g,
    interpolate : /<%=([\s\S]+?)%>/g,
    escape      : /<%-([\s\S]+?)%>/g
  };

  // When customizing `templateSettings`, if you don't want to define an
  // interpolation, evaluation or escaping regex, we need one that is
  // guaranteed not to match.
  var noMatch = /(.)^/;

  // Certain characters need to be escaped so that they can be put into a
  // string literal.
  var escapes = {
    "'":      "'",
    '\\':     '\\',
    '\r':     'r',
    '\n':     'n',
    '\u2028': 'u2028',
    '\u2029': 'u2029'
  };

  var escaper = /\\|'|\r|\n|\u2028|\u2029/g;

  var escapeChar = function(match) {
    return '\\' + escapes[match];
  };

  // JavaScript micro-templating, similar to John Resig's implementation.
  // Underscore templating handles arbitrary delimiters, preserves whitespace,
  // and correctly escapes quotes within interpolated code.
  // NB: `oldSettings` only exists for backwards compatibility.
  _.template = function(text, settings, oldSettings) {
    if (!settings && oldSettings) settings = oldSettings;
    settings = _.defaults({}, settings, _.templateSettings);

    // Combine delimiters into one regular expression via alternation.
    var matcher = RegExp([
      (settings.escape || noMatch).source,
      (settings.interpolate || noMatch).source,
      (settings.evaluate || noMatch).source
    ].join('|') + '|$', 'g');

    // Compile the template source, escaping string literals appropriately.
    var index = 0;
    var source = "__p+='";
    text.replace(matcher, function(match, escape, interpolate, evaluate, offset) {
      source += text.slice(index, offset).replace(escaper, escapeChar);
      index = offset + match.length;

      if (escape) {
        source += "'+\n((__t=(" + escape + "))==null?'':_.escape(__t))+\n'";
      } else if (interpolate) {
        source += "'+\n((__t=(" + interpolate + "))==null?'':__t)+\n'";
      } else if (evaluate) {
        source += "';\n" + evaluate + "\n__p+='";
      }

      // Adobe VMs need the match returned to produce the correct offest.
      return match;
    });
    source += "';\n";

    // If a variable is not specified, place data values in local scope.
    if (!settings.variable) source = 'with(obj||{}){\n' + source + '}\n';

    source = "var __t,__p='',__j=Array.prototype.join," +
      "print=function(){__p+=__j.call(arguments,'');};\n" +
      source + 'return __p;\n';

    try {
      var render = new Function(settings.variable || 'obj', '_', source);
    } catch (e) {
      e.source = source;
      throw e;
    }

    var template = function(data) {
      return render.call(this, data, _);
    };

    // Provide the compiled source as a convenience for precompilation.
    var argument = settings.variable || 'obj';
    template.source = 'function(' + argument + '){\n' + source + '}';

    return template;
  };

  // Add a "chain" function. Start chaining a wrapped Underscore object.
  _.chain = function(obj) {
    var instance = _(obj);
    instance._chain = true;
    return instance;
  };

  // OOP
  // ---------------
  // If Underscore is called as a function, it returns a wrapped object that
  // can be used OO-style. This wrapper holds altered versions of all the
  // underscore functions. Wrapped objects may be chained.

  // Helper function to continue chaining intermediate results.
  var result = function(instance, obj) {
    return instance._chain ? _(obj).chain() : obj;
  };

  // Add your own custom functions to the Underscore object.
  _.mixin = function(obj) {
    _.each(_.functions(obj), function(name) {
      var func = _[name] = obj[name];
      _.prototype[name] = function() {
        var args = [this._wrapped];
        push.apply(args, arguments);
        return result(this, func.apply(_, args));
      };
    });
  };

  // Add all of the Underscore functions to the wrapper object.
  _.mixin(_);

  // Add all mutator Array functions to the wrapper.
  _.each(['pop', 'push', 'reverse', 'shift', 'sort', 'splice', 'unshift'], function(name) {
    var method = ArrayProto[name];
    _.prototype[name] = function() {
      var obj = this._wrapped;
      method.apply(obj, arguments);
      if ((name === 'shift' || name === 'splice') && obj.length === 0) delete obj[0];
      return result(this, obj);
    };
  });

  // Add all accessor Array functions to the wrapper.
  _.each(['concat', 'join', 'slice'], function(name) {
    var method = ArrayProto[name];
    _.prototype[name] = function() {
      return result(this, method.apply(this._wrapped, arguments));
    };
  });

  // Extracts the result from a wrapped and chained object.
  _.prototype.value = function() {
    return this._wrapped;
  };

  // Provide unwrapping proxy for some methods used in engine operations
  // such as arithmetic and JSON stringification.
  _.prototype.valueOf = _.prototype.toJSON = _.prototype.value;

  _.prototype.toString = function() {
    return '' + this._wrapped;
  };

  // AMD registration happens at the end for compatibility with AMD loaders
  // that may not enforce next-turn semantics on modules. Even though general
  // practice for AMD registration is to be anonymous, underscore registers
  // as a named module because, like jQuery, it is a base library that is
  // popular enough to be bundled in a third party lib, but not be part of
  // an AMD load request. Those cases could generate an error when an
  // anonymous define() is called outside of a loader request.
  if (true) {
    !(__WEBPACK_AMD_DEFINE_ARRAY__ = [], __WEBPACK_AMD_DEFINE_RESULT__ = function() {
      return _;
    }.apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
  }
}.call(this));


/***/ }),
/* 4 */
/***/ (function(module, exports, __webpack_require__) {

/**!

 @license
 handlebars v4.0.10

Copyright (C) 2011-2016 by Yehuda Katz

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

*/
(function webpackUniversalModuleDefinition(root, factory) {
	if(true)
		module.exports = factory();
	else if(typeof define === 'function' && define.amd)
		define([], factory);
	else if(typeof exports === 'object')
		exports["Handlebars"] = factory();
	else
		root["Handlebars"] = factory();
})(this, function() {
return /******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};

/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {

/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId])
/******/ 			return installedModules[moduleId].exports;

/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			exports: {},
/******/ 			id: moduleId,
/******/ 			loaded: false
/******/ 		};

/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);

/******/ 		// Flag the module as loaded
/******/ 		module.loaded = true;

/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}


/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;

/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;

/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";

/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(0);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';

	var _interopRequireDefault = __webpack_require__(1)['default'];

	exports.__esModule = true;

	var _handlebarsRuntime = __webpack_require__(2);

	var _handlebarsRuntime2 = _interopRequireDefault(_handlebarsRuntime);

	// Compiler imports

	var _handlebarsCompilerAst = __webpack_require__(35);

	var _handlebarsCompilerAst2 = _interopRequireDefault(_handlebarsCompilerAst);

	var _handlebarsCompilerBase = __webpack_require__(36);

	var _handlebarsCompilerCompiler = __webpack_require__(41);

	var _handlebarsCompilerJavascriptCompiler = __webpack_require__(42);

	var _handlebarsCompilerJavascriptCompiler2 = _interopRequireDefault(_handlebarsCompilerJavascriptCompiler);

	var _handlebarsCompilerVisitor = __webpack_require__(39);

	var _handlebarsCompilerVisitor2 = _interopRequireDefault(_handlebarsCompilerVisitor);

	var _handlebarsNoConflict = __webpack_require__(34);

	var _handlebarsNoConflict2 = _interopRequireDefault(_handlebarsNoConflict);

	var _create = _handlebarsRuntime2['default'].create;
	function create() {
	  var hb = _create();

	  hb.compile = function (input, options) {
	    return _handlebarsCompilerCompiler.compile(input, options, hb);
	  };
	  hb.precompile = function (input, options) {
	    return _handlebarsCompilerCompiler.precompile(input, options, hb);
	  };

	  hb.AST = _handlebarsCompilerAst2['default'];
	  hb.Compiler = _handlebarsCompilerCompiler.Compiler;
	  hb.JavaScriptCompiler = _handlebarsCompilerJavascriptCompiler2['default'];
	  hb.Parser = _handlebarsCompilerBase.parser;
	  hb.parse = _handlebarsCompilerBase.parse;

	  return hb;
	}

	var inst = create();
	inst.create = create;

	_handlebarsNoConflict2['default'](inst);

	inst.Visitor = _handlebarsCompilerVisitor2['default'];

	inst['default'] = inst;

	exports['default'] = inst;
	module.exports = exports['default'];

/***/ }),
/* 1 */
/***/ (function(module, exports) {

	"use strict";

	exports["default"] = function (obj) {
	  return obj && obj.__esModule ? obj : {
	    "default": obj
	  };
	};

	exports.__esModule = true;

/***/ }),
/* 2 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';

	var _interopRequireWildcard = __webpack_require__(3)['default'];

	var _interopRequireDefault = __webpack_require__(1)['default'];

	exports.__esModule = true;

	var _handlebarsBase = __webpack_require__(4);

	var base = _interopRequireWildcard(_handlebarsBase);

	// Each of these augment the Handlebars object. No need to setup here.
	// (This is done to easily share code between commonjs and browse envs)

	var _handlebarsSafeString = __webpack_require__(21);

	var _handlebarsSafeString2 = _interopRequireDefault(_handlebarsSafeString);

	var _handlebarsException = __webpack_require__(6);

	var _handlebarsException2 = _interopRequireDefault(_handlebarsException);

	var _handlebarsUtils = __webpack_require__(5);

	var Utils = _interopRequireWildcard(_handlebarsUtils);

	var _handlebarsRuntime = __webpack_require__(22);

	var runtime = _interopRequireWildcard(_handlebarsRuntime);

	var _handlebarsNoConflict = __webpack_require__(34);

	var _handlebarsNoConflict2 = _interopRequireDefault(_handlebarsNoConflict);

	// For compatibility and usage outside of module systems, make the Handlebars object a namespace
	function create() {
	  var hb = new base.HandlebarsEnvironment();

	  Utils.extend(hb, base);
	  hb.SafeString = _handlebarsSafeString2['default'];
	  hb.Exception = _handlebarsException2['default'];
	  hb.Utils = Utils;
	  hb.escapeExpression = Utils.escapeExpression;

	  hb.VM = runtime;
	  hb.template = function (spec) {
	    return runtime.template(spec, hb);
	  };

	  return hb;
	}

	var inst = create();
	inst.create = create;

	_handlebarsNoConflict2['default'](inst);

	inst['default'] = inst;

	exports['default'] = inst;
	module.exports = exports['default'];

/***/ }),
/* 3 */
/***/ (function(module, exports) {

	"use strict";

	exports["default"] = function (obj) {
	  if (obj && obj.__esModule) {
	    return obj;
	  } else {
	    var newObj = {};

	    if (obj != null) {
	      for (var key in obj) {
	        if (Object.prototype.hasOwnProperty.call(obj, key)) newObj[key] = obj[key];
	      }
	    }

	    newObj["default"] = obj;
	    return newObj;
	  }
	};

	exports.__esModule = true;

/***/ }),
/* 4 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';

	var _interopRequireDefault = __webpack_require__(1)['default'];

	exports.__esModule = true;
	exports.HandlebarsEnvironment = HandlebarsEnvironment;

	var _utils = __webpack_require__(5);

	var _exception = __webpack_require__(6);

	var _exception2 = _interopRequireDefault(_exception);

	var _helpers = __webpack_require__(10);

	var _decorators = __webpack_require__(18);

	var _logger = __webpack_require__(20);

	var _logger2 = _interopRequireDefault(_logger);

	var VERSION = '4.0.10';
	exports.VERSION = VERSION;
	var COMPILER_REVISION = 7;

	exports.COMPILER_REVISION = COMPILER_REVISION;
	var REVISION_CHANGES = {
	  1: '<= 1.0.rc.2', // 1.0.rc.2 is actually rev2 but doesn't report it
	  2: '== 1.0.0-rc.3',
	  3: '== 1.0.0-rc.4',
	  4: '== 1.x.x',
	  5: '== 2.0.0-alpha.x',
	  6: '>= 2.0.0-beta.1',
	  7: '>= 4.0.0'
	};

	exports.REVISION_CHANGES = REVISION_CHANGES;
	var objectType = '[object Object]';

	function HandlebarsEnvironment(helpers, partials, decorators) {
	  this.helpers = helpers || {};
	  this.partials = partials || {};
	  this.decorators = decorators || {};

	  _helpers.registerDefaultHelpers(this);
	  _decorators.registerDefaultDecorators(this);
	}

	HandlebarsEnvironment.prototype = {
	  constructor: HandlebarsEnvironment,

	  logger: _logger2['default'],
	  log: _logger2['default'].log,

	  registerHelper: function registerHelper(name, fn) {
	    if (_utils.toString.call(name) === objectType) {
	      if (fn) {
	        throw new _exception2['default']('Arg not supported with multiple helpers');
	      }
	      _utils.extend(this.helpers, name);
	    } else {
	      this.helpers[name] = fn;
	    }
	  },
	  unregisterHelper: function unregisterHelper(name) {
	    delete this.helpers[name];
	  },

	  registerPartial: function registerPartial(name, partial) {
	    if (_utils.toString.call(name) === objectType) {
	      _utils.extend(this.partials, name);
	    } else {
	      if (typeof partial === 'undefined') {
	        throw new _exception2['default']('Attempting to register a partial called "' + name + '" as undefined');
	      }
	      this.partials[name] = partial;
	    }
	  },
	  unregisterPartial: function unregisterPartial(name) {
	    delete this.partials[name];
	  },

	  registerDecorator: function registerDecorator(name, fn) {
	    if (_utils.toString.call(name) === objectType) {
	      if (fn) {
	        throw new _exception2['default']('Arg not supported with multiple decorators');
	      }
	      _utils.extend(this.decorators, name);
	    } else {
	      this.decorators[name] = fn;
	    }
	  },
	  unregisterDecorator: function unregisterDecorator(name) {
	    delete this.decorators[name];
	  }
	};

	var log = _logger2['default'].log;

	exports.log = log;
	exports.createFrame = _utils.createFrame;
	exports.logger = _logger2['default'];

/***/ }),
/* 5 */
/***/ (function(module, exports) {

	'use strict';

	exports.__esModule = true;
	exports.extend = extend;
	exports.indexOf = indexOf;
	exports.escapeExpression = escapeExpression;
	exports.isEmpty = isEmpty;
	exports.createFrame = createFrame;
	exports.blockParams = blockParams;
	exports.appendContextPath = appendContextPath;
	var escape = {
	  '&': '&amp;',
	  '<': '&lt;',
	  '>': '&gt;',
	  '"': '&quot;',
	  "'": '&#x27;',
	  '`': '&#x60;',
	  '=': '&#x3D;'
	};

	var badChars = /[&<>"'`=]/g,
	    possible = /[&<>"'`=]/;

	function escapeChar(chr) {
	  return escape[chr];
	}

	function extend(obj /* , ...source */) {
	  for (var i = 1; i < arguments.length; i++) {
	    for (var key in arguments[i]) {
	      if (Object.prototype.hasOwnProperty.call(arguments[i], key)) {
	        obj[key] = arguments[i][key];
	      }
	    }
	  }

	  return obj;
	}

	var toString = Object.prototype.toString;

	exports.toString = toString;
	// Sourced from lodash
	// https://github.com/bestiejs/lodash/blob/master/LICENSE.txt
	/* eslint-disable func-style */
	var isFunction = function isFunction(value) {
	  return typeof value === 'function';
	};
	// fallback for older versions of Chrome and Safari
	/* istanbul ignore next */
	if (isFunction(/x/)) {
	  exports.isFunction = isFunction = function (value) {
	    return typeof value === 'function' && toString.call(value) === '[object Function]';
	  };
	}
	exports.isFunction = isFunction;

	/* eslint-enable func-style */

	/* istanbul ignore next */
	var isArray = Array.isArray || function (value) {
	  return value && typeof value === 'object' ? toString.call(value) === '[object Array]' : false;
	};

	exports.isArray = isArray;
	// Older IE versions do not directly support indexOf so we must implement our own, sadly.

	function indexOf(array, value) {
	  for (var i = 0, len = array.length; i < len; i++) {
	    if (array[i] === value) {
	      return i;
	    }
	  }
	  return -1;
	}

	function escapeExpression(string) {
	  if (typeof string !== 'string') {
	    // don't escape SafeStrings, since they're already safe
	    if (string && string.toHTML) {
	      return string.toHTML();
	    } else if (string == null) {
	      return '';
	    } else if (!string) {
	      return string + '';
	    }

	    // Force a string conversion as this will be done by the append regardless and
	    // the regex test will do this transparently behind the scenes, causing issues if
	    // an object's to string has escaped characters in it.
	    string = '' + string;
	  }

	  if (!possible.test(string)) {
	    return string;
	  }
	  return string.replace(badChars, escapeChar);
	}

	function isEmpty(value) {
	  if (!value && value !== 0) {
	    return true;
	  } else if (isArray(value) && value.length === 0) {
	    return true;
	  } else {
	    return false;
	  }
	}

	function createFrame(object) {
	  var frame = extend({}, object);
	  frame._parent = object;
	  return frame;
	}

	function blockParams(params, ids) {
	  params.path = ids;
	  return params;
	}

	function appendContextPath(contextPath, id) {
	  return (contextPath ? contextPath + '.' : '') + id;
	}

/***/ }),
/* 6 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';

	var _Object$defineProperty = __webpack_require__(7)['default'];

	exports.__esModule = true;

	var errorProps = ['description', 'fileName', 'lineNumber', 'message', 'name', 'number', 'stack'];

	function Exception(message, node) {
	  var loc = node && node.loc,
	      line = undefined,
	      column = undefined;
	  if (loc) {
	    line = loc.start.line;
	    column = loc.start.column;

	    message += ' - ' + line + ':' + column;
	  }

	  var tmp = Error.prototype.constructor.call(this, message);

	  // Unfortunately errors are not enumerable in Chrome (at least), so `for prop in tmp` doesn't work.
	  for (var idx = 0; idx < errorProps.length; idx++) {
	    this[errorProps[idx]] = tmp[errorProps[idx]];
	  }

	  /* istanbul ignore else */
	  if (Error.captureStackTrace) {
	    Error.captureStackTrace(this, Exception);
	  }

	  try {
	    if (loc) {
	      this.lineNumber = line;

	      // Work around issue under safari where we can't directly set the column value
	      /* istanbul ignore next */
	      if (_Object$defineProperty) {
	        Object.defineProperty(this, 'column', {
	          value: column,
	          enumerable: true
	        });
	      } else {
	        this.column = column;
	      }
	    }
	  } catch (nop) {
	    /* Ignore if the browser is very particular */
	  }
	}

	Exception.prototype = new Error();

	exports['default'] = Exception;
	module.exports = exports['default'];

/***/ }),
/* 7 */
/***/ (function(module, exports, __webpack_require__) {

	module.exports = { "default": __webpack_require__(8), __esModule: true };

/***/ }),
/* 8 */
/***/ (function(module, exports, __webpack_require__) {

	var $ = __webpack_require__(9);
	module.exports = function defineProperty(it, key, desc){
	  return $.setDesc(it, key, desc);
	};

/***/ }),
/* 9 */
/***/ (function(module, exports) {

	var $Object = Object;
	module.exports = {
	  create:     $Object.create,
	  getProto:   $Object.getPrototypeOf,
	  isEnum:     {}.propertyIsEnumerable,
	  getDesc:    $Object.getOwnPropertyDescriptor,
	  setDesc:    $Object.defineProperty,
	  setDescs:   $Object.defineProperties,
	  getKeys:    $Object.keys,
	  getNames:   $Object.getOwnPropertyNames,
	  getSymbols: $Object.getOwnPropertySymbols,
	  each:       [].forEach
	};

/***/ }),
/* 10 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';

	var _interopRequireDefault = __webpack_require__(1)['default'];

	exports.__esModule = true;
	exports.registerDefaultHelpers = registerDefaultHelpers;

	var _helpersBlockHelperMissing = __webpack_require__(11);

	var _helpersBlockHelperMissing2 = _interopRequireDefault(_helpersBlockHelperMissing);

	var _helpersEach = __webpack_require__(12);

	var _helpersEach2 = _interopRequireDefault(_helpersEach);

	var _helpersHelperMissing = __webpack_require__(13);

	var _helpersHelperMissing2 = _interopRequireDefault(_helpersHelperMissing);

	var _helpersIf = __webpack_require__(14);

	var _helpersIf2 = _interopRequireDefault(_helpersIf);

	var _helpersLog = __webpack_require__(15);

	var _helpersLog2 = _interopRequireDefault(_helpersLog);

	var _helpersLookup = __webpack_require__(16);

	var _helpersLookup2 = _interopRequireDefault(_helpersLookup);

	var _helpersWith = __webpack_require__(17);

	var _helpersWith2 = _interopRequireDefault(_helpersWith);

	function registerDefaultHelpers(instance) {
	  _helpersBlockHelperMissing2['default'](instance);
	  _helpersEach2['default'](instance);
	  _helpersHelperMissing2['default'](instance);
	  _helpersIf2['default'](instance);
	  _helpersLog2['default'](instance);
	  _helpersLookup2['default'](instance);
	  _helpersWith2['default'](instance);
	}

/***/ }),
/* 11 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';

	exports.__esModule = true;

	var _utils = __webpack_require__(5);

	exports['default'] = function (instance) {
	  instance.registerHelper('blockHelperMissing', function (context, options) {
	    var inverse = options.inverse,
	        fn = options.fn;

	    if (context === true) {
	      return fn(this);
	    } else if (context === false || context == null) {
	      return inverse(this);
	    } else if (_utils.isArray(context)) {
	      if (context.length > 0) {
	        if (options.ids) {
	          options.ids = [options.name];
	        }

	        return instance.helpers.each(context, options);
	      } else {
	        return inverse(this);
	      }
	    } else {
	      if (options.data && options.ids) {
	        var data = _utils.createFrame(options.data);
	        data.contextPath = _utils.appendContextPath(options.data.contextPath, options.name);
	        options = { data: data };
	      }

	      return fn(context, options);
	    }
	  });
	};

	module.exports = exports['default'];

/***/ }),
/* 12 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';

	var _interopRequireDefault = __webpack_require__(1)['default'];

	exports.__esModule = true;

	var _utils = __webpack_require__(5);

	var _exception = __webpack_require__(6);

	var _exception2 = _interopRequireDefault(_exception);

	exports['default'] = function (instance) {
	  instance.registerHelper('each', function (context, options) {
	    if (!options) {
	      throw new _exception2['default']('Must pass iterator to #each');
	    }

	    var fn = options.fn,
	        inverse = options.inverse,
	        i = 0,
	        ret = '',
	        data = undefined,
	        contextPath = undefined;

	    if (options.data && options.ids) {
	      contextPath = _utils.appendContextPath(options.data.contextPath, options.ids[0]) + '.';
	    }

	    if (_utils.isFunction(context)) {
	      context = context.call(this);
	    }

	    if (options.data) {
	      data = _utils.createFrame(options.data);
	    }

	    function execIteration(field, index, last) {
	      if (data) {
	        data.key = field;
	        data.index = index;
	        data.first = index === 0;
	        data.last = !!last;

	        if (contextPath) {
	          data.contextPath = contextPath + field;
	        }
	      }

	      ret = ret + fn(context[field], {
	        data: data,
	        blockParams: _utils.blockParams([context[field], field], [contextPath + field, null])
	      });
	    }

	    if (context && typeof context === 'object') {
	      if (_utils.isArray(context)) {
	        for (var j = context.length; i < j; i++) {
	          if (i in context) {
	            execIteration(i, i, i === context.length - 1);
	          }
	        }
	      } else {
	        var priorKey = undefined;

	        for (var key in context) {
	          if (context.hasOwnProperty(key)) {
	            // We're running the iterations one step out of sync so we can detect
	            // the last iteration without have to scan the object twice and create
	            // an itermediate keys array.
	            if (priorKey !== undefined) {
	              execIteration(priorKey, i - 1);
	            }
	            priorKey = key;
	            i++;
	          }
	        }
	        if (priorKey !== undefined) {
	          execIteration(priorKey, i - 1, true);
	        }
	      }
	    }

	    if (i === 0) {
	      ret = inverse(this);
	    }

	    return ret;
	  });
	};

	module.exports = exports['default'];

/***/ }),
/* 13 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';

	var _interopRequireDefault = __webpack_require__(1)['default'];

	exports.__esModule = true;

	var _exception = __webpack_require__(6);

	var _exception2 = _interopRequireDefault(_exception);

	exports['default'] = function (instance) {
	  instance.registerHelper('helperMissing', function () /* [args, ]options */{
	    if (arguments.length === 1) {
	      // A missing field in a {{foo}} construct.
	      return undefined;
	    } else {
	      // Someone is actually trying to call something, blow up.
	      throw new _exception2['default']('Missing helper: "' + arguments[arguments.length - 1].name + '"');
	    }
	  });
	};

	module.exports = exports['default'];

/***/ }),
/* 14 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';

	exports.__esModule = true;

	var _utils = __webpack_require__(5);

	exports['default'] = function (instance) {
	  instance.registerHelper('if', function (conditional, options) {
	    if (_utils.isFunction(conditional)) {
	      conditional = conditional.call(this);
	    }

	    // Default behavior is to render the positive path if the value is truthy and not empty.
	    // The `includeZero` option may be set to treat the condtional as purely not empty based on the
	    // behavior of isEmpty. Effectively this determines if 0 is handled by the positive path or negative.
	    if (!options.hash.includeZero && !conditional || _utils.isEmpty(conditional)) {
	      return options.inverse(this);
	    } else {
	      return options.fn(this);
	    }
	  });

	  instance.registerHelper('unless', function (conditional, options) {
	    return instance.helpers['if'].call(this, conditional, { fn: options.inverse, inverse: options.fn, hash: options.hash });
	  });
	};

	module.exports = exports['default'];

/***/ }),
/* 15 */
/***/ (function(module, exports) {

	'use strict';

	exports.__esModule = true;

	exports['default'] = function (instance) {
	  instance.registerHelper('log', function () /* message, options */{
	    var args = [undefined],
	        options = arguments[arguments.length - 1];
	    for (var i = 0; i < arguments.length - 1; i++) {
	      args.push(arguments[i]);
	    }

	    var level = 1;
	    if (options.hash.level != null) {
	      level = options.hash.level;
	    } else if (options.data && options.data.level != null) {
	      level = options.data.level;
	    }
	    args[0] = level;

	    instance.log.apply(instance, args);
	  });
	};

	module.exports = exports['default'];

/***/ }),
/* 16 */
/***/ (function(module, exports) {

	'use strict';

	exports.__esModule = true;

	exports['default'] = function (instance) {
	  instance.registerHelper('lookup', function (obj, field) {
	    return obj && obj[field];
	  });
	};

	module.exports = exports['default'];

/***/ }),
/* 17 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';

	exports.__esModule = true;

	var _utils = __webpack_require__(5);

	exports['default'] = function (instance) {
	  instance.registerHelper('with', function (context, options) {
	    if (_utils.isFunction(context)) {
	      context = context.call(this);
	    }

	    var fn = options.fn;

	    if (!_utils.isEmpty(context)) {
	      var data = options.data;
	      if (options.data && options.ids) {
	        data = _utils.createFrame(options.data);
	        data.contextPath = _utils.appendContextPath(options.data.contextPath, options.ids[0]);
	      }

	      return fn(context, {
	        data: data,
	        blockParams: _utils.blockParams([context], [data && data.contextPath])
	      });
	    } else {
	      return options.inverse(this);
	    }
	  });
	};

	module.exports = exports['default'];

/***/ }),
/* 18 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';

	var _interopRequireDefault = __webpack_require__(1)['default'];

	exports.__esModule = true;
	exports.registerDefaultDecorators = registerDefaultDecorators;

	var _decoratorsInline = __webpack_require__(19);

	var _decoratorsInline2 = _interopRequireDefault(_decoratorsInline);

	function registerDefaultDecorators(instance) {
	  _decoratorsInline2['default'](instance);
	}

/***/ }),
/* 19 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';

	exports.__esModule = true;

	var _utils = __webpack_require__(5);

	exports['default'] = function (instance) {
	  instance.registerDecorator('inline', function (fn, props, container, options) {
	    var ret = fn;
	    if (!props.partials) {
	      props.partials = {};
	      ret = function (context, options) {
	        // Create a new partials stack frame prior to exec.
	        var original = container.partials;
	        container.partials = _utils.extend({}, original, props.partials);
	        var ret = fn(context, options);
	        container.partials = original;
	        return ret;
	      };
	    }

	    props.partials[options.args[0]] = options.fn;

	    return ret;
	  });
	};

	module.exports = exports['default'];

/***/ }),
/* 20 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';

	exports.__esModule = true;

	var _utils = __webpack_require__(5);

	var logger = {
	  methodMap: ['debug', 'info', 'warn', 'error'],
	  level: 'info',

	  // Maps a given level value to the `methodMap` indexes above.
	  lookupLevel: function lookupLevel(level) {
	    if (typeof level === 'string') {
	      var levelMap = _utils.indexOf(logger.methodMap, level.toLowerCase());
	      if (levelMap >= 0) {
	        level = levelMap;
	      } else {
	        level = parseInt(level, 10);
	      }
	    }

	    return level;
	  },

	  // Can be overridden in the host environment
	  log: function log(level) {
	    level = logger.lookupLevel(level);

	    if (typeof console !== 'undefined' && logger.lookupLevel(logger.level) <= level) {
	      var method = logger.methodMap[level];
	      if (!console[method]) {
	        // eslint-disable-line no-console
	        method = 'log';
	      }

	      for (var _len = arguments.length, message = Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
	        message[_key - 1] = arguments[_key];
	      }

	      console[method].apply(console, message); // eslint-disable-line no-console
	    }
	  }
	};

	exports['default'] = logger;
	module.exports = exports['default'];

/***/ }),
/* 21 */
/***/ (function(module, exports) {

	// Build out our basic SafeString type
	'use strict';

	exports.__esModule = true;
	function SafeString(string) {
	  this.string = string;
	}

	SafeString.prototype.toString = SafeString.prototype.toHTML = function () {
	  return '' + this.string;
	};

	exports['default'] = SafeString;
	module.exports = exports['default'];

/***/ }),
/* 22 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';

	var _Object$seal = __webpack_require__(23)['default'];

	var _interopRequireWildcard = __webpack_require__(3)['default'];

	var _interopRequireDefault = __webpack_require__(1)['default'];

	exports.__esModule = true;
	exports.checkRevision = checkRevision;
	exports.template = template;
	exports.wrapProgram = wrapProgram;
	exports.resolvePartial = resolvePartial;
	exports.invokePartial = invokePartial;
	exports.noop = noop;

	var _utils = __webpack_require__(5);

	var Utils = _interopRequireWildcard(_utils);

	var _exception = __webpack_require__(6);

	var _exception2 = _interopRequireDefault(_exception);

	var _base = __webpack_require__(4);

	function checkRevision(compilerInfo) {
	  var compilerRevision = compilerInfo && compilerInfo[0] || 1,
	      currentRevision = _base.COMPILER_REVISION;

	  if (compilerRevision !== currentRevision) {
	    if (compilerRevision < currentRevision) {
	      var runtimeVersions = _base.REVISION_CHANGES[currentRevision],
	          compilerVersions = _base.REVISION_CHANGES[compilerRevision];
	      throw new _exception2['default']('Template was precompiled with an older version of Handlebars than the current runtime. ' + 'Please update your precompiler to a newer version (' + runtimeVersions + ') or downgrade your runtime to an older version (' + compilerVersions + ').');
	    } else {
	      // Use the embedded version info since the runtime doesn't know about this revision yet
	      throw new _exception2['default']('Template was precompiled with a newer version of Handlebars than the current runtime. ' + 'Please update your runtime to a newer version (' + compilerInfo[1] + ').');
	    }
	  }
	}

	function template(templateSpec, env) {
	  /* istanbul ignore next */
	  if (!env) {
	    throw new _exception2['default']('No environment passed to template');
	  }
	  if (!templateSpec || !templateSpec.main) {
	    throw new _exception2['default']('Unknown template object: ' + typeof templateSpec);
	  }

	  templateSpec.main.decorator = templateSpec.main_d;

	  // Note: Using env.VM references rather than local var references throughout this section to allow
	  // for external users to override these as psuedo-supported APIs.
	  env.VM.checkRevision(templateSpec.compiler);

	  function invokePartialWrapper(partial, context, options) {
	    if (options.hash) {
	      context = Utils.extend({}, context, options.hash);
	      if (options.ids) {
	        options.ids[0] = true;
	      }
	    }

	    partial = env.VM.resolvePartial.call(this, partial, context, options);
	    var result = env.VM.invokePartial.call(this, partial, context, options);

	    if (result == null && env.compile) {
	      options.partials[options.name] = env.compile(partial, templateSpec.compilerOptions, env);
	      result = options.partials[options.name](context, options);
	    }
	    if (result != null) {
	      if (options.indent) {
	        var lines = result.split('\n');
	        for (var i = 0, l = lines.length; i < l; i++) {
	          if (!lines[i] && i + 1 === l) {
	            break;
	          }

	          lines[i] = options.indent + lines[i];
	        }
	        result = lines.join('\n');
	      }
	      return result;
	    } else {
	      throw new _exception2['default']('The partial ' + options.name + ' could not be compiled when running in runtime-only mode');
	    }
	  }

	  // Just add water
	  var container = {
	    strict: function strict(obj, name) {
	      if (!(name in obj)) {
	        throw new _exception2['default']('"' + name + '" not defined in ' + obj);
	      }
	      return obj[name];
	    },
	    lookup: function lookup(depths, name) {
	      var len = depths.length;
	      for (var i = 0; i < len; i++) {
	        if (depths[i] && depths[i][name] != null) {
	          return depths[i][name];
	        }
	      }
	    },
	    lambda: function lambda(current, context) {
	      return typeof current === 'function' ? current.call(context) : current;
	    },

	    escapeExpression: Utils.escapeExpression,
	    invokePartial: invokePartialWrapper,

	    fn: function fn(i) {
	      var ret = templateSpec[i];
	      ret.decorator = templateSpec[i + '_d'];
	      return ret;
	    },

	    programs: [],
	    program: function program(i, data, declaredBlockParams, blockParams, depths) {
	      var programWrapper = this.programs[i],
	          fn = this.fn(i);
	      if (data || depths || blockParams || declaredBlockParams) {
	        programWrapper = wrapProgram(this, i, fn, data, declaredBlockParams, blockParams, depths);
	      } else if (!programWrapper) {
	        programWrapper = this.programs[i] = wrapProgram(this, i, fn);
	      }
	      return programWrapper;
	    },

	    data: function data(value, depth) {
	      while (value && depth--) {
	        value = value._parent;
	      }
	      return value;
	    },
	    merge: function merge(param, common) {
	      var obj = param || common;

	      if (param && common && param !== common) {
	        obj = Utils.extend({}, common, param);
	      }

	      return obj;
	    },
	    // An empty object to use as replacement for null-contexts
	    nullContext: _Object$seal({}),

	    noop: env.VM.noop,
	    compilerInfo: templateSpec.compiler
	  };

	  function ret(context) {
	    var options = arguments.length <= 1 || arguments[1] === undefined ? {} : arguments[1];

	    var data = options.data;

	    ret._setup(options);
	    if (!options.partial && templateSpec.useData) {
	      data = initData(context, data);
	    }
	    var depths = undefined,
	        blockParams = templateSpec.useBlockParams ? [] : undefined;
	    if (templateSpec.useDepths) {
	      if (options.depths) {
	        depths = context != options.depths[0] ? [context].concat(options.depths) : options.depths;
	      } else {
	        depths = [context];
	      }
	    }

	    function main(context /*, options*/) {
	      return '' + templateSpec.main(container, context, container.helpers, container.partials, data, blockParams, depths);
	    }
	    main = executeDecorators(templateSpec.main, main, container, options.depths || [], data, blockParams);
	    return main(context, options);
	  }
	  ret.isTop = true;

	  ret._setup = function (options) {
	    if (!options.partial) {
	      container.helpers = container.merge(options.helpers, env.helpers);

	      if (templateSpec.usePartial) {
	        container.partials = container.merge(options.partials, env.partials);
	      }
	      if (templateSpec.usePartial || templateSpec.useDecorators) {
	        container.decorators = container.merge(options.decorators, env.decorators);
	      }
	    } else {
	      container.helpers = options.helpers;
	      container.partials = options.partials;
	      container.decorators = options.decorators;
	    }
	  };

	  ret._child = function (i, data, blockParams, depths) {
	    if (templateSpec.useBlockParams && !blockParams) {
	      throw new _exception2['default']('must pass block params');
	    }
	    if (templateSpec.useDepths && !depths) {
	      throw new _exception2['default']('must pass parent depths');
	    }

	    return wrapProgram(container, i, templateSpec[i], data, 0, blockParams, depths);
	  };
	  return ret;
	}

	function wrapProgram(container, i, fn, data, declaredBlockParams, blockParams, depths) {
	  function prog(context) {
	    var options = arguments.length <= 1 || arguments[1] === undefined ? {} : arguments[1];

	    var currentDepths = depths;
	    if (depths && context != depths[0] && !(context === container.nullContext && depths[0] === null)) {
	      currentDepths = [context].concat(depths);
	    }

	    return fn(container, context, container.helpers, container.partials, options.data || data, blockParams && [options.blockParams].concat(blockParams), currentDepths);
	  }

	  prog = executeDecorators(fn, prog, container, depths, data, blockParams);

	  prog.program = i;
	  prog.depth = depths ? depths.length : 0;
	  prog.blockParams = declaredBlockParams || 0;
	  return prog;
	}

	function resolvePartial(partial, context, options) {
	  if (!partial) {
	    if (options.name === '@partial-block') {
	      partial = options.data['partial-block'];
	    } else {
	      partial = options.partials[options.name];
	    }
	  } else if (!partial.call && !options.name) {
	    // This is a dynamic partial that returned a string
	    options.name = partial;
	    partial = options.partials[partial];
	  }
	  return partial;
	}

	function invokePartial(partial, context, options) {
	  // Use the current closure context to save the partial-block if this partial
	  var currentPartialBlock = options.data && options.data['partial-block'];
	  options.partial = true;
	  if (options.ids) {
	    options.data.contextPath = options.ids[0] || options.data.contextPath;
	  }

	  var partialBlock = undefined;
	  if (options.fn && options.fn !== noop) {
	    (function () {
	      options.data = _base.createFrame(options.data);
	      // Wrapper function to get access to currentPartialBlock from the closure
	      var fn = options.fn;
	      partialBlock = options.data['partial-block'] = function partialBlockWrapper(context) {
	        var options = arguments.length <= 1 || arguments[1] === undefined ? {} : arguments[1];

	        // Restore the partial-block from the closure for the execution of the block
	        // i.e. the part inside the block of the partial call.
	        options.data = _base.createFrame(options.data);
	        options.data['partial-block'] = currentPartialBlock;
	        return fn(context, options);
	      };
	      if (fn.partials) {
	        options.partials = Utils.extend({}, options.partials, fn.partials);
	      }
	    })();
	  }

	  if (partial === undefined && partialBlock) {
	    partial = partialBlock;
	  }

	  if (partial === undefined) {
	    throw new _exception2['default']('The partial ' + options.name + ' could not be found');
	  } else if (partial instanceof Function) {
	    return partial(context, options);
	  }
	}

	function noop() {
	  return '';
	}

	function initData(context, data) {
	  if (!data || !('root' in data)) {
	    data = data ? _base.createFrame(data) : {};
	    data.root = context;
	  }
	  return data;
	}

	function executeDecorators(fn, prog, container, depths, data, blockParams) {
	  if (fn.decorator) {
	    var props = {};
	    prog = fn.decorator(prog, props, container, depths && depths[0], data, blockParams, depths);
	    Utils.extend(prog, props);
	  }
	  return prog;
	}

/***/ }),
/* 23 */
/***/ (function(module, exports, __webpack_require__) {

	module.exports = { "default": __webpack_require__(24), __esModule: true };

/***/ }),
/* 24 */
/***/ (function(module, exports, __webpack_require__) {

	__webpack_require__(25);
	module.exports = __webpack_require__(30).Object.seal;

/***/ }),
/* 25 */
/***/ (function(module, exports, __webpack_require__) {

	// 19.1.2.17 Object.seal(O)
	var isObject = __webpack_require__(26);

	__webpack_require__(27)('seal', function($seal){
	  return function seal(it){
	    return $seal && isObject(it) ? $seal(it) : it;
	  };
	});

/***/ }),
/* 26 */
/***/ (function(module, exports) {

	module.exports = function(it){
	  return typeof it === 'object' ? it !== null : typeof it === 'function';
	};

/***/ }),
/* 27 */
/***/ (function(module, exports, __webpack_require__) {

	// most Object methods by ES6 should accept primitives
	var $export = __webpack_require__(28)
	  , core    = __webpack_require__(30)
	  , fails   = __webpack_require__(33);
	module.exports = function(KEY, exec){
	  var fn  = (core.Object || {})[KEY] || Object[KEY]
	    , exp = {};
	  exp[KEY] = exec(fn);
	  $export($export.S + $export.F * fails(function(){ fn(1); }), 'Object', exp);
	};

/***/ }),
/* 28 */
/***/ (function(module, exports, __webpack_require__) {

	var global    = __webpack_require__(29)
	  , core      = __webpack_require__(30)
	  , ctx       = __webpack_require__(31)
	  , PROTOTYPE = 'prototype';

	var $export = function(type, name, source){
	  var IS_FORCED = type & $export.F
	    , IS_GLOBAL = type & $export.G
	    , IS_STATIC = type & $export.S
	    , IS_PROTO  = type & $export.P
	    , IS_BIND   = type & $export.B
	    , IS_WRAP   = type & $export.W
	    , exports   = IS_GLOBAL ? core : core[name] || (core[name] = {})
	    , target    = IS_GLOBAL ? global : IS_STATIC ? global[name] : (global[name] || {})[PROTOTYPE]
	    , key, own, out;
	  if(IS_GLOBAL)source = name;
	  for(key in source){
	    // contains in native
	    own = !IS_FORCED && target && key in target;
	    if(own && key in exports)continue;
	    // export native or passed
	    out = own ? target[key] : source[key];
	    // prevent global pollution for namespaces
	    exports[key] = IS_GLOBAL && typeof target[key] != 'function' ? source[key]
	    // bind timers to global for call from export context
	    : IS_BIND && own ? ctx(out, global)
	    // wrap global constructors for prevent change them in library
	    : IS_WRAP && target[key] == out ? (function(C){
	      var F = function(param){
	        return this instanceof C ? new C(param) : C(param);
	      };
	      F[PROTOTYPE] = C[PROTOTYPE];
	      return F;
	    // make static versions for prototype methods
	    })(out) : IS_PROTO && typeof out == 'function' ? ctx(Function.call, out) : out;
	    if(IS_PROTO)(exports[PROTOTYPE] || (exports[PROTOTYPE] = {}))[key] = out;
	  }
	};
	// type bitmap
	$export.F = 1;  // forced
	$export.G = 2;  // global
	$export.S = 4;  // static
	$export.P = 8;  // proto
	$export.B = 16; // bind
	$export.W = 32; // wrap
	module.exports = $export;

/***/ }),
/* 29 */
/***/ (function(module, exports) {

	// https://github.com/zloirock/core-js/issues/86#issuecomment-115759028
	var global = module.exports = typeof window != 'undefined' && window.Math == Math
	  ? window : typeof self != 'undefined' && self.Math == Math ? self : Function('return this')();
	if(typeof __g == 'number')__g = global; // eslint-disable-line no-undef

/***/ }),
/* 30 */
/***/ (function(module, exports) {

	var core = module.exports = {version: '1.2.6'};
	if(typeof __e == 'number')__e = core; // eslint-disable-line no-undef

/***/ }),
/* 31 */
/***/ (function(module, exports, __webpack_require__) {

	// optional / simple context binding
	var aFunction = __webpack_require__(32);
	module.exports = function(fn, that, length){
	  aFunction(fn);
	  if(that === undefined)return fn;
	  switch(length){
	    case 1: return function(a){
	      return fn.call(that, a);
	    };
	    case 2: return function(a, b){
	      return fn.call(that, a, b);
	    };
	    case 3: return function(a, b, c){
	      return fn.call(that, a, b, c);
	    };
	  }
	  return function(/* ...args */){
	    return fn.apply(that, arguments);
	  };
	};

/***/ }),
/* 32 */
/***/ (function(module, exports) {

	module.exports = function(it){
	  if(typeof it != 'function')throw TypeError(it + ' is not a function!');
	  return it;
	};

/***/ }),
/* 33 */
/***/ (function(module, exports) {

	module.exports = function(exec){
	  try {
	    return !!exec();
	  } catch(e){
	    return true;
	  }
	};

/***/ }),
/* 34 */
/***/ (function(module, exports) {

	/* WEBPACK VAR INJECTION */(function(global) {/* global window */
	'use strict';

	exports.__esModule = true;

	exports['default'] = function (Handlebars) {
	  /* istanbul ignore next */
	  var root = typeof global !== 'undefined' ? global : window,
	      $Handlebars = root.Handlebars;
	  /* istanbul ignore next */
	  Handlebars.noConflict = function () {
	    if (root.Handlebars === Handlebars) {
	      root.Handlebars = $Handlebars;
	    }
	    return Handlebars;
	  };
	};

	module.exports = exports['default'];
	/* WEBPACK VAR INJECTION */}.call(exports, (function() { return this; }())))

/***/ }),
/* 35 */
/***/ (function(module, exports) {

	'use strict';

	exports.__esModule = true;
	var AST = {
	  // Public API used to evaluate derived attributes regarding AST nodes
	  helpers: {
	    // a mustache is definitely a helper if:
	    // * it is an eligible helper, and
	    // * it has at least one parameter or hash segment
	    helperExpression: function helperExpression(node) {
	      return node.type === 'SubExpression' || (node.type === 'MustacheStatement' || node.type === 'BlockStatement') && !!(node.params && node.params.length || node.hash);
	    },

	    scopedId: function scopedId(path) {
	      return (/^\.|this\b/.test(path.original)
	      );
	    },

	    // an ID is simple if it only has one part, and that part is not
	    // `..` or `this`.
	    simpleId: function simpleId(path) {
	      return path.parts.length === 1 && !AST.helpers.scopedId(path) && !path.depth;
	    }
	  }
	};

	// Must be exported as an object rather than the root of the module as the jison lexer
	// must modify the object to operate properly.
	exports['default'] = AST;
	module.exports = exports['default'];

/***/ }),
/* 36 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';

	var _interopRequireDefault = __webpack_require__(1)['default'];

	var _interopRequireWildcard = __webpack_require__(3)['default'];

	exports.__esModule = true;
	exports.parse = parse;

	var _parser = __webpack_require__(37);

	var _parser2 = _interopRequireDefault(_parser);

	var _whitespaceControl = __webpack_require__(38);

	var _whitespaceControl2 = _interopRequireDefault(_whitespaceControl);

	var _helpers = __webpack_require__(40);

	var Helpers = _interopRequireWildcard(_helpers);

	var _utils = __webpack_require__(5);

	exports.parser = _parser2['default'];

	var yy = {};
	_utils.extend(yy, Helpers);

	function parse(input, options) {
	  // Just return if an already-compiled AST was passed in.
	  if (input.type === 'Program') {
	    return input;
	  }

	  _parser2['default'].yy = yy;

	  // Altering the shared object here, but this is ok as parser is a sync operation
	  yy.locInfo = function (locInfo) {
	    return new yy.SourceLocation(options && options.srcName, locInfo);
	  };

	  var strip = new _whitespaceControl2['default'](options);
	  return strip.accept(_parser2['default'].parse(input));
	}

/***/ }),
/* 37 */
/***/ (function(module, exports) {

	// File ignored in coverage tests via setting in .istanbul.yml
	/* Jison generated parser */
	"use strict";

	exports.__esModule = true;
	var handlebars = (function () {
	    var parser = { trace: function trace() {},
	        yy: {},
	        symbols_: { "error": 2, "root": 3, "program": 4, "EOF": 5, "program_repetition0": 6, "statement": 7, "mustache": 8, "block": 9, "rawBlock": 10, "partial": 11, "partialBlock": 12, "content": 13, "COMMENT": 14, "CONTENT": 15, "openRawBlock": 16, "rawBlock_repetition_plus0": 17, "END_RAW_BLOCK": 18, "OPEN_RAW_BLOCK": 19, "helperName": 20, "openRawBlock_repetition0": 21, "openRawBlock_option0": 22, "CLOSE_RAW_BLOCK": 23, "openBlock": 24, "block_option0": 25, "closeBlock": 26, "openInverse": 27, "block_option1": 28, "OPEN_BLOCK": 29, "openBlock_repetition0": 30, "openBlock_option0": 31, "openBlock_option1": 32, "CLOSE": 33, "OPEN_INVERSE": 34, "openInverse_repetition0": 35, "openInverse_option0": 36, "openInverse_option1": 37, "openInverseChain": 38, "OPEN_INVERSE_CHAIN": 39, "openInverseChain_repetition0": 40, "openInverseChain_option0": 41, "openInverseChain_option1": 42, "inverseAndProgram": 43, "INVERSE": 44, "inverseChain": 45, "inverseChain_option0": 46, "OPEN_ENDBLOCK": 47, "OPEN": 48, "mustache_repetition0": 49, "mustache_option0": 50, "OPEN_UNESCAPED": 51, "mustache_repetition1": 52, "mustache_option1": 53, "CLOSE_UNESCAPED": 54, "OPEN_PARTIAL": 55, "partialName": 56, "partial_repetition0": 57, "partial_option0": 58, "openPartialBlock": 59, "OPEN_PARTIAL_BLOCK": 60, "openPartialBlock_repetition0": 61, "openPartialBlock_option0": 62, "param": 63, "sexpr": 64, "OPEN_SEXPR": 65, "sexpr_repetition0": 66, "sexpr_option0": 67, "CLOSE_SEXPR": 68, "hash": 69, "hash_repetition_plus0": 70, "hashSegment": 71, "ID": 72, "EQUALS": 73, "blockParams": 74, "OPEN_BLOCK_PARAMS": 75, "blockParams_repetition_plus0": 76, "CLOSE_BLOCK_PARAMS": 77, "path": 78, "dataName": 79, "STRING": 80, "NUMBER": 81, "BOOLEAN": 82, "UNDEFINED": 83, "NULL": 84, "DATA": 85, "pathSegments": 86, "SEP": 87, "$accept": 0, "$end": 1 },
	        terminals_: { 2: "error", 5: "EOF", 14: "COMMENT", 15: "CONTENT", 18: "END_RAW_BLOCK", 19: "OPEN_RAW_BLOCK", 23: "CLOSE_RAW_BLOCK", 29: "OPEN_BLOCK", 33: "CLOSE", 34: "OPEN_INVERSE", 39: "OPEN_INVERSE_CHAIN", 44: "INVERSE", 47: "OPEN_ENDBLOCK", 48: "OPEN", 51: "OPEN_UNESCAPED", 54: "CLOSE_UNESCAPED", 55: "OPEN_PARTIAL", 60: "OPEN_PARTIAL_BLOCK", 65: "OPEN_SEXPR", 68: "CLOSE_SEXPR", 72: "ID", 73: "EQUALS", 75: "OPEN_BLOCK_PARAMS", 77: "CLOSE_BLOCK_PARAMS", 80: "STRING", 81: "NUMBER", 82: "BOOLEAN", 83: "UNDEFINED", 84: "NULL", 85: "DATA", 87: "SEP" },
	        productions_: [0, [3, 2], [4, 1], [7, 1], [7, 1], [7, 1], [7, 1], [7, 1], [7, 1], [7, 1], [13, 1], [10, 3], [16, 5], [9, 4], [9, 4], [24, 6], [27, 6], [38, 6], [43, 2], [45, 3], [45, 1], [26, 3], [8, 5], [8, 5], [11, 5], [12, 3], [59, 5], [63, 1], [63, 1], [64, 5], [69, 1], [71, 3], [74, 3], [20, 1], [20, 1], [20, 1], [20, 1], [20, 1], [20, 1], [20, 1], [56, 1], [56, 1], [79, 2], [78, 1], [86, 3], [86, 1], [6, 0], [6, 2], [17, 1], [17, 2], [21, 0], [21, 2], [22, 0], [22, 1], [25, 0], [25, 1], [28, 0], [28, 1], [30, 0], [30, 2], [31, 0], [31, 1], [32, 0], [32, 1], [35, 0], [35, 2], [36, 0], [36, 1], [37, 0], [37, 1], [40, 0], [40, 2], [41, 0], [41, 1], [42, 0], [42, 1], [46, 0], [46, 1], [49, 0], [49, 2], [50, 0], [50, 1], [52, 0], [52, 2], [53, 0], [53, 1], [57, 0], [57, 2], [58, 0], [58, 1], [61, 0], [61, 2], [62, 0], [62, 1], [66, 0], [66, 2], [67, 0], [67, 1], [70, 1], [70, 2], [76, 1], [76, 2]],
	        performAction: function anonymous(yytext, yyleng, yylineno, yy, yystate, $$, _$
	        /**/) {

	            var $0 = $$.length - 1;
	            switch (yystate) {
	                case 1:
	                    return $$[$0 - 1];
	                    break;
	                case 2:
	                    this.$ = yy.prepareProgram($$[$0]);
	                    break;
	                case 3:
	                    this.$ = $$[$0];
	                    break;
	                case 4:
	                    this.$ = $$[$0];
	                    break;
	                case 5:
	                    this.$ = $$[$0];
	                    break;
	                case 6:
	                    this.$ = $$[$0];
	                    break;
	                case 7:
	                    this.$ = $$[$0];
	                    break;
	                case 8:
	                    this.$ = $$[$0];
	                    break;
	                case 9:
	                    this.$ = {
	                        type: 'CommentStatement',
	                        value: yy.stripComment($$[$0]),
	                        strip: yy.stripFlags($$[$0], $$[$0]),
	                        loc: yy.locInfo(this._$)
	                    };

	                    break;
	                case 10:
	                    this.$ = {
	                        type: 'ContentStatement',
	                        original: $$[$0],
	                        value: $$[$0],
	                        loc: yy.locInfo(this._$)
	                    };

	                    break;
	                case 11:
	                    this.$ = yy.prepareRawBlock($$[$0 - 2], $$[$0 - 1], $$[$0], this._$);
	                    break;
	                case 12:
	                    this.$ = { path: $$[$0 - 3], params: $$[$0 - 2], hash: $$[$0 - 1] };
	                    break;
	                case 13:
	                    this.$ = yy.prepareBlock($$[$0 - 3], $$[$0 - 2], $$[$0 - 1], $$[$0], false, this._$);
	                    break;
	                case 14:
	                    this.$ = yy.prepareBlock($$[$0 - 3], $$[$0 - 2], $$[$0 - 1], $$[$0], true, this._$);
	                    break;
	                case 15:
	                    this.$ = { open: $$[$0 - 5], path: $$[$0 - 4], params: $$[$0 - 3], hash: $$[$0 - 2], blockParams: $$[$0 - 1], strip: yy.stripFlags($$[$0 - 5], $$[$0]) };
	                    break;
	                case 16:
	                    this.$ = { path: $$[$0 - 4], params: $$[$0 - 3], hash: $$[$0 - 2], blockParams: $$[$0 - 1], strip: yy.stripFlags($$[$0 - 5], $$[$0]) };
	                    break;
	                case 17:
	                    this.$ = { path: $$[$0 - 4], params: $$[$0 - 3], hash: $$[$0 - 2], blockParams: $$[$0 - 1], strip: yy.stripFlags($$[$0 - 5], $$[$0]) };
	                    break;
	                case 18:
	                    this.$ = { strip: yy.stripFlags($$[$0 - 1], $$[$0 - 1]), program: $$[$0] };
	                    break;
	                case 19:
	                    var inverse = yy.prepareBlock($$[$0 - 2], $$[$0 - 1], $$[$0], $$[$0], false, this._$),
	                        program = yy.prepareProgram([inverse], $$[$0 - 1].loc);
	                    program.chained = true;

	                    this.$ = { strip: $$[$0 - 2].strip, program: program, chain: true };

	                    break;
	                case 20:
	                    this.$ = $$[$0];
	                    break;
	                case 21:
	                    this.$ = { path: $$[$0 - 1], strip: yy.stripFlags($$[$0 - 2], $$[$0]) };
	                    break;
	                case 22:
	                    this.$ = yy.prepareMustache($$[$0 - 3], $$[$0 - 2], $$[$0 - 1], $$[$0 - 4], yy.stripFlags($$[$0 - 4], $$[$0]), this._$);
	                    break;
	                case 23:
	                    this.$ = yy.prepareMustache($$[$0 - 3], $$[$0 - 2], $$[$0 - 1], $$[$0 - 4], yy.stripFlags($$[$0 - 4], $$[$0]), this._$);
	                    break;
	                case 24:
	                    this.$ = {
	                        type: 'PartialStatement',
	                        name: $$[$0 - 3],
	                        params: $$[$0 - 2],
	                        hash: $$[$0 - 1],
	                        indent: '',
	                        strip: yy.stripFlags($$[$0 - 4], $$[$0]),
	                        loc: yy.locInfo(this._$)
	                    };

	                    break;
	                case 25:
	                    this.$ = yy.preparePartialBlock($$[$0 - 2], $$[$0 - 1], $$[$0], this._$);
	                    break;
	                case 26:
	                    this.$ = { path: $$[$0 - 3], params: $$[$0 - 2], hash: $$[$0 - 1], strip: yy.stripFlags($$[$0 - 4], $$[$0]) };
	                    break;
	                case 27:
	                    this.$ = $$[$0];
	                    break;
	                case 28:
	                    this.$ = $$[$0];
	                    break;
	                case 29:
	                    this.$ = {
	                        type: 'SubExpression',
	                        path: $$[$0 - 3],
	                        params: $$[$0 - 2],
	                        hash: $$[$0 - 1],
	                        loc: yy.locInfo(this._$)
	                    };

	                    break;
	                case 30:
	                    this.$ = { type: 'Hash', pairs: $$[$0], loc: yy.locInfo(this._$) };
	                    break;
	                case 31:
	                    this.$ = { type: 'HashPair', key: yy.id($$[$0 - 2]), value: $$[$0], loc: yy.locInfo(this._$) };
	                    break;
	                case 32:
	                    this.$ = yy.id($$[$0 - 1]);
	                    break;
	                case 33:
	                    this.$ = $$[$0];
	                    break;
	                case 34:
	                    this.$ = $$[$0];
	                    break;
	                case 35:
	                    this.$ = { type: 'StringLiteral', value: $$[$0], original: $$[$0], loc: yy.locInfo(this._$) };
	                    break;
	                case 36:
	                    this.$ = { type: 'NumberLiteral', value: Number($$[$0]), original: Number($$[$0]), loc: yy.locInfo(this._$) };
	                    break;
	                case 37:
	                    this.$ = { type: 'BooleanLiteral', value: $$[$0] === 'true', original: $$[$0] === 'true', loc: yy.locInfo(this._$) };
	                    break;
	                case 38:
	                    this.$ = { type: 'UndefinedLiteral', original: undefined, value: undefined, loc: yy.locInfo(this._$) };
	                    break;
	                case 39:
	                    this.$ = { type: 'NullLiteral', original: null, value: null, loc: yy.locInfo(this._$) };
	                    break;
	                case 40:
	                    this.$ = $$[$0];
	                    break;
	                case 41:
	                    this.$ = $$[$0];
	                    break;
	                case 42:
	                    this.$ = yy.preparePath(true, $$[$0], this._$);
	                    break;
	                case 43:
	                    this.$ = yy.preparePath(false, $$[$0], this._$);
	                    break;
	                case 44:
	                    $$[$0 - 2].push({ part: yy.id($$[$0]), original: $$[$0], separator: $$[$0 - 1] });this.$ = $$[$0 - 2];
	                    break;
	                case 45:
	                    this.$ = [{ part: yy.id($$[$0]), original: $$[$0] }];
	                    break;
	                case 46:
	                    this.$ = [];
	                    break;
	                case 47:
	                    $$[$0 - 1].push($$[$0]);
	                    break;
	                case 48:
	                    this.$ = [$$[$0]];
	                    break;
	                case 49:
	                    $$[$0 - 1].push($$[$0]);
	                    break;
	                case 50:
	                    this.$ = [];
	                    break;
	                case 51:
	                    $$[$0 - 1].push($$[$0]);
	                    break;
	                case 58:
	                    this.$ = [];
	                    break;
	                case 59:
	                    $$[$0 - 1].push($$[$0]);
	                    break;
	                case 64:
	                    this.$ = [];
	                    break;
	                case 65:
	                    $$[$0 - 1].push($$[$0]);
	                    break;
	                case 70:
	                    this.$ = [];
	                    break;
	                case 71:
	                    $$[$0 - 1].push($$[$0]);
	                    break;
	                case 78:
	                    this.$ = [];
	                    break;
	                case 79:
	                    $$[$0 - 1].push($$[$0]);
	                    break;
	                case 82:
	                    this.$ = [];
	                    break;
	                case 83:
	                    $$[$0 - 1].push($$[$0]);
	                    break;
	                case 86:
	                    this.$ = [];
	                    break;
	                case 87:
	                    $$[$0 - 1].push($$[$0]);
	                    break;
	                case 90:
	                    this.$ = [];
	                    break;
	                case 91:
	                    $$[$0 - 1].push($$[$0]);
	                    break;
	                case 94:
	                    this.$ = [];
	                    break;
	                case 95:
	                    $$[$0 - 1].push($$[$0]);
	                    break;
	                case 98:
	                    this.$ = [$$[$0]];
	                    break;
	                case 99:
	                    $$[$0 - 1].push($$[$0]);
	                    break;
	                case 100:
	                    this.$ = [$$[$0]];
	                    break;
	                case 101:
	                    $$[$0 - 1].push($$[$0]);
	                    break;
	            }
	        },
	        table: [{ 3: 1, 4: 2, 5: [2, 46], 6: 3, 14: [2, 46], 15: [2, 46], 19: [2, 46], 29: [2, 46], 34: [2, 46], 48: [2, 46], 51: [2, 46], 55: [2, 46], 60: [2, 46] }, { 1: [3] }, { 5: [1, 4] }, { 5: [2, 2], 7: 5, 8: 6, 9: 7, 10: 8, 11: 9, 12: 10, 13: 11, 14: [1, 12], 15: [1, 20], 16: 17, 19: [1, 23], 24: 15, 27: 16, 29: [1, 21], 34: [1, 22], 39: [2, 2], 44: [2, 2], 47: [2, 2], 48: [1, 13], 51: [1, 14], 55: [1, 18], 59: 19, 60: [1, 24] }, { 1: [2, 1] }, { 5: [2, 47], 14: [2, 47], 15: [2, 47], 19: [2, 47], 29: [2, 47], 34: [2, 47], 39: [2, 47], 44: [2, 47], 47: [2, 47], 48: [2, 47], 51: [2, 47], 55: [2, 47], 60: [2, 47] }, { 5: [2, 3], 14: [2, 3], 15: [2, 3], 19: [2, 3], 29: [2, 3], 34: [2, 3], 39: [2, 3], 44: [2, 3], 47: [2, 3], 48: [2, 3], 51: [2, 3], 55: [2, 3], 60: [2, 3] }, { 5: [2, 4], 14: [2, 4], 15: [2, 4], 19: [2, 4], 29: [2, 4], 34: [2, 4], 39: [2, 4], 44: [2, 4], 47: [2, 4], 48: [2, 4], 51: [2, 4], 55: [2, 4], 60: [2, 4] }, { 5: [2, 5], 14: [2, 5], 15: [2, 5], 19: [2, 5], 29: [2, 5], 34: [2, 5], 39: [2, 5], 44: [2, 5], 47: [2, 5], 48: [2, 5], 51: [2, 5], 55: [2, 5], 60: [2, 5] }, { 5: [2, 6], 14: [2, 6], 15: [2, 6], 19: [2, 6], 29: [2, 6], 34: [2, 6], 39: [2, 6], 44: [2, 6], 47: [2, 6], 48: [2, 6], 51: [2, 6], 55: [2, 6], 60: [2, 6] }, { 5: [2, 7], 14: [2, 7], 15: [2, 7], 19: [2, 7], 29: [2, 7], 34: [2, 7], 39: [2, 7], 44: [2, 7], 47: [2, 7], 48: [2, 7], 51: [2, 7], 55: [2, 7], 60: [2, 7] }, { 5: [2, 8], 14: [2, 8], 15: [2, 8], 19: [2, 8], 29: [2, 8], 34: [2, 8], 39: [2, 8], 44: [2, 8], 47: [2, 8], 48: [2, 8], 51: [2, 8], 55: [2, 8], 60: [2, 8] }, { 5: [2, 9], 14: [2, 9], 15: [2, 9], 19: [2, 9], 29: [2, 9], 34: [2, 9], 39: [2, 9], 44: [2, 9], 47: [2, 9], 48: [2, 9], 51: [2, 9], 55: [2, 9], 60: [2, 9] }, { 20: 25, 72: [1, 35], 78: 26, 79: 27, 80: [1, 28], 81: [1, 29], 82: [1, 30], 83: [1, 31], 84: [1, 32], 85: [1, 34], 86: 33 }, { 20: 36, 72: [1, 35], 78: 26, 79: 27, 80: [1, 28], 81: [1, 29], 82: [1, 30], 83: [1, 31], 84: [1, 32], 85: [1, 34], 86: 33 }, { 4: 37, 6: 3, 14: [2, 46], 15: [2, 46], 19: [2, 46], 29: [2, 46], 34: [2, 46], 39: [2, 46], 44: [2, 46], 47: [2, 46], 48: [2, 46], 51: [2, 46], 55: [2, 46], 60: [2, 46] }, { 4: 38, 6: 3, 14: [2, 46], 15: [2, 46], 19: [2, 46], 29: [2, 46], 34: [2, 46], 44: [2, 46], 47: [2, 46], 48: [2, 46], 51: [2, 46], 55: [2, 46], 60: [2, 46] }, { 13: 40, 15: [1, 20], 17: 39 }, { 20: 42, 56: 41, 64: 43, 65: [1, 44], 72: [1, 35], 78: 26, 79: 27, 80: [1, 28], 81: [1, 29], 82: [1, 30], 83: [1, 31], 84: [1, 32], 85: [1, 34], 86: 33 }, { 4: 45, 6: 3, 14: [2, 46], 15: [2, 46], 19: [2, 46], 29: [2, 46], 34: [2, 46], 47: [2, 46], 48: [2, 46], 51: [2, 46], 55: [2, 46], 60: [2, 46] }, { 5: [2, 10], 14: [2, 10], 15: [2, 10], 18: [2, 10], 19: [2, 10], 29: [2, 10], 34: [2, 10], 39: [2, 10], 44: [2, 10], 47: [2, 10], 48: [2, 10], 51: [2, 10], 55: [2, 10], 60: [2, 10] }, { 20: 46, 72: [1, 35], 78: 26, 79: 27, 80: [1, 28], 81: [1, 29], 82: [1, 30], 83: [1, 31], 84: [1, 32], 85: [1, 34], 86: 33 }, { 20: 47, 72: [1, 35], 78: 26, 79: 27, 80: [1, 28], 81: [1, 29], 82: [1, 30], 83: [1, 31], 84: [1, 32], 85: [1, 34], 86: 33 }, { 20: 48, 72: [1, 35], 78: 26, 79: 27, 80: [1, 28], 81: [1, 29], 82: [1, 30], 83: [1, 31], 84: [1, 32], 85: [1, 34], 86: 33 }, { 20: 42, 56: 49, 64: 43, 65: [1, 44], 72: [1, 35], 78: 26, 79: 27, 80: [1, 28], 81: [1, 29], 82: [1, 30], 83: [1, 31], 84: [1, 32], 85: [1, 34], 86: 33 }, { 33: [2, 78], 49: 50, 65: [2, 78], 72: [2, 78], 80: [2, 78], 81: [2, 78], 82: [2, 78], 83: [2, 78], 84: [2, 78], 85: [2, 78] }, { 23: [2, 33], 33: [2, 33], 54: [2, 33], 65: [2, 33], 68: [2, 33], 72: [2, 33], 75: [2, 33], 80: [2, 33], 81: [2, 33], 82: [2, 33], 83: [2, 33], 84: [2, 33], 85: [2, 33] }, { 23: [2, 34], 33: [2, 34], 54: [2, 34], 65: [2, 34], 68: [2, 34], 72: [2, 34], 75: [2, 34], 80: [2, 34], 81: [2, 34], 82: [2, 34], 83: [2, 34], 84: [2, 34], 85: [2, 34] }, { 23: [2, 35], 33: [2, 35], 54: [2, 35], 65: [2, 35], 68: [2, 35], 72: [2, 35], 75: [2, 35], 80: [2, 35], 81: [2, 35], 82: [2, 35], 83: [2, 35], 84: [2, 35], 85: [2, 35] }, { 23: [2, 36], 33: [2, 36], 54: [2, 36], 65: [2, 36], 68: [2, 36], 72: [2, 36], 75: [2, 36], 80: [2, 36], 81: [2, 36], 82: [2, 36], 83: [2, 36], 84: [2, 36], 85: [2, 36] }, { 23: [2, 37], 33: [2, 37], 54: [2, 37], 65: [2, 37], 68: [2, 37], 72: [2, 37], 75: [2, 37], 80: [2, 37], 81: [2, 37], 82: [2, 37], 83: [2, 37], 84: [2, 37], 85: [2, 37] }, { 23: [2, 38], 33: [2, 38], 54: [2, 38], 65: [2, 38], 68: [2, 38], 72: [2, 38], 75: [2, 38], 80: [2, 38], 81: [2, 38], 82: [2, 38], 83: [2, 38], 84: [2, 38], 85: [2, 38] }, { 23: [2, 39], 33: [2, 39], 54: [2, 39], 65: [2, 39], 68: [2, 39], 72: [2, 39], 75: [2, 39], 80: [2, 39], 81: [2, 39], 82: [2, 39], 83: [2, 39], 84: [2, 39], 85: [2, 39] }, { 23: [2, 43], 33: [2, 43], 54: [2, 43], 65: [2, 43], 68: [2, 43], 72: [2, 43], 75: [2, 43], 80: [2, 43], 81: [2, 43], 82: [2, 43], 83: [2, 43], 84: [2, 43], 85: [2, 43], 87: [1, 51] }, { 72: [1, 35], 86: 52 }, { 23: [2, 45], 33: [2, 45], 54: [2, 45], 65: [2, 45], 68: [2, 45], 72: [2, 45], 75: [2, 45], 80: [2, 45], 81: [2, 45], 82: [2, 45], 83: [2, 45], 84: [2, 45], 85: [2, 45], 87: [2, 45] }, { 52: 53, 54: [2, 82], 65: [2, 82], 72: [2, 82], 80: [2, 82], 81: [2, 82], 82: [2, 82], 83: [2, 82], 84: [2, 82], 85: [2, 82] }, { 25: 54, 38: 56, 39: [1, 58], 43: 57, 44: [1, 59], 45: 55, 47: [2, 54] }, { 28: 60, 43: 61, 44: [1, 59], 47: [2, 56] }, { 13: 63, 15: [1, 20], 18: [1, 62] }, { 15: [2, 48], 18: [2, 48] }, { 33: [2, 86], 57: 64, 65: [2, 86], 72: [2, 86], 80: [2, 86], 81: [2, 86], 82: [2, 86], 83: [2, 86], 84: [2, 86], 85: [2, 86] }, { 33: [2, 40], 65: [2, 40], 72: [2, 40], 80: [2, 40], 81: [2, 40], 82: [2, 40], 83: [2, 40], 84: [2, 40], 85: [2, 40] }, { 33: [2, 41], 65: [2, 41], 72: [2, 41], 80: [2, 41], 81: [2, 41], 82: [2, 41], 83: [2, 41], 84: [2, 41], 85: [2, 41] }, { 20: 65, 72: [1, 35], 78: 26, 79: 27, 80: [1, 28], 81: [1, 29], 82: [1, 30], 83: [1, 31], 84: [1, 32], 85: [1, 34], 86: 33 }, { 26: 66, 47: [1, 67] }, { 30: 68, 33: [2, 58], 65: [2, 58], 72: [2, 58], 75: [2, 58], 80: [2, 58], 81: [2, 58], 82: [2, 58], 83: [2, 58], 84: [2, 58], 85: [2, 58] }, { 33: [2, 64], 35: 69, 65: [2, 64], 72: [2, 64], 75: [2, 64], 80: [2, 64], 81: [2, 64], 82: [2, 64], 83: [2, 64], 84: [2, 64], 85: [2, 64] }, { 21: 70, 23: [2, 50], 65: [2, 50], 72: [2, 50], 80: [2, 50], 81: [2, 50], 82: [2, 50], 83: [2, 50], 84: [2, 50], 85: [2, 50] }, { 33: [2, 90], 61: 71, 65: [2, 90], 72: [2, 90], 80: [2, 90], 81: [2, 90], 82: [2, 90], 83: [2, 90], 84: [2, 90], 85: [2, 90] }, { 20: 75, 33: [2, 80], 50: 72, 63: 73, 64: 76, 65: [1, 44], 69: 74, 70: 77, 71: 78, 72: [1, 79], 78: 26, 79: 27, 80: [1, 28], 81: [1, 29], 82: [1, 30], 83: [1, 31], 84: [1, 32], 85: [1, 34], 86: 33 }, { 72: [1, 80] }, { 23: [2, 42], 33: [2, 42], 54: [2, 42], 65: [2, 42], 68: [2, 42], 72: [2, 42], 75: [2, 42], 80: [2, 42], 81: [2, 42], 82: [2, 42], 83: [2, 42], 84: [2, 42], 85: [2, 42], 87: [1, 51] }, { 20: 75, 53: 81, 54: [2, 84], 63: 82, 64: 76, 65: [1, 44], 69: 83, 70: 77, 71: 78, 72: [1, 79], 78: 26, 79: 27, 80: [1, 28], 81: [1, 29], 82: [1, 30], 83: [1, 31], 84: [1, 32], 85: [1, 34], 86: 33 }, { 26: 84, 47: [1, 67] }, { 47: [2, 55] }, { 4: 85, 6: 3, 14: [2, 46], 15: [2, 46], 19: [2, 46], 29: [2, 46], 34: [2, 46], 39: [2, 46], 44: [2, 46], 47: [2, 46], 48: [2, 46], 51: [2, 46], 55: [2, 46], 60: [2, 46] }, { 47: [2, 20] }, { 20: 86, 72: [1, 35], 78: 26, 79: 27, 80: [1, 28], 81: [1, 29], 82: [1, 30], 83: [1, 31], 84: [1, 32], 85: [1, 34], 86: 33 }, { 4: 87, 6: 3, 14: [2, 46], 15: [2, 46], 19: [2, 46], 29: [2, 46], 34: [2, 46], 47: [2, 46], 48: [2, 46], 51: [2, 46], 55: [2, 46], 60: [2, 46] }, { 26: 88, 47: [1, 67] }, { 47: [2, 57] }, { 5: [2, 11], 14: [2, 11], 15: [2, 11], 19: [2, 11], 29: [2, 11], 34: [2, 11], 39: [2, 11], 44: [2, 11], 47: [2, 11], 48: [2, 11], 51: [2, 11], 55: [2, 11], 60: [2, 11] }, { 15: [2, 49], 18: [2, 49] }, { 20: 75, 33: [2, 88], 58: 89, 63: 90, 64: 76, 65: [1, 44], 69: 91, 70: 77, 71: 78, 72: [1, 79], 78: 26, 79: 27, 80: [1, 28], 81: [1, 29], 82: [1, 30], 83: [1, 31], 84: [1, 32], 85: [1, 34], 86: 33 }, { 65: [2, 94], 66: 92, 68: [2, 94], 72: [2, 94], 80: [2, 94], 81: [2, 94], 82: [2, 94], 83: [2, 94], 84: [2, 94], 85: [2, 94] }, { 5: [2, 25], 14: [2, 25], 15: [2, 25], 19: [2, 25], 29: [2, 25], 34: [2, 25], 39: [2, 25], 44: [2, 25], 47: [2, 25], 48: [2, 25], 51: [2, 25], 55: [2, 25], 60: [2, 25] }, { 20: 93, 72: [1, 35], 78: 26, 79: 27, 80: [1, 28], 81: [1, 29], 82: [1, 30], 83: [1, 31], 84: [1, 32], 85: [1, 34], 86: 33 }, { 20: 75, 31: 94, 33: [2, 60], 63: 95, 64: 76, 65: [1, 44], 69: 96, 70: 77, 71: 78, 72: [1, 79], 75: [2, 60], 78: 26, 79: 27, 80: [1, 28], 81: [1, 29], 82: [1, 30], 83: [1, 31], 84: [1, 32], 85: [1, 34], 86: 33 }, { 20: 75, 33: [2, 66], 36: 97, 63: 98, 64: 76, 65: [1, 44], 69: 99, 70: 77, 71: 78, 72: [1, 79], 75: [2, 66], 78: 26, 79: 27, 80: [1, 28], 81: [1, 29], 82: [1, 30], 83: [1, 31], 84: [1, 32], 85: [1, 34], 86: 33 }, { 20: 75, 22: 100, 23: [2, 52], 63: 101, 64: 76, 65: [1, 44], 69: 102, 70: 77, 71: 78, 72: [1, 79], 78: 26, 79: 27, 80: [1, 28], 81: [1, 29], 82: [1, 30], 83: [1, 31], 84: [1, 32], 85: [1, 34], 86: 33 }, { 20: 75, 33: [2, 92], 62: 103, 63: 104, 64: 76, 65: [1, 44], 69: 105, 70: 77, 71: 78, 72: [1, 79], 78: 26, 79: 27, 80: [1, 28], 81: [1, 29], 82: [1, 30], 83: [1, 31], 84: [1, 32], 85: [1, 34], 86: 33 }, { 33: [1, 106] }, { 33: [2, 79], 65: [2, 79], 72: [2, 79], 80: [2, 79], 81: [2, 79], 82: [2, 79], 83: [2, 79], 84: [2, 79], 85: [2, 79] }, { 33: [2, 81] }, { 23: [2, 27], 33: [2, 27], 54: [2, 27], 65: [2, 27], 68: [2, 27], 72: [2, 27], 75: [2, 27], 80: [2, 27], 81: [2, 27], 82: [2, 27], 83: [2, 27], 84: [2, 27], 85: [2, 27] }, { 23: [2, 28], 33: [2, 28], 54: [2, 28], 65: [2, 28], 68: [2, 28], 72: [2, 28], 75: [2, 28], 80: [2, 28], 81: [2, 28], 82: [2, 28], 83: [2, 28], 84: [2, 28], 85: [2, 28] }, { 23: [2, 30], 33: [2, 30], 54: [2, 30], 68: [2, 30], 71: 107, 72: [1, 108], 75: [2, 30] }, { 23: [2, 98], 33: [2, 98], 54: [2, 98], 68: [2, 98], 72: [2, 98], 75: [2, 98] }, { 23: [2, 45], 33: [2, 45], 54: [2, 45], 65: [2, 45], 68: [2, 45], 72: [2, 45], 73: [1, 109], 75: [2, 45], 80: [2, 45], 81: [2, 45], 82: [2, 45], 83: [2, 45], 84: [2, 45], 85: [2, 45], 87: [2, 45] }, { 23: [2, 44], 33: [2, 44], 54: [2, 44], 65: [2, 44], 68: [2, 44], 72: [2, 44], 75: [2, 44], 80: [2, 44], 81: [2, 44], 82: [2, 44], 83: [2, 44], 84: [2, 44], 85: [2, 44], 87: [2, 44] }, { 54: [1, 110] }, { 54: [2, 83], 65: [2, 83], 72: [2, 83], 80: [2, 83], 81: [2, 83], 82: [2, 83], 83: [2, 83], 84: [2, 83], 85: [2, 83] }, { 54: [2, 85] }, { 5: [2, 13], 14: [2, 13], 15: [2, 13], 19: [2, 13], 29: [2, 13], 34: [2, 13], 39: [2, 13], 44: [2, 13], 47: [2, 13], 48: [2, 13], 51: [2, 13], 55: [2, 13], 60: [2, 13] }, { 38: 56, 39: [1, 58], 43: 57, 44: [1, 59], 45: 112, 46: 111, 47: [2, 76] }, { 33: [2, 70], 40: 113, 65: [2, 70], 72: [2, 70], 75: [2, 70], 80: [2, 70], 81: [2, 70], 82: [2, 70], 83: [2, 70], 84: [2, 70], 85: [2, 70] }, { 47: [2, 18] }, { 5: [2, 14], 14: [2, 14], 15: [2, 14], 19: [2, 14], 29: [2, 14], 34: [2, 14], 39: [2, 14], 44: [2, 14], 47: [2, 14], 48: [2, 14], 51: [2, 14], 55: [2, 14], 60: [2, 14] }, { 33: [1, 114] }, { 33: [2, 87], 65: [2, 87], 72: [2, 87], 80: [2, 87], 81: [2, 87], 82: [2, 87], 83: [2, 87], 84: [2, 87], 85: [2, 87] }, { 33: [2, 89] }, { 20: 75, 63: 116, 64: 76, 65: [1, 44], 67: 115, 68: [2, 96], 69: 117, 70: 77, 71: 78, 72: [1, 79], 78: 26, 79: 27, 80: [1, 28], 81: [1, 29], 82: [1, 30], 83: [1, 31], 84: [1, 32], 85: [1, 34], 86: 33 }, { 33: [1, 118] }, { 32: 119, 33: [2, 62], 74: 120, 75: [1, 121] }, { 33: [2, 59], 65: [2, 59], 72: [2, 59], 75: [2, 59], 80: [2, 59], 81: [2, 59], 82: [2, 59], 83: [2, 59], 84: [2, 59], 85: [2, 59] }, { 33: [2, 61], 75: [2, 61] }, { 33: [2, 68], 37: 122, 74: 123, 75: [1, 121] }, { 33: [2, 65], 65: [2, 65], 72: [2, 65], 75: [2, 65], 80: [2, 65], 81: [2, 65], 82: [2, 65], 83: [2, 65], 84: [2, 65], 85: [2, 65] }, { 33: [2, 67], 75: [2, 67] }, { 23: [1, 124] }, { 23: [2, 51], 65: [2, 51], 72: [2, 51], 80: [2, 51], 81: [2, 51], 82: [2, 51], 83: [2, 51], 84: [2, 51], 85: [2, 51] }, { 23: [2, 53] }, { 33: [1, 125] }, { 33: [2, 91], 65: [2, 91], 72: [2, 91], 80: [2, 91], 81: [2, 91], 82: [2, 91], 83: [2, 91], 84: [2, 91], 85: [2, 91] }, { 33: [2, 93] }, { 5: [2, 22], 14: [2, 22], 15: [2, 22], 19: [2, 22], 29: [2, 22], 34: [2, 22], 39: [2, 22], 44: [2, 22], 47: [2, 22], 48: [2, 22], 51: [2, 22], 55: [2, 22], 60: [2, 22] }, { 23: [2, 99], 33: [2, 99], 54: [2, 99], 68: [2, 99], 72: [2, 99], 75: [2, 99] }, { 73: [1, 109] }, { 20: 75, 63: 126, 64: 76, 65: [1, 44], 72: [1, 35], 78: 26, 79: 27, 80: [1, 28], 81: [1, 29], 82: [1, 30], 83: [1, 31], 84: [1, 32], 85: [1, 34], 86: 33 }, { 5: [2, 23], 14: [2, 23], 15: [2, 23], 19: [2, 23], 29: [2, 23], 34: [2, 23], 39: [2, 23], 44: [2, 23], 47: [2, 23], 48: [2, 23], 51: [2, 23], 55: [2, 23], 60: [2, 23] }, { 47: [2, 19] }, { 47: [2, 77] }, { 20: 75, 33: [2, 72], 41: 127, 63: 128, 64: 76, 65: [1, 44], 69: 129, 70: 77, 71: 78, 72: [1, 79], 75: [2, 72], 78: 26, 79: 27, 80: [1, 28], 81: [1, 29], 82: [1, 30], 83: [1, 31], 84: [1, 32], 85: [1, 34], 86: 33 }, { 5: [2, 24], 14: [2, 24], 15: [2, 24], 19: [2, 24], 29: [2, 24], 34: [2, 24], 39: [2, 24], 44: [2, 24], 47: [2, 24], 48: [2, 24], 51: [2, 24], 55: [2, 24], 60: [2, 24] }, { 68: [1, 130] }, { 65: [2, 95], 68: [2, 95], 72: [2, 95], 80: [2, 95], 81: [2, 95], 82: [2, 95], 83: [2, 95], 84: [2, 95], 85: [2, 95] }, { 68: [2, 97] }, { 5: [2, 21], 14: [2, 21], 15: [2, 21], 19: [2, 21], 29: [2, 21], 34: [2, 21], 39: [2, 21], 44: [2, 21], 47: [2, 21], 48: [2, 21], 51: [2, 21], 55: [2, 21], 60: [2, 21] }, { 33: [1, 131] }, { 33: [2, 63] }, { 72: [1, 133], 76: 132 }, { 33: [1, 134] }, { 33: [2, 69] }, { 15: [2, 12] }, { 14: [2, 26], 15: [2, 26], 19: [2, 26], 29: [2, 26], 34: [2, 26], 47: [2, 26], 48: [2, 26], 51: [2, 26], 55: [2, 26], 60: [2, 26] }, { 23: [2, 31], 33: [2, 31], 54: [2, 31], 68: [2, 31], 72: [2, 31], 75: [2, 31] }, { 33: [2, 74], 42: 135, 74: 136, 75: [1, 121] }, { 33: [2, 71], 65: [2, 71], 72: [2, 71], 75: [2, 71], 80: [2, 71], 81: [2, 71], 82: [2, 71], 83: [2, 71], 84: [2, 71], 85: [2, 71] }, { 33: [2, 73], 75: [2, 73] }, { 23: [2, 29], 33: [2, 29], 54: [2, 29], 65: [2, 29], 68: [2, 29], 72: [2, 29], 75: [2, 29], 80: [2, 29], 81: [2, 29], 82: [2, 29], 83: [2, 29], 84: [2, 29], 85: [2, 29] }, { 14: [2, 15], 15: [2, 15], 19: [2, 15], 29: [2, 15], 34: [2, 15], 39: [2, 15], 44: [2, 15], 47: [2, 15], 48: [2, 15], 51: [2, 15], 55: [2, 15], 60: [2, 15] }, { 72: [1, 138], 77: [1, 137] }, { 72: [2, 100], 77: [2, 100] }, { 14: [2, 16], 15: [2, 16], 19: [2, 16], 29: [2, 16], 34: [2, 16], 44: [2, 16], 47: [2, 16], 48: [2, 16], 51: [2, 16], 55: [2, 16], 60: [2, 16] }, { 33: [1, 139] }, { 33: [2, 75] }, { 33: [2, 32] }, { 72: [2, 101], 77: [2, 101] }, { 14: [2, 17], 15: [2, 17], 19: [2, 17], 29: [2, 17], 34: [2, 17], 39: [2, 17], 44: [2, 17], 47: [2, 17], 48: [2, 17], 51: [2, 17], 55: [2, 17], 60: [2, 17] }],
	        defaultActions: { 4: [2, 1], 55: [2, 55], 57: [2, 20], 61: [2, 57], 74: [2, 81], 83: [2, 85], 87: [2, 18], 91: [2, 89], 102: [2, 53], 105: [2, 93], 111: [2, 19], 112: [2, 77], 117: [2, 97], 120: [2, 63], 123: [2, 69], 124: [2, 12], 136: [2, 75], 137: [2, 32] },
	        parseError: function parseError(str, hash) {
	            throw new Error(str);
	        },
	        parse: function parse(input) {
	            var self = this,
	                stack = [0],
	                vstack = [null],
	                lstack = [],
	                table = this.table,
	                yytext = "",
	                yylineno = 0,
	                yyleng = 0,
	                recovering = 0,
	                TERROR = 2,
	                EOF = 1;
	            this.lexer.setInput(input);
	            this.lexer.yy = this.yy;
	            this.yy.lexer = this.lexer;
	            this.yy.parser = this;
	            if (typeof this.lexer.yylloc == "undefined") this.lexer.yylloc = {};
	            var yyloc = this.lexer.yylloc;
	            lstack.push(yyloc);
	            var ranges = this.lexer.options && this.lexer.options.ranges;
	            if (typeof this.yy.parseError === "function") this.parseError = this.yy.parseError;
	            function popStack(n) {
	                stack.length = stack.length - 2 * n;
	                vstack.length = vstack.length - n;
	                lstack.length = lstack.length - n;
	            }
	            function lex() {
	                var token;
	                token = self.lexer.lex() || 1;
	                if (typeof token !== "number") {
	                    token = self.symbols_[token] || token;
	                }
	                return token;
	            }
	            var symbol,
	                preErrorSymbol,
	                state,
	                action,
	                a,
	                r,
	                yyval = {},
	                p,
	                len,
	                newState,
	                expected;
	            while (true) {
	                state = stack[stack.length - 1];
	                if (this.defaultActions[state]) {
	                    action = this.defaultActions[state];
	                } else {
	                    if (symbol === null || typeof symbol == "undefined") {
	                        symbol = lex();
	                    }
	                    action = table[state] && table[state][symbol];
	                }
	                if (typeof action === "undefined" || !action.length || !action[0]) {
	                    var errStr = "";
	                    if (!recovering) {
	                        expected = [];
	                        for (p in table[state]) if (this.terminals_[p] && p > 2) {
	                            expected.push("'" + this.terminals_[p] + "'");
	                        }
	                        if (this.lexer.showPosition) {
	                            errStr = "Parse error on line " + (yylineno + 1) + ":\n" + this.lexer.showPosition() + "\nExpecting " + expected.join(", ") + ", got '" + (this.terminals_[symbol] || symbol) + "'";
	                        } else {
	                            errStr = "Parse error on line " + (yylineno + 1) + ": Unexpected " + (symbol == 1 ? "end of input" : "'" + (this.terminals_[symbol] || symbol) + "'");
	                        }
	                        this.parseError(errStr, { text: this.lexer.match, token: this.terminals_[symbol] || symbol, line: this.lexer.yylineno, loc: yyloc, expected: expected });
	                    }
	                }
	                if (action[0] instanceof Array && action.length > 1) {
	                    throw new Error("Parse Error: multiple actions possible at state: " + state + ", token: " + symbol);
	                }
	                switch (action[0]) {
	                    case 1:
	                        stack.push(symbol);
	                        vstack.push(this.lexer.yytext);
	                        lstack.push(this.lexer.yylloc);
	                        stack.push(action[1]);
	                        symbol = null;
	                        if (!preErrorSymbol) {
	                            yyleng = this.lexer.yyleng;
	                            yytext = this.lexer.yytext;
	                            yylineno = this.lexer.yylineno;
	                            yyloc = this.lexer.yylloc;
	                            if (recovering > 0) recovering--;
	                        } else {
	                            symbol = preErrorSymbol;
	                            preErrorSymbol = null;
	                        }
	                        break;
	                    case 2:
	                        len = this.productions_[action[1]][1];
	                        yyval.$ = vstack[vstack.length - len];
	                        yyval._$ = { first_line: lstack[lstack.length - (len || 1)].first_line, last_line: lstack[lstack.length - 1].last_line, first_column: lstack[lstack.length - (len || 1)].first_column, last_column: lstack[lstack.length - 1].last_column };
	                        if (ranges) {
	                            yyval._$.range = [lstack[lstack.length - (len || 1)].range[0], lstack[lstack.length - 1].range[1]];
	                        }
	                        r = this.performAction.call(yyval, yytext, yyleng, yylineno, this.yy, action[1], vstack, lstack);
	                        if (typeof r !== "undefined") {
	                            return r;
	                        }
	                        if (len) {
	                            stack = stack.slice(0, -1 * len * 2);
	                            vstack = vstack.slice(0, -1 * len);
	                            lstack = lstack.slice(0, -1 * len);
	                        }
	                        stack.push(this.productions_[action[1]][0]);
	                        vstack.push(yyval.$);
	                        lstack.push(yyval._$);
	                        newState = table[stack[stack.length - 2]][stack[stack.length - 1]];
	                        stack.push(newState);
	                        break;
	                    case 3:
	                        return true;
	                }
	            }
	            return true;
	        }
	    };
	    /* Jison generated lexer */
	    var lexer = (function () {
	        var lexer = { EOF: 1,
	            parseError: function parseError(str, hash) {
	                if (this.yy.parser) {
	                    this.yy.parser.parseError(str, hash);
	                } else {
	                    throw new Error(str);
	                }
	            },
	            setInput: function setInput(input) {
	                this._input = input;
	                this._more = this._less = this.done = false;
	                this.yylineno = this.yyleng = 0;
	                this.yytext = this.matched = this.match = '';
	                this.conditionStack = ['INITIAL'];
	                this.yylloc = { first_line: 1, first_column: 0, last_line: 1, last_column: 0 };
	                if (this.options.ranges) this.yylloc.range = [0, 0];
	                this.offset = 0;
	                return this;
	            },
	            input: function input() {
	                var ch = this._input[0];
	                this.yytext += ch;
	                this.yyleng++;
	                this.offset++;
	                this.match += ch;
	                this.matched += ch;
	                var lines = ch.match(/(?:\r\n?|\n).*/g);
	                if (lines) {
	                    this.yylineno++;
	                    this.yylloc.last_line++;
	                } else {
	                    this.yylloc.last_column++;
	                }
	                if (this.options.ranges) this.yylloc.range[1]++;

	                this._input = this._input.slice(1);
	                return ch;
	            },
	            unput: function unput(ch) {
	                var len = ch.length;
	                var lines = ch.split(/(?:\r\n?|\n)/g);

	                this._input = ch + this._input;
	                this.yytext = this.yytext.substr(0, this.yytext.length - len - 1);
	                //this.yyleng -= len;
	                this.offset -= len;
	                var oldLines = this.match.split(/(?:\r\n?|\n)/g);
	                this.match = this.match.substr(0, this.match.length - 1);
	                this.matched = this.matched.substr(0, this.matched.length - 1);

	                if (lines.length - 1) this.yylineno -= lines.length - 1;
	                var r = this.yylloc.range;

	                this.yylloc = { first_line: this.yylloc.first_line,
	                    last_line: this.yylineno + 1,
	                    first_column: this.yylloc.first_column,
	                    last_column: lines ? (lines.length === oldLines.length ? this.yylloc.first_column : 0) + oldLines[oldLines.length - lines.length].length - lines[0].length : this.yylloc.first_column - len
	                };

	                if (this.options.ranges) {
	                    this.yylloc.range = [r[0], r[0] + this.yyleng - len];
	                }
	                return this;
	            },
	            more: function more() {
	                this._more = true;
	                return this;
	            },
	            less: function less(n) {
	                this.unput(this.match.slice(n));
	            },
	            pastInput: function pastInput() {
	                var past = this.matched.substr(0, this.matched.length - this.match.length);
	                return (past.length > 20 ? '...' : '') + past.substr(-20).replace(/\n/g, "");
	            },
	            upcomingInput: function upcomingInput() {
	                var next = this.match;
	                if (next.length < 20) {
	                    next += this._input.substr(0, 20 - next.length);
	                }
	                return (next.substr(0, 20) + (next.length > 20 ? '...' : '')).replace(/\n/g, "");
	            },
	            showPosition: function showPosition() {
	                var pre = this.pastInput();
	                var c = new Array(pre.length + 1).join("-");
	                return pre + this.upcomingInput() + "\n" + c + "^";
	            },
	            next: function next() {
	                if (this.done) {
	                    return this.EOF;
	                }
	                if (!this._input) this.done = true;

	                var token, match, tempMatch, index, col, lines;
	                if (!this._more) {
	                    this.yytext = '';
	                    this.match = '';
	                }
	                var rules = this._currentRules();
	                for (var i = 0; i < rules.length; i++) {
	                    tempMatch = this._input.match(this.rules[rules[i]]);
	                    if (tempMatch && (!match || tempMatch[0].length > match[0].length)) {
	                        match = tempMatch;
	                        index = i;
	                        if (!this.options.flex) break;
	                    }
	                }
	                if (match) {
	                    lines = match[0].match(/(?:\r\n?|\n).*/g);
	                    if (lines) this.yylineno += lines.length;
	                    this.yylloc = { first_line: this.yylloc.last_line,
	                        last_line: this.yylineno + 1,
	                        first_column: this.yylloc.last_column,
	                        last_column: lines ? lines[lines.length - 1].length - lines[lines.length - 1].match(/\r?\n?/)[0].length : this.yylloc.last_column + match[0].length };
	                    this.yytext += match[0];
	                    this.match += match[0];
	                    this.matches = match;
	                    this.yyleng = this.yytext.length;
	                    if (this.options.ranges) {
	                        this.yylloc.range = [this.offset, this.offset += this.yyleng];
	                    }
	                    this._more = false;
	                    this._input = this._input.slice(match[0].length);
	                    this.matched += match[0];
	                    token = this.performAction.call(this, this.yy, this, rules[index], this.conditionStack[this.conditionStack.length - 1]);
	                    if (this.done && this._input) this.done = false;
	                    if (token) return token;else return;
	                }
	                if (this._input === "") {
	                    return this.EOF;
	                } else {
	                    return this.parseError('Lexical error on line ' + (this.yylineno + 1) + '. Unrecognized text.\n' + this.showPosition(), { text: "", token: null, line: this.yylineno });
	                }
	            },
	            lex: function lex() {
	                var r = this.next();
	                if (typeof r !== 'undefined') {
	                    return r;
	                } else {
	                    return this.lex();
	                }
	            },
	            begin: function begin(condition) {
	                this.conditionStack.push(condition);
	            },
	            popState: function popState() {
	                return this.conditionStack.pop();
	            },
	            _currentRules: function _currentRules() {
	                return this.conditions[this.conditionStack[this.conditionStack.length - 1]].rules;
	            },
	            topState: function topState() {
	                return this.conditionStack[this.conditionStack.length - 2];
	            },
	            pushState: function begin(condition) {
	                this.begin(condition);
	            } };
	        lexer.options = {};
	        lexer.performAction = function anonymous(yy, yy_, $avoiding_name_collisions, YY_START
	        /**/) {

	            function strip(start, end) {
	                return yy_.yytext = yy_.yytext.substr(start, yy_.yyleng - end);
	            }

	            var YYSTATE = YY_START;
	            switch ($avoiding_name_collisions) {
	                case 0:
	                    if (yy_.yytext.slice(-2) === "\\\\") {
	                        strip(0, 1);
	                        this.begin("mu");
	                    } else if (yy_.yytext.slice(-1) === "\\") {
	                        strip(0, 1);
	                        this.begin("emu");
	                    } else {
	                        this.begin("mu");
	                    }
	                    if (yy_.yytext) return 15;

	                    break;
	                case 1:
	                    return 15;
	                    break;
	                case 2:
	                    this.popState();
	                    return 15;

	                    break;
	                case 3:
	                    this.begin('raw');return 15;
	                    break;
	                case 4:
	                    this.popState();
	                    // Should be using `this.topState()` below, but it currently
	                    // returns the second top instead of the first top. Opened an
	                    // issue about it at https://github.com/zaach/jison/issues/291
	                    if (this.conditionStack[this.conditionStack.length - 1] === 'raw') {
	                        return 15;
	                    } else {
	                        yy_.yytext = yy_.yytext.substr(5, yy_.yyleng - 9);
	                        return 'END_RAW_BLOCK';
	                    }

	                    break;
	                case 5:
	                    return 15;
	                    break;
	                case 6:
	                    this.popState();
	                    return 14;

	                    break;
	                case 7:
	                    return 65;
	                    break;
	                case 8:
	                    return 68;
	                    break;
	                case 9:
	                    return 19;
	                    break;
	                case 10:
	                    this.popState();
	                    this.begin('raw');
	                    return 23;

	                    break;
	                case 11:
	                    return 55;
	                    break;
	                case 12:
	                    return 60;
	                    break;
	                case 13:
	                    return 29;
	                    break;
	                case 14:
	                    return 47;
	                    break;
	                case 15:
	                    this.popState();return 44;
	                    break;
	                case 16:
	                    this.popState();return 44;
	                    break;
	                case 17:
	                    return 34;
	                    break;
	                case 18:
	                    return 39;
	                    break;
	                case 19:
	                    return 51;
	                    break;
	                case 20:
	                    return 48;
	                    break;
	                case 21:
	                    this.unput(yy_.yytext);
	                    this.popState();
	                    this.begin('com');

	                    break;
	                case 22:
	                    this.popState();
	                    return 14;

	                    break;
	                case 23:
	                    return 48;
	                    break;
	                case 24:
	                    return 73;
	                    break;
	                case 25:
	                    return 72;
	                    break;
	                case 26:
	                    return 72;
	                    break;
	                case 27:
	                    return 87;
	                    break;
	                case 28:
	                    // ignore whitespace
	                    break;
	                case 29:
	                    this.popState();return 54;
	                    break;
	                case 30:
	                    this.popState();return 33;
	                    break;
	                case 31:
	                    yy_.yytext = strip(1, 2).replace(/\\"/g, '"');return 80;
	                    break;
	                case 32:
	                    yy_.yytext = strip(1, 2).replace(/\\'/g, "'");return 80;
	                    break;
	                case 33:
	                    return 85;
	                    break;
	                case 34:
	                    return 82;
	                    break;
	                case 35:
	                    return 82;
	                    break;
	                case 36:
	                    return 83;
	                    break;
	                case 37:
	                    return 84;
	                    break;
	                case 38:
	                    return 81;
	                    break;
	                case 39:
	                    return 75;
	                    break;
	                case 40:
	                    return 77;
	                    break;
	                case 41:
	                    return 72;
	                    break;
	                case 42:
	                    yy_.yytext = yy_.yytext.replace(/\\([\\\]])/g, '$1');return 72;
	                    break;
	                case 43:
	                    return 'INVALID';
	                    break;
	                case 44:
	                    return 5;
	                    break;
	            }
	        };
	        lexer.rules = [/^(?:[^\x00]*?(?=(\{\{)))/, /^(?:[^\x00]+)/, /^(?:[^\x00]{2,}?(?=(\{\{|\\\{\{|\\\\\{\{|$)))/, /^(?:\{\{\{\{(?=[^\/]))/, /^(?:\{\{\{\{\/[^\s!"#%-,\.\/;->@\[-\^`\{-~]+(?=[=}\s\/.])\}\}\}\})/, /^(?:[^\x00]*?(?=(\{\{\{\{)))/, /^(?:[\s\S]*?--(~)?\}\})/, /^(?:\()/, /^(?:\))/, /^(?:\{\{\{\{)/, /^(?:\}\}\}\})/, /^(?:\{\{(~)?>)/, /^(?:\{\{(~)?#>)/, /^(?:\{\{(~)?#\*?)/, /^(?:\{\{(~)?\/)/, /^(?:\{\{(~)?\^\s*(~)?\}\})/, /^(?:\{\{(~)?\s*else\s*(~)?\}\})/, /^(?:\{\{(~)?\^)/, /^(?:\{\{(~)?\s*else\b)/, /^(?:\{\{(~)?\{)/, /^(?:\{\{(~)?&)/, /^(?:\{\{(~)?!--)/, /^(?:\{\{(~)?![\s\S]*?\}\})/, /^(?:\{\{(~)?\*?)/, /^(?:=)/, /^(?:\.\.)/, /^(?:\.(?=([=~}\s\/.)|])))/, /^(?:[\/.])/, /^(?:\s+)/, /^(?:\}(~)?\}\})/, /^(?:(~)?\}\})/, /^(?:"(\\["]|[^"])*")/, /^(?:'(\\[']|[^'])*')/, /^(?:@)/, /^(?:true(?=([~}\s)])))/, /^(?:false(?=([~}\s)])))/, /^(?:undefined(?=([~}\s)])))/, /^(?:null(?=([~}\s)])))/, /^(?:-?[0-9]+(?:\.[0-9]+)?(?=([~}\s)])))/, /^(?:as\s+\|)/, /^(?:\|)/, /^(?:([^\s!"#%-,\.\/;->@\[-\^`\{-~]+(?=([=~}\s\/.)|]))))/, /^(?:\[(\\\]|[^\]])*\])/, /^(?:.)/, /^(?:$)/];
	        lexer.conditions = { "mu": { "rules": [7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44], "inclusive": false }, "emu": { "rules": [2], "inclusive": false }, "com": { "rules": [6], "inclusive": false }, "raw": { "rules": [3, 4, 5], "inclusive": false }, "INITIAL": { "rules": [0, 1, 44], "inclusive": true } };
	        return lexer;
	    })();
	    parser.lexer = lexer;
	    function Parser() {
	        this.yy = {};
	    }Parser.prototype = parser;parser.Parser = Parser;
	    return new Parser();
	})();exports["default"] = handlebars;
	module.exports = exports["default"];

/***/ }),
/* 38 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';

	var _interopRequireDefault = __webpack_require__(1)['default'];

	exports.__esModule = true;

	var _visitor = __webpack_require__(39);

	var _visitor2 = _interopRequireDefault(_visitor);

	function WhitespaceControl() {
	  var options = arguments.length <= 0 || arguments[0] === undefined ? {} : arguments[0];

	  this.options = options;
	}
	WhitespaceControl.prototype = new _visitor2['default']();

	WhitespaceControl.prototype.Program = function (program) {
	  var doStandalone = !this.options.ignoreStandalone;

	  var isRoot = !this.isRootSeen;
	  this.isRootSeen = true;

	  var body = program.body;
	  for (var i = 0, l = body.length; i < l; i++) {
	    var current = body[i],
	        strip = this.accept(current);

	    if (!strip) {
	      continue;
	    }

	    var _isPrevWhitespace = isPrevWhitespace(body, i, isRoot),
	        _isNextWhitespace = isNextWhitespace(body, i, isRoot),
	        openStandalone = strip.openStandalone && _isPrevWhitespace,
	        closeStandalone = strip.closeStandalone && _isNextWhitespace,
	        inlineStandalone = strip.inlineStandalone && _isPrevWhitespace && _isNextWhitespace;

	    if (strip.close) {
	      omitRight(body, i, true);
	    }
	    if (strip.open) {
	      omitLeft(body, i, true);
	    }

	    if (doStandalone && inlineStandalone) {
	      omitRight(body, i);

	      if (omitLeft(body, i)) {
	        // If we are on a standalone node, save the indent info for partials
	        if (current.type === 'PartialStatement') {
	          // Pull out the whitespace from the final line
	          current.indent = /([ \t]+$)/.exec(body[i - 1].original)[1];
	        }
	      }
	    }
	    if (doStandalone && openStandalone) {
	      omitRight((current.program || current.inverse).body);

	      // Strip out the previous content node if it's whitespace only
	      omitLeft(body, i);
	    }
	    if (doStandalone && closeStandalone) {
	      // Always strip the next node
	      omitRight(body, i);

	      omitLeft((current.inverse || current.program).body);
	    }
	  }

	  return program;
	};

	WhitespaceControl.prototype.BlockStatement = WhitespaceControl.prototype.DecoratorBlock = WhitespaceControl.prototype.PartialBlockStatement = function (block) {
	  this.accept(block.program);
	  this.accept(block.inverse);

	  // Find the inverse program that is involed with whitespace stripping.
	  var program = block.program || block.inverse,
	      inverse = block.program && block.inverse,
	      firstInverse = inverse,
	      lastInverse = inverse;

	  if (inverse && inverse.chained) {
	    firstInverse = inverse.body[0].program;

	    // Walk the inverse chain to find the last inverse that is actually in the chain.
	    while (lastInverse.chained) {
	      lastInverse = lastInverse.body[lastInverse.body.length - 1].program;
	    }
	  }

	  var strip = {
	    open: block.openStrip.open,
	    close: block.closeStrip.close,

	    // Determine the standalone candiacy. Basically flag our content as being possibly standalone
	    // so our parent can determine if we actually are standalone
	    openStandalone: isNextWhitespace(program.body),
	    closeStandalone: isPrevWhitespace((firstInverse || program).body)
	  };

	  if (block.openStrip.close) {
	    omitRight(program.body, null, true);
	  }

	  if (inverse) {
	    var inverseStrip = block.inverseStrip;

	    if (inverseStrip.open) {
	      omitLeft(program.body, null, true);
	    }

	    if (inverseStrip.close) {
	      omitRight(firstInverse.body, null, true);
	    }
	    if (block.closeStrip.open) {
	      omitLeft(lastInverse.body, null, true);
	    }

	    // Find standalone else statments
	    if (!this.options.ignoreStandalone && isPrevWhitespace(program.body) && isNextWhitespace(firstInverse.body)) {
	      omitLeft(program.body);
	      omitRight(firstInverse.body);
	    }
	  } else if (block.closeStrip.open) {
	    omitLeft(program.body, null, true);
	  }

	  return strip;
	};

	WhitespaceControl.prototype.Decorator = WhitespaceControl.prototype.MustacheStatement = function (mustache) {
	  return mustache.strip;
	};

	WhitespaceControl.prototype.PartialStatement = WhitespaceControl.prototype.CommentStatement = function (node) {
	  /* istanbul ignore next */
	  var strip = node.strip || {};
	  return {
	    inlineStandalone: true,
	    open: strip.open,
	    close: strip.close
	  };
	};

	function isPrevWhitespace(body, i, isRoot) {
	  if (i === undefined) {
	    i = body.length;
	  }

	  // Nodes that end with newlines are considered whitespace (but are special
	  // cased for strip operations)
	  var prev = body[i - 1],
	      sibling = body[i - 2];
	  if (!prev) {
	    return isRoot;
	  }

	  if (prev.type === 'ContentStatement') {
	    return (sibling || !isRoot ? /\r?\n\s*?$/ : /(^|\r?\n)\s*?$/).test(prev.original);
	  }
	}
	function isNextWhitespace(body, i, isRoot) {
	  if (i === undefined) {
	    i = -1;
	  }

	  var next = body[i + 1],
	      sibling = body[i + 2];
	  if (!next) {
	    return isRoot;
	  }

	  if (next.type === 'ContentStatement') {
	    return (sibling || !isRoot ? /^\s*?\r?\n/ : /^\s*?(\r?\n|$)/).test(next.original);
	  }
	}

	// Marks the node to the right of the position as omitted.
	// I.e. {{foo}}' ' will mark the ' ' node as omitted.
	//
	// If i is undefined, then the first child will be marked as such.
	//
	// If mulitple is truthy then all whitespace will be stripped out until non-whitespace
	// content is met.
	function omitRight(body, i, multiple) {
	  var current = body[i == null ? 0 : i + 1];
	  if (!current || current.type !== 'ContentStatement' || !multiple && current.rightStripped) {
	    return;
	  }

	  var original = current.value;
	  current.value = current.value.replace(multiple ? /^\s+/ : /^[ \t]*\r?\n?/, '');
	  current.rightStripped = current.value !== original;
	}

	// Marks the node to the left of the position as omitted.
	// I.e. ' '{{foo}} will mark the ' ' node as omitted.
	//
	// If i is undefined then the last child will be marked as such.
	//
	// If mulitple is truthy then all whitespace will be stripped out until non-whitespace
	// content is met.
	function omitLeft(body, i, multiple) {
	  var current = body[i == null ? body.length - 1 : i - 1];
	  if (!current || current.type !== 'ContentStatement' || !multiple && current.leftStripped) {
	    return;
	  }

	  // We omit the last node if it's whitespace only and not preceeded by a non-content node.
	  var original = current.value;
	  current.value = current.value.replace(multiple ? /\s+$/ : /[ \t]+$/, '');
	  current.leftStripped = current.value !== original;
	  return current.leftStripped;
	}

	exports['default'] = WhitespaceControl;
	module.exports = exports['default'];

/***/ }),
/* 39 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';

	var _interopRequireDefault = __webpack_require__(1)['default'];

	exports.__esModule = true;

	var _exception = __webpack_require__(6);

	var _exception2 = _interopRequireDefault(_exception);

	function Visitor() {
	  this.parents = [];
	}

	Visitor.prototype = {
	  constructor: Visitor,
	  mutating: false,

	  // Visits a given value. If mutating, will replace the value if necessary.
	  acceptKey: function acceptKey(node, name) {
	    var value = this.accept(node[name]);
	    if (this.mutating) {
	      // Hacky sanity check: This may have a few false positives for type for the helper
	      // methods but will generally do the right thing without a lot of overhead.
	      if (value && !Visitor.prototype[value.type]) {
	        throw new _exception2['default']('Unexpected node type "' + value.type + '" found when accepting ' + name + ' on ' + node.type);
	      }
	      node[name] = value;
	    }
	  },

	  // Performs an accept operation with added sanity check to ensure
	  // required keys are not removed.
	  acceptRequired: function acceptRequired(node, name) {
	    this.acceptKey(node, name);

	    if (!node[name]) {
	      throw new _exception2['default'](node.type + ' requires ' + name);
	    }
	  },

	  // Traverses a given array. If mutating, empty respnses will be removed
	  // for child elements.
	  acceptArray: function acceptArray(array) {
	    for (var i = 0, l = array.length; i < l; i++) {
	      this.acceptKey(array, i);

	      if (!array[i]) {
	        array.splice(i, 1);
	        i--;
	        l--;
	      }
	    }
	  },

	  accept: function accept(object) {
	    if (!object) {
	      return;
	    }

	    /* istanbul ignore next: Sanity code */
	    if (!this[object.type]) {
	      throw new _exception2['default']('Unknown type: ' + object.type, object);
	    }

	    if (this.current) {
	      this.parents.unshift(this.current);
	    }
	    this.current = object;

	    var ret = this[object.type](object);

	    this.current = this.parents.shift();

	    if (!this.mutating || ret) {
	      return ret;
	    } else if (ret !== false) {
	      return object;
	    }
	  },

	  Program: function Program(program) {
	    this.acceptArray(program.body);
	  },

	  MustacheStatement: visitSubExpression,
	  Decorator: visitSubExpression,

	  BlockStatement: visitBlock,
	  DecoratorBlock: visitBlock,

	  PartialStatement: visitPartial,
	  PartialBlockStatement: function PartialBlockStatement(partial) {
	    visitPartial.call(this, partial);

	    this.acceptKey(partial, 'program');
	  },

	  ContentStatement: function ContentStatement() /* content */{},
	  CommentStatement: function CommentStatement() /* comment */{},

	  SubExpression: visitSubExpression,

	  PathExpression: function PathExpression() /* path */{},

	  StringLiteral: function StringLiteral() /* string */{},
	  NumberLiteral: function NumberLiteral() /* number */{},
	  BooleanLiteral: function BooleanLiteral() /* bool */{},
	  UndefinedLiteral: function UndefinedLiteral() /* literal */{},
	  NullLiteral: function NullLiteral() /* literal */{},

	  Hash: function Hash(hash) {
	    this.acceptArray(hash.pairs);
	  },
	  HashPair: function HashPair(pair) {
	    this.acceptRequired(pair, 'value');
	  }
	};

	function visitSubExpression(mustache) {
	  this.acceptRequired(mustache, 'path');
	  this.acceptArray(mustache.params);
	  this.acceptKey(mustache, 'hash');
	}
	function visitBlock(block) {
	  visitSubExpression.call(this, block);

	  this.acceptKey(block, 'program');
	  this.acceptKey(block, 'inverse');
	}
	function visitPartial(partial) {
	  this.acceptRequired(partial, 'name');
	  this.acceptArray(partial.params);
	  this.acceptKey(partial, 'hash');
	}

	exports['default'] = Visitor;
	module.exports = exports['default'];

/***/ }),
/* 40 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';

	var _interopRequireDefault = __webpack_require__(1)['default'];

	exports.__esModule = true;
	exports.SourceLocation = SourceLocation;
	exports.id = id;
	exports.stripFlags = stripFlags;
	exports.stripComment = stripComment;
	exports.preparePath = preparePath;
	exports.prepareMustache = prepareMustache;
	exports.prepareRawBlock = prepareRawBlock;
	exports.prepareBlock = prepareBlock;
	exports.prepareProgram = prepareProgram;
	exports.preparePartialBlock = preparePartialBlock;

	var _exception = __webpack_require__(6);

	var _exception2 = _interopRequireDefault(_exception);

	function validateClose(open, close) {
	  close = close.path ? close.path.original : close;

	  if (open.path.original !== close) {
	    var errorNode = { loc: open.path.loc };

	    throw new _exception2['default'](open.path.original + " doesn't match " + close, errorNode);
	  }
	}

	function SourceLocation(source, locInfo) {
	  this.source = source;
	  this.start = {
	    line: locInfo.first_line,
	    column: locInfo.first_column
	  };
	  this.end = {
	    line: locInfo.last_line,
	    column: locInfo.last_column
	  };
	}

	function id(token) {
	  if (/^\[.*\]$/.test(token)) {
	    return token.substr(1, token.length - 2);
	  } else {
	    return token;
	  }
	}

	function stripFlags(open, close) {
	  return {
	    open: open.charAt(2) === '~',
	    close: close.charAt(close.length - 3) === '~'
	  };
	}

	function stripComment(comment) {
	  return comment.replace(/^\{\{~?\!-?-?/, '').replace(/-?-?~?\}\}$/, '');
	}

	function preparePath(data, parts, loc) {
	  loc = this.locInfo(loc);

	  var original = data ? '@' : '',
	      dig = [],
	      depth = 0,
	      depthString = '';

	  for (var i = 0, l = parts.length; i < l; i++) {
	    var part = parts[i].part,

	    // If we have [] syntax then we do not treat path references as operators,
	    // i.e. foo.[this] resolves to approximately context.foo['this']
	    isLiteral = parts[i].original !== part;
	    original += (parts[i].separator || '') + part;

	    if (!isLiteral && (part === '..' || part === '.' || part === 'this')) {
	      if (dig.length > 0) {
	        throw new _exception2['default']('Invalid path: ' + original, { loc: loc });
	      } else if (part === '..') {
	        depth++;
	        depthString += '../';
	      }
	    } else {
	      dig.push(part);
	    }
	  }

	  return {
	    type: 'PathExpression',
	    data: data,
	    depth: depth,
	    parts: dig,
	    original: original,
	    loc: loc
	  };
	}

	function prepareMustache(path, params, hash, open, strip, locInfo) {
	  // Must use charAt to support IE pre-10
	  var escapeFlag = open.charAt(3) || open.charAt(2),
	      escaped = escapeFlag !== '{' && escapeFlag !== '&';

	  var decorator = /\*/.test(open);
	  return {
	    type: decorator ? 'Decorator' : 'MustacheStatement',
	    path: path,
	    params: params,
	    hash: hash,
	    escaped: escaped,
	    strip: strip,
	    loc: this.locInfo(locInfo)
	  };
	}

	function prepareRawBlock(openRawBlock, contents, close, locInfo) {
	  validateClose(openRawBlock, close);

	  locInfo = this.locInfo(locInfo);
	  var program = {
	    type: 'Program',
	    body: contents,
	    strip: {},
	    loc: locInfo
	  };

	  return {
	    type: 'BlockStatement',
	    path: openRawBlock.path,
	    params: openRawBlock.params,
	    hash: openRawBlock.hash,
	    program: program,
	    openStrip: {},
	    inverseStrip: {},
	    closeStrip: {},
	    loc: locInfo
	  };
	}

	function prepareBlock(openBlock, program, inverseAndProgram, close, inverted, locInfo) {
	  if (close && close.path) {
	    validateClose(openBlock, close);
	  }

	  var decorator = /\*/.test(openBlock.open);

	  program.blockParams = openBlock.blockParams;

	  var inverse = undefined,
	      inverseStrip = undefined;

	  if (inverseAndProgram) {
	    if (decorator) {
	      throw new _exception2['default']('Unexpected inverse block on decorator', inverseAndProgram);
	    }

	    if (inverseAndProgram.chain) {
	      inverseAndProgram.program.body[0].closeStrip = close.strip;
	    }

	    inverseStrip = inverseAndProgram.strip;
	    inverse = inverseAndProgram.program;
	  }

	  if (inverted) {
	    inverted = inverse;
	    inverse = program;
	    program = inverted;
	  }

	  return {
	    type: decorator ? 'DecoratorBlock' : 'BlockStatement',
	    path: openBlock.path,
	    params: openBlock.params,
	    hash: openBlock.hash,
	    program: program,
	    inverse: inverse,
	    openStrip: openBlock.strip,
	    inverseStrip: inverseStrip,
	    closeStrip: close && close.strip,
	    loc: this.locInfo(locInfo)
	  };
	}

	function prepareProgram(statements, loc) {
	  if (!loc && statements.length) {
	    var firstLoc = statements[0].loc,
	        lastLoc = statements[statements.length - 1].loc;

	    /* istanbul ignore else */
	    if (firstLoc && lastLoc) {
	      loc = {
	        source: firstLoc.source,
	        start: {
	          line: firstLoc.start.line,
	          column: firstLoc.start.column
	        },
	        end: {
	          line: lastLoc.end.line,
	          column: lastLoc.end.column
	        }
	      };
	    }
	  }

	  return {
	    type: 'Program',
	    body: statements,
	    strip: {},
	    loc: loc
	  };
	}

	function preparePartialBlock(open, program, close, locInfo) {
	  validateClose(open, close);

	  return {
	    type: 'PartialBlockStatement',
	    name: open.path,
	    params: open.params,
	    hash: open.hash,
	    program: program,
	    openStrip: open.strip,
	    closeStrip: close && close.strip,
	    loc: this.locInfo(locInfo)
	  };
	}

/***/ }),
/* 41 */
/***/ (function(module, exports, __webpack_require__) {

	/* eslint-disable new-cap */

	'use strict';

	var _interopRequireDefault = __webpack_require__(1)['default'];

	exports.__esModule = true;
	exports.Compiler = Compiler;
	exports.precompile = precompile;
	exports.compile = compile;

	var _exception = __webpack_require__(6);

	var _exception2 = _interopRequireDefault(_exception);

	var _utils = __webpack_require__(5);

	var _ast = __webpack_require__(35);

	var _ast2 = _interopRequireDefault(_ast);

	var slice = [].slice;

	function Compiler() {}

	// the foundHelper register will disambiguate helper lookup from finding a
	// function in a context. This is necessary for mustache compatibility, which
	// requires that context functions in blocks are evaluated by blockHelperMissing,
	// and then proceed as if the resulting value was provided to blockHelperMissing.

	Compiler.prototype = {
	  compiler: Compiler,

	  equals: function equals(other) {
	    var len = this.opcodes.length;
	    if (other.opcodes.length !== len) {
	      return false;
	    }

	    for (var i = 0; i < len; i++) {
	      var opcode = this.opcodes[i],
	          otherOpcode = other.opcodes[i];
	      if (opcode.opcode !== otherOpcode.opcode || !argEquals(opcode.args, otherOpcode.args)) {
	        return false;
	      }
	    }

	    // We know that length is the same between the two arrays because they are directly tied
	    // to the opcode behavior above.
	    len = this.children.length;
	    for (var i = 0; i < len; i++) {
	      if (!this.children[i].equals(other.children[i])) {
	        return false;
	      }
	    }

	    return true;
	  },

	  guid: 0,

	  compile: function compile(program, options) {
	    this.sourceNode = [];
	    this.opcodes = [];
	    this.children = [];
	    this.options = options;
	    this.stringParams = options.stringParams;
	    this.trackIds = options.trackIds;

	    options.blockParams = options.blockParams || [];

	    // These changes will propagate to the other compiler components
	    var knownHelpers = options.knownHelpers;
	    options.knownHelpers = {
	      'helperMissing': true,
	      'blockHelperMissing': true,
	      'each': true,
	      'if': true,
	      'unless': true,
	      'with': true,
	      'log': true,
	      'lookup': true
	    };
	    if (knownHelpers) {
	      for (var _name in knownHelpers) {
	        /* istanbul ignore else */
	        if (_name in knownHelpers) {
	          this.options.knownHelpers[_name] = knownHelpers[_name];
	        }
	      }
	    }

	    return this.accept(program);
	  },

	  compileProgram: function compileProgram(program) {
	    var childCompiler = new this.compiler(),
	        // eslint-disable-line new-cap
	    result = childCompiler.compile(program, this.options),
	        guid = this.guid++;

	    this.usePartial = this.usePartial || result.usePartial;

	    this.children[guid] = result;
	    this.useDepths = this.useDepths || result.useDepths;

	    return guid;
	  },

	  accept: function accept(node) {
	    /* istanbul ignore next: Sanity code */
	    if (!this[node.type]) {
	      throw new _exception2['default']('Unknown type: ' + node.type, node);
	    }

	    this.sourceNode.unshift(node);
	    var ret = this[node.type](node);
	    this.sourceNode.shift();
	    return ret;
	  },

	  Program: function Program(program) {
	    this.options.blockParams.unshift(program.blockParams);

	    var body = program.body,
	        bodyLength = body.length;
	    for (var i = 0; i < bodyLength; i++) {
	      this.accept(body[i]);
	    }

	    this.options.blockParams.shift();

	    this.isSimple = bodyLength === 1;
	    this.blockParams = program.blockParams ? program.blockParams.length : 0;

	    return this;
	  },

	  BlockStatement: function BlockStatement(block) {
	    transformLiteralToPath(block);

	    var program = block.program,
	        inverse = block.inverse;

	    program = program && this.compileProgram(program);
	    inverse = inverse && this.compileProgram(inverse);

	    var type = this.classifySexpr(block);

	    if (type === 'helper') {
	      this.helperSexpr(block, program, inverse);
	    } else if (type === 'simple') {
	      this.simpleSexpr(block);

	      // now that the simple mustache is resolved, we need to
	      // evaluate it by executing `blockHelperMissing`
	      this.opcode('pushProgram', program);
	      this.opcode('pushProgram', inverse);
	      this.opcode('emptyHash');
	      this.opcode('blockValue', block.path.original);
	    } else {
	      this.ambiguousSexpr(block, program, inverse);

	      // now that the simple mustache is resolved, we need to
	      // evaluate it by executing `blockHelperMissing`
	      this.opcode('pushProgram', program);
	      this.opcode('pushProgram', inverse);
	      this.opcode('emptyHash');
	      this.opcode('ambiguousBlockValue');
	    }

	    this.opcode('append');
	  },

	  DecoratorBlock: function DecoratorBlock(decorator) {
	    var program = decorator.program && this.compileProgram(decorator.program);
	    var params = this.setupFullMustacheParams(decorator, program, undefined),
	        path = decorator.path;

	    this.useDecorators = true;
	    this.opcode('registerDecorator', params.length, path.original);
	  },

	  PartialStatement: function PartialStatement(partial) {
	    this.usePartial = true;

	    var program = partial.program;
	    if (program) {
	      program = this.compileProgram(partial.program);
	    }

	    var params = partial.params;
	    if (params.length > 1) {
	      throw new _exception2['default']('Unsupported number of partial arguments: ' + params.length, partial);
	    } else if (!params.length) {
	      if (this.options.explicitPartialContext) {
	        this.opcode('pushLiteral', 'undefined');
	      } else {
	        params.push({ type: 'PathExpression', parts: [], depth: 0 });
	      }
	    }

	    var partialName = partial.name.original,
	        isDynamic = partial.name.type === 'SubExpression';
	    if (isDynamic) {
	      this.accept(partial.name);
	    }

	    this.setupFullMustacheParams(partial, program, undefined, true);

	    var indent = partial.indent || '';
	    if (this.options.preventIndent && indent) {
	      this.opcode('appendContent', indent);
	      indent = '';
	    }

	    this.opcode('invokePartial', isDynamic, partialName, indent);
	    this.opcode('append');
	  },
	  PartialBlockStatement: function PartialBlockStatement(partialBlock) {
	    this.PartialStatement(partialBlock);
	  },

	  MustacheStatement: function MustacheStatement(mustache) {
	    this.SubExpression(mustache);

	    if (mustache.escaped && !this.options.noEscape) {
	      this.opcode('appendEscaped');
	    } else {
	      this.opcode('append');
	    }
	  },
	  Decorator: function Decorator(decorator) {
	    this.DecoratorBlock(decorator);
	  },

	  ContentStatement: function ContentStatement(content) {
	    if (content.value) {
	      this.opcode('appendContent', content.value);
	    }
	  },

	  CommentStatement: function CommentStatement() {},

	  SubExpression: function SubExpression(sexpr) {
	    transformLiteralToPath(sexpr);
	    var type = this.classifySexpr(sexpr);

	    if (type === 'simple') {
	      this.simpleSexpr(sexpr);
	    } else if (type === 'helper') {
	      this.helperSexpr(sexpr);
	    } else {
	      this.ambiguousSexpr(sexpr);
	    }
	  },
	  ambiguousSexpr: function ambiguousSexpr(sexpr, program, inverse) {
	    var path = sexpr.path,
	        name = path.parts[0],
	        isBlock = program != null || inverse != null;

	    this.opcode('getContext', path.depth);

	    this.opcode('pushProgram', program);
	    this.opcode('pushProgram', inverse);

	    path.strict = true;
	    this.accept(path);

	    this.opcode('invokeAmbiguous', name, isBlock);
	  },

	  simpleSexpr: function simpleSexpr(sexpr) {
	    var path = sexpr.path;
	    path.strict = true;
	    this.accept(path);
	    this.opcode('resolvePossibleLambda');
	  },

	  helperSexpr: function helperSexpr(sexpr, program, inverse) {
	    var params = this.setupFullMustacheParams(sexpr, program, inverse),
	        path = sexpr.path,
	        name = path.parts[0];

	    if (this.options.knownHelpers[name]) {
	      this.opcode('invokeKnownHelper', params.length, name);
	    } else if (this.options.knownHelpersOnly) {
	      throw new _exception2['default']('You specified knownHelpersOnly, but used the unknown helper ' + name, sexpr);
	    } else {
	      path.strict = true;
	      path.falsy = true;

	      this.accept(path);
	      this.opcode('invokeHelper', params.length, path.original, _ast2['default'].helpers.simpleId(path));
	    }
	  },

	  PathExpression: function PathExpression(path) {
	    this.addDepth(path.depth);
	    this.opcode('getContext', path.depth);

	    var name = path.parts[0],
	        scoped = _ast2['default'].helpers.scopedId(path),
	        blockParamId = !path.depth && !scoped && this.blockParamIndex(name);

	    if (blockParamId) {
	      this.opcode('lookupBlockParam', blockParamId, path.parts);
	    } else if (!name) {
	      // Context reference, i.e. `{{foo .}}` or `{{foo ..}}`
	      this.opcode('pushContext');
	    } else if (path.data) {
	      this.options.data = true;
	      this.opcode('lookupData', path.depth, path.parts, path.strict);
	    } else {
	      this.opcode('lookupOnContext', path.parts, path.falsy, path.strict, scoped);
	    }
	  },

	  StringLiteral: function StringLiteral(string) {
	    this.opcode('pushString', string.value);
	  },

	  NumberLiteral: function NumberLiteral(number) {
	    this.opcode('pushLiteral', number.value);
	  },

	  BooleanLiteral: function BooleanLiteral(bool) {
	    this.opcode('pushLiteral', bool.value);
	  },

	  UndefinedLiteral: function UndefinedLiteral() {
	    this.opcode('pushLiteral', 'undefined');
	  },

	  NullLiteral: function NullLiteral() {
	    this.opcode('pushLiteral', 'null');
	  },

	  Hash: function Hash(hash) {
	    var pairs = hash.pairs,
	        i = 0,
	        l = pairs.length;

	    this.opcode('pushHash');

	    for (; i < l; i++) {
	      this.pushParam(pairs[i].value);
	    }
	    while (i--) {
	      this.opcode('assignToHash', pairs[i].key);
	    }
	    this.opcode('popHash');
	  },

	  // HELPERS
	  opcode: function opcode(name) {
	    this.opcodes.push({ opcode: name, args: slice.call(arguments, 1), loc: this.sourceNode[0].loc });
	  },

	  addDepth: function addDepth(depth) {
	    if (!depth) {
	      return;
	    }

	    this.useDepths = true;
	  },

	  classifySexpr: function classifySexpr(sexpr) {
	    var isSimple = _ast2['default'].helpers.simpleId(sexpr.path);

	    var isBlockParam = isSimple && !!this.blockParamIndex(sexpr.path.parts[0]);

	    // a mustache is an eligible helper if:
	    // * its id is simple (a single part, not `this` or `..`)
	    var isHelper = !isBlockParam && _ast2['default'].helpers.helperExpression(sexpr);

	    // if a mustache is an eligible helper but not a definite
	    // helper, it is ambiguous, and will be resolved in a later
	    // pass or at runtime.
	    var isEligible = !isBlockParam && (isHelper || isSimple);

	    // if ambiguous, we can possibly resolve the ambiguity now
	    // An eligible helper is one that does not have a complex path, i.e. `this.foo`, `../foo` etc.
	    if (isEligible && !isHelper) {
	      var _name2 = sexpr.path.parts[0],
	          options = this.options;

	      if (options.knownHelpers[_name2]) {
	        isHelper = true;
	      } else if (options.knownHelpersOnly) {
	        isEligible = false;
	      }
	    }

	    if (isHelper) {
	      return 'helper';
	    } else if (isEligible) {
	      return 'ambiguous';
	    } else {
	      return 'simple';
	    }
	  },

	  pushParams: function pushParams(params) {
	    for (var i = 0, l = params.length; i < l; i++) {
	      this.pushParam(params[i]);
	    }
	  },

	  pushParam: function pushParam(val) {
	    var value = val.value != null ? val.value : val.original || '';

	    if (this.stringParams) {
	      if (value.replace) {
	        value = value.replace(/^(\.?\.\/)*/g, '').replace(/\//g, '.');
	      }

	      if (val.depth) {
	        this.addDepth(val.depth);
	      }
	      this.opcode('getContext', val.depth || 0);
	      this.opcode('pushStringParam', value, val.type);

	      if (val.type === 'SubExpression') {
	        // SubExpressions get evaluated and passed in
	        // in string params mode.
	        this.accept(val);
	      }
	    } else {
	      if (this.trackIds) {
	        var blockParamIndex = undefined;
	        if (val.parts && !_ast2['default'].helpers.scopedId(val) && !val.depth) {
	          blockParamIndex = this.blockParamIndex(val.parts[0]);
	        }
	        if (blockParamIndex) {
	          var blockParamChild = val.parts.slice(1).join('.');
	          this.opcode('pushId', 'BlockParam', blockParamIndex, blockParamChild);
	        } else {
	          value = val.original || value;
	          if (value.replace) {
	            value = value.replace(/^this(?:\.|$)/, '').replace(/^\.\//, '').replace(/^\.$/, '');
	          }

	          this.opcode('pushId', val.type, value);
	        }
	      }
	      this.accept(val);
	    }
	  },

	  setupFullMustacheParams: function setupFullMustacheParams(sexpr, program, inverse, omitEmpty) {
	    var params = sexpr.params;
	    this.pushParams(params);

	    this.opcode('pushProgram', program);
	    this.opcode('pushProgram', inverse);

	    if (sexpr.hash) {
	      this.accept(sexpr.hash);
	    } else {
	      this.opcode('emptyHash', omitEmpty);
	    }

	    return params;
	  },

	  blockParamIndex: function blockParamIndex(name) {
	    for (var depth = 0, len = this.options.blockParams.length; depth < len; depth++) {
	      var blockParams = this.options.blockParams[depth],
	          param = blockParams && _utils.indexOf(blockParams, name);
	      if (blockParams && param >= 0) {
	        return [depth, param];
	      }
	    }
	  }
	};

	function precompile(input, options, env) {
	  if (input == null || typeof input !== 'string' && input.type !== 'Program') {
	    throw new _exception2['default']('You must pass a string or Handlebars AST to Handlebars.precompile. You passed ' + input);
	  }

	  options = options || {};
	  if (!('data' in options)) {
	    options.data = true;
	  }
	  if (options.compat) {
	    options.useDepths = true;
	  }

	  var ast = env.parse(input, options),
	      environment = new env.Compiler().compile(ast, options);
	  return new env.JavaScriptCompiler().compile(environment, options);
	}

	function compile(input, options, env) {
	  if (options === undefined) options = {};

	  if (input == null || typeof input !== 'string' && input.type !== 'Program') {
	    throw new _exception2['default']('You must pass a string or Handlebars AST to Handlebars.compile. You passed ' + input);
	  }

	  options = _utils.extend({}, options);
	  if (!('data' in options)) {
	    options.data = true;
	  }
	  if (options.compat) {
	    options.useDepths = true;
	  }

	  var compiled = undefined;

	  function compileInput() {
	    var ast = env.parse(input, options),
	        environment = new env.Compiler().compile(ast, options),
	        templateSpec = new env.JavaScriptCompiler().compile(environment, options, undefined, true);
	    return env.template(templateSpec);
	  }

	  // Template is only compiled on first use and cached after that point.
	  function ret(context, execOptions) {
	    if (!compiled) {
	      compiled = compileInput();
	    }
	    return compiled.call(this, context, execOptions);
	  }
	  ret._setup = function (setupOptions) {
	    if (!compiled) {
	      compiled = compileInput();
	    }
	    return compiled._setup(setupOptions);
	  };
	  ret._child = function (i, data, blockParams, depths) {
	    if (!compiled) {
	      compiled = compileInput();
	    }
	    return compiled._child(i, data, blockParams, depths);
	  };
	  return ret;
	}

	function argEquals(a, b) {
	  if (a === b) {
	    return true;
	  }

	  if (_utils.isArray(a) && _utils.isArray(b) && a.length === b.length) {
	    for (var i = 0; i < a.length; i++) {
	      if (!argEquals(a[i], b[i])) {
	        return false;
	      }
	    }
	    return true;
	  }
	}

	function transformLiteralToPath(sexpr) {
	  if (!sexpr.path.parts) {
	    var literal = sexpr.path;
	    // Casting to string here to make false and 0 literal values play nicely with the rest
	    // of the system.
	    sexpr.path = {
	      type: 'PathExpression',
	      data: false,
	      depth: 0,
	      parts: [literal.original + ''],
	      original: literal.original + '',
	      loc: literal.loc
	    };
	  }
	}

/***/ }),
/* 42 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';

	var _interopRequireDefault = __webpack_require__(1)['default'];

	exports.__esModule = true;

	var _base = __webpack_require__(4);

	var _exception = __webpack_require__(6);

	var _exception2 = _interopRequireDefault(_exception);

	var _utils = __webpack_require__(5);

	var _codeGen = __webpack_require__(43);

	var _codeGen2 = _interopRequireDefault(_codeGen);

	function Literal(value) {
	  this.value = value;
	}

	function JavaScriptCompiler() {}

	JavaScriptCompiler.prototype = {
	  // PUBLIC API: You can override these methods in a subclass to provide
	  // alternative compiled forms for name lookup and buffering semantics
	  nameLookup: function nameLookup(parent, name /* , type*/) {
	    if (JavaScriptCompiler.isValidJavaScriptVariableName(name)) {
	      return [parent, '.', name];
	    } else {
	      return [parent, '[', JSON.stringify(name), ']'];
	    }
	  },
	  depthedLookup: function depthedLookup(name) {
	    return [this.aliasable('container.lookup'), '(depths, "', name, '")'];
	  },

	  compilerInfo: function compilerInfo() {
	    var revision = _base.COMPILER_REVISION,
	        versions = _base.REVISION_CHANGES[revision];
	    return [revision, versions];
	  },

	  appendToBuffer: function appendToBuffer(source, location, explicit) {
	    // Force a source as this simplifies the merge logic.
	    if (!_utils.isArray(source)) {
	      source = [source];
	    }
	    source = this.source.wrap(source, location);

	    if (this.environment.isSimple) {
	      return ['return ', source, ';'];
	    } else if (explicit) {
	      // This is a case where the buffer operation occurs as a child of another
	      // construct, generally braces. We have to explicitly output these buffer
	      // operations to ensure that the emitted code goes in the correct location.
	      return ['buffer += ', source, ';'];
	    } else {
	      source.appendToBuffer = true;
	      return source;
	    }
	  },

	  initializeBuffer: function initializeBuffer() {
	    return this.quotedString('');
	  },
	  // END PUBLIC API

	  compile: function compile(environment, options, context, asObject) {
	    this.environment = environment;
	    this.options = options;
	    this.stringParams = this.options.stringParams;
	    this.trackIds = this.options.trackIds;
	    this.precompile = !asObject;

	    this.name = this.environment.name;
	    this.isChild = !!context;
	    this.context = context || {
	      decorators: [],
	      programs: [],
	      environments: []
	    };

	    this.preamble();

	    this.stackSlot = 0;
	    this.stackVars = [];
	    this.aliases = {};
	    this.registers = { list: [] };
	    this.hashes = [];
	    this.compileStack = [];
	    this.inlineStack = [];
	    this.blockParams = [];

	    this.compileChildren(environment, options);

	    this.useDepths = this.useDepths || environment.useDepths || environment.useDecorators || this.options.compat;
	    this.useBlockParams = this.useBlockParams || environment.useBlockParams;

	    var opcodes = environment.opcodes,
	        opcode = undefined,
	        firstLoc = undefined,
	        i = undefined,
	        l = undefined;

	    for (i = 0, l = opcodes.length; i < l; i++) {
	      opcode = opcodes[i];

	      this.source.currentLocation = opcode.loc;
	      firstLoc = firstLoc || opcode.loc;
	      this[opcode.opcode].apply(this, opcode.args);
	    }

	    // Flush any trailing content that might be pending.
	    this.source.currentLocation = firstLoc;
	    this.pushSource('');

	    /* istanbul ignore next */
	    if (this.stackSlot || this.inlineStack.length || this.compileStack.length) {
	      throw new _exception2['default']('Compile completed with content left on stack');
	    }

	    if (!this.decorators.isEmpty()) {
	      this.useDecorators = true;

	      this.decorators.prepend('var decorators = container.decorators;\n');
	      this.decorators.push('return fn;');

	      if (asObject) {
	        this.decorators = Function.apply(this, ['fn', 'props', 'container', 'depth0', 'data', 'blockParams', 'depths', this.decorators.merge()]);
	      } else {
	        this.decorators.prepend('function(fn, props, container, depth0, data, blockParams, depths) {\n');
	        this.decorators.push('}\n');
	        this.decorators = this.decorators.merge();
	      }
	    } else {
	      this.decorators = undefined;
	    }

	    var fn = this.createFunctionContext(asObject);
	    if (!this.isChild) {
	      var ret = {
	        compiler: this.compilerInfo(),
	        main: fn
	      };

	      if (this.decorators) {
	        ret.main_d = this.decorators; // eslint-disable-line camelcase
	        ret.useDecorators = true;
	      }

	      var _context = this.context;
	      var programs = _context.programs;
	      var decorators = _context.decorators;

	      for (i = 0, l = programs.length; i < l; i++) {
	        if (programs[i]) {
	          ret[i] = programs[i];
	          if (decorators[i]) {
	            ret[i + '_d'] = decorators[i];
	            ret.useDecorators = true;
	          }
	        }
	      }

	      if (this.environment.usePartial) {
	        ret.usePartial = true;
	      }
	      if (this.options.data) {
	        ret.useData = true;
	      }
	      if (this.useDepths) {
	        ret.useDepths = true;
	      }
	      if (this.useBlockParams) {
	        ret.useBlockParams = true;
	      }
	      if (this.options.compat) {
	        ret.compat = true;
	      }

	      if (!asObject) {
	        ret.compiler = JSON.stringify(ret.compiler);

	        this.source.currentLocation = { start: { line: 1, column: 0 } };
	        ret = this.objectLiteral(ret);

	        if (options.srcName) {
	          ret = ret.toStringWithSourceMap({ file: options.destName });
	          ret.map = ret.map && ret.map.toString();
	        } else {
	          ret = ret.toString();
	        }
	      } else {
	        ret.compilerOptions = this.options;
	      }

	      return ret;
	    } else {
	      return fn;
	    }
	  },

	  preamble: function preamble() {
	    // track the last context pushed into place to allow skipping the
	    // getContext opcode when it would be a noop
	    this.lastContext = 0;
	    this.source = new _codeGen2['default'](this.options.srcName);
	    this.decorators = new _codeGen2['default'](this.options.srcName);
	  },

	  createFunctionContext: function createFunctionContext(asObject) {
	    var varDeclarations = '';

	    var locals = this.stackVars.concat(this.registers.list);
	    if (locals.length > 0) {
	      varDeclarations += ', ' + locals.join(', ');
	    }

	    // Generate minimizer alias mappings
	    //
	    // When using true SourceNodes, this will update all references to the given alias
	    // as the source nodes are reused in situ. For the non-source node compilation mode,
	    // aliases will not be used, but this case is already being run on the client and
	    // we aren't concern about minimizing the template size.
	    var aliasCount = 0;
	    for (var alias in this.aliases) {
	      // eslint-disable-line guard-for-in
	      var node = this.aliases[alias];

	      if (this.aliases.hasOwnProperty(alias) && node.children && node.referenceCount > 1) {
	        varDeclarations += ', alias' + ++aliasCount + '=' + alias;
	        node.children[0] = 'alias' + aliasCount;
	      }
	    }

	    var params = ['container', 'depth0', 'helpers', 'partials', 'data'];

	    if (this.useBlockParams || this.useDepths) {
	      params.push('blockParams');
	    }
	    if (this.useDepths) {
	      params.push('depths');
	    }

	    // Perform a second pass over the output to merge content when possible
	    var source = this.mergeSource(varDeclarations);

	    if (asObject) {
	      params.push(source);

	      return Function.apply(this, params);
	    } else {
	      return this.source.wrap(['function(', params.join(','), ') {\n  ', source, '}']);
	    }
	  },
	  mergeSource: function mergeSource(varDeclarations) {
	    var isSimple = this.environment.isSimple,
	        appendOnly = !this.forceBuffer,
	        appendFirst = undefined,
	        sourceSeen = undefined,
	        bufferStart = undefined,
	        bufferEnd = undefined;
	    this.source.each(function (line) {
	      if (line.appendToBuffer) {
	        if (bufferStart) {
	          line.prepend('  + ');
	        } else {
	          bufferStart = line;
	        }
	        bufferEnd = line;
	      } else {
	        if (bufferStart) {
	          if (!sourceSeen) {
	            appendFirst = true;
	          } else {
	            bufferStart.prepend('buffer += ');
	          }
	          bufferEnd.add(';');
	          bufferStart = bufferEnd = undefined;
	        }

	        sourceSeen = true;
	        if (!isSimple) {
	          appendOnly = false;
	        }
	      }
	    });

	    if (appendOnly) {
	      if (bufferStart) {
	        bufferStart.prepend('return ');
	        bufferEnd.add(';');
	      } else if (!sourceSeen) {
	        this.source.push('return "";');
	      }
	    } else {
	      varDeclarations += ', buffer = ' + (appendFirst ? '' : this.initializeBuffer());

	      if (bufferStart) {
	        bufferStart.prepend('return buffer + ');
	        bufferEnd.add(';');
	      } else {
	        this.source.push('return buffer;');
	      }
	    }

	    if (varDeclarations) {
	      this.source.prepend('var ' + varDeclarations.substring(2) + (appendFirst ? '' : ';\n'));
	    }

	    return this.source.merge();
	  },

	  // [blockValue]
	  //
	  // On stack, before: hash, inverse, program, value
	  // On stack, after: return value of blockHelperMissing
	  //
	  // The purpose of this opcode is to take a block of the form
	  // `{{#this.foo}}...{{/this.foo}}`, resolve the value of `foo`, and
	  // replace it on the stack with the result of properly
	  // invoking blockHelperMissing.
	  blockValue: function blockValue(name) {
	    var blockHelperMissing = this.aliasable('helpers.blockHelperMissing'),
	        params = [this.contextName(0)];
	    this.setupHelperArgs(name, 0, params);

	    var blockName = this.popStack();
	    params.splice(1, 0, blockName);

	    this.push(this.source.functionCall(blockHelperMissing, 'call', params));
	  },

	  // [ambiguousBlockValue]
	  //
	  // On stack, before: hash, inverse, program, value
	  // Compiler value, before: lastHelper=value of last found helper, if any
	  // On stack, after, if no lastHelper: same as [blockValue]
	  // On stack, after, if lastHelper: value
	  ambiguousBlockValue: function ambiguousBlockValue() {
	    // We're being a bit cheeky and reusing the options value from the prior exec
	    var blockHelperMissing = this.aliasable('helpers.blockHelperMissing'),
	        params = [this.contextName(0)];
	    this.setupHelperArgs('', 0, params, true);

	    this.flushInline();

	    var current = this.topStack();
	    params.splice(1, 0, current);

	    this.pushSource(['if (!', this.lastHelper, ') { ', current, ' = ', this.source.functionCall(blockHelperMissing, 'call', params), '}']);
	  },

	  // [appendContent]
	  //
	  // On stack, before: ...
	  // On stack, after: ...
	  //
	  // Appends the string value of `content` to the current buffer
	  appendContent: function appendContent(content) {
	    if (this.pendingContent) {
	      content = this.pendingContent + content;
	    } else {
	      this.pendingLocation = this.source.currentLocation;
	    }

	    this.pendingContent = content;
	  },

	  // [append]
	  //
	  // On stack, before: value, ...
	  // On stack, after: ...
	  //
	  // Coerces `value` to a String and appends it to the current buffer.
	  //
	  // If `value` is truthy, or 0, it is coerced into a string and appended
	  // Otherwise, the empty string is appended
	  append: function append() {
	    if (this.isInline()) {
	      this.replaceStack(function (current) {
	        return [' != null ? ', current, ' : ""'];
	      });

	      this.pushSource(this.appendToBuffer(this.popStack()));
	    } else {
	      var local = this.popStack();
	      this.pushSource(['if (', local, ' != null) { ', this.appendToBuffer(local, undefined, true), ' }']);
	      if (this.environment.isSimple) {
	        this.pushSource(['else { ', this.appendToBuffer("''", undefined, true), ' }']);
	      }
	    }
	  },

	  // [appendEscaped]
	  //
	  // On stack, before: value, ...
	  // On stack, after: ...
	  //
	  // Escape `value` and append it to the buffer
	  appendEscaped: function appendEscaped() {
	    this.pushSource(this.appendToBuffer([this.aliasable('container.escapeExpression'), '(', this.popStack(), ')']));
	  },

	  // [getContext]
	  //
	  // On stack, before: ...
	  // On stack, after: ...
	  // Compiler value, after: lastContext=depth
	  //
	  // Set the value of the `lastContext` compiler value to the depth
	  getContext: function getContext(depth) {
	    this.lastContext = depth;
	  },

	  // [pushContext]
	  //
	  // On stack, before: ...
	  // On stack, after: currentContext, ...
	  //
	  // Pushes the value of the current context onto the stack.
	  pushContext: function pushContext() {
	    this.pushStackLiteral(this.contextName(this.lastContext));
	  },

	  // [lookupOnContext]
	  //
	  // On stack, before: ...
	  // On stack, after: currentContext[name], ...
	  //
	  // Looks up the value of `name` on the current context and pushes
	  // it onto the stack.
	  lookupOnContext: function lookupOnContext(parts, falsy, strict, scoped) {
	    var i = 0;

	    if (!scoped && this.options.compat && !this.lastContext) {
	      // The depthed query is expected to handle the undefined logic for the root level that
	      // is implemented below, so we evaluate that directly in compat mode
	      this.push(this.depthedLookup(parts[i++]));
	    } else {
	      this.pushContext();
	    }

	    this.resolvePath('context', parts, i, falsy, strict);
	  },

	  // [lookupBlockParam]
	  //
	  // On stack, before: ...
	  // On stack, after: blockParam[name], ...
	  //
	  // Looks up the value of `parts` on the given block param and pushes
	  // it onto the stack.
	  lookupBlockParam: function lookupBlockParam(blockParamId, parts) {
	    this.useBlockParams = true;

	    this.push(['blockParams[', blockParamId[0], '][', blockParamId[1], ']']);
	    this.resolvePath('context', parts, 1);
	  },

	  // [lookupData]
	  //
	  // On stack, before: ...
	  // On stack, after: data, ...
	  //
	  // Push the data lookup operator
	  lookupData: function lookupData(depth, parts, strict) {
	    if (!depth) {
	      this.pushStackLiteral('data');
	    } else {
	      this.pushStackLiteral('container.data(data, ' + depth + ')');
	    }

	    this.resolvePath('data', parts, 0, true, strict);
	  },

	  resolvePath: function resolvePath(type, parts, i, falsy, strict) {
	    // istanbul ignore next

	    var _this = this;

	    if (this.options.strict || this.options.assumeObjects) {
	      this.push(strictLookup(this.options.strict && strict, this, parts, type));
	      return;
	    }

	    var len = parts.length;
	    for (; i < len; i++) {
	      /* eslint-disable no-loop-func */
	      this.replaceStack(function (current) {
	        var lookup = _this.nameLookup(current, parts[i], type);
	        // We want to ensure that zero and false are handled properly if the context (falsy flag)
	        // needs to have the special handling for these values.
	        if (!falsy) {
	          return [' != null ? ', lookup, ' : ', current];
	        } else {
	          // Otherwise we can use generic falsy handling
	          return [' && ', lookup];
	        }
	      });
	      /* eslint-enable no-loop-func */
	    }
	  },

	  // [resolvePossibleLambda]
	  //
	  // On stack, before: value, ...
	  // On stack, after: resolved value, ...
	  //
	  // If the `value` is a lambda, replace it on the stack by
	  // the return value of the lambda
	  resolvePossibleLambda: function resolvePossibleLambda() {
	    this.push([this.aliasable('container.lambda'), '(', this.popStack(), ', ', this.contextName(0), ')']);
	  },

	  // [pushStringParam]
	  //
	  // On stack, before: ...
	  // On stack, after: string, currentContext, ...
	  //
	  // This opcode is designed for use in string mode, which
	  // provides the string value of a parameter along with its
	  // depth rather than resolving it immediately.
	  pushStringParam: function pushStringParam(string, type) {
	    this.pushContext();
	    this.pushString(type);

	    // If it's a subexpression, the string result
	    // will be pushed after this opcode.
	    if (type !== 'SubExpression') {
	      if (typeof string === 'string') {
	        this.pushString(string);
	      } else {
	        this.pushStackLiteral(string);
	      }
	    }
	  },

	  emptyHash: function emptyHash(omitEmpty) {
	    if (this.trackIds) {
	      this.push('{}'); // hashIds
	    }
	    if (this.stringParams) {
	      this.push('{}'); // hashContexts
	      this.push('{}'); // hashTypes
	    }
	    this.pushStackLiteral(omitEmpty ? 'undefined' : '{}');
	  },
	  pushHash: function pushHash() {
	    if (this.hash) {
	      this.hashes.push(this.hash);
	    }
	    this.hash = { values: [], types: [], contexts: [], ids: [] };
	  },
	  popHash: function popHash() {
	    var hash = this.hash;
	    this.hash = this.hashes.pop();

	    if (this.trackIds) {
	      this.push(this.objectLiteral(hash.ids));
	    }
	    if (this.stringParams) {
	      this.push(this.objectLiteral(hash.contexts));
	      this.push(this.objectLiteral(hash.types));
	    }

	    this.push(this.objectLiteral(hash.values));
	  },

	  // [pushString]
	  //
	  // On stack, before: ...
	  // On stack, after: quotedString(string), ...
	  //
	  // Push a quoted version of `string` onto the stack
	  pushString: function pushString(string) {
	    this.pushStackLiteral(this.quotedString(string));
	  },

	  // [pushLiteral]
	  //
	  // On stack, before: ...
	  // On stack, after: value, ...
	  //
	  // Pushes a value onto the stack. This operation prevents
	  // the compiler from creating a temporary variable to hold
	  // it.
	  pushLiteral: function pushLiteral(value) {
	    this.pushStackLiteral(value);
	  },

	  // [pushProgram]
	  //
	  // On stack, before: ...
	  // On stack, after: program(guid), ...
	  //
	  // Push a program expression onto the stack. This takes
	  // a compile-time guid and converts it into a runtime-accessible
	  // expression.
	  pushProgram: function pushProgram(guid) {
	    if (guid != null) {
	      this.pushStackLiteral(this.programExpression(guid));
	    } else {
	      this.pushStackLiteral(null);
	    }
	  },

	  // [registerDecorator]
	  //
	  // On stack, before: hash, program, params..., ...
	  // On stack, after: ...
	  //
	  // Pops off the decorator's parameters, invokes the decorator,
	  // and inserts the decorator into the decorators list.
	  registerDecorator: function registerDecorator(paramSize, name) {
	    var foundDecorator = this.nameLookup('decorators', name, 'decorator'),
	        options = this.setupHelperArgs(name, paramSize);

	    this.decorators.push(['fn = ', this.decorators.functionCall(foundDecorator, '', ['fn', 'props', 'container', options]), ' || fn;']);
	  },

	  // [invokeHelper]
	  //
	  // On stack, before: hash, inverse, program, params..., ...
	  // On stack, after: result of helper invocation
	  //
	  // Pops off the helper's parameters, invokes the helper,
	  // and pushes the helper's return value onto the stack.
	  //
	  // If the helper is not found, `helperMissing` is called.
	  invokeHelper: function invokeHelper(paramSize, name, isSimple) {
	    var nonHelper = this.popStack(),
	        helper = this.setupHelper(paramSize, name),
	        simple = isSimple ? [helper.name, ' || '] : '';

	    var lookup = ['('].concat(simple, nonHelper);
	    if (!this.options.strict) {
	      lookup.push(' || ', this.aliasable('helpers.helperMissing'));
	    }
	    lookup.push(')');

	    this.push(this.source.functionCall(lookup, 'call', helper.callParams));
	  },

	  // [invokeKnownHelper]
	  //
	  // On stack, before: hash, inverse, program, params..., ...
	  // On stack, after: result of helper invocation
	  //
	  // This operation is used when the helper is known to exist,
	  // so a `helperMissing` fallback is not required.
	  invokeKnownHelper: function invokeKnownHelper(paramSize, name) {
	    var helper = this.setupHelper(paramSize, name);
	    this.push(this.source.functionCall(helper.name, 'call', helper.callParams));
	  },

	  // [invokeAmbiguous]
	  //
	  // On stack, before: hash, inverse, program, params..., ...
	  // On stack, after: result of disambiguation
	  //
	  // This operation is used when an expression like `{{foo}}`
	  // is provided, but we don't know at compile-time whether it
	  // is a helper or a path.
	  //
	  // This operation emits more code than the other options,
	  // and can be avoided by passing the `knownHelpers` and
	  // `knownHelpersOnly` flags at compile-time.
	  invokeAmbiguous: function invokeAmbiguous(name, helperCall) {
	    this.useRegister('helper');

	    var nonHelper = this.popStack();

	    this.emptyHash();
	    var helper = this.setupHelper(0, name, helperCall);

	    var helperName = this.lastHelper = this.nameLookup('helpers', name, 'helper');

	    var lookup = ['(', '(helper = ', helperName, ' || ', nonHelper, ')'];
	    if (!this.options.strict) {
	      lookup[0] = '(helper = ';
	      lookup.push(' != null ? helper : ', this.aliasable('helpers.helperMissing'));
	    }

	    this.push(['(', lookup, helper.paramsInit ? ['),(', helper.paramsInit] : [], '),', '(typeof helper === ', this.aliasable('"function"'), ' ? ', this.source.functionCall('helper', 'call', helper.callParams), ' : helper))']);
	  },

	  // [invokePartial]
	  //
	  // On stack, before: context, ...
	  // On stack after: result of partial invocation
	  //
	  // This operation pops off a context, invokes a partial with that context,
	  // and pushes the result of the invocation back.
	  invokePartial: function invokePartial(isDynamic, name, indent) {
	    var params = [],
	        options = this.setupParams(name, 1, params);

	    if (isDynamic) {
	      name = this.popStack();
	      delete options.name;
	    }

	    if (indent) {
	      options.indent = JSON.stringify(indent);
	    }
	    options.helpers = 'helpers';
	    options.partials = 'partials';
	    options.decorators = 'container.decorators';

	    if (!isDynamic) {
	      params.unshift(this.nameLookup('partials', name, 'partial'));
	    } else {
	      params.unshift(name);
	    }

	    if (this.options.compat) {
	      options.depths = 'depths';
	    }
	    options = this.objectLiteral(options);
	    params.push(options);

	    this.push(this.source.functionCall('container.invokePartial', '', params));
	  },

	  // [assignToHash]
	  //
	  // On stack, before: value, ..., hash, ...
	  // On stack, after: ..., hash, ...
	  //
	  // Pops a value off the stack and assigns it to the current hash
	  assignToHash: function assignToHash(key) {
	    var value = this.popStack(),
	        context = undefined,
	        type = undefined,
	        id = undefined;

	    if (this.trackIds) {
	      id = this.popStack();
	    }
	    if (this.stringParams) {
	      type = this.popStack();
	      context = this.popStack();
	    }

	    var hash = this.hash;
	    if (context) {
	      hash.contexts[key] = context;
	    }
	    if (type) {
	      hash.types[key] = type;
	    }
	    if (id) {
	      hash.ids[key] = id;
	    }
	    hash.values[key] = value;
	  },

	  pushId: function pushId(type, name, child) {
	    if (type === 'BlockParam') {
	      this.pushStackLiteral('blockParams[' + name[0] + '].path[' + name[1] + ']' + (child ? ' + ' + JSON.stringify('.' + child) : ''));
	    } else if (type === 'PathExpression') {
	      this.pushString(name);
	    } else if (type === 'SubExpression') {
	      this.pushStackLiteral('true');
	    } else {
	      this.pushStackLiteral('null');
	    }
	  },

	  // HELPERS

	  compiler: JavaScriptCompiler,

	  compileChildren: function compileChildren(environment, options) {
	    var children = environment.children,
	        child = undefined,
	        compiler = undefined;

	    for (var i = 0, l = children.length; i < l; i++) {
	      child = children[i];
	      compiler = new this.compiler(); // eslint-disable-line new-cap

	      var existing = this.matchExistingProgram(child);

	      if (existing == null) {
	        this.context.programs.push(''); // Placeholder to prevent name conflicts for nested children
	        var index = this.context.programs.length;
	        child.index = index;
	        child.name = 'program' + index;
	        this.context.programs[index] = compiler.compile(child, options, this.context, !this.precompile);
	        this.context.decorators[index] = compiler.decorators;
	        this.context.environments[index] = child;

	        this.useDepths = this.useDepths || compiler.useDepths;
	        this.useBlockParams = this.useBlockParams || compiler.useBlockParams;
	        child.useDepths = this.useDepths;
	        child.useBlockParams = this.useBlockParams;
	      } else {
	        child.index = existing.index;
	        child.name = 'program' + existing.index;

	        this.useDepths = this.useDepths || existing.useDepths;
	        this.useBlockParams = this.useBlockParams || existing.useBlockParams;
	      }
	    }
	  },
	  matchExistingProgram: function matchExistingProgram(child) {
	    for (var i = 0, len = this.context.environments.length; i < len; i++) {
	      var environment = this.context.environments[i];
	      if (environment && environment.equals(child)) {
	        return environment;
	      }
	    }
	  },

	  programExpression: function programExpression(guid) {
	    var child = this.environment.children[guid],
	        programParams = [child.index, 'data', child.blockParams];

	    if (this.useBlockParams || this.useDepths) {
	      programParams.push('blockParams');
	    }
	    if (this.useDepths) {
	      programParams.push('depths');
	    }

	    return 'container.program(' + programParams.join(', ') + ')';
	  },

	  useRegister: function useRegister(name) {
	    if (!this.registers[name]) {
	      this.registers[name] = true;
	      this.registers.list.push(name);
	    }
	  },

	  push: function push(expr) {
	    if (!(expr instanceof Literal)) {
	      expr = this.source.wrap(expr);
	    }

	    this.inlineStack.push(expr);
	    return expr;
	  },

	  pushStackLiteral: function pushStackLiteral(item) {
	    this.push(new Literal(item));
	  },

	  pushSource: function pushSource(source) {
	    if (this.pendingContent) {
	      this.source.push(this.appendToBuffer(this.source.quotedString(this.pendingContent), this.pendingLocation));
	      this.pendingContent = undefined;
	    }

	    if (source) {
	      this.source.push(source);
	    }
	  },

	  replaceStack: function replaceStack(callback) {
	    var prefix = ['('],
	        stack = undefined,
	        createdStack = undefined,
	        usedLiteral = undefined;

	    /* istanbul ignore next */
	    if (!this.isInline()) {
	      throw new _exception2['default']('replaceStack on non-inline');
	    }

	    // We want to merge the inline statement into the replacement statement via ','
	    var top = this.popStack(true);

	    if (top instanceof Literal) {
	      // Literals do not need to be inlined
	      stack = [top.value];
	      prefix = ['(', stack];
	      usedLiteral = true;
	    } else {
	      // Get or create the current stack name for use by the inline
	      createdStack = true;
	      var _name = this.incrStack();

	      prefix = ['((', this.push(_name), ' = ', top, ')'];
	      stack = this.topStack();
	    }

	    var item = callback.call(this, stack);

	    if (!usedLiteral) {
	      this.popStack();
	    }
	    if (createdStack) {
	      this.stackSlot--;
	    }
	    this.push(prefix.concat(item, ')'));
	  },

	  incrStack: function incrStack() {
	    this.stackSlot++;
	    if (this.stackSlot > this.stackVars.length) {
	      this.stackVars.push('stack' + this.stackSlot);
	    }
	    return this.topStackName();
	  },
	  topStackName: function topStackName() {
	    return 'stack' + this.stackSlot;
	  },
	  flushInline: function flushInline() {
	    var inlineStack = this.inlineStack;
	    this.inlineStack = [];
	    for (var i = 0, len = inlineStack.length; i < len; i++) {
	      var entry = inlineStack[i];
	      /* istanbul ignore if */
	      if (entry instanceof Literal) {
	        this.compileStack.push(entry);
	      } else {
	        var stack = this.incrStack();
	        this.pushSource([stack, ' = ', entry, ';']);
	        this.compileStack.push(stack);
	      }
	    }
	  },
	  isInline: function isInline() {
	    return this.inlineStack.length;
	  },

	  popStack: function popStack(wrapped) {
	    var inline = this.isInline(),
	        item = (inline ? this.inlineStack : this.compileStack).pop();

	    if (!wrapped && item instanceof Literal) {
	      return item.value;
	    } else {
	      if (!inline) {
	        /* istanbul ignore next */
	        if (!this.stackSlot) {
	          throw new _exception2['default']('Invalid stack pop');
	        }
	        this.stackSlot--;
	      }
	      return item;
	    }
	  },

	  topStack: function topStack() {
	    var stack = this.isInline() ? this.inlineStack : this.compileStack,
	        item = stack[stack.length - 1];

	    /* istanbul ignore if */
	    if (item instanceof Literal) {
	      return item.value;
	    } else {
	      return item;
	    }
	  },

	  contextName: function contextName(context) {
	    if (this.useDepths && context) {
	      return 'depths[' + context + ']';
	    } else {
	      return 'depth' + context;
	    }
	  },

	  quotedString: function quotedString(str) {
	    return this.source.quotedString(str);
	  },

	  objectLiteral: function objectLiteral(obj) {
	    return this.source.objectLiteral(obj);
	  },

	  aliasable: function aliasable(name) {
	    var ret = this.aliases[name];
	    if (ret) {
	      ret.referenceCount++;
	      return ret;
	    }

	    ret = this.aliases[name] = this.source.wrap(name);
	    ret.aliasable = true;
	    ret.referenceCount = 1;

	    return ret;
	  },

	  setupHelper: function setupHelper(paramSize, name, blockHelper) {
	    var params = [],
	        paramsInit = this.setupHelperArgs(name, paramSize, params, blockHelper);
	    var foundHelper = this.nameLookup('helpers', name, 'helper'),
	        callContext = this.aliasable(this.contextName(0) + ' != null ? ' + this.contextName(0) + ' : (container.nullContext || {})');

	    return {
	      params: params,
	      paramsInit: paramsInit,
	      name: foundHelper,
	      callParams: [callContext].concat(params)
	    };
	  },

	  setupParams: function setupParams(helper, paramSize, params) {
	    var options = {},
	        contexts = [],
	        types = [],
	        ids = [],
	        objectArgs = !params,
	        param = undefined;

	    if (objectArgs) {
	      params = [];
	    }

	    options.name = this.quotedString(helper);
	    options.hash = this.popStack();

	    if (this.trackIds) {
	      options.hashIds = this.popStack();
	    }
	    if (this.stringParams) {
	      options.hashTypes = this.popStack();
	      options.hashContexts = this.popStack();
	    }

	    var inverse = this.popStack(),
	        program = this.popStack();

	    // Avoid setting fn and inverse if neither are set. This allows
	    // helpers to do a check for `if (options.fn)`
	    if (program || inverse) {
	      options.fn = program || 'container.noop';
	      options.inverse = inverse || 'container.noop';
	    }

	    // The parameters go on to the stack in order (making sure that they are evaluated in order)
	    // so we need to pop them off the stack in reverse order
	    var i = paramSize;
	    while (i--) {
	      param = this.popStack();
	      params[i] = param;

	      if (this.trackIds) {
	        ids[i] = this.popStack();
	      }
	      if (this.stringParams) {
	        types[i] = this.popStack();
	        contexts[i] = this.popStack();
	      }
	    }

	    if (objectArgs) {
	      options.args = this.source.generateArray(params);
	    }

	    if (this.trackIds) {
	      options.ids = this.source.generateArray(ids);
	    }
	    if (this.stringParams) {
	      options.types = this.source.generateArray(types);
	      options.contexts = this.source.generateArray(contexts);
	    }

	    if (this.options.data) {
	      options.data = 'data';
	    }
	    if (this.useBlockParams) {
	      options.blockParams = 'blockParams';
	    }
	    return options;
	  },

	  setupHelperArgs: function setupHelperArgs(helper, paramSize, params, useRegister) {
	    var options = this.setupParams(helper, paramSize, params);
	    options = this.objectLiteral(options);
	    if (useRegister) {
	      this.useRegister('options');
	      params.push('options');
	      return ['options=', options];
	    } else if (params) {
	      params.push(options);
	      return '';
	    } else {
	      return options;
	    }
	  }
	};

	(function () {
	  var reservedWords = ('break else new var' + ' case finally return void' + ' catch for switch while' + ' continue function this with' + ' default if throw' + ' delete in try' + ' do instanceof typeof' + ' abstract enum int short' + ' boolean export interface static' + ' byte extends long super' + ' char final native synchronized' + ' class float package throws' + ' const goto private transient' + ' debugger implements protected volatile' + ' double import public let yield await' + ' null true false').split(' ');

	  var compilerWords = JavaScriptCompiler.RESERVED_WORDS = {};

	  for (var i = 0, l = reservedWords.length; i < l; i++) {
	    compilerWords[reservedWords[i]] = true;
	  }
	})();

	JavaScriptCompiler.isValidJavaScriptVariableName = function (name) {
	  return !JavaScriptCompiler.RESERVED_WORDS[name] && /^[a-zA-Z_$][0-9a-zA-Z_$]*$/.test(name);
	};

	function strictLookup(requireTerminal, compiler, parts, type) {
	  var stack = compiler.popStack(),
	      i = 0,
	      len = parts.length;
	  if (requireTerminal) {
	    len--;
	  }

	  for (; i < len; i++) {
	    stack = compiler.nameLookup(stack, parts[i], type);
	  }

	  if (requireTerminal) {
	    return [compiler.aliasable('container.strict'), '(', stack, ', ', compiler.quotedString(parts[i]), ')'];
	  } else {
	    return stack;
	  }
	}

	exports['default'] = JavaScriptCompiler;
	module.exports = exports['default'];

/***/ }),
/* 43 */
/***/ (function(module, exports, __webpack_require__) {

	/* global define */
	'use strict';

	exports.__esModule = true;

	var _utils = __webpack_require__(5);

	var SourceNode = undefined;

	try {
	  /* istanbul ignore next */
	  if (false) {
	    // We don't support this in AMD environments. For these environments, we asusme that
	    // they are running on the browser and thus have no need for the source-map library.
	    var SourceMap = require('source-map');
	    SourceNode = SourceMap.SourceNode;
	  }
	} catch (err) {}
	/* NOP */

	/* istanbul ignore if: tested but not covered in istanbul due to dist build  */
	if (!SourceNode) {
	  SourceNode = function (line, column, srcFile, chunks) {
	    this.src = '';
	    if (chunks) {
	      this.add(chunks);
	    }
	  };
	  /* istanbul ignore next */
	  SourceNode.prototype = {
	    add: function add(chunks) {
	      if (_utils.isArray(chunks)) {
	        chunks = chunks.join('');
	      }
	      this.src += chunks;
	    },
	    prepend: function prepend(chunks) {
	      if (_utils.isArray(chunks)) {
	        chunks = chunks.join('');
	      }
	      this.src = chunks + this.src;
	    },
	    toStringWithSourceMap: function toStringWithSourceMap() {
	      return { code: this.toString() };
	    },
	    toString: function toString() {
	      return this.src;
	    }
	  };
	}

	function castChunk(chunk, codeGen, loc) {
	  if (_utils.isArray(chunk)) {
	    var ret = [];

	    for (var i = 0, len = chunk.length; i < len; i++) {
	      ret.push(codeGen.wrap(chunk[i], loc));
	    }
	    return ret;
	  } else if (typeof chunk === 'boolean' || typeof chunk === 'number') {
	    // Handle primitives that the SourceNode will throw up on
	    return chunk + '';
	  }
	  return chunk;
	}

	function CodeGen(srcFile) {
	  this.srcFile = srcFile;
	  this.source = [];
	}

	CodeGen.prototype = {
	  isEmpty: function isEmpty() {
	    return !this.source.length;
	  },
	  prepend: function prepend(source, loc) {
	    this.source.unshift(this.wrap(source, loc));
	  },
	  push: function push(source, loc) {
	    this.source.push(this.wrap(source, loc));
	  },

	  merge: function merge() {
	    var source = this.empty();
	    this.each(function (line) {
	      source.add(['  ', line, '\n']);
	    });
	    return source;
	  },

	  each: function each(iter) {
	    for (var i = 0, len = this.source.length; i < len; i++) {
	      iter(this.source[i]);
	    }
	  },

	  empty: function empty() {
	    var loc = this.currentLocation || { start: {} };
	    return new SourceNode(loc.start.line, loc.start.column, this.srcFile);
	  },
	  wrap: function wrap(chunk) {
	    var loc = arguments.length <= 1 || arguments[1] === undefined ? this.currentLocation || { start: {} } : arguments[1];

	    if (chunk instanceof SourceNode) {
	      return chunk;
	    }

	    chunk = castChunk(chunk, this, loc);

	    return new SourceNode(loc.start.line, loc.start.column, this.srcFile, chunk);
	  },

	  functionCall: function functionCall(fn, type, params) {
	    params = this.generateList(params);
	    return this.wrap([fn, type ? '.' + type + '(' : '(', params, ')']);
	  },

	  quotedString: function quotedString(str) {
	    return '"' + (str + '').replace(/\\/g, '\\\\').replace(/"/g, '\\"').replace(/\n/g, '\\n').replace(/\r/g, '\\r').replace(/\u2028/g, '\\u2028') // Per Ecma-262 7.3 + 7.8.4
	    .replace(/\u2029/g, '\\u2029') + '"';
	  },

	  objectLiteral: function objectLiteral(obj) {
	    var pairs = [];

	    for (var key in obj) {
	      if (obj.hasOwnProperty(key)) {
	        var value = castChunk(obj[key], this);
	        if (value !== 'undefined') {
	          pairs.push([this.quotedString(key), ':', value]);
	        }
	      }
	    }

	    var ret = this.generateList(pairs);
	    ret.prepend('{');
	    ret.add('}');
	    return ret;
	  },

	  generateList: function generateList(entries) {
	    var ret = this.empty();

	    for (var i = 0, len = entries.length; i < len; i++) {
	      if (i) {
	        ret.add(',');
	      }

	      ret.add(castChunk(entries[i], this));
	    }

	    return ret;
	  },

	  generateArray: function generateArray(entries) {
	    var ret = this.generateList(entries);
	    ret.prepend('[');
	    ret.add(']');

	    return ret;
	  }
	};

	exports['default'] = CodeGen;
	module.exports = exports['default'];

/***/ })
/******/ ])
});
;

/***/ }),
/* 5 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function() {
	'use strict';
	return $;
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 6 */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(global) {var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;//     Backbone.js 1.3.3

//     (c) 2010-2016 Jeremy Ashkenas, DocumentCloud and Investigative Reporters & Editors
//     Backbone may be freely distributed under the MIT license.
//     For all details and documentation:
//     http://backbonejs.org

(function(factory) {

  // Establish the root object, `window` (`self`) in the browser, or `global` on the server.
  // We use `self` instead of `window` for `WebWorker` support.
  var root = (typeof self == 'object' && self.self === self && self) ||
            (typeof global == 'object' && global.global === global && global);

  // Set up Backbone appropriately for the environment. Start with AMD.
  if (true) {
    !(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__(3), __webpack_require__(5), exports], __WEBPACK_AMD_DEFINE_RESULT__ = function(_, $, exports) {
      // Export global even in AMD case in case this script is loaded with
      // others that may still expect a global Backbone.
      root.Backbone = factory(root, exports, _, $);
    }.apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));

  // Next for Node.js or CommonJS. jQuery may not be needed as a module.
  } else if (typeof exports !== 'undefined') {
    var _ = require('underscore'), $;
    try { $ = require('jquery'); } catch (e) {}
    factory(root, exports, _, $);

  // Finally, as a browser global.
  } else {
    root.Backbone = factory(root, {}, root._, (root.jQuery || root.Zepto || root.ender || root.$));
  }

})(function(root, Backbone, _, $) {

  // Initial Setup
  // -------------

  // Save the previous value of the `Backbone` variable, so that it can be
  // restored later on, if `noConflict` is used.
  var previousBackbone = root.Backbone;

  // Create a local reference to a common array method we'll want to use later.
  var slice = Array.prototype.slice;

  // Current version of the library. Keep in sync with `package.json`.
  Backbone.VERSION = '1.3.3';

  // For Backbone's purposes, jQuery, Zepto, Ender, or My Library (kidding) owns
  // the `$` variable.
  Backbone.$ = $;

  // Runs Backbone.js in *noConflict* mode, returning the `Backbone` variable
  // to its previous owner. Returns a reference to this Backbone object.
  Backbone.noConflict = function() {
    root.Backbone = previousBackbone;
    return this;
  };

  // Turn on `emulateHTTP` to support legacy HTTP servers. Setting this option
  // will fake `"PATCH"`, `"PUT"` and `"DELETE"` requests via the `_method` parameter and
  // set a `X-Http-Method-Override` header.
  Backbone.emulateHTTP = false;

  // Turn on `emulateJSON` to support legacy servers that can't deal with direct
  // `application/json` requests ... this will encode the body as
  // `application/x-www-form-urlencoded` instead and will send the model in a
  // form param named `model`.
  Backbone.emulateJSON = false;

  // Proxy Backbone class methods to Underscore functions, wrapping the model's
  // `attributes` object or collection's `models` array behind the scenes.
  //
  // collection.filter(function(model) { return model.get('age') > 10 });
  // collection.each(this.addView);
  //
  // `Function#apply` can be slow so we use the method's arg count, if we know it.
  var addMethod = function(length, method, attribute) {
    switch (length) {
      case 1: return function() {
        return _[method](this[attribute]);
      };
      case 2: return function(value) {
        return _[method](this[attribute], value);
      };
      case 3: return function(iteratee, context) {
        return _[method](this[attribute], cb(iteratee, this), context);
      };
      case 4: return function(iteratee, defaultVal, context) {
        return _[method](this[attribute], cb(iteratee, this), defaultVal, context);
      };
      default: return function() {
        var args = slice.call(arguments);
        args.unshift(this[attribute]);
        return _[method].apply(_, args);
      };
    }
  };
  var addUnderscoreMethods = function(Class, methods, attribute) {
    _.each(methods, function(length, method) {
      if (_[method]) Class.prototype[method] = addMethod(length, method, attribute);
    });
  };

  // Support `collection.sortBy('attr')` and `collection.findWhere({id: 1})`.
  var cb = function(iteratee, instance) {
    if (_.isFunction(iteratee)) return iteratee;
    if (_.isObject(iteratee) && !instance._isModel(iteratee)) return modelMatcher(iteratee);
    if (_.isString(iteratee)) return function(model) { return model.get(iteratee); };
    return iteratee;
  };
  var modelMatcher = function(attrs) {
    var matcher = _.matches(attrs);
    return function(model) {
      return matcher(model.attributes);
    };
  };

  // Backbone.Events
  // ---------------

  // A module that can be mixed in to *any object* in order to provide it with
  // a custom event channel. You may bind a callback to an event with `on` or
  // remove with `off`; `trigger`-ing an event fires all callbacks in
  // succession.
  //
  //     var object = {};
  //     _.extend(object, Backbone.Events);
  //     object.on('expand', function(){ alert('expanded'); });
  //     object.trigger('expand');
  //
  var Events = Backbone.Events = {};

  // Regular expression used to split event strings.
  var eventSplitter = /\s+/;

  // Iterates over the standard `event, callback` (as well as the fancy multiple
  // space-separated events `"change blur", callback` and jQuery-style event
  // maps `{event: callback}`).
  var eventsApi = function(iteratee, events, name, callback, opts) {
    var i = 0, names;
    if (name && typeof name === 'object') {
      // Handle event maps.
      if (callback !== void 0 && 'context' in opts && opts.context === void 0) opts.context = callback;
      for (names = _.keys(name); i < names.length ; i++) {
        events = eventsApi(iteratee, events, names[i], name[names[i]], opts);
      }
    } else if (name && eventSplitter.test(name)) {
      // Handle space-separated event names by delegating them individually.
      for (names = name.split(eventSplitter); i < names.length; i++) {
        events = iteratee(events, names[i], callback, opts);
      }
    } else {
      // Finally, standard events.
      events = iteratee(events, name, callback, opts);
    }
    return events;
  };

  // Bind an event to a `callback` function. Passing `"all"` will bind
  // the callback to all events fired.
  Events.on = function(name, callback, context) {
    return internalOn(this, name, callback, context);
  };

  // Guard the `listening` argument from the public API.
  var internalOn = function(obj, name, callback, context, listening) {
    obj._events = eventsApi(onApi, obj._events || {}, name, callback, {
      context: context,
      ctx: obj,
      listening: listening
    });

    if (listening) {
      var listeners = obj._listeners || (obj._listeners = {});
      listeners[listening.id] = listening;
    }

    return obj;
  };

  // Inversion-of-control versions of `on`. Tell *this* object to listen to
  // an event in another object... keeping track of what it's listening to
  // for easier unbinding later.
  Events.listenTo = function(obj, name, callback) {
    if (!obj) return this;
    var id = obj._listenId || (obj._listenId = _.uniqueId('l'));
    var listeningTo = this._listeningTo || (this._listeningTo = {});
    var listening = listeningTo[id];

    // This object is not listening to any other events on `obj` yet.
    // Setup the necessary references to track the listening callbacks.
    if (!listening) {
      var thisId = this._listenId || (this._listenId = _.uniqueId('l'));
      listening = listeningTo[id] = {obj: obj, objId: id, id: thisId, listeningTo: listeningTo, count: 0};
    }

    // Bind callbacks on obj, and keep track of them on listening.
    internalOn(obj, name, callback, this, listening);
    return this;
  };

  // The reducing API that adds a callback to the `events` object.
  var onApi = function(events, name, callback, options) {
    if (callback) {
      var handlers = events[name] || (events[name] = []);
      var context = options.context, ctx = options.ctx, listening = options.listening;
      if (listening) listening.count++;

      handlers.push({callback: callback, context: context, ctx: context || ctx, listening: listening});
    }
    return events;
  };

  // Remove one or many callbacks. If `context` is null, removes all
  // callbacks with that function. If `callback` is null, removes all
  // callbacks for the event. If `name` is null, removes all bound
  // callbacks for all events.
  Events.off = function(name, callback, context) {
    if (!this._events) return this;
    this._events = eventsApi(offApi, this._events, name, callback, {
      context: context,
      listeners: this._listeners
    });
    return this;
  };

  // Tell this object to stop listening to either specific events ... or
  // to every object it's currently listening to.
  Events.stopListening = function(obj, name, callback) {
    var listeningTo = this._listeningTo;
    if (!listeningTo) return this;

    var ids = obj ? [obj._listenId] : _.keys(listeningTo);

    for (var i = 0; i < ids.length; i++) {
      var listening = listeningTo[ids[i]];

      // If listening doesn't exist, this object is not currently
      // listening to obj. Break out early.
      if (!listening) break;

      listening.obj.off(name, callback, this);
    }

    return this;
  };

  // The reducing API that removes a callback from the `events` object.
  var offApi = function(events, name, callback, options) {
    if (!events) return;

    var i = 0, listening;
    var context = options.context, listeners = options.listeners;

    // Delete all events listeners and "drop" events.
    if (!name && !callback && !context) {
      var ids = _.keys(listeners);
      for (; i < ids.length; i++) {
        listening = listeners[ids[i]];
        delete listeners[listening.id];
        delete listening.listeningTo[listening.objId];
      }
      return;
    }

    var names = name ? [name] : _.keys(events);
    for (; i < names.length; i++) {
      name = names[i];
      var handlers = events[name];

      // Bail out if there are no events stored.
      if (!handlers) break;

      // Replace events if there are any remaining.  Otherwise, clean up.
      var remaining = [];
      for (var j = 0; j < handlers.length; j++) {
        var handler = handlers[j];
        if (
          callback && callback !== handler.callback &&
            callback !== handler.callback._callback ||
              context && context !== handler.context
        ) {
          remaining.push(handler);
        } else {
          listening = handler.listening;
          if (listening && --listening.count === 0) {
            delete listeners[listening.id];
            delete listening.listeningTo[listening.objId];
          }
        }
      }

      // Update tail event if the list has any events.  Otherwise, clean up.
      if (remaining.length) {
        events[name] = remaining;
      } else {
        delete events[name];
      }
    }
    return events;
  };

  // Bind an event to only be triggered a single time. After the first time
  // the callback is invoked, its listener will be removed. If multiple events
  // are passed in using the space-separated syntax, the handler will fire
  // once for each event, not once for a combination of all events.
  Events.once = function(name, callback, context) {
    // Map the event into a `{event: once}` object.
    var events = eventsApi(onceMap, {}, name, callback, _.bind(this.off, this));
    if (typeof name === 'string' && context == null) callback = void 0;
    return this.on(events, callback, context);
  };

  // Inversion-of-control versions of `once`.
  Events.listenToOnce = function(obj, name, callback) {
    // Map the event into a `{event: once}` object.
    var events = eventsApi(onceMap, {}, name, callback, _.bind(this.stopListening, this, obj));
    return this.listenTo(obj, events);
  };

  // Reduces the event callbacks into a map of `{event: onceWrapper}`.
  // `offer` unbinds the `onceWrapper` after it has been called.
  var onceMap = function(map, name, callback, offer) {
    if (callback) {
      var once = map[name] = _.once(function() {
        offer(name, once);
        callback.apply(this, arguments);
      });
      once._callback = callback;
    }
    return map;
  };

  // Trigger one or many events, firing all bound callbacks. Callbacks are
  // passed the same arguments as `trigger` is, apart from the event name
  // (unless you're listening on `"all"`, which will cause your callback to
  // receive the true name of the event as the first argument).
  Events.trigger = function(name) {
    if (!this._events) return this;

    var length = Math.max(0, arguments.length - 1);
    var args = Array(length);
    for (var i = 0; i < length; i++) args[i] = arguments[i + 1];

    eventsApi(triggerApi, this._events, name, void 0, args);
    return this;
  };

  // Handles triggering the appropriate event callbacks.
  var triggerApi = function(objEvents, name, callback, args) {
    if (objEvents) {
      var events = objEvents[name];
      var allEvents = objEvents.all;
      if (events && allEvents) allEvents = allEvents.slice();
      if (events) triggerEvents(events, args);
      if (allEvents) triggerEvents(allEvents, [name].concat(args));
    }
    return objEvents;
  };

  // A difficult-to-believe, but optimized internal dispatch function for
  // triggering events. Tries to keep the usual cases speedy (most internal
  // Backbone events have 3 arguments).
  var triggerEvents = function(events, args) {
    var ev, i = -1, l = events.length, a1 = args[0], a2 = args[1], a3 = args[2];
    switch (args.length) {
      case 0: while (++i < l) (ev = events[i]).callback.call(ev.ctx); return;
      case 1: while (++i < l) (ev = events[i]).callback.call(ev.ctx, a1); return;
      case 2: while (++i < l) (ev = events[i]).callback.call(ev.ctx, a1, a2); return;
      case 3: while (++i < l) (ev = events[i]).callback.call(ev.ctx, a1, a2, a3); return;
      default: while (++i < l) (ev = events[i]).callback.apply(ev.ctx, args); return;
    }
  };

  // Aliases for backwards compatibility.
  Events.bind   = Events.on;
  Events.unbind = Events.off;

  // Allow the `Backbone` object to serve as a global event bus, for folks who
  // want global "pubsub" in a convenient place.
  _.extend(Backbone, Events);

  // Backbone.Model
  // --------------

  // Backbone **Models** are the basic data object in the framework --
  // frequently representing a row in a table in a database on your server.
  // A discrete chunk of data and a bunch of useful, related methods for
  // performing computations and transformations on that data.

  // Create a new model with the specified attributes. A client id (`cid`)
  // is automatically generated and assigned for you.
  var Model = Backbone.Model = function(attributes, options) {
    var attrs = attributes || {};
    options || (options = {});
    this.cid = _.uniqueId(this.cidPrefix);
    this.attributes = {};
    if (options.collection) this.collection = options.collection;
    if (options.parse) attrs = this.parse(attrs, options) || {};
    var defaults = _.result(this, 'defaults');
    attrs = _.defaults(_.extend({}, defaults, attrs), defaults);
    this.set(attrs, options);
    this.changed = {};
    this.initialize.apply(this, arguments);
  };

  // Attach all inheritable methods to the Model prototype.
  _.extend(Model.prototype, Events, {

    // A hash of attributes whose current and previous value differ.
    changed: null,

    // The value returned during the last failed validation.
    validationError: null,

    // The default name for the JSON `id` attribute is `"id"`. MongoDB and
    // CouchDB users may want to set this to `"_id"`.
    idAttribute: 'id',

    // The prefix is used to create the client id which is used to identify models locally.
    // You may want to override this if you're experiencing name clashes with model ids.
    cidPrefix: 'c',

    // Initialize is an empty function by default. Override it with your own
    // initialization logic.
    initialize: function(){},

    // Return a copy of the model's `attributes` object.
    toJSON: function(options) {
      return _.clone(this.attributes);
    },

    // Proxy `Backbone.sync` by default -- but override this if you need
    // custom syncing semantics for *this* particular model.
    sync: function() {
      return Backbone.sync.apply(this, arguments);
    },

    // Get the value of an attribute.
    get: function(attr) {
      return this.attributes[attr];
    },

    // Get the HTML-escaped value of an attribute.
    escape: function(attr) {
      return _.escape(this.get(attr));
    },

    // Returns `true` if the attribute contains a value that is not null
    // or undefined.
    has: function(attr) {
      return this.get(attr) != null;
    },

    // Special-cased proxy to underscore's `_.matches` method.
    matches: function(attrs) {
      return !!_.iteratee(attrs, this)(this.attributes);
    },

    // Set a hash of model attributes on the object, firing `"change"`. This is
    // the core primitive operation of a model, updating the data and notifying
    // anyone who needs to know about the change in state. The heart of the beast.
    set: function(key, val, options) {
      if (key == null) return this;

      // Handle both `"key", value` and `{key: value}` -style arguments.
      var attrs;
      if (typeof key === 'object') {
        attrs = key;
        options = val;
      } else {
        (attrs = {})[key] = val;
      }

      options || (options = {});

      // Run validation.
      if (!this._validate(attrs, options)) return false;

      // Extract attributes and options.
      var unset      = options.unset;
      var silent     = options.silent;
      var changes    = [];
      var changing   = this._changing;
      this._changing = true;

      if (!changing) {
        this._previousAttributes = _.clone(this.attributes);
        this.changed = {};
      }

      var current = this.attributes;
      var changed = this.changed;
      var prev    = this._previousAttributes;

      // For each `set` attribute, update or delete the current value.
      for (var attr in attrs) {
        val = attrs[attr];
        if (!_.isEqual(current[attr], val)) changes.push(attr);
        if (!_.isEqual(prev[attr], val)) {
          changed[attr] = val;
        } else {
          delete changed[attr];
        }
        unset ? delete current[attr] : current[attr] = val;
      }

      // Update the `id`.
      if (this.idAttribute in attrs) this.id = this.get(this.idAttribute);

      // Trigger all relevant attribute changes.
      if (!silent) {
        if (changes.length) this._pending = options;
        for (var i = 0; i < changes.length; i++) {
          this.trigger('change:' + changes[i], this, current[changes[i]], options);
        }
      }

      // You might be wondering why there's a `while` loop here. Changes can
      // be recursively nested within `"change"` events.
      if (changing) return this;
      if (!silent) {
        while (this._pending) {
          options = this._pending;
          this._pending = false;
          this.trigger('change', this, options);
        }
      }
      this._pending = false;
      this._changing = false;
      return this;
    },

    // Remove an attribute from the model, firing `"change"`. `unset` is a noop
    // if the attribute doesn't exist.
    unset: function(attr, options) {
      return this.set(attr, void 0, _.extend({}, options, {unset: true}));
    },

    // Clear all attributes on the model, firing `"change"`.
    clear: function(options) {
      var attrs = {};
      for (var key in this.attributes) attrs[key] = void 0;
      return this.set(attrs, _.extend({}, options, {unset: true}));
    },

    // Determine if the model has changed since the last `"change"` event.
    // If you specify an attribute name, determine if that attribute has changed.
    hasChanged: function(attr) {
      if (attr == null) return !_.isEmpty(this.changed);
      return _.has(this.changed, attr);
    },

    // Return an object containing all the attributes that have changed, or
    // false if there are no changed attributes. Useful for determining what
    // parts of a view need to be updated and/or what attributes need to be
    // persisted to the server. Unset attributes will be set to undefined.
    // You can also pass an attributes object to diff against the model,
    // determining if there *would be* a change.
    changedAttributes: function(diff) {
      if (!diff) return this.hasChanged() ? _.clone(this.changed) : false;
      var old = this._changing ? this._previousAttributes : this.attributes;
      var changed = {};
      for (var attr in diff) {
        var val = diff[attr];
        if (_.isEqual(old[attr], val)) continue;
        changed[attr] = val;
      }
      return _.size(changed) ? changed : false;
    },

    // Get the previous value of an attribute, recorded at the time the last
    // `"change"` event was fired.
    previous: function(attr) {
      if (attr == null || !this._previousAttributes) return null;
      return this._previousAttributes[attr];
    },

    // Get all of the attributes of the model at the time of the previous
    // `"change"` event.
    previousAttributes: function() {
      return _.clone(this._previousAttributes);
    },

    // Fetch the model from the server, merging the response with the model's
    // local attributes. Any changed attributes will trigger a "change" event.
    fetch: function(options) {
      options = _.extend({parse: true}, options);
      var model = this;
      var success = options.success;
      options.success = function(resp) {
        var serverAttrs = options.parse ? model.parse(resp, options) : resp;
        if (!model.set(serverAttrs, options)) return false;
        if (success) success.call(options.context, model, resp, options);
        model.trigger('sync', model, resp, options);
      };
      wrapError(this, options);
      return this.sync('read', this, options);
    },

    // Set a hash of model attributes, and sync the model to the server.
    // If the server returns an attributes hash that differs, the model's
    // state will be `set` again.
    save: function(key, val, options) {
      // Handle both `"key", value` and `{key: value}` -style arguments.
      var attrs;
      if (key == null || typeof key === 'object') {
        attrs = key;
        options = val;
      } else {
        (attrs = {})[key] = val;
      }

      options = _.extend({validate: true, parse: true}, options);
      var wait = options.wait;

      // If we're not waiting and attributes exist, save acts as
      // `set(attr).save(null, opts)` with validation. Otherwise, check if
      // the model will be valid when the attributes, if any, are set.
      if (attrs && !wait) {
        if (!this.set(attrs, options)) return false;
      } else if (!this._validate(attrs, options)) {
        return false;
      }

      // After a successful server-side save, the client is (optionally)
      // updated with the server-side state.
      var model = this;
      var success = options.success;
      var attributes = this.attributes;
      options.success = function(resp) {
        // Ensure attributes are restored during synchronous saves.
        model.attributes = attributes;
        var serverAttrs = options.parse ? model.parse(resp, options) : resp;
        if (wait) serverAttrs = _.extend({}, attrs, serverAttrs);
        if (serverAttrs && !model.set(serverAttrs, options)) return false;
        if (success) success.call(options.context, model, resp, options);
        model.trigger('sync', model, resp, options);
      };
      wrapError(this, options);

      // Set temporary attributes if `{wait: true}` to properly find new ids.
      if (attrs && wait) this.attributes = _.extend({}, attributes, attrs);

      var method = this.isNew() ? 'create' : (options.patch ? 'patch' : 'update');
      if (method === 'patch' && !options.attrs) options.attrs = attrs;
      var xhr = this.sync(method, this, options);

      // Restore attributes.
      this.attributes = attributes;

      return xhr;
    },

    // Destroy this model on the server if it was already persisted.
    // Optimistically removes the model from its collection, if it has one.
    // If `wait: true` is passed, waits for the server to respond before removal.
    destroy: function(options) {
      options = options ? _.clone(options) : {};
      var model = this;
      var success = options.success;
      var wait = options.wait;

      var destroy = function() {
        model.stopListening();
        model.trigger('destroy', model, model.collection, options);
      };

      options.success = function(resp) {
        if (wait) destroy();
        if (success) success.call(options.context, model, resp, options);
        if (!model.isNew()) model.trigger('sync', model, resp, options);
      };

      var xhr = false;
      if (this.isNew()) {
        _.defer(options.success);
      } else {
        wrapError(this, options);
        xhr = this.sync('delete', this, options);
      }
      if (!wait) destroy();
      return xhr;
    },

    // Default URL for the model's representation on the server -- if you're
    // using Backbone's restful methods, override this to change the endpoint
    // that will be called.
    url: function() {
      var base =
        _.result(this, 'urlRoot') ||
        _.result(this.collection, 'url') ||
        urlError();
      if (this.isNew()) return base;
      var id = this.get(this.idAttribute);
      return base.replace(/[^\/]$/, '$&/') + encodeURIComponent(id);
    },

    // **parse** converts a response into the hash of attributes to be `set` on
    // the model. The default implementation is just to pass the response along.
    parse: function(resp, options) {
      return resp;
    },

    // Create a new model with identical attributes to this one.
    clone: function() {
      return new this.constructor(this.attributes);
    },

    // A model is new if it has never been saved to the server, and lacks an id.
    isNew: function() {
      return !this.has(this.idAttribute);
    },

    // Check if the model is currently in a valid state.
    isValid: function(options) {
      return this._validate({}, _.extend({}, options, {validate: true}));
    },

    // Run validation against the next complete set of model attributes,
    // returning `true` if all is well. Otherwise, fire an `"invalid"` event.
    _validate: function(attrs, options) {
      if (!options.validate || !this.validate) return true;
      attrs = _.extend({}, this.attributes, attrs);
      var error = this.validationError = this.validate(attrs, options) || null;
      if (!error) return true;
      this.trigger('invalid', this, error, _.extend(options, {validationError: error}));
      return false;
    }

  });

  // Underscore methods that we want to implement on the Model, mapped to the
  // number of arguments they take.
  var modelMethods = {keys: 1, values: 1, pairs: 1, invert: 1, pick: 0,
      omit: 0, chain: 1, isEmpty: 1};

  // Mix in each Underscore method as a proxy to `Model#attributes`.
  addUnderscoreMethods(Model, modelMethods, 'attributes');

  // Backbone.Collection
  // -------------------

  // If models tend to represent a single row of data, a Backbone Collection is
  // more analogous to a table full of data ... or a small slice or page of that
  // table, or a collection of rows that belong together for a particular reason
  // -- all of the messages in this particular folder, all of the documents
  // belonging to this particular author, and so on. Collections maintain
  // indexes of their models, both in order, and for lookup by `id`.

  // Create a new **Collection**, perhaps to contain a specific type of `model`.
  // If a `comparator` is specified, the Collection will maintain
  // its models in sort order, as they're added and removed.
  var Collection = Backbone.Collection = function(models, options) {
    options || (options = {});
    if (options.model) this.model = options.model;
    if (options.comparator !== void 0) this.comparator = options.comparator;
    this._reset();
    this.initialize.apply(this, arguments);
    if (models) this.reset(models, _.extend({silent: true}, options));
  };

  // Default options for `Collection#set`.
  var setOptions = {add: true, remove: true, merge: true};
  var addOptions = {add: true, remove: false};

  // Splices `insert` into `array` at index `at`.
  var splice = function(array, insert, at) {
    at = Math.min(Math.max(at, 0), array.length);
    var tail = Array(array.length - at);
    var length = insert.length;
    var i;
    for (i = 0; i < tail.length; i++) tail[i] = array[i + at];
    for (i = 0; i < length; i++) array[i + at] = insert[i];
    for (i = 0; i < tail.length; i++) array[i + length + at] = tail[i];
  };

  // Define the Collection's inheritable methods.
  _.extend(Collection.prototype, Events, {

    // The default model for a collection is just a **Backbone.Model**.
    // This should be overridden in most cases.
    model: Model,

    // Initialize is an empty function by default. Override it with your own
    // initialization logic.
    initialize: function(){},

    // The JSON representation of a Collection is an array of the
    // models' attributes.
    toJSON: function(options) {
      return this.map(function(model) { return model.toJSON(options); });
    },

    // Proxy `Backbone.sync` by default.
    sync: function() {
      return Backbone.sync.apply(this, arguments);
    },

    // Add a model, or list of models to the set. `models` may be Backbone
    // Models or raw JavaScript objects to be converted to Models, or any
    // combination of the two.
    add: function(models, options) {
      return this.set(models, _.extend({merge: false}, options, addOptions));
    },

    // Remove a model, or a list of models from the set.
    remove: function(models, options) {
      options = _.extend({}, options);
      var singular = !_.isArray(models);
      models = singular ? [models] : models.slice();
      var removed = this._removeModels(models, options);
      if (!options.silent && removed.length) {
        options.changes = {added: [], merged: [], removed: removed};
        this.trigger('update', this, options);
      }
      return singular ? removed[0] : removed;
    },

    // Update a collection by `set`-ing a new list of models, adding new ones,
    // removing models that are no longer present, and merging models that
    // already exist in the collection, as necessary. Similar to **Model#set**,
    // the core operation for updating the data contained by the collection.
    set: function(models, options) {
      if (models == null) return;

      options = _.extend({}, setOptions, options);
      if (options.parse && !this._isModel(models)) {
        models = this.parse(models, options) || [];
      }

      var singular = !_.isArray(models);
      models = singular ? [models] : models.slice();

      var at = options.at;
      if (at != null) at = +at;
      if (at > this.length) at = this.length;
      if (at < 0) at += this.length + 1;

      var set = [];
      var toAdd = [];
      var toMerge = [];
      var toRemove = [];
      var modelMap = {};

      var add = options.add;
      var merge = options.merge;
      var remove = options.remove;

      var sort = false;
      var sortable = this.comparator && at == null && options.sort !== false;
      var sortAttr = _.isString(this.comparator) ? this.comparator : null;

      // Turn bare objects into model references, and prevent invalid models
      // from being added.
      var model, i;
      for (i = 0; i < models.length; i++) {
        model = models[i];

        // If a duplicate is found, prevent it from being added and
        // optionally merge it into the existing model.
        var existing = this.get(model);
        if (existing) {
          if (merge && model !== existing) {
            var attrs = this._isModel(model) ? model.attributes : model;
            if (options.parse) attrs = existing.parse(attrs, options);
            existing.set(attrs, options);
            toMerge.push(existing);
            if (sortable && !sort) sort = existing.hasChanged(sortAttr);
          }
          if (!modelMap[existing.cid]) {
            modelMap[existing.cid] = true;
            set.push(existing);
          }
          models[i] = existing;

        // If this is a new, valid model, push it to the `toAdd` list.
        } else if (add) {
          model = models[i] = this._prepareModel(model, options);
          if (model) {
            toAdd.push(model);
            this._addReference(model, options);
            modelMap[model.cid] = true;
            set.push(model);
          }
        }
      }

      // Remove stale models.
      if (remove) {
        for (i = 0; i < this.length; i++) {
          model = this.models[i];
          if (!modelMap[model.cid]) toRemove.push(model);
        }
        if (toRemove.length) this._removeModels(toRemove, options);
      }

      // See if sorting is needed, update `length` and splice in new models.
      var orderChanged = false;
      var replace = !sortable && add && remove;
      if (set.length && replace) {
        orderChanged = this.length !== set.length || _.some(this.models, function(m, index) {
          return m !== set[index];
        });
        this.models.length = 0;
        splice(this.models, set, 0);
        this.length = this.models.length;
      } else if (toAdd.length) {
        if (sortable) sort = true;
        splice(this.models, toAdd, at == null ? this.length : at);
        this.length = this.models.length;
      }

      // Silently sort the collection if appropriate.
      if (sort) this.sort({silent: true});

      // Unless silenced, it's time to fire all appropriate add/sort/update events.
      if (!options.silent) {
        for (i = 0; i < toAdd.length; i++) {
          if (at != null) options.index = at + i;
          model = toAdd[i];
          model.trigger('add', model, this, options);
        }
        if (sort || orderChanged) this.trigger('sort', this, options);
        if (toAdd.length || toRemove.length || toMerge.length) {
          options.changes = {
            added: toAdd,
            removed: toRemove,
            merged: toMerge
          };
          this.trigger('update', this, options);
        }
      }

      // Return the added (or merged) model (or models).
      return singular ? models[0] : models;
    },

    // When you have more items than you want to add or remove individually,
    // you can reset the entire set with a new list of models, without firing
    // any granular `add` or `remove` events. Fires `reset` when finished.
    // Useful for bulk operations and optimizations.
    reset: function(models, options) {
      options = options ? _.clone(options) : {};
      for (var i = 0; i < this.models.length; i++) {
        this._removeReference(this.models[i], options);
      }
      options.previousModels = this.models;
      this._reset();
      models = this.add(models, _.extend({silent: true}, options));
      if (!options.silent) this.trigger('reset', this, options);
      return models;
    },

    // Add a model to the end of the collection.
    push: function(model, options) {
      return this.add(model, _.extend({at: this.length}, options));
    },

    // Remove a model from the end of the collection.
    pop: function(options) {
      var model = this.at(this.length - 1);
      return this.remove(model, options);
    },

    // Add a model to the beginning of the collection.
    unshift: function(model, options) {
      return this.add(model, _.extend({at: 0}, options));
    },

    // Remove a model from the beginning of the collection.
    shift: function(options) {
      var model = this.at(0);
      return this.remove(model, options);
    },

    // Slice out a sub-array of models from the collection.
    slice: function() {
      return slice.apply(this.models, arguments);
    },

    // Get a model from the set by id, cid, model object with id or cid
    // properties, or an attributes object that is transformed through modelId.
    get: function(obj) {
      if (obj == null) return void 0;
      return this._byId[obj] ||
        this._byId[this.modelId(obj.attributes || obj)] ||
        obj.cid && this._byId[obj.cid];
    },

    // Returns `true` if the model is in the collection.
    has: function(obj) {
      return this.get(obj) != null;
    },

    // Get the model at the given index.
    at: function(index) {
      if (index < 0) index += this.length;
      return this.models[index];
    },

    // Return models with matching attributes. Useful for simple cases of
    // `filter`.
    where: function(attrs, first) {
      return this[first ? 'find' : 'filter'](attrs);
    },

    // Return the first model with matching attributes. Useful for simple cases
    // of `find`.
    findWhere: function(attrs) {
      return this.where(attrs, true);
    },

    // Force the collection to re-sort itself. You don't need to call this under
    // normal circumstances, as the set will maintain sort order as each item
    // is added.
    sort: function(options) {
      var comparator = this.comparator;
      if (!comparator) throw new Error('Cannot sort a set without a comparator');
      options || (options = {});

      var length = comparator.length;
      if (_.isFunction(comparator)) comparator = _.bind(comparator, this);

      // Run sort based on type of `comparator`.
      if (length === 1 || _.isString(comparator)) {
        this.models = this.sortBy(comparator);
      } else {
        this.models.sort(comparator);
      }
      if (!options.silent) this.trigger('sort', this, options);
      return this;
    },

    // Pluck an attribute from each model in the collection.
    pluck: function(attr) {
      return this.map(attr + '');
    },

    // Fetch the default set of models for this collection, resetting the
    // collection when they arrive. If `reset: true` is passed, the response
    // data will be passed through the `reset` method instead of `set`.
    fetch: function(options) {
      options = _.extend({parse: true}, options);
      var success = options.success;
      var collection = this;
      options.success = function(resp) {
        var method = options.reset ? 'reset' : 'set';
        collection[method](resp, options);
        if (success) success.call(options.context, collection, resp, options);
        collection.trigger('sync', collection, resp, options);
      };
      wrapError(this, options);
      return this.sync('read', this, options);
    },

    // Create a new instance of a model in this collection. Add the model to the
    // collection immediately, unless `wait: true` is passed, in which case we
    // wait for the server to agree.
    create: function(model, options) {
      options = options ? _.clone(options) : {};
      var wait = options.wait;
      model = this._prepareModel(model, options);
      if (!model) return false;
      if (!wait) this.add(model, options);
      var collection = this;
      var success = options.success;
      options.success = function(m, resp, callbackOpts) {
        if (wait) collection.add(m, callbackOpts);
        if (success) success.call(callbackOpts.context, m, resp, callbackOpts);
      };
      model.save(null, options);
      return model;
    },

    // **parse** converts a response into a list of models to be added to the
    // collection. The default implementation is just to pass it through.
    parse: function(resp, options) {
      return resp;
    },

    // Create a new collection with an identical list of models as this one.
    clone: function() {
      return new this.constructor(this.models, {
        model: this.model,
        comparator: this.comparator
      });
    },

    // Define how to uniquely identify models in the collection.
    modelId: function(attrs) {
      return attrs[this.model.prototype.idAttribute || 'id'];
    },

    // Private method to reset all internal state. Called when the collection
    // is first initialized or reset.
    _reset: function() {
      this.length = 0;
      this.models = [];
      this._byId  = {};
    },

    // Prepare a hash of attributes (or other model) to be added to this
    // collection.
    _prepareModel: function(attrs, options) {
      if (this._isModel(attrs)) {
        if (!attrs.collection) attrs.collection = this;
        return attrs;
      }
      options = options ? _.clone(options) : {};
      options.collection = this;
      var model = new this.model(attrs, options);
      if (!model.validationError) return model;
      this.trigger('invalid', this, model.validationError, options);
      return false;
    },

    // Internal method called by both remove and set.
    _removeModels: function(models, options) {
      var removed = [];
      for (var i = 0; i < models.length; i++) {
        var model = this.get(models[i]);
        if (!model) continue;

        var index = this.indexOf(model);
        this.models.splice(index, 1);
        this.length--;

        // Remove references before triggering 'remove' event to prevent an
        // infinite loop. #3693
        delete this._byId[model.cid];
        var id = this.modelId(model.attributes);
        if (id != null) delete this._byId[id];

        if (!options.silent) {
          options.index = index;
          model.trigger('remove', model, this, options);
        }

        removed.push(model);
        this._removeReference(model, options);
      }
      return removed;
    },

    // Method for checking whether an object should be considered a model for
    // the purposes of adding to the collection.
    _isModel: function(model) {
      return model instanceof Model;
    },

    // Internal method to create a model's ties to a collection.
    _addReference: function(model, options) {
      this._byId[model.cid] = model;
      var id = this.modelId(model.attributes);
      if (id != null) this._byId[id] = model;
      model.on('all', this._onModelEvent, this);
    },

    // Internal method to sever a model's ties to a collection.
    _removeReference: function(model, options) {
      delete this._byId[model.cid];
      var id = this.modelId(model.attributes);
      if (id != null) delete this._byId[id];
      if (this === model.collection) delete model.collection;
      model.off('all', this._onModelEvent, this);
    },

    // Internal method called every time a model in the set fires an event.
    // Sets need to update their indexes when models change ids. All other
    // events simply proxy through. "add" and "remove" events that originate
    // in other collections are ignored.
    _onModelEvent: function(event, model, collection, options) {
      if (model) {
        if ((event === 'add' || event === 'remove') && collection !== this) return;
        if (event === 'destroy') this.remove(model, options);
        if (event === 'change') {
          var prevId = this.modelId(model.previousAttributes());
          var id = this.modelId(model.attributes);
          if (prevId !== id) {
            if (prevId != null) delete this._byId[prevId];
            if (id != null) this._byId[id] = model;
          }
        }
      }
      this.trigger.apply(this, arguments);
    }

  });

  // Underscore methods that we want to implement on the Collection.
  // 90% of the core usefulness of Backbone Collections is actually implemented
  // right here:
  var collectionMethods = {forEach: 3, each: 3, map: 3, collect: 3, reduce: 0,
      foldl: 0, inject: 0, reduceRight: 0, foldr: 0, find: 3, detect: 3, filter: 3,
      select: 3, reject: 3, every: 3, all: 3, some: 3, any: 3, include: 3, includes: 3,
      contains: 3, invoke: 0, max: 3, min: 3, toArray: 1, size: 1, first: 3,
      head: 3, take: 3, initial: 3, rest: 3, tail: 3, drop: 3, last: 3,
      without: 0, difference: 0, indexOf: 3, shuffle: 1, lastIndexOf: 3,
      isEmpty: 1, chain: 1, sample: 3, partition: 3, groupBy: 3, countBy: 3,
      sortBy: 3, indexBy: 3, findIndex: 3, findLastIndex: 3};

  // Mix in each Underscore method as a proxy to `Collection#models`.
  addUnderscoreMethods(Collection, collectionMethods, 'models');

  // Backbone.View
  // -------------

  // Backbone Views are almost more convention than they are actual code. A View
  // is simply a JavaScript object that represents a logical chunk of UI in the
  // DOM. This might be a single item, an entire list, a sidebar or panel, or
  // even the surrounding frame which wraps your whole app. Defining a chunk of
  // UI as a **View** allows you to define your DOM events declaratively, without
  // having to worry about render order ... and makes it easy for the view to
  // react to specific changes in the state of your models.

  // Creating a Backbone.View creates its initial element outside of the DOM,
  // if an existing element is not provided...
  var View = Backbone.View = function(options) {
    this.cid = _.uniqueId('view');
    _.extend(this, _.pick(options, viewOptions));
    this._ensureElement();
    this.initialize.apply(this, arguments);
  };

  // Cached regex to split keys for `delegate`.
  var delegateEventSplitter = /^(\S+)\s*(.*)$/;

  // List of view options to be set as properties.
  var viewOptions = ['model', 'collection', 'el', 'id', 'attributes', 'className', 'tagName', 'events'];

  // Set up all inheritable **Backbone.View** properties and methods.
  _.extend(View.prototype, Events, {

    // The default `tagName` of a View's element is `"div"`.
    tagName: 'div',

    // jQuery delegate for element lookup, scoped to DOM elements within the
    // current view. This should be preferred to global lookups where possible.
    $: function(selector) {
      return this.$el.find(selector);
    },

    // Initialize is an empty function by default. Override it with your own
    // initialization logic.
    initialize: function(){},

    // **render** is the core function that your view should override, in order
    // to populate its element (`this.el`), with the appropriate HTML. The
    // convention is for **render** to always return `this`.
    render: function() {
      return this;
    },

    // Remove this view by taking the element out of the DOM, and removing any
    // applicable Backbone.Events listeners.
    remove: function() {
      this._removeElement();
      this.stopListening();
      return this;
    },

    // Remove this view's element from the document and all event listeners
    // attached to it. Exposed for subclasses using an alternative DOM
    // manipulation API.
    _removeElement: function() {
      this.$el.remove();
    },

    // Change the view's element (`this.el` property) and re-delegate the
    // view's events on the new element.
    setElement: function(element) {
      this.undelegateEvents();
      this._setElement(element);
      this.delegateEvents();
      return this;
    },

    // Creates the `this.el` and `this.$el` references for this view using the
    // given `el`. `el` can be a CSS selector or an HTML string, a jQuery
    // context or an element. Subclasses can override this to utilize an
    // alternative DOM manipulation API and are only required to set the
    // `this.el` property.
    _setElement: function(el) {
      this.$el = el instanceof Backbone.$ ? el : Backbone.$(el);
      this.el = this.$el[0];
    },

    // Set callbacks, where `this.events` is a hash of
    //
    // *{"event selector": "callback"}*
    //
    //     {
    //       'mousedown .title':  'edit',
    //       'click .button':     'save',
    //       'click .open':       function(e) { ... }
    //     }
    //
    // pairs. Callbacks will be bound to the view, with `this` set properly.
    // Uses event delegation for efficiency.
    // Omitting the selector binds the event to `this.el`.
    delegateEvents: function(events) {
      events || (events = _.result(this, 'events'));
      if (!events) return this;
      this.undelegateEvents();
      for (var key in events) {
        var method = events[key];
        if (!_.isFunction(method)) method = this[method];
        if (!method) continue;
        var match = key.match(delegateEventSplitter);
        this.delegate(match[1], match[2], _.bind(method, this));
      }
      return this;
    },

    // Add a single event listener to the view's element (or a child element
    // using `selector`). This only works for delegate-able events: not `focus`,
    // `blur`, and not `change`, `submit`, and `reset` in Internet Explorer.
    delegate: function(eventName, selector, listener) {
      this.$el.on(eventName + '.delegateEvents' + this.cid, selector, listener);
      return this;
    },

    // Clears all callbacks previously bound to the view by `delegateEvents`.
    // You usually don't need to use this, but may wish to if you have multiple
    // Backbone views attached to the same DOM element.
    undelegateEvents: function() {
      if (this.$el) this.$el.off('.delegateEvents' + this.cid);
      return this;
    },

    // A finer-grained `undelegateEvents` for removing a single delegated event.
    // `selector` and `listener` are both optional.
    undelegate: function(eventName, selector, listener) {
      this.$el.off(eventName + '.delegateEvents' + this.cid, selector, listener);
      return this;
    },

    // Produces a DOM element to be assigned to your view. Exposed for
    // subclasses using an alternative DOM manipulation API.
    _createElement: function(tagName) {
      return document.createElement(tagName);
    },

    // Ensure that the View has a DOM element to render into.
    // If `this.el` is a string, pass it through `$()`, take the first
    // matching element, and re-assign it to `el`. Otherwise, create
    // an element from the `id`, `className` and `tagName` properties.
    _ensureElement: function() {
      if (!this.el) {
        var attrs = _.extend({}, _.result(this, 'attributes'));
        if (this.id) attrs.id = _.result(this, 'id');
        if (this.className) attrs['class'] = _.result(this, 'className');
        this.setElement(this._createElement(_.result(this, 'tagName')));
        this._setAttributes(attrs);
      } else {
        this.setElement(_.result(this, 'el'));
      }
    },

    // Set attributes from a hash on this view's element.  Exposed for
    // subclasses using an alternative DOM manipulation API.
    _setAttributes: function(attributes) {
      this.$el.attr(attributes);
    }

  });

  // Backbone.sync
  // -------------

  // Override this function to change the manner in which Backbone persists
  // models to the server. You will be passed the type of request, and the
  // model in question. By default, makes a RESTful Ajax request
  // to the model's `url()`. Some possible customizations could be:
  //
  // * Use `setTimeout` to batch rapid-fire updates into a single request.
  // * Send up the models as XML instead of JSON.
  // * Persist models via WebSockets instead of Ajax.
  //
  // Turn on `Backbone.emulateHTTP` in order to send `PUT` and `DELETE` requests
  // as `POST`, with a `_method` parameter containing the true HTTP method,
  // as well as all requests with the body as `application/x-www-form-urlencoded`
  // instead of `application/json` with the model in a param named `model`.
  // Useful when interfacing with server-side languages like **PHP** that make
  // it difficult to read the body of `PUT` requests.
  Backbone.sync = function(method, model, options) {
    var type = methodMap[method];

    // Default options, unless specified.
    _.defaults(options || (options = {}), {
      emulateHTTP: Backbone.emulateHTTP,
      emulateJSON: Backbone.emulateJSON
    });

    // Default JSON-request options.
    var params = {type: type, dataType: 'json'};

    // Ensure that we have a URL.
    if (!options.url) {
      params.url = _.result(model, 'url') || urlError();
    }

    // Ensure that we have the appropriate request data.
    if (options.data == null && model && (method === 'create' || method === 'update' || method === 'patch')) {
      params.contentType = 'application/json';
      params.data = JSON.stringify(options.attrs || model.toJSON(options));
    }

    // For older servers, emulate JSON by encoding the request into an HTML-form.
    if (options.emulateJSON) {
      params.contentType = 'application/x-www-form-urlencoded';
      params.data = params.data ? {model: params.data} : {};
    }

    // For older servers, emulate HTTP by mimicking the HTTP method with `_method`
    // And an `X-HTTP-Method-Override` header.
    if (options.emulateHTTP && (type === 'PUT' || type === 'DELETE' || type === 'PATCH')) {
      params.type = 'POST';
      if (options.emulateJSON) params.data._method = type;
      var beforeSend = options.beforeSend;
      options.beforeSend = function(xhr) {
        xhr.setRequestHeader('X-HTTP-Method-Override', type);
        if (beforeSend) return beforeSend.apply(this, arguments);
      };
    }

    // Don't process data on a non-GET request.
    if (params.type !== 'GET' && !options.emulateJSON) {
      params.processData = false;
    }

    // Pass along `textStatus` and `errorThrown` from jQuery.
    var error = options.error;
    options.error = function(xhr, textStatus, errorThrown) {
      options.textStatus = textStatus;
      options.errorThrown = errorThrown;
      if (error) error.call(options.context, xhr, textStatus, errorThrown);
    };

    // Make the request, allowing the user to override any Ajax options.
    var xhr = options.xhr = Backbone.ajax(_.extend(params, options));
    model.trigger('request', model, xhr, options);
    return xhr;
  };

  // Map from CRUD to HTTP for our default `Backbone.sync` implementation.
  var methodMap = {
    'create': 'POST',
    'update': 'PUT',
    'patch': 'PATCH',
    'delete': 'DELETE',
    'read': 'GET'
  };

  // Set the default implementation of `Backbone.ajax` to proxy through to `$`.
  // Override this if you'd like to use a different library.
  Backbone.ajax = function() {
    return Backbone.$.ajax.apply(Backbone.$, arguments);
  };

  // Backbone.Router
  // ---------------

  // Routers map faux-URLs to actions, and fire events when routes are
  // matched. Creating a new one sets its `routes` hash, if not set statically.
  var Router = Backbone.Router = function(options) {
    options || (options = {});
    if (options.routes) this.routes = options.routes;
    this._bindRoutes();
    this.initialize.apply(this, arguments);
  };

  // Cached regular expressions for matching named param parts and splatted
  // parts of route strings.
  var optionalParam = /\((.*?)\)/g;
  var namedParam    = /(\(\?)?:\w+/g;
  var splatParam    = /\*\w+/g;
  var escapeRegExp  = /[\-{}\[\]+?.,\\\^$|#\s]/g;

  // Set up all inheritable **Backbone.Router** properties and methods.
  _.extend(Router.prototype, Events, {

    // Initialize is an empty function by default. Override it with your own
    // initialization logic.
    initialize: function(){},

    // Manually bind a single named route to a callback. For example:
    //
    //     this.route('search/:query/p:num', 'search', function(query, num) {
    //       ...
    //     });
    //
    route: function(route, name, callback) {
      if (!_.isRegExp(route)) route = this._routeToRegExp(route);
      if (_.isFunction(name)) {
        callback = name;
        name = '';
      }
      if (!callback) callback = this[name];
      var router = this;
      Backbone.history.route(route, function(fragment) {
        var args = router._extractParameters(route, fragment);
        if (router.execute(callback, args, name) !== false) {
          router.trigger.apply(router, ['route:' + name].concat(args));
          router.trigger('route', name, args);
          Backbone.history.trigger('route', router, name, args);
        }
      });
      return this;
    },

    // Execute a route handler with the provided parameters.  This is an
    // excellent place to do pre-route setup or post-route cleanup.
    execute: function(callback, args, name) {
      if (callback) callback.apply(this, args);
    },

    // Simple proxy to `Backbone.history` to save a fragment into the history.
    navigate: function(fragment, options) {
      Backbone.history.navigate(fragment, options);
      return this;
    },

    // Bind all defined routes to `Backbone.history`. We have to reverse the
    // order of the routes here to support behavior where the most general
    // routes can be defined at the bottom of the route map.
    _bindRoutes: function() {
      if (!this.routes) return;
      this.routes = _.result(this, 'routes');
      var route, routes = _.keys(this.routes);
      while ((route = routes.pop()) != null) {
        this.route(route, this.routes[route]);
      }
    },

    // Convert a route string into a regular expression, suitable for matching
    // against the current location hash.
    _routeToRegExp: function(route) {
      route = route.replace(escapeRegExp, '\\$&')
                   .replace(optionalParam, '(?:$1)?')
                   .replace(namedParam, function(match, optional) {
                     return optional ? match : '([^/?]+)';
                   })
                   .replace(splatParam, '([^?]*?)');
      return new RegExp('^' + route + '(?:\\?([\\s\\S]*))?$');
    },

    // Given a route, and a URL fragment that it matches, return the array of
    // extracted decoded parameters. Empty or unmatched parameters will be
    // treated as `null` to normalize cross-browser behavior.
    _extractParameters: function(route, fragment) {
      var params = route.exec(fragment).slice(1);
      return _.map(params, function(param, i) {
        // Don't decode the search params.
        if (i === params.length - 1) return param || null;
        return param ? decodeURIComponent(param) : null;
      });
    }

  });

  // Backbone.History
  // ----------------

  // Handles cross-browser history management, based on either
  // [pushState](http://diveintohtml5.info/history.html) and real URLs, or
  // [onhashchange](https://developer.mozilla.org/en-US/docs/DOM/window.onhashchange)
  // and URL fragments. If the browser supports neither (old IE, natch),
  // falls back to polling.
  var History = Backbone.History = function() {
    this.handlers = [];
    this.checkUrl = _.bind(this.checkUrl, this);

    // Ensure that `History` can be used outside of the browser.
    if (typeof window !== 'undefined') {
      this.location = window.location;
      this.history = window.history;
    }
  };

  // Cached regex for stripping a leading hash/slash and trailing space.
  var routeStripper = /^[#\/]|\s+$/g;

  // Cached regex for stripping leading and trailing slashes.
  var rootStripper = /^\/+|\/+$/g;

  // Cached regex for stripping urls of hash.
  var pathStripper = /#.*$/;

  // Has the history handling already been started?
  History.started = false;

  // Set up all inheritable **Backbone.History** properties and methods.
  _.extend(History.prototype, Events, {

    // The default interval to poll for hash changes, if necessary, is
    // twenty times a second.
    interval: 50,

    // Are we at the app root?
    atRoot: function() {
      var path = this.location.pathname.replace(/[^\/]$/, '$&/');
      return path === this.root && !this.getSearch();
    },

    // Does the pathname match the root?
    matchRoot: function() {
      var path = this.decodeFragment(this.location.pathname);
      var rootPath = path.slice(0, this.root.length - 1) + '/';
      return rootPath === this.root;
    },

    // Unicode characters in `location.pathname` are percent encoded so they're
    // decoded for comparison. `%25` should not be decoded since it may be part
    // of an encoded parameter.
    decodeFragment: function(fragment) {
      return decodeURI(fragment.replace(/%25/g, '%2525'));
    },

    // In IE6, the hash fragment and search params are incorrect if the
    // fragment contains `?`.
    getSearch: function() {
      var match = this.location.href.replace(/#.*/, '').match(/\?.+/);
      return match ? match[0] : '';
    },

    // Gets the true hash value. Cannot use location.hash directly due to bug
    // in Firefox where location.hash will always be decoded.
    getHash: function(window) {
      var match = (window || this).location.href.match(/#(.*)$/);
      return match ? match[1] : '';
    },

    // Get the pathname and search params, without the root.
    getPath: function() {
      var path = this.decodeFragment(
        this.location.pathname + this.getSearch()
      ).slice(this.root.length - 1);
      return path.charAt(0) === '/' ? path.slice(1) : path;
    },

    // Get the cross-browser normalized URL fragment from the path or hash.
    getFragment: function(fragment) {
      if (fragment == null) {
        if (this._usePushState || !this._wantsHashChange) {
          fragment = this.getPath();
        } else {
          fragment = this.getHash();
        }
      }
      return fragment.replace(routeStripper, '');
    },

    // Start the hash change handling, returning `true` if the current URL matches
    // an existing route, and `false` otherwise.
    start: function(options) {
      if (History.started) throw new Error('Backbone.history has already been started');
      History.started = true;

      // Figure out the initial configuration. Do we need an iframe?
      // Is pushState desired ... is it available?
      this.options          = _.extend({root: '/'}, this.options, options);
      this.root             = this.options.root;
      this._wantsHashChange = this.options.hashChange !== false;
      this._hasHashChange   = 'onhashchange' in window && (document.documentMode === void 0 || document.documentMode > 7);
      this._useHashChange   = this._wantsHashChange && this._hasHashChange;
      this._wantsPushState  = !!this.options.pushState;
      this._hasPushState    = !!(this.history && this.history.pushState);
      this._usePushState    = this._wantsPushState && this._hasPushState;
      this.fragment         = this.getFragment();

      // Normalize root to always include a leading and trailing slash.
      this.root = ('/' + this.root + '/').replace(rootStripper, '/');

      // Transition from hashChange to pushState or vice versa if both are
      // requested.
      if (this._wantsHashChange && this._wantsPushState) {

        // If we've started off with a route from a `pushState`-enabled
        // browser, but we're currently in a browser that doesn't support it...
        if (!this._hasPushState && !this.atRoot()) {
          var rootPath = this.root.slice(0, -1) || '/';
          this.location.replace(rootPath + '#' + this.getPath());
          // Return immediately as browser will do redirect to new url
          return true;

        // Or if we've started out with a hash-based route, but we're currently
        // in a browser where it could be `pushState`-based instead...
        } else if (this._hasPushState && this.atRoot()) {
          this.navigate(this.getHash(), {replace: true});
        }

      }

      // Proxy an iframe to handle location events if the browser doesn't
      // support the `hashchange` event, HTML5 history, or the user wants
      // `hashChange` but not `pushState`.
      if (!this._hasHashChange && this._wantsHashChange && !this._usePushState) {
        this.iframe = document.createElement('iframe');
        this.iframe.src = 'javascript:0';
        this.iframe.style.display = 'none';
        this.iframe.tabIndex = -1;
        var body = document.body;
        // Using `appendChild` will throw on IE < 9 if the document is not ready.
        var iWindow = body.insertBefore(this.iframe, body.firstChild).contentWindow;
        iWindow.document.open();
        iWindow.document.close();
        iWindow.location.hash = '#' + this.fragment;
      }

      // Add a cross-platform `addEventListener` shim for older browsers.
      var addEventListener = window.addEventListener || function(eventName, listener) {
        return attachEvent('on' + eventName, listener);
      };

      // Depending on whether we're using pushState or hashes, and whether
      // 'onhashchange' is supported, determine how we check the URL state.
      if (this._usePushState) {
        addEventListener('popstate', this.checkUrl, false);
      } else if (this._useHashChange && !this.iframe) {
        addEventListener('hashchange', this.checkUrl, false);
      } else if (this._wantsHashChange) {
        this._checkUrlInterval = setInterval(this.checkUrl, this.interval);
      }

      if (!this.options.silent) return this.loadUrl();
    },

    // Disable Backbone.history, perhaps temporarily. Not useful in a real app,
    // but possibly useful for unit testing Routers.
    stop: function() {
      // Add a cross-platform `removeEventListener` shim for older browsers.
      var removeEventListener = window.removeEventListener || function(eventName, listener) {
        return detachEvent('on' + eventName, listener);
      };

      // Remove window listeners.
      if (this._usePushState) {
        removeEventListener('popstate', this.checkUrl, false);
      } else if (this._useHashChange && !this.iframe) {
        removeEventListener('hashchange', this.checkUrl, false);
      }

      // Clean up the iframe if necessary.
      if (this.iframe) {
        document.body.removeChild(this.iframe);
        this.iframe = null;
      }

      // Some environments will throw when clearing an undefined interval.
      if (this._checkUrlInterval) clearInterval(this._checkUrlInterval);
      History.started = false;
    },

    // Add a route to be tested when the fragment changes. Routes added later
    // may override previous routes.
    route: function(route, callback) {
      this.handlers.unshift({route: route, callback: callback});
    },

    // Checks the current URL to see if it has changed, and if it has,
    // calls `loadUrl`, normalizing across the hidden iframe.
    checkUrl: function(e) {
      var current = this.getFragment();

      // If the user pressed the back button, the iframe's hash will have
      // changed and we should use that for comparison.
      if (current === this.fragment && this.iframe) {
        current = this.getHash(this.iframe.contentWindow);
      }

      if (current === this.fragment) return false;
      if (this.iframe) this.navigate(current);
      this.loadUrl();
    },

    // Attempt to load the current URL fragment. If a route succeeds with a
    // match, returns `true`. If no defined routes matches the fragment,
    // returns `false`.
    loadUrl: function(fragment) {
      // If the root doesn't match, no routes can match either.
      if (!this.matchRoot()) return false;
      fragment = this.fragment = this.getFragment(fragment);
      return _.some(this.handlers, function(handler) {
        if (handler.route.test(fragment)) {
          handler.callback(fragment);
          return true;
        }
      });
    },

    // Save a fragment into the hash history, or replace the URL state if the
    // 'replace' option is passed. You are responsible for properly URL-encoding
    // the fragment in advance.
    //
    // The options object can contain `trigger: true` if you wish to have the
    // route callback be fired (not usually desirable), or `replace: true`, if
    // you wish to modify the current URL without adding an entry to the history.
    navigate: function(fragment, options) {
      if (!History.started) return false;
      if (!options || options === true) options = {trigger: !!options};

      // Normalize the fragment.
      fragment = this.getFragment(fragment || '');

      // Don't include a trailing slash on the root.
      var rootPath = this.root;
      if (fragment === '' || fragment.charAt(0) === '?') {
        rootPath = rootPath.slice(0, -1) || '/';
      }
      var url = rootPath + fragment;

      // Strip the hash and decode for matching.
      fragment = this.decodeFragment(fragment.replace(pathStripper, ''));

      if (this.fragment === fragment) return;
      this.fragment = fragment;

      // If pushState is available, we use it to set the fragment as a real URL.
      if (this._usePushState) {
        this.history[options.replace ? 'replaceState' : 'pushState']({}, document.title, url);

      // If hash changes haven't been explicitly disabled, update the hash
      // fragment to store history.
      } else if (this._wantsHashChange) {
        this._updateHash(this.location, fragment, options.replace);
        if (this.iframe && fragment !== this.getHash(this.iframe.contentWindow)) {
          var iWindow = this.iframe.contentWindow;

          // Opening and closing the iframe tricks IE7 and earlier to push a
          // history entry on hash-tag change.  When replace is true, we don't
          // want this.
          if (!options.replace) {
            iWindow.document.open();
            iWindow.document.close();
          }

          this._updateHash(iWindow.location, fragment, options.replace);
        }

      // If you've told us that you explicitly don't want fallback hashchange-
      // based history, then `navigate` becomes a page refresh.
      } else {
        return this.location.assign(url);
      }
      if (options.trigger) return this.loadUrl(fragment);
    },

    // Update the hash location, either replacing the current entry, or adding
    // a new one to the browser history.
    _updateHash: function(location, fragment, replace) {
      if (replace) {
        var href = location.href.replace(/(javascript:|#).*$/, '');
        location.replace(href + '#' + fragment);
      } else {
        // Some browsers require that `hash` contains a leading #.
        location.hash = '#' + fragment;
      }
    }

  });

  // Create the default Backbone.history.
  Backbone.history = new History;

  // Helpers
  // -------

  // Helper function to correctly set up the prototype chain for subclasses.
  // Similar to `goog.inherits`, but uses a hash of prototype properties and
  // class properties to be extended.
  var extend = function(protoProps, staticProps) {
    var parent = this;
    var child;

    // The constructor function for the new subclass is either defined by you
    // (the "constructor" property in your `extend` definition), or defaulted
    // by us to simply call the parent constructor.
    if (protoProps && _.has(protoProps, 'constructor')) {
      child = protoProps.constructor;
    } else {
      child = function(){ return parent.apply(this, arguments); };
    }

    // Add static properties to the constructor function, if supplied.
    _.extend(child, parent, staticProps);

    // Set the prototype chain to inherit from `parent`, without calling
    // `parent`'s constructor function and add the prototype properties.
    child.prototype = _.create(parent.prototype, protoProps);
    child.prototype.constructor = child;

    // Set a convenience property in case the parent's prototype is needed
    // later.
    child.__super__ = parent.prototype;

    return child;
  };

  // Set up inheritance for the model, collection, router, view and history.
  Model.extend = Collection.extend = Router.extend = View.extend = History.extend = extend;

  // Throw an error when a URL is needed, and none is supplied.
  var urlError = function() {
    throw new Error('A "url" property or function must be specified');
  };

  // Wrap an optional error callback with a fallback error event.
  var wrapError = function(model, options) {
    var error = options.error;
    options.error = function(resp) {
      if (error) error.call(options.context, model, resp, options);
      model.trigger('error', model, resp, options);
    };
  };

  return Backbone;
});

/* WEBPACK VAR INJECTION */}.call(exports, __webpack_require__(13)))

/***/ }),
/* 7 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function() {
	'use strict';

	return window.OC || {};
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));



/***/ }),
/* 8 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015, 2016
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var _ = __webpack_require__(3);
	var $ = __webpack_require__(5);
	var storage = $.sessionStorage;
	var Account = __webpack_require__(18);

	var MessageCache = {
		/**
		 * @param {Account} account
		 * @returns {string}
		 */
		getAccountPath: function(account) {
			return ['messages', account.get('accountId').toString()].join('.');
		},
		/**
		 * @param {Account} account
		 * @param {Folder} folder
		 * @returns {string}
		 */
		getFolderPath: function(account, folder) {
			return [this.getAccountPath(account), folder.get('id').toString()].join('.');
		},
		/**
		 * @param {Account} account
		 * @param {Folder} folder
		 * @param {number} messageId
		 * @returns {string}
		 */
		getMessagePath: function(account, folder, messageId) {
			return [this.getFolderPath(account, folder), messageId.toString()].join('.');
		}
	};

	function init() {
		console.log('initializing cache…');
		var installedVersion = $('#config-installed-version').val();
		if (storage.isSet('mail-app-version')) {
			var cachedVersion = storage.get('mail-app-version');
			if (cachedVersion !== installedVersion) {
				console.log('clearing cache because app version has changed');
				storage.removeAll();
			}
		} else {
			// Could be an old version -> clear data
			storage.removeAll();
		}
		storage.set('mail-app-version', installedVersion);
	}

	/**
	 * @param {AccountsCollection} accounts
	 * @returns {undefined}
	 */
	function cleanUp(accounts) {
		var activeAccounts = accounts.map(function(account) {
			return account.get('accountId');
		});
		_.each(storage.get('messages'), function(account, accountId) {
			var isActive = _.any(activeAccounts, function(a) {
				return a === parseInt(accountId);
			});
			if (!isActive) {
				// Account does not exist anymore -> remove it
				storage.remove('messages.' + accountId);
			}
		});
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {Message} messageId
	 * @returns {unresolved}
	 */
	function getMessage(account, folder, messageId) {
		var path = MessageCache.getMessagePath(account, folder, messageId);
		if (storage.isSet(path)) {
			var message = storage.get(path);
			// Update the timestamp
			addMessage(account, folder, message);
			return message;
		} else {
			return null;
		}
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {Message} message
	 * @returns {undefined}
	 */
	function addMessage(account, folder, message) {
		var path = MessageCache.getMessagePath(account, folder, message.id);
		// Save the message to local storage
		storage.set(path, message);
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {Message} messages
	 * @returns {undefined}
	 */
	function addMessages(account, folder, messages) {
		_.each(messages, function(message) {
			addMessage(account, folder, message);
		});
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {number} messageId
	 * @returns {undefined}
	 */
	function removeMessage(account, folder, messageId) {
		var message = getMessage(account, folder, messageId);
		if (message) {
			// message exists in cache -> remove it
			storage.remove(MessageCache.getMessagePath(account, folder, messageId));
		}
	}

	/**
	 * @param {Account} account
	 * @returns {undefined}
	 */
	function removeAccount(account) {
		// Remove cached messages
		var path = MessageCache.getAccountPath(account);
		if (storage.isSet(path)) {
			storage.remove(path);
		}

		// Unified inbox hack
		if (account.get('accountId') !== -1) {
			// Make sure unified inbox cache is cleared to prevent
			// old message showing up on the next load
			removeAccount(new Account({accountId: -1}));
		}
		// End unified inbox hack
	}

	return {
		init: init,
		cleanUp: cleanUp,
		getMessage: getMessage,
		addMessage: addMessage,
		addMessages: addMessages,
		removeMessage: removeMessage,
		removeAccount: removeAccount
	};
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 9 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
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

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var $ = __webpack_require__(5);
	var _ = __webpack_require__(3);
	var OC = __webpack_require__(7);
	var Radio = __webpack_require__(1);
	var ErrorMessageFactory = __webpack_require__(21);

	Radio.message.on('load', load);
	Radio.message.on('forward', openForwardComposer);
	Radio.message.on('flag', flagMessage);
	Radio.message.on('move', moveMessage);

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {Message} message
	 * @param {object} options
	 * @returns {undefined}
	 */
	function load(account, folder, message, options) {
		options = options || {};
		var defaultOptions = {
			force: false
		};
		_.defaults(options, defaultOptions);

		// Do not reload email when clicking same again
		if (__webpack_require__(0).currentMessage && __webpack_require__(0).currentMessage.get('id') === message.get('id')) {
			return;
		}

		Radio.ui.trigger('composer:leave');

		// TODO: expression is useless?
		if (!options.force && false) {
			return;
		}

		// check if message is a draft
		var draft = __webpack_require__(0).currentFolder.get('specialRole') === 'drafts';

		// close email first
		// Check if message is open
		if (__webpack_require__(0).currentMessage !== null) {
			var lastMessage = __webpack_require__(0).currentMessage;
			Radio.ui.trigger('messagesview:message:setactive', null);
			if (lastMessage.get('id') === message.get('id')) {
				return;
			}
		}

		Radio.ui.trigger('message:loading');

		// Set current Message as active
		Radio.ui.trigger('messagesview:message:setactive', message);
		__webpack_require__(0).currentMessageBody = '';

		// Fade out the message composer
		$('#mail_new_message').prop('disabled', false);

		Radio.message.request('entity', account, folder, message.get('id')).then(function(messageBody) {
			if (draft) {
				Radio.ui.trigger('composer:show', messageBody);
			} else {
				// TODO: ideally this should be handled in messageservice.js
				__webpack_require__(8).addMessage(account, folder, messageBody);
				Radio.ui.trigger('message:show', message, messageBody);
			}
		}, function() {
			Radio.ui.trigger('message:error', ErrorMessageFactory.getRandomMessageErrorMessage());
		});
	}

	/**
	 * @returns {undefined}
	 */
	function openForwardComposer() {
		var header = '\n\n\n\n-------- ' +
			t('mail', 'Forwarded message') +
			' --------\n';

		// TODO: find a better way to get the current message body
		var data = {
			subject: 'Fwd: ' + __webpack_require__(0).currentMessageSubject,
			body: header + __webpack_require__(0).currentMessageBody.replace(/<br \/>/g, '\n')
		};

		if (__webpack_require__(0).currentAccount.get('accountId') !== -1) {
			data.accountId = __webpack_require__(0).currentAccount.get('accountId');
		}

		Radio.ui.trigger('composer:show', data);
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {number} messageId
	 * @param {number} attachmentId
	 * @returns {Promise}
	 */
	function saveAttachmentToFiles(account, folder, messageId, attachmentId) {
		var saveAll = _.isUndefined(attachmentId);

		return new Promise(function(resolve, reject) {
			OC.dialogs.filepicker(
				t('mail', 'Choose a folder to store the attachment in'),
				function(path) {
					Radio.message.request('save:cloud', account,
						folder, messageId, attachmentId, path).then(function() {
						if (saveAll) {
							Radio.ui.trigger('error:show', t('mail', 'Attachments saved to Files.'));
						} else {
							Radio.ui.trigger('error:show', t('mail', 'Attachment saved to Files.'));
						}
						resolve();
					}, function() {
						if (saveAll) {
							Radio.ui.trigger('error:show', t('mail', 'Error while saving attachments to Files.'));
						} else {
							Radio.ui.trigger('error:show', t('mail', 'Error while saving attachment to Files.'));
						}
						reject();
					});
				}, false, 'httpd/unix-directory', true);
		});
	}

	function flagMessage(message, flag, value) {
		var folder = message.folder;
		var account = folder.account;
		var prevUnseen = folder.get('unseen');

		if (message.get('flags').get(flag) === value) {
			// Nothing to do
			return;
		}
		message.get('flags').set(flag, value);

		// Update folder counter
		if (flag === 'unseen') {
			var unseen = Math.max(0, prevUnseen + (value ? 1 : -1));
			folder.set('unseen', unseen);
		}

		// Update the folder to reflect the new unread count
		Radio.ui.trigger('title:update');

		Radio.message.request('flag', account, folder, message, flag, value).
			catch(function() {
				Radio.ui.trigger('error:show', t('mail', 'Message flag could not be set.'));

				// Restore previous state
				message.get('flags').set(flag, !value);
				folder.set('unseen', prevUnseen);
				Radio.ui.trigger('title:update');
			});
	}

	function moveMessage(sourceAccount, sourceFolder, message, destAccount,
		destFolder) {
		if (sourceAccount.get('accountId') === destAccount.get('accountId')
			&& sourceFolder.get('id') === destFolder.get('id')) {
			// Nothing to move
			return;
		}

		sourceFolder.messages.remove(message);
		destFolder.addMessage(message);

		Radio.message.request('move', sourceAccount, sourceFolder, message, destAccount, destFolder).
			then(function() {
				// TODO: update counters
			}, function() {
				Radio.ui.trigger('error:show', t('mail', 'Could not move message.'));
				sourceFolder.addMessage(message);
			});
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {number} messageId
	 * @returns {Promise}
	 */
	function saveAttachmentsToFiles(account, folder, messageId) {
		return saveAttachmentToFiles(account, folder, messageId);
	}

	return {
		saveAttachmentToFiles: saveAttachmentToFiles,
		saveAttachmentsToFiles: saveAttachmentsToFiles
	};
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 10 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
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

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Handlebars = __webpack_require__(4);
	var Marionette = __webpack_require__(2);
	var ErrorTemplate = __webpack_require__(62);

	var ErrorView = Marionette.View.extend({

		id: 'emptycontent',

		className: 'container',

		template: Handlebars.compile(ErrorTemplate),

		_text: undefined,

		_icon: undefined,

		_canRetry: undefined,

		events: {
			'click .retry': '_onRetry'
		},

		templateContext: function() {
			return {
				text: this._text,
				icon: this._icon,
				canRetry: this._canRetry
			};
		},

		initialize: function(options) {
			this._text = options.text || t('mail', 'An unknown error occurred');
			this._icon = options.icon || 'icon-mail';
			this._canRetry = options.canRetry || false;
		},

		_onRetry: function() {
			this.triggerMethod('retry');
		}
	});

	return ErrorView;
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 11 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
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

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Handlebars = __webpack_require__(4);
	var Marionette = __webpack_require__(2);
	var LoadingTemplate = __webpack_require__(63);

	/**
	 * @class LoadingView
	 */
	var LoadingView = Marionette.View.extend({
		template: Handlebars.compile(LoadingTemplate),
		templateContext: function() {
			return {
				hint: this.hint
			};
		},
		className: 'container',
		hint: '',
		initialize: function(options) {
			this.hint = options.text || '';
		}
	});

	return LoadingView;
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 12 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/* global Promise */

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

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var $ = __webpack_require__(5);
	var _ = __webpack_require__(3);
	var Radio = __webpack_require__(1);
	var ErrorMessageFactory = __webpack_require__(21);

	Radio.message.on('fetch:bodies', fetchBodies);
	Radio.folder.reply('message:delete', deleteMessage);

	/**
	 * @param {Account} account
	 * @returns {Promise}
	 */
	function loadFolders(account) {
		return Radio.folder.request('entities', account)
			.catch(function() {
				Radio.ui.trigger('error:show', t('mail', 'Error while loading the selected account.'));
			});
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {string} searchQuery
	 * @param {bool} openFirstMessage
	 * @returns {Promise}
	 */
	function loadFolderMessages(account, folder, searchQuery, openFirstMessage) {
		openFirstMessage = openFirstMessage !== false;
		Radio.ui.trigger('composer:leave');

		// Set folder active
		Radio.folder.trigger('setactive', account, folder);

		if (folder.get('noSelect')) {
			Radio.ui.trigger('content:error', t('mail', 'Can not load this folder.'));
			__webpack_require__(0).currentAccount = account;
			__webpack_require__(0).currentFolder = folder;
			Radio.ui.trigger('messagesview:message:setactive', null);
			__webpack_require__(0).currentlyLoading = null;
			return Promise.resolve();
		} else {
			return Radio.message.request('entities', account, folder, {
				cache: true,
				filter: searchQuery,
				replace: true
			}).then(function(messages) {
				Radio.ui.trigger('foldercontent:show', account, folder, {
					searchQuery: searchQuery
				});
				__webpack_require__(0).currentlyLoading = null;
				__webpack_require__(0).currentAccount = account;
				__webpack_require__(0).currentFolder = folder;
				Radio.ui.trigger('messagesview:message:setactive', null);

				// Fade out the message composer
				$('#mail_new_message').prop('disabled', false);

				if (messages.length > 0) {
					// Fetch first 10 messages in background
					Radio.message.trigger('fetch:bodies', account, folder, messages.slice(0, 10));
					if (openFirstMessage) {
						var message = messages.first();
						Radio.message.trigger('load', message.folder.account, message.folder, message);
					}
				}
			}, function(error) {
				console.error('error while loading messages: ', error);
				var icon;
				if (folder.get('specialRole')) {
					icon = 'icon-' + folder.get('specialRole');
				}
				Radio.ui.trigger('content:error', ErrorMessageFactory.getRandomFolderErrorMessage(folder), icon);

				// Set the old folder as being active
				var oldFolder = __webpack_require__(0).currentFolder;
				Radio.folder.trigger('setactive', account, oldFolder);
			}).catch(console.error.bind(this));
		}
	}

	var loadFolderMessagesDebounced = _.debounce(loadFolderMessages, 1000);

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {bool} loadFirstMessage
	 * @returns {Promise}
	 */
	function showFolder(account, folder, loadFirstMessage) {
		loadFirstMessage = loadFirstMessage !== false;
		Radio.ui.trigger('search:set', '');
		Radio.ui.trigger('content:loading', t('mail', 'Loading {folder}', {
			folder: folder.get('name')
		}));

		return new Promise(function(resolve, reject) {
			_.defer(function() {
				var loading = loadFolderMessages(account, folder, undefined, loadFirstMessage);

				// Save current folder
				Radio.folder.trigger('setactive', account, folder);
				__webpack_require__(0).currentAccount = account;
				__webpack_require__(0).currentFolder = folder;

				loading.then(resolve).catch(reject);
			});
		});
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {string} query
	 * @returns {Promise}
	 */
	function searchFolder(account, folder, query) {
		// If this was triggered by a URL change, we set the search input manually
		Radio.ui.trigger('search:set', query);

		Radio.ui.trigger('composer:leave');
		Radio.ui.trigger('content:loading', t('mail', 'Searching for {query}', {
			query: query
		}));
		_.defer(function() {
			loadFolderMessagesDebounced(account, folder, query);
		});
	}

	/**
	 * Fetch and cache messages in the background
	 *
	 * The message is only fetched if it has not been cached already
	 *
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {Message[]} messages
	 * @returns {undefined}
	 */
	function fetchBodies(account, folder, messages) {
		if (messages.length > 0) {
			var ids = _.map(messages, function(message) {
				return message.get('id');
			});
			Radio.message.request('bodies', account, folder, ids).
				then(function(messages) {
					__webpack_require__(8).addMessages(account, folder, messages);
				}, console.error.bind(this));
		}
	}

	/**
	 * @param {Folder} folder
	 * @param {Folder} currentFolder
	 * @returns {Array} array of two folders, the first one is the individual
	 */
	function getSpecificAndUnifiedFolder(folder, currentFolder) {
		// Case 1: we're currently in a unified folder
		if (currentFolder.account.get('accountId') === -1) {
			return [folder, currentFolder];
		}

		// Locate unified folder if existent
		var unifiedAccount = __webpack_require__(0).accounts.get(-1);
		var unifiedFolder = unifiedAccount ? unifiedAccount.folders.first() : null;

		// Case 2: we're in a specific folder and a unified one is available too
		if (currentFolder.get('specialRole') === 'inbox' && unifiedFolder) {
			return [folder, unifiedFolder];
		}

		// Case 3: we're in a specific folder, but there's no unified one
		return [folder, null];
	}

	/**
	 * Call supplied function with folder as first parameter, if
	 * the folder is not undefined
	 *
	 * @param {Array<Folder>} folders
	 * @param {Function} fn
	 * @returns {mixed}
	 */
	function applyOnFolders(folders, fn) {
		folders.forEach(function(folder) {
			if (!folder) {
				// ignore
				return;
			}

			return fn(folder);
		});
	}

	/**
	 * @param {Message} message
	 * @param {Folder} currentFolder
	 * @returns {Promise}
	 */
	function deleteMessage(message, currentFolder) {
		var folders = getSpecificAndUnifiedFolder(message.folder, currentFolder);

		applyOnFolders(folders, function(folder) {
			// Update total counter and prevent negative values
			folder.set('total', Math.max(0, folder.get('total')));
		});

		var searchCollection = currentFolder.messages;
		var index = searchCollection.indexOf(message);
		// Select previous or first
		if (index === 0) {
			index = 1;
		} else {
			index = index - 1;
		}
		var nextMessage = searchCollection.at(index);

		// Remove message
		applyOnFolders(folders, function(folder) {
			folder.messages.remove(message);
		});

		if (__webpack_require__(0).currentMessage && __webpack_require__(0).currentMessage.get('id') === message.id) {
			if (nextMessage) {
				Radio.message.trigger('load', message.folder.account, message.folder, nextMessage);
			}
		}

		return Radio.message.request('delete', message)
			.catch(function(err) {
				console.error(err);

				Radio.ui.trigger('error:show', t('mail', 'Error while deleting message.'));

				applyOnFolders(folders, function(folder) {
					// Restore counter

					folder.set('total', folder.previousAttributes.total);

					// Add the message to the collection again
					folder.addMessage(message);
				});
			});
	}

	return {
		loadAccountFolders: loadFolders,
		showFolder: showFolder,
		searchFolder: searchFolder
	};
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 13 */
/***/ (function(module, exports) {

var g;

// This works in non-strict mode
g = (function() {
	return this;
})();

try {
	// This works if eval is allowed (see CSP)
	g = g || Function("return this")() || (1,eval)("this");
} catch(e) {
	// This works if the window reference is available
	if(typeof window === "object")
		g = window;
}

// g can still be undefined, but nothing to do about it...
// We return undefined, instead of nothing here, so it's
// easier to handle this case. if(!global) { ...}

module.exports = g;


/***/ }),
/* 14 */
/***/ (function(module, exports, __webpack_require__) {

// Backbone.Radio v2.0.0

(function (global, factory) {
   true ? module.exports = factory(__webpack_require__(3), __webpack_require__(6)) :
  typeof define === 'function' && define.amd ? define(['underscore', 'backbone'], factory) :
  (global.Backbone = global.Backbone || {}, global.Backbone.Radio = factory(global._,global.Backbone));
}(this, function (_,Backbone) { 'use strict';

  _ = 'default' in _ ? _['default'] : _;
  Backbone = 'default' in Backbone ? Backbone['default'] : Backbone;

  var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) {
    return typeof obj;
  } : function (obj) {
    return obj && typeof Symbol === "function" && obj.constructor === Symbol ? "symbol" : typeof obj;
  };

  var previousRadio = Backbone.Radio;

  var Radio = Backbone.Radio = {};

  Radio.VERSION = '2.0.0';

  // This allows you to run multiple instances of Radio on the same
  // webapp. After loading the new version, call `noConflict()` to
  // get a reference to it. At the same time the old version will be
  // returned to Backbone.Radio.
  Radio.noConflict = function () {
    Backbone.Radio = previousRadio;
    return this;
  };

  // Whether or not we're in DEBUG mode or not. DEBUG mode helps you
  // get around the issues of lack of warnings when events are mis-typed.
  Radio.DEBUG = false;

  // Format debug text.
  Radio._debugText = function (warning, eventName, channelName) {
    return warning + (channelName ? ' on the ' + channelName + ' channel' : '') + ': "' + eventName + '"';
  };

  // This is the method that's called when an unregistered event was called.
  // By default, it logs warning to the console. By overriding this you could
  // make it throw an Error, for instance. This would make firing a nonexistent event
  // have the same consequence as firing a nonexistent method on an Object.
  Radio.debugLog = function (warning, eventName, channelName) {
    if (Radio.DEBUG && console && console.warn) {
      console.warn(Radio._debugText(warning, eventName, channelName));
    }
  };

  var eventSplitter = /\s+/;

  // An internal method used to handle Radio's method overloading for Requests.
  // It's borrowed from Backbone.Events. It differs from Backbone's overload
  // API (which is used in Backbone.Events) in that it doesn't support space-separated
  // event names.
  Radio._eventsApi = function (obj, action, name, rest) {
    if (!name) {
      return false;
    }

    var results = {};

    // Handle event maps.
    if ((typeof name === 'undefined' ? 'undefined' : _typeof(name)) === 'object') {
      for (var key in name) {
        var result = obj[action].apply(obj, [key, name[key]].concat(rest));
        eventSplitter.test(key) ? _.extend(results, result) : results[key] = result;
      }
      return results;
    }

    // Handle space separated event names.
    if (eventSplitter.test(name)) {
      var names = name.split(eventSplitter);
      for (var i = 0, l = names.length; i < l; i++) {
        results[names[i]] = obj[action].apply(obj, [names[i]].concat(rest));
      }
      return results;
    }

    return false;
  };

  // An optimized way to execute callbacks.
  Radio._callHandler = function (callback, context, args) {
    var a1 = args[0],
        a2 = args[1],
        a3 = args[2];
    switch (args.length) {
      case 0:
        return callback.call(context);
      case 1:
        return callback.call(context, a1);
      case 2:
        return callback.call(context, a1, a2);
      case 3:
        return callback.call(context, a1, a2, a3);
      default:
        return callback.apply(context, args);
    }
  };

  // A helper used by `off` methods to the handler from the store
  function removeHandler(store, name, callback, context) {
    var event = store[name];
    if ((!callback || callback === event.callback || callback === event.callback._callback) && (!context || context === event.context)) {
      delete store[name];
      return true;
    }
  }

  function removeHandlers(store, name, callback, context) {
    store || (store = {});
    var names = name ? [name] : _.keys(store);
    var matched = false;

    for (var i = 0, length = names.length; i < length; i++) {
      name = names[i];

      // If there's no event by this name, log it and continue
      // with the loop
      if (!store[name]) {
        continue;
      }

      if (removeHandler(store, name, callback, context)) {
        matched = true;
      }
    }

    return matched;
  }

  /*
   * tune-in
   * -------
   * Get console logs of a channel's activity
   *
   */

  var _logs = {};

  // This is to produce an identical function in both tuneIn and tuneOut,
  // so that Backbone.Events unregisters it.
  function _partial(channelName) {
    return _logs[channelName] || (_logs[channelName] = _.bind(Radio.log, Radio, channelName));
  }

  _.extend(Radio, {

    // Log information about the channel and event
    log: function log(channelName, eventName) {
      if (typeof console === 'undefined') {
        return;
      }
      var args = _.toArray(arguments).slice(2);
      console.log('[' + channelName + '] "' + eventName + '"', args);
    },

    // Logs all events on this channel to the console. It sets an
    // internal value on the channel telling it we're listening,
    // then sets a listener on the Backbone.Events
    tuneIn: function tuneIn(channelName) {
      var channel = Radio.channel(channelName);
      channel._tunedIn = true;
      channel.on('all', _partial(channelName));
      return this;
    },

    // Stop logging all of the activities on this channel to the console
    tuneOut: function tuneOut(channelName) {
      var channel = Radio.channel(channelName);
      channel._tunedIn = false;
      channel.off('all', _partial(channelName));
      delete _logs[channelName];
      return this;
    }
  });

  /*
   * Backbone.Radio.Requests
   * -----------------------
   * A messaging system for requesting data.
   *
   */

  function makeCallback(callback) {
    return _.isFunction(callback) ? callback : function () {
      return callback;
    };
  }

  Radio.Requests = {

    // Make a request
    request: function request(name) {
      var args = _.toArray(arguments).slice(1);
      var results = Radio._eventsApi(this, 'request', name, args);
      if (results) {
        return results;
      }
      var channelName = this.channelName;
      var requests = this._requests;

      // Check if we should log the request, and if so, do it
      if (channelName && this._tunedIn) {
        Radio.log.apply(this, [channelName, name].concat(args));
      }

      // If the request isn't handled, log it in DEBUG mode and exit
      if (requests && (requests[name] || requests['default'])) {
        var handler = requests[name] || requests['default'];
        args = requests[name] ? args : arguments;
        return Radio._callHandler(handler.callback, handler.context, args);
      } else {
        Radio.debugLog('An unhandled request was fired', name, channelName);
      }
    },

    // Set up a handler for a request
    reply: function reply(name, callback, context) {
      if (Radio._eventsApi(this, 'reply', name, [callback, context])) {
        return this;
      }

      this._requests || (this._requests = {});

      if (this._requests[name]) {
        Radio.debugLog('A request was overwritten', name, this.channelName);
      }

      this._requests[name] = {
        callback: makeCallback(callback),
        context: context || this
      };

      return this;
    },

    // Set up a handler that can only be requested once
    replyOnce: function replyOnce(name, callback, context) {
      if (Radio._eventsApi(this, 'replyOnce', name, [callback, context])) {
        return this;
      }

      var self = this;

      var once = _.once(function () {
        self.stopReplying(name);
        return makeCallback(callback).apply(this, arguments);
      });

      return this.reply(name, once, context);
    },

    // Remove handler(s)
    stopReplying: function stopReplying(name, callback, context) {
      if (Radio._eventsApi(this, 'stopReplying', name)) {
        return this;
      }

      // Remove everything if there are no arguments passed
      if (!name && !callback && !context) {
        delete this._requests;
      } else if (!removeHandlers(this._requests, name, callback, context)) {
        Radio.debugLog('Attempted to remove the unregistered request', name, this.channelName);
      }

      return this;
    }
  };

  /*
   * Backbone.Radio.channel
   * ----------------------
   * Get a reference to a channel by name.
   *
   */

  Radio._channels = {};

  Radio.channel = function (channelName) {
    if (!channelName) {
      throw new Error('You must provide a name for the channel.');
    }

    if (Radio._channels[channelName]) {
      return Radio._channels[channelName];
    } else {
      return Radio._channels[channelName] = new Radio.Channel(channelName);
    }
  };

  /*
   * Backbone.Radio.Channel
   * ----------------------
   * A Channel is an object that extends from Backbone.Events,
   * and Radio.Requests.
   *
   */

  Radio.Channel = function (channelName) {
    this.channelName = channelName;
  };

  _.extend(Radio.Channel.prototype, Backbone.Events, Radio.Requests, {

    // Remove all handlers from the messaging systems of this channel
    reset: function reset() {
      this.off();
      this.stopListening();
      this.stopReplying();
      return this;
    }
  });

  /*
   * Top-level API
   * -------------
   * Supplies the 'top-level API' for working with Channels directly
   * from Backbone.Radio.
   *
   */

  var channel;
  var args;
  var systems = [Backbone.Events, Radio.Requests];
  _.each(systems, function (system) {
    _.each(system, function (method, methodName) {
      Radio[methodName] = function (channelName) {
        args = _.toArray(arguments).slice(1);
        channel = this.channel(channelName);
        return channel[methodName].apply(channel, args);
      };
    });
  });

  Radio.reset = function (channelName) {
    var channels = !channelName ? this._channels : [this._channels[channelName]];
    _.each(channels, function (channel) {
      channel.reset();
    });
  };

  return Radio;

}));
//# sourceMappingURL=./backbone.radio.js.map

/***/ }),
/* 15 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2017
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Marionette = __webpack_require__(2);
	var Handlebars = __webpack_require__(4);
	var $ = __webpack_require__(5);
	var _ = __webpack_require__(3);
	var OC = __webpack_require__(7);
	var Radio = __webpack_require__(1);
	var Attachments = __webpack_require__(16);
	var AttachmentsView = __webpack_require__(31);
	var ComposerTemplate = __webpack_require__(36);

	return Marionette.View.extend({
		template: Handlebars.compile(ComposerTemplate),
		templateContext: function() {
			var aliases = null;
			if (this.accounts) {
				aliases = this.buildAliases();
				aliases = _.filter(aliases, function(alias) {
					return alias.accountId !== -1;
				});
			}

			return {
				aliases: aliases,
				isReply: this.isReply(),
				to: this.data.to,
				cc: this.data.cc,
				subject: this.data.subject,
				message: this.data.body,
				submitButtonTitle: this.isReply() ? t('mail', 'Reply') : t('mail', 'Send'),
				// Reply data
				replyToList: this.data.replyToList,
				replyCc: this.data.replyCc,
				replyCcList: this.data.replyCcList
			};
		},
		type: 'new',
		data: null,
		attachments: null,
		accounts: null,
		aliases: null,
		account: null,
		folder: null,
		repliedMessage: null,
		draftInterval: 1500,
		draftTimer: null,
		draftUID: null,
		hasData: false,
		autosized: false,
		regions: {
			attachmentsRegion: '.new-message-attachments'
		},
		events: {
			'click .submit-message': 'submitMessage',
			'click .submit-message-wrapper-inside': 'submitMessageWrapperInside',
			'keypress .message-body': 'handleKeyPress',
			'input  .to': 'onInputChanged',
			'paste  .to': 'onInputChanged',
			'keyup  .to': 'onInputChanged',
			'input  .cc': 'onInputChanged',
			'paste  .cc': 'onInputChanged',
			'keyup  .cc': 'onInputChanged',
			'input  .bcc': 'onInputChanged',
			'paste  .bcc': 'onInputChanged',
			'keyup  .bcc': 'onInputChanged',
			'input  .subject': 'onInputChanged',
			'paste  .subject': 'onInputChanged',
			'keyup  .subject': 'onInputChanged',
			'input  .message-body': 'onInputChanged',
			'paste  .message-body': 'onInputChanged',
			'keyup  .message-body': 'onInputChanged',
			'focus  .recipient-autocomplete': 'onAutoComplete',
			// CC/BCC toggle
			'click .composer-cc-bcc-toggle': 'ccBccToggle'
		},
		initialize: function(options) {
			var defaultOptions = {
				type: 'new',
				account: null,
				repliedMessage: null,
				data: {
					to: '',
					cc: '',
					subject: '',
					body: ''
				}
			};
			_.defaults(options, defaultOptions);

			/**
			 * Composer type (new, reply)
			 */
			this.type = options.type;

			/**
			 * Containing element
			 */
			if (options.el) {
				this.el = options.el;
			}

			/**
			 * Attachments sub-view
			 */
			this.attachments = new Attachments();
			this.bindAttachments();

			/**
			 * Data for replies
			 */
			this.data = options.data;

			if (!this.isReply()) {
				this.accounts = options.accounts;
				this.account = options.account || this.accounts.at(0);
				this.draftUID = options.data.id;
			} else {
				this.account = options.account;
				this.accounts = options.accounts;
				this.folder = options.folder;
				this.repliedMessage = options.repliedMessage;
			}
		},
		onRender: function() {
			this.showChildView('attachmentsRegion', new AttachmentsView({
				collection: this.attachments
			}));

			$('.tooltip-mailto').tooltip({placement: 'bottom'});

			if (this.isReply()) {
				// Expand reply message body on click
				var _this = this;
				this.$('.message-body').click(function() {
					_this.setAutoSize(true);
				});
			} else {
				this.setAutoSize(true);
			}

			this.defaultMailSelect();
		},
		setAutoSize: function(state) {
			if (state === true) {
				if (!this.autosized) {
					this.$('textarea').autosize({append: '\n\n'});
					this.autosized = true;
				}
				this.$('.message-body').trigger('autosize.resize');
			} else {
				this.$('.message-body').trigger('autosize.destroy');

				// dirty workaround to set reply message body to the default size
				this.$('.message-body').css('height', '');
				this.autosized = false;
			}
		},
		bindAttachments: function() {
			// when the attachment list changed (add, remove, change), we make sure
			// to update the 'Send' button
			this.attachments.bind('all', this.onInputChanged.bind(this));
		},
		isReply: function() {
			return this.type === 'reply';
		},
		onInputChanged: function() {
			// Submit button state
			var to = this.$('.to').val();
			var subject = this.$('.subject').val();
			var body = this.$('.message-body').val();
			// if some attachments are not valid, we disable the 'send' button
			var attachmentsValid = this.checkAllAttachmentsValid();
			if ((to !== '' || subject !== '' || body !== '') && attachmentsValid) {
				this.$('.submit-message').removeAttr('disabled');
			} else {
				this.$('.submit-message').attr('disabled', true);
			}

			// Save draft
			this.hasData = true;
			clearTimeout(this.draftTimer);
			var _this = this;
			this.draftTimer = setTimeout(function() {
				_this.saveDraft();
			}, this.draftInterval);
		},
		handleKeyPress: function(event) {
			// Define which objects to check for the event properties.
			// (Window object provides fallback for IE8 and lower.)
			event = event || window.event;
			var key = event.keyCode || event.which;
			// If enter and control keys are pressed:
			// (Key 13 and 10 set for compatibility across different operating systems.)
			if ((key === 13 || key === 10) && event.ctrlKey) {
				// If the new message is completely filled, and ready to be sent:
				// Send the new message.
				var sendBtnState = this.$('.submit-message').attr('disabled');
				if (sendBtnState === undefined) {
					this.submitMessage();
				}
			}
			return true;
		},
		ccBccToggle: function(e) {
			e.preventDefault();
			this.$('.composer-cc-bcc').slideToggle();
			this.$('.composer-cc-bcc .cc').focus();
			this.$('.composer-cc-bcc-toggle').fadeOut();
		},
		getMessage: function() {
			var message = {};
			var newMessageBody = this.$('.message-body');
			var to = this.$('.to');
			var cc = this.$('.cc');
			var bcc = this.$('.bcc');
			var subject = this.$('.subject');

			message.body = newMessageBody.val();
			message.to = to.val();
			message.cc = cc.val();
			message.bcc = bcc.val();
			message.subject = subject.val();
			message.attachments = this.attachments.toJSON();

			return message;
		},
		submitMessageWrapperInside: function() {
			// http://stackoverflow.com/questions/487073/check-if-element-is-visible-after-scrolling
			if (this._isVisible()) {
				this.$('.submit-message').click();
			} else {
				$('#mail-message').animate({
					scrollTop: this.$el.offset().top
				}, 1000);
				this.$('.submit-message-wrapper-inside').hide();
				// This function is needed because $('.message-body').focus does not focus the first line
				this._setCaretToPos(this.$('.message-body')[0], 0);
			}
		},
		_setSelectionRange: function(input, selectionStart, selectionEnd) {
			if (input.setSelectionRange) {
				input.focus();
				input.setSelectionRange(selectionStart, selectionEnd);
			} else if (input.createTextRange) {
				var range = input.createTextRange();
				range.collapse(true);
				range.moveEnd('character', selectionEnd);
				range.moveStart('character', selectionStart);
				range.select();
			}
		},
		_setCaretToPos: function(input, pos) {
			this._setSelectionRange(input, pos, pos);
		},
		_isVisible: function() {
			var $elem = this.$el;
			var $window = $(window);
			var docViewTop = $window.scrollTop();
			var docViewBottom = docViewTop + $window.height();
			var elemTop = $elem.offset().top;

			return elemTop <= docViewBottom;
		},
		submitMessage: function() {
			clearTimeout(this.draftTimer);
			//
			// TODO:
			//  - input validation
			//  - feedback on success
			//  - undo lie - very important
			//

			// loading feedback: show spinner and disable elements
			var newMessageBody = this.$('.message-body');
			var newMessageSend = this.$('.submit-message');
			newMessageBody.addClass('icon-loading');
			var to = this.$('.to');
			var cc = this.$('.cc');
			var bcc = this.$('.bcc');
			var subject = this.$('.subject');
			this.$('.mail-account').prop('disabled', true);
			to.prop('disabled', true);
			cc.prop('disabled', true);
			bcc.prop('disabled', true);
			subject.prop('disabled', true);
			this.$('.new-message-attachments-action').css('display', 'none');
			this.$('#add-cloud-attachment').prop('disabled', true);
			this.$('#add-local-attachment').prop('disabled', true);
			newMessageBody.prop('disabled', true);
			newMessageSend.prop('disabled', true);
			newMessageSend.val(t('mail', 'Sending …'));
			var alias = null;

			// if available get account from drop-down list
			if (this.$('.mail-account').length > 0) {
				alias = this.findAliasById(this.$('.mail-account').
					find(':selected').val());
				this.account = this.accounts.get(alias.accountId);
			}

			// send the mail
			var _this = this;
			var options = {
				draftUID: this.draftUID,
				aliasId: alias.aliasId
			};

			if (this.isReply()) {
				options.repliedMessage = this.repliedMessage;
				options.folder = this.folder;
			}

			Radio.message.request('send', this.account, this.getMessage(), options).then(function() {
				OC.Notification.showTemporary(t('mail', 'Message sent!'));

				if (!!options.repliedMessage) {
					// Reply -> flag message as replied
					Radio.message.trigger('flag',
						options.repliedMessage,
						'answered',
						true);
				}

				_this.$('#mail_new_message').prop('disabled', false);
				to.val('');
				cc.val('');
				bcc.val('');
				subject.val('');
				newMessageBody.val('');
				newMessageBody.trigger('autosize.resize');
				_this.attachments.reset();
				if (_this.draftUID !== null) {
					// the sent message was a draft
					if (!_.isUndefined(Radio.ui.request('messagesview:collection'))) {
						Radio.ui.request('messagesview:collection').
							remove({id: _this.draftUID});
					}
					_this.draftUID = null;
				}
			}, function(jqXHR) {
				var error = '';
				if (jqXHR.status === 500) {
					error = t('mail', 'Server error');
				} else {
					var resp = JSON.parse(jqXHR.responseText);
					error = resp.message;
				}
				newMessageSend.prop('disabled', false);
				OC.Notification.showTemporary(error);
			}).then(function() {
				// remove loading feedback
				newMessageBody.removeClass('icon-loading');
				_this.$('.mail-account').prop('disabled', false);
				to.prop('disabled', false);
				cc.prop('disabled', false);
				bcc.prop('disabled', false);
				subject.prop('disabled', false);
				_this.$('.new-message-attachments-action').
					css('display', 'inline-block');
				_this.$('#add-cloud-attachment').prop('disabled', false);
				_this.$('#add-local-attachment').prop('disabled', false);
				newMessageBody.prop('disabled', false);
				newMessageSend.prop('disabled', false);
				newMessageSend.val(t('mail', 'Send'));
			});
			return false;
		},
		saveDraft: function(onSuccess) {
			clearTimeout(this.draftTimer);
			//
			// TODO:
			//  - input validation
			//  - feedback on success
			//  - undo lie - very important
			//

			// if available get account from drop-down list
			if (this.$('.mail-account').length > 0) {
				var alias = this.findAliasById(this.$('.mail-account').
					find(':selected').val());
				this.account = this.accounts.get(alias.accountId);
			}

			// send the mail
			var _this = this;
			Radio.message.request('draft', this.account, this.getMessage(), {
				folder: _this.folder,
				repliedMessage: _this.repliedMessage,
				draftUID: _this.draftUID
			}).then(function(data) {
				if (_.isFunction(onSuccess)) {
					onSuccess();
				}

				if (_this.draftUID !== null) {
					// update UID in message list
					var collection = Radio.ui.request('messagesview:collection');
					var message = collection.findWhere({id: _this.draftUID});
					if (message) {
						message.set({id: data.uid});
						collection.set([message], {remove: false});
					}
				}
				_this.draftUID = data.uid;
			}, console.error.bind(this));
			return false;
		},
		setReplyBody: function(from, date, text) {
			var minutes = date.getMinutes();

			this.$('.message-body').first().text(
				'\n\n\n' +
				from + ' – ' +
				$.datepicker.formatDate('D, d. MM yy ', date) +
				date.getHours() + ':' + (minutes < 10 ? '0' : '') + minutes + '\n> ' +
				text.replace(/\n/g, '\n> ')
				);

			this.setAutoSize(false);
			// Expand reply message body on click
			var _this = this;
			this.$('.message-body').click(function() {
				_this.setAutoSize(true);
			});
		},
		focusTo: function() {
			this.$el.find('input.to').focus();
		},
		setTo: function(value) {
			this.$el.find('input.to').val(value);
		},
		focusSubject: function() {
			this.$el.find('input.subject').focus();
		},
		onAutoComplete: function(e) {
			var elem = $(e.target);
			function split(val) {
				return val.split(/,\s*/);
			}

			function extractLast(term) {
				return split(term).pop();
			}
			if (!elem.data('autocomplete')) {
				// If the autocomplete wasn't called yet:
				// don't navigate away from the field on tab when selecting an item
				var prevUID = false;

				elem.bind('keydown', function(event) {
					if (event.keyCode === $.ui.keyCode.TAB &&
						typeof elem.data('autocomplete') !== 'undefined' &&
						elem.data('autocomplete').menu.active) {
						event.preventDefault();
					}
				}).autocomplete({
					source: function(request, response) {
						$.getJSON(
							OC.generateUrl('/apps/mail/autoComplete'),
							{
								term: extractLast(request.term)
							}, response);
					},
					search: function() {
						// custom minLength
						var term = extractLast(this.value);
						return term.length >= 2;
					},
					response: function() {
						// Reset prevUid
						prevUID = false;
					},
					focus: function() {
						// prevent value inserted on focus
						return false;
					},
					select: function(event, ui) {
						var terms = split(this.value);
						// remove the current input
						terms.pop();
						// add the selected item
						terms.push(ui.item.value);
						// add placeholder to get the comma-and-space at the end
						terms.push('');
						this.value = terms.join(', ');
						return false;
					}
				}).
					data('ui-autocomplete')._renderItem = function(
					$ul, item) {
					var $item = $('<li/>');
					var $row = $('<a/>');

					$row.addClass('mail-recipient-autocomplete');

					var $placeholder;
					if (prevUID === item.id) {
						$placeholder = $('<div/>');
						$placeholder.addClass('avatar');
						$row.append($placeholder);
					} else if (item.photo && item.photo !== null) {
						var $avatar = $('<img/>');
						$avatar.addClass('avatar');
						$avatar.height('32px');
						$avatar.width('32px');
						$avatar.attr('src', item.photo);
						$row.append($avatar);
					} else {
						$placeholder = $('<div/>');
						$placeholder.imageplaceholder(item.label || item.value);
						$placeholder.addClass('avatar');
						$row.append($placeholder);
					}

					prevUID = item.id;

					$row.append($('<span>').text(item.label || item.value));

					$item.append($row);
					$item.appendTo($ul);
					return $item;
				};
			}
		},
		buildAliases: function() {
			var aliases = [];
			var id = 1;

			this.accounts.forEach(function(account) {
				var json = account.toJSON();
				// add Primary email address
				aliases.push({
					id: id++,
					accountId: json.accountId,
					aliasId: null,
					emailAddress: json.emailAddress,
					name: json.name
				});
				// add Aliases email adresses
				for (var x in json.aliases) {
					aliases.push({
						id: id++,
						accountId: json.aliases[x].accountId,
						aliasId: json.aliases[x].id,
						emailAddress: json.aliases[x].alias,
						name: json.aliases[x].name
					});
				}
			});
			this.aliases = aliases;
			return aliases;
		},
		findAliasById: function(id) {
			return _.find(this.aliases, function(alias) {
				return parseInt(alias.id) === parseInt(id);
			});
		},
		defaultMailSelect: function() {
			var alias = null;
			if (!this.isReply()) {
				if (__webpack_require__(0).currentAccount.get('accountId') !== -1) {
					alias = _.find(this.aliases, function(alias) {
						return alias.emailAddress === __webpack_require__(0).currentAccount.get('email');
					});
				} else {
					var firstAccount = this.accounts.filter(function(
						account) {
						return account.get('accountId') !== -1;
					})[0];
					alias = _.find(this.aliases, function(alias) {
						return alias.emailAddress === firstAccount.get('emailAddress');
					});
				}
			} else {
				var toEmail = this.data.toEmail;
				alias = _.find(this.aliases, function(alias) {
					return alias.emailAddress === toEmail;
				});
			}
			if (alias) {
				this.$('.mail-account').val(alias.id);
			}
		},
		/**
		 * Checke that all attachments are valid.
		 * If there is some LocalAttachments stil pending, ongoing or that failed,
		 * This method will return false.
		 * If there is no LocalAttachments, or if they are are all sent,
		 * this method will return true.
		 * @return {boolean} all attachments valid
		 */
		checkAllAttachmentsValid: function() {
			var allAttachmentsValid = true;
			var len = this.attachments.length;
			for (var i = 0; i < len; i++) {
				/* We check all the attachments here */
				var attachment = this.attachments.models[i];
				var uploadStatus = attachment.get('uploadStatus');
				var isLocalUpload = (uploadStatus !== undefined);
				/* If at least one attachment is a local upload and */
				/* not a success (==3), we disable the send button */
				if (isLocalUpload && uploadStatus < 3) {
					allAttachmentsValid = false;
				}
			}
			return allAttachmentsValid;
		}
	});

}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 16 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Backbone = __webpack_require__(6);
	var Attachment = __webpack_require__(17);

	/**
	 * @class AttachmentCollection
	 */
	var AttachmentCollection = Backbone.Collection.extend({
		model: Attachment
	});

	return AttachmentCollection;
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 17 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2017
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Backbone = __webpack_require__(6);
	var _ = __webpack_require__(3);

	/**
	 * @class Attachment
	 */
	var Attachment = Backbone.Model.extend({
		defaults: {
			isLocal: false
		},

		initialize: function() {
			if (_.isUndefined(this.get('id'))) {
				this.set('id', _.uniqueId());
			}

			var s = this.get('fileName');

			if (_.isUndefined(s)) {
				return;
			}

			if (s.charAt(0) === '/') {
				s = s.substr(1);
			}

			this.set('displayName', s);
		}
	});

	return Attachment;
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 18 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015, 2017
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var _ = __webpack_require__(3);
	var Backbone = __webpack_require__(6);
	var FolderCollection = __webpack_require__(19);
	var AliasesCollection = __webpack_require__(42);
	var OC = __webpack_require__(7);

	/**
	 * @class Account
	 */
	var Account = Backbone.Model.extend({
		defaults: {
			aliases: [],
			specialFolders: [],
			isUnified: false
		},
		idAttribute: 'accountId',
		url: function() {
			return OC.generateUrl('apps/mail/accounts');
		},
		initialize: function() {
			this.folders = new FolderCollection();
			this.set('aliases', new AliasesCollection(this.get('aliases')));
		},
		_getFolderByIdRecursively: function(folder, folderId) {
			if (!folder) {
				return null;
			}

			if (folder.get('id') === folderId) {
				return folder;
			}

			var subFolders = folder.folders;
			if (!subFolders) {
				return null;
			}
			for (var i = 0; i < subFolders.length; i++) {
				var subFolder = this._getFolderByIdRecursively(subFolders.at(i), folderId);
				if (subFolder) {
					return subFolder;
				}
			}

			return null;
		},
		/**
		 * @param {Folder} folder
		 * @returns {undefined}
		 */
		addFolder: function(folder) {
			folder.account = this;
			this.folders.add(folder);
		},
		getFolderById: function(folderId) {
			if (!this.folders) {
				return undefined;
			}
			for (var i = 0; i < this.folders.length; i++) {
				var result = this._getFolderByIdRecursively(this.folders.at(i), folderId);
				if (result) {
					return result;
				}
			}
			return undefined;
		},
		getSpecialFolder: function() {
			if (!this.folders) {
				return undefined;
			}
			return _.find(this.folders, function(folder) {
				// TODO: handle special folders in subfolder properly
				if (folder.get('specialRole') === 'draft') {
					return true;
				}
			});
		},
		toJSON: function() {
			var data = Backbone.Model.prototype.toJSON.call(this);
			if (data.folders && data.folders.toJSON) {
				data.folders = data.folders.toJSON();
			}
			if (data.aliases && data.aliases.toJSON) {
				data.aliases = data.aliases.toJSON();
			}
			if (!data.id) {
				data.id = this.cid;
			}
			return data;
		}
	});

	return Account;
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 19 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015, 2016
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Backbone = __webpack_require__(6);
	var Folder = __webpack_require__(38);

	/**
	 * @class FolderCollection
	 */
	var FolderCollection = Backbone.Collection.extend({
		model: Folder
	});

	return FolderCollection;
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 20 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Backbone = __webpack_require__(6);
	var Message = __webpack_require__(39);

	/**
	 * @class MessageCollection
	 */
	var MessageCollection = Backbone.Collection.extend({
		model: Message,
		comparator: function(message) {
			return message.get('dateInt') * -1;
		}
	});

	return MessageCollection;
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 21 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
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

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var smileys = [
		':-(',
		':-/',
		':-\\',
		':-|',
		':\'-(',
		':\'-/',
		':\'-\\',
		':\'-|'
	];

	function getRandomSmiley() {
		return smileys[Math.floor(Math.random() * smileys.length)]
	}

	/**
	 * @param {Folder} folder
	 * @returns {string}
	 */
	function getRandomFolderErrorMessage(folder) {
		var folderName = folder.get('name');
		var rawTexts = [
			t('mail', 'Could not load {tag}{name}{endtag}', {
				name: folderName
			}),
			t('mail', 'We couldn’t load {tag}{name}{endtag}', {
				name: folderName
			}),
			t('mail', 'There was a problem loading {tag}{name}{endtag}', {
				name: folderName
			})
		];
		var texts = _.map(rawTexts, function(text) {
			return text.replace('{tag}', '<strong>').replace('{endtag}', '</strong>');
		});
		var text = texts[Math.floor(Math.random() * texts.length)]
		return text + ' ' + getRandomSmiley();
	}

	/**
	 * @returns {string}
	 */
	function getRandomMessageErrorMessage() {
		var texts = [
			t('mail', 'We couldn’t load your message'),
			t('mail', 'Unable to load the desired message'),
			t('mail', 'There was a problem loading the message')
		];
		var text = texts[Math.floor(Math.random() * texts.length)]
		return text + ' ' + getRandomSmiley();
	}

	return {
		getRandomFolderErrorMessage: getRandomFolderErrorMessage,
		getRandomMessageErrorMessage: getRandomMessageErrorMessage
	};
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 22 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2016
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Marionette = __webpack_require__(2);
	var FolderView = __webpack_require__(67);

	var SHOW_COLLAPSED = Object.seal([
		'inbox',
		'flagged',
		'drafts',
		'sent'
	]);

	var FolderListView = Marionette.CollectionView.extend({
		tagName: 'ul',
		childView: FolderView,
		collapsed: true,
		initialize: function(options) {
			this.collapsed = options.collapsed;
		},
		filter: function(child) {
			if (!this.collapsed) {
				return true;
			}
			var specialRole = child.get('specialRole');
			return SHOW_COLLAPSED.indexOf(specialRole) !== -1;
		}
	});

	return FolderListView;
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 23 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/* global Promise */

/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2016, 2017
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var FolderController = __webpack_require__(12);
	var Radio = __webpack_require__(1);

	/**
	 * Load all accounts
	 *
	 * @returns {Promise}
	 */
	function loadAccounts() {
		// Do not show sidebar content until everything has been loaded
		Radio.ui.trigger('sidebar:loading');

		return Radio.account.request('entities').then(function(accounts) {
			if (accounts.length === 0) {
				Radio.navigation.trigger('setup');
				Radio.ui.trigger('sidebar:accounts');
				return Promise.resolve(accounts);
			}

			return Promise.all(accounts.map(function(account) {
				return FolderController.loadAccountFolders(account);
			})).then(function() {
				return accounts;
			});
		}).then(function(accounts) {
			// Show accounts regardless of the result of
			// loading the folders
			Radio.ui.trigger('sidebar:accounts');

			return accounts;
		}, function(e) {
			console.error(e);
			Radio.ui.trigger('error:show', t('mail', 'Error while loading the accounts.'));
		});
	}

	return {
		loadAccounts: loadAccounts
	};
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 24 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
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

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	var App = __webpack_require__(25);

	$(function() {
		// Start app when the page is ready
		console.log('Starting Mail …');
		App.start();
	});
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 25 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/* global SearchProxy */

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

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	// Enable ES6 promise polyfill
	__webpack_require__(26).polyfill();

	var $ = __webpack_require__(5);
	var Backbone = __webpack_require__(6);
	var Marionette = __webpack_require__(2);
	var OC = __webpack_require__(7);
	var AppView = __webpack_require__(28);
	var Cache = __webpack_require__(8);
	var Radio = __webpack_require__(1);
	var Router = __webpack_require__(87);
	var AccountController = __webpack_require__(23);
	var RouteController = __webpack_require__(88);

	// Load controllers/services
	__webpack_require__(12);
	__webpack_require__(9);
	__webpack_require__(89);
	__webpack_require__(90);
	__webpack_require__(91);
	__webpack_require__(92);
	__webpack_require__(96);
	__webpack_require__(97);
	__webpack_require__(98);
	__webpack_require__(99);
	__webpack_require__(100);

	var Mail = Marionette.Application.extend({

		/**
		 * Register the mailto protocol handler
		 *
		 * @returns {undefined}
		 */
		registerProtocolHandler: function() {
			if (window.navigator.registerProtocolHandler) {
				var url = window.location.protocol + '//' +
					window.location.host +
					OC.generateUrl('apps/mail/compose?uri=%s');
				try {
					window.navigator
						.registerProtocolHandler('mailto', url, OC.theme.name + ' Mail');
				} catch (e) {
				}
			}
		},

		/**
		 * @returns {undefined}
		 */
		requestNotificationPermissions: function() {
			Radio.ui.trigger('notification:request');
		},

		/**
		 * Register the actual search module in the search proxy
		 *
		 * @returns {undefined}
		 */
		setUpSearch: function() {
			SearchProxy.setFilter(__webpack_require__(101).filter);
		},

		/**
		 * Start syncing accounts in the background
		 *
		 * @param {AccountCollection} accounts
		 * @returns {undefined}
		 */
		startBackgroundSync: function(accounts) {
			Radio.sync.trigger('start', accounts);
		}
	});

	Mail = new Mail();

	Mail.on('start', function() {
		this.view = new AppView();
		Cache.init();

		Radio.ui.trigger('content:loading');

		this.registerProtocolHandler();
		this.requestNotificationPermissions();
		this.setUpSearch();

		var _this = this;
		AccountController.loadAccounts().then(function(accounts) {
			_this.router = new Router({
				controller: new RouteController(accounts)
			});
			Backbone.history.start();
			_this.startBackgroundSync(accounts);
		});

		/**
		 * Detects pasted text by browser plugins, and other software.
		 * Check for changes in message bodies every second.
		 */
		setInterval((function() {
			// Begin the loop.
			return function() {

				// Define which elements hold the message body.
				var MessageBody = $('.message-body');

				/**
				 * If the message body is displayed and has content:
				 * Prepare the message body content for processing.
				 * If there is new message body content to process:
				 * Resize the text area.
				 * Toggle the send button, based on whether the message is ready or not.
				 * Prepare the new message body content for future processing.
				 */
				if (MessageBody.val()) {
					var OldMessageBody = MessageBody.val();
					var NewMessageBody = MessageBody.val();
					if (NewMessageBody !== OldMessageBody) {
						MessageBody.trigger('autosize.resize');
						OldMessageBody = NewMessageBody;
					}
				}
			};
		})(), 1000);
	});

	return Mail;
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 26 */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(process, global) {var require;!function(t,e){ true?module.exports=e():"function"==typeof define&&define.amd?define(e):t.ES6Promise=e()}(this,function(){"use strict";function t(t){var e=typeof t;return null!==t&&("object"===e||"function"===e)}function e(t){return"function"==typeof t}function n(t){I=t}function r(t){J=t}function o(){return function(){return process.nextTick(a)}}function i(){return"undefined"!=typeof H?function(){H(a)}:c()}function s(){var t=0,e=new V(a),n=document.createTextNode("");return e.observe(n,{characterData:!0}),function(){n.data=t=++t%2}}function u(){var t=new MessageChannel;return t.port1.onmessage=a,function(){return t.port2.postMessage(0)}}function c(){var t=setTimeout;return function(){return t(a,1)}}function a(){for(var t=0;t<G;t+=2){var e=$[t],n=$[t+1];e(n),$[t]=void 0,$[t+1]=void 0}G=0}function f(){try{var t=require,e=__webpack_require__(!(function webpackMissingModule() { var e = new Error("Cannot find module \"vertx\""); e.code = 'MODULE_NOT_FOUND'; throw e; }()));return H=e.runOnLoop||e.runOnContext,i()}catch(n){return c()}}function l(t,e){var n=arguments,r=this,o=new this.constructor(p);void 0===o[et]&&k(o);var i=r._state;return i?!function(){var t=n[i-1];J(function(){return x(i,o,t,r._result)})}():E(r,o,t,e),o}function h(t){var e=this;if(t&&"object"==typeof t&&t.constructor===e)return t;var n=new e(p);return g(n,t),n}function p(){}function v(){return new TypeError("You cannot resolve a promise with itself")}function d(){return new TypeError("A promises callback cannot return that same promise.")}function _(t){try{return t.then}catch(e){return it.error=e,it}}function y(t,e,n,r){try{t.call(e,n,r)}catch(o){return o}}function m(t,e,n){J(function(t){var r=!1,o=y(n,e,function(n){r||(r=!0,e!==n?g(t,n):S(t,n))},function(e){r||(r=!0,j(t,e))},"Settle: "+(t._label||" unknown promise"));!r&&o&&(r=!0,j(t,o))},t)}function b(t,e){e._state===rt?S(t,e._result):e._state===ot?j(t,e._result):E(e,void 0,function(e){return g(t,e)},function(e){return j(t,e)})}function w(t,n,r){n.constructor===t.constructor&&r===l&&n.constructor.resolve===h?b(t,n):r===it?(j(t,it.error),it.error=null):void 0===r?S(t,n):e(r)?m(t,n,r):S(t,n)}function g(e,n){e===n?j(e,v()):t(n)?w(e,n,_(n)):S(e,n)}function A(t){t._onerror&&t._onerror(t._result),T(t)}function S(t,e){t._state===nt&&(t._result=e,t._state=rt,0!==t._subscribers.length&&J(T,t))}function j(t,e){t._state===nt&&(t._state=ot,t._result=e,J(A,t))}function E(t,e,n,r){var o=t._subscribers,i=o.length;t._onerror=null,o[i]=e,o[i+rt]=n,o[i+ot]=r,0===i&&t._state&&J(T,t)}function T(t){var e=t._subscribers,n=t._state;if(0!==e.length){for(var r=void 0,o=void 0,i=t._result,s=0;s<e.length;s+=3)r=e[s],o=e[s+n],r?x(n,r,o,i):o(i);t._subscribers.length=0}}function M(){this.error=null}function P(t,e){try{return t(e)}catch(n){return st.error=n,st}}function x(t,n,r,o){var i=e(r),s=void 0,u=void 0,c=void 0,a=void 0;if(i){if(s=P(r,o),s===st?(a=!0,u=s.error,s.error=null):c=!0,n===s)return void j(n,d())}else s=o,c=!0;n._state!==nt||(i&&c?g(n,s):a?j(n,u):t===rt?S(n,s):t===ot&&j(n,s))}function C(t,e){try{e(function(e){g(t,e)},function(e){j(t,e)})}catch(n){j(t,n)}}function O(){return ut++}function k(t){t[et]=ut++,t._state=void 0,t._result=void 0,t._subscribers=[]}function Y(t,e){this._instanceConstructor=t,this.promise=new t(p),this.promise[et]||k(this.promise),B(e)?(this.length=e.length,this._remaining=e.length,this._result=new Array(this.length),0===this.length?S(this.promise,this._result):(this.length=this.length||0,this._enumerate(e),0===this._remaining&&S(this.promise,this._result))):j(this.promise,q())}function q(){return new Error("Array Methods must be provided an Array")}function F(t){return new Y(this,t).promise}function D(t){var e=this;return new e(B(t)?function(n,r){for(var o=t.length,i=0;i<o;i++)e.resolve(t[i]).then(n,r)}:function(t,e){return e(new TypeError("You must pass an array to race."))})}function K(t){var e=this,n=new e(p);return j(n,t),n}function L(){throw new TypeError("You must pass a resolver function as the first argument to the promise constructor")}function N(){throw new TypeError("Failed to construct 'Promise': Please use the 'new' operator, this object constructor cannot be called as a function.")}function U(t){this[et]=O(),this._result=this._state=void 0,this._subscribers=[],p!==t&&("function"!=typeof t&&L(),this instanceof U?C(this,t):N())}function W(){var t=void 0;if("undefined"!=typeof global)t=global;else if("undefined"!=typeof self)t=self;else try{t=Function("return this")()}catch(e){throw new Error("polyfill failed because global object is unavailable in this environment")}var n=t.Promise;if(n){var r=null;try{r=Object.prototype.toString.call(n.resolve())}catch(e){}if("[object Promise]"===r&&!n.cast)return}t.Promise=U}var z=void 0;z=Array.isArray?Array.isArray:function(t){return"[object Array]"===Object.prototype.toString.call(t)};var B=z,G=0,H=void 0,I=void 0,J=function(t,e){$[G]=t,$[G+1]=e,G+=2,2===G&&(I?I(a):tt())},Q="undefined"!=typeof window?window:void 0,R=Q||{},V=R.MutationObserver||R.WebKitMutationObserver,X="undefined"==typeof self&&"undefined"!=typeof process&&"[object process]"==={}.toString.call(process),Z="undefined"!=typeof Uint8ClampedArray&&"undefined"!=typeof importScripts&&"undefined"!=typeof MessageChannel,$=new Array(1e3),tt=void 0;tt=X?o():V?s():Z?u():void 0===Q&&"function"=="function"?f():c();var et=Math.random().toString(36).substring(16),nt=void 0,rt=1,ot=2,it=new M,st=new M,ut=0;return Y.prototype._enumerate=function(t){for(var e=0;this._state===nt&&e<t.length;e++)this._eachEntry(t[e],e)},Y.prototype._eachEntry=function(t,e){var n=this._instanceConstructor,r=n.resolve;if(r===h){var o=_(t);if(o===l&&t._state!==nt)this._settledAt(t._state,e,t._result);else if("function"!=typeof o)this._remaining--,this._result[e]=t;else if(n===U){var i=new n(p);w(i,t,o),this._willSettleAt(i,e)}else this._willSettleAt(new n(function(e){return e(t)}),e)}else this._willSettleAt(r(t),e)},Y.prototype._settledAt=function(t,e,n){var r=this.promise;r._state===nt&&(this._remaining--,t===ot?j(r,n):this._result[e]=n),0===this._remaining&&S(r,this._result)},Y.prototype._willSettleAt=function(t,e){var n=this;E(t,void 0,function(t){return n._settledAt(rt,e,t)},function(t){return n._settledAt(ot,e,t)})},U.all=F,U.race=D,U.resolve=h,U.reject=K,U._setScheduler=n,U._setAsap=r,U._asap=J,U.prototype={constructor:U,then:l,"catch":function(t){return this.then(null,t)}},U.polyfill=W,U.Promise=U,U});
/* WEBPACK VAR INJECTION */}.call(exports, __webpack_require__(27), __webpack_require__(13)))

/***/ }),
/* 27 */
/***/ (function(module, exports) {

// shim for using process in browser
var process = module.exports = {};

// cached from whatever global is present so that test runners that stub it
// don't break things.  But we need to wrap it in a try catch in case it is
// wrapped in strict mode code which doesn't define any globals.  It's inside a
// function because try/catches deoptimize in certain engines.

var cachedSetTimeout;
var cachedClearTimeout;

function defaultSetTimout() {
    throw new Error('setTimeout has not been defined');
}
function defaultClearTimeout () {
    throw new Error('clearTimeout has not been defined');
}
(function () {
    try {
        if (typeof setTimeout === 'function') {
            cachedSetTimeout = setTimeout;
        } else {
            cachedSetTimeout = defaultSetTimout;
        }
    } catch (e) {
        cachedSetTimeout = defaultSetTimout;
    }
    try {
        if (typeof clearTimeout === 'function') {
            cachedClearTimeout = clearTimeout;
        } else {
            cachedClearTimeout = defaultClearTimeout;
        }
    } catch (e) {
        cachedClearTimeout = defaultClearTimeout;
    }
} ())
function runTimeout(fun) {
    if (cachedSetTimeout === setTimeout) {
        //normal enviroments in sane situations
        return setTimeout(fun, 0);
    }
    // if setTimeout wasn't available but was latter defined
    if ((cachedSetTimeout === defaultSetTimout || !cachedSetTimeout) && setTimeout) {
        cachedSetTimeout = setTimeout;
        return setTimeout(fun, 0);
    }
    try {
        // when when somebody has screwed with setTimeout but no I.E. maddness
        return cachedSetTimeout(fun, 0);
    } catch(e){
        try {
            // When we are in I.E. but the script has been evaled so I.E. doesn't trust the global object when called normally
            return cachedSetTimeout.call(null, fun, 0);
        } catch(e){
            // same as above but when it's a version of I.E. that must have the global object for 'this', hopfully our context correct otherwise it will throw a global error
            return cachedSetTimeout.call(this, fun, 0);
        }
    }


}
function runClearTimeout(marker) {
    if (cachedClearTimeout === clearTimeout) {
        //normal enviroments in sane situations
        return clearTimeout(marker);
    }
    // if clearTimeout wasn't available but was latter defined
    if ((cachedClearTimeout === defaultClearTimeout || !cachedClearTimeout) && clearTimeout) {
        cachedClearTimeout = clearTimeout;
        return clearTimeout(marker);
    }
    try {
        // when when somebody has screwed with setTimeout but no I.E. maddness
        return cachedClearTimeout(marker);
    } catch (e){
        try {
            // When we are in I.E. but the script has been evaled so I.E. doesn't  trust the global object when called normally
            return cachedClearTimeout.call(null, marker);
        } catch (e){
            // same as above but when it's a version of I.E. that must have the global object for 'this', hopfully our context correct otherwise it will throw a global error.
            // Some versions of I.E. have different rules for clearTimeout vs setTimeout
            return cachedClearTimeout.call(this, marker);
        }
    }



}
var queue = [];
var draining = false;
var currentQueue;
var queueIndex = -1;

function cleanUpNextTick() {
    if (!draining || !currentQueue) {
        return;
    }
    draining = false;
    if (currentQueue.length) {
        queue = currentQueue.concat(queue);
    } else {
        queueIndex = -1;
    }
    if (queue.length) {
        drainQueue();
    }
}

function drainQueue() {
    if (draining) {
        return;
    }
    var timeout = runTimeout(cleanUpNextTick);
    draining = true;

    var len = queue.length;
    while(len) {
        currentQueue = queue;
        queue = [];
        while (++queueIndex < len) {
            if (currentQueue) {
                currentQueue[queueIndex].run();
            }
        }
        queueIndex = -1;
        len = queue.length;
    }
    currentQueue = null;
    draining = false;
    runClearTimeout(timeout);
}

process.nextTick = function (fun) {
    var args = new Array(arguments.length - 1);
    if (arguments.length > 1) {
        for (var i = 1; i < arguments.length; i++) {
            args[i - 1] = arguments[i];
        }
    }
    queue.push(new Item(fun, args));
    if (queue.length === 1 && !draining) {
        runTimeout(drainQueue);
    }
};

// v8 likes predictible objects
function Item(fun, array) {
    this.fun = fun;
    this.array = array;
}
Item.prototype.run = function () {
    this.fun.apply(null, this.array);
};
process.title = 'browser';
process.browser = true;
process.env = {};
process.argv = [];
process.version = ''; // empty string to avoid regexp issues
process.versions = {};

function noop() {}

process.on = noop;
process.addListener = noop;
process.once = noop;
process.off = noop;
process.removeListener = noop;
process.removeAllListeners = noop;
process.emit = noop;
process.prependListener = noop;
process.prependOnceListener = noop;

process.listeners = function (name) { return [] }

process.binding = function (name) {
    throw new Error('process.binding is not supported');
};

process.cwd = function () { return '/' };
process.chdir = function (dir) {
    throw new Error('process.chdir is not supported');
};
process.umask = function() { return 0; };


/***/ }),
/* 28 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/* global oc_defaults */

/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2016
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var document = __webpack_require__(29);
	var Marionette = __webpack_require__(2);
	var $ = __webpack_require__(5);
	var OC = __webpack_require__(7);
	var Radio = __webpack_require__(1);
	var FolderContentView = __webpack_require__(30);
	var NavigationAccountsView = __webpack_require__(65);
	var SettingsView = __webpack_require__(70);
	var ErrorView = __webpack_require__(10);
	var LoadingView = __webpack_require__(11);
	var NavigationView = __webpack_require__(72);
	var SetupView = __webpack_require__(75);
	var AccountSettingsView = __webpack_require__(79);
	var KeyboardShortcutView = __webpack_require__(84);

	// Load handlebars helper
	__webpack_require__(86);

	var ContentType = Object.freeze({
		ERROR: -2,
		LOADING: -1,
		FOLDER_CONTENT: 0,
		SETUP: 1,
		ACCOUNT_SETTINGS: 2,
		KEYBOARD_SHORTCUTS: 3
	});

	var AppView = Marionette.View.extend({
		el: '#app',
		accountsView: null,
		activeContent: null,
		regions: {
			content: '#app-content .mail-content',
			setup: '#setup'
		},
		initialize: function() {
			this.bindUIElements();

			// Global event handlers:
			this.listenTo(Radio.notification, 'favicon:change', this.changeFavicon);
			this.listenTo(Radio.ui, 'notification:show', this.showNotification);
			this.listenTo(Radio.ui, 'error:show', this.showError);
			this.listenTo(Radio.ui, 'setup:show', this.showSetup);
			this.listenTo(Radio.ui, 'foldercontent:show', this.showFolderContent);
			this.listenTo(Radio.ui, 'content:error', this.showContentError);
			this.listenTo(Radio.ui, 'content:loading', this.showContentLoading);
			this.listenTo(Radio.ui, 'title:update', this.updateTitle);
			this.listenTo(Radio.ui, 'accountsettings:show', this.showAccountSettings);
			this.listenTo(Radio.ui, 'search:set', this.setSearchQuery);
			this.listenTo(Radio.ui, 'sidebar:loading', this.showSidebarLoading);
			this.listenTo(Radio.ui, 'sidebar:accounts', this.showSidebarAccounts);
			this.listenTo(Radio.ui, 'keyboardShortcuts:show', this.showKeyboardShortcuts);

			// Hide notification favicon when switching back from
			// another browser tab
			$(document).on('show', this.onDocumentShow);

			$(document).on('click', this.onDocumentClick);

			// Listens to key strokes, and executes a function based
			// on the key combinations.
			$(document).keyup(this.onKeyUp);

			window.addEventListener('resize', this.onWindowResize);

			$(document).on('click', function(e) {
				Radio.ui.trigger('document:click', e);
			});

			// TODO: create marionette view and encapsulate events
			$(document).on('click', '#forward-button', function() {
				Radio.message.trigger('forward');
			});

			$(document).on('click', '.link-mailto', function(event) {
				Radio.ui.trigger('composer:show', event);
			});

			// TODO: create marionette view and encapsulate events
			// close message when close button is tapped on mobile
			$(document).on('click', '#mail-message-close', function() {
				$('#mail-message').addClass('hidden-mobile');
			});

			// TODO: create marionette view and encapsulate events
			// Show the images if wanted
			$(document).on('click', '#show-images-button', function() {
				$('#show-images-text').hide();
				$('iframe').contents().find('img[data-original-src]').each(function() {
					$(this).attr('src', $(this).attr('data-original-src'));
					$(this).show();
				});
				$('iframe').contents().find('[data-original-style]').each(function() {
					$(this).attr('style', $(this).attr('data-original-style'));
				});
			});

			// Render settings menu
			this.navigation = new NavigationView({
				accounts: __webpack_require__(0).accounts
			});
			this.navigation.showChildView('settings', new SettingsView());
		},
		onDocumentClick: function(event) {
			Radio.ui.trigger('document:click', event);
		},
		onDocumentShow: function(e) {
			e.preventDefault();
			Radio.notification.trigger('favicon:change', OC.filePath('mail', 'img', 'favicon.png'));
		},
		onKeyUp: function(e) {
			// Define which objects to check for the event properties.
			var key = e.keyCode || e.which;

			// Trigger the event only if no input or textarea is focused
			// and the CTRL key is not pressed
			if ($('input:focus').length === 0 &&
				$('textarea:focus').length === 0 &&
				!e.ctrlKey) {
				Radio.keyboard.trigger('keyup', e, key);
			}
		},
		onWindowResize: function() {
			// Resize iframe
			var iframe = $('#mail-content iframe');
			iframe.height(iframe.contents().find('html').height() + 20);
		},
		render: function() {
			// This view doesn't need rendering
		},
		changeFavicon: function(src) {
			$('link[rel="shortcut icon"]').attr('href', src);
		},
		showNotification: function(message) {
			OC.Notification.showTemporary(message);
		},
		showError: function(message) {
			OC.Notification.showTemporary(message);
			$('#mail_message').removeClass('icon-loading');
		},
		showSetup: function() {
			this.activeContent = ContentType.SETUP;

			this.showChildView('content', new SetupView({
				config: {
					accountName: $('#user-displayname').text(),
					emailAddress: $('#user-email').text()
				}
			}));
		},
		showKeyboardShortcuts: function() {
			this.activeContent = ContentType.KEYBOARD_SHORTCUTS;
			this.showChildView('content', new KeyboardShortcutView({}));
		},
		showFolderContent: function(account, folder, options) {
			this.activeContent = ContentType.FOLDER_CONTENT;

			// Merge account, folder into a single options object
			options.account = account;
			options.folder = folder;

			this.showChildView('content', new FolderContentView(options));
		},
		showContentError: function(text, icon) {
			this.activeContent = ContentType.ERROR;
			this.showChildView('content', new ErrorView({
				text: text,
				icon: icon
			}));
		},
		showContentLoading: function(text) {
			this.activeContent = ContentType.LOADING;
			this.showChildView('content', new LoadingView({
				text: text
			}));
		},
		updateTitle: function() {
			var activeEmail = '';
			if (__webpack_require__(0).currentAccount.get('accountId') !== -1) {
				var activeAccount = __webpack_require__(0).currentAccount;
				activeEmail = ' - ' + activeAccount.get('email');
			}
			var activeFolder = __webpack_require__(0).currentFolder;
			var name = activeFolder.name || activeFolder.get('name');
			var count = 0;
			// TODO: use specialRole instead, otherwise this won't work with localized drafts folders
			if (name === 'Drafts') {
				count = activeFolder.total || activeFolder.get('total');
			} else {
				count = activeFolder.unseen || activeFolder.get('unseen');
			}
			if (count > 0) {
				window.document.title = name + ' (' + count + ')' +
					// jscs:disable requireCamelCaseOrUpperCaseIdentifiers
					activeEmail + ' - Mail - ' + oc_defaults.title;
				// jscs:enable requireCamelCaseOrUpperCaseIdentifiers
			} else {
				window.document.title = name + activeEmail +
					// jscs:disable requireCamelCaseOrUpperCaseIdentifiers
					' - Mail - ' + oc_defaults.title;
				// jscs:enable requireCamelCaseOrUpperCaseIdentifiers
			}
		},
		showAccountSettings: function(account) {
			this.activeContent = ContentType.ACCOUNT_SETTINGS;

			this.showChildView('content', new AccountSettingsView({
				account: account
			}));
		},
		setSearchQuery: function(val) {
			val = val || '';
			$('#searchbox').val(val);
		},
		showSidebarLoading: function() {
			$('#app-navigation').addClass('icon-loading');
			if (this.navigation.getChildView('accounts')) {
				this.navigation.detachChildView('accounts');
			}
		},
		showSidebarAccounts: function() {
			$('#app-navigation').removeClass('icon-loading');
			// setup folder view
			this.navigation.showChildView('accounts', new NavigationAccountsView({
				collection: __webpack_require__(0).accounts
			}));
			// Also show the 'New message' button
			Radio.ui.trigger('navigation:newmessage:show');
		}
	});

	return AppView;
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 29 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * @license RequireJS domReady 2.0.1 Copyright (c) 2010-2012, The Dojo Foundation All Rights Reserved.
 * Available via the MIT or new BSD license.
 * see: http://github.com/requirejs/domReady for details
 */
/*jslint */
/*global require: false, define: false, requirejs: false,
  window: false, clearInterval: false, document: false,
  self: false, setInterval: false */


!(__WEBPACK_AMD_DEFINE_RESULT__ = function () {
    'use strict';

    var isTop, testDiv, scrollIntervalId,
        isBrowser = typeof window !== "undefined" && window.document,
        isPageLoaded = !isBrowser,
        doc = isBrowser ? document : null,
        readyCalls = [];

    function runCallbacks(callbacks) {
        var i;
        for (i = 0; i < callbacks.length; i += 1) {
            callbacks[i](doc);
        }
    }

    function callReady() {
        var callbacks = readyCalls;

        if (isPageLoaded) {
            //Call the DOM ready callbacks
            if (callbacks.length) {
                readyCalls = [];
                runCallbacks(callbacks);
            }
        }
    }

    /**
     * Sets the page as loaded.
     */
    function pageLoaded() {
        if (!isPageLoaded) {
            isPageLoaded = true;
            if (scrollIntervalId) {
                clearInterval(scrollIntervalId);
            }

            callReady();
        }
    }

    if (isBrowser) {
        if (document.addEventListener) {
            //Standards. Hooray! Assumption here that if standards based,
            //it knows about DOMContentLoaded.
            document.addEventListener("DOMContentLoaded", pageLoaded, false);
            window.addEventListener("load", pageLoaded, false);
        } else if (window.attachEvent) {
            window.attachEvent("onload", pageLoaded);

            testDiv = document.createElement('div');
            try {
                isTop = window.frameElement === null;
            } catch (e) {}

            //DOMContentLoaded approximation that uses a doScroll, as found by
            //Diego Perini: http://javascript.nwbox.com/IEContentLoaded/,
            //but modified by other contributors, including jdalton
            if (testDiv.doScroll && isTop && window.external) {
                scrollIntervalId = setInterval(function () {
                    try {
                        testDiv.doScroll();
                        pageLoaded();
                    } catch (e) {}
                }, 30);
            }
        }

        //Check if document already complete, and if so, just trigger page load
        //listeners. Latest webkit browsers also use "interactive", and
        //will fire the onDOMContentLoaded before "interactive" but not after
        //entering "interactive" or "complete". More details:
        //http://dev.w3.org/html5/spec/the-end.html#the-end
        //http://stackoverflow.com/questions/3665561/document-readystate-of-interactive-vs-ondomcontentloaded
        //Hmm, this is more complicated on further use, see "firing too early"
        //bug: https://github.com/requirejs/domReady/issues/1
        //so removing the || document.readyState === "interactive" test.
        //There is still a window.onload binding that should get fired if
        //DOMContentLoaded is missed.
        if (document.readyState === "complete") {
            pageLoaded();
        }
    }

    /** START OF PUBLIC API **/

    /**
     * Registers a callback for DOM ready. If DOM is already ready, the
     * callback is called immediately.
     * @param {Function} callback
     */
    function domReady(callback) {
        if (isPageLoaded) {
            callback(doc);
        } else {
            readyCalls.push(callback);
        }
        return domReady;
    }

    domReady.version = '2.0.1';

    /**
     * Loader Plugin API method
     */
    domReady.load = function (name, req, onLoad, config) {
        if (config.isBuild) {
            onLoad(null);
        } else {
            domReady(onLoad);
        }
    };

    /** END OF PUBLIC API **/

    return domReady;
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 30 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
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

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var $ = __webpack_require__(5);
	var _ = __webpack_require__(3);
	var Backbone = __webpack_require__(6);
	var Marionette = __webpack_require__(2);
	var Handlebars = __webpack_require__(4);
	var Radio = __webpack_require__(1);
	var ComposerView = __webpack_require__(15);
	var MessageView = __webpack_require__(44);
	var MessagesView = __webpack_require__(54);
	var ErrorView = __webpack_require__(10);
	var LoadingView = __webpack_require__(11);
	var MessageContentTemplate = __webpack_require__(64);

	var DetailView = Object.freeze({
		ERROR: -2,
		MESSAGE: 1,
		COMPOSER: 2
	});

	return Marionette.View.extend({
		template: Handlebars.compile(MessageContentTemplate),
		className: 'container',
		detailView: null,
		account: null,
		folder: null,
		searchQuery: null,
		composer: null,
		regions: {
			messages: '#mail-messages',
			message: '#mail-message'
		},
		initialize: function(options) {
			this.account = options.account;
			this.folder = options.folder;
			this.searchQuery = options.searchQuery;

			this.listenTo(Radio.ui, 'message:show', this.onShowMessage);
			this.listenTo(Radio.ui, 'message:error', this.onShowError);
			this.listenTo(Radio.ui, 'composer:show', this.onShowComposer);
			this.listenTo(Radio.ui, 'composer:leave', this.onComposerLeave);
			this.listenTo(Radio.keyboard, 'keyup', this.onKeyUp);

			// TODO: check whether this code is still needed
			this.listenTo(Radio.ui, 'composer:events:undelegate', function() {
				if (this.composer) {
					this.composer.undelegateEvents();
				}
			});
			// END TODO

			this.listenTo(Radio.ui, 'message:loading', this.onMessageLoading);
		},
		onRender: function() {
			this.showChildView('messages', new MessagesView({
				collection: this.folder.messages,
				searchQuery: this.searchQuery
			}));
		},
		onShowMessage: function(message, body) {
			// Temporarily disable new-message composer events
			Radio.ui.trigger('composer:events:undelegate');

			var messageModel = new Backbone.Model(body);
			this.showChildView('message', new MessageView({
				account: this.account,
				folder: this.folder,
				message: message,
				model: messageModel
			}));
			this.detailView = DetailView.MESSAGE;
			this.markMessageAsRead(message);
		},
		markMessageAsRead: function(message) {
			// The message is not actually displayed on mobile when calling onShowMessage()
			// on mobiles then, we shall not mark the email as read until the user opened it
			var isMobile = $(window).width() < 768;
			if (isMobile === false) {
				Radio.message.trigger('flag', message, 'unseen', false);
			}
		},
		onShowError: function(errorMessage) {
			this.showChildView('message', new ErrorView({
				text: errorMessage
			}));
			this.detailView = DetailView.ERROR;
		},
		onShowComposer: function(data) {
			$('.tooltip').remove();
			$('#mail_new_message').prop('disabled', true);
			$('#mail-message').removeClass('hidden-mobile');

			// setup composer view
			this.showChildView('message', new ComposerView({
				accounts: __webpack_require__(0).accounts,
				data: data
			}));
			this.detailView = DetailView.COMPOSER;
			this.composer = this.getChildView('message');

			if (data && data.hasHtmlBody) {
				Radio.ui.trigger('error:show', t('mail', 'Opening HTML drafts is not supported yet.'));
			}

			// focus 'to' field automatically on clicking New message button
			this.composer.focusTo();

			if (data && !_.isUndefined(data.currentTarget) && !_.isUndefined($(data.currentTarget).
				data().email)) {
				var to = '"' + $(data.currentTarget).
					data().label + '" <' + $(data.currentTarget).
					data().email + '>';
				this.composer.setTo(to);
				this.composer.focusSubject();
			}

			Radio.ui.trigger('messagesview:message:setactive', null);
		},
		onComposerLeave: function() {
			// TODO: refactor 'composer:leave' as it's buggy

			// Trigger only once
			if (this.detailView === DetailView.COMPOSER) {
				this.detailView = null;

				if (this.composer && this.composer.hasData === true) {
					if (this.composer.hasUnsavedChanges === true) {
						this.composer.saveDraft(function() {
							Radio.ui.trigger('notification:show', t('mail', 'Draft saved!'));
						});
					} else {
						Radio.ui.trigger('notification:show', t('mail', 'Draft saved!'));
					}
				}
			}
		},
		onMessageLoading: function(text) {
			this.showChildView('message', new LoadingView({
				text: text
			}));
		},
		onKeyUp: function(event, key) {
			var message;
			var state;
			switch (key) {
				case 46:
					// Mimic a client clicking the delete button for the currently active message.
					$('.mail-message-summary.active .icon-delete.action.delete').click();
					break;
				case 39:
				case 74:
					// right arrow or 'j' -> next message
					event.preventDefault();
					Radio.message.trigger('messagesview:message:next');
					break;
				case 37:
				case 75:
					// left arrow or 'k' -> previous message
					event.preventDefault();
					Radio.message.trigger('messagesview:message:prev');
					break;
				case 67:
					// 'c' -> show new message composer
					event.preventDefault();
					Radio.ui.trigger('composer:show');
					break;
				case 82:
					// 'r' -> refresh list of messages
					event.preventDefault();
					Radio.ui.trigger('messagesview:messages:update');
					break;
				case 83:
					// 's' -> toggle star
					event.preventDefault();
					message = __webpack_require__(0).currentMessage;
					if (message) {
						state = message.get('flags').get('flagged');
						Radio.message.trigger('flag', message, 'flagged', !state);
					}
					break;
				case 85:
					// 'u' -> toggle unread
					event.preventDefault();
					message = __webpack_require__(0).currentMessage;
					if (message) {
						state = message.get('flags').get('unseen');
						Radio.message.trigger('flag', message, 'unseen', !state);
					}
					break;
			}
		}
	});
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 31 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Luc Calaresu <dev@calaresu.com>
 * @copyright Christoph Wurst 2015
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var _ = __webpack_require__(3);
	var Marionette = __webpack_require__(2);
	var OC = __webpack_require__(7);
	var Handlebars = __webpack_require__(4);
	var Radio = __webpack_require__(1);
	var AttachmentView = __webpack_require__(32);
	var AttachmentsTemplate = __webpack_require__(34);
	var LocalAttachment = __webpack_require__(35);

	return Marionette.CompositeView.extend({
		collection: null,
		childView: AttachmentView,
		childViewContainer: 'ul',
		template: Handlebars.compile(AttachmentsTemplate),
		ui: {
			'fileInput': '#local-attachments'
		},
		events: {
			'click #add-cloud-attachment': 'addCloudAttachment',
			'click #add-local-attachment': 'addLocalAttachment',
			'change @ui.fileInput': 'onLocalAttachmentsChanged'
		},
		initialize: function(options) {
			this.collection = options.collection;
		},

		/**
		 * Click on 'Add from Files'
		 */
		addCloudAttachment: function() {
			var title = t('mail', 'Choose a file to add as attachment');
			OC.dialogs.filepicker(title, _.bind(this.onCloudFileSelected, this));
		},
		onCloudFileSelected: function(path) {
			this.collection.add({
				fileName: path
			});
		},

		/**
		 * Click on 'Add Attachment'
		 */
		addLocalAttachment: function() {
			/* reset the fileInput value to allow sending the same file several */
			/* times. e.g. if the previous upload failed. */
			this.ui.fileInput.val('');
			this.ui.fileInput.click();
		},
		onLocalAttachmentsChanged: function(event) {
			var files = event.target.files;
			for (var i = 0; i < files.length; i++) {
				var file = files[i];
				this.uploadLocalAttachment(file);
			}
		},

		uploadLocalAttachment: function(file) {
			// TODO check file size?
			var attachment = new LocalAttachment({
				fileName: file.name
			});

			Radio.attachment.request('upload:local', file, attachment)
				.then(function(ret) {
					Radio.attachment.request('upload:finished', attachment, ret);
				})
				.catch(function() {
					Radio.attachment.request('upload:finished', attachment);
					var errorMsg = t('mail',
						'An error occurred while uploading {fname}', {fname: file.name}
					);
					Radio.ui.trigger('error:show', errorMsg);
				});

			this.collection.add(attachment);
		}
	});
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 32 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Luc Calaresu <dev@calaresu.com>
 * @copyright Christoph Wurst 2015
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Handlebars = __webpack_require__(4);
	var Marionette = __webpack_require__(2);
	var Radio = __webpack_require__(1);
	var AttachmentTemplate = __webpack_require__(33);

	return Marionette.View.extend({
		tagName: 'li',
		template: Handlebars.compile(AttachmentTemplate),
		ui: {
			attachmentName: '.new-message-attachment-name'
		},
		events: {
			'click .icon-delete': 'removeAttachment'
		},
		modelEvents: {
			'change:progress': 'onProgress',
			'change:uploadStatus': 'onUploadStatus'
		},
		onRender: function() {
			var uploadRequest = this.model.get('uploadRequest');
			if (uploadRequest) {
				/* If upload, init the progressbar with the initial and max value  */
				this.ui.attachmentName.progressbar({value: 0, max: 1});
				// Remove two jQuery styling classes that make it ugly
				// and add a blue text while uploading
				this.ui.attachmentName
					.removeClass('ui-progressbar')
					.removeClass('ui-widget-content')
					.addClass('upload-ongoing');
			}
		},

		/**
		 * Called when the user clicked on the wastebasket.
		 */
		removeAttachment: function() {
			/* If we are trying to delete a still-uploading attachment, */
			/* we have to abort the request first */
			Radio.attachment.request('upload:abort', this.model);
		},

		/**
		 * Triggered when the attachment progress value changed
		 */
		onProgress: function() {
			/* Update the ProgressBar with the new model value */
			var progressValue = this.model.get('progress');
			this.ui.attachmentName.progressbar('option', 'value', progressValue);
		},

		/**
		 * Triggered when the attachment upload status has changed
		 */
		onUploadStatus: function() {
			switch (this.model.get('uploadStatus')) {
				case 1:     // uploading
					this.ui.attachmentName.addClass('upload-ongoing');
					break;
				case 2:     // error
					/* An error occurred, we make the filename and the progressbar red */
					this.ui.attachmentName
						.removeClass('upload-ongoing')
						.addClass('upload-warning');
					break;
				case 3:     // success
					/* remove the 'ongoing' class  */
					this.ui.attachmentName
						.removeClass('upload-ongoing')
						.removeClass('upload-warning');
					/* If everything went well, we just fade out the progressbar */
					this.ui.attachmentName.find('.ui-progressbar-value').fadeOut();
					break;
			}
		}
	});
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 33 */
/***/ (function(module, exports) {

module.exports = "module.exports = \"<div class=\\\"new-message-attachment-name\\\">{{displayName}}</div>\\n<div class=\\\"new-message-attachments-action svg icon-delete\\\"></div>\\n\""

/***/ }),
/* 34 */
/***/ (function(module, exports) {

module.exports = "module.exports = \"<ul></ul>\\n<button type=\\\"button\\\" id=\\\"add-local-attachment\\\" style=\\\"display: inline-block;\\\">\\n  <span class=\\\"icon-upload\\\"/> {{ t 'Add attachment' }}\\n</button>\\n<button type=\\\"button\\\" id=\\\"add-cloud-attachment\\\" style=\\\"display: inline-block;\\\">\\n  <span class=\\\"icon-edit\\\"/> {{ t 'Add from Files' }}\\n</button>\\n<input type=\\\"file\\\" multiple id=\\\"local-attachments\\\" style=\\\"display: none;\\\">\\n\""

/***/ }),
/* 35 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Luc Calaresu <dev@calaresu.com>
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Attachment = __webpack_require__(17);

	var LocalAttachment = Attachment.extend({

		defaults: {
			progress: 0,
			uploadStatus: 0,  /* 0=pending, 1=ongoing, 2=error, 3=success */
			isLocal: true
		},

		/**
		 * @param {Event} evt
		 * @returns {undefined}
		 */
		onProgress: function(evt) {
			if (evt.lengthComputable) {
				this.set('uploadStatus', 1);
				this.set('progress', evt.loaded / evt.total);
			}
		}
	});

	return LocalAttachment;
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 36 */
/***/ (function(module, exports) {

module.exports = "module.exports = \"<div class=\\\"message-composer\\\">\\n\\t<select class=\\\"mail-account\\\">\\n\\t\\t{{#each aliases}}\\n\\t\\t<option value=\\\"{{id}}\\\">{{ t 'from' }} {{name}} &lt;{{emailAddress}}&gt;</option>\\n\\t\\t{{/each}}\\n\\t</select>\\n\\t<div class=\\\"composer-fields\\\">\\n\\t\\t<a href=\\\"#\\\" class=\\\"composer-cc-bcc-toggle transparency\\n\\t\\t\\t{{#ifHasCC replyCc replyCcList}}\\n\\t\\t\\thidden\\n\\t\\t\\t{{/ifHasCC}}\\\">{{ t '+ cc/bcc' }}</a>\\n\\t\\t<input type=\\\"text\\\" name=\\\"to\\\"\\n\\t\\t    {{#if replyToList}}\\n\\t\\t    value=\\\"{{printAddressListPlain replyToList}}\\\"\\n\\t\\t    {{else}}\\n\\t\\t    value=\\\"{{to}}\\\"\\n\\t\\t    {{/if}}\\n\\t\\t    class=\\\"to recipient-autocomplete\\\" />\\n\\t\\t<label class=\\\"to-label\\\" for=\\\"to\\\" class=\\\"transparency\\\">{{ t 'to' }}</label>\\n\\t\\t<div class=\\\"composer-cc-bcc\\n\\t\\t    {{#unlessHasCC replyCc replyCcList}}\\n\\t\\t    hidden\\n\\t\\t    {{/unlessHasCC}}\\\">\\n\\t\\t\\t<input type=\\\"text\\\" name=\\\"cc\\\"\\n\\t\\t\\t    {{#if replyCc}}\\n\\t\\t\\t    value=\\\"{{replyCc}}\\\"\\n\\t\\t\\t    {{else}}\\n\\t\\t\\t\\t{{#if replyCcList}}\\n\\t\\t\\t\\tvalue=\\\"{{printAddressListPlain replyCcList}}\\\"\\n\\t\\t\\t\\t{{else}}\\n\\t\\t\\t\\tvalue=\\\"{{cc}}\\\"\\n\\t\\t\\t\\t{{/if}}\\n\\t\\t\\t    {{/if}}\\n\\t\\t\\t    class=\\\"cc recipient-autocomplete\\\" />\\n\\t\\t\\t<label for=\\\"cc\\\" class=\\\"cc-label transparency\\\">{{ t 'cc' }}</label>\\n\\t\\t\\t<input type=\\\"text\\\" name=\\\"bcc\\\" value=\\\"{{bcc}}\\\" class=\\\"bcc recipient-autocomplete\\\" />\\n\\t\\t\\t<label for=\\\"bcc\\\" class=\\\"bcc-label transparency\\\">{{ t 'bcc' }}</label>\\n\\t\\t</div>\\n\\t\\t{{#unless isReply}}\\n\\t\\t<input type=\\\"text\\\" name=\\\"subject\\\" value=\\\"{{subject}}\\\" class=\\\"subject\\\" autocomplete=\\\"off\\\"\\n\\t\\t\\tplaceholder=\\\"{{ t 'Subject' }}\\\" />\\n\\t\\t{{/unless}}\\n\\t\\t<textarea name=\\\"body\\\" class=\\\"message-body\\n\\t\\t\\t\\t\\t{{#if isReply}} reply{{/if}}\\\"\\n\\t\\t\\tplaceholder=\\\"{{ t 'Message …' }}\\\">{{message}}</textarea>\\n\\t</div>\\n\\t<div class=\\\"submit-message-wrapper\\\">\\n\\t\\t<input class=\\\"submit-message send primary\\\" type=\\\"submit\\\" value=\\\"{{submitButtonTitle}}\\\" disabled>\\n\\t\\t<div class=\\\"submit-message-wrapper-inside\\\" ></div>\\n\\t</div>\\n\\t<div class=\\\"new-message-attachments\\\">\\n\\t</div>\\n</div>\\n\""

/***/ }),
/* 37 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015, 2016
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Backbone = __webpack_require__(6);
	var Account = __webpack_require__(18);
	var OC = __webpack_require__(7);

	/**
	 * @class AccountCollection
	 */
	var AccountCollection = Backbone.Collection.extend({
		model: Account,
		url: function() {
			return OC.generateUrl('apps/mail/accounts');
		},
		comparator: function(account) {
			return account.get('accountId');
		}
	});

	return AccountCollection;
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 38 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var _ = __webpack_require__(3);
	var Backbone = __webpack_require__(6);

	/**
	 * @class Folder
	 */
	var Folder = Backbone.Model.extend({
		messages: undefined,
		account: undefined,
		folder: undefined,
		folders: undefined,
		defaults: {
			open: false,
			folders: [],
			messagesLoaded: false
		},

		initialize: function() {
			var FolderCollection = __webpack_require__(19);
			var MessageCollection = __webpack_require__(20);
			var UnifiedMessageCollection = __webpack_require__(41);
			this.account = this.get('account');
			this.unset('account');
			this.folders = new FolderCollection(this.get('folders') || []);
			this.folders.forEach(_.bind(function(folder) {
				folder.account = this.account;
			}, this));
			this.unset('folders');
			if (this.account && this.account.get('isUnified') === true) {
				this.messages = new UnifiedMessageCollection();
			} else {
				this.messages = new MessageCollection();
			}
		},

		toggleOpen: function() {
			this.set({open: !this.get('open')});
		},

		/**
		 * @param {Message} message
		 * @returns {undefined}
		 */
		addMessage: function(message) {
			if (this.account.id !== -1) {
				// Non-unified folder messages should keep their source folder
				message.folder = this;
			}
			message = this.messages.add(message);
			if (this.account.id === -1) {
				message.set('unifiedId', this.messages.getUnifiedId(message));
			}
			return message;
		},

		/**
		 * @param {Array<Message>} messages
		 * @returns {undefined}
		 */
		addMessages: function(messages) {
			var _this = this;
			return _.map(messages, _this.addMessage, this);
		},

		/**
		 * @param {Folder} folder
		 * @returns {undefined}
		 */

		addFolder: function(folder) {
			folder = this.folders.add(folder);
			folder.account = this.account;
		},

		toJSON: function() {
			var data = Backbone.Model.prototype.toJSON.call(this);
			if (!data.id) {
				data.id = this.cid;
			}
			return data;
		}
	});

	return Folder;
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 39 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Backbone = __webpack_require__(6);
	var MessageFlags = __webpack_require__(40);

	/**
	 * @class Message
	 */
	var Message = Backbone.Model.extend({
		folder: undefined,
		defaults: {
			flags: [],
			active: false
		},
		initialize: function() {
			this.set('flags', new MessageFlags(this.get('flags')));
			if (this.get('folder')) {
				// Folder should be a simple property
				this.folder = this.get('folder');
				this.unset('folder');
			}
			this.listenTo(this.get('flags'), 'change', this._transformEvent);
		},
		_transformEvent: function() {
			this.trigger('change');
			this.trigger('change:flags', this);
		},
		toJSON: function() {
			var data = Backbone.Model.prototype.toJSON.call(this);
			if (data.flags && data.flags.toJSON) {
				data.flags = data.flags.toJSON();
			}
			if (!data.id) {
				data.id = this.cid;
			}
			return data;
		}
	});

	return Message;
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 40 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Backbone = __webpack_require__(6);

	/**
	 * @class MessageFlags
	 */
	var MessageFlags = Backbone.Model.extend({
		defaults: {
			answered: false
		}
	});

	return MessageFlags;
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 41 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2017
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var MessageCollection = __webpack_require__(20);

	/**
	 * @class UnifiedMessageCollection
	 */
	var UnifiedMessageCollection = MessageCollection.extend({

		modelId: function(attrs) {
			return attrs.unifiedId;
		},

		getUnifiedId: function(message) {
			return message.id + '-' + message.folder.id + '-' + message.folder.account.id;
		}

	});

	return UnifiedMessageCollection;
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 42 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Tahaa Karim <tahaalibra@gmail.com>
 * @copyright Tahaa Karim  2016
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Backbone = __webpack_require__(6);
	var Alias = __webpack_require__(43);

	/**
	 * @class AliasesCollection
	 */
	var AliasesCollection = Backbone.Collection.extend({
		model: Alias
	});

	return AliasesCollection;
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 43 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Tahaa Karim <tahaalibra@gmail.com>
 * @copyright Tahaa Karim 2016
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Backbone = __webpack_require__(6);

	/**
	 * @class Alias
	 */
	var Alias = Backbone.Model.extend({
		defaults: {
		},
		initialize: function() {

		},
		toJSON: function() {
			var data = Backbone.Model.prototype.toJSON.call(this);
			if (data.alias && data.alias.toJSON) {
				data.alias = data.alias.toJSON();
			}
			if (!data.id) {
				data.id = this.cid;
			}
			return data;
		}
	});

	return Alias;
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 44 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/* global adjustControlsWidth */

/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2016
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Marionette = __webpack_require__(2);
	var Handlebars = __webpack_require__(4);
	var _ = __webpack_require__(3);
	var $ = __webpack_require__(5);
	var Attachments = __webpack_require__(16);
	var HtmlHelper = __webpack_require__(45);
	var ComposerView = __webpack_require__(15);
	var MessageAttachmentsView = __webpack_require__(46);
	var MessageTemplate = __webpack_require__(53);

	return Marionette.View.extend({
		template: Handlebars.compile(MessageTemplate),
		className: 'mail-message-container',
		message: null,
		messageBody: null,
		reply: null,
		account: null,
		folder: null,
		ui: {
			messageIframe: 'iframe'
		},
		regions: {
			replyComposer: '#reply-composer',
			attachments: '.mail-message-attachments'
		},
		initialize: function(options) {
			this.account = options.account;
			this.folder = options.folder;
			this.message = options.message;
			this.messageBody = options.model;
			this.reply = {
				replyToList: this.messageBody.get('replyToList'),
				replyCc: this.messageBody.get('replyCc'),
				toEmail: this.messageBody.get('toEmail'),
				replyCcList: this.messageBody.get('replyCcList'),
				body: ''
			};

			// Add body content to inline reply (text mails)
			if (!this.messageBody.get('hasHtmlBody')) {
				var date = new Date(this.messageBody.get('dateIso'));
				var minutes = date.getMinutes();
				var text = HtmlHelper.htmlToText(this.messageBody.get('body'));

				this.reply.body = '\n\n\n\n' +
					this.messageBody.get('from') + ' – ' +
					$.datepicker.formatDate('D, d. MM yy ', date) +
					date.getHours() + ':' + (minutes < 10 ? '0' : '') + minutes + '\n> ' +
					text.replace(/\n/g, '\n> ');
			}

			// Save current messages's content for later use (forward)
			if (!this.messageBody.get('hasHtmlBody')) {
				__webpack_require__(0).currentMessageBody = this.messageBody.get('body');
			}
			__webpack_require__(0).currentMessageSubject = this.messageBody.get('subject');

			// Render the message body
			adjustControlsWidth();

			// Hide forward button until the message has finished loading
			if (this.messageBody.get('hasHtmlBody')) {
				$('#forward-button').hide();
			}
		},
		onIframeLoad: function() {
			// Expand height to not have two scrollbars
			this.getUI('messageIframe').height(this.getUI('messageIframe').contents().find('html').height() + 20);
			// Fix styling
			this.getUI('messageIframe').contents().find('body').css({
				'margin': '0',
				'font-weight': 'normal',
				'font-size': '.8em',
				'line-height': '1.6em',
				'font-family': '"Open Sans", Frutiger, Calibri, "Myriad Pro", Myriad, sans-serif',
				'color': '#000'
			});
			// Fix font when different font is forced
			this.getUI('messageIframe').contents().find('font').prop({
				'face': 'Open Sans',
				'color': '#000'
			});
			this.getUI('messageIframe').contents().find('.moz-text-flowed').css({
				'font-family': 'inherit',
				'font-size': 'inherit'
			});
			// Expand height again after rendering to account for new size
			this.getUI('messageIframe').height(this.getUI('messageIframe').contents().find('html').height() + 20);
			// Grey out previous replies
			this.getUI('messageIframe').contents().find('blockquote').css({
				'color': '#888'
			});
			// Remove spinner when loading finished
			this.getUI('messageIframe').parent().removeClass('icon-loading');

			// Does the html mail have blocked images?
			var hasBlockedImages = false;
			if (this.getUI('messageIframe').contents().
				find('[data-original-src],[data-original-style]').length) {
				hasBlockedImages = true;
			}

			// Show/hide button to load images
			if (hasBlockedImages) {
				$('#show-images-text').show();
			} else {
				$('#show-images-text').hide();
			}

			// Add body content to inline reply (html mails)
			var text = this.getUI('messageIframe').contents().find('body').html();
			text = HtmlHelper.htmlToText(text);
			var date = new Date(this.messageBody.get('dateIso'));
			this.getChildView('replyComposer').setReplyBody(this.messageBody.get('from'), date, text);

			// Safe current mesages's content for later use (forward)
			__webpack_require__(0).currentMessageBody = text;

			// Show forward button
			this.$('#forward-button').show();
		},
		onRender: function() {
			this.getUI('messageIframe').on('load', _.bind(this.onIframeLoad, this));

			this.showChildView('attachments', new MessageAttachmentsView({
				collection: new Attachments(this.messageBody.get('attachments')),
				message: this.model
			}));

			// setup reply composer view
			this.showChildView('replyComposer', new ComposerView({
				type: 'reply',
				accounts: __webpack_require__(0).accounts,
				account: this.account,
				folder: this.folder,
				repliedMessage: this.message,
				data: this.reply
			}));
		}
	});
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 45 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2016
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function() {
	'use strict';

	function htmlToText(html) {
		var breakToken = '__break_token__';
		// Preserve line breaks
		html = html.replace(/<br>/g, breakToken);
		html = html.replace(/<br\/>/g, breakToken);

		// Add <br> break after each closing div, p, li to preserve visual
		// line breaks for replies
		html = html.replace(/(<\/div>)([^$]?)/g, '\$1' + breakToken + '\$2');
		html = html.replace(/(<\/p>)([^$]?)/g, '\$1' + breakToken + '\$2');
		html = html.replace(/(<\/li>)([^$]?)/g, '\$1' + breakToken + '\$2');

		var tmp = $('<div>');
		tmp.html(html);
		var text = tmp.text();

		// Finally, replace tokens with line breaks
		text = text.replace(new RegExp(breakToken, 'g'), '\n');
		return text.trim();
	}

	return {
		htmlToText: htmlToText
	};
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 46 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
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

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Handlebars = __webpack_require__(4);
	var Marionette = __webpack_require__(2);
	var MessageController = __webpack_require__(9);
	var AttachmentView = __webpack_require__(47);
	var AttachmentsTemplate = __webpack_require__(52);

	/**
	 * @type MessageAttachmentsView
	 */
	var MessageAttachmentsView = Marionette.CompositeView.extend({
		/**
		 * @lends Marionette.CompositeView
		 */
		template: Handlebars.compile(AttachmentsTemplate),
		ui: {
			'saveAllToCloud': '.attachments-save-to-cloud'
		},
		events: {
			'click @ui.saveAllToCloud': '_onSaveAllToCloud'
		},
		templateContext: function() {
			return {
				moreThanOne: this.collection.length > 1
			};
		},
		childView: AttachmentView,
		childViewContainer: '.attachments',
		initialize: function(options) {
			this.message = options.message;
		},
		_onSaveAllToCloud: function(e) {
			e.preventDefault();

			// TODO: 'message' should be a property of this attachment model
			// TODO: 'folder' should be a property of the message model and so on
			var account = __webpack_require__(0).currentAccount;
			var folder = __webpack_require__(0).currentFolder;
			var messageId = this.message.get('id');
			// Loading feedback
			this.getUI('saveAllToCloud').removeClass('icon-folder')
				.addClass('icon-loading-small')
				.prop('disabled', true);

			var _this = this;
			MessageController.saveAttachmentsToFiles(account, folder, messageId)
				.catch(console.error.bind(this)).then(function() {
				// Remove loading feedback again
				_this.getUI('saveAllToCloud').addClass('icon-folder')
					.removeClass('icon-loading-small')
					.prop('disabled', false);
			});
		}
	});

	return MessageAttachmentsView;
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 47 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
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

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var $ = __webpack_require__(5);
	var Handlebars = __webpack_require__(4);
	var Marionette = __webpack_require__(2);
	var Radio = __webpack_require__(1);
	var MessageController = __webpack_require__(9);
	var CalendarsPopoverView = __webpack_require__(48);
	var MessageAttachmentTemplate = __webpack_require__(51);

	/**
	 * @class MessageAttachmentView
	 */
	var MessageAttachmentView = Marionette.View.extend({
		template: Handlebars.compile(MessageAttachmentTemplate),
		ui: {
			'downloadButton': '.attachment-download',
			'saveToCloudButton': '.attachment-save-to-cloud',
			'importCalendarEventButton': '.attachment-import.calendar',
			'attachmentImportPopover': '.attachment-import-popover'
		},
		events: {
			'click': '_onClick',
			'click @ui.saveToCloudButton': '_onSaveToCloud',
			'click @ui.importCalendarEventButton': '_onImportCalendarEvent'
		},
		initialize: function() {
			this.listenTo(Radio.ui, 'document:click', this._closeImportPopover);
		},
		_onClick: function(e) {
			if (!e.isDefaultPrevented()) {
				var $target = $(e.target);
				if ($target.hasClass('select-calendar')) {
					var url = $target.data('calendar-url');
					this._uploadToCalendar(url);
					return;
				}

				e.preventDefault();
				window.open(this.model.get('downloadUrl'));
				window.focus();
			}
		},
		_onSaveToCloud: function(e) {
			e.preventDefault();
			// TODO: 'message' should be a property of this attachment model
			// TODO: 'folder' should be a property of the message model and so on
			var account = __webpack_require__(0).currentAccount;
			var folder = __webpack_require__(0).currentFolder;
			var messageId = this.model.get('messageId');
			var attachmentId = this.model.get('id');
			// Loading feedback
			this.getUI('saveToCloudButton').removeClass('icon-folder')
				.addClass('icon-loading-small')
				.prop('disabled', true);

			var _this = this;
			MessageController.saveAttachmentToFiles(account, folder, messageId, attachmentId)
				.catch(console.error.bind(this)).then(function() {
				// Remove loading feedback again
				_this.getUI('saveToCloudButton').addClass('icon-folder')
					.removeClass('icon-loading-small')
					.prop('disabled', false);
			});
		},
		_onImportCalendarEvent: function(e) {
			e.preventDefault();

			this.getUI('importCalendarEventButton')
				.removeClass('icon-add')
				.addClass('icon-loading-small');

			var _this = this;
			Radio.dav.request('calendars').then(function(calendars) {
				if (calendars.length > 0) {
					_this.getUI('attachmentImportPopover').addClass('open');
					var calendarsView = new CalendarsPopoverView({
						collection: calendars
					});
					calendarsView.render();
					_this.getUI('attachmentImportPopover').html(calendarsView.$el);
				} else {
					Radio.ui.trigger('error:show', t('mail', 'No writable calendars found'));
				}
			}).catch(console.error.bind(this)).then(function() {
				_this.getUI('importCalendarEventButton')
					.removeClass('icon-loading-small')
					.addClass('icon-add');
			});
		},
		_uploadToCalendar: function(url) {
			this._closeImportPopover();
			this.getUI('importCalendarEventButton')
				.removeClass('icon-add')
				.addClass('icon-loading-small');

			var downloadUrl = this.model.get('downloadUrl');
			var _this = this;
			Radio.message.request('attachment:download', downloadUrl).then(function(content) {
				return Radio.dav.request('calendar:import', url, content).catch(function() {
					Radio.ui.trigger('error:show', t('mail', 'Error while importing the calendar event'));
				});
			}).then(function() {
				_this.getUI('importCalendarEventButton')
					.removeClass('icon-loading-small')
					.addClass('icon-add');
			}).catch(function() {
				Radio.ui.trigger('error:show', t('mail', 'Error while downloading calendar event'));
				_this.getUI('importCalendarEventButton')
					.removeClass('icon-loading-small')
					.addClass('icon-add');
			});
		},
		_closeImportPopover: function(e) {
			if (_.isUndefined(e)) {
				this.getUI('attachmentImportPopover').removeClass('open');
				return;
			}
			var $target = $(e.target);
			if (this.$el.find($target).length === 0) {
				this.getUI('attachmentImportPopover').removeClass('open');
			}
		}
	});

	return MessageAttachmentView;
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 48 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
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

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Marionette = __webpack_require__(2);
	var CalendarView = __webpack_require__(49);

	return Marionette.CollectionView.extend({
		childView: CalendarView,
		tagName: 'ul'
	});
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 49 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
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

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Marionette = __webpack_require__(2);
	var Handlebars = __webpack_require__(4);
	var CalendarTemplate = __webpack_require__(50);

	return Marionette.View.extend({
		template: Handlebars.compile(CalendarTemplate),
		tagName: 'li'
	});
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 50 */
/***/ (function(module, exports) {

module.exports = "module.exports = \"<a class=\\\"select-calendar\\\" data-calendar-url=\\\"{{url}}\\\">{{displayname}}</a>\""

/***/ }),
/* 51 */
/***/ (function(module, exports) {

module.exports = "module.exports = \"{{#if isImage}}\\n<img class=\\\"mail-attached-image\\\" src=\\\"{{downloadUrl}}\\\">\\n<br>\\n{{/if}}\\n<img class=\\\"attachment-icon\\\" src=\\\"{{mimeUrl}}\\\" />\\n<span class=\\\"attachment-name\\\" title=\\\"{{fileName}} ({{humanFileSize size}})\\\">{{fileName}} <span class=\\\"attachment-size\\\">({{humanFileSize size}})</span></span>\\n{{#if isCalendarEvent}}\\n<button class=\\\"button icon-add attachment-import calendar\\\" title=\\\"{{ t 'Import into calendar' }}\\\"></button>\\n{{/if}}\\n<button class=\\\"button icon-download attachment-download\\\" title=\\\"{{ t 'Download attachment' }}\\\"></button>\\n<button class=\\\"icon-folder attachment-save-to-cloud\\\" title=\\\"{{ t 'Save to Files' }}\\\"></button>\\n<div class=\\\"popovermenu bubble attachment-import-popover hidden\\\"></div>\""

/***/ }),
/* 52 */
/***/ (function(module, exports) {

module.exports = "module.exports = \"<div class=\\\"attachments\\\">\\n\\t\\n</div>\\n{{#if moreThanOne}}\\n<p>\\n\\t<button class=\\\"icon-folder attachments-save-to-cloud\\\">{{ t 'Save all to Files' }}</button>\\n</p>\\n{{/if}}\""

/***/ }),
/* 53 */
/***/ (function(module, exports) {

module.exports = "module.exports = \"<div id=\\\"mail-message-close\\\" class=\\\"icon-close\\\"></div>\\n<div id=\\\"mail-message-header\\\" class=\\\"section\\\">\\n\\t<h2 title=\\\"{{subject}}\\\">{{subject}}</h2>\\n\\t<p class=\\\"transparency\\\">\\n\\t\\t{{printAddressList fromList}}\\n\\t\\t{{#if toList}}\\n\\t\\t{{ t 'to' }}\\n\\t\\t{{printAddressList toList}}\\n\\t\\t{{/if}}\\n\\t\\t{{#if ccList}}\\n\\t\\t({{ t 'cc' }} {{printAddressList ccList}})\\n\\t\\t{{/if}}\\n\\t</p>\\n</div>\\n<div class=\\\"mail-message-body\\\">\\n\\t<div id=\\\"mail-content\\\">\\n\\t\\t{{#if hasHtmlBody}}\\n\\t\\t<div id=\\\"show-images-text\\\">\\n\\t\\t\\t{{ t 'The images have been blocked to protect your privacy.' }}\\n\\t\\t\\t<button id=\\\"show-images-button\\\">{{ t 'Show images from this sender' }}</button>\\n\\t\\t</div>\\n\\t\\t<div class=\\\"icon-loading\\\">\\n\\t\\t\\t<iframe src=\\\"{{htmlBodyUrl}}\\\" seamless>\\n\\t\\t\\t</iframe>\\n\\t\\t</div>\\n\\t\\t{{else}}\\n\\t\\t{{{body}}}\\n\\t\\t{{/if}}\\n\\t</div>\\n\\t{{#if signature}}\\n\\t<div class=\\\"mail-signature\\\">\\n\\t\\t{{{signature}}}\\n\\t</div>\\n\\t{{/if}}\\n\\n\\t<div class=\\\"mail-message-attachments\\\"></div>\\n\\t<div id=\\\"reply-composer\\\"></div>\\n\\t<input type=\\\"button\\\" id=\\\"forward-button\\\" value=\\\"{{ t 'Forward' }}\\\">\\n</div>\\n\""

/***/ }),
/* 54 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
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

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var _ = __webpack_require__(3);
	var $ = __webpack_require__(5);
	var Marionette = __webpack_require__(2);
	var Handlebars = __webpack_require__(4);
	var Radio = __webpack_require__(1);
	var MessagesItemView = __webpack_require__(55);
	var MessageListTemplate = __webpack_require__(57);
	var EmptyFolderView = __webpack_require__(58);
	var NoSearchResultView = __webpack_require__(60);

	return Marionette.CompositeView.extend({
		collection: null,
		$scrollContainer: undefined,
		childView: MessagesItemView,
		childViewContainer: '#mail-message-list',
		template: Handlebars.compile(MessageListTemplate),
		currentMessage: null,
		searchQuery: null,
		loadingMore: false,

		/**
		 * @private
		 * @type {bool}
		 */
		_reloaded: false,

		events: {
			DOMMouseScroll: 'onWheel',
			mousewheel: 'onWheel'
		},
		initialize: function(options) {
			this.searchQuery = options.searchQuery;

			var _this = this;
			this.on('dom:refresh', this._bindScrollEvents);
			Radio.ui.reply('messagesview:collection', function() {
				return _this.collection;
			});
			this.listenTo(Radio.ui, 'messagesview:messages:update', this.refresh);
			this.listenTo(Radio.ui, 'messagesview:filter', this.filterCurrentMailbox);
			this.listenTo(Radio.ui, 'messagesview:message:setactive', this.setActiveMessage);
			this.listenTo(Radio.message, 'messagesview:message:next', this.selectNextMessage);
			this.listenTo(Radio.message, 'messagesview:message:prev', this.selectPreviousMessage);
		},
		_bindScrollEvents: function() {
			this.$scrollContainer = this.$el.parent();
			this.$scrollContainer.scroll(_.bind(this.onScroll, this));
		},
		emptyView: function() {
			if (this.searchQuery && this.searchQuery !== '') {
				return NoSearchResultView;
			} else {
				return EmptyFolderView;
			}
		},
		emptyViewOptions: function() {
			return {
				searchQuery: this.searchQuery
			};
		},
		/**
		 * Set active class for current message and remove it from old one
		 *
		 * @param {Message} message
		 */
		setActiveMessage: function(message) {
			if (this.currentMessage !== null) {
				this.currentMessage.set('active', false);
			}

			this.currentMessage = message;
			if (message !== null) {
				message.set('active', true);
			}

			__webpack_require__(0).currentMessage = message;
			Radio.ui.trigger('title:update');
		},
		selectNextMessage: function() {
			if (this.currentMessage === null) {
				return;
			}

			var message = this.collection.get(this.currentMessage);
			if (message === null) {
				return;
			}

			if (this.collection.indexOf(message) === (this.collection.length - 1)) {
				// Last message, nothing to do
				return;
			}

			var nextMessage = this.collection.at(this.collection.indexOf(message) + 1);
			if (nextMessage) {
				var folder = nextMessage.folder;
				var account = folder.account;
				Radio.message.trigger('load', account, folder, nextMessage, {
					force: true
				});
			}
		},
		selectPreviousMessage: function() {
			if (this.currentMessage === null) {
				return;
			}

			var message = this.collection.get(this.currentMessage);
			if (message === null) {
				return;
			}

			if (this.collection.indexOf(message) === 0) {
				// First message, nothing to do
				return;
			}

			var previousMessage = this.collection.at(this.collection.indexOf(message) - 1);
			if (previousMessage) {
				var folder = previousMessage.folder;
				var account = folder.account;
				Radio.message.trigger('load', account, folder, previousMessage, {
					force: true
				});
			}
		},
		refresh: function() {
			if (!__webpack_require__(0).currentAccount) {
				return;
			}
			if (!__webpack_require__(0).currentFolder) {
				return;
			}
			this._syncMessages();
		},
		onScroll: function() {
			if (this._reloaded) {
				this._reloaded = false;
				return;
			}
			if (this.loadingMore === true) {
				// Ignore events until loading has finished
				return;
			}
			if (this.$scrollContainer.scrollTop() === 0) {
				// Scrolled to top -> refresh
				this.loadingMore = true;
				this._syncMessages();
				return;
			}
			if ((this.$scrollContainer.scrollTop() + this.$scrollContainer.height()) > (this.$el.height() - 150)) {
				// Scrolled all the way down -> load more
				this.loadingMore = true;
				this._loadNextMessages();
				return;
			}
		},
		onWheel: function(event) {
			if (event.originalEvent.wheelDelta && event.originalEvent.wheelDelta > 0) {
				// Scrolling up in non-FF browsers
				this.onScroll();
			} else if (event.originalEvent.detail && event.originalEvent.detail < 0) {
				// Scrolling up in FF
				this.onScroll();
			}
		},
		filterCurrentMailbox: function(query) {
			this.filterCriteria = {
				text: query
			};
			this._syncMessages();
		},

		/**
		 * @private
		 * @returns {Promise}
		 */
		_loadNextMessages: function() {
			// Add loading feedback
			this.$('#load-more-mail-messages').addClass('icon-loading-small');

			var account = __webpack_require__(0).currentAccount;
			var folder = __webpack_require__(0).currentFolder;
			return Radio.message.request('next-page', account, folder, {
				filter: this.searchQuery || ''
			}).then(function() {
				Radio.ui.trigger('messagesview:message:setactive', __webpack_require__(0).currentMessage);
			}, function() {
				Radio.ui.trigger('error:show', t('mail', 'Error while loading messages.'));
			}).then(function() {
				// Remove loading feedback again
				this.$('#load-more-mail-messages').removeClass('icon-loading-small');
				this.loadingMore = false;
				// Reload scrolls the list to the top, hence a unwanted
				// scroll event is fired, which we want to ignore
				this._reloaded = false;
			}.bind(this), console.error.bind(this));
		},

		/**
		 * @private
		 * @returns {Promise}
		 */
		_syncMessages: function() {
			// Loading feedback
			$('#mail-message-list-loading').css('opacity', 0)
				.slideDown('slow')
				.animate(
					{opacity: 1},
					{queue: false, duration: 'slow'}
				);

			var folder = __webpack_require__(0).currentFolder;
			return Radio.sync.request('sync:folder', folder)
				.catch(function(e) {
					console.error(e);
					Radio.ui.trigger('error:show', t('mail', 'Error while refreshing messages.'));
				})
				.then(function() {
					$('#mail-message-list-loading').css('opacity', 1)
						.slideUp('slow')
						.animate(
							{
								opacity: 0
							},
							{
								queue: false,
								duration: 'slow',
								complete: function() {
									this.loadingMore = false;
								}.bind(this)
							});
					this._reloaded = true;
				}.bind(this), console.error.bind(this));
		},

		onBeforeRender: function() {
			// FF jump scrolls when we load more mesages. This stores the scroll
			// position before the element is re-rendered and restores it afterwards
			if (this.$scrollContainer) {
				this._prevScrollTop = this.$scrollContainer.scrollTop();
			}
		},
		onRender: function() {
			// see onBeforeRender
			if (this.$scrollContainer) {
				if (this._prevScrollTop) {
					this.$scrollContainer.scrollTop(this._prevScrollTop);
				}
			}
		}
	});
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 55 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2017
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var $ = __webpack_require__(5);
	var _ = __webpack_require__(3);
	var Handlebars = __webpack_require__(4);
	var Marionette = __webpack_require__(2);
	var Radio = __webpack_require__(1);
	var MessageTemplate = __webpack_require__(56);

	return Marionette.View.extend({
		template: Handlebars.compile(MessageTemplate),
		ui: {
			iconDelete: '.action.delete',
			star: '.star'
		},
		events: {
			'click .action.delete': 'deleteMessage',
			'click .mail-message-header': 'openMessage',
			'click .star': 'toggleMessageStar'
		},
		modelEvents: {
			change: 'render'
		},
		serializeModel: function() {
			var json = this.model.toJSON();
			json.isUnified = __webpack_require__(0).currentAccount.get('isUnified');
			return json;
		},
		onRender: function() {
			// Get rid of that pesky wrapping-div.
			// Assumes 1 child element present in template.
			this.$el = this.$el.children();
			// Unwrap the element to prevent infinitely
			// nesting elements during re-render.
			this.$el.unwrap();
			this.setElement(this.$el);

			var displayName = this.model.get('from');
			// Don't show any placeholder if 'from' isn't set
			if (displayName) {
				_.each(this.$el.find('.avatar'), function(a) {
					$(a).height('32px');
					$(a).imageplaceholder(displayName, displayName);
				});
			}

			var _this = this;
			var dragScope = 'folder-' + this.model.folder.account.get('accountId');
			this.$el.draggable({
				appendTo: '#content-wrapper',
				scope: dragScope,
				helper: function() {
					var el = $('<div class="icon-mail"></div>');
					el.data('folderId', _this.model.folder.get('id'));
					el.data('messageId', _this.model.get('id'));
					return el;
				},
				cursorAt: {
					top: -5,
					left: -5
				},
				revert: 'invalid'
			});

			$('.action.delete').tooltip({placement: 'left'});
		},
		toggleMessageStar: function(event) {
			event.stopPropagation();

			var starred = this.model.get('flags').get('flagged');

			// directly change star state in the interface for quick feedback
			if (starred) {
				this.getUI('star')
					.removeClass('icon-starred')
					.addClass('icon-star');
			} else {
				this.getUI('star')
					.removeClass('icon-star')
					.addClass('icon-starred');
			}

			Radio.message.trigger('flag', this.model, 'flagged', !starred);
		},
		openMessage: function(event) {
			event.stopPropagation();
			$('#mail-message').removeClass('hidden-mobile');
			// make sure message is marked as read when clicked on it
			Radio.message.trigger('flag', this.model, 'unseen', false);
			Radio.message.trigger('load', this.model.folder.account, this.model.folder, this.model, {
				force: true
			});
		},
		deleteMessage: function(event) {
			event.stopPropagation();
			var message = this.model;

			this.getUI('iconDelete').removeClass('icon-delete').addClass('icon-loading-small');
			$('.tooltip').remove();

			this.$el.addClass('transparency').slideUp(function() {
				$('.tooltip').remove();

				// really delete the message
				Radio.folder.request('message:delete', message, __webpack_require__(0).currentFolder);

				// manually trigger mouseover event for current mouse position
				// in order to create a tooltip for the next message if needed
				if (event.clientX) {
					$(document.elementFromPoint(event.clientX, event.clientY)).trigger('mouseover');
				}
			});
		}
	});
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 56 */
/***/ (function(module, exports) {

module.exports = "module.exports = \"<div class=\\\"mail-message-summary {{#if flags.unseen}}unseen{{/if}} {{#if active}}active{{/if}}\\\" data-message-id=\\\"{{id}}\\\">\\n\\t{{#if isUnified}}\\n\\t<div class=\\\"mail-message-account-color\\\" style=\\\"background-color: {{accountColor accountMail}}\\\"></div>\\n\\t{{/if}}\\n\\t<div class=\\\"mail-message-header\\\">\\n\\t\\t<div class=\\\"sender-image avatardiv\\\">\\n\\t\\t\\t{{#if senderImage}}\\n\\t\\t\\t<img src=\\\"{{senderImage}}\\\" width=\\\"32px\\\" height=\\\"32px\\\" />\\n\\t\\t\\t{{else}}\\n\\t\\t\\t<div class=\\\"avatar\\\" data-user=\\\"{{from}}\\\" data-size=\\\"32\\\"></div>\\n\\t\\t\\t{{/if}}\\n\\t\\t</div>\\n\\n\\t\\t{{#if flags.flagged}}\\n\\t\\t<div class=\\\"star icon-starred\\\" data-starred=\\\"true\\\"></div>\\n\\t\\t{{else}}\\n\\t\\t<div class=\\\"star icon-star\\\" data-starred=\\\"false\\\"></div>\\n\\t\\t{{/if}}\\n\\n\\t\\t{{#if flags.answered}}\\n\\t\\t<div class=\\\"icon-reply\\\"></div>\\n\\t\\t{{/if}}\\n\\n\\t\\t{{#if flags.hasAttachments}}\\n\\t\\t<div class=\\\"icon-public icon-attachment\\\"></div>\\n\\t\\t{{/if}}\\n\\n\\t\\t<div class=\\\"mail-message-summary-from\\\" title=\\\"{{fromEmail}}\\\">{{from}}</div>\\n\\t\\t<div class=\\\"mail-message-summary-subject\\\" title=\\\"{{subject}}\\\">\\n\\t\\t\\t{{subject}}\\n\\t\\t</div>\\n\\t\\t<div class=\\\"date\\\">\\n\\t\\t\\t\\t<span class=\\\"modified live-relative-timestamp\\\"\\n\\t\\t\\t\\t\\tdata-timestamp=\\\"{{dateMicro}}\\\"\\n\\t\\t\\t\\t\\ttitle=\\\"{{formatDate dateInt}}\\\">\\n\\t\\t\\t\\t\\t{{relativeModifiedDate dateInt}}\\n\\t\\t\\t\\t</span>\\n\\t\\t</div>\\n\\t\\t<div class=\\\"icon-delete action delete\\\" title=\\\"{{delete}}\\\"></div>\\n\\t</div>\\n</div>\\n\""

/***/ }),
/* 57 */
/***/ (function(module, exports) {

module.exports = "module.exports = \"<div id=\\\"mail-message-list-loading\\\"\\n     class=\\\"icon-loading-small\\\"\\n     style=\\\"display: none\\\"></div>\\n<div id=\\\"mail-message-list\\\"></div>\\n<div id=\\\"load-more-mail-messages\\\"></div>\""

/***/ }),
/* 58 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
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

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'strict';

	var Handlebars = __webpack_require__(4);
	var Marionette = __webpack_require__(2);
	var EmptyMessagesTemplate = __webpack_require__(59);

	var EmptyMessagesView = Marionette.View.extend({
		id: 'emptycontent',
		template: Handlebars.compile(EmptyMessagesTemplate)
	});

	return EmptyMessagesView;
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 59 */
/***/ (function(module, exports) {

module.exports = "module.exports = \"<div class=\\\"icon-mail\\\"></div>\\n<h2>{{ t 'No messages in this folder!' }}</h2>\""

/***/ }),
/* 60 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015, 2016
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Handlebars = __webpack_require__(4);
	var Marionette = __webpack_require__(2);
	var NoSearchResultMessageListViewTemplate
		= __webpack_require__(61);

	return Marionette.View.extend({
		initialize: function(options) {
			this.model.set('searchTerm', options.searchQuery);
		},
		template: Handlebars.compile(NoSearchResultMessageListViewTemplate)
	});
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));



/***/ }),
/* 61 */
/***/ (function(module, exports) {

module.exports = "module.exports = \"<div id=\\\"emptycontent\\\" class=\\\"emptycontent-search\\\">\\n\\t<div class=\\\"icon-search\\\"></div>\\n\\t<h2>{{ t 'No search results for' }} {{ searchTerm }}</h2>\\n</div>\""

/***/ }),
/* 62 */
/***/ (function(module, exports) {

module.exports = "module.exports = \"<div class=\\\"\\\">\\n\\t{{#if icon}}<div class=\\\"{{icon}}\\\"></div>{{/if}}\\n\\t<h2>{{{ text }}}</h2>\\n\\t{{# if canRetry }}\\n\\t<br>\\n\\t<button class=\\\"retry\\\">{{ t 'Try again' }}</button>\\n\\t{{/if}}\\n</div>\""

/***/ }),
/* 63 */
/***/ (function(module, exports) {

module.exports = "module.exports = \"{{#if hint}}\\n<div class=\\\"emptycontent\\\">\\n\\t<a class=\\\"icon-loading\\\"></a>\\n\\t<h2>{{{ hint }}}</h2>\\n</div>\\n{{else}}\\n<div class=\\\"container icon-loading\\\"></div>\\n{{/if}}\""

/***/ }),
/* 64 */
/***/ (function(module, exports) {

module.exports = "module.exports = \"<div id=\\\"mail-messages\\\"></div>\\n<div id=\\\"mail-message\\\" class=\\\" hidden-mobile\\\"></div>\""

/***/ }),
/* 65 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var _ = __webpack_require__(3);
	var $ = __webpack_require__(5);
	var Marionette = __webpack_require__(2);
	var AccountView = __webpack_require__(66);
	var Radio = __webpack_require__(1);

	/**
	 * @class NavigationAccountsView
	 */
	return Marionette.CollectionView.extend({
		collection: null,
		childView: AccountView,
		/**
		 * @returns {undefined}
		 */
		initialize: function() {
			this.listenTo(Radio.ui, 'folder:changed', this.onFolderChanged);
			this.listenTo(Radio.folder, 'setactive', this.setFolderActive);
		},
		/**
		 * @param {Account} account
		 * @param {Folder} folder
		 * @returns {undefined}
		 */
		setFolderActive: function(account, folder) {
			// disable all other folders for all accounts
			__webpack_require__(0).accounts.each(function(acnt) {
				// TODO: useless? accounts.get(acnt.get('accountId')) === acnt ?
				var localAccount = __webpack_require__(0).accounts.get(acnt.get('accountId'));
				if (_.isUndefined(localAccount)) {
					return;
				}
				var folders = localAccount.folders;
				_.each(folders.models, function(folder) {
					folders.get(folder).set('active', false);
				});
			});

			if (folder) {
				folder.set('active', true);
			}
		},
		/**
		 * @returns {undefined}
		 */
		onFolderChanged: function() {
			// hide message detail view on mobile
			// TODO: find better place for this
			$('#mail-message').addClass('hidden-mobile');
		}
	});
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 66 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015, 2016
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Handlebars = __webpack_require__(4);
	var Marionette = __webpack_require__(2);
	var OC = __webpack_require__(7);
	var Radio = __webpack_require__(1);
	var FolderListView = __webpack_require__(22);
	var AccountTemplate = __webpack_require__(69);

	return Marionette.View.extend({
		template: Handlebars.compile(AccountTemplate),
		templateContext: function() {
			var toggleCollapseMessage = this.collapsed ? t('mail', 'Show all folders') : t('mail', 'Collapse folders');
			return {
				isUnifiedInbox: this.model.get('accountId') === -1,
				toggleCollapseMessage: toggleCollapseMessage,
				hasMenu: this.model.get('accountId') !== -1,
				hasFolders: this.model.folders.length > 0,
				isDeletable: this.model.get('accountId') !== -2,
			};
		},
		events: {
			'click .account-toggle-collapse': 'toggleCollapse',
			'click .app-navigation-entry-utils-menu-button button': 'toggleMenu',
			'click @ui.deleteButton': 'onDelete',
			'click @ui.settingsButton': 'showAccountSettings',
			'click @ui.email': 'onClick'
		},
		regions: {
			folders: '.folders'
		},
		ui: {
			email: '.mail-account-email',
			menu: '.app-navigation-entry-menu',
			settingsButton: '.action-settings',
			deleteButton: '.action-delete'
		},
		className: 'navigation-account',
		menuShown: false,
		collapsed: true,
		initialize: function(options) {
			this.model = options.model;
		},
		toggleCollapse: function() {
			this.collapsed = !this.collapsed;
			this.render();
		},
		toggleMenu: function(e) {
			e.preventDefault();
			this.menuShown = !this.menuShown;
			this.toggleMenuClass();
		},
		toggleMenuClass: function() {
			this.getUI('menu').toggleClass('open', this.menuShown);
		},
		onDelete: function(e) {
			e.stopPropagation();

			this.getUI('deleteButton').find('.icon-delete').removeClass('icon-delete').addClass('icon-loading-small');

			var account = this.model;

			Radio.account.request('delete', account).then(function() {
				// reload the complete page
				// TODO should only reload the app nav/content
				window.location.reload();
			}, function() {
				OC.Notification.show(t('mail', 'Error while deleting account.'));
			});
		},
		onClick: function(e) {
			e.preventDefault();
			if (this.model.folders.length > 0) {
				var accountId = this.model.get('accountId');
				var folderId = this.model.folders.first().get('id');
				Radio.navigation.trigger('folder', accountId, folderId);
			}
		},
		onRender: function() {
			this.listenTo(Radio.ui, 'document:click', function(event) {
				var target = $(event.target);
				if (!this.$el.is(target.closest('.navigation-account'))) {
					// Click was not triggered by this element -> close menu
					this.menuShown = false;
					this.toggleMenuClass();
				}
			});

			this.showChildView('folders', new FolderListView({
				collection: this.model.folders,
				collapsed: this.collapsed
			}));
		},
		showAccountSettings: function(e) {
			this.toggleMenu(e);
			Radio.navigation.trigger('accountsettings', this.model.get('accountId'));

		}
	});
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 67 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015, 2016
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var _ = __webpack_require__(3);
	var Marionette = __webpack_require__(2);
	var Handlebars = __webpack_require__(4);
	var OC = __webpack_require__(7);
	var Radio = __webpack_require__(1);
	var FolderTemplate = __webpack_require__(68);

	return Marionette.View.extend({

		tagName: 'li',

		updateElClasses: function() {
			var classes = [];
			if (this.model.get('unseen')) {
				classes.push('unseen');
			}
			if (this.model.get('active')) {
				classes.push('active');
			}
			if (this.model.get('specialRole')) {
				classes.push('special-' + this.model.get('specialRole'));
			}
			if (this.model.folders.length > 0) {
				classes.push('collapsible');
			}
			if (this.model.get('open')) {
				classes.push('open');
			}
			// .removeClass() does not work, https://bugs.jqueryui.com/ticket/9015
			this.$el.prop('class', '');
			var _this = this;
			_.each(classes, function(clazz) {
				_this.$el.addClass(clazz);
			});
		},

		template: Handlebars.compile(FolderTemplate),

		templateContext: function() {
			var count = null;
			if (this.model.get('specialRole') === 'drafts') {
				count = this.model.get('total');
			} else {
				count = this.model.get('unseen');
			}

			var url = OC.generateUrl('apps/mail/#accounts/{accountId}/folders/{folderId}', {
				// TODO: account should be property of folder
				accountId: this.model.get('accountId'),
				folderId: this.model.get('id')
			});

			var folders = this.model.folders.length > 0 ? this.model.folders.toJSON() : undefined;

			return {
				count: count,
				url: url,
				folders: folders
			};
		},

		regions: {
			folders: '.folders'
		},

		events: {
			'click .collapse': 'collapseFolder',
			'click .folder': 'loadFolder'
		},

		modelEvents: {
			change: 'render'
		},

		collapseFolder: function(e) {
			e.preventDefault();
			this.model.toggleOpen();
		},

		loadFolder: function(e) {
			e.preventDefault();
			// TODO: account should be property of folder
			var account = __webpack_require__(0).accounts.get(this.model.get('accountId'));
			var folder = this.model;

			if (folder.get('noSelect')) {
				console.info('ignoring \'loadFolder\' event for noSelect folder');
				return;
			}

			Radio.navigation.trigger('folder', account.get('accountId'), folder.get('id'));
		},

		onRender: function() {
			var FolderListView = __webpack_require__(22);

			this.showChildView('folders', new FolderListView({
				collection: this.model.folders
			}));

			this.updateElClasses();

			// Make non search folder folders droppable
			if (!(/\/FLAGGED$/.test(atob(this.model.get('id'))))) {
				var dropScope = 'folder-' + this.model.account.get('accountId');
				this.$el.droppable({
					scope: dropScope,
					greedy: true,
					drop: _.bind(function(event, ui) {
						var account = __webpack_require__(0).currentAccount;
						var sourceFolder = account.getFolderById(ui.helper.data('folderId'));
						var message = sourceFolder.messages.get(ui.helper.data('messageId'));
						Radio.message.trigger('move', account, sourceFolder, message, account, this.model);
					}, this),
					hoverClass: 'ui-droppable-active'
				});
			}
		}
	});
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 68 */
/***/ (function(module, exports) {

module.exports = "module.exports = \"{{#if folders}}\\n<button class=\\\"collapse\\\"></button>\\n{{/if}}\\n<a class=\\\"folder\\n\\t{{#if specialRole}} icon-{{specialRole}} svg{{/if}}\\n\\t{{#if noSelect}} no-select {{/if}}\\\"\\n   href=\\\"{{#if noSelect}}#{{else}}{{url}}{{/if}}\\\">\\n\\t{{name}}\\n\\t{{#if count}}\\n\\t<span class=\\\"utils\\\">{{count}}</span>\\n\\t{{/if}}\\n</a>\\n<div class=\\\"folders\\\"></div>\""

/***/ }),
/* 69 */
/***/ (function(module, exports) {

module.exports = "module.exports = \"{{#if emailAddress}}\\n<div class=\\\"mail-account-color\\\" style=\\\"background-color: {{accountColor emailAddress}}\\\"></div>\\n{{/if}}\\n<h2 class=\\\"mail-account-email\\\" title=\\\"{{emailAddress}}\\\">{{emailAddress}}</h2>\\n\\n{{#if hasMenu}}\\n<div class=\\\"app-navigation-entry-utils\\\">\\n\\t<ul>\\n\\t\\t<li class=\\\"app-navigation-entry-utils-menu-button svg\\\"><button></button></li>\\n\\t</ul>\\n</div>\\n\\n<div class=\\\"app-navigation-entry-menu popovermenu bubble menu\\\">\\n\\t<ul>\\n\\t\\t<li>\\n\\t\\t\\t<a href=\\\"#\\\" class=\\\"menuitem action action-settings permanent\\\" data-action=\\\"Settings\\\">\\n\\t\\t\\t\\t<span class=\\\"icon icon-rename\\\"></span>\\n\\t\\t\\t\\t<span>{{ t 'Settings' }}</span>\\n\\t\\t\\t</a>\\n\\t\\t</li>\\n\\t\\t{{#if isDeletable}}\\n        <li>\\n            <a href=\\\"#\\\" class=\\\"menuitem action action-delete permanent\\\" data-action=\\\"Delete\\\">\\n\\t\\t\\t\\t<span class=\\\"icon icon-delete\\\"></span>\\n\\t\\t\\t\\t<span>{{ t 'Delete account' }}</span>\\n\\t\\t\\t</a>\\n        </li>\\n\\t\\t{{/if}}\\n\\t</ul>\\n</div>\\n{{/if}}\\n\\n<div class=\\\"folders with-icon\\\"></div>\\n{{#unless isUnifiedInbox}}\\n{{#if hasFolders}}\\n<span class=\\\"account-toggle-collapse\\\">{{toggleCollapseMessage}}</span>\\n{{/if}}\\n{{/unless}}\\n\""

/***/ }),
/* 70 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Handlebars = __webpack_require__(4);
	var Marionette = __webpack_require__(2);
	var OC = __webpack_require__(7);
	var Radio = __webpack_require__(1);
	var SettingsTemplate = __webpack_require__(71);

	return Marionette.View.extend({
		accounts: null,
		template: Handlebars.compile(SettingsTemplate),
		templateContext: function() {
			return {
				addAccountUrl: OC.generateUrl('apps/mail/#setup'),
				keyboardShortcutUrl: OC.generateUrl('apps/mail/#shortcuts')
			};
		},
		regions: {
			accountsList: '#settings-accounts'
		},
		events: {
			'click #new-mail-account': 'addAccount',
			'click #keyboard-shortcuts': 'showKeyboardShortcuts'
		},
		addAccount: function(e) {
			e.preventDefault();
			Radio.navigation.trigger('setup');
		},
		showKeyboardShortcuts: function(e) {
			e.preventDefault();
			Radio.navigation.trigger('keyboardshortcuts');
		}
	});
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 71 */
/***/ (function(module, exports) {

module.exports = "module.exports = \"<div id=\\\"mailsettings\\\">\\n\\t<ul id=\\\"settings-accounts\\\" class=\\\"mailaccount-list\\\">\\n\\t</ul>\\n\\n\\t<a id=\\\"new-mail-account\\\"\\n\\t   class=\\\"button new-button\\\"\\n\\t   href=\\\"{{addAccountUrl}}\\\">{{ t 'Add mail account' }}</a>\\n\\n\\t<p class=\\\"app-settings-hint\\\">\\n\\t<a id=\\\"keyboard-shortcuts\\\"\\n\\t\\t  href=\\\"{{keyboardShortcutUrl}}\\\">{{ t 'Keyboard shortcuts' }}</a></p>\\n\\n\\t<p class=\\\"app-settings-hint\\\">\\n\\t\\t{{{ t 'Looking to encrypt your emails? Install the <a href=\\\"https://www.mailvelope.com/\\\" target=\\\"_blank\\\">Mailvelope browser extension</a>!' }}}\\n\\t</p>\\n</div>\""

/***/ }),
/* 72 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015, 2016
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var $ = __webpack_require__(5);
	var Marionette = __webpack_require__(2);
	var Radio = __webpack_require__(1);
	var NewMessageView = __webpack_require__(73);

	return Marionette.View.extend({
		el: '#app-navigation',
		regions: {
			newMessage: '#mail-new-message-fixed',
			accounts: '#app-navigation-accounts',
			settings: '#app-settings-content'
		},
		initialize: function() {
			this.bindUIElements();

			this.listenTo(Radio.ui, 'navigation:show', this.show);
			this.listenTo(Radio.ui, 'navigation:hide', this.hide);
			this.listenTo(Radio.ui, 'navigation:newmessage:show', this.onShowNewMessage);
		},
		render: function() {
			// This view doesn't need rendering
		},
		show: function() {
			this.$el.show();
			$('#app-navigation-toggle').css('background-image', '');
		},
		hide: function() {
			// TODO: move if or rename function
			if (__webpack_require__(0).accounts.length === 0) {
				this.$el.hide();
				$('#app-navigation-toggle').css('background-image', 'none');
			}
		},
		onShowNewMessage: function() {
			this.showChildView('newMessage', new NewMessageView({
				accounts: this.options.accounts
			}));
		}
	});
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 73 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
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

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Marionette = __webpack_require__(2);
	var Handlebars = __webpack_require__(4);
	var Radio = __webpack_require__(1);
	var NewMessageTemplate = __webpack_require__(74);

	return Marionette.View.extend({
		template: Handlebars.compile(NewMessageTemplate),
		accounts: null,
		ui: {
			button: '#mail_new_message'
		},
		events: {
			'click @ui.button': 'onClick'
		},
		initialize: function(options) {
			this.accounts = options.accounts;
			this.listenTo(options.accounts, 'add', this.onAccountsChanged);
		},
		onRender: function() {
			// Set the approriate ui state
			this.onAccountsChanged();
		},
		onAccountsChanged: function() {
			if (this.accounts.size === 0) {
				this.getUI('button').hide();
			} else {
				this.getUI('button').show();
			}
		},
		onClick: function(e) {
			e.preventDefault();
			Radio.ui.trigger('composer:show', e);
		}
	});
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 74 */
/***/ (function(module, exports) {

module.exports = "module.exports = \"<button type=\\\"button\\\"\\n       id=\\\"mail_new_message\\\"\\n       class=\\\"icon-add\\\">{{ t 'New message' }}</button>\""

/***/ }),
/* 75 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
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

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var _ = __webpack_require__(3);
	var Handlebars = __webpack_require__(4);
	var Marionette = __webpack_require__(2);
	var Radio = __webpack_require__(1);
	var AccountController = __webpack_require__(23);
	var AccountFormView = __webpack_require__(76);
	var ErrorView = __webpack_require__(10);
	var LoadingView = __webpack_require__(11);
	var SetupTemplate = __webpack_require__(78);

	/**
	 * @class SetupView
	 */
	return Marionette.View.extend({

		/** @type {string} */
		className: 'container',

		/** @type {Function} */
		template: Handlebars.compile(SetupTemplate),

		/** @type {boolean} */
		_loading: false,

		/** @type {boolean} */
		_error: undefined,

		/** @type {Object} */
		_config: undefined,

		regions: {
			content: '.setup-content'
		},

		initialize: function(options) {
			this._config = options.config;
		},

		/**
		 * @returns {undefined}
		 */
		onRender: function() {
			if (!_.isUndefined(this._error)) {
				this.showChildView('content', new ErrorView({
					text: this._error,
					canRetry: true
				}));
			} else if (this._loading) {
				// Rendering the first time
				this.showChildView('content', new LoadingView({
					text: t('mail', 'Setting up your account')
				}));
			} else {
				// Re-rending because an error occurred
				this.showChildView('content', new AccountFormView({
					config: this._config
				}));
			}
		},

		/**
		 * @private
		 * @param {Object} config
		 * @returns {Promise}
		 */
		onChildviewFormSubmit: function(config) {
			var _this = this;
			this._loading = true;
			this._config = config;
			this.render();

			return Radio.account.request('create', config).then(function() {
				Radio.ui.trigger('navigation:show');
				Radio.ui.trigger('content:loading');
				// reload accounts
				return AccountController.loadAccounts();
			}).then(function(accounts) {
				// Let's assume there's at least one account after a successful
				// setup, so let's show the first one (could be the unified inbox)
				var firstAccount = accounts.first();
				var firstFolder = firstAccount.folders.first();
				Radio.navigation.trigger('folder', firstAccount.get('accountId'), firstFolder.get('id'));
			}).catch(function(error) {
				console.error('could not create account:', error);
				// Show error view for a few seconds
				_this._loading = false;
				_this._error = error;
				_this.render();
			}).catch(console.error.bind(this));
		},

		onChildviewRetry: function() {
			this._loading = false;
			this._error = undefined;
			this.render();
		}

	});
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 76 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2016, 2017
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var _ = __webpack_require__(3);
	var Marionette = __webpack_require__(2);
	var Handlebars = __webpack_require__(4);
	var AccountFormTemplate = __webpack_require__(77);

	/**
	 * @class AccountFormView
	 */
	return Marionette.View.extend({

		/** @type {string} */
		id: 'account-form',

		/** @type {Function} */
		template: Handlebars.compile(AccountFormTemplate),

		/**
		 * @returns {object}
		 */
		templateContext: function() {
			return {
				config: this._config
			};
		},

		/** @type {boolean} */
		firstToggle: true,

		/** @type {object} */
		_config: '',

		ui: {
			form: 'form',
			inputs: 'input, select',
			toggleManualMode: '.toggle-manual-mode',
			accountName: 'input[name="account-name"]',
			mailAddress: 'input[name="mail-address"]',
			mailPassword: 'input[name="mail-password"]',
			manualInputs: '.manual-inputs',
			imapHost: 'input[name="imap-host"]',
			imapPort: 'input[name="imap-port"]',
			imapSslMode: '#setup-imap-ssl-mode',
			imapUser: 'input[name="imap-user"]',
			imapPassword: 'input[name="imap-password"]',
			smtpHost: 'input[name="smtp-host"]',
			smtpSslMode: '#setup-smtp-ssl-mode',
			smtpPort: 'input[name="smtp-port"]',
			smtpUser: 'input[name="smtp-user"]',
			smtpPassword: 'input[name="smtp-password"]',
			submitButton: 'input[type=submit]'
		},

		events: {
			'click @ui.submitButton': 'onSubmit',
			'submit @ui.form': 'onSubmit',
			'click @ui.toggleManualMode': 'toggleManualMode',
			'change @ui.imapSslMode': 'onImapSslModeChange',
			'change @ui.smtpSslMode': 'onSmtpSslModeChange'
		},

		/**
		 * @param {object} options
		 * @returns {undefined}
		 */
		initialize: function(options) {
			this._config = _.defaults(options.config || {}, {
				accountName: '',
				emailAddress: '',
				autoDetect: true,
				imapPort: 993,
				imapSslMode: 'ssl',
				smtpPort: 587,
				smtpSslMode: 'tls'
			});
		},

		/**
		 * @returns {undefined}
		 */
		onRender: function() {
			if (this._config.autoDetect) {
				this.getUI('mailPassword').show();
				this.getUI('manualInputs').hide();
			} else {
				this.getUI('mailPassword').hide();
			}

			this.getUI('imapSslMode').find('[value="' + this._config.imapSslMode + '"]').attr({'selected': 'selected'});
			this.getUI('smtpSslMode').find('[value="' + this._config.smtpSslMode + '"]').attr({'selected': 'selected'});
		},

		/**
		 * @param {Event} e
		 * @returns {undefined}
		 */
		toggleManualMode: function(e) {
			e.stopPropagation();
			this._config.autoDetect = !this._config.autoDetect;

			this.getUI('manualInputs').slideToggle();
			this.getUI('imapHost').focus();

			if (!this._config.autoDetect) {
				if (this.firstToggle) {
					// Manual mode opened for the first time
					// -> copy email, password for imap&smtp
					var email = this.getUI('mailAddress').val();
					var password = this.getUI('mailPassword').val();
					this.getUI('imapUser').val(email);
					this.getUI('imapPassword').val(password);
					this.getUI('smtpUser').val(email);
					this.getUI('smtpPassword').val(password);
					this.firstToggle = false;
				}

				var _this = this;
				this.getUI('mailPassword').slideToggle(function() {
					_this.getUI('mailAddress').parent()
						.removeClass('groupmiddle').addClass('groupbottom');

					// Focus imap host input
					_this.getUI('imapHost').focus();
				});
			} else {
				this.getUI('mailPassword').slideToggle();
				this.getUI('mailAddress').parent()
					.removeClass('groupbottom').addClass('groupmiddle');
			}
		},

		/**
		 * @param {type} e
		 * @returns {undefined}
		 */
		onSubmit: function(e) {
			e.preventDefault();
			e.stopPropagation();

			var emailAddress = this.getUI('mailAddress').val();
			var accountName = this.getUI('accountName').val();
			var password = this.getUI('mailPassword').val();

			var config = {
				accountName: accountName,
				emailAddress: emailAddress,
				password: password,
				autoDetect: true
			};

			// if manual setup is open, use manual values
			if (!this._config.autoDetect) {
				config = {
					accountName: accountName,
					emailAddress: emailAddress,
					password: password,
					imapHost: this.getUI('imapHost').val(),
					imapPort: this.getUI('imapPort').val(),
					imapSslMode: this.getUI('imapSslMode').val(),
					imapUser: this.getUI('imapUser').val(),
					imapPassword: this.getUI('imapPassword').val(),
					smtpHost: this.getUI('smtpHost').val(),
					smtpPort: this.getUI('smtpPort').val(),
					smtpSslMode: this.getUI('smtpSslMode').val(),
					smtpUser: this.getUI('smtpUser').val(),
					smtpPassword: this.getUI('smtpPassword').val(),
					autoDetect: false
				};
			}

			this.triggerMethod('form:submit', config);
		},

		/**
		 * @returns {undefined}
		 */
		onImapSslModeChange: function() {
			// set standard port for the selected IMAP & SMTP security
			var imapDefaultPort = 143;
			var imapDefaultSecurePort = 993;

			switch (this.getUI('imapSslMode').val()) {
				case 'none':
				case 'tls':
					this.getUI('imapPort').val(imapDefaultPort);
					break;
				case 'ssl':
					this.getUI('imapPort').val(imapDefaultSecurePort);
					break;
			}
		},

		/**
		 * @returns {undefined}
		 */
		onSmtpSslModeChange: function() {
			var smtpDefaultPort = 587;
			var smtpDefaultSecurePort = 465;

			switch (this.getUI('smtpSslMode').val()) {
				case 'none':
				case 'tls':
					this.getUI('smtpPort').val(smtpDefaultPort);
					break;
				case 'ssl':
					this.getUI('smtpPort').val(smtpDefaultSecurePort);
					break;
			}
		}
	});
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 77 */
/***/ (function(module, exports) {

module.exports = "module.exports = \"<form method=\\\"post\\\">\\n\\t<div class=\\\"hidden-visually\\\">\\n\\t\\t<!-- Hack for Safari and Chromium/Chrome which ignore autocomplete=\\\"off\\\" -->\\n\\t\\t<input type=\\\"text\\\" id=\\\"fake_user\\\" name=\\\"fake_user\\\"\\n\\t\\t       autocomplete=\\\"off\\\" tabindex=\\\"-1\\\">\\n\\t\\t<input type=\\\"password\\\" id=\\\"fake_password\\\" name=\\\"fake_password\\\"\\n\\t\\t       autocomplete=\\\"off\\\" tabindex=\\\"-1\\\">\\n\\t</div>\\n\\t<fieldset>\\n\\t\\t<div id=\\\"emptycontent\\\">\\n\\t\\t\\t<div class=\\\"icon-mail\\\"></div>\\n\\t\\t\\t<h2>{{ t 'Connect your mail account' }}</h2>\\n\\t\\t</div>\\n\\t\\t<p class=\\\"grouptop\\\">\\n\\t\\t\\t<input type=\\\"text\\\"\\n\\t\\t\\t       name=\\\"account-name\\\"\\n\\t\\t\\t       placeholder=\\\"{{ t 'Name' }}\\\"\\n\\t\\t\\t       value=\\\"{{ config.accountName }}\\\"\\n\\t\\t\\t       autofocus />\\n\\t\\t</p>\\n\\t\\t<p class=\\\"groupmiddle\\\">\\n\\t\\t\\t<input type=\\\"email\\\"\\n\\t\\t\\t       name=\\\"mail-address\\\"\\n\\t\\t\\t       placeholder=\\\"{{ t 'Mail Address' }}\\\"\\n\\t\\t\\t       value=\\\"{{ config.emailAddress }}\\\"\\n\\t\\t\\t       required />\\n\\t\\t</p>\\n\\t\\t<p class=\\\"groupbottom\\\">\\n\\t\\t\\t<input type=\\\"password\\\"\\n\\t\\t\\t       name=\\\"mail-password\\\"\\n\\t\\t\\t       placeholder=\\\"{{ t 'Password' }}\\\"\\n\\t\\t\\t       value=\\\"{{ config.password }}\\\"\\n\\t\\t\\t       required />\\n\\t\\t</p>\\n\\n\\t\\t<a class=\\\"toggle-manual-mode icon-caret-dark\\\">{{ t 'Manual configuration' }}</a>\\n\\n\\t\\t<div class=\\\"manual-inputs\\\">\\n\\t\\t\\t<p class=\\\"grouptop\\\">\\n\\t\\t\\t\\t<input type=\\\"text\\\"\\n\\t\\t\\t\\t       name=\\\"imap-host\\\"\\n\\t\\t\\t\\t       placeholder=\\\"{{ t 'IMAP Host' }}\\\"\\n\\t\\t\\t\\t       value=\\\"{{ config.imapHost }}\\\" />\\n\\t\\t\\t</p>\\n\\t\\t\\t<p class=\\\"groupmiddle\\\" id=\\\"setup-imap-ssl\\\">\\n\\t\\t\\t\\t<select id=\\\"setup-imap-ssl-mode\\\"\\n\\t\\t\\t\\t\\tname=\\\"imap-sslmode\\\"\\n\\t\\t\\t\\t\\ttitle=\\\"{{ t 'IMAP security' }}\\\">\\n\\t\\t\\t\\t\\t<option value=\\\"none\\\">{{ t 'None' }}</option>\\n\\t\\t\\t\\t\\t<option value=\\\"ssl\\\">{{ t 'SSL/TLS' }}</option>\\n\\t\\t\\t\\t\\t<option value=\\\"tls\\\">{{ t 'STARTTLS' }}</option>\\n\\t\\t\\t\\t</select>\\n\\t\\t\\t</p>\\n\\t\\t\\t<p class=\\\"groupmiddle\\\">\\n\\t\\t\\t\\t<input type=\\\"number\\\"\\n\\t\\t\\t\\t       name=\\\"imap-port\\\"\\n\\t\\t\\t\\t       placeholder=\\\"{{ t 'IMAP Port' }}\\\"\\n\\t\\t\\t\\t       value=\\\"{{ config.imapPort }}\\\" />\\n\\t\\t\\t</p>\\n\\t\\t\\t<p class=\\\"groupmiddle\\\">\\n\\t\\t\\t\\t<input type=\\\"text\\\"\\n\\t\\t\\t\\t       name=\\\"imap-user\\\"\\n\\t\\t\\t\\t       placeholder=\\\"{{ t 'IMAP User' }}\\\"\\n\\t\\t\\t\\t       value=\\\"{{ config.imapUser }}\\\" />\\n\\t\\t\\t</p>\\n\\t\\t\\t<p class=\\\"groupbottom\\\">\\n\\t\\t\\t\\t<input type=\\\"password\\\"\\n\\t\\t\\t\\t       name=\\\"imap-password\\\"\\n\\t\\t\\t\\t       placeholder=\\\"{{ t 'IMAP Password' }}\\\"\\n\\t\\t\\t\\t       value=\\\"{{ config.imapPassword }}\\\"\\n\\t\\t\\t\\t       required />\\n\\t\\t\\t</p>\\n\\t\\t\\t<p class=\\\"grouptop\\\">\\n\\t\\t\\t\\t<input type=\\\"text\\\"\\n\\t\\t\\t\\t       name=\\\"smtp-host\\\"\\n\\t\\t\\t\\t       placeholder=\\\"{{ t 'SMTP Host' }}\\\"\\n\\t\\t\\t\\t       value=\\\"{{ config.smtpHost }}\\\" />\\n\\t\\t\\t</p>\\n\\t\\t\\t<p class=\\\"groupmiddle\\\" id=\\\"setup-smtp-ssl\\\">\\n\\t\\t\\t\\t<select id=\\\"setup-smtp-ssl-mode\\\"\\n\\t\\t\\t\\t\\tname=\\\"mail-smtp-sslmode\\\"\\n\\t\\t\\t\\t\\ttitle=\\\"{{ t 'SMTP security' }}\\\">\\n\\t\\t\\t\\t\\t<option value=\\\"none\\\">{{ t 'None' }}</option>\\n\\t\\t\\t\\t\\t<option value=\\\"ssl\\\">{{ t 'SSL/TLS' }}</option>\\n\\t\\t\\t\\t\\t<option value=\\\"tls\\\">{{ t 'STARTTLS' }}</option>\\n\\t\\t\\t\\t</select>\\n\\t\\t\\t</p>\\n\\t\\t\\t<p class=\\\"groupmiddle\\\">\\n\\t\\t\\t\\t<input type=\\\"number\\\"\\n\\t\\t\\t\\t       name=\\\"smtp-port\\\"\\n\\t\\t\\t\\t       placeholder=\\\"{{ t 'SMTP Port' }}\\\"\\n\\t\\t\\t\\t       value=\\\"{{ config.smtpPort }}\\\" />\\n\\t\\t\\t</p>\\n\\t\\t\\t<p class=\\\"groupmiddle\\\">\\n\\t\\t\\t\\t<input type=\\\"text\\\"\\n\\t\\t\\t\\t       name=\\\"smtp-user\\\"\\n\\t\\t\\t\\t       placeholder=\\\"{{ t 'SMTP User' }}\\\"\\n\\t\\t\\t\\t       value=\\\"{{ config.smtpUser }}\\\" />\\n\\t\\t\\t</p>\\n\\t\\t\\t<p class=\\\"groupbottom\\\">\\n\\t\\t\\t\\t<input type=\\\"password\\\"\\n\\t\\t\\t\\t       name=\\\"smtp-password\\\"\\n\\t\\t\\t\\t       placeholder=\\\"{{ t 'SMTP Password' }}\\\"\\n\\t\\t\\t\\t       value=\\\"{{ config.smtpPassword }}\\\"\\n\\t\\t\\t\\t       required />\\n\\t\\t\\t</p>\\n\\t\\t</div>\\n\\n\\t\\t<input type=\\\"submit\\\"\\n\\t\\t       class=\\\"primary\\\"\\n\\t\\t       value=\\\"{{ t 'Connect' }}\\\"/>\\n\\t</fieldset>\\n</form>\\n\""

/***/ }),
/* 78 */
/***/ (function(module, exports) {

module.exports = "module.exports = \"<div class=\\\"setup-content container\\\"></div>\\n\""

/***/ }),
/* 79 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Tahaa Karim <tahaalibra@gmail.com>
 * @copyright Tahaa Karim 2016
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Marionette = __webpack_require__(2);
	var Handlebars = __webpack_require__(4);
	var AccountSettingsTemplate = __webpack_require__(80);
	var AliasesView = __webpack_require__(81);
	var Radio = __webpack_require__(1);

	return Marionette.View.extend({
		template: Handlebars.compile(AccountSettingsTemplate),
		templateContext: function() {
			var aliases = this.aliases;
			return {
				aliases: aliases,
				email: this.currentAccount.get('email')
			};
		},
		currentAccount: null,
		aliases: null,
		ui: {
			'form': 'form',
			'alias': 'input[name="alias"]',
			'submitButton': 'input[type=submit]',
			'aliasName': 'input[name="alias-name"]'
		},
		events: {
			'click @ui.submitButton': 'onSubmit',
			'submit @ui.form': 'onSubmit'
		},
		regions: {
			aliasesRegion: '#aliases-list'
		},
		initialize: function(options) {
			this.currentAccount = options.account;
		},
		onSubmit: function(e) {
			e.preventDefault();
			var alias = {
				alias: this.getUI('alias').val(),
				name: this.getUI('aliasName').val()
			};
			this.getUI('alias').prop('disabled', true);
			this.getUI('aliasName').prop('disabled', true);
			this.getUI('submitButton').val('Saving');
			this.getUI('submitButton').prop('disabled', true);
			var _this = this;

			Radio.aliases.request('save', this.currentAccount, alias)
				.then(function(data) {
					_this.currentAccount.get('aliases').add(data);
				}, console.error.bind(this))
				.then(function() {
					_this.getUI('alias').val('');
					_this.getUI('aliasName').val('');
					_this.getUI('alias').prop('disabled', false);
					_this.getUI('aliasName').prop('disabled', false);
					_this.getUI('submitButton').prop('disabled', false);
					_this.getUI('submitButton').val('Save');
				});
		},
		onRender: function() {
			this.showAliases();
		},
		showAliases: function() {
			this.showChildView('aliasesRegion', new AliasesView({
				currentAccount: this.currentAccount
			}));
		}
	});
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 80 */
/***/ (function(module, exports) {

module.exports = "module.exports = \"<div class=\\\"section\\\" id=\\\"account-info\\\">\\n\\t<h2>{{ t 'Account Settings' }} - {{ email }}</h2>\\n</div>\\n\\n<div class=\\\"section\\\" id=\\\"aliases\\\">\\n\\t<h2>{{ t 'Aliases' }}</h2>\\n\\t<div id=\\\"aliases-list\\\"></div>\\n\\t<form name=\\\"aliasForm\\\" method=\\\"post\\\">\\n\\t\\t<input type=\\\"text\\\" name=\\\"alias\\\" id=\\\"alias\\\" placeholder=\\\"{{ t 'Mail address' }}\\\">\\n\\t\\t<input type=\\\"text\\\" name=\\\"alias-name\\\" id=\\\"alias-name\\\" placeholder=\\\"{{ t 'Display Name' }}\\\">\\n\\t\\t<input type=\\\"submit\\\" value=\\\"{{ t 'Save' }}\\\">\\n\\t</form>\\n</div>\\n\""

/***/ }),
/* 81 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Tahaa Karim <tahaalibra@gmail.com>
 * @copyright Tahaa Karim 2016
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Marionette = __webpack_require__(2);
	var AliasesListView = __webpack_require__(82);

	return Marionette.CollectionView.extend({
		collection: null,
		tagName: 'table',
		childView: AliasesListView,
		currentAccount: null,
		initialize: function(options) {
			this.currentAccount = options.currentAccount;
			this.collection = this.currentAccount.get('aliases');
		}
	});
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 82 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Tahaa Karim <tahaalibra@gmail.com>
 * @copyright Tahaa Karim 2016
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Marionette = __webpack_require__(2);
	var Handlebars = __webpack_require__(4);
	var AliasesListTemplate = __webpack_require__(83);
	var Radio = __webpack_require__(1);

	return Marionette.View.extend({
		collection: null,
		model: null,
		tagName: 'tr',
		childViewContainer: 'tbody',
		template: Handlebars.compile(AliasesListTemplate),
		templateContext: function() {
			return {
				aliases: this.model.toJSON()
			};
		},
		ui: {
			deleteButton: 'button'
		},
		events: {
			'click @ui.deleteButton': 'deleteAlias'
		},
		initialize: function(options) {
			this.model = options.model;
		},
		deleteAlias: function(event) {
			event.stopPropagation();
			var currentAccount = __webpack_require__(0).accounts.get(this.model.get('accountId'));
			var _this = this;
			this.getUI('deleteButton').prop('disabled', true);
			this.getUI('deleteButton').attr('class', 'icon-loading-small');
			Radio.aliases.request('delete', currentAccount, this.model.get('id'))
				.then(function() {
					currentAccount.get('aliases').remove(_this.model);
				}, console.error.bind(this))
				.then(function() {
					_this.getUI('deleteButton').attr('class', 'icon-delete');
					_this.getUI('deleteButton').prop('disabled', false);
				});
		}

	});
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 83 */
/***/ (function(module, exports) {

module.exports = "module.exports = \"<table class=\\\"grid\\\">\\n\\t<tbody>\\n\\t<tr id=\\\"{{ aliases.id }}\\\">\\n\\t\\t<td style=\\\"padding:0 5px 0 5px;\\\">\\n\\t\\t\\t{{ aliases.alias }}\\n\\t\\t</td>\\n\\t\\t<td style=\\\"padding:0 5px 0 5px;\\\">\\n\\t\\t\\t{{ aliases.name }}\\n\\t\\t</td>\\n\\t\\t<td style=\\\"padding:0 5px 0 5px;\\\"> <button type=\\\"submit\\\" value=\\\"{{ id }}\\\" class=\\\"icon-delete\\\"></button>\\n\\t\\t</td>\\n\\t</tr>\\n\\t</tbody>\\n</table>\""

/***/ }),
/* 84 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * @author Steffen Lindner <mail@steffen-lindner.de>
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

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'strict';

	var Handlebars = __webpack_require__(4);
	var Marionette = __webpack_require__(2);
	var KeyboardShortcutTemplate = __webpack_require__(85);

	var KeyboardShortcutView = Marionette.View.extend({
		id: 'keyboardshortcut',
		template: Handlebars.compile(KeyboardShortcutTemplate)
	});

	return KeyboardShortcutView;
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 85 */
/***/ (function(module, exports) {

module.exports = "module.exports = \"<div id=\\\"app-shortcuts\\\" class=\\\"section\\\">\\n\\n\\t<h2>{{t 'Keyboard shortcuts'}}</h2>\\n\\n\\t<p>{{t 'Speed up your Mail experience with these quick shortcuts.'}}</p>\\n\\n\\t<dl>\\n\\t\\t<div>\\n\\t\\t\\t<dt><kbd>C</kbd></dt>\\n\\t\\t\\t<dd>{{t 'Compose new message'}}</dd>\\n\\t\\t</div>\\n\\t\\t<div>\\n\\t\\t\\t<dt><kbd>K</kbd> or <kbd>←</kbd></dt>\\n\\t\\t\\t<dd>{{t 'Newer message'}}</dd>\\n\\t\\t</div>\\n\\t\\t<div>\\n\\t\\t\\t<dt><kbd>J</kbd> or <kbd>→</kbd></dt>\\n\\t\\t\\t<dd>{{t 'Older message'}}</dd>\\n\\t\\t</div>\\n\\n\\t\\t<div>\\n\\t\\t\\t<dt><kbd>S</kbd></dt>\\n\\t\\t\\t<dd>{{ t 'Toggle star' }}</dd>\\n\\t\\t</div>\\n\\t\\t<div>\\n\\t\\t\\t<dt><kbd>U</kbd></dt>\\n\\t\\t\\t<dd>{{ t 'Toggle unread' }}</dd>\\n\\t\\t</div>\\n\\t\\t<div>\\n\\t\\t\\t<dt><kbd>Del</kbd></dt>\\n\\t\\t\\t<dd>{{ t 'Delete' }}</dd>\\n\\t\\t</div>\\n\\n\\t\\t<div>\\n\\t\\t\\t<dt><kbd>Ctrl</kbd> + <kbd>F</kbd></dt>\\n\\t\\t\\t<dd>{{ t 'Search' }}</dd>\\n\\t\\t</div>\\n\\t\\t<div>\\n\\t\\t\\t<dt><kbd>Ctrl</kbd> + <kbd>Enter</kbd></dt>\\n\\t\\t\\t<dd>{{ t 'Send' }}</dd>\\n\\t\\t</div>\\n\\t\\t<div>\\n\\t\\t\\t<dt><kbd>R</kbd></dt>\\n\\t\\t\\t<dd>{{t 'Refresh'}}</dd>\\n\\t\\t</div>\\n\\t</dl>\\n\\n</div>\\n\""

/***/ }),
/* 86 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/* global relative_modified_date, formatDate, md5, humanFileSize, getScrollBarWidth */

/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Handlebars = __webpack_require__(4);

	Handlebars.registerHelper('relativeModifiedDate', function(dateInt) {
		var lastModified = new Date(dateInt * 1000);
		var lastModifiedTime = Math.round(lastModified.getTime() / 1000);
		// jscs:disable requireCamelCaseOrUpperCaseIdentifiers
		return relative_modified_date(lastModifiedTime);
		// jscs:enable requireCamelCaseOrUpperCaseIdentifiers
	});

	Handlebars.registerHelper('formatDate', function(dateInt) {
		var lastModified = new Date(dateInt * 1000);
		return formatDate(lastModified);
	});

	Handlebars.registerHelper('humanFileSize', function(size) {
		return humanFileSize(size);
	});

	Handlebars.registerHelper('accountColor', function(account) {
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
	});

	Handlebars.registerHelper('printAddressList', function(addressList) {
		var currentAccount = __webpack_require__(0).currentAccount;

		var str = _.reduce(addressList, function(memo, value, index) {
			if (index !== 0) {
				memo += ', ';
			}
			var label = value.label
				.replace(/(^"|"$)/g, '')
				.replace(/(^'|'$)/g, '');
			label = Handlebars.Utils.escapeExpression(label);
			var email = Handlebars.Utils.escapeExpression(value.email);

			if (currentAccount && (email === currentAccount.get('emailAddress') ||
				_.find(currentAccount.get('aliases').toJSON(), function(alias) { return alias.alias  === email; }))) {
				label = t('mail', 'you');
			}
			var title = t('mail', 'Send message to {email}', {email: email});
			memo += '<span class="tooltip-mailto" title="' + title + '">';
			memo += '<a class="link-mailto" data-email="' + email + '" data-label="' + label + '">';
			memo += label + '</a></span>';
			return memo;
		}, '');
		return new Handlebars.SafeString(str);
	});

	Handlebars.registerHelper('printAddressListPlain', function(addressList) {
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
	});

	Handlebars.registerHelper('ifHasCC', function(cc, ccList, options) {
		if (!_.isUndefined(cc) || (!_.isUndefined(ccList) && ccList.length > 0)) {
			return options.fn(this);
		} else {
			return options.inverse(this);
		}
	});

	Handlebars.registerHelper('unlessHasCC', function(cc, ccList, options) {
		if (_.isUndefined(cc) && (_.isUndefined(ccList) || ccList.length === 0)) {
			return options.fn(this);
		} else {
			return options.inverse(this);
		}
	});

	Handlebars.registerHelper('t', function(text) {
		return t('mail', text);
	});

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
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 87 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
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

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Marionette = __webpack_require__(2);

	/**
	 * @class Router
	 */
	var Router = Marionette.AppRouter.extend({
		appRoutes: {
			'': 'default',
			'accounts/:accountId/folders/:folderId': 'showFolder',
			'accounts/:accountId/folders/:folderId/search/:query': 'searchFolder',
			'mailto(?:params)': 'mailTo',
			'setup': 'showSetup',
			'shortcuts': 'showKeyboardShortcuts',
			'accounts/:accountId/settings': 'showAccountSettings'
		}
	});

	return Router;
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 88 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/* global Promise */

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

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var _ = __webpack_require__(3);
	var Backbone = __webpack_require__(6);
	var Radio = __webpack_require__(1);
	var FolderController = __webpack_require__(12);

	/**
	 * @class RoutingController
	 */
	var RoutingController = function(accounts) {
		this.initialize(accounts);
	};

	RoutingController.prototype = {
		accounts: undefined,
		initialize: function(accounts) {
			this.accounts = accounts;

			Radio.navigation.on('folder', _.bind(this.showFolder, this));
			Radio.navigation.on('search', _.bind(this.searchFolder, this));
			Radio.navigation.on('setup', _.bind(this.showSetup, this));
			Radio.navigation.on('accountsettings', _.bind(this.showAccountSettings, this));
			Radio.navigation.on('keyboardshortcuts', _.bind(this.showKeyboardShortcuts, this));
		},
		_navigate: function(route, options) {
			options = options || {};
			Backbone.history.navigate(route, options);
		},

		/**
		 * Handle mailto links
		 *
		 * @returns {Promise}
		 */
		_handleMailto: function(params) {
			var composerOptions = {};
			params = params.split('&');

			_.each(params, function(param) {
				param = param.split('=');
				var key = param[0];
				var value = param[1];
				value = decodeURIComponent((value).replace(/\+/g, '%20'));

				switch (key) {
					case 'mailto':
					case 'to':
						composerOptions.to = value;
						break;
					case 'cc':
						composerOptions.cc = value;
						break;
					case 'bcc':
						composerOptions.bcc = value;
						break;
					case 'subject':
						composerOptions.subject = value;
						break;
					case 'body':
						composerOptions.body = value;
						break;
				}
			});

			return this.default(true).then(function() {
				Radio.ui.trigger('composer:show', composerOptions);
			}).catch(console.error.bind(this));
		},

		/**
		 * @param {bool} showComposer
		 * @returns {Promise}
		 */
		default: function(showComposer) {
			this._navigate('');
			var _this = this;
			if (this.accounts.isEmpty()) {
				// No account configured -> show setup
				return _this.showSetup();
			}

			// Show first folder of first account
			var firstAccount = this.accounts.at(0);
			var firstFolder = firstAccount.folders.at(0);
			return _this.showFolder(firstAccount.get('accountId'), firstFolder.get('id'), showComposer);
		},

		/**
		 * @param {int} accountId
		 * @param {string} folderId
		 * @param {bool} showComposer
		 * @returns {Promise}
		 */
		showFolder: function(accountId, folderId, showComposer) {
			this._navigate('accounts/' + accountId + '/folders/' + folderId);
			var _this = this;
			var account = this.accounts.get(accountId);
			if (_.isUndefined(account)) {
				// Unknown account id -> redirect
				Radio.ui.trigger('error:show', t('mail', 'Invalid account'));
				return _this.default();
			}

			var folder = account.getFolderById(folderId);
			if (_.isUndefined(folder)) {
				folder = account.folders.at(0);
				Radio.ui.trigger('error:show', t('mail', 'Invalid folder'));
				this._navigate('accounts/' + accountId + '/folders/' + folder.get('id'));
				return Promise.resolve();
			}
			return FolderController.showFolder(account, folder, !showComposer);
		},

		searchFolder: function(accountId, folderId, query) {
			if (!query || query === '') {
				this.showFolder(accountId, folderId);
				return;
			}

			this._navigate('accounts/' + accountId + '/folders/' + folderId + '/search/' + query);
			var account = this.accounts.get(accountId);
			if (_.isUndefined(account)) {
				// Unknown account id -> redirect
				Radio.ui.trigger('error:show', t('mail', 'Invalid account'));
				this.default();
				return;
			}

			var folder = account.getFolderById(folderId);
			if (_.isUndefined(folder)) {
				folder = account.folders.at(0);
				Radio.ui.trigger('error:show', t('mail', 'Invalid folder'));
				this._navigate('accounts/' + accountId + '/folders/' + folder.get('id'));
			}
			FolderController.searchFolder(account, folder, query);
		},
		mailTo: function(params) {
			this._handleMailto(params);
		},

		/**
		 * @returns {Promise}
		 */
		showSetup: function() {
			this._navigate('setup');
			Radio.ui.trigger('composer:leave');
			Radio.ui.trigger('navigation:hide');
			Radio.ui.trigger('setup:show');
			return Promise.resolve();
		},
		showKeyboardShortcuts: function() {
			this._navigate('shortcuts');
			Radio.ui.trigger('composer:leave');
			Radio.ui.trigger('keyboardShortcuts:show');
		},
		showAccountSettings: function(accountId) {
			this._navigate('accounts/' + accountId + '/settings');
			var account = this.accounts.get(accountId);
			if (_.isUndefined(account)) {
				// Unknown account id -> redirect
				Radio.ui.trigger('error:show', t('mail', 'Invalid account'));
				this.default();
				return;
			}
			Radio.ui.trigger('composer:leave');
			Radio.ui.trigger('navigation:hide');
			Radio.ui.trigger('accountsettings:show', account);
		}
	};

	return RoutingController;
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 89 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/* global Promise */

/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015, 2017
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var $ = __webpack_require__(5);
	var OC = __webpack_require__(7);
	var Radio = __webpack_require__(1);

	Radio.account.reply('create', createAccount);
	Radio.account.reply('entities', getAccountEntities);
	Radio.account.reply('delete', deleteAccount);

	function createAccount(config) {
		var url = OC.generateUrl('apps/mail/accounts');
		return new Promise(function(resolve, reject) {
			$.ajax(url, {
				data: config,
				type: 'POST',
				success: resolve,
				error: function(jqXHR, textStatus, errorThrown) {
					switch (jqXHR.status) {
						case 400:
							var response = JSON.parse(jqXHR.responseText);
							reject(t('mail', 'Error while creating the account: ' + response.message));
							break;
						default:
							var error = errorThrown || textStatus || t('mail', 'Unknown error');
							reject(t('mail', 'Error while creating the account: ' + error));
					}
				}
			});
		});
	}

	/**
	 * @private
	 * @returns {Promise}
	 */
	function loadAccountData() {
		var $serialized = $('#serialized-accounts');
		var accounts = __webpack_require__(0).accounts;

		if ($serialized.val() !== '') {
			var serialized = $serialized.val();
			var serialzedAccounts = JSON.parse(atob(serialized));

			accounts.reset();
			for (var i = 0; i < serialzedAccounts.length; i++) {
				accounts.add(serialzedAccounts[i]);
			}
			$serialized.val('');
			return Promise.resolve(accounts);
		}

		return new Promise(function(resolve, reject) {
			accounts.fetch({
				success: function() {
					// fetch resolves the Promise with the raw data returned by
					// the ajax call. Since we want the Backbone models, we have
					// to 'convert' the response here.
					resolve(accounts);
				},
				error: reject
			});
		});
	}

	/**
	 * @returns {Promise}
	 */
	function getAccountEntities() {
		return loadAccountData().then(function(accounts) {
			__webpack_require__(8).cleanUp(accounts);

			if (accounts.length > 1) {
				accounts.add({
					accountId: -1,
					isUnified: true
				}, {
					at: 0
				});
			}

			return accounts;
		});
	}

	/**
	 * @param {Account} account
	 * @returns {Promise}
	 */
	function deleteAccount(account) {
		var url = OC.generateUrl('/apps/mail/accounts/{accountId}', {
			accountId: account.get('accountId')
		});

		return Promise.resolve($.ajax(url, {
			type: 'DELETE'
		})).then(function() {
			// Delete cached message lists
			__webpack_require__(8).removeAccount(account);
		});
	}

	return {
		createAccount: createAccount
	};
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 90 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/* global Promise */

/**
 * @author Tahaa Karim <tahaalibra@gmail.com>
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

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var $ = __webpack_require__(5);
	var OC = __webpack_require__(7);
	var Radio = __webpack_require__(1);

	Radio.aliases.reply('save', saveAlias);
	Radio.aliases.reply('delete', deleteAlias);

	/**
	 * @param {Account} account
	 * @param alias
	 * @returns {Promise}
	 */
	function saveAlias(account, alias) {
		var url = OC.generateUrl('/apps/mail/accounts/{id}/aliases', {
			id: account.get('accountId')
		});
		var data = {
			type: 'POST',
			data: {
				accountId: account.get('accountId'),
				alias: alias.alias,
				aliasName: alias.name
			}
		};
		return Promise.resolve($.ajax(url, data));
	}

	/**
	 * @param {Account} account
	 * @param aliasId
	 * @returns {Promise}
	 */
	function deleteAlias(account, aliasId) {
		var url = OC.generateUrl('/apps/mail/accounts/{id}/aliases/{aliasId}', {
			id: account.get('accountId'),
			aliasId: aliasId
		});
		var data = {
			type: 'DELETE'
		};
		return Promise.resolve($.ajax(url, data));
	}

}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));

/***/ }),
/* 91 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/* global Promise */

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Luc Calaresu <dev@calaresu.com>
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

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var _ = __webpack_require__(3);
	var $ = __webpack_require__(5);
	var OC = __webpack_require__(7);
	var Radio = __webpack_require__(1);

	Radio.message.reply('save:cloud', saveToFiles);
	Radio.message.reply('attachment:download', downloadAttachment);
	Radio.attachment.reply('upload:local', uploadLocalAttachment);
	Radio.attachment.reply('upload:abort', abortLocalAttachment);
	Radio.attachment.reply('upload:finished', uploadLocalAttachmentFinished);

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {number} messageId
	 * @param {number} attachmentId
	 * @param {string} path
	 * @returns {Promise}
	 */
	function saveToFiles(account, folder, messageId, attachmentId, path) {
		var url = OC.generateUrl(
			'apps/mail/accounts/{accountId}/' +
			'folders/{folderId}/messages/{messageId}/' +
			'attachment/{attachmentId}', {
				accountId: account.get('accountId'),
				folderId: folder.get('id'),
				messageId: messageId,
				attachmentId: attachmentId
			});

		var options = {
			data: {
				targetPath: path
			},
			type: 'POST'
		};

		return Promise.resolve($.ajax(url, options));
	}

	/**
	 * @param {string} url
	 * @returns {Promise}
	 */
	function downloadAttachment(url) {
		return Promise.resolve($.ajax(url));
	}

	/**
	 * @param {File} file
	 * @param {LocalAttachment} localAttachment
	 * @returns {Promise}
	 */
	function uploadLocalAttachment(file, localAttachment) {
		var fd = new FormData();
		fd.append('attachment', file);

		var progressCallback = localAttachment.onProgress;
		var url = OC.generateUrl('/apps/mail/attachments');

		return Promise.resolve($.ajax({
			url: url,
			type: 'POST',
			xhr: function() {
				var customXhr = $.ajaxSettings.xhr();
				// save the xhr into the model in order to :
				//  - distinguish upload and nextcloud file attachments
				//  - keep the upload status for later use
				localAttachment.set('uploadRequest', customXhr);
				// and start the request
				if (customXhr.upload && _.isFunction(progressCallback)) {
					customXhr.upload.addEventListener(
						'progress',
						progressCallback.bind(localAttachment),
						false);
				}
				return customXhr;
			},
			data: fd,
			processData: false,
			contentType: false
		})).then(function(data) {
			return data.id;
		});
	}

	/**
	 * This method is called when a local attachment upload should be aborted.
	 * If there is no upload ongoing, this method has no effect.
	 *
	 * @param {LocalAttachment} localAttachment
	 */
	function abortLocalAttachment(localAttachment) {
		var uploadRequest = localAttachment.get('uploadRequest');
		if (uploadRequest && uploadRequest.readyState < 4) {
			uploadRequest.abort();
		}
		localAttachment.collection.remove(localAttachment);
	}

	/**
	 * This method is called when a local attachment upload has
	 * successfully finished. The server returned the db attachment id.
	 *
	 * @param {LocalAttachment} localAttachment
	 * @param {number} fileId
	 */
	function uploadLocalAttachmentFinished(localAttachment, fileId) {
		if (fileId === undefined || localAttachment.get('progress') < 1) {
			localAttachment.set('uploadStatus', 2);  // error
		} else {
			/* If we have a file id (file successfully uploaded), we saved it */
			localAttachment.set('id', fileId);
			localAttachment.set('uploadStatus', 3);  // success
		}
		// we are done with the request, just get rid of it!
		localAttachment.unset('uploadRequest');
	}


	return {
		uploadLocalAttachment: uploadLocalAttachment,
		uploadLocalAttachmentFinished: uploadLocalAttachmentFinished
	};

}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 92 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/* global Promise */

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

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var _ = __webpack_require__(3);
	var $ = __webpack_require__(5);
	var Backbone = __webpack_require__(6);
	var dav = __webpack_require__(93);
	var ical = __webpack_require__(94);
	var OC = __webpack_require__(7);
	var Radio = __webpack_require__(1);
	var Calendar = __webpack_require__(95);

	Radio.dav.reply('calendars', getUserCalendars);
	Radio.dav.reply('calendar:import', importCalendarEvent);

	var client = new dav.Client({
		baseUrl: OC.linkToRemote('dav/calendars'),
		xmlNamespaces: {
			'DAV:': 'd',
			'urn:ietf:params:xml:ns:caldav': 'c',
			'http://apple.com/ns/ical/': 'aapl',
			'http://owncloud.org/ns': 'oc',
			'http://calendarserver.org/ns/': 'cs'
		}
	});
	var props = [
		'{DAV:}displayname',
		'{urn:ietf:params:xml:ns:caldav}calendar-description',
		'{urn:ietf:params:xml:ns:caldav}calendar-timezone',
		'{http://apple.com/ns/ical/}calendar-order',
		'{http://apple.com/ns/ical/}calendar-color',
		'{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set',
		'{http://owncloud.org/ns}calendar-enabled',
		'{DAV:}acl',
		'{DAV:}owner',
		'{http://owncloud.org/ns}invite'
	];

	function getResponseCodeFromHTTPResponse(t) {
		return parseInt(t.split(' ')[1]);
	}

	function getACLFromResponse(properties) {
		var canWrite = false;
		var acl = properties['{DAV:}acl'];
		if (acl) {
			for (var k = 0; k < acl.length; k++) {
				var href = acl[k].getElementsByTagNameNS('DAV:', 'href');
				if (href.length === 0) {
					continue;
				}
				href = href[0].textContent;
				var writeNode = acl[k].getElementsByTagNameNS('DAV:', 'write');
				if (writeNode.length > 0) {
					canWrite = true;
				}
			}
		}
		properties.canWrite = canWrite;
	}
	;

	function getCalendarData(properties) {
		getACLFromResponse(properties);

		var data = {
			displayname: properties['{DAV:}displayname'],
			color: properties['{http://apple.com/ns/ical/}calendar-color'],
			order: properties['{http://apple.com/ns/ical/}calendar-order'],
			components: {
				vevent: false
			},
			writable: properties.canWrite
		};

		var components = properties['{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set'] || [];
		for (var i = 0; i < components.length; i++) {
			var name = components[i].attributes.getNamedItem('name').textContent.toLowerCase();
			if (data.components.hasOwnProperty(name)) {
				data.components[name] = true;
			}
		}

		return data;
	}

	/**
	 * @returns {Promise}
	 */
	function getUserCalendars() {
		var url = OC.linkToRemote('dav/calendars') + '/' + OC.currentUser + '/';

		return client.propFind(url, props, 1, {
			requesttoken: OC.requestToken
		}).then(function(data) {
			var calendars = new Backbone.Collection();

			_.each(data.body, function(cal) {
				if (cal.propStat.length < 1) {
					return;
				}
				if (getResponseCodeFromHTTPResponse(cal.propStat[0].status) === 200) {
					var properties = getCalendarData(cal.propStat[0].properties);
					if (properties && properties.components.vevent && properties.writable === true) {
						properties.url = cal.href;
						calendars.push(new Calendar(properties));
					}
				}
			});

			return calendars;
		});
	}

	function getRandomString() {
		var str = '';
		for (var i = 0; i < 7; i++) {
			str += Math.random().toString(36).substring(7);
		}
		return str;
	}

	function createICalElement() {
		var root = new ical.Component(['vcalendar', [], []]);

		root.updatePropertyWithValue('prodid', '-//' + OC.theme.name + ' Mail');

		return root;
	}

	function splitCalendar(data) {
		var timezones = [];
		var allObjects = {};
		var jCal = ical.parse(data);
		var components = new ical.Component(jCal);

		var vtimezones = components.getAllSubcomponents('vtimezone');
		_.each(vtimezones, function(vtimezone) {
			timezones.push(vtimezone);
		});

		var componentNames = ['vevent', 'vjournal', 'vtodo'];
		_.each(componentNames, function(componentName) {
			var vobjects = components.getAllSubcomponents(componentName);
			allObjects[componentName] = {};

			_.each(vobjects, function(vobject) {
				var uid = vobject.getFirstPropertyValue('uid');
				allObjects[componentName][uid] = allObjects[componentName][uid] || [];
				allObjects[componentName][uid].push(vobject);
			});
		});

		var split = [];
		_.each(componentNames, function(componentName) {
			split[componentName] = [];
			_.each(allObjects[componentName], function(objects) {
				var component = createICalElement();
				_.each(timezones, function(timezone) {
					component.addSubcomponent(timezone);
				});
				_.each(objects, function(object) {
					component.addSubcomponent(object);
				});
				split[componentName].push(component.toString());
			});
		});

		return {
			name: components.getFirstPropertyValue('x-wr-calname'),
			color: components.getFirstPropertyValue('x-apple-calendar-color'),
			split: split
		};
	}

	/**
	 * @param {string} url
	 * @param {object} data
	 * @returns {Promise}
	 */
	function importCalendarEvent(url, data) {
		var promises = [];

		var file = splitCalendar(data);

		var componentNames = ['vevent', 'vjournal', 'vtodo'];
		_.each(componentNames, function(componentName) {
			_.each(file.split[componentName], function(component) {
				promises.push(Promise.resolve($.ajax({
					url: url + getRandomString(),
					method: 'PUT',
					contentType: 'text/calendar; charset=utf-8',
					data: component
				})));
			});
		});

		return Promise.all(promises);
	}
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 93 */
/***/ (function(module, exports) {

if (typeof dav == 'undefined') { dav = {}; };

dav._XML_CHAR_MAP = {
    '<': '&lt;',
    '>': '&gt;',
    '&': '&amp;',
    '"': '&quot;',
    "'": '&apos;'
};

dav._escapeXml = function(s) {
    return s.replace(/[<>&"']/g, function (ch) {
        return dav._XML_CHAR_MAP[ch];
    });
};

dav.Client = function(options) {
    var i;
    for(i in options) {
        this[i] = options[i];
    }

};

dav.Client.prototype = {

    baseUrl : null,

    userName : null,

    password : null,


    xmlNamespaces : {
        'DAV:' : 'd'
    },

    /**
     * Generates a propFind request.
     *
     * @param {string} url Url to do the propfind request on
     * @param {Array} properties List of properties to retrieve.
     * @param {Object} [headers] headers
     * @return {Promise}
     */
    propFind : function(url, properties, depth, headers) {

        if(typeof depth == "undefined") {
            depth = 0;
        }

        headers = headers || {};

        headers['Depth'] = depth;
        headers['Content-Type'] = 'application/xml; charset=utf-8';

        var body =
            '<?xml version="1.0"?>\n' +
            '<d:propfind ';
        var namespace;
        for (namespace in this.xmlNamespaces) {
            body += ' xmlns:' + this.xmlNamespaces[namespace] + '="' + namespace + '"';
        }
        body += '>\n' +
            '  <d:prop>\n';

        for(var ii in properties) {

            var property = this.parseClarkNotation(properties[ii]);
            if (this.xmlNamespaces[property.namespace]) {
                body+='    <' + this.xmlNamespaces[property.namespace] + ':' + property.name + ' />\n';
            } else {
                body+='    <x:' + property.name + ' xmlns:x="' + property.namespace + '" />\n';
            }

        }
        body+='  </d:prop>\n';
        body+='</d:propfind>';

        return this.request('PROPFIND', url, headers, body).then(
            function(result) {

                if (depth===0) {
                    return {
                        status: result.status,
                        body: result.body[0],
                        xhr: result.xhr
                    };
                } else {
                    return {
                        status: result.status,
                        body: result.body,
                        xhr: result.xhr
                    };
                }

            }.bind(this)
        );

    },

    /**
     * Generates a propPatch request.
     *
     * @param {string} url Url to do the proppatch request on
     * @param {Array} properties List of properties to store.
     * @param {Object} [headers] headers
     * @return {Promise}
     */
    propPatch : function(url, properties, headers) {
        headers = headers || {};

        headers['Content-Type'] = 'application/xml; charset=utf-8';

        var body =
            '<?xml version="1.0"?>\n' +
            '<d:propertyupdate ';
        var namespace;
        for (namespace in this.xmlNamespaces) {
            body += ' xmlns:' + this.xmlNamespaces[namespace] + '="' + namespace + '"';
        }
        body += '>\n' +
            '  <d:set>\n' +
            '   <d:prop>\n';

        for(var ii in properties) {

            var property = this.parseClarkNotation(ii);
            var propName;
            var propValue = properties[ii];
            if (this.xmlNamespaces[property.namespace]) {
                propName = this.xmlNamespaces[property.namespace] + ':' + property.name;
            } else {
                propName = 'x:' + property.name + ' xmlns:x="' + property.namespace + '"';
            }
            body += '      <' + propName + '>' + dav._escapeXml(propValue) + '</' + propName + '>\n';
        }
        body+='    </d:prop>\n';
        body+='  </d:set>\n';
        body+='</d:propertyupdate>';

        return this.request('PROPPATCH', url, headers, body).then(
            function(result) {
                return {
                    status: result.status,
                    body: result.body,
                    xhr: result.xhr
                };
            }.bind(this)
        );

    },

    /**
     * Performs a HTTP request, and returns a Promise
     *
     * @param {string} method HTTP method
     * @param {string} url Relative or absolute url
     * @param {Object} headers HTTP headers as an object.
     * @param {string} body HTTP request body.
     * @return {Promise}
     */
    request : function(method, url, headers, body) {

        var self = this;
        var xhr = this.xhrProvider();
        headers = headers || {};
        
        if (this.userName) {
            headers['Authorization'] = 'Basic ' + btoa(this.userName + ':' + this.password);
            // xhr.open(method, this.resolveUrl(url), true, this.userName, this.password);
        }
        xhr.open(method, this.resolveUrl(url), true);
        var ii;
        for(ii in headers) {
            xhr.setRequestHeader(ii, headers[ii]);
        }

        // Work around for edge
        if (body === undefined) {
            xhr.send();
        } else {
            xhr.send(body);
        }

        return new Promise(function(fulfill, reject) {

            xhr.onreadystatechange = function() {

                if (xhr.readyState !== 4) {
                    return;
                }

                var resultBody = xhr.response;
                if (xhr.status === 207) {
                    resultBody = self.parseMultiStatus(xhr.response);
                }

                fulfill({
                    body: resultBody,
                    status: xhr.status,
                    xhr: xhr
                });

            };

            xhr.ontimeout = function() {

                reject(new Error('Timeout exceeded'));

            };

        });

    },

    /**
     * Returns an XMLHttpRequest object.
     *
     * This is in its own method, so it can be easily overridden.
     *
     * @return {XMLHttpRequest}
     */
    xhrProvider : function() {

        return new XMLHttpRequest();

    },

    /**
     * Parses a property node.
     *
     * Either returns a string if the node only contains text, or returns an
     * array of non-text subnodes.
     *
     * @param {Object} propNode node to parse
     * @return {string|Array} text content as string or array of subnodes, excluding text nodes
     */
    _parsePropNode: function(propNode) {
        var content = null;
        if (propNode.childNodes && propNode.childNodes.length > 0) {
            var subNodes = [];
            // filter out text nodes
            for (var j = 0; j < propNode.childNodes.length; j++) {
                var node = propNode.childNodes[j];
                if (node.nodeType === 1) {
                    subNodes.push(node);
                }
            }
            if (subNodes.length) {
                content = subNodes;
            }
        }

        return content || propNode.textContent || propNode.text || '';
    },

    /**
     * Parses a multi-status response body.
     *
     * @param {string} xmlBody
     * @param {Array}
     */
    parseMultiStatus : function(xmlBody) {

        var parser = new DOMParser();
        var doc = parser.parseFromString(xmlBody, "application/xml");

        var resolver = function(foo) {
            var ii;
            for(ii in this.xmlNamespaces) {
                if (this.xmlNamespaces[ii] === foo) {
                    return ii;
                }
            }
        }.bind(this);

        var responseIterator = doc.evaluate('/d:multistatus/d:response', doc, resolver, XPathResult.ANY_TYPE, null);

        var result = [];
        var responseNode = responseIterator.iterateNext();

        while(responseNode) {

            var response = {
                href : null,
                propStat : []
            };

            response.href = doc.evaluate('string(d:href)', responseNode, resolver, XPathResult.ANY_TYPE, null).stringValue;

            var propStatIterator = doc.evaluate('d:propstat', responseNode, resolver, XPathResult.ANY_TYPE, null);
            var propStatNode = propStatIterator.iterateNext();

            while(propStatNode) {

                var propStat = {
                    status : doc.evaluate('string(d:status)', propStatNode, resolver, XPathResult.ANY_TYPE, null).stringValue,
                    properties : [],
                };

                var propIterator = doc.evaluate('d:prop/*', propStatNode, resolver, XPathResult.ANY_TYPE, null);

                var propNode = propIterator.iterateNext();
                while(propNode) {
                    var content = this._parsePropNode(propNode);
                    propStat.properties['{' + propNode.namespaceURI + '}' + propNode.localName] = content;
                    propNode = propIterator.iterateNext();

                }
                response.propStat.push(propStat);
                propStatNode = propStatIterator.iterateNext();


            }

            result.push(response);
            responseNode = responseIterator.iterateNext();

        }

        return result;

    },

    /**
     * Takes a relative url, and maps it to an absolute url, using the baseUrl
     *
     * @param {string} url
     * @return {string}
     */
    resolveUrl : function(url) {

        // Note: this is rudamentary.. not sure yet if it handles every case.
        if (/^https?:\/\//i.test(url)) {
            // absolute
            return url;
        }

        var baseParts = this.parseUrl(this.baseUrl);
        if (url.charAt('/')) {
            // Url starts with a slash
            return baseParts.root + url;
        }

        // Url does not start with a slash, we need grab the base url right up until the last slash.
        var newUrl = baseParts.root + '/';
        if (baseParts.path.lastIndexOf('/')!==-1) {
            newUrl = newUrl = baseParts.path.subString(0, baseParts.path.lastIndexOf('/')) + '/';
        }
        newUrl+=url;
        return url;

    },

    /**
     * Parses a url and returns its individual components.
     *
     * @param {String} url
     * @return {Object}
     */
    parseUrl : function(url) {

         var parts = url.match(/^(?:([A-Za-z]+):)?(\/{0,3})([0-9.\-A-Za-z]+)(?::(\d+))?(?:\/([^?#]*))?(?:\?([^#]*))?(?:#(.*))?$/);
         var result = {
             url : parts[0],
             scheme : parts[1],
             host : parts[3],
             port : parts[4],
             path : parts[5],
             query : parts[6],
             fragment : parts[7],
         };
         result.root =
            result.scheme + '://' +
            result.host +
            (result.port ? ':' + result.port : '');

         return result;

    },

    parseClarkNotation : function(propertyName) {

        var result = propertyName.match(/^{([^}]+)}(.*)$/);
        if (!result) {
            return;
        }

        return {
            name : result[2],
            namespace : result[1]
        };

    }

};



/***/ }),
/* 94 */
/***/ (function(module, exports, __webpack_require__) {

 true?ICAL=module.exports:"object"!=typeof ICAL&&(this.ICAL={}),ICAL.foldLength=75,ICAL.newLineChar="\r\n",ICAL.helpers={isStrictlyNaN:function(a){return"number"==typeof a&&isNaN(a)},strictParseInt:function(a){var b=parseInt(a,10);if(ICAL.helpers.isStrictlyNaN(b))throw new Error('Could not extract integer from "'+a+'"');return b},formatClassType:function(a,b){if("undefined"!=typeof a)return a instanceof b?a:new b(a)},unescapedIndexOf:function(a,b,c){for(;(c=a.indexOf(b,c))!==-1;){if(!(c>0&&"\\"===a[c-1]))return c;c+=1}return-1},binsearchInsert:function(a,b,c){if(!a.length)return 0;for(var d,e,f=0,g=a.length-1;f<=g;)if(d=f+Math.floor((g-f)/2),e=c(b,a[d]),e<0)g=d-1;else{if(!(e>0))break;f=d+1}return e<0?d:e>0?d+1:d},dumpn:function(){ICAL.debug&&("undefined"!=typeof console&&"log"in console?ICAL.helpers.dumpn=function(a){console.log(a)}:ICAL.helpers.dumpn=function(a){dump(a+"\n")},ICAL.helpers.dumpn(arguments[0]))},clone:function(a,b){if(a&&"object"==typeof a){if(a instanceof Date)return new Date(a.getTime());if("clone"in a)return a.clone();if(Array.isArray(a)){for(var c=[],d=0;d<a.length;d++)c.push(b?ICAL.helpers.clone(a[d],!0):a[d]);return c}var e={};for(var f in a)Object.prototype.hasOwnProperty.call(a,f)&&(b?e[f]=ICAL.helpers.clone(a[f],!0):e[f]=a[f]);return e}return a},foldline:function(a){for(var b="",c=a||"";c.length;)b+=ICAL.newLineChar+" "+c.substr(0,ICAL.foldLength),c=c.substr(ICAL.foldLength);return b.substr(ICAL.newLineChar.length+1)},pad2:function(a){"string"!=typeof a&&("number"==typeof a&&(a=parseInt(a)),a=String(a));var b=a.length;switch(b){case 0:return"00";case 1:return"0"+a;default:return a}},trunc:function(a){return a<0?Math.ceil(a):Math.floor(a)},inherits:function(a,b,c){function d(){}d.prototype=a.prototype,b.prototype=new d,c&&ICAL.helpers.extend(c,b.prototype)},extend:function(a,b){for(var c in a){var d=Object.getOwnPropertyDescriptor(a,c);d&&!Object.getOwnPropertyDescriptor(b,c)&&Object.defineProperty(b,c,d)}return b}},ICAL.design=function(){"use strict";function a(a,b){var d={matches:/.*/,fromICAL:function(b,d){return c(b,a,d)},toICAL:function(a,c){var d=b;return c&&(d=new RegExp(d.source+"|"+c)),a.replace(d,function(a){switch(a){case"\\":return"\\\\";case";":return"\\;";case",":return"\\,";case"\n":return"\\n";default:return a}})}};return d}function b(a){switch(a){case"\\\\":return"\\";case"\\;":return";";case"\\,":return",";case"\\n":case"\\N":return"\n";default:return a}}function c(a,c,d){return a.indexOf("\\")===-1?a:(d&&(c=new RegExp(c.source+"|\\\\"+d)),a.replace(c,b))}var d=/\\\\|\\;|\\,|\\[Nn]/g,e=/\\|;|,|\n/g,f=/\\\\|\\,|\\[Nn]/g,g=/\\|,|\n/g,h={defaultType:"text"},i={defaultType:"text",multiValue:","},j={defaultType:"text",structuredValue:";"},k={defaultType:"integer"},l={defaultType:"date-time",allowedTypes:["date-time","date"]},m={defaultType:"date-time"},n={defaultType:"uri"},o={defaultType:"utc-offset"},p={defaultType:"recur"},q={defaultType:"date-and-or-time",allowedTypes:["date-time","date","text"]},r={categories:i,url:n,version:h,uid:h},s={boolean:{values:["TRUE","FALSE"],fromICAL:function(a){switch(a){case"TRUE":return!0;case"FALSE":return!1;default:return!1}},toICAL:function(a){return a?"TRUE":"FALSE"}},float:{matches:/^[+-]?\d+\.\d+$/,fromICAL:function(a){var b=parseFloat(a);return ICAL.helpers.isStrictlyNaN(b)?0:b},toICAL:function(a){return String(a)}},integer:{fromICAL:function(a){var b=parseInt(a);return ICAL.helpers.isStrictlyNaN(b)?0:b},toICAL:function(a){return String(a)}},"utc-offset":{toICAL:function(a){return a.length<7?a.substr(0,3)+a.substr(4,2):a.substr(0,3)+a.substr(4,2)+a.substr(7,2)},fromICAL:function(a){return a.length<6?a.substr(0,3)+":"+a.substr(3,2):a.substr(0,3)+":"+a.substr(3,2)+":"+a.substr(5,2)},decorate:function(a){return ICAL.UtcOffset.fromString(a)},undecorate:function(a){return a.toString()}}},t={cutype:{values:["INDIVIDUAL","GROUP","RESOURCE","ROOM","UNKNOWN"],allowXName:!0,allowIanaToken:!0},"delegated-from":{valueType:"cal-address",multiValue:",",multiValueSeparateDQuote:!0},"delegated-to":{valueType:"cal-address",multiValue:",",multiValueSeparateDQuote:!0},encoding:{values:["8BIT","BASE64"]},fbtype:{values:["FREE","BUSY","BUSY-UNAVAILABLE","BUSY-TENTATIVE"],allowXName:!0,allowIanaToken:!0},member:{valueType:"cal-address",multiValue:",",multiValueSeparateDQuote:!0},partstat:{values:["NEEDS-ACTION","ACCEPTED","DECLINED","TENTATIVE","DELEGATED","COMPLETED","IN-PROCESS"],allowXName:!0,allowIanaToken:!0},range:{values:["THISLANDFUTURE"]},related:{values:["START","END"]},reltype:{values:["PARENT","CHILD","SIBLING"],allowXName:!0,allowIanaToken:!0},role:{values:["REQ-PARTICIPANT","CHAIR","OPT-PARTICIPANT","NON-PARTICIPANT"],allowXName:!0,allowIanaToken:!0},rsvp:{values:["TRUE","FALSE"]},"sent-by":{valueType:"cal-address"},tzid:{matches:/^\//},value:{values:["binary","boolean","cal-address","date","date-time","duration","float","integer","period","recur","text","time","uri","utc-offset"],allowXName:!0,allowIanaToken:!0}},u=ICAL.helpers.extend(s,{text:a(d,e),uri:{},binary:{decorate:function(a){return ICAL.Binary.fromString(a)},undecorate:function(a){return a.toString()}},"cal-address":{},date:{decorate:function(a,b){return ICAL.Time.fromDateString(a,b)},undecorate:function(a){return a.toString()},fromICAL:function(a){return a.substr(0,4)+"-"+a.substr(4,2)+"-"+a.substr(6,2)},toICAL:function(a){return a.length>11?a:a.substr(0,4)+a.substr(5,2)+a.substr(8,2)}},"date-time":{fromICAL:function(a){var b=a.substr(0,4)+"-"+a.substr(4,2)+"-"+a.substr(6,2)+"T"+a.substr(9,2)+":"+a.substr(11,2)+":"+a.substr(13,2);return a[15]&&"Z"===a[15]&&(b+="Z"),b},toICAL:function(a){if(a.length<19)return a;var b=a.substr(0,4)+a.substr(5,2)+a.substr(8,5)+a.substr(14,2)+a.substr(17,2);return a[19]&&"Z"===a[19]&&(b+="Z"),b},decorate:function(a,b){return ICAL.Time.fromDateTimeString(a,b)},undecorate:function(a){return a.toString()}},duration:{decorate:function(a){return ICAL.Duration.fromString(a)},undecorate:function(a){return a.toString()}},period:{fromICAL:function(a){var b=a.split("/");return b[0]=u["date-time"].fromICAL(b[0]),ICAL.Duration.isValueString(b[1])||(b[1]=u["date-time"].fromICAL(b[1])),b},toICAL:function(a){return a[0]=u["date-time"].toICAL(a[0]),ICAL.Duration.isValueString(a[1])||(a[1]=u["date-time"].toICAL(a[1])),a.join("/")},decorate:function(a,b){return ICAL.Period.fromJSON(a,b)},undecorate:function(a){return a.toJSON()}},recur:{fromICAL:function(a){return ICAL.Recur._stringToData(a,!0)},toICAL:function(a){var b="";for(var c in a)if(Object.prototype.hasOwnProperty.call(a,c)){var d=a[c];"until"==c?d=d.length>10?u["date-time"].toICAL(d):u.date.toICAL(d):"wkst"==c?"number"==typeof d&&(d=ICAL.Recur.numericDayToIcalDay(d)):Array.isArray(d)&&(d=d.join(",")),b+=c.toUpperCase()+"="+d+";"}return b.substr(0,b.length-1)},decorate:function(a){return ICAL.Recur.fromData(a)},undecorate:function(a){return a.toJSON()}},time:{fromICAL:function(a){if(a.length<6)return a;var b=a.substr(0,2)+":"+a.substr(2,2)+":"+a.substr(4,2);return"Z"===a[6]&&(b+="Z"),b},toICAL:function(a){if(a.length<8)return a;var b=a.substr(0,2)+a.substr(3,2)+a.substr(6,2);return"Z"===a[8]&&(b+="Z"),b}}}),v=ICAL.helpers.extend(r,{action:h,attach:{defaultType:"uri"},attendee:{defaultType:"cal-address"},calscale:h,class:h,comment:h,completed:m,contact:h,created:m,description:h,dtend:l,dtstamp:m,dtstart:l,due:l,duration:{defaultType:"duration"},exdate:{defaultType:"date-time",allowedTypes:["date-time","date"],multiValue:","},exrule:p,freebusy:{defaultType:"period",multiValue:","},geo:{defaultType:"float",structuredValue:";"},"last-modified":m,location:h,method:h,organizer:{defaultType:"cal-address"},"percent-complete":k,priority:k,prodid:h,"related-to":h,repeat:k,rdate:{defaultType:"date-time",allowedTypes:["date-time","date","period"],multiValue:",",detectType:function(a){return a.indexOf("/")!==-1?"period":a.indexOf("T")===-1?"date":"date-time"}},"recurrence-id":l,resources:i,"request-status":j,rrule:p,sequence:k,status:h,summary:h,transp:h,trigger:{defaultType:"duration",allowedTypes:["duration","date-time"]},tzoffsetfrom:o,tzoffsetto:o,tzurl:n,tzid:h,tzname:h}),w=ICAL.helpers.extend(s,{text:a(f,g),uri:a(f,g),date:{decorate:function(a){return ICAL.VCardTime.fromDateAndOrTimeString(a,"date")},undecorate:function(a){return a.toString()},fromICAL:function(a){return 8==a.length?u.date.fromICAL(a):"-"==a[0]&&6==a.length?a.substr(0,4)+"-"+a.substr(4):a},toICAL:function(a){return 10==a.length?u.date.toICAL(a):"-"==a[0]&&7==a.length?a.substr(0,4)+a.substr(5):a}},time:{decorate:function(a){return ICAL.VCardTime.fromDateAndOrTimeString("T"+a,"time")},undecorate:function(a){return a.toString()},fromICAL:function(a){var b=w.time._splitZone(a,!0),c=b[0],d=b[1];return 6==d.length?d=d.substr(0,2)+":"+d.substr(2,2)+":"+d.substr(4,2):4==d.length&&"-"!=d[0]?d=d.substr(0,2)+":"+d.substr(2,2):5==d.length&&(d=d.substr(0,3)+":"+d.substr(3,2)),5!=c.length||"-"!=c[0]&&"+"!=c[0]||(c=c.substr(0,3)+":"+c.substr(3)),d+c},toICAL:function(a){var b=w.time._splitZone(a),c=b[0],d=b[1];return 8==d.length?d=d.substr(0,2)+d.substr(3,2)+d.substr(6,2):5==d.length&&"-"!=d[0]?d=d.substr(0,2)+d.substr(3,2):6==d.length&&(d=d.substr(0,3)+d.substr(4,2)),6!=c.length||"-"!=c[0]&&"+"!=c[0]||(c=c.substr(0,3)+c.substr(4)),d+c},_splitZone:function(a,b){var c,d,e=a.length-1,f=a.length-(b?5:6),g=a[f];return"Z"==a[e]?(c=a[e],d=a.substr(0,e)):a.length>6&&("-"==g||"+"==g)?(c=a.substr(f),d=a.substr(0,f)):(c="",d=a),[c,d]}},"date-time":{decorate:function(a){return ICAL.VCardTime.fromDateAndOrTimeString(a,"date-time")},undecorate:function(a){return a.toString()},fromICAL:function(a){return w["date-and-or-time"].fromICAL(a)},toICAL:function(a){return w["date-and-or-time"].toICAL(a)}},"date-and-or-time":{decorate:function(a){return ICAL.VCardTime.fromDateAndOrTimeString(a,"date-and-or-time")},undecorate:function(a){return a.toString()},fromICAL:function(a){var b=a.split("T");return(b[0]?w.date.fromICAL(b[0]):"")+(b[1]?"T"+w.time.fromICAL(b[1]):"")},toICAL:function(a){var b=a.split("T");return w.date.toICAL(b[0])+(b[1]?"T"+w.time.toICAL(b[1]):"")}},timestamp:u["date-time"],"language-tag":{matches:/^[a-zA-Z0-9\-]+$/}}),x={type:{valueType:"text",multiValue:","},value:{values:["text","uri","date","time","date-time","date-and-or-time","timestamp","boolean","integer","float","utc-offset","language-tag"],allowXName:!0,allowIanaToken:!0}},y=ICAL.helpers.extend(r,{adr:{defaultType:"text",structuredValue:";",multiValue:","},anniversary:q,bday:q,caladruri:n,caluri:n,clientpidmap:j,email:h,fburl:n,fn:h,gender:j,geo:n,impp:n,key:n,kind:h,lang:{defaultType:"language-tag"},logo:n,member:n,n:{defaultType:"text",structuredValue:";",multiValue:","},nickname:i,note:h,org:{defaultType:"text",structuredValue:";"},photo:n,related:n,rev:{defaultType:"timestamp"},role:h,sound:n,source:n,tel:{defaultType:"uri",allowedTypes:["uri","text"]},title:h,tz:{defaultType:"text",allowedTypes:["text","utc-offset","uri"]},xml:h}),z=ICAL.helpers.extend(s,{binary:u.binary,date:w.date,"date-time":w["date-time"],"phone-number":{},uri:u.uri,text:u.text,time:u.time,vcard:u.text,"utc-offset":{toICAL:function(a){return a.substr(0,7)},fromICAL:function(a){return a.substr(0,7)},decorate:function(a){return ICAL.UtcOffset.fromString(a)},undecorate:function(a){return a.toString()}}}),A={type:{valueType:"text",multiValue:","},value:{values:["text","uri","date","date-time","phone-number","time","boolean","integer","float","utc-offset","vcard","binary"],allowXName:!0,allowIanaToken:!0}},B=ICAL.helpers.extend(r,{fn:h,n:{defaultType:"text",structuredValue:";",multiValue:","},nickname:i,photo:{defaultType:"binary",allowedTypes:["binary","uri"]},bday:{defaultType:"date-time",allowedTypes:["date-time","date"],detectType:function(a){return a.indexOf("T")===-1?"date":"date-time"}},adr:{defaultType:"text",structuredValue:";",multiValue:","},label:h,tel:{defaultType:"phone-number"},email:h,mailer:h,tz:{defaultType:"utc-offset",allowedTypes:["utc-offset","text"]},geo:{defaultType:"float",structuredValue:";"},title:h,role:h,logo:{defaultType:"binary",allowedTypes:["binary","uri"]},agent:{defaultType:"vcard",allowedTypes:["vcard","text","uri"]},org:j,note:i,prodid:h,rev:{defaultType:"date-time",allowedTypes:["date-time","date"],detectType:function(a){return a.indexOf("T")===-1?"date":"date-time"}},"sort-string":h,sound:{defaultType:"binary",allowedTypes:["binary","uri"]},class:h,key:{defaultType:"binary",allowedTypes:["binary","text"]}}),C={value:u,param:t,property:v},D={value:w,param:x,property:y},E={value:z,param:A,property:B},F={defaultSet:C,defaultType:"unknown",components:{vcard:D,vcard3:E,vevent:C,vtodo:C,vjournal:C,valarm:C,vtimezone:C,daylight:C,standard:C},icalendar:C,vcard:D,vcard3:E,getDesignSet:function(a){var b=a&&a in F.components;return b?F.components[a]:F.defaultSet}};return F}(),ICAL.stringify=function(){"use strict";function a(c){"string"==typeof c[0]&&(c=[c]);for(var d=0,e=c.length,f="";d<e;d++)f+=a.component(c[d])+b;return f}var b="\r\n",c="unknown",d=ICAL.design,e=ICAL.helpers;a.component=function(c,e){var f=c[0].toUpperCase(),g="BEGIN:"+f+b,h=c[1],i=0,j=h.length,k=c[0];for("vcard"===k&&c[1].length>0&&("version"!==c[1][0][0]||"4.0"!==c[1][0][3])&&(k="vcard3"),e=e||d.getDesignSet(k);i<j;i++)g+=a.property(h[i],e)+b;for(var l=c[2],m=0,n=l.length;m<n;m++)g+=a.component(l[m],e)+b;return g+="END:"+f},a.property=function(b,e,f){var g,h=b[0].toUpperCase(),i=b[0],j=b[1],k=h;for(g in j){var l=j[g];if(j.hasOwnProperty(g)){var m=g in e.param&&e.param[g].multiValue;m&&Array.isArray(l)?(e.param[g].multiValueSeparateDQuote&&(m='"'+m+'"'),l=l.map(a._rfc6868Unescape),l=a.multiValue(l,m,"unknown",null,e)):l=a._rfc6868Unescape(l),k+=";"+g.toUpperCase(),k+="="+a.propertyValue(l)}}if(3===b.length)return k+":";var n=b[2];e||(e=d.defaultSet);var o,m=!1,p=!1,q=!1;return i in e.property?(o=e.property[i],"multiValue"in o&&(m=o.multiValue),"structuredValue"in o&&Array.isArray(b[3])&&(p=o.structuredValue),"defaultType"in o?n===o.defaultType&&(q=!0):n===c&&(q=!0)):n===c&&(q=!0),q||(k+=";VALUE="+n.toUpperCase()),k+=":",k+=m&&p?a.multiValue(b[3],p,n,m,e,p):m?a.multiValue(b.slice(3),m,n,null,e,!1):p?a.multiValue(b[3],p,n,null,e,p):a.value(b[3],n,e,!1),f?k:ICAL.helpers.foldline(k)},a.propertyValue=function(a){return e.unescapedIndexOf(a,",")===-1&&e.unescapedIndexOf(a,":")===-1&&e.unescapedIndexOf(a,";")===-1?a:'"'+a+'"'},a.multiValue=function(b,c,d,e,f,g){for(var h="",i=b.length,j=0;j<i;j++)h+=e&&Array.isArray(b[j])?a.multiValue(b[j],e,d,null,f,g):a.value(b[j],d,f,g),j!==i-1&&(h+=c);return h},a.value=function(a,b,c,d){return b in c.value&&"toICAL"in c.value[b]?c.value[b].toICAL(a,d):a},a._rfc6868Unescape=function(a){return a.replace(/[\n^"]/g,function(a){return f[a]})};var f={'"':"^'","\n":"^n","^":"^^"};return a}(),ICAL.parse=function(){"use strict";function a(a){this.message=a,this.name="ParserError";try{throw new Error}catch(a){if(a.stack){var b=a.stack.split("\n");b.shift(),this.stack=b.join("\n")}}}function b(c){var d={},e=d.component=[];if(d.stack=[e],b._eachLine(c,function(a,c){b._handleContentLine(c,d)}),d.stack.length>1)throw new a("invalid ical body. component began but did not end");return d=null,1==e.length?e[0]:e}var c=/[^ \t]/,d=":",e=";",f="=",g="unknown",h="text",i=ICAL.design,j=ICAL.helpers;a.prototype=Error.prototype,b.property=function(a,c){var d={component:[[],[]],designSet:c||i.defaultSet};return b._handleContentLine(a,d),d.component[1][0]},b.component=function(a){return b(a)},b.ParserError=a,b._handleContentLine=function(c,f){var h,j,k,l,m=c.indexOf(d),n=c.indexOf(e),o={};n!==-1&&m!==-1&&n>m&&(n=-1);var p;if(n!==-1){if(k=c.substring(0,n).toLowerCase(),p=b._parseParameters(c.substring(n),0,f.designSet),p[2]==-1)throw new a("Invalid parameters in '"+c+"'");if(o=p[0],h=p[1].length+p[2]+n,(j=c.substring(h).indexOf(d))===-1)throw new a("Missing parameter value in '"+c+"'");l=c.substring(h+j+1)}else{if(m===-1)throw new a('invalid line (no token ";" or ":") "'+c+'"');if(k=c.substring(0,m).toLowerCase(),l=c.substring(m+1),"begin"===k){var q=[l.toLowerCase(),[],[]];return 1===f.stack.length?f.component.push(q):f.component[2].push(q),f.stack.push(f.component),f.component=q,void(f.designSet||(f.designSet=i.getDesignSet(f.component[0])))}if("end"===k)return void(f.component=f.stack.pop())}var r,s,t=!1,u=!1;k in f.designSet.property&&(s=f.designSet.property[k],"multiValue"in s&&(t=s.multiValue),"structuredValue"in s&&(u=s.structuredValue),l&&"detectType"in s&&(r=s.detectType(l))),r||(r="value"in o?o.value.toLowerCase():s?s.defaultType:g),delete o.value;var v;t&&u?(l=b._parseMultiValue(l,u,r,[],t,f.designSet,u),v=[k,o,r,l]):t?(v=[k,o,r],b._parseMultiValue(l,t,r,v,null,f.designSet,!1)):u?(l=b._parseMultiValue(l,u,r,[],null,f.designSet,u),v=[k,o,r,l]):(l=b._parseValue(l,r,f.designSet,!1),v=[k,o,r,l]),"vcard"!==f.component[0]||0!==f.component[1].length||"version"===k&&"4.0"===l||(f.designSet=i.getDesignSet("vcard3")),f.component[1].push(v)},b._parseValue=function(a,b,c,d){return b in c.value&&"fromICAL"in c.value[b]?c.value[b].fromICAL(a,d):a},b._parseParameters=function(c,g,i){for(var k,l,m,n,o,p,q=g,r=0,s=f,t={},u=-1;r!==!1&&(r=j.unescapedIndexOf(c,s,r+1))!==-1;){if(k=c.substr(q+1,r-q-1),0==k.length)throw new a("Empty parameter name in '"+c+"'");l=k.toLowerCase(),n=l in i.param&&i.param[l].valueType?i.param[l].valueType:h,l in i.param&&(o=i.param[l].multiValue,i.param[l].multiValueSeparateDQuote&&(p=b._rfc6868Escape('"'+o+'"')));var v=c[r+1];if('"'===v){if(u=r+2,r=j.unescapedIndexOf(c,'"',u),o&&r!=-1)for(var w=!0;w;)c[r+1]==o&&'"'==c[r+2]?r=j.unescapedIndexOf(c,'"',r+3):w=!1;if(r===-1)throw new a('invalid line (no matching double quote) "'+c+'"');m=c.substr(u,r-u),q=j.unescapedIndexOf(c,e,r),q===-1&&(r=!1)}else{u=r+1;var x=j.unescapedIndexOf(c,e,u),y=j.unescapedIndexOf(c,d,u);y!==-1&&x>y?(x=y,r=!1):x===-1?(x=y===-1?c.length:y,r=!1):(q=x,r=x),m=c.substr(u,x-u)}if(m=b._rfc6868Escape(m),o){var z=p||o;t[l]=b._parseMultiValue(m,z,n,[],null,i)}else t[l]=b._parseValue(m,n,i)}return[t,m,u]},b._rfc6868Escape=function(a){return a.replace(/\^['n^]/g,function(a){return k[a]})};var k={"^'":'"',"^n":"\n","^^":"^"};return b._parseMultiValue=function(a,c,d,e,f,g,h){var i,k=0,l=0;if(0===c.length)return a;for(;(k=j.unescapedIndexOf(a,c,l))!==-1;)i=a.substr(l,k-l),i=f?b._parseMultiValue(i,f,d,[],null,g,h):b._parseValue(i,d,g,h),e.push(i),l=k+c.length;return i=a.substr(l),i=f?b._parseMultiValue(i,f,d,[],null,g,h):b._parseValue(i,d,g,h),e.push(i),1==e.length?e[0]:e},b._eachLine=function(a,b){var d,e,f,g=a.length,h=a.search(c),i=h;do i=a.indexOf("\n",h)+1,f=i>1&&"\r"===a[i-2]?2:1,0===i&&(i=g,f=0),e=a[h]," "===e||"\t"===e?d+=a.substr(h+1,i-h-(f+1)):(d&&b(null,d),d=a.substr(h,i-h-f)),h=i;while(i!==g);d=d.trim(),d.length&&b(null,d)},b}(),ICAL.Component=function(){"use strict";function a(a,b){"string"==typeof a&&(a=[a,[],[]]),this.jCal=a,this.parent=b||null}var b=1,c=2,d=0;return a.prototype={_hydratedPropertyCount:0,_hydratedComponentCount:0,get name(){return this.jCal[d]},get _designSet(){var a=this.parent&&this.parent._designSet;return a||ICAL.design.getDesignSet(this.name)},_hydrateComponent:function(b){if(this._components||(this._components=[],this._hydratedComponentCount=0),this._components[b])return this._components[b];var d=new a(this.jCal[c][b],this);return this._hydratedComponentCount++,this._components[b]=d},_hydrateProperty:function(a){if(this._properties||(this._properties=[],this._hydratedPropertyCount=0),this._properties[a])return this._properties[a];var c=new ICAL.Property(this.jCal[b][a],this);return this._hydratedPropertyCount++,this._properties[a]=c},getFirstSubcomponent:function(a){if(a){for(var b=0,e=this.jCal[c],f=e.length;b<f;b++)if(e[b][d]===a){var g=this._hydrateComponent(b);return g}}else if(this.jCal[c].length)return this._hydrateComponent(0);return null},getAllSubcomponents:function(a){var b=this.jCal[c].length,e=0;if(a){for(var f=this.jCal[c],g=[];e<b;e++)a===f[e][d]&&g.push(this._hydrateComponent(e));return g}if(!this._components||this._hydratedComponentCount!==b)for(;e<b;e++)this._hydrateComponent(e);return this._components||[]},hasProperty:function(a){for(var c=this.jCal[b],e=c.length,f=0;f<e;f++)if(c[f][d]===a)return!0;return!1},getFirstProperty:function(a){if(a){for(var c=0,e=this.jCal[b],f=e.length;c<f;c++)if(e[c][d]===a){var g=this._hydrateProperty(c);return g}}else if(this.jCal[b].length)return this._hydrateProperty(0);return null},getFirstPropertyValue:function(a){var b=this.getFirstProperty(a);return b?b.getFirstValue():null},getAllProperties:function(a){var c=this.jCal[b].length,e=0;if(a){for(var f=this.jCal[b],g=[];e<c;e++)a===f[e][d]&&g.push(this._hydrateProperty(e));return g}if(!this._properties||this._hydratedPropertyCount!==c)for(;e<c;e++)this._hydrateProperty(e);return this._properties||[]},_removeObjectByIndex:function(a,b,c){if(b=b||[],b[c]){var d=b[c];"parent"in d&&(d.parent=null)}b.splice(c,1),this.jCal[a].splice(c,1)},_removeObject:function(a,b,c){var e=0,f=this.jCal[a],g=f.length,h=this[b];if("string"==typeof c){for(;e<g;e++)if(f[e][d]===c)return this._removeObjectByIndex(a,h,e),!0}else if(h)for(;e<g;e++)if(h[e]&&h[e]===c)return this._removeObjectByIndex(a,h,e),!0;return!1},_removeAllObjects:function(a,b,c){for(var e=this[b],f=this.jCal[a],g=f.length-1;g>=0;g--)c&&f[g][d]!==c||this._removeObjectByIndex(a,e,g)},addSubcomponent:function(a){this._components||(this._components=[],this._hydratedComponentCount=0),a.parent&&a.parent.removeSubcomponent(a);var b=this.jCal[c].push(a.jCal);return this._components[b-1]=a,this._hydratedComponentCount++,a.parent=this,a},removeSubcomponent:function(a){var b=this._removeObject(c,"_components",a);return b&&this._hydratedComponentCount--,b},removeAllSubcomponents:function(a){var b=this._removeAllObjects(c,"_components",a);return this._hydratedComponentCount=0,b},addProperty:function(a){if(!(a instanceof ICAL.Property))throw new TypeError("must instance of ICAL.Property");this._properties||(this._properties=[],this._hydratedPropertyCount=0),a.parent&&a.parent.removeProperty(a);var c=this.jCal[b].push(a.jCal);return this._properties[c-1]=a,this._hydratedPropertyCount++,a.parent=this,a},addPropertyWithValue:function(a,b){var c=new ICAL.Property(a);return c.setValue(b),this.addProperty(c),c},updatePropertyWithValue:function(a,b){var c=this.getFirstProperty(a);return c?c.setValue(b):c=this.addPropertyWithValue(a,b),c},removeProperty:function(a){var c=this._removeObject(b,"_properties",a);return c&&this._hydratedPropertyCount--,c},removeAllProperties:function(a){var c=this._removeAllObjects(b,"_properties",a);return this._hydratedPropertyCount=0,c},toJSON:function(){return this.jCal},toString:function(){return ICAL.stringify.component(this.jCal,this._designSet)}},a.fromString=function(b){return new a(ICAL.parse.component(b))},a}(),ICAL.Property=function(){"use strict";function a(a,b){this._parent=b||null,"string"==typeof a?(this.jCal=[a,{},f.defaultType],this.jCal[d]=this.getDefaultType()):this.jCal=a,this._updateType()}var b=0,c=1,d=2,e=3,f=ICAL.design;return a.prototype={get type(){return this.jCal[d]},get name(){return this.jCal[b]},get parent(){return this._parent},set parent(a){var b=!this._parent||a&&a._designSet!=this._parent._designSet;return this._parent=a,this.type==f.defaultType&&b&&(this.jCal[d]=this.getDefaultType(),this._updateType()),a},get _designSet(){return this.parent?this.parent._designSet:f.defaultSet},_updateType:function(){var a=this._designSet;if(this.type in a.value){a.value[this.type];"decorate"in a.value[this.type]?this.isDecorated=!0:this.isDecorated=!1,this.name in a.property&&(this.isMultiValue="multiValue"in a.property[this.name],this.isStructuredValue="structuredValue"in a.property[this.name])}},_hydrateValue:function(a){return this._values&&this._values[a]?this._values[a]:this.jCal.length<=e+a?null:this.isDecorated?(this._values||(this._values=[]),this._values[a]=this._decorate(this.jCal[e+a])):this.jCal[e+a]},_decorate:function(a){return this._designSet.value[this.type].decorate(a,this)},_undecorate:function(a){return this._designSet.value[this.type].undecorate(a,this)},_setDecoratedValue:function(a,b){this._values||(this._values=[]),"object"==typeof a&&"icaltype"in a?(this.jCal[e+b]=this._undecorate(a),this._values[b]=a):(this.jCal[e+b]=a,this._values[b]=this._decorate(a))},getParameter:function(a){return a in this.jCal[c]?this.jCal[c][a]:void 0},setParameter:function(a,b){var d=a.toLowerCase();"string"==typeof b&&d in this._designSet.param&&"multiValue"in this._designSet.param[d]&&(b=[b]),this.jCal[c][a]=b},removeParameter:function(a){delete this.jCal[c][a]},getDefaultType:function(){var a=this.jCal[b],c=this._designSet;if(a in c.property){var d=c.property[a];if("defaultType"in d)return d.defaultType}return f.defaultType},resetType:function(a){this.removeAllValues(),this.jCal[d]=a,this._updateType()},getFirstValue:function(){return this._hydrateValue(0)},getValues:function(){var a=this.jCal.length-e;if(a<1)return[];for(var b=0,c=[];b<a;b++)c[b]=this._hydrateValue(b);return c},removeAllValues:function(){this._values&&(this._values.length=0),this.jCal.length=3},setValues:function(a){if(!this.isMultiValue)throw new Error(this.name+": does not not support mulitValue.\noverride isMultiValue");var b=a.length,c=0;if(this.removeAllValues(),b>0&&"object"==typeof a[0]&&"icaltype"in a[0]&&this.resetType(a[0].icaltype),this.isDecorated)for(;c<b;c++)this._setDecoratedValue(a[c],c);else for(;c<b;c++)this.jCal[e+c]=a[c]},setValue:function(a){this.removeAllValues(),"object"==typeof a&&"icaltype"in a&&this.resetType(a.icaltype),this.isDecorated?this._setDecoratedValue(a,0):this.jCal[e]=a},toJSON:function(){return this.jCal},toICALString:function(){return ICAL.stringify.property(this.jCal,this._designSet,!0)}},a.fromString=function(b,c){return new a(ICAL.parse.property(b,c))},a}(),ICAL.UtcOffset=function(){function a(a){this.fromData(a)}return a.prototype={hours:0,minutes:0,factor:1,icaltype:"utc-offset",clone:function(){return ICAL.UtcOffset.fromSeconds(this.toSeconds())},fromData:function(a){if(a)for(var b in a)a.hasOwnProperty(b)&&(this[b]=a[b]);this._normalize()},fromSeconds:function(a){var b=Math.abs(a);return this.factor=a<0?-1:1,this.hours=ICAL.helpers.trunc(b/3600),b-=3600*this.hours,this.minutes=ICAL.helpers.trunc(b/60),this},toSeconds:function(){return this.factor*(60*this.minutes+3600*this.hours)},compare:function(a){var b=this.toSeconds(),c=a.toSeconds();return(b>c)-(c>b)},_normalize:function(){for(var a=this.toSeconds(),b=this.factor;a<-43200;)a+=97200;for(;a>50400;)a-=97200;this.fromSeconds(a),0==a&&(this.factor=b)},toICALString:function(){return ICAL.design.icalendar.value["utc-offset"].toICAL(this.toString())},toString:function(){return(1==this.factor?"+":"-")+ICAL.helpers.pad2(this.hours)+":"+ICAL.helpers.pad2(this.minutes)}},a.fromString=function(a){var b={};return b.factor="+"===a[0]?1:-1,b.hours=ICAL.helpers.strictParseInt(a.substr(1,2)),b.minutes=ICAL.helpers.strictParseInt(a.substr(4,2)),new ICAL.UtcOffset(b)},a.fromSeconds=function(b){var c=new a;return c.fromSeconds(b),c},a}(),ICAL.Binary=function(){function a(a){this.value=a}return a.prototype={icaltype:"binary",decodeValue:function(){return this._b64_decode(this.value)},setEncodedValue:function(a){this.value=this._b64_encode(a)},_b64_encode:function(a){var b,c,d,e,f,g,h,i,j="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",k=0,l=0,m="",n=[];if(!a)return a;do b=a.charCodeAt(k++),c=a.charCodeAt(k++),d=a.charCodeAt(k++),i=b<<16|c<<8|d,e=i>>18&63,f=i>>12&63,g=i>>6&63,h=63&i,n[l++]=j.charAt(e)+j.charAt(f)+j.charAt(g)+j.charAt(h);while(k<a.length);m=n.join("");var o=a.length%3;return(o?m.slice(0,o-3):m)+"===".slice(o||3)},_b64_decode:function(a){var b,c,d,e,f,g,h,i,j="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",k=0,l=0,m="",n=[];if(!a)return a;a+="";do e=j.indexOf(a.charAt(k++)),f=j.indexOf(a.charAt(k++)),g=j.indexOf(a.charAt(k++)),h=j.indexOf(a.charAt(k++)),i=e<<18|f<<12|g<<6|h,b=i>>16&255,c=i>>8&255,d=255&i,64==g?n[l++]=String.fromCharCode(b):64==h?n[l++]=String.fromCharCode(b,c):n[l++]=String.fromCharCode(b,c,d);while(k<a.length);return m=n.join("")},toString:function(){return this.value}},a.fromString=function(b){return new a(b)},a}(),function(){ICAL.Period=function(a){if(this.wrappedJSObject=this,a&&"start"in a){if(a.start&&!(a.start instanceof ICAL.Time))throw new TypeError(".start must be an instance of ICAL.Time");this.start=a.start}if(a&&a.end&&a.duration)throw new Error("cannot accept both end and duration");if(a&&"end"in a){if(a.end&&!(a.end instanceof ICAL.Time))throw new TypeError(".end must be an instance of ICAL.Time");this.end=a.end}if(a&&"duration"in a){if(a.duration&&!(a.duration instanceof ICAL.Duration))throw new TypeError(".duration must be an instance of ICAL.Duration");this.duration=a.duration}},ICAL.Period.prototype={start:null,end:null,duration:null,icalclass:"icalperiod",icaltype:"period",clone:function(){return ICAL.Period.fromData({start:this.start?this.start.clone():null,end:this.end?this.end.clone():null,duration:this.duration?this.duration.clone():null})},getDuration:function(){return this.duration?this.duration:this.end.subtractDate(this.start)},getEnd:function(){if(this.end)return this.end;var a=this.start.clone();return a.addDuration(this.duration),a},toString:function(){return this.start+"/"+(this.end||this.duration)},toJSON:function(){return[this.start.toString(),(this.end||this.duration).toString()]},toICALString:function(){return this.start.toICALString()+"/"+(this.end||this.duration).toICALString()}},ICAL.Period.fromString=function(a,b){var c=a.split("/");if(2!==c.length)throw new Error('Invalid string value: "'+a+'" must contain a "/" char.');var d={start:ICAL.Time.fromDateTimeString(c[0],b)},e=c[1];return ICAL.Duration.isValueString(e)?d.duration=ICAL.Duration.fromString(e):d.end=ICAL.Time.fromDateTimeString(e,b),new ICAL.Period(d)},ICAL.Period.fromData=function(a){return new ICAL.Period(a)},ICAL.Period.fromJSON=function(a,b){return ICAL.Duration.isValueString(a[1])?ICAL.Period.fromData({start:ICAL.Time.fromDateTimeString(a[0],b),duration:ICAL.Duration.fromString(a[1])}):ICAL.Period.fromData({start:ICAL.Time.fromDateTimeString(a[0],b),end:ICAL.Time.fromDateTimeString(a[1],b)})}}(),function(){function a(a,b,c){var d;switch(a){case"P":b&&"-"===b?c.isNegative=!0:c.isNegative=!1;break;case"D":d="days";break;case"W":d="weeks";break;case"H":d="hours";break;case"M":d="minutes";break;case"S":d="seconds";break;default:return 0}if(d){if(!b&&0!==b)throw new Error('invalid duration value: Missing number before "'+a+'"');var e=parseInt(b,10);if(ICAL.helpers.isStrictlyNaN(e))throw new Error('invalid duration value: Invalid number "'+b+'" before "'+a+'"');c[d]=e}return 1}var b=/([PDWHMTS]{1,1})/;ICAL.Duration=function(a){this.wrappedJSObject=this,this.fromData(a)},ICAL.Duration.prototype={weeks:0,days:0,hours:0,minutes:0,seconds:0,isNegative:!1,icalclass:"icalduration",icaltype:"duration",clone:function(){return ICAL.Duration.fromData(this)},toSeconds:function(){var a=this.seconds+60*this.minutes+3600*this.hours+86400*this.days+604800*this.weeks;return this.isNegative?-a:a},fromSeconds:function(a){var b=Math.abs(a);return this.isNegative=a<0,this.days=ICAL.helpers.trunc(b/86400),this.days%7==0?(this.weeks=this.days/7,this.days=0):this.weeks=0,b-=86400*(this.days+7*this.weeks),this.hours=ICAL.helpers.trunc(b/3600),b-=3600*this.hours,this.minutes=ICAL.helpers.trunc(b/60),b-=60*this.minutes,this.seconds=b,this},fromData:function(a){var b=["weeks","days","hours","minutes","seconds","isNegative"];for(var c in b)if(b.hasOwnProperty(c)){var d=b[c];a&&d in a?this[d]=a[d]:this[d]=0}},reset:function(){this.isNegative=!1,this.weeks=0,this.days=0,this.hours=0,this.minutes=0,this.seconds=0},compare:function(a){var b=this.toSeconds(),c=a.toSeconds();return(b>c)-(b<c)},normalize:function(){this.fromSeconds(this.toSeconds())},toString:function(){if(0==this.toSeconds())return"PT0S";var a="";return this.isNegative&&(a+="-"),
a+="P",this.weeks&&(a+=this.weeks+"W"),this.days&&(a+=this.days+"D"),(this.hours||this.minutes||this.seconds)&&(a+="T",this.hours&&(a+=this.hours+"H"),this.minutes&&(a+=this.minutes+"M"),this.seconds&&(a+=this.seconds+"S")),a},toICALString:function(){return this.toString()}},ICAL.Duration.fromSeconds=function(a){return(new ICAL.Duration).fromSeconds(a)},ICAL.Duration.isValueString=function(a){return"P"===a[0]||"P"===a[1]},ICAL.Duration.fromString=function(c){for(var d=0,e=Object.create(null),f=0;(d=c.search(b))!==-1;){var g=c[d],h=c.substr(0,d);c=c.substr(d+1),f+=a(g,h,e)}if(f<2)throw new Error('invalid duration value: Not enough duration components in "'+c+'"');return new ICAL.Duration(e)},ICAL.Duration.fromData=function(a){return new ICAL.Duration(a)}}(),function(){var a=["tzid","location","tznames","latitude","longitude"];ICAL.Timezone=function(a){this.wrappedJSObject=this,this.fromData(a)},ICAL.Timezone.prototype={tzid:"",location:"",tznames:"",latitude:0,longitude:0,component:null,expandedUntilYear:0,icalclass:"icaltimezone",fromData:function(b){if(this.expandedUntilYear=0,this.changes=[],b instanceof ICAL.Component)this.component=b;else{if(b&&"component"in b)if("string"==typeof b.component){var c=ICAL.parse(b.component);this.component=new ICAL.Component(c)}else b.component instanceof ICAL.Component?this.component=b.component:this.component=null;for(var d in a)if(a.hasOwnProperty(d)){var e=a[d];b&&e in b&&(this[e]=b[e])}}return this.component instanceof ICAL.Component&&!this.tzid&&(this.tzid=this.component.getFirstPropertyValue("tzid")),this},utcOffset:function(a){if(this==ICAL.Timezone.utcTimezone||this==ICAL.Timezone.localTimezone)return 0;if(this._ensureCoverage(a.year),!this.changes.length)return 0;for(var b={year:a.year,month:a.month,day:a.day,hour:a.hour,minute:a.minute,second:a.second},c=this._findNearbyChange(b),d=-1,e=1;;){var f=ICAL.helpers.clone(this.changes[c],!0);f.utcOffset<f.prevUtcOffset?ICAL.Timezone.adjust_change(f,0,0,0,f.utcOffset):ICAL.Timezone.adjust_change(f,0,0,0,f.prevUtcOffset);var g=ICAL.Timezone._compare_change_fn(b,f);if(g>=0?d=c:e=-1,e==-1&&d!=-1)break;if(c+=e,c<0)return 0;if(c>=this.changes.length)break}var h=this.changes[d],i=h.utcOffset-h.prevUtcOffset;if(i<0&&d>0){var j=ICAL.helpers.clone(h,!0);if(ICAL.Timezone.adjust_change(j,0,0,0,j.prevUtcOffset),ICAL.Timezone._compare_change_fn(b,j)<0){var k=this.changes[d-1],l=!1;h.is_daylight!=l&&k.is_daylight==l&&(h=k)}}return h.utcOffset},_findNearbyChange:function(a){var b=ICAL.helpers.binsearchInsert(this.changes,a,ICAL.Timezone._compare_change_fn);return b>=this.changes.length?this.changes.length-1:b},_ensureCoverage:function(a){if(ICAL.Timezone._minimumExpansionYear==-1){var b=ICAL.Time.now();ICAL.Timezone._minimumExpansionYear=b.year}var c=a;if(c<ICAL.Timezone._minimumExpansionYear&&(c=ICAL.Timezone._minimumExpansionYear),c+=ICAL.Timezone.EXTRA_COVERAGE,c>ICAL.Timezone.MAX_YEAR&&(c=ICAL.Timezone.MAX_YEAR),!this.changes.length||this.expandedUntilYear<a){for(var d=this.component.getAllSubcomponents(),e=d.length,f=0;f<e;f++)this._expandComponent(d[f],c,this.changes);this.changes.sort(ICAL.Timezone._compare_change_fn),this.expandedUntilYear=c}},_expandComponent:function(a,b,c){function d(a){return a.factor*(3600*a.hours+60*a.minutes)}function e(){var b={};return b.is_daylight="daylight"==a.name,b.utcOffset=d(a.getFirstProperty("tzoffsetto").getFirstValue()),b.prevUtcOffset=d(a.getFirstProperty("tzoffsetfrom").getFirstValue()),b}if(!a.hasProperty("dtstart")||!a.hasProperty("tzoffsetto")||!a.hasProperty("tzoffsetfrom"))return null;var f,g=a.getFirstProperty("dtstart").getFirstValue();if(a.hasProperty("rrule")||a.hasProperty("rdate")){var h=a.getAllProperties("rdate");for(var i in h)if(h.hasOwnProperty(i)){var j=h[i],k=j.getFirstValue();f=e(),f.year=k.year,f.month=k.month,f.day=k.day,k.isDate?(f.hour=g.hour,f.minute=g.minute,f.second=g.second,g.zone!=ICAL.Timezone.utcTimezone&&ICAL.Timezone.adjust_change(f,0,0,0,-f.prevUtcOffset)):(f.hour=k.hour,f.minute=k.minute,f.second=k.second,k.zone!=ICAL.Timezone.utcTimezone&&ICAL.Timezone.adjust_change(f,0,0,0,-f.prevUtcOffset)),c.push(f)}var l=a.getFirstProperty("rrule");if(l){l=l.getFirstValue(),f=e(),l.until&&l.until.zone==ICAL.Timezone.utcTimezone&&(l.until.adjust(0,0,0,f.prevUtcOffset),l.until.zone=ICAL.Timezone.localTimezone);for(var m,n=l.iterator(g);(m=n.next())&&(f=e(),!(m.year>b)&&m);)f.year=m.year,f.month=m.month,f.day=m.day,f.hour=m.hour,f.minute=m.minute,f.second=m.second,f.isDate=m.isDate,ICAL.Timezone.adjust_change(f,0,0,0,-f.prevUtcOffset),c.push(f)}}else f=e(),f.year=g.year,f.month=g.month,f.day=g.day,f.hour=g.hour,f.minute=g.minute,f.second=g.second,ICAL.Timezone.adjust_change(f,0,0,0,-f.prevUtcOffset),c.push(f);return c},toString:function(){return this.tznames?this.tznames:this.tzid}},ICAL.Timezone._compare_change_fn=function(a,b){return a.year<b.year?-1:a.year>b.year?1:a.month<b.month?-1:a.month>b.month?1:a.day<b.day?-1:a.day>b.day?1:a.hour<b.hour?-1:a.hour>b.hour?1:a.minute<b.minute?-1:a.minute>b.minute?1:a.second<b.second?-1:a.second>b.second?1:0},ICAL.Timezone.convert_time=function(a,b,c){if(a.isDate||b.tzid==c.tzid||b==ICAL.Timezone.localTimezone||c==ICAL.Timezone.localTimezone)return a.zone=c,a;var d=b.utcOffset(a);return a.adjust(0,0,0,-d),d=c.utcOffset(a),a.adjust(0,0,0,d),null},ICAL.Timezone.fromData=function(a){var b=new ICAL.Timezone;return b.fromData(a)},ICAL.Timezone.utcTimezone=ICAL.Timezone.fromData({tzid:"UTC"}),ICAL.Timezone.localTimezone=ICAL.Timezone.fromData({tzid:"floating"}),ICAL.Timezone.adjust_change=function(a,b,c,d,e){return ICAL.Time.prototype.adjust.call(a,b,c,d,e,a)},ICAL.Timezone._minimumExpansionYear=-1,ICAL.Timezone.MAX_YEAR=2035,ICAL.Timezone.EXTRA_COVERAGE=5}(),ICAL.TimezoneService=function(){var a,b={reset:function(){a=Object.create(null);var b=ICAL.Timezone.utcTimezone;a.Z=b,a.UTC=b,a.GMT=b},has:function(b){return!!a[b]},get:function(b){return a[b]},register:function(b,c){if(b instanceof ICAL.Component&&"vtimezone"===b.name&&(c=new ICAL.Timezone(b),b=c.tzid),!(c instanceof ICAL.Timezone))throw new TypeError("timezone must be ICAL.Timezone or ICAL.Component");a[b]=c},remove:function(b){return delete a[b]}};return b.reset(),b}(),function(){ICAL.Time=function(a,b){this.wrappedJSObject=this;var c=this._time=Object.create(null);c.year=0,c.month=1,c.day=1,c.hour=0,c.minute=0,c.second=0,c.isDate=!1,this.fromData(a,b)},ICAL.Time._dowCache={},ICAL.Time._wnCache={},ICAL.Time.prototype={icalclass:"icaltime",_cachedUnixTime:null,get icaltype(){return this.isDate?"date":"date-time"},zone:null,_pendingNormalization:!1,clone:function(){return new ICAL.Time(this._time,this.zone)},reset:function(){this.fromData(ICAL.Time.epochTime),this.zone=ICAL.Timezone.utcTimezone},resetTo:function(a,b,c,d,e,f,g){this.fromData({year:a,month:b,day:c,hour:d,minute:e,second:f,zone:g})},fromJSDate:function(a,b){return a?b?(this.zone=ICAL.Timezone.utcTimezone,this.year=a.getUTCFullYear(),this.month=a.getUTCMonth()+1,this.day=a.getUTCDate(),this.hour=a.getUTCHours(),this.minute=a.getUTCMinutes(),this.second=a.getUTCSeconds()):(this.zone=ICAL.Timezone.localTimezone,this.year=a.getFullYear(),this.month=a.getMonth()+1,this.day=a.getDate(),this.hour=a.getHours(),this.minute=a.getMinutes(),this.second=a.getSeconds()):this.reset(),this._cachedUnixTime=null,this},fromData:function(a,b){if(a)for(var c in a)if(Object.prototype.hasOwnProperty.call(a,c)){if("icaltype"===c)continue;this[c]=a[c]}if(b&&(this.zone=b),!a||"isDate"in a?a&&"isDate"in a&&(this.isDate=a.isDate):this.isDate=!("hour"in a),a&&"timezone"in a){var d=ICAL.TimezoneService.get(a.timezone);this.zone=d||ICAL.Timezone.localTimezone}return a&&"zone"in a&&(this.zone=a.zone),this.zone||(this.zone=ICAL.Timezone.localTimezone),this._cachedUnixTime=null,this},dayOfWeek:function(){var a=(this.year<<9)+(this.month<<5)+this.day;if(a in ICAL.Time._dowCache)return ICAL.Time._dowCache[a];var b=this.day,c=this.month+(this.month<3?12:0),d=this.year-(this.month<3?1:0),e=b+d+ICAL.helpers.trunc(26*(c+1)/10)+ICAL.helpers.trunc(d/4);return e+=6*ICAL.helpers.trunc(d/100)+ICAL.helpers.trunc(d/400),e=(e+6)%7+1,ICAL.Time._dowCache[a]=e,e},dayOfYear:function(){var a=ICAL.Time.isLeapYear(this.year)?1:0,b=ICAL.Time.daysInYearPassedMonth;return b[a][this.month-1]+this.day},startOfWeek:function(a){var b=a||ICAL.Time.SUNDAY,c=this.clone();return c.day-=(this.dayOfWeek()+7-b)%7,c.isDate=!0,c.hour=0,c.minute=0,c.second=0,c},endOfWeek:function(a){var b=a||ICAL.Time.SUNDAY,c=this.clone();return c.day+=(7-this.dayOfWeek()+b-ICAL.Time.SUNDAY)%7,c.isDate=!0,c.hour=0,c.minute=0,c.second=0,c},startOfMonth:function(){var a=this.clone();return a.day=1,a.isDate=!0,a.hour=0,a.minute=0,a.second=0,a},endOfMonth:function(){var a=this.clone();return a.day=ICAL.Time.daysInMonth(a.month,a.year),a.isDate=!0,a.hour=0,a.minute=0,a.second=0,a},startOfYear:function(){var a=this.clone();return a.day=1,a.month=1,a.isDate=!0,a.hour=0,a.minute=0,a.second=0,a},endOfYear:function(){var a=this.clone();return a.day=31,a.month=12,a.isDate=!0,a.hour=0,a.minute=0,a.second=0,a},startDoyWeek:function(a){var b=a||ICAL.Time.SUNDAY,c=this.dayOfWeek()-b;return c<0&&(c+=7),this.dayOfYear()-c},getDominicalLetter:function(){return ICAL.Time.getDominicalLetter(this.year)},nthWeekDay:function(a,b){var c,d=ICAL.Time.daysInMonth(this.month,this.year),e=b,f=0,g=this.clone();if(e>=0){g.day=1,0!=e&&e--,f=g.day;var h=g.dayOfWeek(),i=a-h;i<0&&(i+=7),f+=i,f-=a,c=a}else{g.day=d;var j=g.dayOfWeek();e++,c=j-a,c<0&&(c+=7),c=d-c}return c+=7*e,f+c},isNthWeekDay:function(a,b){var c=this.dayOfWeek();if(0===b&&c===a)return!0;var d=this.nthWeekDay(a,b);return d===this.day},weekNumber:function(a){var b=(this.year<<12)+(this.month<<8)+(this.day<<3)+a;if(b in ICAL.Time._wnCache)return ICAL.Time._wnCache[b];var c,d=this.clone();d.isDate=!0;var e=this.year;12==d.month&&d.day>25?(c=ICAL.Time.weekOneStarts(e+1,a),d.compare(c)<0?c=ICAL.Time.weekOneStarts(e,a):e++):(c=ICAL.Time.weekOneStarts(e,a),d.compare(c)<0&&(c=ICAL.Time.weekOneStarts(--e,a)));var f=d.subtractDate(c).toSeconds()/86400,g=ICAL.helpers.trunc(f/7)+1;return ICAL.Time._wnCache[b]=g,g},addDuration:function(a){var b=a.isNegative?-1:1,c=this.second,d=this.minute,e=this.hour,f=this.day;c+=b*a.seconds,d+=b*a.minutes,e+=b*a.hours,f+=b*a.days,f+=7*b*a.weeks,this.second=c,this.minute=d,this.hour=e,this.day=f,this._cachedUnixTime=null},subtractDate:function(a){var b=this.toUnixTime()+this.utcOffset(),c=a.toUnixTime()+a.utcOffset();return ICAL.Duration.fromSeconds(b-c)},subtractDateTz:function(a){var b=this.toUnixTime(),c=a.toUnixTime();return ICAL.Duration.fromSeconds(b-c)},compare:function(a){var b=this.toUnixTime(),c=a.toUnixTime();return b>c?1:c>b?-1:0},compareDateOnlyTz:function(a,b){function c(a){return ICAL.Time._cmp_attr(d,e,a)}var d=this.convertToZone(b),e=a.convertToZone(b),f=0;return 0!=(f=c("year"))?f:0!=(f=c("month"))?f:0!=(f=c("day"))?f:f},convertToZone:function(a){var b=this.clone(),c=this.zone.tzid==a.tzid;return this.isDate||c||ICAL.Timezone.convert_time(b,this.zone,a),b.zone=a,b},utcOffset:function(){return this.zone==ICAL.Timezone.localTimezone||this.zone==ICAL.Timezone.utcTimezone?0:this.zone.utcOffset(this)},toICALString:function(){var a=this.toString();return a.length>10?ICAL.design.icalendar.value["date-time"].toICAL(a):ICAL.design.icalendar.value.date.toICAL(a)},toString:function(){var a=this.year+"-"+ICAL.helpers.pad2(this.month)+"-"+ICAL.helpers.pad2(this.day);return this.isDate||(a+="T"+ICAL.helpers.pad2(this.hour)+":"+ICAL.helpers.pad2(this.minute)+":"+ICAL.helpers.pad2(this.second),this.zone===ICAL.Timezone.utcTimezone&&(a+="Z")),a},toJSDate:function(){return this.zone==ICAL.Timezone.localTimezone?this.isDate?new Date(this.year,this.month-1,this.day):new Date(this.year,this.month-1,this.day,this.hour,this.minute,this.second,0):new Date(1e3*this.toUnixTime())},_normalize:function(){this._time.isDate;return this._time.isDate&&(this._time.hour=0,this._time.minute=0,this._time.second=0),this.adjust(0,0,0,0),this},adjust:function(a,b,c,d,e){var f,g,h,i,j,k,l,m=0,n=0,o=e||this._time;if(o.isDate||(h=o.second+d,o.second=h%60,f=ICAL.helpers.trunc(h/60),o.second<0&&(o.second+=60,f--),i=o.minute+c+f,o.minute=i%60,g=ICAL.helpers.trunc(i/60),o.minute<0&&(o.minute+=60,g--),j=o.hour+b+g,o.hour=j%24,m=ICAL.helpers.trunc(j/24),o.hour<0&&(o.hour+=24,m--)),o.month>12?n=ICAL.helpers.trunc((o.month-1)/12):o.month<1&&(n=ICAL.helpers.trunc(o.month/12)-1),o.year+=n,o.month-=12*n,k=o.day+a+m,k>0)for(;l=ICAL.Time.daysInMonth(o.month,o.year),!(k<=l);)o.month++,o.month>12&&(o.year++,o.month=1),k-=l;else for(;k<=0;)1==o.month?(o.year--,o.month=12):o.month--,k+=ICAL.Time.daysInMonth(o.month,o.year);return o.day=k,this._cachedUnixTime=null,this},fromUnixTime:function(a){this.zone=ICAL.Timezone.utcTimezone;var b=ICAL.Time.epochTime.clone();b.adjust(0,0,0,a),this.year=b.year,this.month=b.month,this.day=b.day,this.hour=b.hour,this.minute=b.minute,this.second=Math.floor(b.second),this._cachedUnixTime=null},toUnixTime:function(){if(null!==this._cachedUnixTime)return this._cachedUnixTime;var a=this.utcOffset(),b=Date.UTC(this.year,this.month-1,this.day,this.hour,this.minute,this.second-a);return this._cachedUnixTime=b/1e3,this._cachedUnixTime},toJSON:function(){for(var a,b=["year","month","day","hour","minute","second","isDate"],c=Object.create(null),d=0,e=b.length;d<e;d++)a=b[d],c[a]=this[a];return this.zone&&(c.timezone=this.zone.tzid),c}},function(){function a(a){Object.defineProperty(ICAL.Time.prototype,a,{get:function(){return this._pendingNormalization&&(this._normalize(),this._pendingNormalization=!1),this._time[a]},set:function(b){return this._cachedUnixTime=null,this._pendingNormalization=!0,this._time[a]=b,b}})}"defineProperty"in Object&&(a("year"),a("month"),a("day"),a("hour"),a("minute"),a("second"),a("isDate"))}(),ICAL.Time.daysInMonth=function(a,b){var c=[0,31,28,31,30,31,30,31,31,30,31,30,31],d=30;return a<1||a>12?d:(d=c[a],2==a&&(d+=ICAL.Time.isLeapYear(b)),d)},ICAL.Time.isLeapYear=function(a){return a<=1752?a%4==0:a%4==0&&a%100!=0||a%400==0},ICAL.Time.fromDayOfYear=function(a,b){var c=b,d=a,e=new ICAL.Time;e.auto_normalize=!1;var f=ICAL.Time.isLeapYear(c)?1:0;if(d<1)return c--,f=ICAL.Time.isLeapYear(c)?1:0,d+=ICAL.Time.daysInYearPassedMonth[f][12],ICAL.Time.fromDayOfYear(d,c);if(d>ICAL.Time.daysInYearPassedMonth[f][12])return f=ICAL.Time.isLeapYear(c)?1:0,d-=ICAL.Time.daysInYearPassedMonth[f][12],c++,ICAL.Time.fromDayOfYear(d,c);e.year=c,e.isDate=!0;for(var g=11;g>=0;g--)if(d>ICAL.Time.daysInYearPassedMonth[f][g]){e.month=g+1,e.day=d-ICAL.Time.daysInYearPassedMonth[f][g];break}return e.auto_normalize=!0,e},ICAL.Time.fromStringv2=function(a){return new ICAL.Time({year:parseInt(a.substr(0,4),10),month:parseInt(a.substr(5,2),10),day:parseInt(a.substr(8,2),10),isDate:!0})},ICAL.Time.fromDateString=function(a){return new ICAL.Time({year:ICAL.helpers.strictParseInt(a.substr(0,4)),month:ICAL.helpers.strictParseInt(a.substr(5,2)),day:ICAL.helpers.strictParseInt(a.substr(8,2)),isDate:!0})},ICAL.Time.fromDateTimeString=function(a,b){if(a.length<19)throw new Error('invalid date-time value: "'+a+'"');var c;a[19]&&"Z"===a[19]?c="Z":b&&(c=b.getParameter("tzid"));var d=new ICAL.Time({year:ICAL.helpers.strictParseInt(a.substr(0,4)),month:ICAL.helpers.strictParseInt(a.substr(5,2)),day:ICAL.helpers.strictParseInt(a.substr(8,2)),hour:ICAL.helpers.strictParseInt(a.substr(11,2)),minute:ICAL.helpers.strictParseInt(a.substr(14,2)),second:ICAL.helpers.strictParseInt(a.substr(17,2)),timezone:c});return d},ICAL.Time.fromString=function(a){return a.length>10?ICAL.Time.fromDateTimeString(a):ICAL.Time.fromDateString(a)},ICAL.Time.fromJSDate=function(a,b){var c=new ICAL.Time;return c.fromJSDate(a,b)},ICAL.Time.fromData=function(a,b){var c=new ICAL.Time;return c.fromData(a,b)},ICAL.Time.now=function(){return ICAL.Time.fromJSDate(new Date,!1)},ICAL.Time.weekOneStarts=function(a,b){var c=ICAL.Time.fromData({year:a,month:1,day:1,isDate:!0}),d=c.dayOfWeek(),e=b||ICAL.Time.DEFAULT_WEEK_START;return d>ICAL.Time.THURSDAY&&(c.day+=7),e>ICAL.Time.THURSDAY&&(c.day-=7),c.day-=d-e,c},ICAL.Time.getDominicalLetter=function(a){var b="GFEDCBA",c=(a+(a/4|0)+(a/400|0)-(a/100|0)-1)%7,d=ICAL.Time.isLeapYear(a);return d?b[(c+6)%7]+b[c]:b[c]},ICAL.Time.epochTime=ICAL.Time.fromData({year:1970,month:1,day:1,hour:0,minute:0,second:0,isDate:!1,timezone:"Z"}),ICAL.Time._cmp_attr=function(a,b,c){return a[c]>b[c]?1:a[c]<b[c]?-1:0},ICAL.Time.daysInYearPassedMonth=[[0,31,59,90,120,151,181,212,243,273,304,334,365],[0,31,60,91,121,152,182,213,244,274,305,335,366]],ICAL.Time.SUNDAY=1,ICAL.Time.MONDAY=2,ICAL.Time.TUESDAY=3,ICAL.Time.WEDNESDAY=4,ICAL.Time.THURSDAY=5,ICAL.Time.FRIDAY=6,ICAL.Time.SATURDAY=7,ICAL.Time.DEFAULT_WEEK_START=ICAL.Time.MONDAY}(),function(){ICAL.VCardTime=function(a,b,c){this.wrappedJSObject=this;var d=this._time=Object.create(null);d.year=null,d.month=null,d.day=null,d.hour=null,d.minute=null,d.second=null,this.icaltype=c||"date-and-or-time",this.fromData(a,b)},ICAL.helpers.inherits(ICAL.Time,ICAL.VCardTime,{icalclass:"vcardtime",icaltype:"date-and-or-time",zone:null,clone:function(){return new ICAL.VCardTime(this._time,this.zone,this.icaltype)},_normalize:function(){return this},utcOffset:function(){return this.zone instanceof ICAL.UtcOffset?this.zone.toSeconds():ICAL.Time.prototype.utcOffset.apply(this,arguments)},toICALString:function(){return ICAL.design.vcard.value[this.icaltype].toICAL(this.toString())},toString:function(){var a,b=ICAL.helpers.pad2,c=this.year,d=this.month,e=this.day,f=this.hour,g=this.minute,h=this.second,i=null!==c,j=null!==d,k=null!==e,l=null!==f,m=null!==g,n=null!==h,o=(i?b(c)+(j||k?"-":""):j||k?"--":"")+(j?b(d):"")+(k?"-"+b(e):""),p=(l?b(f):"-")+(l&&m?":":"")+(m?b(g):"")+(l||m?"":"-")+(m&&n?":":"")+(n?b(h):"");if(this.zone===ICAL.Timezone.utcTimezone)a="Z";else if(this.zone instanceof ICAL.UtcOffset)a=this.zone.toString();else if(this.zone===ICAL.Timezone.localTimezone)a="";else if(this.zone instanceof ICAL.Timezone){var q=ICAL.UtcOffset.fromSeconds(this.zone.utcOffset(this));a=q.toString()}else a="";switch(this.icaltype){case"time":return p+a;case"date-and-or-time":case"date-time":return o+("--"==p?"":"T"+p+a);case"date":return o}return null}}),ICAL.VCardTime.fromDateAndOrTimeString=function(a,b){function c(a,b,c){return a?ICAL.helpers.strictParseInt(a.substr(b,c)):null}var d=a.split("T"),e=d[0],f=d[1],g=f?ICAL.design.vcard.value.time._splitZone(f):[],h=g[0],i=g[1],j=(ICAL.helpers.strictParseInt,e?e.length:0),k=i?i.length:0,l=e&&"-"==e[0]&&"-"==e[1],m=i&&"-"==i[0],n={year:l?null:c(e,0,4),month:!l||4!=j&&7!=j?7==j?c(e,5,2):10==j?c(e,5,2):null:c(e,2,2),day:5==j?c(e,3,2):7==j&&l?c(e,5,2):10==j?c(e,8,2):null,hour:m?null:c(i,0,2),minute:m&&3==k?c(i,1,2):k>4?m?c(i,1,2):c(i,3,2):null,second:4==k?c(i,2,2):6==k?c(i,4,2):8==k?c(i,6,2):null};return h="Z"==h?ICAL.Timezone.utcTimezone:h&&":"==h[3]?ICAL.UtcOffset.fromString(h):null,new ICAL.VCardTime(n,h,b)}}(),function(){function a(a,b,c,d){var e=d;if("+"===d[0]&&(e=d.substr(1)),e=ICAL.helpers.strictParseInt(e),void 0!==b&&d<b)throw new Error(a+': invalid value "'+d+'" must be > '+b);if(void 0!==c&&d>c)throw new Error(a+': invalid value "'+d+'" must be < '+b);return e}var b={SU:ICAL.Time.SUNDAY,MO:ICAL.Time.MONDAY,TU:ICAL.Time.TUESDAY,WE:ICAL.Time.WEDNESDAY,TH:ICAL.Time.THURSDAY,FR:ICAL.Time.FRIDAY,SA:ICAL.Time.SATURDAY},c={};for(var d in b)b.hasOwnProperty(d)&&(c[b[d]]=d);ICAL.Recur=function(a){this.wrappedJSObject=this,this.parts={},a&&"object"==typeof a&&this.fromData(a)},ICAL.Recur.prototype={parts:null,interval:1,wkst:ICAL.Time.MONDAY,until:null,count:null,freq:null,icalclass:"icalrecur",icaltype:"recur",iterator:function(a){return new ICAL.RecurIterator({rule:this,dtstart:a})},clone:function(){return new ICAL.Recur(this.toJSON())},isFinite:function(){return!(!this.count&&!this.until)},isByCount:function(){return!(!this.count||this.until)},addComponent:function(a,b){var c=a.toUpperCase();c in this.parts?this.parts[c].push(b):this.parts[c]=[b]},setComponent:function(a,b){this.parts[a.toUpperCase()]=b.slice()},getComponent:function(a){var b=a.toUpperCase();return b in this.parts?this.parts[b].slice():[]},getNextOccurrence:function(a,b){var c,d=this.iterator(a);do c=d.next();while(c&&c.compare(b)<=0);return c&&b.zone&&(c.zone=b.zone),c},fromData:function(a){for(var b in a){var c=b.toUpperCase();c in i?Array.isArray(a[b])?this.parts[c]=a[b]:this.parts[c]=[a[b]]:this[b]=a[b]}this.wkst&&"number"!=typeof this.wkst&&(this.wkst=ICAL.Recur.icalDayToNumericDay(this.wkst)),!this.until||this.until instanceof ICAL.Time||(this.until=ICAL.Time.fromString(this.until))},toJSON:function(){var a=Object.create(null);a.freq=this.freq,this.count&&(a.count=this.count),this.interval>1&&(a.interval=this.interval);for(var b in this.parts)if(this.parts.hasOwnProperty(b)){var c=this.parts[b];Array.isArray(c)&&1==c.length?a[b.toLowerCase()]=c[0]:a[b.toLowerCase()]=ICAL.helpers.clone(this.parts[b])}return this.until&&(a.until=this.until.toString()),"wkst"in this&&this.wkst!==ICAL.Time.DEFAULT_WEEK_START&&(a.wkst=ICAL.Recur.numericDayToIcalDay(this.wkst)),a},toString:function(){var a="FREQ="+this.freq;this.count&&(a+=";COUNT="+this.count),this.interval>1&&(a+=";INTERVAL="+this.interval);for(var b in this.parts)this.parts.hasOwnProperty(b)&&(a+=";"+b+"="+this.parts[b]);return this.until&&(a+=";UNTIL="+this.until.toString()),"wkst"in this&&this.wkst!==ICAL.Time.DEFAULT_WEEK_START&&(a+=";WKST="+ICAL.Recur.numericDayToIcalDay(this.wkst)),a}},ICAL.Recur.icalDayToNumericDay=function(a){return b[a]},ICAL.Recur.numericDayToIcalDay=function(a){return c[a]};var e=/^(SU|MO|TU|WE|TH|FR|SA)$/,f=/^([+-])?(5[0-3]|[1-4][0-9]|[1-9])?(SU|MO|TU|WE|TH|FR|SA)$/,g=["SECONDLY","MINUTELY","HOURLY","DAILY","WEEKLY","MONTHLY","YEARLY"],h={FREQ:function(a,b,c){if(g.indexOf(a)===-1)throw new Error('invalid frequency "'+a+'" expected: "'+g.join(", ")+'"');b.freq=a},COUNT:function(a,b,c){b.count=ICAL.helpers.strictParseInt(a)},INTERVAL:function(a,b,c){b.interval=ICAL.helpers.strictParseInt(a),b.interval<1&&(b.interval=1)},UNTIL:function(a,b,c){c?a.length>10?b.until=ICAL.design.icalendar.value["date-time"].fromICAL(a):b.until=ICAL.design.icalendar.value.date.fromICAL(a):b.until=ICAL.Time.fromString(a)},WKST:function(a,b,c){if(!e.test(a))throw new Error('invalid WKST value "'+a+'"');b.wkst=ICAL.Recur.icalDayToNumericDay(a)}},i={BYSECOND:a.bind(this,"BYSECOND",0,60),BYMINUTE:a.bind(this,"BYMINUTE",0,59),BYHOUR:a.bind(this,"BYHOUR",0,23),BYDAY:function(a){if(f.test(a))return a;throw new Error('invalid BYDAY value "'+a+'"')},BYMONTHDAY:a.bind(this,"BYMONTHDAY",-31,31),BYYEARDAY:a.bind(this,"BYYEARDAY",-366,366),BYWEEKNO:a.bind(this,"BYWEEKNO",-53,53),BYMONTH:a.bind(this,"BYMONTH",0,12),BYSETPOS:a.bind(this,"BYSETPOS",-366,366)};ICAL.Recur.fromString=function(a){var b=ICAL.Recur._stringToData(a,!1);return new ICAL.Recur(b)},ICAL.Recur.fromData=function(a){return new ICAL.Recur(a)},ICAL.Recur._stringToData=function(a,b){for(var c=Object.create(null),d=a.split(";"),e=d.length,f=0;f<e;f++){var g=d[f].split("="),j=g[0].toUpperCase(),k=g[0].toLowerCase(),l=b?k:j,m=g[1];if(j in i){for(var n=m.split(","),o=0,p=n.length;o<p;o++)n[o]=i[j](n[o]);c[l]=1==n.length?n[0]:n}else j in h?h[j](m,c,b):c[k]=m}return c}}(),ICAL.RecurIterator=function(){function a(a){this.fromData(a)}return a.prototype={completed:!1,rule:null,dtstart:null,last:null,occurrence_number:0,by_indices:null,initialized:!1,by_data:null,days:null,days_index:0,fromData:function(a){if(this.rule=ICAL.helpers.formatClassType(a.rule,ICAL.Recur),!this.rule)throw new Error("iterator requires a (ICAL.Recur) rule");if(this.dtstart=ICAL.helpers.formatClassType(a.dtstart,ICAL.Time),!this.dtstart)throw new Error("iterator requires a (ICAL.Time) dtstart");a.by_data?this.by_data=a.by_data:this.by_data=ICAL.helpers.clone(this.rule.parts,!0),a.occurrence_number&&(this.occurrence_number=a.occurrence_number),this.days=a.days||[],a.last&&(this.last=ICAL.helpers.formatClassType(a.last,ICAL.Time)),this.by_indices=a.by_indices,this.by_indices||(this.by_indices={BYSECOND:0,BYMINUTE:0,BYHOUR:0,BYDAY:0,BYMONTH:0,BYWEEKNO:0,BYMONTHDAY:0}),this.initialized=a.initialized||!1,this.initialized||this.init()},init:function(){this.initialized=!0,this.last=this.dtstart.clone();var a=this.by_data;if("BYDAY"in a&&this.sort_byday_rules(a.BYDAY,this.rule.wkst),"BYYEARDAY"in a&&("BYMONTH"in a||"BYWEEKNO"in a||"BYMONTHDAY"in a||"BYDAY"in a))throw new Error("Invalid BYYEARDAY rule");if("BYWEEKNO"in a&&"BYMONTHDAY"in a)throw new Error("BYWEEKNO does not fit to BYMONTHDAY");if("MONTHLY"==this.rule.freq&&("BYYEARDAY"in a||"BYWEEKNO"in a))throw new Error("For MONTHLY recurrences neither BYYEARDAY nor BYWEEKNO may appear");if("WEEKLY"==this.rule.freq&&("BYYEARDAY"in a||"BYMONTHDAY"in a))throw new Error("For WEEKLY recurrences neither BYMONTHDAY nor BYYEARDAY may appear");if("YEARLY"!=this.rule.freq&&"BYYEARDAY"in a)throw new Error("BYYEARDAY may only appear in YEARLY rules");if(this.last.second=this.setup_defaults("BYSECOND","SECONDLY",this.dtstart.second),this.last.minute=this.setup_defaults("BYMINUTE","MINUTELY",this.dtstart.minute),this.last.hour=this.setup_defaults("BYHOUR","HOURLY",this.dtstart.hour),this.last.day=this.setup_defaults("BYMONTHDAY","DAILY",this.dtstart.day),this.last.month=this.setup_defaults("BYMONTH","MONTHLY",this.dtstart.month),"WEEKLY"==this.rule.freq)if("BYDAY"in a){var b=this.ruleDayOfWeek(a.BYDAY[0]),c=b[0],d=b[1],e=d-this.last.dayOfWeek();(this.last.dayOfWeek()<d&&e>=0||e<0)&&(this.last.day+=e)}else{var f=ICAL.Recur.numericDayToIcalDay(this.dtstart.dayOfWeek());a.BYDAY=[f]}if("YEARLY"==this.rule.freq){for(;this.expand_year_days(this.last.year),!(this.days.length>0);)this.increment_year(this.rule.interval);this._nextByYearDay()}if("MONTHLY"==this.rule.freq&&this.has_by_data("BYDAY")){var g=null,h=this.last.clone(),i=ICAL.Time.daysInMonth(this.last.month,this.last.year);for(var j in this.by_data.BYDAY)if(this.by_data.BYDAY.hasOwnProperty(j)){this.last=h.clone();var b=this.ruleDayOfWeek(this.by_data.BYDAY[j]),c=b[0],d=b[1],k=this.last.nthWeekDay(d,c);if(c>=6||c<=-6)throw new Error("Malformed values in BYDAY part");if(k>i||k<=0){if(g&&g.month==h.month)continue;for(;k>i||k<=0;)this.increment_month(),i=ICAL.Time.daysInMonth(this.last.month,this.last.year),k=this.last.nthWeekDay(d,c)}this.last.day=k,(!g||this.last.compare(g)<0)&&(g=this.last.clone())}if(this.last=g.clone(),this.has_by_data("BYMONTHDAY")&&this._byDayAndMonthDay(!0),this.last.day>i||0==this.last.day)throw new Error("Malformed values in BYDAY part")}else if(this.has_by_data("BYMONTHDAY")&&this.last.day<0){var i=ICAL.Time.daysInMonth(this.last.month,this.last.year);this.last.day=i+this.last.day+1}},next:function(){var a=this.last?this.last.clone():null;if(this.rule.count&&this.occurrence_number>=this.rule.count||this.rule.until&&this.last.compare(this.rule.until)>0)return this.completed=!0,null;if(0==this.occurrence_number&&this.last.compare(this.dtstart)>=0)return this.occurrence_number++,this.last;var b;do switch(b=1,this.rule.freq){case"SECONDLY":this.next_second();break;case"MINUTELY":this.next_minute();break;case"HOURLY":this.next_hour();break;case"DAILY":this.next_day();break;case"WEEKLY":this.next_week();break;case"MONTHLY":b=this.next_month();break;case"YEARLY":this.next_year();break;default:return null}while(!this.check_contracting_rules()||this.last.compare(this.dtstart)<0||!b);if(0==this.last.compare(a))throw new Error("Same occurrence found twice, protecting you from death by recursion");return this.rule.until&&this.last.compare(this.rule.until)>0?(this.completed=!0,null):(this.occurrence_number++,this.last)},next_second:function(){return this.next_generic("BYSECOND","SECONDLY","second","minute")},increment_second:function(a){return this.increment_generic(a,"second",60,"minute")},next_minute:function(){return this.next_generic("BYMINUTE","MINUTELY","minute","hour","next_second")},increment_minute:function(a){return this.increment_generic(a,"minute",60,"hour")},next_hour:function(){return this.next_generic("BYHOUR","HOURLY","hour","monthday","next_minute")},increment_hour:function(a){this.increment_generic(a,"hour",24,"monthday")},next_day:function(){var a=("BYDAY"in this.by_data,"DAILY"==this.rule.freq);return 0==this.next_hour()?0:(a?this.increment_monthday(this.rule.interval):this.increment_monthday(1),0)},next_week:function(){var a=0;if(0==this.next_weekday_by_week())return a;if(this.has_by_data("BYWEEKNO")){++this.by_indices.BYWEEKNO;this.by_indices.BYWEEKNO==this.by_data.BYWEEKNO.length&&(this.by_indices.BYWEEKNO=0,a=1),this.last.month=1,this.last.day=1;var b=this.by_data.BYWEEKNO[this.by_indices.BYWEEKNO];this.last.day+=7*b,a&&this.increment_year(1)}else this.increment_monthday(7*this.rule.interval);return a},normalizeByMonthDayRules:function(a,b,c){for(var d,e=ICAL.Time.daysInMonth(b,a),f=[],g=0,h=c.length;g<h;g++)if(d=c[g],!(Math.abs(d)>e)){if(d<0)d=e+(d+1);else if(0===d)continue;f.indexOf(d)===-1&&f.push(d)}return f.sort(function(a,b){return a-b})},_byDayAndMonthDay:function(a){function b(){for(g=ICAL.Time.daysInMonth(l.last.month,l.last.year),d=l.normalizeByMonthDayRules(l.last.year,l.last.month,l.by_data.BYMONTHDAY),f=d.length;d[i]<=m&&(!a||d[i]!=m)&&i<f-1;)i++}function c(){m=0,l.increment_month(),i=0,b()}var d,e,f,g,h=this.by_data.BYDAY,i=0,j=h.length,k=0,l=this,m=this.last.day;b(),a&&(m-=1);for(var n=48;!k&&n;)if(n--,e=m+1,e>g)c();else{var o=d[i++];if(o>=e){m=o;for(var p=0;p<j;p++){var q=this.ruleDayOfWeek(h[p]),r=q[0],s=q[1];if(this.last.day=m,this.last.isNthWeekDay(s,r)){k=1;break}}k||i!==f||c()}else c()}if(n<=0)throw new Error("Malformed values in BYDAY combined with BYMONTHDAY parts");return k},next_month:function(){var a=("MONTHLY"==this.rule.freq,1);if(0==this.next_hour())return a;if(this.has_by_data("BYDAY")&&this.has_by_data("BYMONTHDAY"))a=this._byDayAndMonthDay();else if(this.has_by_data("BYDAY")){var b=ICAL.Time.daysInMonth(this.last.month,this.last.year),c=0,d=0;if(this.has_by_data("BYSETPOS")){for(var e=this.last.day,f=1;f<=b;f++)this.last.day=f,this.is_day_in_byday(this.last)&&(d++,f<=e&&c++);this.last.day=e}a=0;for(var f=this.last.day+1;f<=b;f++)if(this.last.day=f,this.is_day_in_byday(this.last)&&(!this.has_by_data("BYSETPOS")||this.check_set_position(++c)||this.check_set_position(c-d-1))){a=1;break}f>b&&(this.last.day=1,this.increment_month(),this.is_day_in_byday(this.last)?this.has_by_data("BYSETPOS")&&!this.check_set_position(1)||(a=1):a=0)}else if(this.has_by_data("BYMONTHDAY")){this.by_indices.BYMONTHDAY++,this.by_indices.BYMONTHDAY>=this.by_data.BYMONTHDAY.length&&(this.by_indices.BYMONTHDAY=0,this.increment_month());var b=ICAL.Time.daysInMonth(this.last.month,this.last.year),f=this.by_data.BYMONTHDAY[this.by_indices.BYMONTHDAY];f<0&&(f=b+f+1),f>b?(this.last.day=1,a=this.is_day_in_byday(this.last)):this.last.day=f}else{this.increment_month();var b=ICAL.Time.daysInMonth(this.last.month,this.last.year);this.by_data.BYMONTHDAY[0]>b?a=0:this.last.day=this.by_data.BYMONTHDAY[0]}return a},next_weekday_by_week:function(){var a=0;if(0==this.next_hour())return a;if(!this.has_by_data("BYDAY"))return 1;for(;;){var b=new ICAL.Time;this.by_indices.BYDAY++,this.by_indices.BYDAY==Object.keys(this.by_data.BYDAY).length&&(this.by_indices.BYDAY=0,a=1);var c=this.by_data.BYDAY[this.by_indices.BYDAY],d=this.ruleDayOfWeek(c),e=d[1];e-=this.rule.wkst,e<0&&(e+=7),b.year=this.last.year,b.month=this.last.month,b.day=this.last.day;var f=b.startDoyWeek(this.rule.wkst);if(!(e+f<1)||a){var g=ICAL.Time.fromDayOfYear(f+e,this.last.year);return this.last.year=g.year,this.last.month=g.month,this.last.day=g.day,a}}},next_year:function(){if(0==this.next_hour())return 0;if(++this.days_index==this.days.length){this.days_index=0;do this.increment_year(this.rule.interval),this.expand_year_days(this.last.year);while(0==this.days.length)}return this._nextByYearDay(),1},_nextByYearDay:function(){var a=this.days[this.days_index],b=this.last.year;a<1&&(a+=1,b+=1);var c=ICAL.Time.fromDayOfYear(a,b);
this.last.day=c.day,this.last.month=c.month},ruleDayOfWeek:function(a){var b=a.match(/([+-]?[0-9])?(MO|TU|WE|TH|FR|SA|SU)/);if(b){var c=parseInt(b[1]||0,10);return a=ICAL.Recur.icalDayToNumericDay(b[2]),[c,a]}return[0,0]},next_generic:function(a,b,c,d,e){var f=a in this.by_data,g=this.rule.freq==b,h=0;if(e&&0==this[e]())return h;if(f){this.by_indices[a]++;var i=(this.by_indices[a],this.by_data[a]);this.by_indices[a]==i.length&&(this.by_indices[a]=0,h=1),this.last[c]=i[this.by_indices[a]]}else g&&this["increment_"+c](this.rule.interval);return f&&h&&g&&this["increment_"+d](1),h},increment_monthday:function(a){for(var b=0;b<a;b++){var c=ICAL.Time.daysInMonth(this.last.month,this.last.year);this.last.day++,this.last.day>c&&(this.last.day-=c,this.increment_month())}},increment_month:function(){if(this.last.day=1,this.has_by_data("BYMONTH"))this.by_indices.BYMONTH++,this.by_indices.BYMONTH==this.by_data.BYMONTH.length&&(this.by_indices.BYMONTH=0,this.increment_year(1)),this.last.month=this.by_data.BYMONTH[this.by_indices.BYMONTH];else{"MONTHLY"==this.rule.freq?this.last.month+=this.rule.interval:this.last.month++,this.last.month--;var a=ICAL.helpers.trunc(this.last.month/12);this.last.month%=12,this.last.month++,0!=a&&this.increment_year(a)}},increment_year:function(a){this.last.year+=a},increment_generic:function(a,b,c,d){this.last[b]+=a;var e=ICAL.helpers.trunc(this.last[b]/c);this.last[b]%=c,0!=e&&this["increment_"+d](e)},has_by_data:function(a){return a in this.rule.parts},expand_year_days:function(a){var b=new ICAL.Time;this.days=[];var c={},d=["BYDAY","BYWEEKNO","BYMONTHDAY","BYMONTH","BYYEARDAY"];for(var e in d)if(d.hasOwnProperty(e)){var f=d[e];f in this.rule.parts&&(c[f]=this.rule.parts[f])}if("BYMONTH"in c&&"BYWEEKNO"in c){var g=1,h={};b.year=a,b.isDate=!0;for(var i=0;i<this.by_data.BYMONTH.length;i++){var j=this.by_data.BYMONTH[i];b.month=j,b.day=1;var k=b.weekNumber(this.rule.wkst);b.day=ICAL.Time.daysInMonth(j,a);var l=b.weekNumber(this.rule.wkst);for(i=k;i<l;i++)h[i]=1}for(var m=0;m<this.by_data.BYWEEKNO.length&&g;m++){var n=this.by_data.BYWEEKNO[m];n<52?g&=h[m]:g=0}g?delete c.BYMONTH:delete c.BYWEEKNO}var o=Object.keys(c).length;if(0==o){var p=this.dtstart.clone();p.year=this.last.year,this.days.push(p.dayOfYear())}else if(1==o&&"BYMONTH"in c){for(var q in this.by_data.BYMONTH)if(this.by_data.BYMONTH.hasOwnProperty(q)){var r=this.dtstart.clone();r.year=a,r.month=this.by_data.BYMONTH[q],r.isDate=!0,this.days.push(r.dayOfYear())}}else if(1==o&&"BYMONTHDAY"in c){for(var s in this.by_data.BYMONTHDAY)if(this.by_data.BYMONTHDAY.hasOwnProperty(s)){var t=this.dtstart.clone(),u=this.by_data.BYMONTHDAY[s];if(u<0){var v=ICAL.Time.daysInMonth(t.month,a);u=u+v+1}t.day=u,t.year=a,t.isDate=!0,this.days.push(t.dayOfYear())}}else if(2==o&&"BYMONTHDAY"in c&&"BYMONTH"in c){for(var q in this.by_data.BYMONTH)if(this.by_data.BYMONTH.hasOwnProperty(q)){var w=this.by_data.BYMONTH[q],v=ICAL.Time.daysInMonth(w,a);for(var s in this.by_data.BYMONTHDAY)if(this.by_data.BYMONTHDAY.hasOwnProperty(s)){var u=this.by_data.BYMONTHDAY[s];u<0&&(u=u+v+1),b.day=u,b.month=w,b.year=a,b.isDate=!0,this.days.push(b.dayOfYear())}}}else if(1==o&&"BYWEEKNO"in c);else if(2==o&&"BYWEEKNO"in c&&"BYMONTHDAY"in c);else if(1==o&&"BYDAY"in c)this.days=this.days.concat(this.expand_by_day(a));else if(2==o&&"BYDAY"in c&&"BYMONTH"in c){for(var q in this.by_data.BYMONTH)if(this.by_data.BYMONTH.hasOwnProperty(q)){var j=this.by_data.BYMONTH[q],v=ICAL.Time.daysInMonth(j,a);b.year=a,b.month=this.by_data.BYMONTH[q],b.day=1,b.isDate=!0;var x=b.dayOfWeek(),y=b.dayOfYear()-1;b.day=v;var z=b.dayOfWeek();if(this.has_by_data("BYSETPOS")){for(var A=[],B=1;B<=v;B++)b.day=B,this.is_day_in_byday(b)&&A.push(B);for(var C=0;C<A.length;C++)(this.check_set_position(C+1)||this.check_set_position(C-A.length))&&this.days.push(y+A[C])}else for(var D in this.by_data.BYDAY)if(this.by_data.BYDAY.hasOwnProperty(D)){var E,F=this.by_data.BYDAY[D],G=this.ruleDayOfWeek(F),H=G[0],I=G[1],J=(I+7-x)%7+1,K=v-(z+7-I)%7;if(0==H)for(var B=J;B<=v;B+=7)this.days.push(y+B);else H>0?(E=J+7*(H-1),E<=v&&this.days.push(y+E)):(E=K+7*(H+1),E>0&&this.days.push(y+E))}}this.days.sort(function(a,b){return a-b})}else if(2==o&&"BYDAY"in c&&"BYMONTHDAY"in c){var L=this.expand_by_day(a);for(var M in L)if(L.hasOwnProperty(M)){var B=L[M],N=ICAL.Time.fromDayOfYear(B,a);this.by_data.BYMONTHDAY.indexOf(N.day)>=0&&this.days.push(B)}}else if(3==o&&"BYDAY"in c&&"BYMONTHDAY"in c&&"BYMONTH"in c){var L=this.expand_by_day(a);for(var M in L)if(L.hasOwnProperty(M)){var B=L[M],N=ICAL.Time.fromDayOfYear(B,a);this.by_data.BYMONTH.indexOf(N.month)>=0&&this.by_data.BYMONTHDAY.indexOf(N.day)>=0&&this.days.push(B)}}else if(2==o&&"BYDAY"in c&&"BYWEEKNO"in c){var L=this.expand_by_day(a);for(var M in L)if(L.hasOwnProperty(M)){var B=L[M],N=ICAL.Time.fromDayOfYear(B,a),n=N.weekNumber(this.rule.wkst);this.by_data.BYWEEKNO.indexOf(n)&&this.days.push(B)}}else 3==o&&"BYDAY"in c&&"BYWEEKNO"in c&&"BYMONTHDAY"in c||(1==o&&"BYYEARDAY"in c?this.days=this.days.concat(this.by_data.BYYEARDAY):this.days=[]);return 0},expand_by_day:function(a){var b=[],c=this.last.clone();c.year=a,c.month=1,c.day=1,c.isDate=!0;var d=c.dayOfWeek();c.month=12,c.day=31,c.isDate=!0;var e=c.dayOfWeek(),f=c.dayOfYear();for(var g in this.by_data.BYDAY)if(this.by_data.BYDAY.hasOwnProperty(g)){var h=this.by_data.BYDAY[g],i=this.ruleDayOfWeek(h),j=i[0],k=i[1];if(0==j)for(var l=(k+7-d)%7+1,m=l;m<=f;m+=7)b.push(m);else if(j>0){var n;n=k>=d?k-d+1:k-d+8,b.push(n+7*(j-1))}else{var o;j=-j,o=k<=e?f-e+k:f-e+k-7,b.push(o-7*(j-1))}}return b},is_day_in_byday:function(a){for(var b in this.by_data.BYDAY)if(this.by_data.BYDAY.hasOwnProperty(b)){var c=this.by_data.BYDAY[b],d=this.ruleDayOfWeek(c),e=d[0],f=d[1],g=a.dayOfWeek();if(0==e&&f==g||a.nthWeekDay(f,e)==a.day)return 1}return 0},check_set_position:function(a){if(this.has_by_data("BYSETPOS")){var b=this.by_data.BYSETPOS.indexOf(a);return b!==-1}return!1},sort_byday_rules:function(a,b){for(var c=0;c<a.length;c++)for(var d=0;d<c;d++){var e=this.ruleDayOfWeek(a[d])[1],f=this.ruleDayOfWeek(a[c])[1];if(e-=b,f-=b,e<0&&(e+=7),f<0&&(f+=7),e>f){var g=a[c];a[c]=a[d],a[d]=g}}},check_contract_restriction:function(b,c){var d=a._indexMap[b],e=a._expandMap[this.rule.freq][d],f=!1;if(b in this.by_data&&e==a.CONTRACT){var g=this.by_data[b];for(var h in g)if(g.hasOwnProperty(h)&&g[h]==c){f=!0;break}}else f=!0;return f},check_contracting_rules:function(){var a=this.last.dayOfWeek(),b=this.last.weekNumber(this.rule.wkst),c=this.last.dayOfYear();return this.check_contract_restriction("BYSECOND",this.last.second)&&this.check_contract_restriction("BYMINUTE",this.last.minute)&&this.check_contract_restriction("BYHOUR",this.last.hour)&&this.check_contract_restriction("BYDAY",ICAL.Recur.numericDayToIcalDay(a))&&this.check_contract_restriction("BYWEEKNO",b)&&this.check_contract_restriction("BYMONTHDAY",this.last.day)&&this.check_contract_restriction("BYMONTH",this.last.month)&&this.check_contract_restriction("BYYEARDAY",c)},setup_defaults:function(b,c,d){var e=a._indexMap[b],f=a._expandMap[this.rule.freq][e];return f!=a.CONTRACT&&(b in this.by_data||(this.by_data[b]=[d]),this.rule.freq!=c)?this.by_data[b][0]:d},toJSON:function(){var a=Object.create(null);return a.initialized=this.initialized,a.rule=this.rule.toJSON(),a.dtstart=this.dtstart.toJSON(),a.by_data=this.by_data,a.days=this.days,a.last=this.last.toJSON(),a.by_indices=this.by_indices,a.occurrence_number=this.occurrence_number,a}},a._indexMap={BYSECOND:0,BYMINUTE:1,BYHOUR:2,BYDAY:3,BYMONTHDAY:4,BYYEARDAY:5,BYWEEKNO:6,BYMONTH:7,BYSETPOS:8},a._expandMap={SECONDLY:[1,1,1,1,1,1,1,1],MINUTELY:[2,1,1,1,1,1,1,1],HOURLY:[2,2,1,1,1,1,1,1],DAILY:[2,2,2,1,1,1,1,1],WEEKLY:[2,2,2,2,3,3,1,1],MONTHLY:[2,2,2,2,2,3,3,1],YEARLY:[2,2,2,2,2,2,2,2]},a.UNKNOWN=0,a.CONTRACT=1,a.EXPAND=2,a.ILLEGAL=3,a}(),ICAL.RecurExpansion=function(){function a(a){return ICAL.helpers.formatClassType(a,ICAL.Time)}function b(a,b){return a.compare(b)}function c(a){return a.hasProperty("rdate")||a.hasProperty("rrule")||a.hasProperty("recurrence-id")}function d(a){this.ruleDates=[],this.exDates=[],this.fromData(a)}return d.prototype={complete:!1,ruleIterators:null,ruleDates:null,exDates:null,ruleDateInc:0,exDateInc:0,exDate:null,ruleDate:null,dtstart:null,last:null,fromData:function(b){var c=ICAL.helpers.formatClassType(b.dtstart,ICAL.Time);if(!c)throw new Error(".dtstart (ICAL.Time) must be given");if(this.dtstart=c,b.component)this._init(b.component);else{if(this.last=a(b.last)||c.clone(),!b.ruleIterators)throw new Error(".ruleIterators or .component must be given");this.ruleIterators=b.ruleIterators.map(function(a){return ICAL.helpers.formatClassType(a,ICAL.RecurIterator)}),this.ruleDateInc=b.ruleDateInc,this.exDateInc=b.exDateInc,b.ruleDates&&(this.ruleDates=b.ruleDates.map(a),this.ruleDate=this.ruleDates[this.ruleDateInc]),b.exDates&&(this.exDates=b.exDates.map(a),this.exDate=this.exDates[this.exDateInc]),"undefined"!=typeof b.complete&&(this.complete=b.complete)}},next:function(){for(var a,b,c,d=500,e=0;;){if(e++>d)throw new Error("max tries have occured, rule may be impossible to forfill.");if(b=this.ruleDate,a=this._nextRecurrenceIter(this.last),!b&&!a){this.complete=!0;break}if((!b||a&&b.compare(a.last)>0)&&(b=a.last.clone(),a.next()),this.ruleDate===b&&this._nextRuleDay(),this.last=b,!this.exDate||(c=this.exDate.compare(this.last),c<0&&this._nextExDay(),0!==c))return this.last;this._nextExDay()}},toJSON:function(){function a(a){return a.toJSON()}var b=Object.create(null);return b.ruleIterators=this.ruleIterators.map(a),this.ruleDates&&(b.ruleDates=this.ruleDates.map(a)),this.exDates&&(b.exDates=this.exDates.map(a)),b.ruleDateInc=this.ruleDateInc,b.exDateInc=this.exDateInc,b.last=this.last.toJSON(),b.dtstart=this.dtstart.toJSON(),b.complete=this.complete,b},_extractDates:function(a,c){function d(a){e=ICAL.helpers.binsearchInsert(f,a,b),f.splice(e,0,a)}for(var e,f=[],g=a.getAllProperties(c),h=g.length,i=0;i<h;i++)g[i].getValues().forEach(d);return f},_init:function(a){if(this.ruleIterators=[],this.last=this.dtstart.clone(),!c(a))return this.ruleDate=this.last.clone(),void(this.complete=!0);if(a.hasProperty("rdate")&&(this.ruleDates=this._extractDates(a,"rdate"),this.ruleDates[0]&&this.ruleDates[0].compare(this.dtstart)<0?(this.ruleDateInc=0,this.last=this.ruleDates[0].clone()):this.ruleDateInc=ICAL.helpers.binsearchInsert(this.ruleDates,this.last,b),this.ruleDate=this.ruleDates[this.ruleDateInc]),a.hasProperty("rrule"))for(var d,e,f=a.getAllProperties("rrule"),g=0,h=f.length;g<h;g++)d=f[g].getFirstValue(),e=d.iterator(this.dtstart),this.ruleIterators.push(e),e.next();a.hasProperty("exdate")&&(this.exDates=this._extractDates(a,"exdate"),this.exDateInc=ICAL.helpers.binsearchInsert(this.exDates,this.last,b),this.exDate=this.exDates[this.exDateInc])},_nextExDay:function(){this.exDate=this.exDates[++this.exDateInc]},_nextRuleDay:function(){this.ruleDate=this.ruleDates[++this.ruleDateInc]},_nextRecurrenceIter:function(){var a=this.ruleIterators;if(0===a.length)return null;for(var b,c,d,e=a.length,f=0;f<e;f++)b=a[f],c=b.last,b.completed?(e--,0!==f&&f--,a.splice(f,1)):(!d||d.last.compare(c)>0)&&(d=b);return d}},d}(),ICAL.Event=function(){function a(a,b){a instanceof ICAL.Component||(b=a,a=null),a?this.component=a:this.component=new ICAL.Component("vevent"),this._rangeExceptionCache=Object.create(null),this.exceptions=Object.create(null),this.rangeExceptions=[],b&&b.strictExceptions&&(this.strictExceptions=b.strictExceptions),b&&b.exceptions&&b.exceptions.forEach(this.relateException,this)}function b(a,b){return a[0]>b[0]?1:b[0]>a[0]?-1:0}return a.prototype={THISANDFUTURE:"THISANDFUTURE",exceptions:null,strictExceptions:!1,relateException:function(a){if(this.isRecurrenceException())throw new Error("cannot relate exception to exceptions");if(a instanceof ICAL.Component&&(a=new ICAL.Event(a)),this.strictExceptions&&a.uid!==this.uid)throw new Error("attempted to relate unrelated exception");var c=a.recurrenceId.toString();if(this.exceptions[c]=a,a.modifiesFuture()){var d=[a.recurrenceId.toUnixTime(),c],e=ICAL.helpers.binsearchInsert(this.rangeExceptions,d,b);this.rangeExceptions.splice(e,0,d)}},modifiesFuture:function(){var a=this.component.getFirstPropertyValue("range");return a===this.THISANDFUTURE},findRangeException:function(a){if(!this.rangeExceptions.length)return null;var c=a.toUnixTime(),d=ICAL.helpers.binsearchInsert(this.rangeExceptions,[c],b);if(d-=1,d<0)return null;var e=this.rangeExceptions[d];return c<e[0]?null:e[1]},getOccurrenceDetails:function(a){var b,c=a.toString(),d=a.convertToZone(ICAL.Timezone.utcTimezone).toString(),e={recurrenceId:a};if(c in this.exceptions)b=e.item=this.exceptions[c],e.startDate=b.startDate,e.endDate=b.endDate,e.item=b;else if(d in this.exceptions)b=this.exceptions[d],e.startDate=b.startDate,e.endDate=b.endDate,e.item=b;else{var f,g=this.findRangeException(a);if(g){var h=this.exceptions[g];e.item=h;var i=this._rangeExceptionCache[g];if(!i){var j=h.recurrenceId.clone(),k=h.startDate.clone();j.zone=k.zone,i=k.subtractDate(j),this._rangeExceptionCache[g]=i}var l=a.clone();l.zone=h.startDate.zone,l.addDuration(i),f=l.clone(),f.addDuration(h.duration),e.startDate=l,e.endDate=f}else f=a.clone(),f.addDuration(this.duration),e.endDate=f,e.startDate=a,e.item=this}return e},iterator:function(a){return new ICAL.RecurExpansion({component:this.component,dtstart:a||this.startDate})},isRecurring:function(){var a=this.component;return a.hasProperty("rrule")||a.hasProperty("rdate")},isRecurrenceException:function(){return this.component.hasProperty("recurrence-id")},getRecurrenceTypes:function(){for(var a=this.component.getAllProperties("rrule"),b=0,c=a.length,d=Object.create(null);b<c;b++){var e=a[b].getFirstValue();d[e.freq]=!0}return d},get uid(){return this._firstProp("uid")},set uid(a){this._setProp("uid",a)},get startDate(){return this._firstProp("dtstart")},set startDate(a){this._setTime("dtstart",a)},get endDate(){var a=this._firstProp("dtend");if(!a){var b=this._firstProp("duration");a=this.startDate.clone(),b?a.addDuration(b):a.isDate&&(a.day+=1)}return a},set endDate(a){this._setTime("dtend",a)},get duration(){var a=this._firstProp("duration");return a?a:this.endDate.subtractDate(this.startDate)},get location(){return this._firstProp("location")},set location(a){return this._setProp("location",a)},get attendees(){return this.component.getAllProperties("attendee")},get summary(){return this._firstProp("summary")},set summary(a){this._setProp("summary",a)},get description(){return this._firstProp("description")},set description(a){this._setProp("description",a)},get organizer(){return this._firstProp("organizer")},set organizer(a){this._setProp("organizer",a)},get sequence(){return this._firstProp("sequence")},set sequence(a){this._setProp("sequence",a)},get recurrenceId(){return this._firstProp("recurrence-id")},set recurrenceId(a){this._setProp("recurrence-id",a)},_setTime:function(a,b){var c=this.component.getFirstProperty(a);c||(c=new ICAL.Property(a),this.component.addProperty(c)),b.zone===ICAL.Timezone.localTimezone||b.zone===ICAL.Timezone.utcTimezone?c.removeParameter("tzid"):c.setParameter("tzid",b.zone.tzid),c.setValue(b)},_setProp:function(a,b){this.component.updatePropertyWithValue(a,b)},_firstProp:function(a){return this.component.getFirstPropertyValue(a)},toString:function(){return this.component.toString()}},a}(),ICAL.ComponentParser=function(){function a(a){"undefined"==typeof a&&(a={});var b;for(b in a)a.hasOwnProperty(b)&&(this[b]=a[b])}return a.prototype={parseEvent:!0,parseTimezone:!0,oncomplete:function(){},onerror:function(a){},ontimezone:function(a){},onevent:function(a){},process:function(a){"string"==typeof a&&(a=ICAL.parse(a)),a instanceof ICAL.Component||(a=new ICAL.Component(a));for(var b,c=a.getAllSubcomponents(),d=0,e=c.length;d<e;d++)switch(b=c[d],b.name){case"vtimezone":if(this.parseTimezone){var f=b.getFirstPropertyValue("tzid");f&&this.ontimezone(new ICAL.Timezone({tzid:f,component:b}))}break;case"vevent":this.parseEvent&&this.onevent(new ICAL.Event(b));break;default:continue}this.oncomplete()}},a}();
//# sourceMappingURL=ical.min.js.map

/***/ }),
/* 95 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
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

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Backbone = __webpack_require__(6);

	return Backbone.Model.extend({});
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));

/***/ }),
/* 96 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/* global Promise */

/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015, 2016, 2017
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var $ = __webpack_require__(5);
	var _ = __webpack_require__(3);
	var OC = __webpack_require__(7);
	var Radio = __webpack_require__(1);

	Radio.folder.reply('entities', getFolderEntities);

	function buildUnifiedInbox(account) {
		account.addFolder({
			id: btoa('all-inboxes'),
			name: t('mail', 'All inboxes'),
			specialRole: 'inbox',
			isEmpty: false,
			accountId: -1,
			noSelect: false,
			delimiter: '.'
		});

		return Promise.resolve(account.folders);
	}

	/**
	 * @param {Account} account
	 * @returns {Promise}
	 */
	function getFolderEntities(account) {
		var url = OC.generateUrl('apps/mail/accounts/{id}/folders', {
			id: account.get('accountId')
		});

		if (account.id === -1) {
			return buildUnifiedInbox(account);
		}

		return Promise.resolve($.get(url))
			.then(function(data) {
				for (var prop in data) {
					if (prop === 'folders') {
						account.folders.reset();
						_.each(data.folders, account.addFolder, account);
					} else {
						account.set(prop, data[prop]);
					}
				}
				return account.folders;
			});
	}
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 97 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/* global Promise */

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

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var _ = __webpack_require__(3);
	var $ = __webpack_require__(5);
	var OC = __webpack_require__(7);
	var Radio = __webpack_require__(1);

	Radio.sync.reply('sync:folder', syncFolder);

	/**
	 * @private
	 * @param {Folder} folder
	 * @returns {Promise}
	 */
	function syncSingleFolder(folder, unifiedFolder) {
		var url = OC.generateUrl('/apps/mail/accounts/{accountId}/folders/{folderId}/sync', {
			accountId: folder.account.get('accountId'),
			folderId: folder.get('id')
		});

		return Promise.resolve($.ajax(url, {
			data: {
				syncToken: folder.get('syncToken'),
				uids: folder.messages.pluck('id')
			}
		})).then(function(syncResp) {
			folder.set('syncToken', syncResp.token);

			var newMessages = folder.addMessages(syncResp.newMessages);
			if (unifiedFolder) {
				unifiedFolder.addMessages(newMessages);
			}
			_.each(syncResp.changedMessages, function(msg) {
				var existing = folder.messages.get(msg.id);
				if (existing) {
					var flags = {};
					if (msg.flags && _.isObject(msg.flags)) {
						flags = msg.flags;
						delete msg.flags;
					}
					existing.set(msg);
					existing.get('flags').set(flags);
				} else {
					// TODO: remove once we're confident this
					// condition never occurs
					throw new Error('non-existing message while syncing');
				}

				if (unifiedFolder) {
					var id = unifiedFolder.messages.getUnifiedId(folder.messages.get(msg.id));
					var message = unifiedFolder.messages.get(id);
					if (!message) {
						console.info('Changed message missing in unified inbox');
					} else {
						message.set(msg);
					}
				}
			});
			_.each(syncResp.vanishedMessages, function(id) {
				if (unifiedFolder) {
					var unifiedInboxId = unifiedFolder.messages.getUnifiedId(folder.messages.get(id));
					unifiedFolder.messages.remove(unifiedInboxId);
				}

				folder.messages.remove(id);
			});

			return newMessages;
		});
	}

	/**
	 * @param {Folder} folder
	 * @returns {Promise}
	 */
	function syncFolder(folder) {
		var allAccounts = __webpack_require__(0).accounts;

		if (folder.account.get('isUnified')) {
			var unifiedFolder = folder;
			// Sync other accounts
			return Promise.all(allAccounts.filter(function(acc) {
				// Select other accounts
				return acc.id !== folder.account.id;
			}).map(function(acc) {
				// Select its inboxes
				return acc.folders.filter(function(f) {
					return f.get('specialRole') === 'inbox';
				});
			}).reduce(function(acc, f) {
				// Flatten nested array
				return acc.concat(f);
			}, []).map(function(folder) {
				return syncSingleFolder(folder, unifiedFolder);
			})).then(function(results) {
				return results.reduce(function(acc, newMessages) {
					return acc.concat(newMessages);
				}, []);
			});
		} else {
			var unifiedAccount = allAccounts.get(-1);
			if (unifiedAccount) {
				var unifiedFolder = unifiedAccount.folders.first();
				return syncSingleFolder(folder, unifiedFolder);
			}
			return syncSingleFolder(folder);
		}
	}

	return {
		syncFolder: syncFolder
	};
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 98 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/* global Promise, Infinity */

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

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var $ = __webpack_require__(5);
	var _ = __webpack_require__(3);
	var OC = __webpack_require__(7);
	var Radio = __webpack_require__(1);

	Radio.message.reply('entities', getMessageEntities);
	Radio.message.reply('next-page', getNextMessagePage);
	Radio.message.reply('entity', getMessageEntity);
	Radio.message.reply('bodies', fetchMessageBodies);
	Radio.message.reply('flag', flagMessage);
	Radio.message.reply('move', moveMessage);
	Radio.message.reply('send', sendMessage);
	Radio.message.reply('draft', saveDraft);
	Radio.message.reply('delete', deleteMessage);

	function getFolderMessages(folder, options) {
		var defaults = {
			cache: false,
			filter: ''
		};
		_.defaults(options, defaults);

		// Do not cache search queries
		if (options.filter !== '') {
			options.cache = false;
		}
		if (options.cache && folder.get('messagesLoaded')) {
			return Promise.resolve(folder.messages, true);
		}

		var url = OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages', {
			accountId: folder.account.get('accountId'),
			folderId: folder.get('id')
		});

		return Promise.resolve($.ajax(url, {
			data: {
				filter: options.filter
			},
			error: function(error, status) {
				if (status !== 'abort') {
					console.error('error loading messages', error);
					throw new Error(error);
				}
			}
		})).then(function(messages) {
			var isSearching = options.filter !== '';
			var collection = folder.messages;

			if (isSearching) {
				// Get rid of other messages
				collection.reset();
				folder.set('messagesLoaded', false);
			} else {
				folder.set('messagesLoaded', true);
			}

			_.forEach(messages, function(msg) {
				msg.accountMail = folder.account.get('email');
			});
			folder.addMessages(messages);

			return collection;
		});
	}

	function getUnifiedFolderMessages(folder, options) {
		var allAccounts = __webpack_require__(0).accounts;
		// Fetch and merge other accounts
		return Promise.all(allAccounts.filter(function(acc) {
			// Select other accounts
			return acc.id !== folder.account.id;
		}).map(function(acc) {
			// Select its inboxes
			return acc.folders.filter(function(f) {
				return f.get('specialRole') === 'inbox';
			});
		}).reduce(function(acc, f) {
			// Flatten nested array
			return acc.concat(f);
		}, []).map(function(otherInbox) {
			return getFolderMessages(otherInbox, options)
				.then(function(messages) {
					folder.addMessages(messages.models);
				});
		})).then(function() {
			// Truncate after 20 messages
			// TODO: there might be a more efficient/convenient
			// Backbone.Collection or underscore helper function
			var top20 = folder.messages.slice(0, 20);
			folder.messages.reset();
			folder.addMessages(top20);
			return folder.messages;
		});
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {object} options
	 * @returns {Promise}
	 */
	function getMessageEntities(account, folder, options) {
		options = options || {};

		if (account.get('isUnified')) {
			return getUnifiedFolderMessages(folder, options);
		} else {
			return getFolderMessages(folder, options);
		}
	}

	function getNextUnifiedMessagePage(unifiedFolder, options) {
		var allAccounts = __webpack_require__(0).accounts;
		var cursor = Infinity;
		if (!unifiedFolder.messages.isEmpty()) {
			cursor = unifiedFolder.messages.last().get('dateInt');
		}

		var individualAccounts = allAccounts.filter(function(account) {
			// Only non-unified accounts
			return !account.get('isUnified');
		});

		// Load data from folders where we do not have enough data
		return Promise.all(individualAccounts.map(function(account) {
			return Promise.all(account.folders.filter(function(folder) {
				// Only consider inboxes
				// TODO: generalize for other combined mailboxes
				return folder.get('specialRole') === 'inbox';
			}).filter(function(folder) {
				// Only fetch mailboxes that do not have enough data
				return folder.messages.filter(function(message) {
					return message.get('dateInt') < cursor;
				}).length < 21;
			}).map(function(folder) {
				return getNextMessagePage(folder.account, folder, options);
			}));
		})).then(function() {
			var allMessagesPage = individualAccounts.map(function(account) {
				return account.folders.filter(function(folder) {
					// Only consider inboxes
					// TODO: generalize for other combined mailboxes
					return folder.get('specialRole') === 'inbox';
				}).map(function(folder) {
					var messages = folder.messages.filter(function(message) {
						return message.get('dateInt') < cursor;
					});
					// Take all but the last message (acts as cursor)
					return messages.slice(0, messages.length - 2);
				}).reduce(function(all, messages) {
					return all.concat(messages);
				}, []);
			}).reduce(function(all, messages) {
				return all.concat(messages);
			}, []);

			var nextPage = allMessagesPage.sort(function(message) {
				return message.get('dateInt') * -1;
			}).slice(0, 20);

			nextPage.forEach(function(msg) {
				msg.set('unifiedId', unifiedFolder.messages.getUnifiedId(msg));
			});

			unifiedFolder.addMessages(nextPage, unifiedFolder);
		});
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {object} options
	 * @returns {Promise}
	 */
	function getNextMessagePage(account, folder, options) {
		options = options || {};
		var defaults = {
			filter: ''
		};
		_.defaults(options, defaults);

		if (account.get('isUnified')) {
			return getNextUnifiedMessagePage(folder, options);
		} else {
			var url = OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages', {
				accountId: account.get('accountId'),
				folderId: folder.get('id')
			});
			var cursor = null;
			if (!folder.messages.isEmpty()) {
				cursor = folder.messages.last().get('dateInt');
			}

			return new Promise(function(resolve, reject) {
				$.ajax(url, {
					method: 'GET',
					data: {
						filter: options.filter,
						cursor: cursor
					},
					success: resolve,
					error: function(error, status) {
						if (status !== 'abort') {
							reject(error);
						}
					}
				});
			}).then(function(messages) {
				var collection = folder.messages;
				folder.addMessages(messages);
				return collection;
			});
		}
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {number} messageId
	 * @param {object} options
	 * @returns {Promise}
	 */
	function getMessageEntity(account, folder, messageId, options) {
		options = options || {};

		var url = OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages/{messageId}', {
			accountId: account.get('accountId'),
			folderId: folder.get('id'),
			messageId: messageId
		});

		// Load cached version if available
		var message = __webpack_require__(8).getMessage(account,
			folder,
			messageId);
		if (message) {
			return Promise.resolve(message);
		}

		return new Promise(function(resolve, reject) {
			$.ajax(url, {
				type: 'GET',
				success: resolve,
				error: function(jqXHR, textStatus) {
					console.error('error loading message', jqXHR);
					if (textStatus !== 'abort') {
						reject(jqXHR);
					}
				}
			});
		});
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {array} messageIds
	 * @returns {Promise}
	 */
	function fetchMessageBodies(account, folder, messageIds) {
		var cachedMessages = [];
		var uncachedIds = [];

		_.each(messageIds, function(messageId) {
			var message = __webpack_require__(8).getMessage(account, folder, messageId);
			if (message) {
				cachedMessages.push(message);
			} else {
				uncachedIds.push(messageId);
			}
		});

		return new Promise(function(resolve, reject) {
			if (uncachedIds.length > 0) {
				var Ids = uncachedIds.join(',');
				var url = OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages?ids={ids}', {
					accountId: account.get('accountId'),
					folderId: folder.get('id'),
					ids: Ids
				});
				return Promise.resolve($.ajax(url, {
					type: 'GET'
				}));
			}
			reject();
		});
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {Message} message
	 * @param {string} flag
	 * @param {boolean} value
	 * @returns {Promise}
	 */
	function flagMessage(account, folder, message, flag, value) {
		var flags = [flag, value];
		var url = OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages/{messageId}/flags', {
			accountId: account.get('accountId'),
			folderId: folder.get('id'),
			messageId: message.id
		});
		return Promise.resolve($.ajax(url, {
			type: 'PUT',
			data: {
				flags: _.object([flags])
			}
		}));
	}

	/**
	 * @param {Account} sourceAccount
	 * @param {Folder} sourceFolder
	 * @param {Message} message
	 * @param {Account} destAccount
	 * @param {Folder} destFolder
	 * @returns {Promise}
	 */
	function moveMessage(sourceAccount, sourceFolder, message, destAccount,
		destFolder) {

		var url = OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages/{messageId}/move', {
			accountId: sourceAccount.get('accountId'),
			folderId: sourceFolder.get('id'),
			messageId: message.get('id')
		});
		return Promise.resolve($.ajax(url, {
			type: 'POST',
			data: {
				destAccountId: destAccount.get('accountId'),
				destFolderId: destFolder.get('id')
			}
		}));
	}

	/**
	 * @param {Account} account
	 * @param {object} message
	 * @param {object} options
	 * @returns {Promise}
	 */
	function sendMessage(account, message, options) {
		var defaultOptions = {
			draftUID: null,
			aliasId: null
		};
		_.defaults(options, defaultOptions);
		var url = OC.generateUrl('/apps/mail/accounts/{id}/send', {
			id: account.get('id')
		});
		return Promise.resolve($.ajax(url, {
			type: 'POST',
			data: {
				to: message.to,
				cc: message.cc,
				bcc: message.bcc,
				subject: message.subject,
				body: message.body,
				attachments: message.attachments,
				folderId: options.repliedMessage ? options.repliedMessage.get('folderId') : undefined,
				messageId: options.repliedMessage ? options.repliedMessage.get('messageId') : undefined,
				draftUID: options.draftUID,
				aliasId: options.aliasId
			}
		}));
	}

	/**
	 * @param {Account} account
	 * @param {object} message
	 * @param {object} options
	 * @returns {Promise}
	 */
	function saveDraft(account, message, options) {
		var defaultOptions = {
			folder: null,
			messageId: null,
			draftUID: null
		};
		_.defaults(options, defaultOptions);

		// TODO: replace by Backbone model method
		function undefinedOrEmptyString(prop) {
			return prop === undefined || prop === '';
		}
		var emptyMessage = true;
		var propertiesToCheck = ['to', 'cc', 'bcc', 'subject', 'body'];
		_.each(propertiesToCheck, function(property) {
			if (!undefinedOrEmptyString(message[property])) {
				emptyMessage = false;
			}
		});
		// END TODO

		if (emptyMessage) {
			if (options.draftUID !== null) {
				// Message is empty + previous draft exists -> delete it
				var draftsFolder = account.getSpecialFolder('draft');
				var deleteUrl =
					OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages/{messageId}', {
						accountId: account.get('accountId'),
						folderId: draftsFolder,
						messageId: options.draftUID
					});
				return Promise.resolve($.ajax(deleteUrl, {
					type: 'DELETE'
				}));
			}
			return Promise.resolve({
				uid: null
			});
		}

		var url = OC.generateUrl('/apps/mail/accounts/{id}/draft', {
			id: account.get('accountId')
		});
		return Promise.resolve($.ajax(url, {
			type: 'POST',
			data: {
				to: message.to,
				cc: message.cc,
				bcc: message.bcc,
				subject: message.subject,
				body: message.body,
				attachments: message.attachments,
				folderId: options.folder ? options.folder.get('id') : null,
				messageId: options.repliedMessage ? options.repliedMessage.get('id') : null,
				uid: options.draftUID
			}
		}));
	}

	/**
	 * @param {Message} message
	 * @returns {Promise}
	 */
	function deleteMessage(message) {
		var url = OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages/{messageId}', {
			accountId: message.folder.account.get('accountId'),
			folderId: message.folder.get('id'),
			messageId: message.get('id')
		});
		return Promise.resolve($.ajax(url, {
			type: 'DELETE'
		}));
	}

	return {
		getNextMessagePage: getNextMessagePage
	};
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 99 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
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

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Radio = __webpack_require__(1);
	var _timer = null;
	var _accounts = null;

	Radio.sync.on('start', startBackgroundSync);

	var SYNC_INTERVAL = 30 * 1000; // twice a minute

	function startBackgroundSync(accounts) {
		_accounts = accounts;
		clearTimeout(_timer);
		triggerNextSync();
	}

	function triggerNextSync() {
		_timer = setTimeout(function() {
			var account;
			if (__webpack_require__(0).accounts.length === 0) {
				account = _accounts.first();
			} else {
				account = _accounts.get(-1);
			}
			sync(account);
		}, SYNC_INTERVAL);
	}

	/**
	 * @param {Account} account
	 * @returns {Promise}
	 */
	function sync(account) {
		return Radio.sync.request('sync:folder', account.folders.first())
			.then(function(newMessages) {
				Radio.ui.trigger('notification:mail:show', newMessages);
			})
			.catch(function(e) {
				console.error(e);
			})
			.then(triggerNextSync);
	}

	return {
		sync: sync
	};
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 100 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/* global Notification */

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

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var _ = __webpack_require__(3);
	var OC = __webpack_require__(7);
	var Radio = __webpack_require__(1);

	Radio.ui.on('notification:mail:show', showMailNotification);
	Radio.ui.on('notification:request', requestNotification);

	function requestNotification() {
		if (typeof Notification !== 'undefined') {
			Notification.requestPermission();
		}
	}

	function showNotification(title, body, icon) {
		// notifications not supported -> go away
		if (typeof Notification === 'undefined') {
			return;
		}
		// browser is active -> go away
		var isWindowFocused = document.querySelector(':focus') !== null;
		if (isWindowFocused) {
			return;
		}
		var notification = new Notification(
			title,
			{
				body: body,
				icon: icon
			}
		);
		notification.onclick = function() {
			window.focus();
		};
	}

	/**
	 * @param {array<Message>} messages
	 * @returns {undefined}
	 */
	function showMailNotification(messages) {
		if (Notification.permission === 'granted' && messages.length > 0) {
			var from = _.map(messages, function(m) {
				return m.get('from');
			});
			from = _.uniq(from);
			if (from.length > 2) {
				from = from.slice(0, 2);
				from.push('…');
			} else {
				from = from.slice(0, 2);
			}
			// special layout if there is only 1 new message
			var body = '';
			if (messages.length === 1) {
				var subject = _.map(messages, function(m) {
					return m.get('subject');
				});
				body = t('mail',
					'{from}\n{subject}', {
						from: from.join(),
						subject: subject.join()
					});
			} else {
				body = n('mail',
					'%n new message \nfrom {from}',
					'%n new messages \nfrom {from}',
					messages.length, {
						from: from.join()
					});
			}
			// If it's okay let's create a notification
			var icon = OC.filePath('mail', 'img', 'mail-notification.png');
			showNotification(t('mail', 'Nextcloud Mail'), body, icon);
		}
	}

	return {
		showNotification: showNotification,
		showMailNotification: showMailNotification
	};
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ }),
/* 101 */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015, 2016
 */

!(__WEBPACK_AMD_DEFINE_RESULT__ = function(require) {
	'use strict';

	var Radio = __webpack_require__(1);
	var lastQuery = '';

	function filter(query) {
		if (query !== lastQuery) {
			lastQuery = query;

			if (__webpack_require__(0).currentAccount && __webpack_require__(0).currentFolder) {
				var accountId = __webpack_require__(0).currentAccount.get('accountId');
				var folderId = __webpack_require__(0).currentFolder.get('id');
				Radio.navigation.trigger('search', accountId, folderId, query);
			}
		}
	}

	return {
		filter: filter
	};
}.call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));


/***/ })
/******/ ]);