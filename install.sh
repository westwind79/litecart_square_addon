#!/bin/bash

STAGE=$1
IS_TEST=$2

if [ -z ${STAGE} ]; then
  echo "STAGE is unset" 1>&2 && exit 1; else
  echo "STAGE is set to '$STAGE'";
fi

if [[ $IS_TEST == "--test" ]]; then
  echo "This is a test.";
fi

SCRIPT_DIRECTORY="$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
echo "running from $SCRIPT_DIRECTORY"
TARGET_FILE_NAME="/targets.json"
TARGET_FILE="$SCRIPT_DIRECTORY/$TARGET_FILE_NAME"

if [ ! -f $TARGET_FILE ]; then
  echo "No target file found." 1>&2 && exit 1;
fi

TARGET=$(<"$TARGET_FILE")

cat targets.json | python3 ./extract-targets.py "$STAGE" | while read TARGET_DIR ; do
  echo === $TARGET_DIR ===
  if [[ $IS_TEST == "--test" ]]; then
    TARGET_DIR="temp-public"
  fi

  if [ ! -d $TARGET_DIR ]; then
    sudo mkdir -p $TARGET_DIR
  fi

  sudo cp -R public_html/* $TARGET_DIR
  sudo chown -R www-data:www-data $TARGET_DIR
  sudo chmod -R g+w $TARGET_DIR
done
