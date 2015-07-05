<?php

namespace Jarvis\Component;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class Routing extends RouteCollection
{
    const DO_NOTHING_ON_INVALID_PATH = 0;
    const THROWS_EXCEPTION_ON_INVALID_PATH = 1;

    /**
     * [__construct description]
     *
     * @param [type] $paths         [description]
     * @param [type] $onInvalidPath [description]
     */
    public function __construct($paths = null, $onInvalidPath = self::THROWS_EXCEPTION_ON_INVALID_PATH)
    {
        foreach ((array) $paths as $filepath) {
            if (!is_file($filepath) || !is_readable($filepath)) {
                if (self::THROWS_EXCEPTION_ON_INVALID_PATH === $onInvalidPath) {
                    throw new \InvalidArgumentException(sprintf(
                        '[%s] invalid or not readable filepath "%s".',
                        __METHOD__,
                        $filepath
                    ));
                } else {
                    continue;
                }
            }

            foreach (Yaml::parse(file_get_contents($filepath)) as $routeName => $data) {
                try {
                    $routeInfos = $this->translateJarvisRoute($data);
                } catch (\Exception $e) {
                    throw $e;
                }

                $this->add($routeName, new Route(
                    $routeInfos['path'],
                    $routeInfos['defaults'],
                    $routeInfos['requirements'],
                    $routeInfos['options'],
                    $routeInfos['host'],
                    $routeInfos['schemes'],
                    $routeInfos['methods'],
                    $routeInfos['conditions']
                ));
            }
        }
    }

    /**
     * [translateJarvisRoute description]
     *
     * @param  [type] $data [description]
     * @return [type]          [description]
     */
    private function translateJarvisRoute($data)
    {
        $routeInfos = [
            'defaults' => [],
            'requirements' => isset($data['requirements']) ? $data['requirements'] : [],
            'options' => [],
            'host' => '',
            'schemes' => [],
            'methods' => [],
            'conditions' => '',
        ];

        if (!isset($data['pattern'])) {
            throw new \InvalidArgumentException(sprintf('%s: "%s" is missing from route declaration', __METHOD__, $pattern));
        }

        $routeInfos['path'] = $data['pattern'];

        if (!isset($data['controller'])) {
            throw new \InvalidArgumentException(sprintf('%s: "%s" is missing from route declaration', __METHOD__, $controller));
        }

        $routeInfos['defaults']['_controller'] = $data['controller'];

        if (!isset($data['action'])) {
            throw new \InvalidArgumentException(sprintf('%s: "%s" is missing from route declaration', __METHOD__, $action));
        }

        $routeInfos['defaults']['_action'] = $data['action'];

        if (isset($data['methods'])) {
            $routeInfos['methods'] = (array) $data['methods'];
        }

        return $routeInfos;
    }
}
