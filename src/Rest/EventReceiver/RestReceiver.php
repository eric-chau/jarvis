<?php

namespace Jarvis\Rest\EventReceiver;

use Jarvis\Ability\ScopeManager;
use Jarvis\Event\AnalyzeEvent;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class RestReceiver
{
    const REST_SCOPE_NAME = 'rest';

    private $scopeManager;
    private $restBaseUrls;

    public function __construct(ScopeManager $scopeManager, array $restConfig = [])
    {
        $this->scopeManager = $scopeManager;
        $this->restBaseUrls = isset($restConfig['base_urls']) ? (array) $restConfig['base_urls'] : [];
    }

    public function onAnalyzeEvent(AnalyzeEvent $event)
    {
        foreach ($this->restBaseUrls as $baseUrl) {
            if (0 === strpos($event->getRequest()->getPathInfo(), $baseUrl)) {
                $this->scopeManager->enable(self::REST_SCOPE_NAME);

                return;
            }
        }

    }
}
