#!/usr/bin/env bash

# --------------------------------------------------------------
# Official install‑wp‑tests.sh from WP‑CLI scaffold command.
# --------------------------------------------------------------
# Usage: bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation]
# Example:
#   composer setup-tests          # runs the script with the default args you defined in composer.json
# --------------------------------------------------------------

# -------------------------- Colors ---------------------------
RED="\033[0;31m"
GREEN="\033[0;32m"
YELLOW="\033[0;33m"
CYAN="\033[0;36m"
RESET="\033[0m"

# -------------------------- Args ----------------------------
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

# -------------------------- Paths ---------------------------
TMPDIR=${TMPDIR-/tmp}
TMPDIR=$(echo "$TMPDIR" | sed -e "s/\\/$//")
WP_TESTS_DIR=${WP_TESTS_DIR-$TMPDIR/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR-$TMPDIR/wordpress}

# -------------------------- Helpers -------------------------
download() {
    if command -v curl >/dev/null 2>&1; then
        curl -L -s "$1" -o "$2"
        return $?
    elif command -v wget >/dev/null 2>&1; then
        wget -nv -O "$2" "$1"
        return $?
    else
        echo -e "${RED}Error: Neither curl nor wget is installed.${RESET}"
        exit 1
    fi
}

check_for_updates() {
    local remote_url="https://raw.githubusercontent.com/wp-cli/scaffold-command/main/templates/install-wp-tests.sh"
    local tmp_script="${TMPDIR}/install-wp-tests.sh.latest"

    if ! download "$remote_url" "$tmp_script"; then
        echo -e "${YELLOW}Warning: Failed to download the latest version of the script for update check.${RESET}"
        return
    fi

    if [ ! -f "$tmp_script" ] || [ ! -s "$tmp_script" ]; then
        echo -e "${YELLOW}Warning: Downloaded script is missing or empty; cannot check for updates.${RESET}"
        rm -f "$tmp_script"
        return
    fi

    local local_hash remote_hash
    if command -v shasum >/dev/null; then
        local_hash=$(shasum -a 256 "$0" | awk '{print $1}')
        remote_hash=$(shasum -a 256 "$tmp_script" | awk '{print $1}')
    elif command -v sha256sum >/dev/null; then
        local_hash=$(sha256sum "$0" | awk '{print $1}')
        remote_hash=$(sha256sum "$tmp_script" | awk '{print $1}')
    else
        echo -e "${YELLOW}Warning: No shasum/sha256sum; skipping update check.${RESET}"
        rm -f "$tmp_script"
        return
    fi

    rm -f "$tmp_script"

    if [ "$local_hash" != "$remote_hash" ]; then
        echo -e "${YELLOW}Warning: A newer version of this script is available at $remote_url${RESET}"
    fi
}

# ------------------- Optional update check ---------------
if [ "${WP_INSTALL_TESTS_SKIP_UPDATE_CHECK:-false}" != "true" ]; then
    check_for_updates
fi

# ---------------------- WP version tag --------------------
if [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+-(beta|RC)[0-9]+$ ]]; then
    WP_BRANCH=${WP_VERSION%-*}
    WP_TESTS_TAG="branches/$WP_BRANCH"
elif [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+$ ]]; then
    WP_TESTS_TAG="branches/$WP_VERSION"
elif [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    if [[ $WP_VERSION =~ \.0$ ]]; then
        WP_TESTS_TAG="tags/${WP_VERSION%??}"
    else
        WP_TESTS_TAG="tags/$WP_VERSION"
    fi
elif [[ $WP_VERSION == "nightly" || $WP_VERSION == "trunk" ]]; then
    WP_TESTS_TAG="trunk"
else
    # Fallback – grab the latest stable version via the API.
    download "http://api.wordpress.org/core/version-check/1.7/" "/tmp/wp-latest.json"
    LATEST_VERSION=$(grep -oE '"version":"[^"]+"' /tmp/wp-latest.json | head -1 | cut -d'"' -f4)
    WP_VERSION=$LATEST_VERSION
    WP_TESTS_TAG="tags/$WP_VERSION"
fi

# ---------------------- Core download --------------------
if [ ! -f "$WP_CORE_DIR/wp-settings.php" ]; then
    echo -e "${CYAN}Downloading WordPress $WP_VERSION...${RESET}"
    download "https://wordpress.org/${WP_VERSION}.tar.gz" "/tmp/wordpress.tar.gz"
    mkdir -p "$WP_CORE_DIR"
    tar -xzf "/tmp/wordpress.tar.gz" -C "$WP_CORE_DIR" --strip-components=1
fi

# --------------------- Tests library --------------------
if [ ! -f "$WP_TESTS_DIR/includes/functions.php" ]; then
    echo -e "${CYAN}Downloading WordPress test suite...${RESET}"
    download "https://downloads.wordpress.org/plugin/unit-tests.zip" "/tmp/wordpress-tests.zip"
    unzip -q "/tmp/wordpress-tests.zip" -d "$TMPDIR"
    mv "$TMPDIR/wordpress-tests-lib" "$WP_TESTS_DIR"
fi

# ------------------- Composer deps (tests) ---------------
if [ -f "$WP_TESTS_DIR/composer.json" ]; then
    (cd "$WP_TESTS_DIR" && composer install --quiet)
fi

# -------------------------- DB ---------------------------
if [ "$SKIP_DB_CREATE" != "true" ]; then
    echo -e "${CYAN}Creating test database $DB_NAME...${RESET}"
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;"
fi

# -------------------- Export env vars -------------------
export WP_TESTS_DIR
export WP_CORE_DIR
export WP_DB_NAME=$DB_NAME
export WP_DB_USER=$DB_USER
export WP_DB_PASSWORD=$DB_PASS
export WP_DB_HOST=$DB_HOST

echo -e "${GREEN}WordPress test environment installed.${RESET}"
