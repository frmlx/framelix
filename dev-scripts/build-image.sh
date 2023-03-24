#!/usr/bin/bash

SCRIPTDIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" &>/dev/null && pwd)
source $SCRIPTDIR/lib.sh

cecho b "Build Docker Image"
echo "Available command line flags:"
echo "-t : Type of build: dev, docker-hub"

BUILD_TYPE=0
GITHUB_REPO=nullixat/framelix
DOCKER_REPO=nullixat/framelix
while getopts "t:" opt; do
  case $opt in
  t) BUILD_TYPE=$OPTARG ;;
  esac
done

if [ "$BUILD_TYPE" == "docker-hub" ]; then
  echo "=== Docker Hub build ==="
#  curl_response=$(curl -s https://api.github.com/repos/$GITHUB_REPO/tags)
#  if [ $(echo $curl_response | grep -c '"name": "'$VERSION'"') != "1" ]; then
#    cecho r "Github Repository Tag '$VERSION' does not exist in repository '$GITHUB_REPO'"
#    exit 1
#  fi
#  docker pull $DOCKER_REPO:$VERSION > /dev/null
#  if [ "$?" == "0" ]; then
#    cecho r "Docker Image Tag '$VERSION' already exist in docker hub. Use a new version number."
#    exit 1
#  fi
  source $SCRIPTDIR/stop-container.sh
  # remove old tagged images
  docker image rm $DOCKER_REPO:latest
  docker image rm $DOCKER_REPO:$VERSION
  docker image rm $DOCKER_REPO:$MINOR_VERSION
  docker image rm $DOCKER_REPO:$MAJOR_VERSION
  docker build -t $COMPOSE_PROJECT_NAME --build-arg "FRAMELIX_BUILD_TYPE=$BUILD_TYPE"  $SCRIPTDIR/..
  # tag images
  docker tag $COMPOSE_PROJECT_NAME $DOCKER_REPO:latest
  docker tag $COMPOSE_PROJECT_NAME $DOCKER_REPO:$VERSION
  docker tag $COMPOSE_PROJECT_NAME $DOCKER_REPO:$MINOR_VERSION
  docker tag $COMPOSE_PROJECT_NAME $DOCKER_REPO:$MAJOR_VERSION
elif [ "$BUILD_TYPE" == "dev" ]; then
  echo "=== Development build ==="
  source $SCRIPTDIR/stop-container.sh
  docker build -t $COMPOSE_PROJECT_NAME --build-arg "FRAMELIX_BUILD_TYPE=$BUILD_TYPE" $SCRIPTDIR/..
else
  echo "Please specify build type 'dev' or 'prod'"
  exit 1
fi
