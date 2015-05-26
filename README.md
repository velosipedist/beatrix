![Face the truth — now it is better, much better!](http://velosipedist.org/images/beatrix/mascot-mini.png)

# Beatrix

Attempt to beat the Bitrix defectiveness. You should not know what is inside.

## What i have to do?

Just download `beatrix.phar` from releases Github section or build it by yourself using [Phing](https://www.phing.info/trac/wiki/Users/Installation)

```
php phing-latest.phar
```

Include `beatrix.phar` at Bitrix startup (recommended in /bitrix/php_interface/init.php, create if not exists).

Initialize with options if you need to tune up something:

```php
// inside of init.php
Beatrix::init(array(
	Beatrix::SETTINGS_TEMPLATES_DIR => $_SERVER['DOCUMENT_ROOT'].'/.tpl' // where to look for templates
	//... any Slim options are acceptable
));
```

Some options will be forced internally, others available as described in [Slim docs](http://docs.slimframework.com/#Application-Settings)

## Nothing changed. What's next?

Of course. There is no magic (almost), you have to write some code yourself :unamused:.

All Beatrix instance methods available through `Beatrix::app()` (it gives us access to Application which is Slim app with some improvements), but also we have some useful shortcut functions.

For now we have following features:

## Plates templating engine

Old school template-sawing (with header.php & footer.php as output) replaced with power of [Plates](http://platesphp.com/) engine. It gives us templates inheritance, blocks, etc.

### Basic setup

Just create .tpl folder at site document root (or configure any other using options). Now you can place any php templates here and render them individually: 

```php
Beatrix::app()->render('my/template', ['var1'=>$anyData, /* etc */]); // will seek /.tpl/my/template.php
```

Non-sawed layout can be used this way:

1. open template's header.php and write only this

```
<?php Beatrix::templateHeader();
```
2. at template's footer.php write:

```
<?php Beatrix::templateFooter();
```

After this, Beatrix will search `.tpl/layout/{templateName}` as layout template.

But you can change layout at runtime from anywhere, just calling:
```php
Beatrix::layout()->setLayout('any/existing/template', ['varToBePassed'=>$var]);
```

> ! Any layout template **MUST** contain `$this->section('content')` call !

### Sections

Inside of any particular template you can create or rewrite existing named section to print at layout later:

```php
// in any template or even outsid of it
Beatrix::layout()->setSection('name', $contents);
```

If contents is string it will be written as section output, that can be overwritten later if you want.

If contents is callback, it will be executed at section rendering moment inside of layout template. It is useful when some heavy rendering logic may become useless for some reason later, and this section will be omitted without overhead.

## Slim instance running

Beatrix uses [Slim](http://slimframework.com/) app instance with some tunings under the hood, to keep all settings, common container features and fields. 

We also have all routing power of Slim. It allows to handle any non-static site part with __no more /urlrewrite.php!__

Put `.htaccess` in desired site subdirectory, i.e. `/company/news`:

```
RewriteEngine On
#just for case:
RewriteBase /company/news

RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]
```

Then inside index.php (leave Bitrix file header & footer)

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

## Database and infoblocks

See readme for iblocks (src/iblock/README.md) for details

## Helper functions

`is_ajax()` does current http request sent by AJAX. Useful in Bitrix templates conditions
(set in PHP condition of empty template, to render "plain" app response).

`slimUrl()` url to named route with path params + query params appending support

And finally, all Illuminate/Support sweet helpers (`array_pluck`, `str_finish` etc).

## RSS builder bundled

Instead of generic component you can emit RSS data using [this library](https://github.com/suin/php-rss-writer)

## Is it all for now?

Yes, for now, but in perspective or alpha development:

* automatic menu builders, by stucture, Infoblock hierarchy and mixed
* email sending
* some console tools for routine tasks

Also, some subsplit modules (not spooky Bitrix modules!) in draft:
* RegionManager
* Faq sending
* «Call me back» form
