#!/bin/bash
set -e -x

#git pull -r

# Remove '-dev' from the version file to prepare for release.
ver="$(cat VERSION | sed -e 's/-dev$//')"
echo $ver > VERSION.tmp
mv -f VERSION.tmp VERSION

MAJOR=$(echo "${ver}" | sed -e 's/\.[0-9]*\.[0-9]*$//')
MINOR=$(echo "${ver}" | sed -e 's/^[0-9]*\.//' | sed -e 's/\.[0-9]*$//')
PATCH=$(echo "${ver}" | sed -e 's/^[0-9]*.[0-9]*.//')

PATCH=$(( $PATCH + 1 ))


# Tag a release
echo "Releasing version: ${ver}"
git commit -am "Version $ver"
git tag "$ver"
git push origin "$ver"

# Advance to the next patch release, add the '-dev' suffix back on, and commit the result.
NEW_VERSION="$MAJOR.$MINOR.$PATCH-dev"
echo "Setting new version: ${NEW_VERSION}"
echo "$NEW_VERSION" > VERSION
git add VERSION
git commit -m "Back to -dev: $NEW_VERSION"
git push origin master
