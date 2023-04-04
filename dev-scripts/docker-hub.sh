#!/usr/bin/bash

SCRIPTDIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" &>/dev/null && pwd)
source $SCRIPTDIR/lib.sh

cecho b "Build/Push image to Docker Hub"
echo "This script does:"
echo "  * Rebuild the image $DOCKER_TAGNAME_LOCAL,run all tests and only on success will be able to push the image"
echo "  * Deletes all local userdata and db volume"
echo "  * Runs all tests"
echo "  * If all success, be able to push to docker"
echo "Available command line flags:"
echo "-t : Type of build: dev, prod"
echo "-p : Push to docker hub (If not set, it only build and tests the image, a dry run, so to speak)"
echo "-s : Skip rebuild (Does take the current existing $DOCKER_TAGNAME_LOCAL image)"
echo ""

BUILD_TYPE=0
PUSH=0
SKIP_REBUILD=0
while getopts "spt:" opt; do
  case $opt in
  t) BUILD_TYPE=$OPTARG ;;
  p) PUSH=1 ;;
  s) SKIP_REBUILD=1 ;;
  esac
done

if [ "$BUILD_TYPE" == "0" ]; then
  cecho r "-t parameter is required"
  exit 1
fi

if [ "$SKIP_REBUILD" != "1" ]; then
  # remove all images of framelix locally
  docker rmi $(docker images | grep "$DOCKER_REPO") > /dev/null 2>&1

  bash $SCRIPTDIR/build-image.sh -t $BUILD_TYPE
  if [ "$?" != "0" ]; then
    cecho r "Build failed"
    exit 1
  fi
fi

bash $SCRIPTDIR/start-container.sh
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

if [ "$BUILD_TYPE" == "dev" ]; then
  docker tag $DOCKER_TAGNAME_LOCAL $DOCKER_REPO:dev
elif [ "$BUILD_TYPE" == "prod" ] ; then
  docker tag $DOCKER_TAGNAME_LOCAL $DOCKER_REPO:latest
  docker tag $DOCKER_TAGNAME_LOCAL $DOCKER_REPO:$VERSION
  docker tag $DOCKER_TAGNAME_LOCAL $DOCKER_REPO:$MINOR_VERSION
  docker tag $DOCKER_TAGNAME_LOCAL $DOCKER_REPO:$MAJOR_VERSION
fi

if [ "$PUSH" == "1" ] ; then
  if [ "$BUILD_TYPE" == "dev" ]; then
    docker tag $DOCKER_TAGNAME_LOCAL $DOCKER_REPO:dev
    docker push  $DOCKER_REPO:dev
  elif [ "$BUILD_TYPE" == "prod" ] ; then
    docker pull $DOCKER_REPO:$VERSION > /dev/null
    if [ "$?" == "0" ]; then
      cecho r "Docker Image Tag '$VERSION' already exist in docker hub. Use a new version number."
      exit 1
    fi

    docker tag $DOCKER_TAGNAME_LOCAL $DOCKER_REPO:latest
    docker tag $DOCKER_TAGNAME_LOCAL $DOCKER_REPO:$VERSION
    docker tag $DOCKER_TAGNAME_LOCAL $DOCKER_REPO:$MINOR_VERSION
    docker tag $DOCKER_TAGNAME_LOCAL $DOCKER_REPO:$MAJOR_VERSION

    docker push $DOCKER_REPO:latest
    docker push $DOCKER_REPO:$VERSION
    docker push $DOCKER_REPO:$MINOR_VERSION
    docker push $DOCKER_REPO:$MAJOR_VERSION
  fi
fi