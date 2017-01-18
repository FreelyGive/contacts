#!/bin/bash

set -e $DRUPAL_TI_DEBUG

# Run PHPUnit tests and submit code coverage statistics.
drupal_ti_ensure_drupal
drupal_ti_ensure_module_linked

echo 'Running coder review.'
composer global require drupal/coder
/home/travis/.composer/vendor/bin/phpcs --config-set installed_paths /home/travis/.composer/vendor/drupal/coder/coder_sniffer
cd $DRUPAL_TI_DRUPAL_DIR/modules/contacts
/home/travis/.composer/vendor/bin/phpcs . -p --standard=Drupal --colors --extensions=php,inc,test,module,install

cd $DRUPAL_TI_DRUPAL_DIR/core

echo 'Scheduling decoupled_auth phpunit tests to be run.'
$DRUPAL_TI_DRUPAL_DIR/vendor/bin/phpunit --group contacts
