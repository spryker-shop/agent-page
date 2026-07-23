<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerShop\Yves\AgentPage\Security;

use Generated\Shared\Transfer\UserTransfer;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class Agent implements UserInterface, PasswordAuthenticatedUserInterface, EquatableInterface
{
    protected const string SERIALIZATION_KEY_USER_TRANSFER = 'userTransfer';

    protected const string SERIALIZATION_KEY_USERNAME = 'username';

    protected const string SERIALIZATION_KEY_PASSWORD = 'password';

    protected const string SERIALIZATION_KEY_ROLES = 'roles';

    protected const string SERIALIZATION_KEY_STATE_HASH = 'stateHash';

    /**
     * Sessions written before `__serialize()` was introduced used default object serialization,
     * where protected property names are prefixed with "\0*\0".
     */
    protected const string LEGACY_PROTECTED_PROPERTY_PREFIX = "\0*\0";

    /**
     * @var \Generated\Shared\Transfer\UserTransfer
     */
    protected $userTransfer;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string|null
     */
    protected $password;

    /**
     * @var array
     */
    protected $roles = [];

    protected ?string $stateHash = null;

    public function __construct(UserTransfer $userTransfer, array $roles = [])
    {
        $this->userTransfer = $userTransfer;
        $this->username = $userTransfer->getUsername();
        $this->password = $userTransfer->getPassword();
        $this->roles = $roles;
        $this->stateHash = $this->computeStateHash($this->password);
    }

    /**
     * @return array<string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @return string|null
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return string|null The password
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function eraseCredentials(): void
    {
    }

    public function isEqualTo(UserInterface $user): bool
    {
        if (!$user instanceof self) {
            return false;
        }

        return $user->getStateHash() === $this->stateHash;
    }

    public function getStateHash(): ?string
    {
        return $this->stateHash;
    }

    /**
     * @return \Generated\Shared\Transfer\UserTransfer
     */
    public function getUserTransfer()
    {
        return $this->userTransfer;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function __serialize(): array
    {
        $userTransferData = $this->userTransfer->modifiedToArray();
        unset($userTransferData[UserTransfer::PASSWORD]);
        $cleanUserTransfer = (new UserTransfer())->fromArray($userTransferData, true);

        return [
            static::SERIALIZATION_KEY_USER_TRANSFER => $cleanUserTransfer,
            static::SERIALIZATION_KEY_USERNAME => $this->username,
            static::SERIALIZATION_KEY_ROLES => $this->roles,
            static::SERIALIZATION_KEY_STATE_HASH => $this->stateHash,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        $data = $this->normalizeLegacySessionData($data);

        $this->userTransfer = $data[static::SERIALIZATION_KEY_USER_TRANSFER];
        $this->username = $data[static::SERIALIZATION_KEY_USERNAME];
        $this->roles = $data[static::SERIALIZATION_KEY_ROLES];
        $this->password = null;

        $this->stateHash = $data[static::SERIALIZATION_KEY_STATE_HASH]
            ?? $this->computeStateHash($data[static::SERIALIZATION_KEY_PASSWORD] ?? null);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function normalizeLegacySessionData(array $data): array
    {
        $normalizedData = [];

        foreach ($data as $key => $value) {
            $normalizedData[str_replace(static::LEGACY_PROTECTED_PROPERTY_PREFIX, '', $key)] = $value;
        }

        return $normalizedData;
    }

    protected function computeStateHash(?string $password): string
    {
        return hash('md5', implode('|', [
            $password ?? '',
            $this->userTransfer->getStatus() ?? '',
            implode(',', $this->roles),
        ]));
    }
}
