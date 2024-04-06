<?php

namespace Framelix\Framelix;

use Framelix\Framelix\Db\Sql;
use Framelix\Framelix\Exception\FatalError;
use Framelix\Framelix\Form\Field\Captcha;
use Framelix\Framelix\Html\CompilerFileBundle;
use Framelix\Framelix\Storable\SystemEventLog;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\JsonUtils;
use JetBrains\PhpStorm\ExpectedValues;
use SensitiveParameter;

use function file_exists;
use function set_time_limit;

use const FRAMELIX_MODULE;

class Config
{

    /**
     * Development mode enables more debugging and generation of required dist and meta files after source changes
     * Enable this with ENV variable FRAMELIX_DEVMODE=1 before starting the docker image
     * @var bool
     */
    public static bool $devMode = false;

    /**
     * The IP address field in $_SERVER which contains the client ip
     * @var string
     */
    public static string $clientIpKey = "REMOTE_ADDR";

    /**
     * For unittests - Override the client IP with this fixed value
     * @var string|null
     */
    public static ?string $clientIpOverride = null;

    /**
     * The languages that are available
     * @var string[]
     */
    public static array $languagesAvailable = ["en", "de"];

    /**
     * The default app language
     * @var string
     */
    public static string $language = "en";

    /**
     * If true, all generated view urls will contain the current language by default
     * @var bool
     */
    public static bool $languageInGeneratedViewUrls = false;

    /**
     * The language fallback in case the actual required language key isn't available
     * @var string
     */
    public static string $languageFallback = "en";

    /**
     * Enable extended error logging with all available runtime data
     * May contain sensible data
     * @var bool
     */
    public static bool $errorLogExtended = false;

    /**
     * The email(s) to send the errors logs to
     * Multiple separated by semicolon ;
     * @var string|null
     */
    public static ?string $errorLogEmail = null;

    /**
     * The cookie name for the backend user token
     * @var string
     */
    public static string $userTokenCookieName = "{module}_user_token";

    /**
     * Array of available user roles
     * @var array{id:string, langKey: string}[]
     */
    public static array $userRoles = [];

    /**
     * The default recaptchaV3Treshold for the integrated captcha field
     * @var float
     */
    public static float $recaptchaV3Treshold = 0.5;

    /**
     * The default captcha type for the captcha field
     * @var string|null
     */
    #[ExpectedValues(valuesFromClass: Captcha::class)]
    public static ?string $captchaType = null;

    /**
     * Captcha keys for the integrated captcha field
     * Use self::addCaptchaKey() to add some
     * @var array
     */
    public static array $captchaKeys = [];

    /**
     * Does the backend require a login captcha
     * @var bool
     */
    public static bool $backendLoginCaptcha = false;

    /**
     * Which built-in system event logs are enabled
     * Choose from SystemEventLog::CATEGORY_
     * @var bool[]
     */
    public static array $enabledBuiltInSystemEventLogs = [
        SystemEventLog::CATEGORY_LOGIN_FAILED => true,
        SystemEventLog::CATEGORY_LOGIN_SUCCESS => true,
    ];

    /**
     * How long should logs be kept
     * @var int[]
     */
    public static array $enabledBuiltInSystemEventLogsKeepDays = [
        SystemEventLog::CATEGORY_LOGIN_FAILED => 60,
        SystemEventLog::CATEGORY_LOGIN_SUCCESS => 60,
    ];

    /**
     * Automatic backup all sql database in given interval days (0=disabled)
     * @var int
     */
    public static int $automaticSqlBackupInterval = 1;

    /**
     * How many logfiles should automatic sql backup keep
     * @var int
     */
    public static int $automaticSqlBackupMaxLogs = 7;

    /**
     * Compiler file bundles added by self::createCompilerFileBundle()
     * @var CompilerFileBundle[]
     */
    public static array $compilerFileBundles = [];

    /**
     * Hash salts
     * Add them with self::addSalt()
     * @var string[]
     */
    public static array $salts = [];

    /**
     * Default email subject where {subject} gets replaced with the actual email subject
     * So you can prefix/affix every mail subject if you want (e.g: adding company name)
     * @var string
     */
    public static string $emailSubject = '{subject}';

    /**
     * Default email body where {body} gets replaced with the actual email body
     * Use this to wrap your body a round a html page for example, for better email styling
     * eg.: <div style="font:arial">{body}</div>
     * @var string
     */
    public static string $emailBody = '{body}';

    /**
     * The default FROM header for sending mails
     * @var string|null
     */
    public static ?string $emailDefaultFrom = null;

    /**
     * Override every outgoing email with this recipient
     * Should be used for debugging only
     * @var string|null
     */
    public static ?string $emailFixedRecipient = null;

    /**
     * With which technique emails will be sent
     * @var string|null
     */
    #[ExpectedValues(values: ['smtp', null])]
    public static ?string $emailSendType = null;

    /**
     * Smtp Host for sending mails
     * @var string|null
     */
    public static ?string $emailSmtpHost = null;

    /**
     * Smtp Port for sending mails
     * @var int|null
     */
    public static ?int $emailSmtpPort = null;

    /**
     * Smtp Username for sending mails
     * @var string|null
     */
    public static ?string $emailSmtpUsername = null;

    /**
     * Smtp Password for sending mails
     * @var string|null
     */
    public static ?string $emailSmtpPassword = null;

    /**
     * Smtp Secure settings for sending mails
     * @var string|null
     */
    #[ExpectedValues(values: ['tls', 'ssl', null])]
    public static ?string $emailSmtpSecure = null;

    /**
     * The application hostname
     * Can contain the port separated by a colon :
     * Use {host} if you want just the current host from the webserver (which can by dynamic)
     * @var string
     */
    public static string $applicationHost = '{host}';

    /**
     * The application url prefix
     * This is only required when to application is NOT installed on the domain root
     * e.g: The app startpoint is installed at yourdomain.com/myapplication, then you have to set prefix to
     * "myapplication"
     * @var string
     */
    public static string $applicationUrlPrefix = '';

    /**
     * The default view after login into backend
     * If null, it's just the root application host
     * @var string|null
     */
    public static ?string $backendDefaultView = null;

    /**
     * A file path pointing to the backend favicon
     * Must be in a public folder (module or userdata)
     * @var string|null
     */
    public static ?string $backendFaviconFilePath = __DIR__ . "/../public/img/logo.png";

    /**
     * A file path pointing to the backend logo
     * Must be in a public folder (module or userdata)
     * @var string|null
     */
    public static ?string $backendLogoFilePath = null;

    /**
     * Environment config about the docker/app environment (ports, modules, etc...)
     * @var array{moduleAccessPoints:array{module:string, port:int, ssl:bool}}
     */
    public static array $environmentConfig;

    /**
     * The human-readable default dateTime format in PHP
     * @var string
     */
    public static string $dateTimeFormatPhp = "d.m.Y H:i:s";

    /**
     * The human-readable default date format in PHP
     * @var string
     */
    public static string $dateFormatPhp = "d.m.Y";

    /**
     * The human-readable default dateTime format in javascript
     * @var string
     */
    public static string $dateTimeFormatJs = "DD.MM.YYYY HH:mm:ss";

    /**
     * The human-readable default date format in javascript
     * @var string
     */
    public static string $dateFormatJs = "DD.MM.YYYY";

    /**
     * Array of all available added sql connections
     * @var array
     */
    public static array $sqlConnections = [];

    /**
     * Called when the module is registered the first time
     * This is used for module defaults
     * @return void
     */
    public static function onRegister(): void
    {
        // add a default salt, after application is set up, this default salt must be replaced
        // app requires any salt to be functional, even during setup
        self::addSalt('none');

        self::$environmentConfig = JsonUtils::readFromFile("/framelix/system/environment.json");

        self::addAvailableUserRole('admin', '__framelix_user_role_admin__');
        self::addAvailableUserRole('dev', '__framelix_user_role_dev__');
        self::addAvailableUserRole('system', '__framelix_user_role_system__');
        self::addAvailableUserRole('usermanagement', '__framelix_edituser_sidebar_title__');

        $bundle = self::createCompilerFileBundle("Framelix", "js", "general-vendor-native");
        $bundle->includeInBackendView = \Framelix\Framelix\View\Backend\View::class;
        $bundle->compile = false;
        $bundle->addFile('node_modules/cash-dom/dist/cash.min.js');
        $bundle->addFile('js/cashjs/cash-improvements.js');
        $bundle->addFile('node_modules/dayjs/dayjs.min.js');
        $bundle->addFile('node_modules/dayjs/plugin/customParseFormat.js');
        $bundle->addFile('node_modules/dayjs/plugin/isoWeek.js');
        $bundle->addFile('node_modules/dayjs/plugin/utc.js');
        $bundle->addFile('node_modules/form-data-json-convert/dist/form-data-json.min.js');
        $bundle->addFile('node_modules/@popperjs/core/dist/umd/popper.min.js');
        $bundle->addFile('node_modules/swiped-events/dist/swiped-events.min.js');

        $bundle = self::createCompilerFileBundle("Framelix", "js", "form");
        $bundle->includeInBackendView = \Framelix\Framelix\View\Backend\View::class;
        $bundle->addFile('js/form/framelix-form.js');
        $bundle->addFile('js/form/framelix-form-field.js');
        $bundle->addFile('js/form/framelix-form-field-text.js');
        $bundle->addFile('js/form/framelix-form-field-select.js');
        $bundle->addFolder('js/form', true);

        $bundle = self::createCompilerFileBundle("Framelix", "js", "sortablejs");
        $bundle->compile = false;
        $bundle->addFile('node_modules/sortablejs/Sortable.min.js');

        $bundle = self::createCompilerFileBundle("Framelix", "js", "qrcodejs");
        $bundle->compile = false;
        $bundle->addFile('node_modules/qrcodejs/qrcode.min.js');
        $bundle->addFolder('vendor/qrcodejs', true);

        $bundle = self::createCompilerFileBundle("Framelix", "js", "general-early");
        $bundle->addFile('js/framelix-local-storage.js');
        $bundle->addFile('js/framelix-session-storage.js');
        $bundle->addFile('js/framelix-device-detection.js');

        $bundle = self::createCompilerFileBundle("Framelix", "js", "general");
        $bundle->includeInBackendView = \Framelix\Framelix\View\Backend\View::class;
        $bundle->addFolder('js', false, Config::$compilerFileBundles);
        $bundle->addFile('custom-elements/framelix-custom-element.js');
        $bundle->addFolder('custom-elements', true);

        $bundle = self::createCompilerFileBundle("Framelix", "js", "table-sorter-serviceworker");
        $bundle->addFile('js/framelix-table-sort-serviceworker.js');

        $bundle = self::createCompilerFileBundle("Framelix", "js", "backend");
        $bundle->includeInBackendView = \Framelix\Framelix\View\Backend\View::class;
        $bundle->addFolder('js/backend', true);

        $bundle = self::createCompilerFileBundle("Framelix", "scss", "general");
        $bundle->includeInBackendView = \Framelix\Framelix\View\Backend\View::class;
        $bundle->addFolder('scss/general', false);
        $bundle->addFolder('custom-elements', true);

        $bundle = self::createCompilerFileBundle("Framelix", "scss", "form");
        $bundle->includeInBackendView = \Framelix\Framelix\View\Backend\View::class;
        $bundle->addFolder('scss/form', true);

        $bundle = self::createCompilerFileBundle("Framelix", "scss", "backend");
        $bundle->includeInBackendView = \Framelix\Framelix\View\Backend\View::class;
        $bundle->addFolder('scss/backend', true);

        // register the other module
        Framelix::registerModule(FRAMELIX_MODULE);
    }

    /**
     * Increase time and/or memory limit for current request
     * Decreased usually not have an effect or will throw an error
     * @param int|null $newTimeLimit There are only 4 possible values
     *  1 = 60 seconds (1 minute = default)
     *  2 = 600 seconds (10 minutes)
     *  3 = 3600 (1 hour)
     *  4 = 7200 seconds (2 hours)
     *  null = Do not modify the default limit
     * @param int|null $newMemoryLimit In MB, null doesn't modify it
     * @return void
     */
    public static function setTimeAndMemoryLimit(
        #[ExpectedValues(values: [null, 1, 2, 3, 4])] ?int $newTimeLimit = 1,
        ?int $newMemoryLimit = 128
    ): void {
        if ($newTimeLimit !== null) {
            if ($newTimeLimit < 1 || $newTimeLimit > 4) {
                throw new FatalError('Unsupported time limit category');
            }
            $limit = match ($newTimeLimit) {
                1 => 60,
                2 => 600,
                3 => 3600,
                4 => 7200,
            };
            set_time_limit($limit);
        }
        if ($newMemoryLimit !== null) {
            ini_set("memory_limit", $newMemoryLimit . "M");
        }
    }

    /**
     * Add a postgres database connection
     * @param string $id
     * @param string $database
     * @param string $host
     * @param string|null $username
     * @param string|null $password
     * @param int|null $port
     * @return void
     */
    public static function addPostgresConnection(
        string $id,
        string $database,
        string $host,
        string|null $username = null,
        #[SensitiveParameter] string|null $password = null,
        int|null $port = null
    ): void {
        Config::$sqlConnections[$id] = [
            'type' => Sql::TYPE_POSTGRES,
            'id' => $id,
            'host' => $host,
            'port' => $port,
            'username' => $username,
            'password' => $password,
            'database' => $database,
        ];
    }

    /**
     * Add a mysql database connection
     * @param string $id
     * @param string $database
     * @param string $host
     * @param string|null $username
     * @param string|null $password
     * @param int|null $port
     * @param string|null $socket
     * @return void
     */
    public static function addMysqlConnection(
        string $id,
        string $database,
        string $host,
        string|null $username = null,
        #[SensitiveParameter] string|null $password = null,
        int|null $port = null,
        string|null $socket = null
    ): void {
        Config::$sqlConnections[$id] = [
            'type' => Sql::TYPE_MYSQL,
            'id' => $id,
            'host' => $host,
            'port' => $port,
            'username' => $username,
            'password' => $password,
            'database' => $database,
            'socket' => $socket,
        ];
    }

    /**
     * Add a sqlite database connection
     * @param string $id
     * @param string $path
     * @return void
     */
    public static function addSqliteConnection(
        string $id,
        string $path
    ): void {
        Config::$sqlConnections[$id] = [
            'type' => Sql::TYPE_SQLITE,
            'id' => $id,
            'path' => $path,
        ];
    }

    /**
     * Add captcha key
     * @param string $type
     * @param string $privateKey
     * @param string $publicKey
     * @param array|null $additionalData
     * @return void
     */
    public static function addCaptchaKey(
        #[ExpectedValues(valuesFromClass: Captcha::class)] string $type,
        #[SensitiveParameter] string $privateKey,
        string $publicKey,
        ?array $additionalData = null
    ): void {
        self::$captchaKeys[$type] = [
            'privateKey' => $privateKey,
            'publicKey' => $publicKey,
            'additionalData' => $additionalData,
        ];
    }

    /**
     * Add a hash salt for CryptoUtils
     * @param string $salt
     * @param string $saltId The salt id. A salt with 'default' must always exist
     * @return void
     */
    public static function addSalt(string $salt, string $saltId = 'default'): void
    {
        self::$salts[$saltId] = $salt;
    }

    /**
     * Create and add a new compiler file bundle
     * @param string $module Framelix module name
     * @param string $type
     * @param string $bundleId
     * @return CompilerFileBundle
     */
    public static function createCompilerFileBundle(
        string $module,
        #[ExpectedValues(['js', 'scss'])] string $type,
        string $bundleId
    ): CompilerFileBundle {
        $bundle = new CompilerFileBundle($module, $type, $bundleId);
        self::$compilerFileBundles[$module . "-" . $type . "-" . $bundleId] = $bundle;
        return $bundle;
    }

    /**
     * Get existing (previously created) compiler file bundle
     * @param string $module Framelix module name
     * @param string $type
     * @param string $bundleId
     * @return CompilerFileBundle|null
     */
    public static function getCompilerFileBundle(
        string $module,
        #[ExpectedValues(['js', 'scss'])] string $type,
        string $bundleId
    ): ?CompilerFileBundle {
        return self::$compilerFileBundles[$module . "-" . $type . "-" . $bundleId] ?? null;
    }

    /**
     * Add an availble user role
     * @param string $id
     * @param string $langKey
     * @return void
     */
    public static function addAvailableUserRole(string $id, string $langKey): void
    {
        self::$userRoles[$id] = ["id" => $id, "langKey" => $langKey];
    }

    /**
     * Get the file to the modules config file, placed in userdata/$module/private folder
     * @param string $module
     * @return string
     */
    public static function getUserConfigFilePath(string $module = FRAMELIX_MODULE): string
    {
        return FileUtils::getUserdataFilepath("config/01-app.php", false, $module);
    }

    /**
     * Get the file to the modules config file, placed in userdata/$module/private folder
     * @param string $module
     * @return bool
     */
    public static function doesUserConfigFileExist(string $module = FRAMELIX_MODULE): bool
    {
        return file_exists(self::getUserConfigFilePath($module));
    }

    /**
     * Create minimal initial user config files to be able to use the application
     * Used in setup via web interface as well
     * Will throw an error if config file aready exist
     * @param string $module
     * @param string $defaultSalt
     * @param string $applicationHost
     * @param string $applicationUrlPrefix
     */
    public static function createInitialUserConfig(
        string $module,
        string $defaultSalt,
        string $applicationHost,
        string $applicationUrlPrefix
    ): void {
        $userConfigFile = Config::getUserConfigFilePath($module);
        if (file_exists($userConfigFile)) {
            throw new FatalError("Config file already exists");
        }
        $fileContents = [
            "<?php",
            "// this file contains overrides and custom configuration for all default settings",
            "// this file should contain data that varies from instance to instance (like server vs. local development)",
            "// database connections, urls, salts and all that sensible stuff belongs to here",
            "\\Framelix\\Framelix\\Config::addSalt('" . $defaultSalt . "');",
            "\\Framelix\\Framelix\\Config::\$applicationHost = '" . $applicationHost . "';",
            "\\Framelix\\Framelix\\Config::\$applicationUrlPrefix = '" . $applicationUrlPrefix . "';",
        ];
        file_put_contents($userConfigFile, implode("\n", $fileContents));
    }

}