kanon-mongo
===========

PHP MongoDb ORM


### Class map
```php
$autoload = array(
'ru\\olamedia\\kanon\\mongo\\connection' => 'connection.php',
'ru\\olamedia\\kanon\\mongo\\collection' => 'collection.php',
'ru\\olamedia\\kanon\\mongo\\model' => 'model.php',
'ru\\olamedia\\kanon\\mongo\\result' => 'result.php',
'ru\\olamedia\\kanon\\mongo\\reference' => 'reference.php',
'ru\\olamedia\\kanon\\mongo\\referenceSet' => 'referenceSet.php',
'ru\\olamedia\\kanon\\mongo\\typeManager' => 'typeManager.php',
);
```


### Property types
* string
* int | integer
* float | double (both are double in mongo)
* bool | boolean
* timestamp
* datetime

### Model definition
```php
<?php
namespace my\namespace;

use ru\olamedia\kanon\mongo\model;

class article extends model{
  protected static $_properties = [
    'parent' => [
      'model'=>'my\\namespace\\article',
      'type'=>'reference'
    ],
		'name' => [
      'default'=>'',
      'type'=>'string'
    ]
	];
}
```

### Setting up connection
```php
use ru\olamedia\kanon\mongo;

mongo\connection::getInstance('default')
  ->connect([
  	'connection' => [
			'hostnames' => 'localhost',
			'database'  => 'dbname',
		]
	])
  ->registerModel(
    'my\\namespace\\article', // class name
    'articles' // collection name
  );
```


### Using models
```php
$article1 = new article();
$article1->name = 'New article';
$article1->save();
$article2 = new article(['name' => 'Hello world']);
$article2->parent = $article1; // reference
$article2->save();
var_dump($article1->getId());
echo $article1->getId(); // MongoDB uses _id field with \MongoId objects convertable toString().
var_dump($article1->name);
$article1->delete();
```

### Queries
```php
$result = article::find($criteria, $fields);
$result = article::findOne($criteria, $fields);
// $result is instance of ru\olamedia\kanon\mongo\result,
// which is a wrapper to \MongoCursor able to return model instances instead of arrays

// to get \MongoCursor back:
$cursor = $result->getCursor();
// creating result from \MongoCursor:
$result = new result('my\\namespace\\article', $cursor);

// using native methods:
$result->asc('name');
$result->desc('name');
$result->sort(['name'=>1]); // @see http://php.net/mongocursor.sort
$result->skip(3); // @see http://php.net/mongocursor.skip
$result->limit(10); // see http://php.net/mongocursor.limit

// using collections:
$collection = collection::forClass('my\\namespace\\article'); // \ru\olamedia\kanon\mongo\collection
$mongoCollection = $collection->select(); // \MongoCollection
$cursor = $mongoCollection->find($criteria, $fields); // \MongoCursor
$result = new result('my\\namespace\\article', $cursor); // \ru\olamedia\kanon\mongo\result
foreach ($result as $article){ // \my\namespace\article
  echo $article->name.'<br />';
}
```

### Result
Implements \Iterator, \Countable
```php
$articles = article::find();
echo count($articles);
foreach ($articles as $article){
}
```
