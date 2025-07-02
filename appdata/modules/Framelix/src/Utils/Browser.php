<?php

namespace Framelix\Framelix\Utils;

use JetBrains\PhpStorm\ExpectedValues;

use function array_shift;
use function curl_close;
use function curl_error;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt;
use function explode;
use function mb_substr;
use function strtoupper;

use const CURLINFO_RESPONSE_CODE;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_HEADER;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_URL;

/**
 * Browser Utils - To mimic browser when calling urls or sending post data
 */
class Browser
{

    /**
     * The current curl handler
     * @var mixed
     */
    public mixed $curl = null;

    /**
     * The last request curl handler
     * @var mixed
     */
    public mixed $lastRequestCurl = null;

    /**
     * User and/or password credentials for basic auth
     * Example: username:yourpassword
     * @var string|null
     */
    public ?string $userPwd = null;

    /**
     * Headers to send with request
     * By default we simulate chrome
     * @var string[]
     */
    public array $sendHeaders = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36'
    ];

    /**
     * Parsed response headers from last request
     * @var string[]
     */
    public array $responseHeaders = [];

    /**
     * Last complete raw response from last request
     * @var string
     */
    public string $rawResponseData = '';

    /**
     * Raw response body from last request
     * @var string
     */
    public string $responseBody = '';

    /**
     * Error message from last request, if there is any
     * @var string|null
     */
    public ?string $requestError = null;

    /**
     * Validate ssl cert when using https
     * @var bool
     */
    public bool $validateSsl = true;

    /**
     * Follow redirects
     * @var bool
     */
    public bool $followRedirects = true;

    /**
     * @param string $url The url for the request
     * @param string $requestMethod Most likeley you will need get or post
     * @param array|string|null $requestBody Pass an array of values or a raw string
     */
    public function __construct(
        public string $url = '',
        #[ExpectedValues(values: [
            'get',
            'post',
            'head',
            'delete',
            'put',
            'patch',
            'options',
            'trace',
            'connect'
        ])] public string $requestMethod = 'get',
        public array|string|null $requestBody = null
    ) {
        $this->resetCurl();
    }

    /**
     * Reset curl instance
     * @return void
     */
    public function resetCurl(): void
    {
        if ($this->curl) {
            curl_close($this->curl);
        }
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_HEADER, true);
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, $this->followRedirects);
    }

    /**
     * Get response code from last request
     * @return int
     */
    public function getResponseCode(): int
    {
        if ($this->lastRequestCurl) {
            return (int)curl_getinfo($this->lastRequestCurl, CURLINFO_RESPONSE_CODE);
        }
        return 0;
    }

    /**
     * Get response body as text
     * @return string
     */
    public function getResponseText(): string
    {
        return $this->responseBody;
    }

    /**
     * Get response body as parsed json data
     * @return mixed
     */
    public function getResponseJson(): mixed
    {
        return $this->responseBody !== '' ? JsonUtils::decode($this->responseBody) : '';
    }

    /**
     * Send the request
     * @return void
     */
    public function sendRequest(): void
    {
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, strtoupper($this->requestMethod));
        curl_setopt($this->curl, CURLOPT_URL, $this->url);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->sendHeaders);
        if ($this->requestBody !== null) {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->requestBody);
            $this->requestBody = null;
        }
        if (!$this->validateSsl) {
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
        }
        if ($this->userPwd) {
            curl_setopt($this->curl, CURLOPT_USERPWD, $this->userPwd);
        }
        $this->rawResponseData = curl_exec($this->curl);
        $error = curl_error($this->curl);
        $this->requestError = $error ?: null;
        $this->responseBody = '';
        $this->responseHeaders = [];
        if (!$this->requestError) {
            $headerSize = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
            $headerData = mb_substr($this->rawResponseData, 0, $headerSize);
            $headerLines = explode("\n", $headerData);
            array_shift($headerLines);
            foreach ($headerLines as $headerLine) {
                $spl = explode(":", trim($headerLine), 2);
                if (count($spl) === 2) {
                    $this->responseHeaders[$spl[0]] = mb_substr($spl[1], 1);
                }
            }
            $this->responseBody = mb_substr($this->rawResponseData, $headerSize);
        }

        // setup new curl and save last curl
        $this->lastRequestCurl = $this->curl;
        $this->resetCurl();
    }
}