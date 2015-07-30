<?php

use Jarvis\Ability\ScopeManager;
use Jarvis\Jarvis;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class ScopeManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultBehavior()
    {
        $scopeManager = new ScopeManager();

        $this->assertCount(1, $scopeManager->getAll());
        $this->assertTrue($scopeManager->isEnabled(Jarvis::JARVIS_DEFAULT_SCOPE));

        $scopeManager->disable(Jarvis::JARVIS_DEFAULT_SCOPE);
        $this->assertTrue($scopeManager->isEnabled(Jarvis::JARVIS_DEFAULT_SCOPE));
    }

    public function testEnableAndDisableScope()
    {
        $scopeManager = new ScopeManager();

        $scope = 'foobar';
        $this->assertFalse($scopeManager->isEnabled($scope));

        $scopeManager->enable($scope);
        $this->assertTrue($scopeManager->isEnabled($scope));
        $this->assertCount(2, $scopeManager->getAll());
        $this->assertTrue(in_array($scope, $scopeManager->getAll()));
    }
}
