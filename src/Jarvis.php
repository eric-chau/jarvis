<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
final class Jarvis implements \ArrayAccess
{
    private static $jarvis;
    private $container;
    private $starttime;

    /**
     * @return self
     */
    public static function helloJarvis()
    {
        if (null === self::$jarvis) {
            $jarvis = new static();
            $jarvis['routing'] = new RouteCollection();
            $jarvis['routing']->add('main_route', new Route('/', []));

            self::$jarvis = $jarvis;
        }

        return self::$jarvis;
    }

    /**
     * to delete
     *
     * @return Response
     */
    public function indexAction()
    {
        return new Response($this->executionDuration().' ms');
    }

    /**
     * Analyzes current request and return result.
     *
     * @return mixed
     */
    public function analyze()
    {
        $this['request'] = Request::createFromGlobals();
        $context = new RequestContext();
        $context->fromRequest($this['request']);
        $matcher = new UrlMatcher($this['routing'], $context);
        $parameters = $matcher->match($this['request']->getPathInfo());

        $callback = [$this, 'indexAction'];

        return call_user_func_array($callback, []);
    }

    public function executionDuration($precision = 8)
    {
        return number_format(microtime(true) - $this->starttime, $precision);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($key)
    {
        return isset($this->container[$key]) ?: array_key_exists($key, $this->container);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($key)
    {
        if (!$this->offsetExists($key)) {
            throw new \InvalidArgumentException(sprintf('"$key" is not a valid key.', $key));
        }

        return $this->container[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($key, $value)
    {
        $this->container[$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($key)
    {
        unset($this->container[$key]);
    }

    private function __construct()
    {
        $this->starttime = microtime(true);
        $this->container = [];
    }
}
