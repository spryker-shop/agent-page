<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerShopTest\Yves\AgentPage\Security;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\UserTransfer;
use SprykerShop\Yves\AgentPage\Security\Agent;

/**
 * Auto-generated group annotations
 *
 * @group SprykerShopTest
 * @group Yves
 * @group AgentPage
 * @group Security
 * @group AgentTest
 * Add your own group annotations below this line
 */
class AgentTest extends Unit
{
    protected const string PASSWORD_HASH = '$2y$10$examplehashvalue1234567890';

    protected const string STATUS_ACTIVE = 'active';

    public function testUsersWithSameStateAreEqual(): void
    {
        // Arrange
        $user = $this->createUser();
        $sameStateUser = $this->createUser();

        // Act, Assert
        $this->assertTrue($user->isEqualTo($sameStateUser));
    }

    public function testUsersWithDifferentPasswordAreNotEqual(): void
    {
        // Arrange
        $user = $this->createUser();
        $changedPasswordUser = $this->createUser([UserTransfer::PASSWORD => 'another-hash']);

        // Act, Assert
        $this->assertFalse($user->isEqualTo($changedPasswordUser));
    }

    public function testUsersWithDifferentStatusAreNotEqual(): void
    {
        // Arrange
        $user = $this->createUser();
        $blockedUser = $this->createUser([UserTransfer::STATUS => 'blocked']);

        // Act, Assert
        $this->assertFalse($user->isEqualTo($blockedUser));
    }

    public function testUsersWithDifferentRolesAreNotEqual(): void
    {
        // Arrange
        $user = $this->createUser();
        $differentRolesUser = $this->createUser([], ['ACCESS_MODE_PRE_AUTH']);

        // Act, Assert
        $this->assertFalse($user->isEqualTo($differentRolesUser));
    }

    public function testSerializationStripsPasswordAndPreservesEquality(): void
    {
        // Arrange
        $user = $this->createUser();

        // Act
        $serializedUser = serialize($user);
        $unserializedUser = unserialize($serializedUser);

        // Assert
        $this->assertStringNotContainsString(static::PASSWORD_HASH, $serializedUser);
        $this->assertNull($unserializedUser->getPassword());
        $this->assertTrue($unserializedUser->isEqualTo($this->createUser()));
    }

    public function testUnserializeAcceptsSessionWrittenBeforePasswordRemoval(): void
    {
        // Arrange
        $legacySerializedAgent = $this->createLegacySerializedAgent();

        // Act
        $unserializedAgent = unserialize($legacySerializedAgent);

        // Assert
        $this->assertInstanceOf(Agent::class, $unserializedAgent);
        $this->assertNull($unserializedAgent->getPassword());
        // The legacy transfer still carries the hash until the session is written again;
        // the next serialization must strip it.
        $this->assertStringNotContainsString(static::PASSWORD_HASH, serialize($unserializedAgent));
        $this->assertTrue($unserializedAgent->isEqualTo($this->createUser()));
    }

    public function testLegacySessionAgentWithChangedPasswordIsNotEqual(): void
    {
        // Arrange
        $legacySerializedAgent = $this->createLegacySerializedAgent();

        // Act
        $unserializedAgent = unserialize($legacySerializedAgent);

        // Assert
        $this->assertFalse($unserializedAgent->isEqualTo($this->createUser([UserTransfer::PASSWORD => 'another-hash'])));
    }

    /**
     * Builds the exact payload the default object serialization produced before `__serialize()` existed:
     * protected property names prefixed with "\0*\0" and no `stateHash` entry.
     */
    protected function createLegacySerializedAgent(): string
    {
        $userTransfer = (new UserTransfer())->fromArray([
            UserTransfer::USERNAME => 'user@spryker.com',
            UserTransfer::PASSWORD => static::PASSWORD_HASH,
            UserTransfer::STATUS => static::STATUS_ACTIVE,
        ], true);

        $propertyTable = [
            "\0*\0userTransfer" => $userTransfer,
            "\0*\0username" => $userTransfer->getUsername(),
            "\0*\0password" => $userTransfer->getPassword(),
            "\0*\0roles" => ['ROLE_USER'],
        ];
        $serializedPropertyTable = serialize($propertyTable);

        return sprintf(
            'O:%d:"%s":%d:{%s',
            strlen(Agent::class),
            Agent::class,
            count($propertyTable),
            substr($serializedPropertyTable, strpos($serializedPropertyTable, '{') + 1),
        );
    }

    /**
     * @param array<string, mixed> $userData
     * @param list<string> $roles
     */
    protected function createUser(array $userData = [], array $roles = ['ROLE_USER']): Agent
    {
        $userTransfer = (new UserTransfer())->fromArray($userData + [
            UserTransfer::USERNAME => 'user@spryker.com',
            UserTransfer::PASSWORD => static::PASSWORD_HASH,
            UserTransfer::STATUS => static::STATUS_ACTIVE,
        ], true);

        return new Agent($userTransfer, $roles);
    }
}
