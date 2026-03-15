#!/bin/bash

current_version=$(git describe --tags --abbrev=0)
semver_regex="v?([0-9]+)\.([0-9]+)\.([0-9]+)(?:(?:-)(?:(rc\.([0-9]+))|((beta|alpha)\.([1-9][0-9]{0,2}))))?"
major=$( echo "$current_version" | pcre2grep $semver_regex)
echo "$current_version"
semver echo "$major"
