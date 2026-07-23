<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerShopTest\Yves\AgentPage\Controller;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\UserTransfer;
use SprykerShop\Yves\AgentPage\AgentPageFactory;
use SprykerShop\Yves\AgentPage\Controller\AgentController;
use SprykerShop\Yves\AgentPage\Dependency\Client\AgentPageToAgentClientInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerShopTest
 * @group Yves
 * @group AgentPage
 * @group Controller
 * @group AgentControllerTest
 * Add your own group annotations below this line
 */
class AgentControllerTest extends Unit
{
    protected const string KEY_LOGIN_REDIRECT_URL = 'loginRedirectUrl';

    protected const string TEMPLATE_REDIRECT_TO_LOGIN = '@AgentPage/views/login/redirect-to-login.twig';

    public function testIndexActionProvidesLoginRedirectUrlToRedirectTemplateWhenAgentIsNotLoggedIn(): void
    {
        // Arrange
        $agentController = $this->createAgentController($this->createAgentClientMock(false));

        // Act
        $view = $agentController->indexAction();

        // Assert
        $this->assertSame(static::TEMPLATE_REDIRECT_TO_LOGIN, $view->getTemplate());
        $this->assertSame('/agent/login', $view->getData()[static::KEY_LOGIN_REDIRECT_URL] ?? null);
    }

    public function testIndexActionRendersOverviewWhenAgentIsLoggedIn(): void
    {
        // Arrange
        $agentController = $this->createAgentController($this->createAgentClientMock(true));

        // Act
        $view = $agentController->indexAction();

        // Assert
        $this->assertSame('@AgentPage/views/overview/overview.twig', $view->getTemplate());
        $this->assertNotNull($view->getData()['agent'] ?? null);
    }

    protected function createAgentController(AgentPageToAgentClientInterface $agentClientMock): AgentController
    {
        $agentPageFactoryMock = $this->createMock(AgentPageFactory::class);
        $agentPageFactoryMock->method('getAgentClient')->willReturn($agentClientMock);

        return new class ($agentPageFactoryMock) extends AgentController
        {
            public function __construct(protected AgentPageFactory $agentPageFactoryMock)
            {
            }

            protected function getFactory(): AgentPageFactory
            {
                return $this->agentPageFactoryMock;
            }
        };
    }

    protected function createAgentClientMock(bool $isLoggedIn): AgentPageToAgentClientInterface
    {
        $agentClientMock = $this->createMock(AgentPageToAgentClientInterface::class);
        $agentClientMock->method('isLoggedIn')->willReturn($isLoggedIn);
        $agentClientMock->method('getAgent')->willReturn((new UserTransfer())->setUsername('agent@spryker.com'));

        return $agentClientMock;
    }
}
