<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerShop\Yves\AgentPage\Dependency\Client;

use Generated\Shared\Transfer\UserTransfer;

interface AgentPageToAgentClientInterface
{
    public function findAgentByUsername(UserTransfer $userTransfer): ?UserTransfer;

    public function isLoggedIn(): bool;

    public function getAgent(): UserTransfer;

    public function setAgent(UserTransfer $userTransfer): void;

    public function invalidateAgentSession(): void;

    public function finishImpersonationSession(): void;
}
