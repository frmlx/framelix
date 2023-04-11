#!/usr/bin/bash

SCRIPTDIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" &>/dev/null && pwd)
source $SCRIPTDIR/lib.sh

cecho b "Build Docker Image for $DOCKER_TAGNAME_LOCAL"
echo "Available command line flags:"
echo "-t : Type of build: dev, prod"

BUILD_TYPE=0
while getopts "t:" opt; do
  case $opt in
  t) BUILD_TYPE=$OPTARG ;;
  esac
done


if [ "$BUILD_TYPE" != "dev" ] && [ "$BUILD_TYPE" != "prod" ]; then
  echo "Please specify build type 'dev' or 'prod'"
  exit 1
fi

source $SCRIPTDIR/stop-container.sh
docker image rm $DOCKER_TAGNAME_LOCAL

TMPFOLDER=$SCRIPTDIR/../tmp/appdata_dev
rm -Rf $TMPFOLDER
mkdir -p $TMPFOLDER

if [ "$BUILD_TYPE" == "dev" ]; then
  cecho b "# Copy required appdata dev which the build process integrates into the container"
  SRCFOLDER=$SCRIPTDIR/../appdata
  mkdir -p $TMPFOLDER/modules
  cp  $SRCFOLDER/* $TMPFOLDER > /dev/null 2>&1
  cp -R $SRCFOLDER/playwright $TMPFOLDER/playwright
  cp -R $SRCFOLDER/modules/Framelix $TMPFOLDER/modules/Framelix
  cp -R $SRCFOLDER/modules/FramelixDocs $TMPFOLDER/modules/FramelixDocs
  cp -R $SRCFOLDER/modules/FramelixStarter $TMPFOLDER/modules/FramelixStarter
fi

docker build -t $DOCKER_TAGNAME_LOCAL --build-arg "FRAMELIX_BUILD_TYPE=$BUILD_TYPE" --build-arg "FRAMELIX_BUILD_VERSION=$VERSION" $SCRIPTDIR/..

if [ "$?" != "0" ]; then
  cecho r "Build failed"
  exit 1
fi
