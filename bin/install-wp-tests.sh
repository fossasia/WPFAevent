#!/usr/bin/env bash

# See https://raw.githubusercontent.com/wp-cli/scaffold-command/master/templates/install-wp-tests.sh

# Set up colors for output
RED="\033[0;31m"
GREEN="\033[0;32m"
YELLOW="\033[0;33m"
CYAN="\033[0;36m"
RESET="\033[0m"

if [ $# -lt 3 ]; then
	echo -e "${YELLOW}Usage:${RESET} $0 <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation]"
	exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}
SKIP_DB_CREATE=${6-false}

TMPDIR=${TMPDIR-/tmp}
TMPDIR=$(echo $TMPDIR | sed -e "s/\/$//")
WP_TESTS_DIR=${WP_TESTS_DIR-$TMPDIR/wordpress-tests-lib}
WP_TESTS_FILE="$WP_TESTS_DIR"/includes/functions.php
WP_CORE_DIR=${WP_CORE_DIR-$TMPDIR/wordpress}
WP_CORE_FILE="$WP_CORE_DIR"/wp-settings.php

download() {
    if command -v curl > /dev/null 2>&1; then
        curl -L -s "$1" > "$2";
        return $?
    elif command -v wget > /dev/null 2>&1; then
        wget -nv -O "$2" "$1"
        return $?
    else
        echo -e "${RED}Error: Neither curl nor wget is installed.${RESET}"
        exit 1
    fi
}

check_for_updates() {
	local remote_url="https://raw.githubusercontent.com/wp-cli/scaffold-command/main/templates/install-wp-tests.sh"
	local tmp_script="$TMPDIR/install-wp-tests.sh.latest"

	if ! download "$remote_url" "$tmp_script"; then
		echo -e "${YELLOW}Warning: Failed to download the latest version of the script for update check.${RESET}"
		return
	fi

	if [ ! -f "$tmp_script" ] || [ ! -s "$tmp_script" ]; then
		echo -e "${YELLOW}Warning: Downloaded script is missing or empty, cannot check for updates.${RESET}"
		rm -f "$tmp_script"
		return
	fi

	local local_hash=""
	local remote_hash=""

	if command -v shasum > /dev/null; then
		local_hash=$(shasum -a 256 "$0" | awk '{print $1}')
		remote_hash=$(shasum -a 256 "$tmp_script" | awk '{print $1}')
	elif command -v sha256sum > /dev/null; then
		local_hash=$(sha256sum "$0" | awk '{print $1}')
		remote_hash=$(sha256sum "$tmp_script" | awk '{print $1}')
	else
		echo -e "${YELLOW}Warning: Could not find shasum or sha256sum to check for script updates.${RESET}"
		rm "$tmp_script"
		return
	fi

	rm "$tmp_script"

	if [ "$local_hash" != "$remote_hash" ]; then
		echo -e "${YELLOW}Warning: A newer version of this script is available at $remote_url${RESET}"
	fi
}
# Allow disabling the update check by setting WP_INSTALL_TESTS_SKIP_UPDATE_CHECK=true in the environment.
if [ "${WP_INSTALL_TESTS_SKIP_UPDATE_CHECK:-false}" != "true" ]; then
	check_for_updates
fi

if [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+\-(beta|RC)[0-9]+$ ]]; then
	WP_BRANCH=${WP_VERSION%\-*}
	WP_TESTS_TAG="branches/$WP_BRANCH"
elif [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+$ ]]; then
	WP_TESTS_TAG="branches/$WP_VERSION"
elif [[ $WP_VERSION =~ [0-9]+\.[0-9]+\.[0-9]+ ]]; then
	if [[ $WP_VERSION =~ [0-9]+\.[0-9]+\.[0] ]]; then
		# version x.x.0 means the first release of the major version, so strip off the .0 and download version x.x
		WP_TESTS_TAG="tags/${WP_VERSION%??}"
	else
		WP_TESTS_TAG="tags/$WP_VERSION"
	fi
elif [[ $WP_VERSION == 'nightly' || $WP_VERSION == 'trunk' ]]; then
	WP_TESTS_TAG="trunk"
else
	# http serves a single offer, whereas https serves multiple. we only want one
	download http://api.wordpress.org/core/version-check/1.7/ /tmp/wp-latest.json
	LATEST_VERSION=$(grep -oE '"version":"[^"]*' /tmp/wp-latest.json | head -n 1 | sed 's/"version":"//')
	if [[ -z "$LATEST_VERSION" ]]; then
		echo -e "${RED}Error: Latest WordPress version could not be found.${RESET}"
		exit 1
	fi
	# The version-check endpoint returns major.minor (e.g., 6.9), but GitHub tags include the patch version (e.g., 6.9.0)
	if [[ $LATEST_VERSION =~ ^[0-9]+\.[0-9]+$ ]]; then
		LATEST_VERSION="${LATEST_VERSION}.0"
	fi
	WP_TESTS_TAG="tags/$LATEST_VERSION"
fi

set -ex

install_wp() {

	if [ -f $WP_CORE_FILE ]; then
		echo -e "${CYAN}WordPress is already installed.${RESET}"
		return;
	fi

	echo -e "${CYAN}Installing WordPress...${RESET}"

	rm -rf $WP_CORE_DIR
	mkdir -p $WP_CORE_DIR

	if [[ $WP_VERSION == 'nightly' || $WP_VERSION == 'trunk' ]]; then
		download https://github.com/WordPress/wordpress/archive/refs/heads/master.tar.gz $TMPDIR/wordpress.tar.gz
		tar --strip-components=1 -zxmf $TMPDIR/wordpress.tar.gz -C $WP_CORE_DIR
	else
		if [ $WP_VERSION == 'latest' ]; then
			local ARCHIVE_NAME='latest'
		elif [[ $WP_VERSION =~ [0-9]+\.[0-9]+ ]]; then
			# https serves multiple offers, whereas http serves single.
			download https://api.wordpress.org/core/version-check/1.7/ $TMPDIR/wp-latest.json
			if [[ $WP_VERSION =~ [0-9]+\.[0-9]+\.[0] ]]; then
				# version x.x.0 means the first release of the major version, so strip off the .0 and download version x.x
				LATEST_VERSION=${WP_VERSION%??}
			else
				# otherwise, scan the releases and get the most up to date minor version of the major release
				local VERSION_ESCAPED=`echo $WP_VERSION | sed 's/\./\\\\./g'`
				LATEST_VERSION=$(grep -o '"version":"'$VERSION_ESCAPED'[^"]*' $TMPDIR/wp-latest.json | sed 's/"version":"//' | head -1)
			fi
			if [[ -z "$LATEST_VERSION" ]]; then
				local ARCHIVE_NAME="wordpress-$WP_VERSION"
			else
				local ARCHIVE_NAME="wordpress-$LATEST_VERSION"
			fi
		else
			local ARCHIVE_NAME="wordpress-$WP_VERSION"
		fi
		download https://wordpress.org/${ARCHIVE_NAME}.tar.gz  $TMPDIR/wordpress.tar.gz
		tar --strip-components=1 -zxmf $TMPDIR/wordpress.tar.gz -C $WP_CORE_DIR
	fi
	echo -e "${GREEN}WordPress installed successfully.${RESET}"
}

install_test_suite() {
	# portable in-place argument for both GNU sed and Mac OSX sed
	if [[ $(uname -s) == 'Darwin' ]]; then
		local ioption='-i.bak'
	else
		local ioption='-i'
	fi

	# set up testing suite if it doesn't yet exist or only partially exists
	if [ ! -f $WP_TESTS_FILE ]; then
		echo -e "${CYAN}Installing test suite...${RESET}"
		# set up testing suite
		rm -rf $WP_TESTS_DIR
		mkdir -p $WP_TESTS_DIR

		if [[ $WP_TESTS_TAG == 'trunk' ]]; then
			ref=trunk
			archive_url="https://github.com/WordPress/wordpress-develop/archive/refs/heads/${ref}.tar.gz"
		elif [[ $WP_TESTS_TAG == branches/* ]]; then
			ref=${WP_TESTS_TAG#branches/}
			archive_url="https://github.com/WordPress/wordpress-develop/archive/refs/heads/${ref}.tar.gz"
		else
			ref=${WP_TESTS_TAG#tags/}
			archive_url="https://github.com/WordPress/wordpress-develop/archive/refs/tags/${ref}.tar.gz"
		fi

		if [ -z "$ref" ]; then
			echo -e "${RED}Error:${RESET} Unable to determine git reference from WP_TESTS_TAG: $WP_TESTS_TAG"
			exit 1
		fi

		download "${archive_url}" "$TMPDIR/wordpress-develop.tar.gz"

		# Validate that the tarball was downloaded correctly before extracting
		if [ ! -s "$TMPDIR/wordpress-develop.tar.gz" ]; then
			echo -e "${RED}Error:${RESET} Downloaded test suite archive is missing or empty: $TMPDIR/wordpress-develop.tar.gz"
			exit 1
		fi

		if ! tar -tzf "$TMPDIR/wordpress-develop.tar.gz" >/dev/null 2>&1; then
			echo -e "${RED}Error:${RESET} Downloaded test suite archive is not a valid tar.gz file: $TMPDIR/wordpress-develop.tar.gz"
			exit 1
		fi

		tar -zxmf "$TMPDIR/wordpress-develop.tar.gz" -C "$TMPDIR"
		mv "$TMPDIR/wordpress-develop-${ref}/tests/phpunit/includes" "$WP_TESTS_DIR"/
		mv "$TMPDIR/wordpress-develop-${ref}/tests/phpunit/data" "$WP_TESTS_DIR"/
		rm -rf "$TMPDIR/wordpress-develop-${ref}"
		rm "$TMPDIR/wordpress-develop.tar.gz"
		echo -e "${GREEN}Test suite installed.${RESET}"
	else
		echo -e "${CYAN}Test suite is already installed.${RESET}"
	fi

	if [ ! -f "$WP_TESTS_DIR"/wp-tests-config.php ]; then
		echo -e "${CYAN}Configuring test suite...${RESET}"
		if [[ $WP_TESTS_TAG == 'trunk' ]]; then
			ref=trunk
		elif [[ $WP_TESTS_TAG == branches/* ]]; then
			ref=${WP_TESTS_TAG#branches/}
		else
			ref=${WP_TESTS_TAG#tags/}
		fi

		if [ -z "$ref" ]; then
			echo -e "${RED}Error:${RESET} Unable to determine git reference from WP_TESTS_TAG: $WP_TESTS_TAG"
			exit 1
		fi

		download https://raw.githubusercontent.com/WordPress/wordpress-develop/${ref}/wp-tests-config-sample.php "$WP_TESTS_DIR"/wp-tests-config.php
		# remove all forward slashes in the end
		WP_CORE_DIR=$(echo $WP_CORE_DIR | sed "s:/\+$::")
		# escape special sed replacement characters in $WP_CORE_DIR (backslash, pipe, ampersand)
		WP_CORE_DIR_ESCAPED=$(printf '%s' "$WP_CORE_DIR" | sed 's/[\\|&]/\\&/g')
		sed $ioption "s|dirname( __FILE__ ) . '/src/'|'${WP_CORE_DIR_ESCAPED}/'|" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s|__DIR__ . '/src/'|'${WP_CORE_DIR_ESCAPED}/'|" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s/youremptytestdbnamehere/$DB_NAME/" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s/yourusernamehere/$DB_USER/" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s/yourpasswordhere/$DB_PASS/" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s|localhost|${DB_HOST}|" "$WP_TESTS_DIR"/wp-tests-config.php
		echo -e "${GREEN}Test suite configured.${RESET}"
	else
		echo -e "${CYAN}Test suite is already configured.${RESET}"
	fi

}

recreate_db() {
	shopt -s nocasematch
	if [[ $1 =~ ^(y|yes)$ ]]
	then
		echo -e "${CYAN}Recreating the database ($DB_NAME)...${RESET}"
		if command -v mariadb-admin > /dev/null 2>&1; then
			mariadb-admin drop $DB_NAME -f --user="$DB_USER" --password="$DB_PASS"$EXTRA
		else
			mysqladmin drop $DB_NAME -f --user="$DB_USER" --password="$DB_PASS"$EXTRA
		fi
		create_db
		echo -e "${GREEN}Database ($DB_NAME) recreated.${RESET}"
	else
		echo -e "${YELLOW}Leaving the existing database ($DB_NAME) in place.${RESET}"
	fi
	shopt -u nocasematch
}

create_db() {
	if command -v mariadb-admin > /dev/null 2>&1; then
		mariadb-admin create $DB_NAME --user="$DB_USER" --password="$DB_PASS"$EXTRA
	else
		mysqladmin create $DB_NAME --user="$DB_USER" --password="$DB_PASS"$EXTRA
	fi
}

install_db() {

	if [ ${SKIP_DB_CREATE} = "true" ]; then
		echo -e "${YELLOW}Skipping database creation.${RESET}"
		return 0
	fi

	# parse DB_HOST for port or socket references
	local PARTS=(${DB_HOST//\:/ })
	local DB_HOSTNAME=${PARTS[0]};
	local DB_SOCK_OR_PORT=${PARTS[1]};
	local EXTRA=""

	if ! [ -z $DB_HOSTNAME ] ; then
		if [ $(echo $DB_SOCK_OR_PORT | grep -e '^[0-9]\{1,\}$') ]; then
			EXTRA=" --host=$DB_HOSTNAME --port=$DB_SOCK_OR_PORT --protocol=tcp"
		elif ! [ -z $DB_SOCK_OR_PORT ] ; then
			EXTRA=" --socket=$DB_SOCK_OR_PORT"
		elif ! [ -z $DB_HOSTNAME ] ; then
			EXTRA=" --host=$DB_HOSTNAME --protocol=tcp"
		fi
	fi

	# create database
	if command -v mariadb > /dev/null 2>&1; then
		local DB_CLIENT='mariadb'
	else
		local DB_CLIENT='mysql'
	fi
	if $DB_CLIENT --user="$DB_USER" --password="$DB_PASS"$EXTRA --execute='show databases;' | grep -q "^$DB_NAME$";
	then
		echo -e "${YELLOW}Reinstalling will delete the existing test database ($DB_NAME)${RESET}"
		read -p 'Are you sure you want to proceed? [y/N]: ' DELETE_EXISTING_DB
		recreate_db $DELETE_EXISTING_DB
	else
		echo -e "${CYAN}Creating database ($DB_NAME)...${RESET}"
		create_db
		echo -e "${GREEN}Database ($DB_NAME) created.${RESET}"
	fi
}

install_wp
install_test_suite
install_db
echo -e "${GREEN}Done.${RESET}"
