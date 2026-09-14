<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerShop\Yves\AgentPage\Plugin\Log;

use Spryker\Shared\Log\Dependency\Plugin\LogProcessorPluginInterface;
use Spryker\Yves\Kernel\AbstractPlugin;

/**
 * @method \Spryker\Client\Agent\AgentClientInterface getClient()
 * @method \SprykerShop\Yves\AgentPage\AgentPageFactory getFactory()
 * @method \SprykerShop\Yves\AgentPage\AgentPageFactory getConfig()
 */
class AgentCurrentRequestProcessorPlugin extends AbstractPlugin implements LogProcessorPluginInterface
{
    /**
     * {@inheritDoc}
     * - Adds agent related data from the current request.
     *
     * @api
     *
     * @param \Monolog\LogRecord|array<string, mixed> $data
     *
     * @return \Monolog\LogRecord|array<string, mixed>
     */
    public function __invoke($data)
    {
        return $this->getFactory()->createCurrentRequestProcessor()->__invoke($data);
    }
}
