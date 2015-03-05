![Make Bitrix adopted to real world](http://velosipedist.org/images/beatrix/mascot-mini.png)

# Beatrix

Attempt to beat the Bitrix defectiveness. You should not know what is inside.

## What i have to do?

Just download `beatrix.phar` or build it by yourself using [Box](http://box-project.org/)

```
php box.phar build
```

Include `beatrix.phar` at Bitrix startup (recommended in /bitrix/php_interface/init.php, create if not exists).

Initialize just constructing Beatrix instance:

```php
new Beatrix(/* array of Slim options*/);
```

Some options will be forced internally, others available as described in [Slim docs](http://docs.slimframework.com/#Application-Settings)

## Nothing changed. What's next?

Of course. There is no magic (almost), you have to write some code yourself :unamused:.

All Beatrix instance methods available through `Beatrix::app()`, also we have some useful shortcut functions.

For now we have following features:

### Blade engine

Brings us all [Blade](http://laravel.com/docs/templates#blade-templating) powers, templates inheritance, blocks, etc.
Handles any .php and .blade.php templates, but first extensions will be treated as «clean» PHP scripts.

Just add one or more template dirs at runtime and do Blade magic:

```php
Beatrix::app()->addViewsDir('/path/to/templates/dir');
Beatrix::app()->addViewsDir(array('/one/more', '/any/path/'));

// then just
blade('template/name', array('param'=>$val), /* add true for return rendering result */);
```

### Slim instance running

Beatrix is [Slim](http://slimframework.com/) app with some tricky tunings under the hood. But we still have all routing power of Slim.

It allows to handle any non-static site part with __no more /urlrewrite.php!__

Put `.htaccess` in desired directory:

```
RewriteEngine On
#just for case:
#RewriteBase /

RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [QSA,L]
```

Then inside index.php (leave Bitrix header & Bitrix footer)

```php
// news/index.php with .htaccess in same dir:
Beatrix::app()->get('/', function(){
  /* news list */
  Beatrix::app()->render('blade/template', ['var' => $val]);
});
Beatrix::app()->get('/:category+', function($category){/* news list inside category, optionally nested*/});
Beatrix::app()->get('/:category+/:id', function($category, $id){/* news detail */});

// in the end of page, start listening http requests captured with .htaccess
Beatrix::app()->run();
```

All routing features (get/post/etc, grouping, regexp in conditions) available in [docs](http://docs.slimframework.com/#Routing-Overview).

### DbResultIterator

Insanely obvious, long-awaited feature to reduce memory usage and code bloating — forward-only iterating of db query results.

It can be passed to template instead of array of dirty data arrays.

```php
$iterator = DbResultIterator::from(CIBlock::GetList(/* conditions */));
blade('iblock-list', ['items' => $iterator]);
```

### Helper functions

`blade()` see Blade chapter

`is_ajax()` does current http request sent by AJAX. Useful in Bitrix templates conditions
(set in PHP condition of empty template, to render "plain" app response).

`slimUrl()` url to named route with path params + query params appending support

And finally, all Illuminate/Support sweet helpers (`array_pluck`, `str_finish` etc).

## Is it all?

Yes, for now, but in perspective:

* automatic menu builders, by stucture, Infoblock hierarchy and mixed
* Infoblock queryuing facade and comfortable results decorating
* email sending
* some console tools for routine tasks

Also, some subsplit modules (not spooky Bitrix modules!) in draft:
* RegionManager
* Faq sending
* «Call me back» form
