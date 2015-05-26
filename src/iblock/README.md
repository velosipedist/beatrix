# Infoblock query utilities

Now we can forget about endless `GetList()` docs referring & bugs workaroundings. 
We have all shortcuts needed to make iblock queries simple and predictable.

## Query elements or sections

~~~php
// get all elements of news infoblock
$elements = Query::from('news')
  ->select(['NAME', 'DATE_ACTIVE_FROM', 'PROPERTY_*']) // which fields and/or properties are needed
  ->getElements();
foreach($elements as $elem){
  // $elem is array with all Bitrix provided fields
  print $elem['NAME'];
  
  // but properties accessedd like following
  print $elem['PROPERTIES']['propCode']['VALUE'];
  print $elem['PROPERTIES']['propCode']['DESCRIPTION'];
  
  // supporting correct returning of multiple-value properties
  print $elem['PROPERTIES']['multivaluePropCode']['VALUE'][0];
}

// sections list will be returned
$sections = Query::from('news')
  ->select(['NAME', 'CODE'])
  ->getSections();
~~~

## Filtering

~~~php
$query = Query::from('news');

// add field conditions, support Bitrix API operators
$query->filter([
  'NAME' => 'foo',
  '!ID' => '666',
]);

// properties conditions, for now only exact property names supported, with no operators
$query->propertyFilter([
  'propCode' => 1,
]);

// enum properties filtering
$query->enumFilter([
  'married' => 'Y' // Y is code of value, not the value label!
]);

// randomize output
$query->random();

// ...and get your elements
$elements = $query->getElements();
~~~

## Pagination

Just specify limit and page on query building. Or parametrize pagination on getElements() call:

~~~php
// explicit values of pagination
$query = Query::from('news')
  ->limit(10)
  ->page(2);
  
$elements = $query->getElements(10, 'pagination_page');
  
// paginate by sizem optionally chenge default $_GET param for pagenum
$query = Query::from('news');
$elements = $query->getElements(10, 'pagination_page');

// pagination will be rendered with no pain, bootstrap 3 template by default
print $elements->pagination();
~~~

## to be continued...