<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerShopTest\Yves\AgentPage\Form;

use Codeception\Test\Unit;
use SprykerShop\Yves\AgentPage\AgentPageDependencyProvider;
use SprykerShop\Yves\AgentPage\Form\AgentLoginForm;
use SprykerShop\Yves\AgentPage\Formatter\LoginCheckUrlFormatterInterface;
use SprykerShop\Yves\AgentPageExtension\Dependency\Plugin\AgentLoginFormExpanderPluginInterface;
use SprykerShopTest\Yves\AgentPage\AgentPageTester;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Validation;

/**
 * Auto-generated group annotations
 *
 * @group SprykerShopTest
 * @group Yves
 * @group AgentPage
 * @group Form
 * @group AgentLoginFormTest
 * Add your own group annotations below this line
 */
class AgentLoginFormTest extends Unit
{
    protected const string FIELD_CAPTCHA = 'captcha';

    protected const string LOGIN_CHECK_PATH = '/agent/login_check';

    protected AgentPageTester $tester;

    public function testBuildFormAddsFieldOfAgentLoginFormExpanderPluginAfterLoginFields(): void
    {
        // Arrange
        $this->tester->setDependency(AgentPageDependencyProvider::PLUGINS_AGENT_LOGIN_FORM_EXPANDER, [
            $this->createCaptchaFieldExpanderPlugin(),
        ]);

        // Act
        $form = $this->createAgentLoginForm();

        // Assert
        $this->assertSame(
            [AgentLoginForm::FIELD_EMAIL, AgentLoginForm::FIELD_PASSWORD, static::FIELD_CAPTCHA],
            array_keys($form->all()),
        );
    }

    public function testBuildFormKeepsLoginFieldsWhenNoAgentLoginFormExpanderPluginIsRegistered(): void
    {
        // Act
        $form = $this->createAgentLoginForm();

        // Assert
        $this->assertSame(
            [AgentLoginForm::FIELD_EMAIL, AgentLoginForm::FIELD_PASSWORD],
            array_keys($form->all()),
        );
    }

    /**
     * The login check URL formatter is stubbed: it resolves the current store from storage, which is not
     * part of the form fields under test.
     *
     * @return \Symfony\Component\Form\FormInterface<mixed>
     */
    protected function createAgentLoginForm(): FormInterface
    {
        $loginCheckUrlFormatterStub = $this->createStub(LoginCheckUrlFormatterInterface::class);
        $loginCheckUrlFormatterStub->method('getLoginCheckPath')->willReturn(static::LOGIN_CHECK_PATH);
        $this->tester->mockFactoryMethod('createLoginCheckUrlFormatter', $loginCheckUrlFormatterStub);

        $agentLoginForm = new AgentLoginForm();
        $agentLoginForm->setFactory($this->tester->getFactory());

        return Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->addType($agentLoginForm)
            ->getFormFactory()
            ->create(AgentLoginForm::class);
    }

    protected function createCaptchaFieldExpanderPlugin(): AgentLoginFormExpanderPluginInterface
    {
        $agentLoginFormExpanderPluginMock = $this->createMock(AgentLoginFormExpanderPluginInterface::class);
        $agentLoginFormExpanderPluginMock->expects($this->once())
            ->method('expand')
            ->willReturnCallback(function (FormBuilderInterface $builder): FormBuilderInterface {
                return $builder->add(static::FIELD_CAPTCHA, TextType::class);
            });

        return $agentLoginFormExpanderPluginMock;
    }
}
