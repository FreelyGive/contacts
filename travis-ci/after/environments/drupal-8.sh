#!/bin/bash
# @file
# Drupal-8 environment variables and functions.

#
# Install drupal.
#
function drupal_ti_install_drupal() {
    if [ "$TRAVIS_EVENT_TYPE" = "cron" ]
    then
        composer create-project drupal/drupal:8.*@dev -n
    else
        composer create-project drupal/drupal -n
    fi

    cd drupal
    composer config extra.enable-patching true
    composer config extra.merge-plugin.merge-extra true
    composer require cweagans/composer-patches ~1.6
    composer install
    php -d sendmail_path=$(which true) ~/.composer/vendor/bin/drush.php --yes -v site-install "$DRUPAL_TI_INSTALL_PROFILE" --db-url="$DRUPAL_TI_DB_URL"
    drush use $(pwd)#default
}

#
# Ensures that the module is linked into the Drupal code base.
#
function drupal_ti_ensure_module_linked() {
	# Ensure we are in the right directory.
	cd "$DRUPAL_TI_DRUPAL_DIR"

	# This function is re-entrant.
	if [ -L "$DRUPAL_TI_MODULES_PATH/$DRUPAL_TI_MODULE_NAME" ]
	then
		return
	fi

	composer config repositories.$DRUPAL_TI_MODULE_NAME path $TRAVIS_BUILD_DIR
	composer config repositories.drupal composer https://packages.drupal.org/8
	composer require drupal/$DRUPAL_TI_MODULE_NAME *@dev
	git apply -v $DRUPAL_TI_DRUPAL_DIR/modules/contacts/travis-ci/merging_data_types-2693081-15_0.patch
}
