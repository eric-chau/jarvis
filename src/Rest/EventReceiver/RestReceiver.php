<?php

namespace Jarvis\Rest\EventReceiver;

use Jarvis\Event\AnalyzeEvent;
use Jarvis\Jarvis;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class RestReceiver
{
    const REST_SCOPE_NAME = 'rest';

    private $jarvis;
    private $restBaseUrls;

    public function __construct(Jarvis $jarvis)
    {
        $config = isset($jarvis['jarvis.settings']['rest']) ? $jarvis['jarvis.settings']['rest'] : [];

        $this->restBaseUrls = isset($config['base_urls']) ? (array) $config['base_urls'] : [];
        $this->jarvis = $jarvis;
    }

    public function onAnalyzeEvent(AnalyzeEvent $event)
    {
        foreach ($this->restBaseUrls as $baseUrl) {
            if (0 === strpos($event->getRequest()->getPathInfo(), $baseUrl)) {
                $this->jarvis->enableScope(self::REST_SCOPE_NAME);

                return;
            }
        }
    }
}
