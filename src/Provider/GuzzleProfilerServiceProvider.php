<?php

namespace Campru\GuzzleBundle\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Web Profiler provider for guzzle.
 *
 * @author David Camprubí <david.camprubi@gmail.com>
 */
class GuzzleProfilerServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers services on the given app.
     *
     * @param Application $app Silex application.
     */
    public function register(Application $app)
    {
        if (!isset($app['data_collector.templates'])) {
            throw new \LogicException(
                'The provider: "'.__CLASS__.'" must be registered after the "WebProfilerServiceProvider"'
            );
        }

        $dataCollectorTpls   = $app->raw('data_collector.templates');
        $dataCollectorTpls[] = ['guzzle', '@CampruGuzzle/views/Collector/guzzle.html.twig'];
        $app['data_collector.templates'] = $dataCollectorTpls;

        $app['guzzle_bundle.subscriber.profiler'] = $app->share(function () {
            return new \GuzzleHttp\Subscriber\History;
        });

        $app['guzzle_bundle.subscriber.storage'] = $app->share(function () {
            return new \Campru\GuzzleBundle\Subscriber\Stopwatch(new \SplObjectStorage);
        });

        $app['data_collectors'] = $app->share($app->extend('data_collectors', function ($collectors, $app) {
            $collectors['guzzle'] = function ($app) {
                return new \Campru\GuzzleBundle\DataCollector\GuzzleDataCollector(
                    $app['guzzle_bundle.subscriber.profiler'],
                    $app['guzzle_bundle.subscriber.storage']
                );
            };

            return $collectors;
        }));

        $app['guzzle_bundle.profiler.templates_path'] = function () {
            $class = new \ReflectionClass('Campru\GuzzleBundle\DataCollector\GuzzleDataCollector');

            return dirname(dirname($class->getFileName())).'/../resources';
        };

        $app['twig.loader.filesystem'] = $app->share($app->extend('twig.loader.filesystem', function ($loader, $app) {
            $loader->addPath($app['guzzle_bundle.profiler.templates_path'], 'CampruGuzzle');

            return $loader;
        }));
    }

    /**
     * Bootstraps the application.
     *
     * @param Application $app Silex application.
     */
    public function boot(Application $app)
    {
    }
}
