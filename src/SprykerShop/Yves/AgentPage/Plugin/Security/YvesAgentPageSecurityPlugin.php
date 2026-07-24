<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerShop\Yves\AgentPage\Plugin\Security;

use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\SecurityExtension\Configuration\SecurityBuilderInterface;
use Spryker\Shared\SecurityExtension\Dependency\Plugin\SecurityPluginInterface;
use Spryker\Yves\Kernel\AbstractPlugin;

/**
 * @method \SprykerShop\Yves\AgentPage\AgentPageFactory getFactory()
 * @method \SprykerShop\Yves\AgentPage\AgentPageConfig getConfig()
 */
class YvesAgentPageSecurityPlugin extends AbstractPlugin implements SecurityPluginInterface
{
    /**
     * {@inheritDoc}
     * - Adds a firewall for the AgentPages.
     * - Adds a context and switch_user to the existing CustomerPage firewall configuration.
     * - Adds an authenticator for the AgentPages firewall configuration.
     * - Executes {@link \SprykerShop\Yves\AgentPageExtension\Dependency\Plugin\SessionPostImpersonationPluginInterface} plugin stack.
     *
     * @api
     */
    public function extend(SecurityBuilderInterface $securityBuilder, ContainerInterface $container): SecurityBuilderInterface
    {
        return $this->getFactory()->createSecurityBuilderExpander()->extend($securityBuilder, $container);
    }
}
