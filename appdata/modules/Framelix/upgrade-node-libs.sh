#!/usr/bin/bash

SCRIPTDIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" &>/dev/null && pwd)
cd $SCRIPTDIR
rm -f package-lock.json
rm -f bun.lock
bun install  @babel/core @babel/preset-env @popperjs/core cash-dom dayjs form-data-json-convert qrcodejs sass sharp sortablejs swiped-events