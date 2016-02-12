#!/bin/bash

set -e $DRUPAL_TI_DEBUG

# Ensure the right Drupal version is installed.
# Note: This function is re-entrant.
drupal_ti_ensure_drupal

# Download module dependencies.
mkdir -p "$DRUPAL_TI_DRUPAL_DIR/$DRUPAL_TI_MODULES_PATH"
cd "$DRUPAL_TI_DRUPAL_DIR/$DRUPAL_TI_MODULES_PATH"
git clone --branch 8.x-1.x http://git.drupal.org/project/composer_manager.git --depth 1
git clone --branch 8.x-1.x https://github.com/amitaibu/og.git --depth 1
php composer_manager/scripts/init.php

# Ensure the module is linked into the codebase.
drupal_ti_ensure_module_linked

cd $DRUPAL_TI_DRUPAL_DIR

# Update composer dependencies.
composer drupal-rebuild
composer update -n --lock --verbose

#TEMP: Delete broken test from address module.
rm modules/address/tests/src/Unit/Plugin/Validation/Constraint/CountryConstraintValidatorTest.php

# Require that decoupled_auth is always enabled when the user module is enabled.
git apply -v $DRUPAL_TI_DRUPAL_DIR/modules/decoupled_auth/travis-ci/contacts_user_modules_installed.patch

# Allow different simpletests to be run for pull requests by drupal_ti
cd ~/.composer/vendor/lionsad/drupal_ti
git apply -v $DRUPAL_TI_DRUPAL_DIR/modules/decoupled_auth/travis-ci/drupal_ti_pull_simpletest.patch
