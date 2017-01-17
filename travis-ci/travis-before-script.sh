#!/bin/bash

set -e $DRUPAL_TI_DEBUG

# Ensure the right Drupal version is installed.
# Note: This function is re-entrant.
drupal_ti_ensure_drupal

# Download module dependencies.
mkdir -p "$DRUPAL_TI_DRUPAL_DIR/$DRUPAL_TI_MODULES_PATH"
cd "$DRUPAL_TI_DRUPAL_DIR/$DRUPAL_TI_MODULES_PATH"
git clone --branch 8.x-1.x http://git.drupal.org/project/composer_manager.git --depth 1

# Find absolute path to module.
MODULE_DIR=$(cd "$TRAVIS_BUILD_DIR"; pwd)
# Ensure directory exists.
rm -rf "$DRUPAL_TI_MODULE_NAME"
# Point module into the drupal installation.
ln -sf "$MODULE_DIR" "$DRUPAL_TI_MODULE_NAME"

php composer_manager/scripts/init.php

cd $DRUPAL_TI_DRUPAL_DIR

# Update composer dependencies.
composer drupal-rebuild
composer config repositories.0 composer https://packages.drupal.org/8
composer config extra.enable-patching true
composer config extra.merge-plugin.merge-extra true
composer update -n --lock --verbose --with-dependencies

#TEMP: Delete broken test from address module.
rm modules/address/tests/src/Unit/Plugin/Validation/Constraint/CountryConstraintValidatorTest.php

# Fix core schema bug.
git apply -v $DRUPAL_TI_DRUPAL_DIR/modules/contacts/travis-ci/merging_data_types-2693081-15_0.patch

# Require that contacts is always enabled when the user module is enabled.
git apply -v $DRUPAL_TI_DRUPAL_DIR/modules/contacts/travis-ci/contacts_user_modules_installed.patch

# Allow different simpletests to be run for pull requests by drupal_ti
cd ~/.composer/vendor/yanniboi/drupal_ti
git apply -v $DRUPAL_TI_DRUPAL_DIR/modules/contacts/travis-ci/drupal_ti_pull_simpletest.patch
