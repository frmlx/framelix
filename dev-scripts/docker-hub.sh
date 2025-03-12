#!/usr/bin/bash

SCRIPTDIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" &>/dev/null && pwd)
source $SCRIPTDIR/lib.sh

cecho b "Build/Push image to Docker Hub"
echo "This script does:"
echo "  * Rebuild the image $DOCKER_TAGNAME_LOCAL, run all tests and only on success will be able to push the image"
echo "  * Deletes all local userdata and db volume"
echo "  * Runs all tests"
echo "  * If all success, be able to push to docker"
echo "Available command line flags:"
echo "-v : Version of build: dev/master, or tagname"
echo "-p : Push to docker hub (If not set, it only build and tests the image, a dry run, so to speak)"
echo ""

BUILD_VERSION=0
PUSH=0
while getopts "pv:" opt; do
  case $opt in
  v) BUILD_VERSION=$OPTARG ;;
  p) PUSH=1 ;;
  esac
done

# master is equal to dev (master comes from github)
if [ "$BUILD_VERSION" == "master" ]; then
  BUILD_VERSION=dev
fi

# check if version is already in docker hub
if [ "$BUILD_VERSION" != "dev" ]; then
  $DOCKER_CMD pull $DOCKER_REPO:$BUILD_VERSION > /dev/null
  if [ "$?" == "0" ]; then
    cecho r "Docker Image Tag '$BUILD_VERSION' already exist in docker hub. Use a new version number."
    exit 1
  fi
fi

# remove all images of framelix locally
$DOCKER_CMD rmi $($DOCKER_CMD images | grep "$DOCKER_REPO") > /dev/null 2>&1

# build image
bash $SCRIPTDIR/build-image.sh -v $BUILD_VERSION

if [ "$?" != "0" ]; then
  cecho r "Build failed"
  exit 1
fi

bash $SCRIPTDIR/start-container.sh
if [ "$?" != "0" ]; then
  cecho r "Container start failed"
  exit 1
fi

bash $SCRIPTDIR/run-tests.sh -t install-deps
if [ "$?" != "0" ]; then
  cecho r "Container start failed"
  exit 1
fi

bash $SCRIPTDIR/run-tests.sh -t phpstan
if [ "$?" != "0" ]; then
  cecho r "PhpStan tests failed"
  exit 1
fi

bash $SCRIPTDIR/run-tests.sh -t playwright
if [ "$?" != "0" ]; then
  cecho r "Playwright tests failed"
  exit 1
fi

bash $SCRIPTDIR/run-tests.sh -t phpunit
if [ "$?" != "0" ]; then
  cecho r "PhpUnit tests failed"
  exit 1
fi

bash $SCRIPTDIR/stop-container.sh

if [ "$PUSH" == "1" ] ; then
  if [ "$BUILD_VERSION" == "dev" ]; then
    $DOCKER_CMD tag $DOCKER_TAGNAME_LOCAL $DOCKER_REPO:dev
    $DOCKER_CMD push  $DOCKER_REPO:dev
  else

    MAJOR_VERSION=$(echo $BUILD_VERSION| cut -d'.' -f 1)
    MINOR_VERSION=$(echo $BUILD_VERSION| cut -d'.' -f 1,2)
    PRE_VERSION=$(echo $BUILD_VERSION| cut -d'-' -f 2,2)

    # the current version is no pre version, so unset pre version
    if [ "$PRE_VERSION" == "$BUILD_VERSION" ]; then
      PRE_VERSION=""
    fi

    $DOCKER_CMD tag $DOCKER_TAGNAME_LOCAL $DOCKER_REPO:edge
    $DOCKER_CMD tag $DOCKER_TAGNAME_LOCAL $DOCKER_REPO:$BUILD_VERSION
    $DOCKER_CMD push $DOCKER_REPO:$BUILD_VERSION
    $DOCKER_CMD push $DOCKER_REPO:edge

    if [ "$PRE_VERSION" == "" ]; then
      # production tags
      $DOCKER_CMD tag $DOCKER_TAGNAME_LOCAL $DOCKER_REPO:$MINOR_VERSION
      $DOCKER_CMD tag $DOCKER_TAGNAME_LOCAL $DOCKER_REPO:$MAJOR_VERSION

      $DOCKER_CMD push $DOCKER_REPO:$MINOR_VERSION
      $DOCKER_CMD push $DOCKER_REPO:$MAJOR_VERSION
    fi
  fi
fi