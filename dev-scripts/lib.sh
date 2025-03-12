#!/usr/bin/bash

SCRIPTPATH=$(readlink -f "$0")
BASEDIR=$(dirname "$SCRIPTPATH")
ROOTDIR="$BASEDIR/.."

source $BASEDIR/.env

DOCKER_REPO=docker.io/framelix/framelix
DOCKER_TAGNAME_LOCAL=$DOCKER_REPO:local

COMPOSER_FILE_ARGS="-f $SCRIPTDIR/docker-compose.yml"
if [ -f "$SCRIPTDIR/docker-compose.override.yml" ]; then
  COMPOSER_FILE_ARGS="-f $SCRIPTDIR/docker-compose.yml -f $SCRIPTDIR/docker-compose.override.yml"
fi

DOCKER_CMD=docker
DOCKER_COMPOSE="$DOCKER_CMD compose $COMPOSER_FILE_ARGS"
DOCKER_COMPOSE_EXEC="$DOCKER_COMPOSE exec -t"

if ! command -v $DOCKER_CMD 2>&1 >/dev/null
then
  DOCKER_CMD=podman
  DOCKER_COMPOSE="$DOCKER_CMD compose $COMPOSER_FILE_ARGS"
  DOCKER_COMPOSE_EXEC="$DOCKER_COMPOSE exec"
fi

DOCKER_COMPOSE_EXEC_APP="$DOCKER_COMPOSE_EXEC app bash -c "
DOCKER_COMPOSE_EXEC_PW="$DOCKER_COMPOSE_EXEC playwright bash -c "
DOCKER_COMPOSE_EXEC_MARIADB="$DOCKER_COMPOSE_EXEC mariadb bash -c "

cecho() {
  local code="\033["
  case "$1" in
  black | bk) color="${code}0;30m" ;;
  red | r) color="${code}1;31m" ;;
  green | g) color="${code}1;92m" ;;
  yellow | y) color="${code}1;93m" ;;
  blue | b) color="${code}1;34m" ;;
  purple | p) color="${code}1;35m" ;;
  cyan | c) color="${code}1;36m" ;;
  gray | gr) color="${code}0;37m" ;;
  *) local text="$1" ;;
  esac
  [ -z "$text" ] && local text="$color$2${code}0m"
  echo -e "$text"
}