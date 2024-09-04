#!/usr/bin/bash

SCRIPTDIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" &>/dev/null && pwd)
cd $SCRIPTDIR
rm package-lock.json
npm i  @babel/core @babel/preset-env @popperjs/core cash-dom dayjs form-data-json-convert qrcodejs sass sharp sortablejs swiped-events