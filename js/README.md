# JavaScript Development

All source files are stored inside this directory or subdirectories of it. With the help of [requirejs](http://www.requirejs.org/)
any .js and .html template file is loaded into the browser asynchronously if needed. Make sure you have 
[debug mode](https://doc.owncloud.org/server/8.2/developer_manual/general/devenv.html#enabling-debug-mode) enabled in your development
setup because this loads source files instead of the compressed one.

## Optimizing file loading for production use

While it's convenient to be able to change any source file and see those changes on the next page load, it takes some
time to load 50+ files right after the browser has loaded the page. Fortunately, requirejs has an optimizer which can
be easily executed with the Makefile in the root of this repository. Simply run
```bash
make opimize-js
```
inside the project's root directory. This combines and compresses all used JavaScript source files and HTML template
files into one file: ``js/mail.min.js``. If [debug mode](https://doc.owncloud.org/server/8.2/developer_manual/general/devenv.html#enabling-debug-mode)
is disabled, this compressed file is then used.

