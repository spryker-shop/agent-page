<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerShopTest\Yves\AgentPage\Plugin\Provider;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\UserTransfer;
use SprykerShop\Yves\AgentPage\AgentPageFactory;
use SprykerShop\Yves\AgentPage\Dependency\Client\AgentPageToAgentClientInterface;
use SprykerShop\Yves\AgentPage\Plugin\Provider\AgentUserProvider;
use SprykerShop\Yves\AgentPage\Security\Agent;
use SprykerShop\Yves\CustomerPage\Security\Customer;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

/**
 * @group SprykerShop
 * @group Yves
 * @group AgentPage
 * @group Plugin
 * @group Provider
 * @group AgentUserProviderTest
 */
class AgentUserProviderTest extends Unit
{
    /**
     * @uses \Orm\Zed\User\Persistence\Map\SpyUserTableMap::COL_STATUS_ACTIVE
     *
     * @var string
     */
    protected const STATUS_ACTIVE = 'active';

    public function testRefreshUserThrowsUserNotFoundExceptionWhenAgentIsNoLongerActive(): void
    {
        // Arrange
        $agentClientMock = $this->createMock(AgentPageToAgentClientInterface::class);
        $agentClientMock->method('findAgentByUsername')->willReturn(null);
        $agentClientMock->expects($this->once())->method('invalidateAgentSession');
        $agentClientMock->expects($this->once())->method('finishImpersonationSession');

        $agentPageFactoryMock = $this->createMock(AgentPageFactory::class);
        $agentPageFactoryMock->method('getAgentClient')->willReturn($agentClientMock);

        $agentUserProvider = $this->getMockBuilder(AgentUserProvider::class)
            ->onlyMethods(['getFactory'])
            ->getMock();
        $agentUserProvider->method('getFactory')->willReturn($agentPageFactoryMock);

        $agent = new Agent((new UserTransfer())->setUsername('agent123@spryker.com'));

        // Assert
        $this->expectException(UserNotFoundException::class);

        // Act
        $agentUserProvider->refreshUser($agent);
    }

    public function testRefreshUserReturnsRefreshedAgentWhenStillActive(): void
    {
        // Arrange
        $refreshedUserTransfer = (new UserTransfer())
            ->setUsername('agent123@spryker.com')
            ->setStatus(static::STATUS_ACTIVE);

        $agentClientMock = $this->createMock(AgentPageToAgentClientInterface::class);
        $agentClientMock->method('findAgentByUsername')->willReturn($refreshedUserTransfer);
        $agentClientMock->expects($this->never())->method('invalidateAgentSession');
        $agentClientMock->expects($this->never())->method('finishImpersonationSession');

        $refreshedAgent = new Agent($refreshedUserTransfer);

        $agentPageFactoryMock = $this->createMock(AgentPageFactory::class);
        $agentPageFactoryMock->method('getAgentClient')->willReturn($agentClientMock);
        $agentPageFactoryMock->method('createSecurityUser')->willReturn($refreshedAgent);

        $agentUserProvider = $this->getMockBuilder(AgentUserProvider::class)
            ->onlyMethods(['getFactory'])
            ->getMock();
        $agentUserProvider->method('getFactory')->willReturn($agentPageFactoryMock);

        $agent = new Agent((new UserTransfer())->setUsername('agent123@spryker.com'));

        // Act
        $result = $agentUserProvider->refreshUser($agent);

        // Assert
        $this->assertSame($refreshedAgent, $result);
    }

    public function testRefreshUserReturnsNonAgentUserUnchanged(): void
    {
        // Arrange
        $agentUserProvider = $this->getMockBuilder(AgentUserProvider::class)
            ->onlyMethods(['getFactory'])
            ->getMock();
        $agentUserProvider->expects($this->never())->method('getFactory');

        $customer = new Customer(new CustomerTransfer(), 'test', 'test');

        // Act
        $result = $agentUserProvider->refreshUser($customer);

        // Assert
        $this->assertSame($customer, $result);
    }
}
