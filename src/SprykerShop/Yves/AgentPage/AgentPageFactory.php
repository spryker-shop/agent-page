<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerShop\Yves\AgentPage;

use Generated\Shared\Transfer\UserTransfer;
use Spryker\Shared\Application\ApplicationConstants;
use Spryker\Yves\Kernel\AbstractFactory;
use Spryker\Yves\Kernel\Application;
use Spryker\Yves\Router\Router\RouterInterface;
use SprykerShop\Yves\AgentPage\Authenticator\AgentLoginFormAuthenticator;
use SprykerShop\Yves\AgentPage\Badge\MultiFactorAuthBadge;
use SprykerShop\Yves\AgentPage\Builder\AgentSecurityOptionsBuilder;
use SprykerShop\Yves\AgentPage\Builder\AgentSecurityOptionsBuilderInterface;
use SprykerShop\Yves\AgentPage\Dependency\Client\AgentPageToAgentClientInterface;
use SprykerShop\Yves\AgentPage\Dependency\Client\AgentPageToCustomerClientInterface;
use SprykerShop\Yves\AgentPage\Dependency\Client\AgentPageToMessengerClientInterface;
use SprykerShop\Yves\AgentPage\Dependency\Client\AgentPageToQuoteClientInterface;
use SprykerShop\Yves\AgentPage\Dependency\Client\AgentPageToSessionClientInterface;
use SprykerShop\Yves\AgentPage\Dependency\Client\AgentPageToStoreClientInterface;
use SprykerShop\Yves\AgentPage\Expander\SecurityBuilderExpander;
use SprykerShop\Yves\AgentPage\Expander\SecurityBuilderExpanderInterface;
use SprykerShop\Yves\AgentPage\Form\AgentLoginForm;
use SprykerShop\Yves\AgentPage\Formatter\LoginCheckUrlFormatter;
use SprykerShop\Yves\AgentPage\Formatter\LoginCheckUrlFormatterInterface;
use SprykerShop\Yves\AgentPage\Impersonator\SessionImpersonator;
use SprykerShop\Yves\AgentPage\Impersonator\SessionImpersonatorInterface;
use SprykerShop\Yves\AgentPage\Logger\AuditLogger;
use SprykerShop\Yves\AgentPage\Logger\AuditLoggerInterface;
use SprykerShop\Yves\AgentPage\Logger\DataProvider\AuditLoggerCustomerProvider;
use SprykerShop\Yves\AgentPage\Logger\DataProvider\AuditLoggerCustomerProviderInterface;
use SprykerShop\Yves\AgentPage\Plugin\FixAgentTokenAfterCustomerAuthenticationSuccessPlugin;
use SprykerShop\Yves\AgentPage\Plugin\Handler\AgentAuthenticationFailureHandler;
use SprykerShop\Yves\AgentPage\Plugin\Handler\AgentAuthenticationSuccessHandler;
use SprykerShop\Yves\AgentPage\Plugin\Provider\AccessDeniedHandler;
use SprykerShop\Yves\AgentPage\Plugin\Provider\AgentUserProvider;
use SprykerShop\Yves\AgentPage\Plugin\Security\AgentPageSecurityPlugin;
use SprykerShop\Yves\AgentPage\Plugin\Subscriber\SwitchUserEventSubscriber;
use SprykerShop\Yves\AgentPage\Processor\CurrentRequestProcessor;
use SprykerShop\Yves\AgentPage\Processor\CurrentRequestProcessorInterface;
use SprykerShop\Yves\AgentPage\Security\Agent;
use SprykerShop\Yves\AgentPage\Updater\AgentTokenAfterCustomerAuthenticationSuccessUpdater;
use SprykerShop\Yves\AgentPage\Updater\AgentTokenAfterCustomerAuthenticationSuccessUpdaterInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

/**
 * @method \SprykerShop\Yves\AgentPage\AgentPageConfig getConfig()
 */
class AgentPageFactory extends AbstractFactory
{
    public function createSwitchUserEventSubscriber(): EventSubscriberInterface
    {
        return new SwitchUserEventSubscriber();
    }

    public function createAgentUserProvider(): UserProviderInterface
    {
        return new AgentUserProvider();
    }

    public function createAgentAuthenticationSuccessHandler(?string $targetUrl = null): AuthenticationSuccessHandlerInterface
    {
        return new AgentAuthenticationSuccessHandler($targetUrl);
    }

    public function createAgentAuthenticationFailureHandler(?string $targetUrl = null): AuthenticationFailureHandlerInterface
    {
        return new AgentAuthenticationFailureHandler($targetUrl);
    }

    public function getRouter(): RouterInterface
    {
        return $this->getProvidedDependency(AgentPageDependencyProvider::SERVICE_ROUTER);
    }

    public function createSecurityUser(UserTransfer $userTransfer): UserInterface
    {
        return new Agent(
            $userTransfer,
            [AgentPageSecurityPlugin::ROLE_AGENT, AgentPageSecurityPlugin::ROLE_ALLOWED_TO_SWITCH],
        );
    }

    /**
     * @deprecated Will be removed without replacement. Use `new RedirectResponse()` where you need it.
     *
     * @param string $url
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function createRedirectResponse(string $url): RedirectResponse
    {
        return new RedirectResponse($url);
    }

    public function getMessengerClient(): AgentPageToMessengerClientInterface
    {
        return $this->getProvidedDependency(AgentPageDependencyProvider::CLIENT_MESSENGER);
    }

    public function getAgentClient(): AgentPageToAgentClientInterface
    {
        return $this->getProvidedDependency(AgentPageDependencyProvider::CLIENT_AGENT);
    }

    public function getCustomerClient(): AgentPageToCustomerClientInterface
    {
        return $this->getProvidedDependency(AgentPageDependencyProvider::CLIENT_CUSTOMER);
    }

    public function getQuoteClient(): AgentPageToQuoteClientInterface
    {
        return $this->getProvidedDependency(AgentPageDependencyProvider::CLIENT_QUOTE);
    }

    public function getFormFactory(): FormFactoryInterface
    {
        return $this->getProvidedDependency(ApplicationConstants::FORM_FACTORY);
    }

    /**
     * @deprecated The application shouldn't be accessed and will be removed.
     *
     * @return \Spryker\Yves\Kernel\Application
     */
    public function getApplication(): Application
    {
        return $this->getProvidedDependency(AgentPageDependencyProvider::APPLICATION);
    }

    /**
     * @return \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
     */
    public function getTokenStorage()
    {
        return $this->getProvidedDependency(AgentPageDependencyProvider::SERVICE_SECURITY_TOKEN_STORAGE);
    }

    public function getSecurityAuthorizationChecker(): AuthorizationCheckerInterface
    {
        return $this->getProvidedDependency(AgentPageDependencyProvider::SERVICE_SECURITY_AUTHORIZATION_CHECKER);
    }

    public function getLocale(): string
    {
        return $this->getProvidedDependency(AgentPageDependencyProvider::SERVICE_LOCALE);
    }

    public function createAccessDeniedHandler(string $targetUrl): AccessDeniedHandlerInterface
    {
        return new AccessDeniedHandler($targetUrl);
    }

    /**
     * @return \Symfony\Component\Form\FormInterface
     */
    public function createAgentLoginForm()
    {
        return $this->getFormFactory()
            ->create(AgentLoginForm::class);
    }

    public function createLoginCheckUrlFormatter(): LoginCheckUrlFormatterInterface
    {
        return new LoginCheckUrlFormatter(
            $this->getConfig(),
            $this->getLocale(),
            $this->getStoreClient(),
        );
    }

    public function createSessionImpersonator(): SessionImpersonatorInterface
    {
        return new SessionImpersonator(
            $this->getCustomerClient(),
            $this->getSessionPostImpersonationPlugins(),
        );
    }

    /**
     * @return list<\SprykerShop\Yves\AgentPageExtension\Dependency\Plugin\SessionPostImpersonationPluginInterface>
     */
    public function getSessionPostImpersonationPlugins(): array
    {
        return $this->getProvidedDependency(AgentPageDependencyProvider::PLUGINS_SESSION_POST_IMPERSONATION);
    }

    public function createAgentSecurityOptionsBuilder(): AgentSecurityOptionsBuilderInterface
    {
        return new AgentSecurityOptionsBuilder(
            $this->getConfig(),
            $this->createAgentUserProvider(),
            $this->createLoginCheckUrlFormatter(),
        );
    }

    public function createAgentLoginAuthenticator(): AuthenticatorInterface
    {
        return new AgentLoginFormAuthenticator(
            $this->createAgentUserProvider(),
            $this->createAgentAuthenticationSuccessHandler(),
            $this->createAgentAuthenticationFailureHandler(),
            $this->getRouter(),
            $this->createMultiFactorAuthBadge(),
        );
    }

    public function createSecurityBuilderExpander(): SecurityBuilderExpanderInterface
    {
        if (class_exists(AuthenticationProviderManager::class) === true) {
            return new AgentPageSecurityPlugin();
        }

        return new SecurityBuilderExpander(
            $this->createAgentSecurityOptionsBuilder(),
            $this->getConfig(),
            $this->createSwitchUserEventSubscriber(),
            $this->createAgentLoginAuthenticator(),
        );
    }

    public function createAgentTokenAfterCustomerAuthenticationSuccessUpdater(): AgentTokenAfterCustomerAuthenticationSuccessUpdaterInterface
    {
        if (class_exists(AuthenticationProviderManager::class) === true) {
            return new FixAgentTokenAfterCustomerAuthenticationSuccessPlugin();
        }

        return new AgentTokenAfterCustomerAuthenticationSuccessUpdater(
            $this->getSecurityAuthorizationChecker(),
            $this->getAgentClient(),
            $this->getTokenStorage(),
            $this->getCustomerClient(),
        );
    }

    public function createAuditLogger(): AuditLoggerInterface
    {
        return new AuditLogger($this->createAuditLoggerCustomerProvider());
    }

    public function createAuditLoggerCustomerProvider(): AuditLoggerCustomerProviderInterface
    {
        return new AuditLoggerCustomerProvider($this->getTokenStorage());
    }

    public function createCurrentRequestProcessor(): CurrentRequestProcessorInterface
    {
        return new CurrentRequestProcessor($this->getRequestStackService());
    }

    public function getRequestStackService(): RequestStack
    {
        return $this->getProvidedDependency(AgentPageDependencyProvider::SERVICE_REQUEST_STACK);
    }

    public function createMultiFactorAuthBadge(): MultiFactorAuthBadge
    {
        return new MultiFactorAuthBadge($this->getAgentUserMultiFactorAuthenticationHandlerPlugins());
    }

    /**
     * @return array<\SprykerShop\Yves\AgentPageExtension\Dependency\Plugin\AuthenticationHandlerPluginInterface>
     */
    public function getAgentUserMultiFactorAuthenticationHandlerPlugins(): array
    {
        return $this->getProvidedDependency(AgentPageDependencyProvider::PLUGINS_AGENT_USER_AUTHENTICATION_HANDLER);
    }

    public function getSessionClient(): AgentPageToSessionClientInterface
    {
        return $this->getProvidedDependency(AgentPageDependencyProvider::CLIENT_SESSION);
    }

    public function getStoreClient(): AgentPageToStoreClientInterface
    {
        return $this->getProvidedDependency(AgentPageDependencyProvider::CLIENT_STORE);
    }
}
