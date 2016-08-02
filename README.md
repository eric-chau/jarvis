# Jarvis, a PHP 7.1 micro framework

[![Code Climate](https://codeclimate.com/github/eric-chau/jarvis/badges/gpa.svg)](https://codeclimate.com/github/eric-chau/jarvis) [![Test Coverage](https://codeclimate.com/github/eric-chau/jarvis/badges/coverage.svg)](https://codeclimate.com/github/eric-chau/jarvis/coverage) [![Build Status](https://travis-ci.org/eric-chau/jarvis.svg?branch=master)](https://travis-ci.org/eric-chau/jarvis) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/be0c72c7-14f3-4cf2-85cd-a072091e7118/mini.png)](https://insight.sensiolabs.com/projects/be0c72c7-14f3-4cf2-85cd-a072091e7118) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/eric-chau/jarvis/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/eric-chau/jarvis/?branch=master)

Jarvis is a PHP 7.1 micro framework. It is designed to be simple and lightweight.

Note that if you want to use Jarvis with PHP 5.6, please switch on `0.1` branch or use the `v0.1.*` tag.

Jarvis requires `php >= 7.1`. it's based on its own dependency injection container, http foundation component from Symfony and nikic's fast route.

# Usage

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$jarvis = new Jarvis\Jarvis();

$jarvis->router
    ->beginRoute('default')
        ->setMethod('get')
        ->setPattern('/')
        ->setHandler(function () {
            return 'Hello world!';
        })
    ->end()
;

$response = $jarvis->analyze();

$response->send();
```

# How Jarvis process incoming request

The schema below will sum up how  `Jarvis\Jarvis::analyze()` treats any incoming request:

```
INPUT: an instance of Request

|
|__ Step 1: broadcast AnalyzeEvent.
|
|__ Step 2: check if AnalyzeEvent has a response?
|_______
| NO    | YES
|       |
|       |_ RETURN Response
|
|__ Step 3: resolve URI
|
|__ Step 4: does request match any route?
|_______
| NO*   | YES
|       |
|       |_ Step 4a: broadcast ControllerEvent
|       |
|       |_ Step 4b: invoke callback to process the request
|       |
|<------
|
|_ Step 5: broadcast ResponseEvent
|
|_ RETURN Response

OUT: an instance of Response
```

*: note that if provided URI does not match any route `analyze()` will return an instance of Response with 404 or 406 status code.

# Router skill

Jarvis' Router can handle anonymous and named routes. By default, a route is type of HTTP `GET` method and the pattern setted to `/`. You can find some example below:

### Anonymous route, `GET` HTTP method, `/` as pattern
```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$jarvis = new Jarvis\Jarvis();

$jarvis->router
    ->beginRoute()
        ->setHandler(function () {
            return 'foobar!';
        })
    ->end()
;
```

### Named route, name: `user_edit` , http method: `PUT`, pattern: `/user/{id}`

Note that id must be a number. Let's see how to do so.
```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$jarvis = new Jarvis\Jarvis();

$jarvis->router
    ->beginRoute('user_edit')
        ->setMethod('PUT')
        ->setPattern('/user/{id:\d+}')
        ->setHandler(function ($id) {
            // Do some stuff

            return "User $id informations are now up-to-date!";
        })
    ->end()
;

echo $jarvis->router->uri('user_edit', ['id' => 123]); // print '/user/123'
```

# Dependency injection container skill

## `Container::alias()`

Jarvis' DIC (dependency injection container) can deal with alias:

```php
<?php

$jarvis = new Jarvis\Jarvis();

$jarvis['foo'] = 'hello world';
$jarvis->alias('bar', 'foo');

$jarvis['foo'] === $jarvis['bar']; // = true
```

## `Container::find()`

`::find()` is an another useful method provided by Jarvis' DIC:

```php
<?php

$jarvis = new Jarvis\Jarvis();

$jarvis['dicaprio_movie_1997'] = 'Titanic';
$jarvis['dicaprio_movie_2010'] = 'Inception';
$jarvis['dicaprio_movie_2014'] = 'The Wolf of Wall Street';

$jarvis->find('dicaprio_movie_*'); // = ['Titanic', 'Inception', 'The Wolf of Wall Street']
$jarvis->find('dicaprio_movie_19*'); // = ['Titanic']
$jarvis->find('dicaprio_movie_2015'); // = []
```
