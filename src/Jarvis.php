<?php

namespace Jarvis;

use FastRoute\Dispatcher;
use Jarvis\Component\ControllerResolver;
use Jarvis\ContainerProviderInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Jarvis. Minimalist dependency injection container.
 *
 * @author Eric Chau <eriic.chau@gmail.com>
 */
final class Jarvis extends Container
{
    const JARVIS_CONTAINER_PROVIDER_FQCN = 'Jarvis\ContainerProvider';

    private $aliasOf = [];
    private $factories;
    private $hasAliases = [];
    private $locked = [];
    private $raw = [];
    private $values = [];

    /**
     * Creates an instance of Jarvis. It can take settings as first argument.
     * List of accepted options:
     *   - jarvis.container_provider (type: string|array): fqcn of your container provider
     *
     * @param  array $settings Your own settings to modify Jarvis behavior
     * @throws \InvalidArgumentException if provided container provider class doesn't
     *                                   implement ContainerProviderInterface
     */
    public function __construct(array $settings = [])
    {
        parent::__construct();

        $this['jarvis.starttime'] = microtime(true);

        if (!isset($settings['jarvis.container_provider'])) {
            $settings['jarvis.container_provider'] = [self::JARVIS_CONTAINER_PROVIDER_FQCN];
        } else {
            $settings = (array) $settings;
            array_unshift($settings['jarvis.container_provider'], self::JARVIS_CONTAINER_PROVIDER_FQCN);
        }

        $this['jarvis.settings'] = $settings;
        foreach ($settings['jarvis.container_provider'] as $classname) {
            if (!is_subclass_of($classname, ContainerProviderInterface::class)) {
                throw new \InvalidArgumentException(sprintf(
                    'Expect every container provider to implement %s.',
                    ContainerProviderInterface::class
                ));
            }

            $classname::hydrate($this);
        }
    }

    public function analyze()
    {
        $response = null;
        $routeInfo = $this['url_matcher']->dispatch($this['request']->getMethod(), $this['request']->getPathInfo());
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                $response = new Response(null, Response::HTTP_NOT_FOUND);

                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $response = new Response(null, Response::HTTP_METHOD_NOT_ALLOWED);

                break;
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                if (is_array($handler) && is_string($handler[0]) && '@' === $handler[0][0]) {
                    $identifier = substr($handler[0], 1);
                    if (!isset($this[$identifier])) {
                        throw new \Exception('invalid service identifier provided: '.$identifier);
                    }

                    $handler[0] = $this[$identifier];
                }

                $response = call_user_func_array($handler, $routeInfo[2]);

                break;
            default:
                break;
        }

        return $response;
    }

    public function broadcast($event)
    {

    }

    public function getExecutionDuration($precision = 8)
    {
        return number_format(microtime(true) - $this['jarvis.starttime'], $precision);
    }
}
