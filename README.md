# Guzzle Bundle [![Build Status](https://travis-ci.org/campru/guzzle-bundle.svg?branch=master)](https://travis-ci.org/campru/guzzle-bundle) [![Latest Stable Version](https://poser.pugx.org/campru/guzzle-bundle/v/stable)](https://packagist.org/packages/campru/guzzle-bundle)

Provide an advanced profiler for Guzzle. This profiler is for debug purposes and will display a dedicated report available in the toolbar and Silex Web Profiler

<img src="http://ludofleury.github.io/GuzzleBundle/images/guzzle-profiler-panel.png" width="280" height="175" alt="Guzzle Symfony web profiler panel"/>
<img src="http://ludofleury.github.io/GuzzleBundle/images/guzzle-request-detail.png" width="280" height="175" alt="Guzzle Symfony web profiler panel - request details"/>
<img src="http://ludofleury.github.io/GuzzleBundle/images/guzzle-response-detail.png" width="280" height="175" alt="Guzzle Symfony web profiler panel - response details"/>

## Installation

Add the composer requirements
```javascript
{
    "require": {
        "campru/guzzle-bundle": "1.0.0"
    },
}
```

Enable it in your application
```php
use Campru\GuzzleBundle\Provider\GuzzleProfilerServiceProvider;

$app->register(new GuzzleProfilerServiceProvider());
```

The provider depends on ``WebProfilerServiceProvider``, so you also need to enable this if that's not already the case
```php
use Silex\Provider\WebProfilerServiceProvider;

$app->register(new Provider\WebProfilerServiceProvider())
```

Finally, it's needed to add two subscribers to Guzzle client when this is created
```php
use GuzzleHttp\Client;

$client = new Client(['base_url' => 'http://my.api.com']);

$client->getEmitter()->attach($app['guzzle_bundle.subscriber.profiler']);
$client->getEmitter()->attach($app['guzzle_bundle.subscriber.storage']);
```

## Licence

This bundle is under the MIT license. See the complete license in the bundle