#!/bin/bash

test_file_name="$1"
test_dir_path="Tests"
specific_test="$2"
is_shared=""

test_file_path=$(find $test_dir_path -name "${test_file_name}.php" -print -quit)

if [ -z "$test_file_path" ]; then
  echo "Test file not found: ${test_file_name}.php"
  exit 1
fi

if [ -n "$specific_test" ]; then
  echo "vendor/bin/codecept run \"$test_file_path:$specific_test\"";
  vendor/bin/codecept run "$test_file_path:$specific_test"
else
  echo "vendor/bin/codecept run \"$test_file_path\"";
  vendor/bin/codecept run "$test_file_path"
fi
