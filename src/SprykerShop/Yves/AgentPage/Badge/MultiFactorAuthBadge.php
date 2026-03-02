<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerShop\Yves\AgentPage\Badge;

use Generated\Shared\Transfer\MultiFactorAuthTransfer;
use Generated\Shared\Transfer\MultiFactorAuthValidationRequestTransfer;
use Generated\Shared\Transfer\UserTransfer;
use SprykerShop\Yves\AgentPageExtension\Dependency\Plugin\AuthenticationCodeInvalidatorPluginInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

class MultiFactorAuthBadge implements BadgeInterface
{
    /**
     * @uses \Spryker\Yves\MultiFactorAuth\Plugin\AuthenticationHandler\Agent\AgentUserMultiFactorAuthenticationHandlerPlugin::AGENT_USER_MULTI_FACTOR_AUTHENTICATION_HANDLER_NAME
     *
     * @var string
     */
    protected const AGENT_USER_MULTI_FACTOR_AUTHENTICATION_HANDLER_NAME = 'AGENT_USER_MULTI_FACTOR_AUTHENTICATION';

    /**
     * @var string
     */
    protected const PARAMETER_MULTI_FACTOR_AUTH_ENABLED = 'multi_factor_auth_enabled';

    /**
     * @uses \SprykerShop\Yves\AgentPage\Form\AgentLoginForm::FORM_NAME
     *
     * @var string
     */
    protected const PARAMETER_LOGIN_FORM = 'loginForm';

    /**
     * @var bool
     */
    protected bool $isRequired = false;

    /**
     * @var bool
     */
    protected bool $isResolved = true;

    /**
     * @var int|null
     */
    protected ?int $status = null;

    /**
     * @param array<\SprykerShop\Yves\AgentPageExtension\Dependency\Plugin\AuthenticationHandlerPluginInterface> $agentUserMultiFactorAuthenticationHandlerPlugins
     */
    public function __construct(
        protected array $agentUserMultiFactorAuthenticationHandlerPlugins
    ) {
    }

    public function isResolved(): bool
    {
        return $this->isResolved;
    }

    public function setIsResolved(bool $isResolved): void
    {
        $this->isResolved = $isResolved;
    }

    public function setIsRequired(bool $isRequired): void
    {
        $this->isRequired = $isRequired;
    }

    public function getIsRequired(): bool
    {
        return $this->isRequired;
    }

    public function setStatus(?int $status): void
    {
        $this->status = $status;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    /**
     * @param \Generated\Shared\Transfer\UserTransfer $userTransfer
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return $this
     */
    public function enable(UserTransfer $userTransfer, Request $request)
    {
        foreach ($this->agentUserMultiFactorAuthenticationHandlerPlugins as $plugin) {
            if ($plugin->isApplicable(static::AGENT_USER_MULTI_FACTOR_AUTHENTICATION_HANDLER_NAME) === false) {
                continue;
            }

            $multiFactorAuthValidationRequestTransfer = (new MultiFactorAuthValidationRequestTransfer())
                ->setUser($userTransfer)
                ->setIsLogin(true);
            $multiFactorAuthValidationResponseTransfer = $plugin->validateAgentMultiFactorStatus($multiFactorAuthValidationRequestTransfer);

            if ($multiFactorAuthValidationResponseTransfer->getIsRequired() === true) {
                if ($plugin instanceof AuthenticationCodeInvalidatorPluginInterface) {
                    $multiFactorAuthTransfer = (new MultiFactorAuthTransfer())->setUser($userTransfer);
                    $plugin->invalidateAgentCodes($multiFactorAuthTransfer);
                }

                if ($this->isRequestCorrupted($request)) {
                    $this->setIsResolved(false);

                    return $this;
                }
            }

            $this->setIsRequired($multiFactorAuthValidationResponseTransfer->getIsRequiredOrFail());
            $this->setStatus($multiFactorAuthValidationResponseTransfer->getStatus());
        }

        return $this;
    }

    protected function isRequestCorrupted(Request $request): bool
    {
        return !isset($request->request->all(static::PARAMETER_LOGIN_FORM)[static::PARAMETER_MULTI_FACTOR_AUTH_ENABLED]);
    }
}
