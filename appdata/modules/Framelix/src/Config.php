<?php

namespace Framelix\Framelix;

use Framelix\Framelix\Form\Field\Captcha;
use Framelix\Framelix\Form\Field\Email;
use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Field\Number;
use Framelix\Framelix\Form\Field\Password;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Text;
use Framelix\Framelix\Form\Field\Textarea;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\CompilerFileBundle;
use Framelix\Framelix\Storable\SystemEventLog;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\JsonUtils;
use JetBrains\PhpStorm\ExpectedValues;
use SensitiveParameter;

use function file_exists;

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
        SystemEventLog::CATEGORY_LOGIN_SUCCESS => true
    ];

    /**
     * How long should logs be kept
     * @var int[]
     */
    public static array $enabledBuiltInSystemEventLogsKeepDays = [
        SystemEventLog::CATEGORY_LOGIN_FAILED => 60,
        SystemEventLog::CATEGORY_LOGIN_SUCCESS => 60
    ];

    /**
     * Automatic db backup interval (0=disabled)
     * @var int
     */
    public static int $automaticDbBackupInterval = 1;

    /**
     * Automatic db backup how much logs to keep
     * @var int
     */
    public static int $automaticDbBackupMaxLogs = 7;

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
     * e.g: The app startpoint is installed at yourdomain.com/myapplication, then you have to set prefix to "myapplication"
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
     * All available database connections
     * Add new by self::addDbConnection()
     * @var array
     */
    public static array $dbConnections = [];

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
     * Called when the module is registered the first time
     * This is used for module defaults
     * @return void
     */
    public static function onRegister(): void
    {
        // add a default salt, after application is setup, this default salt must be replaced
        // app requires any salt to be functional, even during setup
        self::addSalt('none');

        self::$environmentConfig = JsonUtils::readFromFile("/framelix/system/environment.json");

        // set default mysql db connection for current module
        self::addDbConnection(FRAMELIX_MODULE, 'localhost', 3306, 'root', 'app', FRAMELIX_MODULE);

        // register the other module
        Framelix::registerModule(FRAMELIX_MODULE);

        self::addAvailableUserRole('admin', '__framelix_user_role_admin__');
        self::addAvailableUserRole('dev', '__framelix_user_role_dev__');
        self::addAvailableUserRole('usermanagement', '__framelix_edituser_sidebar_title__');

        $bundle = self::createCompilerFileBundle("Framelix", "js", "form");
        $bundle->addFile('js/form/framelix-form.js');
        $bundle->addFile('js/form/framelix-form-field.js');
        $bundle->addFile('js/form/framelix-form-field-text.js');
        $bundle->addFile('js/form/framelix-form-field-select.js');
        $bundle->addFolder('js/form', true);

        $bundle = self::createCompilerFileBundle("Framelix", "js", "general-vendor-native");
        $bundle->compile = false;
        $bundle->addFile('node_modules/cash-dom/dist/cash.min.js');
        $bundle->addFile('vendor-frontend/cashjs/cash-improvements.js');
        $bundle->addFile('vendor-frontend/dayjs/dayjs.min.js');
        $bundle->addFolder('vendor-frontend/dayjs', true);
        $bundle->addFile('node_modules/form-data-json-convert/dist/form-data-json.min.js');
        $bundle->addFile('node_modules/@popperjs/core/dist/umd/popper.min.js');
        $bundle->addFile('node_modules/swiped-events/dist/swiped-events.min.js');

        $bundle = self::createCompilerFileBundle("Framelix", "js", "sortablejs");
        $bundle->compile = false;
        $bundle->pageAutoInclude = false;
        $bundle->addFile('node_modules/sortablejs/Sortable.min.js');

        $bundle = self::createCompilerFileBundle("Framelix", "js", "qrcodejs");
        $bundle->compile = false;
        $bundle->pageAutoInclude = false;
        $bundle->addFolder('vendor-frontend/qrcodejs', true);

        $bundle = self::createCompilerFileBundle("Framelix", "js", "general-early");
        $bundle->pageAutoInclude = false;
        $bundle->addFile('js/framelix-local-storage.js');
        $bundle->addFile('js/framelix-session-storage.js');
        $bundle->addFile('js/framelix-device-detection.js');

        $bundle = self::createCompilerFileBundle("Framelix", "js", "general");
        $bundle->addFolder('js', false, [
            "framelix-table-sort-serviceworker.js",
            "framelix-device-detection.js",
            "framelix-local-storage.js",
            "framelix-session-storage.js"
        ]);
        $bundle->addFile('custom-elements/framelix-custom-element.js');
        $bundle->addFolder('custom-elements', true);

        $bundle = self::createCompilerFileBundle("Framelix", "js", "table-sorter");
        $bundle->pageAutoInclude = false;
        $bundle->addFile('js/framelix-table-sort-serviceworker.js');

        $bundle = self::createCompilerFileBundle("Framelix", "js", "backend");
        $bundle->pageAutoInclude = false;
        $bundle->addFolder('js/backend', true);

        $bundle = self::createCompilerFileBundle("Framelix", "scss", "general");
        $bundle->addFolder('scss/general', false);
        $bundle->addFolder('custom-elements', true);

        $bundle = self::createCompilerFileBundle("Framelix", "scss", "form");
        $bundle->addFolder('scss/form', true);

        $bundle = self::createCompilerFileBundle("Framelix", "scss", "backend");
        $bundle->pageAutoInclude = false;
        $bundle->addFolder('scss/backend', true, ["framelix-backend-fonts.scss"]);

        $bundle = self::createCompilerFileBundle("Framelix", "scss", "backend-fonts");
        $bundle->pageAutoInclude = false;
        $bundle->addFile("scss/backend/framelix-backend-fonts.scss");
    }

    /**
     * Get form that allow config values to be edited via admin web interface
     * All config keys that not have a field with this name will not be editable in the UI
     * @return Form
     */
    public static function getEditableConfigForm(): Form
    {
        $form = new Form();

        $field = new Select();
        $field->name = "language";
        $field->required = true;
        foreach (Config::$languagesAvailable as $language) {
            $field->addOption($language, $language);
        }
        $form->addField($field);

        $field = new Select();
        $field->name = "languageFallback";
        $field->required = true;
        foreach (Config::$languagesAvailable as $language) {
            $field->addOption($language, $language);
        }
        $form->addField($field);

        $field = new Select();
        $field->addOption(Captcha::TYPE_RECAPTCHA_V2, "ReCaptchaV2");
        $field->addOption(Captcha::TYPE_RECAPTCHA_V3, "ReCaptchaV3 + ReCaptchaV2");
        $field->name = "captchaType";
        $form->addField($field);

        $field = new Toggle();
        $field->name = "backendLoginCaptcha";
        $field->getVisibilityCondition()->equal('captchaType', [Captcha::TYPE_RECAPTCHA_V2, Captcha::TYPE_RECAPTCHA_V3]
        );
        $form->addField($field);

        $field = new Text();
        $field->name = "captchaKeys[" . Captcha::TYPE_RECAPTCHA_V2 . "][privateKey]";
        $field->getVisibilityCondition()->equal('captchaType', [Captcha::TYPE_RECAPTCHA_V2, Captcha::TYPE_RECAPTCHA_V3]
        );
        $field->required = true;
        $form->addField($field);

        $field = new Text();
        $field->name = "captchaKeys[" . Captcha::TYPE_RECAPTCHA_V2 . "][publicKey]";
        $field->getVisibilityCondition()->equal('captchaType', [Captcha::TYPE_RECAPTCHA_V2, Captcha::TYPE_RECAPTCHA_V3]
        );
        $field->required = true;
        $form->addField($field);

        $field = new Text();
        $field->name = "captchaKeys[" . Captcha::TYPE_RECAPTCHA_V3 . "][privateKey]";
        $field->getVisibilityCondition()->equal('captchaType', Captcha::TYPE_RECAPTCHA_V3);
        $field->required = true;
        $form->addField($field);

        $field = new Text();
        $field->name = "captchaKeys[" . Captcha::TYPE_RECAPTCHA_V3 . "][publicKey]";
        $field->getVisibilityCondition()->equal('captchaType', Captcha::TYPE_RECAPTCHA_V3);
        $field->required = true;
        $form->addField($field);

        $field = new Select();
        $field->name = "emailSendType";
        $field->chooseOptionLabel = '__framelix_configuration_module_email_emailsendtype_none__';
        $field->addOption('smtp', '__framelix_configuration_module_email_emailsendtype_smtp__');
        $form->addField($field);

        $field = new Text();
        $field->name = "emailSmtpHost";
        $field->required = true;
        $field->getVisibilityCondition()->equal('emailSendType', 'smtp');
        $form->addField($field);

        $field = new Number();
        $field->name = "emailSmtpPort";
        $field->decimals = 0;
        $field->required = true;
        $field->getVisibilityCondition()->equal('emailSendType', 'smtp');
        $form->addField($field);

        $field = new Text();
        $field->name = "emailSmtpUsername";
        $field->required = true;
        $field->getVisibilityCondition()->equal('emailSendType', 'smtp');
        $form->addField($field);

        $field = new Password();
        $field->name = "emailSmtpPassword";
        $field->getVisibilityCondition()->equal('emailSendType', 'smtp');
        $form->addField($field);

        $field = new Select();
        $field->name = "emailSmtpSecure";
        $field->addOption('tls', "TLS");
        $field->addOption('ssl', "SSL");
        $field->getVisibilityCondition()->equal('emailSendType', 'smtp');
        $form->addField($field);

        $field = new Email();
        $field->name = "emailDefaultFrom";
        $field->required = true;
        $field->getVisibilityCondition()->notEmpty('emailSendType');
        $form->addField($field);

        $field = new Email();
        $field->name = "emailOverrideRecipient";
        $field->getVisibilityCondition()->notEmpty('emailSendType');
        $form->addField($field);

        $field = new Text();
        $field->name = "emailSubject";
        $field->getVisibilityCondition()->notEmpty('emailSendType');
        $form->addField($field);

        $field = new Textarea();
        $field->name = "emailBody";
        $field->getVisibilityCondition()->notEmpty('emailSendType');
        $form->addField($field);

        $field = new Toggle();
        $field->name = "errorLogExtended";
        $form->addField($field);

        $field = new Email();
        $field->name = "errorLogEmail";
        $form->addField($field);

        $field = new Html();
        $field->name = "enabledBuiltInSystemEventLogsInfo";
        $field->label = '';
        $field->labelDescription = '__framelix_config_enabledbuiltinsystemeventlogs_labeldesc__';
        $form->addField($field);

        for ($i = SystemEventLog::CATEGORY_STORABLE_CREATED; $i <= SystemEventLog::CATEGORY_LOGIN_SUCCESS; $i++) {
            $field = new Toggle();
            $field->name = "enabledBuiltInSystemEventLogs[$i]";
            $form->addField($field);

            $field = new Number();
            $field->setIntegerOnly();
            $field->name = "enabledBuiltInSystemEventLogsKeepDays[$i]";
            $field->label = '__framelix_config_enabledbuiltinsystemeventlogsKeepDays_label__';
            $field->getVisibilityCondition()->equal("enabledBuiltInSystemEventLogs[$i]", 1);
            $form->addField($field);
        }
        $field = new Number();
        $field->setIntegerOnly();
        $field->name = 'automaticDbBackupInterval';
        $form->addField($field);

        $field = new Number();
        $field->setIntegerOnly();
        $field->name = 'automaticDbBackupMaxLogs';
        $form->addField($field);

        return $form;
    }

    /**
     * Add a database connection
     * @param string $id
     * @param string $host
     * @param int|null $port Default is 3306
     * @param string $username
     * @param string $password
     * @param string $database
     * @param string|null $socket
     * @return void
     */
    public static function addDbConnection(
        string $id,
        string $host,
        int|null $port,
        string $username,
        #[SensitiveParameter] string $password,
        string $database,
        string|null $socket = null
    ): void {
        self::$dbConnections[$id] = [
            'id' => $id,
            'host' => $host,
            'port' => $port,
            'username' => $username,
            'password' => $password,
            'database' => $database,
            'socket' => $socket
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
            'additionalData' => $additionalData
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
     * @param string $id
     * @param string $module
     * @return string
     */
    public static function getUserConfigFilePath(
        #[ExpectedValues(values: ['01-core', '02-ui', '03-custom'])] string $id = "01-core",
        string $module = FRAMELIX_MODULE
    ): string {
        return FileUtils::getUserdataFilepath(
            "config/$id.php",
            false,
            $module
        );
    }

    /**
     * Get the file to the modules config file, placed in userdata/$module/private folder
     * @param string $id
     * @param string $module
     * @return bool
     */
    public static function doesUserConfigFileExist(
        #[ExpectedValues(values: ['01-core', '02-ui', '03-custom'])] string $id = "01-core",
        string $module = FRAMELIX_MODULE
    ): bool {
        return file_exists(self::getUserConfigFilePath($id, $module));
    }
}