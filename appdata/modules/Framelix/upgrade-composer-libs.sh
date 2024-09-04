#!/usr/bin/bash

SCRIPTDIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" &>/dev/null && pwd)
cd $SCRIPTDIR
rm composer.lock compeser.json
composer require robthree/twofactorauth lbuchs/webauthn mpdf/qrcode phpoffice/phpspreadsheet mpdf/mpdf phpmailer/phpmailer brainfoolong/js-aes-php