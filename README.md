# Jarvis - PHP micro framework

[![Code Climate](https://codeclimate.com/github/eric-chau/jarvis/badges/gpa.svg)](https://codeclimate.com/github/eric-chau/jarvis) [![Test Coverage](https://codeclimate.com/github/eric-chau/jarvis/badges/coverage.svg)](https://codeclimate.com/github/eric-chau/jarvis/coverage) [![Build Status](https://travis-ci.org/eric-chau/jarvis.svg?branch=master)](https://travis-ci.org/eric-chau/jarvis) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/be0c72c7-14f3-4cf2-85cd-a072091e7118/mini.png)](https://insight.sensiolabs.com/projects/be0c72c7-14f3-4cf2-85cd-a072091e7118) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/eric-chau/jarvis/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/eric-chau/jarvis/?branch=master)

## "Hello world!" with Jarvis

```php
<?php

require_once __DIR__.'/vendor/autoload.php';

$jarvis = new Jarvis\Jarvis();

$jarvis->router->addRoute('get', '/', function () {
    return 'Hello world!';
});

$response = $jarvis->analyze();

$response->send();

```
