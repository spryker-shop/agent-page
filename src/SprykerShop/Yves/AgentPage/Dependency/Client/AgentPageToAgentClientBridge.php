<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerShop\Yves\AgentPage\Dependency\Client;

use Generated\Shared\Transfer\UserTransfer;

class AgentPageToAgentClientBridge implements AgentPageToAgentClientInterface
{
    /**
     * @var \Spryker\Client\Agent\AgentClientInterface
     */
    protected $agentClient;

    /**
     * @param \Spryker\Client\Agent\AgentClientInterface $agentClient
     */
    public function __construct($agentClient)
    {
        $this->agentClient = $agentClient;
    }

    public function findAgentByUsername(UserTransfer $userTransfer): ?UserTransfer
    {
        return $this->agentClient->findAgentByUsername($userTransfer);
    }

    public function isLoggedIn(): bool
    {
        return $this->agentClient->isLoggedIn();
    }

    public function getAgent(): UserTransfer
    {
        return $this->agentClient->getAgent();
    }

    public function setAgent(UserTransfer $userTransfer): void
    {
        $this->agentClient->setAgent($userTransfer);
    }

    public function invalidateAgentSession(): void
    {
        $this->agentClient->invalidateAgentSession();
    }

    public function finishImpersonationSession(): void
    {
        $this->agentClient->finishImpersonationSession();
    }
}
