# [4.0.0 - TBA]

⚠️ **BREAKING CHANGE** ⚠️: Combined 3 repositories (tests, core, docker) to one, called `framelix`. This make maintenance, further development and end-user usage a lot easier. The way you must setup your docker installation have changed. However, a working installation of v3.x can be upgraded with a bit of effort.


### 🎯 Framelix core (backend/frontend) changes

⚠️ **BREAKING CHANGE** ⚠️: All changes marked with ⚠️ are breaking changes. Database handling have also changed. The previous default DB `app` is not being generated and used anymore. Each module instance have it's own database, named by the module name by default. So `default` database connection also not exist anymore. To migrate, you must rename the `app` database to the module name of your instance.

* ➕ added property to force screen size and color scheme for layout view
* ➕ added `$hiddenView` property to `View` to allow view to be hidden from public access
* 🛠️ fixed minor layout glitch in small size mode
* 🛠️ fixed many tests and core code that failed because of now better error detection of new PhpUnit
* ✏️ ⚠️ renamed `Time` functions `timeStringToHours->toHours, timeStringToSeconds->toSeconds` and make conversion more lazy (accepting more types of values)
* ✏️ ⚠️ changed database handling so each module instance have it's own separate database, the default `app` database is being dropped
* ✏️ ⚠️ changed database handling to abstract SQL and make place for other types like mssql, sqlite, postgreesql
* ✏️ changed to hidden sidebar by default for some small views (Login, ForgotPassword, etc..)
* ✏️ changed internals of how app is set up (reduced checks, more streamlined, easier maintenance)
* ✏️ changed some vendor frontend libraries to package.json instead of manual installation
* ✏️ changed backend layout to be more flexible
* ❌ removed old unsupported setup vars
* ❌ removed `Config::$appSetupDone`, so you have to update your `01-core.php` config and remove it there manually
* ️⬆️ upgraded PhpUnit to v10, PhpStan, Playwright, and various other integrated vendor libs
* ️⬆️ upgraded several other frontend libraries (cashjs, sortablejs, popperjs, etc...)

### 🐳 Docker container changes

* ➕ added build ARG `OS_IMAGE` to be able to use another base ubuntu image (experimental). So you can install this also on a Raspberry PI for example, which have ARM architecture.
* ✏️ reworked many internals of how the image works and streamlined build, test and development process - most notable the `.env` file has changed variables, so you have to update yours according to the templates
* ⬆️ upgraded mariadb to v10.11.2 (from 10.6.12)
* ⬆️ upgraded NodeJS to v19.8.1 (from 18.14.0)
* ⬆️ upgraded PHP to v8.2.4 (from 8.2.2)

#### 📚 Installed server applications are now:
  * Nginx: nginx version: nginx/1.23.3
  * MariaDB: mysql  Ver 15.1 Distrib 10.11.2-MariaDB, for debian-linux-gnu (x86_64) using  EditLine wrapper
  * PHP: 8.2.4
  * NodeJS: v19.8.1


# [3.1.1 - 2023-02-13]

* integrated playwright installation into docker build and entrypoint runner (as already done with phpunit tests)
* clean some build deps and separate them better
* removed optional volume instruction for appdata volume
* upgrade for server versions to
    * Nginx: nginx/1.23.3
    * MariaDB: mysql Ver 15.1 Distrib 10.6.12-MariaDB, for debian-linux-gnu (x86_64) using EditLine wrapper
    * PHP: 8.2.2
    * NodeJS: v18.14.0

### 🎯 Framelix core (backend/frontend) changes (3.1.1 and 3.1.0)

* fixed bug in prefetch storables with different types
* added user id attribute to `<html>` in backend view
* added prefix `framelix-form` to form id's
* removed 500 error when setup is done
* removed database safe query execute in setup view as it's already done on warmup
* changed captcha validation to use browser instead of raw stream to fix some connection issues
* fixed a few datepicker styling issues
* fixed framelix popup color management
* fixed backend layout type 2 overflow
* fixed default api view cannot be overriden
* other internal minor fixes and improvements

# [3.0.1 - 2022-12-30]

* added missing `apt update` before installing x-debug

# [3.0.0 - 2022-12-09]

* added `healthcheck` to dockerfile
* upgrade to php 8.2

### 🎯 Framelix core (backend/frontend) 

* added `<framelix-alert>` custom element
* added `loadUrlOnChange` to `Select` field
* added some handy condition generators into `Mysql`
* added `isReadable` to `Storable` and updated several things to respect this new flag
* added `PhpToJsData::renderToHtml` for a more generic way to render php to html/js counterparts
* renamed `configTest` console command to `healthCheck`
* renamed `getEditUrl` to `getDetailsUrl` (breaking change)
* changed `isDeletable` defaults now to `isEditable` return value (attention change)
* changed `Storable->store` to require `isEditable` true to be able to store a storable
* changed backend layout to have an always visible top-bar
* changed `Date` field to use own datepicker instead of native one
* fixed `Search` field wanky result behaviour
* fixed timing of popup destroy
* added `__noblur` to `Framelix.redirect()` to fix blur issue with file downloads
* updated (and removed) to verbose php doc comments
* updated material icons
* some styling improvements
* upgraded some vendor files

# [2.4.0 - 2022-11-22]

* added better (newer) mariadb source to use latest bugfix release in LTS version, which at the time include some
  required bugfix updates to crash and dump related mariadb issues
* added `mysql_upgrade` job to mariadb startup in case an upgrade has happened
* added `php8.1-sqlite3` and `php8.1-pgsql` modules

# [2.3.3 - 2022-10-31]

Happy Halloween.

* added cleanup before warmup, removing old version info files

# [2.3.2 - 2022-10-27]

* changed install app script to remove not needed .git folders and to use only production npm modules
* optimized cron log

# [2.3.1 - 2022-10-26]

* fixed form spacer after changed form fields hierarchy

# [2.3.0 - 2022-10-25]

* added `framelix_console` script
* added `framelix_backup_db` script parameter to pass a backup filename as parameter
* added new config flags for how long to keep system event logs and backups
* added `MediaBrowser` field
* added `StorableFolder` and `StorableFile` for new `MediaBrowser`
* added `Archiver` for pack/unpack archive files
* added `ImageUtils` for fast resizing, conversation and comparison of images
* added `framelix_intall_app` `-q` quiet parameter to not throw an error when no args are provided
* added blur window when `Framelix.redirect()` and deactivated pointer events for better user feedback on slow con
* added support for custom elements and make use if `<framelix-button>` and `<framelix-image>` in backend
* added childs fetch for `Storables`
* added `store` method to forms and fields for more flexible Storage handling
* added more flexible positioning and sizing of forms and fields
* changed JS translation loading to directly load json files to make use of browser caching instead of packing that code
  into the main view
* removed unsigned jscall request support
* enabled gzip for static files in nginx for a lot less data transfer volume
* improved readability of docker console messages
* refactored `FramelixModal` to use html native `<dialog>`
* refactored and streamlined php to javascript data transfer
* a lot of smaller changes in UI and usability

# [2.1.2 - 2022-10-14]

* complete rework to docker native way instead of docker-compose
* almost refactored the whole framework, so it is considered to be all new

# [1.9.0 - 2022-06-15]

* some cleanup and removements of old files
* added dev role and set dev pages under this dev role
* added `Shell->getOutput()` for nice formatting
* fixed modal width on small screens
* fixed bug docker update will still be marked as available after update
* fixed remember active tab bug with multiple tabs instances on same page
* removed content-length header for response download to fix issues with corrupt downloads

# [1.8.0 - 2022-05-13]

* changed hidden form submit names, now prefixed with framelix-form-
* fixed wrong redirect after login, when a custom redirect is defined
* fixed backend sidebar overflow when text is too long
* fixed bug with error handler show dupe errors because of StopException catch
* fixed field visibility condition for not* conditions
* fixed number format/parse
* fixed a fiew field layout issues
* fixed FramelixDom.isInDom() with some elements
* fixed setStorableValues in case of storableFile properties without a fileUpload
* fixed FramelixColorUtils.hexToRgb returning object instead of array
* optimized email settings
* optimized FramelixModal for different screen sizes
* views with regex in url now remove parameters that are not used when generating urls
* backend pages now by default need a user to be logged in
* added Lang::concatKeys to easily concat lang keys
* added setIntegerOnly() to number field
* added noAnimation option to FramelixModal
* added QuickSearch->forceInitialQuery to set a initial query no matter what the user have stored
* added fieldGroups to Forms, to be able to group fields under a collapsable
* added `Tar` class to create and extract tar files
* added JsCallUnsigned to call handcrafted PHP methods from frontend without a correctly backend signed url
* refactored language handling for more flexible way to load and add values
* refactored update and release process
* removed a few unused user roles
* removed release build script in favor of new https://github.com/NullixAT/framelix-release action

# [1.7.0 - 2022-02-04]

* design refactoring to be more modern and clear

### :pencil: Changed

* changed url anti cache parameter to be always included instead of only 7 days to fix fallback to old cache when
  parameter gets removed

### :heart: Added

* added maxWidth option to framelix modal and use it for alert, confirm and prompt by default

### :pencil: Changed

* changed url anti cache parameter to be always included instead of only 7 days to fix fallback to old cache when
  parameter gets removed

### :wrench: Fixed

* fixed modal window prompt enter key not working
* fixed url signature check

# [1.6.2 - 2022-03-08]

### :heart: Added

* added maxWidth option to framelix modal and use it for alert, confirm and prompt by default

### :pencil: Changed

* changed url anti cache parameter to be always included instead of only 7 days to fix fallback to old cache when
  parameter gets removed

### :wrench: Fixed

* fixed modal window prompt enter key not working
* fixed url signature check

### :police_car: Security

# [1.6.1 - 2022-02-17]

### :heart: Added

### :pencil: Changed

* changed language key syntax for singular/plurar to must include the number itself for more flexibility
* changed sass compiler to `sass` instead of deprecated `node-sass`
* changed default value for `captchaScoreThreshold` in default config

### :construction: Deprecated

### :x: Removed

### :wrench: Fixed

* fixed typo in `captchaScoreTreshold`
* fixed error when app update throws an error during update result in update never work again because tmp folder was not
  cleared

### :police_car: Security

# [1.5.0 - 2022-02-09]

### :heart: Added

* added config key backendDefaultView which will point to default backend view after login
* added application and module version info to systemcheck page
* added userpwd and requestBody to browser

### :pencil: Changed

* upgraded node-sass compiler and babel compiler to newest version
* updated backend small layout a bit, so it has a blurry bg
* changed modal window now use semi-transparent page in background instead of blur

### :construction: Deprecated

### :x: Removed

* module config key setupDoneRedirect and replaced it with "backendDefaultView"

### :wrench: Fixed

* fixed typo in Config function
* fixed modal window blur filter will result in repaint a "broken" sidebar

### :police_car: Security

# [1.4.0 - 2022-02-08]

a lot of updates and fixes for backend and general framework

### :heart: Added

* added resort actions to developer language editor to resort keys and update lang files easily
* added headHtmlAfterInit
* added getHtmlTableValue for ObjectTransformable
* added cron to delete system event logs

### :pencil: Changed

* system event log page to be
* improved and simplified use of quick search

### :construction: Deprecated

### :x: Removed

* removed grid field as it was flaky and hard to use on mobile

### :wrench: Fixed

* fixed framelix-string-utils -> slugify replacing only one char
* fixed a lot of layout issues
* fixed layout issues with fields that uses buttons

### :police_car: Security

