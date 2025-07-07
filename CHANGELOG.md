# [4.0.0-alpha.26 - TBA]

* upgraded to nginx version: nginx/1.28.0
* upgraded to PHP: 8.4.8
* upgraded vendor libraries
* added `framelix_custom_boot` to be able to add any custom startup script
* added more configuration files to be able to modify nginx behaviour
* changed from docker hub package hosting to ghcr.io package hosting
* fixed permissions on boot
* removed default nodejs starter script in favor to `framelix_custom_boot`
* removed `nodejs` and preinstall `bun` instead
*  a lot more
* https://github.com/frmlx/framelix/compare/4.0.0-alpha.25...4.0.0-alpha.26


# [4.0.0-alpha.25 - 2025-03-28]

* fixed container no restart after shutdown
* fixed robots tag for docs pages
* https://github.com/frmlx/framelix/compare/4.0.0-alpha.24...4.0.0-alpha.25


# [4.0.0-alpha.24 - 2025-03-17]

* updated to php v8.4.5
* added phpunit single test file possibility
* fixed Response::download memory issue
* https://github.com/frmlx/framelix/compare/4.0.0-alpha.23...4.0.0-alpha.24


# [4.0.0-alpha.23 - 2025-03-12]

* updated to php v8.4.4
* updated dependencies
* updated to playwright v1.51.0
* https://github.com/frmlx/framelix/compare/4.0.0-alpha.22...4.0.0-alpha.23


# [4.0.0-alpha.22 - 2024-11-28]

* upgrade to php 8.4 and ubuntu 24.04
* updated dependencies
* https://github.com/frmlx/framelix/compare/4.0.0-alpha.21...4.0.0-alpha.22

# [4.0.0-alpha.21 - 2024-09-04]

* added ImageUtils::compress
* replaced brainfoolong/cryptojs-aes-php with brainfoolong/js-aes-php
* updated dependencies
* https://github.com/frmlx/framelix/compare/4.0.0-alpha.20...4.0.0-alpha.21

# [4.0.0-alpha.20 - 2024-07-24]

* updated dependencies
* https://github.com/frmlx/framelix/compare/4.0.0-alpha.19...4.0.0-alpha.20

# [4.0.0-alpha.19 - 2024-04-13]

* a lot of updates, features and refactoring
* https://github.com/frmlx/framelix/compare/4.0.0-alpha.18...4.0.0-alpha.19

# [4.0.0-alpha.18 - 2024-04-08]

* a lot of updates, features and refactoring
* https://github.com/frmlx/framelix/compare/4.0.0-alpha.17...4.0.0-alpha.18

# [4.0.0-alpha.17 - 2024-04-01]

* restructured src folders for js/scss
* fixed some cache-control issues
* upgraded libraries and vendor

# [4.0.0-alpha.16 - 2023-10-15]

* fixed bug in JS compiler
* fixed missing JS/CSS includes in docs

# [4.0.0-alpha.15 - 2023-10-13]

* refactored `Compiler and Bundler`
* refactored `StorableFile` and added more features for images to it
* added custom `Session` handling
* removed deprecated `MediaBrowser` field
* upgraded vendor libs
* upgraded PHP to 8.3.1
* many minor fixes and improvements


Older changelogs can be found in history