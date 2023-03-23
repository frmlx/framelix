#!/usr/bin/bash

SCRIPTDIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" &>/dev/null && pwd)
source $SCRIPTDIR/lib.sh

cleanup() {
  echo -n "Cleanup build images ... "
  docker image rm $IMAGENAMETMP
  if [ "$DOCKER_REPO" != "0" ]; then
    docker image rm $DOCKER_REPO:latest
    docker image rm $DOCKER_REPO:$VERSION
    docker image rm $DOCKER_REPO:$MINOR_VERSION
    docker image rm $DOCKER_REPO:$MAJOR_VERSION
  fi
  echo  "Cleanup done"
}

if [ "$FRAMELIX_UNIT_TESTS" != "0" ]; then
  cecho r "FRAMELIX_UNIT_TESTS must be 0"
  exit 1
fi

if [ "$FRAMELIX_DEVMODE" != "0" ]; then
  cecho r "FRAMELIX_DEVMODE must be 0"
  exit 1
fi

cecho b "Create/Publish image to Docker Hub"
echo "Available command line flags:"
echo "-n : Does not add appdata into the image"
echo "-k : Keep images after the process (Default is to delete all temporarily created images)"
echo "-p : Push to docker hub (If not set, it only create the images, a dry run, so to speak)"
echo ""

IMAGENAMETMP="${MODULENAME_LOWER}_push"

while getopts "knp" opt; do
  case $opt in
  k) KEEP=1 ;;
  n) NO_APPDATA=1 ;;
  p) PUSH=1 ;;
  esac
done

if [ "$VERSION" == "" ]; then
  cecho r "VERSION file must be set in root directory"
  exit 1
fi

cleanup

if [ "$NO_APPDATA" == "1" ]; then
  docker build -t $IMAGENAMETMP $ROOTDIR
else
  if [ "$GITHUB_REPO" == "0" ]; then
    cecho r "Env Variable DOCKER_REPO must be set in .env"
    exit 1
  fi

  if [ "$DOCKER_REPO" == "0" ]; then
    cecho r "Env Variable DOCKER_REPO must be set in .env"
    exit 1
  fi

  curl_response=$(curl -s https://api.github.com/repos/$GITHUB_REPO/tags)
  if [ $(echo $curl_response | grep -c '"name": "'$VERSION'"') != "1" ]; then
    cecho r "Github Repository Tag '$VERSION' does not exist in repository '$GITHUB_REPO'"
    exit 1
  fi
  docker build -t $IMAGENAMETMP $ROOTDIR --build-arg "FRAMELIX_GITHUB_REPO=$GITHUB_REPO" --build-arg "FRAMELIX_DOCKER_REPO=$DOCKER_REPO" --build-arg "FRAMELIX_VERSION=$VERSION"
fi

docker tag $IMAGENAMETMP $DOCKER_REPO:latest
docker tag $IMAGENAMETMP $DOCKER_REPO:$VERSION
docker tag $IMAGENAMETMP $DOCKER_REPO:$MINOR_VERSION
docker tag $IMAGENAMETMP $DOCKER_REPO:$MAJOR_VERSION

if [ "$PUSH" == "1" ]; then
  docker push -a $DOCKER_REPO
fi

if [ "$KEEP" != "1" ]; then
  cleanup
fi
