<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerShopTest\Yves\AgentPage\Plugin\Security;

use Codeception\Stub;
use Codeception\Test\Unit;
use ReflectionClass;
use Spryker\Client\Storage\StorageDependencyProvider;
use Spryker\Client\StorageRedis\Plugin\StorageRedisPlugin;
use Spryker\Service\Container\ContainerInterface;
use Spryker\Yves\Messenger\FlashMessenger\FlashMessengerInterface;
use Spryker\Yves\Security\Configurator\SecurityConfigurator;
use SprykerShop\Yves\AgentPage\Plugin\Security\YvesAgentPageSecurityPlugin;
use SprykerShop\Yves\CustomerPage\CustomerPageDependencyProvider;
use SprykerShop\Yves\CustomerPage\Plugin\Security\YvesCustomerPageSecurityPlugin;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group SprykerShop
 * @group Yves
 * @group AgentPage
 * @group Plugin
 * @group YvesAgentPageSecurityPluginTest
 */
class YvesAgentPageSecurityPluginTest extends Unit
{
    /**
     * @var string
     */
    protected const SECURITY_TOKEN_STORAGE_SERVICE = 'security.token_storage';

    /**
     * @var string
     */
    protected const ACCESS_MODE_PUBLIC = 'PUBLIC_ACCESS';

    /**
     * @var string
     */
    protected const AUTHENTICATED = 'AUTHENTICATED';

    /**
     * @var string
     */
    protected const IS_AUTHENTICATED_FULLY = 'IS_AUTHENTICATED_FULLY';

    /**
     * @var string
     */
    protected const SERVICE_SECURITY_AUTHORIZATION_CHECKER = 'security.authorization_checker';

    /**
     * @uses \Spryker\Yves\Session\Plugin\Application\SessionApplicationPlugin::SERVICE_SESSION
     *
     * @var string
     */
    protected const SERVICE_SESSION = 'session';

    /**
     * @uses \Spryker\Yves\Messenger\Plugin\Application\FlashMessengerApplicationPlugin::SERVICE_FLASH_MESSENGER
     *
     * @var string
     */
    protected const SERVICE_FLASH_MESSENGER = 'flash_messenger';

    protected const string LOGIN_FORM_NAME = 'loginForm';

    /**
     * @var \SprykerShopTest\Yves\AgentPage\AgentPageTester
     */
    protected $tester;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->tester->isSymfonyVersion5() === true) {
            $this->markTestSkipped('Compatible only with `symfony/security-core` package version >= 6. Will be enabled by default once Symfony 5 support is discontinued.');
        }

        $container = $this->tester->getContainer();
        $container->set(static::SERVICE_FLASH_MESSENGER, function () {
            return Stub::makeEmpty(FlashMessengerInterface::class);
        });

        $this->tester->setDependency(StorageDependencyProvider::PLUGIN_STORAGE, new StorageRedisPlugin());
        $this->tester->setDependency(CustomerPageDependencyProvider::PLUGIN_APPLICATION, $container);

        $this->tester->addRoute('home', '/', function () use ($container) {
            return $this->createResponse($container);
        });

        $this->tester->addRoute('login', '/login', function () {
            return new Response('loginpage');
        });

        $this->tester->addRoute('agent/login', '/agent/login', function () use ($container) {
            return $this->createResponse($container);
        });

        $this->tester->addRoute('agent/login_check', '/agent/login_check', function () use ($container) {
            return $this->createResponse($container);
        });

        $this->tester->addRoute('agent/overview', '/agent/overview', function () use ($container) {
            return $this->createResponse($container);
        });

        $reflection = new ReflectionClass(SecurityConfigurator::class);
        $property = $reflection->getProperty('securityConfiguration');
        $property->setValue(null);
    }

    public function testAgentCanLogin(): void
    {
        // Arrange
        $container = $this->tester->getContainer();
        $userTransfer = $this->tester->haveRegisteredAgent(['password' => 'foo']);

        $securityPlugin = new YvesAgentPageSecurityPlugin();
        $securityPlugin->setFactory($this->tester->getFactory());
        $this->tester->addSecurityPlugin($securityPlugin);
        $this->tester->addSecurityPlugin(new YvesCustomerPageSecurityPlugin());
        $this->tester->mockSecurityDependencies();
        $this->tester->enableSecurityApplicationPlugin();

        $container->get(static::SERVICE_SESSION)->start();
        $csrfToken = $this->tester->addFormCsrfProvider()->getToken(static::LOGIN_FORM_NAME)->getValue();

        // Act
        $httpKernelBrowser = $this->tester->getHttpKernelBrowser();
        $httpKernelBrowser->request('get', '/');
        $httpKernelBrowser->request('post', '/agent/login_check', ['loginForm' => ['email' => $userTransfer->getUsername(), 'password' => 'foo', '_token' => $csrfToken]]);
        $httpKernelBrowser->followRedirect();

        // Assert
        $this->assertSame($userTransfer->getUsername() . static::AUTHENTICATED, $httpKernelBrowser->getResponse()->getContent());
    }

    public function testAgentIsRedirectedOnAuthenticationFailure(): void
    {
        // Arrange
        $container = $this->tester->getContainer();
        $userTransfer = $this->tester->haveRegisteredAgent(['password' => 'foo']);

        $securityPlugin = new YvesAgentPageSecurityPlugin();
        $securityPlugin->setFactory($this->tester->getFactory());
        $this->tester->addSecurityPlugin($securityPlugin);
        $this->tester->addSecurityPlugin(new YvesCustomerPageSecurityPlugin());
        $this->tester->mockSecurityDependencies();
        $this->tester->enableSecurityApplicationPlugin();

        $container->get(static::SERVICE_SESSION)->start();
        $csrfToken = $this->tester->addFormCsrfProvider()->getToken(static::LOGIN_FORM_NAME)->getValue();

        // Act
        $httpKernelBrowser = $this->tester->getHttpKernelBrowser();
        $httpKernelBrowser->request('get', '/');
        $httpKernelBrowser->request('post', '/agent/login_check', ['loginForm' => ['email' => $userTransfer->getUsername(), 'password' => 'bar', '_token' => $csrfToken]]);
        $httpKernelBrowser->followRedirect();

        // Assert
        $this->assertSame(static::ACCESS_MODE_PUBLIC, $httpKernelBrowser->getResponse()->getContent());
    }

    public function testAgentCanSwitchUser(): void
    {
        // Arrange
        $container = $this->tester->getContainer();
        $customerTransfer = $this->tester->haveCustomer(['username' => 'user', 'password' => 'foo']);
        $userTransfer = $this->tester->haveRegisteredAgent(['password' => 'foo']);

        $securityPlugin = new YvesAgentPageSecurityPlugin();
        $securityPlugin->setFactory($this->tester->getFactory());
        $this->tester->addSecurityPlugin($securityPlugin);
        $this->tester->addSecurityPlugin(new YvesCustomerPageSecurityPlugin());
        $this->tester->mockSecurityDependencies();
        $this->tester->enableSecurityApplicationPlugin();

        $container->get(static::SERVICE_SESSION)->start();
        $csrfToken = $this->tester->addFormCsrfProvider()->getToken(static::LOGIN_FORM_NAME)->getValue();

        // Act
        $httpKernelBrowser = $this->tester->getHttpKernelBrowser();
        $httpKernelBrowser->request('get', '/');
        $httpKernelBrowser->request('post', '/agent/login_check', ['loginForm' => ['email' => $userTransfer->getUsername(), 'password' => 'foo', '_token' => $csrfToken]]);

        $httpKernelBrowser->request('get', '/?_switch_user=' . $customerTransfer->getEmail());
        $httpKernelBrowser->followRedirect();

        // Assert
        $this->assertSame($customerTransfer->getEmail() . static::AUTHENTICATED, $httpKernelBrowser->getResponse()->getContent());
    }

    public function testInactiveAgentCanNotLogin(): void
    {
        // Arrange
        $container = $this->tester->getContainer();
        $userTransfer = $this->tester->haveRegisteredAgent(['password' => 'foo']);

        $this->tester->getLocator()->user()->facade()->deactivateUser($userTransfer->getIdUser());

        // Act
        $securityPlugin = new YvesAgentPageSecurityPlugin();
        $securityPlugin->setFactory($this->tester->getFactory());
        $this->tester->addSecurityPlugin($securityPlugin);
        $this->tester->addSecurityPlugin(new YvesCustomerPageSecurityPlugin());
        $this->tester->mockSecurityDependencies();
        $this->tester->enableSecurityApplicationPlugin();

        $container->get(static::SERVICE_SESSION)->start();
        $csrfToken = $this->tester->addFormCsrfProvider()->getToken(static::LOGIN_FORM_NAME)->getValue();

        $httpKernelBrowser = $this->tester->getHttpKernelBrowser();
        $httpKernelBrowser->request('get', '/');
        $httpKernelBrowser->request('post', '/agent/login_check', ['loginForm' => ['email' => $userTransfer->getUsername(), 'password' => 'foo', '_token' => $csrfToken]]);
        $httpKernelBrowser->followRedirect();

        // Assert
        $this->assertSame(static::ACCESS_MODE_PUBLIC, $httpKernelBrowser->getResponse()->getContent());
    }

    /**
     * @dataProvider invalidCsrfTokenDataProvider
     *
     * @param array<string, string> $csrfTokenField
     *
     * @return void
     */
    public function testAgentWithValidCredentialsAndInvalidCsrfTokenCanNotLogin(array $csrfTokenField): void
    {
        // Arrange
        $userTransfer = $this->tester->haveRegisteredAgent(['password' => 'foo']);
        $this->enableAgentSecurity();

        // Act
        $httpKernelBrowser = $this->tester->getHttpKernelBrowser();
        $httpKernelBrowser->request('post', '/agent/login_check', ['loginForm' => ['email' => $userTransfer->getUsername(), 'password' => 'foo'] + $csrfTokenField]);
        $httpKernelBrowser->followRedirect();

        // Assert
        $this->assertSame(static::ACCESS_MODE_PUBLIC, $httpKernelBrowser->getResponse()->getContent());
    }

    /**
     * @return array<string, array<array<string, string>>>
     */
    public function invalidCsrfTokenDataProvider(): array
    {
        return [
            'missing token' => [[]],
            'empty token' => [['_token' => '']],
            'invalid token' => [['_token' => 'invalid-token']],
        ];
    }

    protected function enableAgentSecurity(): void
    {
        $securityPlugin = new YvesAgentPageSecurityPlugin();
        $securityPlugin->setFactory($this->tester->getFactory());
        $this->tester->addSecurityPlugin($securityPlugin);
        $this->tester->addSecurityPlugin(new YvesCustomerPageSecurityPlugin());
        $this->tester->mockSecurityDependencies();
        $this->tester->enableSecurityApplicationPlugin();

        $this->tester->getContainer()->get(static::SERVICE_SESSION)->start();
        $this->tester->addFormCsrfProvider();
    }

    protected function createResponse(ContainerInterface $container): Response
    {
        if (!$container->has(static::SECURITY_TOKEN_STORAGE_SERVICE)) {
                return new Response(static::ACCESS_MODE_PUBLIC);
        }

        $token = $container->get(static::SECURITY_TOKEN_STORAGE_SERVICE)->getToken();
        $content = $token ? $token->getUser()->getUserIdentifier() : static::ACCESS_MODE_PUBLIC;

        if ($container->get(static::SERVICE_SECURITY_AUTHORIZATION_CHECKER)->isGranted(static::IS_AUTHENTICATED_FULLY)) {
            $content .= static::AUTHENTICATED;
        }

        return new Response($content);
    }
}
