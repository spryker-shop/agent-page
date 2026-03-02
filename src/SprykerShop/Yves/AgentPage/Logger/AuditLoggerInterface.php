<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerShop\Yves\AgentPage\Logger;

interface AuditLoggerInterface
{
    public function addAgentFailedLoginAuditLog(): void;

    public function addAgentSuccessfulLoginAuditLog(): void;

    public function addImpersonationStartedAuditLog(): void;

    public function addImpersonationEndedAuditLog(): void;
}
