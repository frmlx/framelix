#!/usr/bin/bash

SCRIPTDIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" &>/dev/null && pwd)
source $SCRIPTDIR/lib.sh

cecho b "Build Image for $DOCKER_TAGNAME_LOCAL"
echo "Available command line flags:"
echo "-v : Version of build: dev/master, or tagname"

BUILD_VERSION=0
USECACHE=""
while getopts "v:n" opt; do
  case $opt in
  v) BUILD_VERSION=$OPTARG ;;
  n) USECACHE="--no-cache" ;;
  esac
done

if [ "$BUILD_VERSION" == "0" ]; then
  cecho r "-v parameter is required"
  exit 1
fi

echo "Version selected: $BUILD_VERSION"

source $SCRIPTDIR/stop-container.sh
$DOCKER_CMD image rm $DOCKER_TAGNAME_LOCAL
if [ "$DOCKER_CMD" == "podman" ]; then
  $DOCKER_CMD build --format docker -t $DOCKER_TAGNAME_LOCAL --build-arg "FRAMELIX_BUILD_VERSION=$BUILD_VERSION" $USECACHE $SCRIPTDIR/..
else
  $DOCKER_CMD build -t $DOCKER_TAGNAME_LOCAL --build-arg "FRAMELIX_BUILD_VERSION=$BUILD_VERSION" $USECACHE $SCRIPTDIR/..
fi

if [ "$?" != "0" ]; then
  cecho r "Build failed"
  exit 1
fi
