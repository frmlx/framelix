#!/usr/bin/bash

SCRIPTDIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" &>/dev/null && pwd)
source $SCRIPTDIR/lib.sh

# just pull all modules and main repos at once

if test -d "$ROOTDIR/.git"; then
  echo "Pull $ROOTDIR"
  git -C "$ROOTDIR" pull
fi

echo "Pull $ROOTDIR/dev-scripts"
git -C "$ROOTDIR/dev-scripts" pull

if test -d "$ROOTDIR/appdata/.git"; then
  echo "Pull $ROOTDIR/appdata"
  git -C "$ROOTDIR/appdata" pull
fi

for d in $ROOTDIR/appdata/modules/*/; do
  echo "Pull $d"
  git -C "$d" pull
done
