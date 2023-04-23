#!/usr/bin/bash

SCRIPTDIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" &>/dev/null && pwd)
source $SCRIPTDIR/lib.sh

cecho b "Build Docker Image for $DOCKER_TAGNAME_LOCAL"
echo "Available command line flags:"
echo "-v : Version of build: dev/master, or tagname"

BUILD_VERSION=0
while getopts "v:" opt; do
  case $opt in
  v) BUILD_VERSION=$OPTARG ;;
  esac
done

if [ "$BUILD_VERSION" == "0" ]; then
  cecho r "-v parameter is required"
  exit 1
fi

source $SCRIPTDIR/stop-container.sh
docker image rm $DOCKER_TAGNAME_LOCAL
docker build -t $DOCKER_TAGNAME_LOCAL --build-arg "FRAMELIX_BUILD_VERSION=$BUILD_VERSION" $SCRIPTDIR/..

if [ "$?" != "0" ]; then
  cecho r "Build failed"
  exit 1
fi
