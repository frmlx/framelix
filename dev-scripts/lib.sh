#!/usr/bin/bash

SCRIPTPATH=$(readlink -f "$0")
BASEDIR=$(dirname "$SCRIPTPATH")
ROOTDIR="$BASEDIR/.."

source $BASEDIR/.env

DOCKER_CONTAINER_NAME=framelix_tests
MODULENAME_LOWER="${MODULENAME,,}"
VERSION=$(cat "$ROOTDIR/VERSION")
MAJOR_VERSION=$(echo $VERSION| cut -d'.' -f 1)
MINOR_VERSION=$(echo $VERSION| cut -d'.' -f 1,2)


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