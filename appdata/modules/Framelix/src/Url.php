<?php

namespace Framelix\Framelix;

use Framelix\Framelix\Exception\Redirect;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Storable\UserToken;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\CryptoUtils;
use JsonSerializable;

use function count;
use function crc32;
use function explode;
use function filemtime;
use function http_build_query;
use function implode;
use function is_array;
use function ltrim;
use function parse_str;
use function parse_url;
use function realpath;
use function rtrim;
use function str_contains;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;
use function time;
use function trim;
use function urldecode;

use const FRAMELIX_APPDATA_FOLDER;

/**
 * URL utilities for frequent tasks
 */
class Url implements JsonSerializable
{
    /**
     * Contains all url data
     * @var array
     */
    public array $urlData = [];

    /**
     * Get an url that is pointing to a public file/folder on disk
     * Could be in /framelix/userdata, /framelix/appdata/modules/ * /public, /framelix/appdata/modules/ * /lang
     * @param string $filePath
     * @param bool $antiCacheParameter Append a "t" parameter representing the filetime in crc32
     * @return Url|null
     */
    public static function getUrlToPublicFile(
        string $filePath,
        bool $antiCacheParameter = true
    ): ?self {
        if (!$filePath) {
            return null;
        }
        $filePath = realpath($filePath);
        if (!$filePath) {
            return null;
        }
        $exp = explode("/", $filePath);
        if (str_starts_with($filePath, FRAMELIX_USERDATA_FOLDER)) {
            $urlPrefix = "__" . $exp[3];
            unset($exp[0], $exp[1], $exp[2], $exp[3], $exp[4]);
            $relativePath = implode("/", $exp);
        } elseif (str_starts_with($filePath, FRAMELIX_APPDATA_FOLDER)) {
            if (str_contains($filePath, "/lang/") && !str_contains($filePath, "/public/")) {
                $urlPrefix = "$" . $exp[4];
                unset($exp[0], $exp[1], $exp[2], $exp[3], $exp[4], $exp[5]);
                $relativePath = implode("/", $exp);
            } else {
                $urlPrefix = "_" . $exp[4];
                unset($exp[0], $exp[1], $exp[2], $exp[3], $exp[4], $exp[5]);
                $relativePath = implode("/", $exp);
            }
        } else {
            return null;
        }
        $url = Url::getApplicationUrl();
        $url->appendPath($urlPrefix . "/" . $relativePath);
        if ($antiCacheParameter) {
            $url->setParameter('t', crc32((string)filemtime($filePath)));
        }
        return $url;
    }

    /**
     * Get application url
     * @return Url
     */
    public static function getApplicationUrl(): self
    {
        $url = 'https://';
        $url .= str_replace("{host}", $_SERVER['HTTP_HOST'] ?? '', Config::$applicationHost);
        $url .= "/" . trim("/" . Config::$applicationUrlPrefix, "/");
        return self::create($url);
    }

    /**
     * Create current browser url
     * Return the same as ::create if no browser url header exist
     * Helpful in nested async requests when context is required
     * @return Url
     */
    public static function getBrowserUrl(): self
    {
        return self::create(Request::getHeader('http_x_browser_url'));
    }

    /**
     * Create url instance based on given url
     * @param string|Url|null $url
     * @return Url
     */
    public static function create(string|Url|null $url = null): self
    {
        if ($url instanceof Url) {
            $url = $url->getUrlAsString();
        }
        if (!$url) {
            $url = (Request::isHttps() ? 'https' : 'http') . "://"
                . ($_SERVER['HTTP_HOST'] ?? Config::$applicationHost) . ($_SERVER['REQUEST_URI'] ?? "/");
        }
        $instance = new self();
        $instance->update($url, true);
        return $instance;
    }

    /**
     * To string converts it to the complete url
     * @return string
     */
    public function __toString(): string
    {
        return $this->getUrlAsString();
    }

    /**
     * Get url as string
     * @param bool $includeHostname If false, it returns only path without host/scheme
     * @return string
     */
    public function getUrlAsString(bool $includeHostname = true): string
    {
        $url = "";
        if ($includeHostname) {
            if ($this->urlData['scheme'] ?? null) {
                $url .= $this->urlData['scheme'] . "://";
            }
            $hostPrefix = null;
            if ($this->urlData['user'] ?? null) {
                $url .= $this->urlData['user'];
                $hostPrefix = "@";
            }
            if ($this->urlData['pass'] ?? null) {
                $url .= ":" . $this->urlData['pass'];
                $hostPrefix = "@";
            }
            if ($this->urlData['host'] ?? null) {
                $url .= $hostPrefix . $this->urlData['host'];
            }
            if ($this->urlData['port'] ?? null) {
                $url .= ":" . $this->urlData['port'];
            }
        }
        $url .= $this->getPathAndQueryString();
        if ($this->urlData['fragment'] ?? null) {
            $url .= "#" . $this->urlData['fragment'];
        }
        return $url;
    }

    /**
     * Set scheme (http/https)
     * @param string $str
     * @return self
     */
    public function setScheme(string $str): self
    {
        $this->urlData['scheme'] = $str;
        return $this;
    }

    /**
     * Get scheme (http/https)
     * @return string|null
     */
    public function getScheme(): ?string
    {
        return $this->urlData['scheme'] ?? null;
    }

    /**
     * Set username
     * @param string|null $str
     * @return self
     */
    public function setUsername(?string $str): self
    {
        $this->urlData['user'] = $str;
        return $this;
    }

    /**
     * Get username
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->urlData['user'] ?? null;
    }

    /**
     * Set password
     * @param string|null $str
     * @return self
     */
    public function setPassword(?string $str): self
    {
        $this->urlData['pass'] = $str;
        return $this;
    }

    /**
     * Get password
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->urlData['pass'] ?? null;
    }

    /**
     * Set host
     * @param string $str
     * @return self
     */
    public function setHost(string $str): self
    {
        $this->urlData['host'] = $str;
        return $this;
    }

    /**
     * Get host
     * @return string|null
     */
    public function getHost(): ?string
    {
        return $this->urlData['host'] ?? null;
    }

    /**
     * Set port
     * @param int|null $port
     * @return self
     */
    public function setPort(?int $port): self
    {
        $this->urlData['port'] = $port;
        return $this;
    }

    /**
     * Get port
     * @return int|null
     */
    public function getPort(): ?int
    {
        return isset($this->urlData['port']) ? (int)$this->urlData['port'] : null;
    }

    /**
     * Set port
     * @param string $path
     * @return self
     */
    public function setPath(string $path): self
    {
        $this->urlData['path'] = $path;
        return $this;
    }

    /**
     * Append given path to existing path
     * @param string $path
     * @return self
     */
    public function appendPath(string $path): self
    {
        if (!$path || $path === "/") {
            return $this;
        }
        $this->urlData['path'] = rtrim($this->getPath(), "/") . "/" . ltrim($path, "/");
        return $this;
    }

    /**
     * Get path
     * @return string
     */
    public function getPath(): string
    {
        return $this->urlData['path'] ?? '';
    }

    /**
     * Get path and query string
     * @return string
     */
    public function getPathAndQueryString(): string
    {
        $str = $this->getPath();
        if (is_array($this->urlData['queryParameters'] ?? null) && $this->urlData['queryParameters']) {
            $str .= "?" . http_build_query($this->urlData['queryParameters']);
        }
        return $str;
    }

    /**
     * Get all query parameters
     * @return mixed
     */
    public function getParameters(): mixed
    {
        return $this->urlData['queryParameters'] ?? null;
    }

    /**
     * Get query parameter
     * @param string $key
     * @return mixed
     */
    public function getParameter(string $key): mixed
    {
        return ArrayUtils::getValue($this->urlData['queryParameters'] ?? null, $key);
    }

    /**
     * Add multiple query parameters
     * @param array|null $parameters
     * @return self
     */
    public function addParameters(?array $parameters): self
    {
        if ($parameters) {
            foreach ($parameters as $key => $value) {
                $this->setParameter($key, $value);
            }
        }
        return $this;
    }

    /**
     * Set query parameter
     * @param string $key
     * @param mixed $value Null will remove the key
     * @return self
     */
    public function setParameter(string $key, mixed $value): self
    {
        if ($value === null) {
            unset($this->urlData['queryParameters'][$key]);
            return $this;
        }
        if (is_array($value)) {
            foreach ($value as $subKey => $subValue) {
                $this->setParameter($key . "[$subKey]", $subValue);
            }
        } else {
            ArrayUtils::setValue($this->urlData['queryParameters'], $key, (string)$value);
        }
        return $this;
    }

    /**
     * Remove all query parameters
     * @return self
     */
    public function removeParameters(): self
    {
        $this->urlData['queryParameters'] = [];
        return $this;
    }

    /**
     * Remove query parameter
     * @param string $key
     * @return self
     */
    public function removeParameter(string $key): self
    {
        return $this->setParameter($key, null);
    }

    /**
     * Check if url has a parameter with the given value
     * @param mixed $value
     * @return bool
     */
    public function hasParameterWithValue(mixed $value): bool
    {
        if ($this->urlData['queryParameters'] ?? null) {
            $compareValue = (string)$value;
            if ($compareValue === '') {
                return false;
            }
            foreach ($this->urlData['queryParameters'] as $parameterValue) {
                if ($compareValue === $parameterValue) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Remove parameters where the value is equal to the given value
     * @param mixed $value
     * @return self
     */
    public function removeParameterByValue(mixed $value): self
    {
        if ($this->urlData['queryParameters'] ?? null) {
            $compareValue = (string)$value;
            foreach ($this->urlData['queryParameters'] as $key => $parameterValue) {
                if ($compareValue === $parameterValue) {
                    unset($this->urlData['queryParameters'][$key]);
                }
            }
        }
        return $this;
    }

    /**
     * Set hash
     * @param string|null $hash
     * @return self
     */
    public function setHash(?string $hash): self
    {
        if ($hash === null) {
            unset($this->urlData['fragment']);
            return $this;
        }
        $this->urlData['fragment'] = $hash;
        return $this;
    }

    /**
     * Get hash
     * @return string|null
     */
    public function getHash(): ?string
    {
        return $this->urlData['fragment'] ?? null;
    }

    /**
     * Update this instance data with data from given url
     * @param string $url
     * @param bool $clearData If true, then delete all other urldata from this instance if not exist in $url
     * @return self
     */
    public function update(string $url, bool $clearData = false): self
    {
        $urlData = parse_url(urldecode($url));
        $urlData['path'] = $urlData['path'] ?? '';
        if (isset($urlData['query'])) {
            parse_str($urlData['query'], $urlData['queryParameters']);
        }
        if ($clearData) {
            $this->urlData = $urlData;
        } else {
            $this->urlData = ArrayUtils::merge($this->urlData, $urlData);
        }
        return $this;
    }

    /**
     * Get language from current url
     * @return string|null
     */
    public function getLanguage(): ?string
    {
        $lang = null;
        if (count(Config::$languagesAvailable) > 1) {
            $relativeUrl = strtolower($this->getRelativePath(self::getApplicationUrl()));
            foreach (Config::$languagesAvailable as $language) {
                if (str_starts_with($relativeUrl, "/$language/") || $relativeUrl === "/$language") {
                    $lang = $language;
                    break;
                }
            }
        }
        return $lang;
    }

    /**
     * Replace current url language with the new language
     * @param string $newLanguage
     */
    public function replaceLanguage(string $newLanguage): void
    {
        $foundLanguage = $this->getLanguage();
        $applicationUrl = self::getApplicationUrl();
        $relativeUrl = $this->getRelativePath($applicationUrl);
        if ($foundLanguage) {
            $relativeUrl = substr($relativeUrl, strlen($foundLanguage) + 1);
        }
        $this->setPath($applicationUrl->appendPath("/$newLanguage" . $relativeUrl)->getPath());
    }

    /**
     * Get relative path to other url
     * @param Url|null $otherUrl If not set, get full relative path of current url
     * @return string
     */
    public function getRelativePath(?self $otherUrl = null): string
    {
        $startFrom = 0;
        if ($otherUrl) {
            $applicationUrl = Url::getApplicationUrl();
            $startFrom = strlen($applicationUrl->urlData['path']);
            if (str_ends_with($applicationUrl->urlData['path'], "/")) {
                $startFrom--;
            }
        }
        return substr($this->urlData['path'], $startFrom);
    }

    /**
     * Redirect
     * @param int $code 301 = permanent, 302 = temporarily
     * @return never
     */
    public function redirect(int $code = 302): never
    {
        throw new Redirect(code: $code, url: $this);
    }

    /**
     * Sign the current url - Add a signature parameter
     * @param bool $signWithCurrentUserToken If true, then sign with current user token, so this url can only be verified by the same user
     * @param int $maxLifetime Max url lifetime in seconds, set to 0 if unlimited
     * @return self
     */
    public function sign(bool $signWithCurrentUserToken = true, int $maxLifetime = 86400): self
    {
        $this->removeParameter('__s');
        if ($signWithCurrentUserToken) {
            $this->setParameter('__usertoken', UserToken::getByCookie()->token ?? '');
            $this->setParameter('__t', "1");
        }
        if ($maxLifetime > 0) {
            $this->setParameter('__expires', time() + $maxLifetime);
        }
        $hash = CryptoUtils::hash($this);
        $this->removeParameter("__usertoken");
        $this->setParameter('__s', $hash);
        return $this;
    }

    /**
     * Verify if the url is correctly signed
     * @return bool
     */
    public function verify(): bool
    {
        $originalData = $this->urlData['queryParameters'] ?? null;
        $sign = (string)$this->getParameter('__s');
        if (!$sign) {
            return false;
        }
        $token = (string)$this->getParameter('__t');
        $expires = (int)$this->getParameter('__expires');
        $this->removeParameter('__s');
        if ($token) {
            $this->removeParameter("__t");
            $this->removeParameter("__expires");
            $this->setParameter('__usertoken', UserToken::getByCookie()->token ?? '');
            $this->setParameter("__t", "1");
            if ($expires > 0) {
                $this->setParameter("__expires", $expires);
            }
        }
        $result = CryptoUtils::compareHash($this, $sign);
        if (!$result) {
            return false;
        }
        if ($expires > 0 && $expires < time()) {
            return false;
        }
        $this->urlData['queryParameters'] = $originalData;
        return true;
    }

    /**
     * Get json data
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->getUrlAsString();
    }
}