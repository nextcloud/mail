# Page Visibility shim for jQuery

This plugin gives you a very simple API that allows you to execute callbacks when the page’s visibility state changes.

It does so by using [the Page Visibility API](http://www.w3.org/TR/page-visibility/) where it’s supported, and falling back to good old `focus` and `blur` in older browsers.

## Demo

<http://mathiasbynens.be/demo/jquery-visibility>

## When to use?

Typical use cases include but are not limited to pausing/resuming slideshows, video, and/or embedded audio clips.

## Example usage

This plugin simply provides two custom document events for you to use: `show` and `hide`. When the page visibility state changes, the appropriate event will be triggered.

You can use them separately:

```js
$(document).on('show', function() {
  // the page gained visibility
});
```

```js
$(document).on('hide', function() {
  // the page was hidden
});
```

For most applications you'll need both events, so the most convenient option is to use an events map. This way, you can bind both event handlers in one go:

```js
$(document).on({
  'show': function() {
    console.log('The page gained visibility; the `show` event was triggered.');
  },
  'hide': function() {
    console.log('The page lost visibility; the `hide` event was triggered.');
  }
});
```

Or bind both to the same callback and distinguish using the event variable.

```js
$(document).on('show hide', function (e) {
	console.log('The page is now', e.type === 'show' ? 'visible' : 'hidden');
});
```

The plugin will detect if the Page Visibility API is natively supported in the browser or not, and expose this information as a boolean (`true`/`false`) in `$.support.pageVisibility`.  
__Warning:__ `$.support` was marked deprecated in jQuery version 1.9, so it is likely to be removed in the future.

```js
if ($.support.pageVisibility) {
  // Page Visibility is natively supported in this browser
}
```

If the Page Visibility API is supported the plugin will also store the current visibility state in `document.hidden`.

```js
if (!document.hidden) {
  // Page is currently visible
}
```

## Notes

This plugin is not a Page Visibility [polyfill](http://mths.be/polyfills), as it doesn’t aim to mimic the standard API. It merely provides a simple way to use this functionality (or a fallback) in your jQuery code.

## License

This plugin is available under the [MIT license](http://opensource.org/licenses/MIT).

## Author

[Mathias Bynens](http://mathiasbynens.be/)

## Contributors

[Jan Paepke](http://github.com/janpaepke),
[John-David Dalton](http://allyoucanleet.com/)