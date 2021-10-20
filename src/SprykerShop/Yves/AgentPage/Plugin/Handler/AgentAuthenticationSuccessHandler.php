<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerShop\Yves\AgentPage\Plugin\Handler;

use Generated\Shared\Transfer\UserTransfer;
use Spryker\Yves\Kernel\AbstractPlugin;
use SprykerShop\Yves\AgentPage\Security\Agent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

/**
 * @method \SprykerShop\Yves\AgentPage\AgentPageFactory getFactory()
 */
class AgentAuthenticationSuccessHandler extends AbstractPlugin implements AuthenticationSuccessHandlerInterface
{
    /**
     * @uses \SprykerShop\Yves\AgentPage\Plugin\Router\AgentPageRouteProviderPlugin::ROUTE_AGENT_OVERVIEW
     * @var string
     */
    protected const ROUTE_AGENT_OVERVIEW = 'agent/overview';

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
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $this->setAgentSession(
            $this->getSecurityUser($token)->getUserTransfer(),
        );

        return $this->createRedirectResponse();
    }

    /**
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     *
     * @return \SprykerShop\Yves\AgentPage\Security\Agent
     */
    protected function getSecurityUser(TokenInterface $token): Agent
    {
        /** @var \SprykerShop\Yves\AgentPage\Security\Agent $user */
        $user = $token->getUser();

        return $user;
    }

    /**
     * @param \Generated\Shared\Transfer\UserTransfer $userTransfer
     *
     * @return void
     */
    protected function setAgentSession(UserTransfer $userTransfer)
    {
        $this->getFactory()
            ->getAgentClient()
            ->setAgent($userTransfer);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function createRedirectResponse()
    {
        if ($this->targetUrl) {
            return new RedirectResponse($this->targetUrl);
        }

        $targetUrl = $this->getFactory()
            ->getRouter()
            ->generate(static::ROUTE_AGENT_OVERVIEW);

        return new RedirectResponse($targetUrl);
    }
}
