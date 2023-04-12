<?php

namespace Framelix\Framelix\Storable;

use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\View;
use SensitiveParameter;

use function array_combine;
use function explode;
use function is_array;
use function password_hash;
use function password_verify;
use function strlen;

/**
 * User
 * @property string $email
 * @property string|null $password
 * @property bool $flagLocked
 * @property string|null $twoFactorSecret
 * @property mixed|null $twoFactorBackupCodes
 * @property mixed|null $settings
 */
class User extends StorableExtended
{

    private static array $cache = [];

    /**
     * Only used for unit tests to simulate specific roles without actually storing that in the database The check is cached by default, once it have been checked. Set true to flush the cache
     * @var string[]|null
     */
    public ?array $simulateRoles = null;

    /**
     * Get current logged-in user
     * @param bool $originalUser If simulated user is active, then return original instead of simulated user
     * @return self|null
     */
    public static function get(bool $originalUser = false): ?self
    {
        $key = "getuser-" . (int)$originalUser;
        if (ArrayUtils::keyExists(self::$cache, $key)) {
            return self::$cache[$key];
        }
        $token = UserToken::getByCookie();
        self::$cache[$key] = null;
        if ($token?->user) {
            self::$cache[$key] = $originalUser ? $token->user : $token->simulatedUser ?? $token->user;
        }
        return self::$cache[$key];
    }

    /**
     * Set current logged-in user
     * Is required when some api require a user and set it to a system user
     * @param User|null $user
     */
    public static function setCurrentUser(?User $user): void
    {
        if ($user === null) {
            unset(self::$cache["getuser-0"], self::$cache["getuser-1"]);
            return;
        }
        self::$cache["getuser-0"] = $user;
        self::$cache["getuser-1"] = $user;
    }

    /**
     * Check if given user has any of the given roles
     * The roles are cached by default, once they have been called for a user
     * @param mixed $checkRoles If an array (or comma separated list) and any of that roles match, return true
     * @param User|false|null $user On false, automatically use the user returned by User::get()
     * @param bool $flushCache The check is cached by default, once it have been checked. Set true to flush the cache
     * @return bool
     */
    public static function hasRole(mixed $checkRoles, User|bool|null $user = false, bool $flushCache = false): bool
    {
        if ($checkRoles === "*") {
            return true;
        }
        if ($user === false) {
            $user = self::get();
        }
        if ($checkRoles === false) {
            return !$user;
        }
        if ($checkRoles === true) {
            return !!$user;
        }
        if (!$user) {
            return false;
        }
        $cacheKeyRoles = __METHOD__ . "-" . $user;
        $existingUserRoles = self::$cache[$cacheKeyRoles] ?? null;
        if ($existingUserRoles === null || $flushCache) {
            if ($user->simulateRoles) {
                $existingUserRoles = array_combine($user->simulateRoles, $user->simulateRoles);
            } else {
                $existingUserRoles = UserRole::getByCondition(
                    $user->getDb()->quoteIdentifier('user') . ' = {0}',
                    [$user]
                );
                $existingUserRoles = ArrayUtils::map($existingUserRoles, 'role', 'role');
            }
            self::$cache[$cacheKeyRoles] = $existingUserRoles;
        }
        if (!$existingUserRoles) {
            return false;
        }
        if (!is_array($checkRoles)) {
            $checkRoles = explode(",", $checkRoles);
        }
        foreach ($checkRoles as $role) {
            $role = trim($role);
            if (!strlen($role)) {
                continue;
            }
            if (isset($existingUserRoles[$role])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get a user by given email
     * @param string $email
     * @param bool $ignoreLocked If true, return even if user is locked
     * @return self|null
     */
    public static function getByEmail(string $email, bool $ignoreLocked = false): ?self
    {
        $condition = "email = {0}";
        if (!$ignoreLocked) {
            $condition .= " AND flagLocked = 0";
        }
        return self::getByConditionOne($condition, [$email]);
    }

    protected static function setupStorableSchema(StorableSchema $selfStorableSchema): void
    {
        parent::setupStorableSchema($selfStorableSchema);
        $selfStorableSchema->connectionId = FRAMELIX_MODULE;
        $selfStorableSchema->properties['settings']->lazyFetch = true;
        $selfStorableSchema->addIndex('email', 'unique');
    }

    public function getDetailsUrl(): ?Url
    {
        return View::getUrl(View\Backend\User\Index::class)->setParameter('id', $this);
    }

    /**
     * Add a role to the user
     * Return UserRole if role was not set previously
     * @param string $role
     * @return UserRole|null
     */
    public function addRole(string $role): ?UserRole
    {
        if (self::hasRole($role, $this)) {
            return null;
        }
        $roleObject = new UserRole();
        $roleObject->user = $this;
        $roleObject->role = $role;
        $roleObject->store();
        // flush the cache after role change
        self::hasRole($role, $this, true);
        return $roleObject;
    }

    /**
     * Remove a role from the user
     * @param string $role
     * @return bool True if role has been found and removed, false if user had not this role
     */
    public function removeRole(string $role): bool
    {
        if (!self::hasRole($role, $this)) {
            return false;
        }
        UserRole::getByConditionOne('`user` = {0}', [$this])?->delete();
        // flush the cache after role change
        self::hasRole($role, $this, true);
        return true;
    }

    public function setPassword(#[SensitiveParameter] string $plainPassword): void
    {
        $this->password = password_hash($plainPassword, PASSWORD_DEFAULT);
    }

    public function passwordVerify(#[SensitiveParameter] string $password): bool
    {
        return password_verify($password, $this->password);
    }

    public function getHtmlString(): string
    {
        return $this->email ?? '';
    }

    public function getRawTextString(): string
    {
        return $this->email ?? '';
    }

    public function delete(bool $force = false): void
    {
        self::deleteMultiple(UserRole::getByCondition('`user` = {0}', [$this]));
        self::deleteMultiple(UserToken::getByCondition('`user` = {0}', [$this]));
        self::deleteMultiple(UserVerificationToken::getByCondition('`user` = {0}', [$this]));
        self::deleteMultiple(UserWebAuthn::getByCondition('`user` = {0}', [$this]));
        parent::delete($force);
    }
}