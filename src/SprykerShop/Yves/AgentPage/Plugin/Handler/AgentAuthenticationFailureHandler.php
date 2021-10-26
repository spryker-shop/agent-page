<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerShop\Yves\AgentPage\Plugin\Handler;

use Spryker\Yves\Kernel\AbstractPlugin;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

/**
 * @method \SprykerShop\Yves\AgentPage\AgentPageFactory getFactory()
 */
class AgentAuthenticationFailureHandler extends AbstractPlugin implements AuthenticationFailureHandlerInterface
{
    /**
     * @var string
     */
    protected const MESSAGE_AGENT_AUTHENTICATION_FAILED = 'agent.authentication.failed';

    /**
     * @uses \SprykerShop\Yves\AgentPage\Plugin\Router\AgentPageRouteProviderPlugin::ROUTE_LOGIN
     *
     * @var string
     */
    protected const ROUTE_LOGIN = 'agent/login';

    /**
     * @var string|null
     */
    protected $targetUrl;

    /**
     * @param string|null $targetUrl
     */
    public function __construct(?string $targetUrl = null)
    {
        $this->targetUrl = $targetUrl;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\Security\Core\Exception\AuthenticationException $exception
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $this->getFactory()
            ->getMessengerClient()
            ->addErrorMessage(static::MESSAGE_AGENT_AUTHENTICATION_FAILED);

        return new RedirectResponse($this->targetUrl ?? $this->getRedirectUrl());
    }

    /**
     * @return string
     */
    protected function getRedirectUrl(): string
    {
        return $this->getFactory()
            ->getRouter()
            ->generate(static::ROUTE_LOGIN);
    }
}
