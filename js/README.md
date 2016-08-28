# JavaScript Development

All source files are stored inside this directory or subdirectories of it. With the help of [requirejs](http://www.requirejs.org/)
any .js and .html template file is loaded into the browser asynchronously if needed. Make sure you have debug mode enabled in your development
setup because this loads source files instead of the compressed one.

## Optimizing file loading for production use

While it's convenient to be able to change any source file and see those changes on the next page load, it takes some
time to load 50+ files right after the browser has loaded the page. Fortunately, requirejs has an optimizer which can
be easily executed with the Makefile in the root of this repository. Simply run
```bash
make optimize-js
```
inside the project's root directory. This combines and compresses all used JavaScript source files and HTML template
files into one file: ``js/mail.min.js``. If debug mode is disabled, this compressed file is then used.

## Coding guidelines

Generally, any code contributed to this repository should comply with the general [ownCloud coding style guidelines](https://doc.owncloud.org/server/9.1/developer_manual/general/codingguidelines.html).

Currently we use several frameworks and their extensions. Namely, this app is build with jQuery, Underscore.js, Backbone.js and Backbone Marionette. Additionally, Require.js is used for loading module dependencies (code and template).

The client-side software is structured as [Model-View-Controller application](https://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93controller).

### Modules
Since Require.js is used for loading module dependencies, all code (Model, View, Controller, Service, Templates) are structured as modules. A typical module looks like this:
```js
define(function(require) {
  'use strict';
  
  // get any other dependencies
  var Backbone = require('backbone');
  
  // create module here
  var MessageFlag = Backbone.Model.extend{
    defaults: {
      name: '',
      value: false
    },
    doSomethingUseful: function(param1) {
      // Something useful
    }
  };
  
  // return the module
  return MessageFlag;
});
```

Since this is a model, it would be stored to ``js/model/messageflag.js``. Any other module that depends on that module can require it with ``require('model/messageflag')``.

For controllers, services and any other module that is used as singleton, the 'revealing module pattern' is used. There are many tutorials on the internet that explain how it works and what it's advantages are.

### Models
All models should be Backbone models. Usually this means you create models by extending ``Backbone.Model``:
```js
define(function(require) {
  'use strict';
  
  return Backbone.Model.extend({
    foo: 13,
    bar: function(input) {
      return input * 2;
    }
  });
});
```

### Views
TODO

### Controller
TODO

### Templates
TODO
