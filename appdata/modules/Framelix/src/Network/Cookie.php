<?php

namespace Framelix\Framelix\Network;

use Framelix\Framelix\Framelix;
use Framelix\Framelix\Utils\CryptoUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Nullix\CryptoJsAes\CryptoJsAes;
use Throwable;

use function base64_decode;
use function base64_encode;
use function setcookie;
use function time;

/**
 * Cookie utilities for frequent tasks
 */
class Cookie
{
    /**
     * Get cookie value
     * @param string $name Cookie key name
     * @param bool $isSigned This cookie value need a valid signature that has been added by self::set() with $sign=true
     * @param bool $encrypted This cookie value is expected to be AES encrypted and can only be decrypted by the app, not the client/browser
     * @return mixed|null
     */
    public static function get(string $name, bool $isSigned = true, bool $encrypted = false): mixed
    {
        $value = $_COOKIE[$name] ?? null;
        if ($value === null) {
            return null;
        }
        if ($isSigned) {
            $hash = $_COOKIE[$name . "__s"] ?? null;
            if ($hash !== CryptoUtils::hash($name . $value)) {
                return null;
            }
        }
        if ($encrypted) {
            $value = CryptoJsAes::decrypt(base64_decode($value), CryptoUtils::hash($name));
        } else {
            // simply ignoring any json parse errors as this value can be modified by a user
            try {
                $value = JsonUtils::decode(base64_decode($value));
            } catch (Throwable $e) {
                $value = null;
            }
        }
        return $value;
    }

    /**
     * Set cookie value
     * @param string $name Cookie key name
     * @param mixed $value Null will unset the cookie key, can be any json serializable value
     * @param bool $sign This set also signed hash cookie value
     *  This make sure that a cookie value can't be faked by just changing it's value in the request
     *  The self::get() than check the given signature and return null if the signature hash does not match
     * @param int|null $lifetime Lifetime in seconds from now + given seconds, null then lifetime is a browser session
     * @param bool $encrypted The cookie value will be AES encrypted and can only be decrypted by the app, not the client/browser
     *      Use it for secrets and stuff that a user shouldn't see, but use with caution, because datasize is much greater then the value itself
     */
    public static function set(
        string $name,
        mixed $value,
        bool $sign = true,
        ?int $lifetime = null,
        bool $encrypted = false
    ): void {
        if ($value === null) {
            $value = "";
            $cookieLifetime = 1;
            unset($_COOKIE[$name]);
        } else {
            if ($encrypted) {
                $value = base64_encode(CryptoJsAes::encrypt($value, CryptoUtils::hash($name)));
            } else {
                $value = base64_encode(JsonUtils::encode($value));
            }
            $cookieLifetime = $lifetime !== null ? time() + $lifetime : 0;
            $_COOKIE[$name] = $value;
        }
        // @codeCoverageIgnoreStart
        if (!Framelix::isCli()) {
            setcookie($name, $value, [
                'expires' => $cookieLifetime,
                'path' => '/',
                'domain' => '',
                'secure' => true,
                'httponly' => true
            ]);
        }
        // @codeCoverageIgnoreEnd
        if ($sign) {
            $_COOKIE[$name . "__s"] = CryptoUtils::hash($name . $value);
            // @codeCoverageIgnoreStart
            if (!Framelix::isCli()) {
                setcookie($name . "__s", $_COOKIE[$name . "__s"], [
                    'expires' => $cookieLifetime,
                    'path' => '/',
                    'domain' => '',
                    'secure' => true,
                    'httponly' => true
                ]);
            }
            // @codeCoverageIgnoreEnd
        }
    }
}