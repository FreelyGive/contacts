#!/usr/bin/env bash

# Remove the git details from our repo so we can treat it as a path.
rm $TRAVIS_BUILD_DIR/.git -rf

# Clone contacts_theme and remove the git details so we can treat it as a path.
git clone --branch=8.x-1.x https://github.com/FreelyGive/contacts_theme.git $DRUPAL_BUILD_ROOT/contacts_theme
rm $DRUPAL_BUILD_ROOT/contacts_theme/.git -rf

# Create our main Drupal Repo.
composer create-project drupal-composer/drupal-project:8.x-dev $DRUPAL_BUILD_ROOT/drupal --stability dev --no-interaction --no-install
cd $DRUPAL_BUILD_ROOT/drupal

# Set our drupal core version.
composer require drupal/core $DRUPAL_CORE --no-update

# Add our repositories for contacts and contact_theme, as well as re-adding
# the Drupal package repo.
composer config repositories.0 path $TRAVIS_BUILD_DIR
composer config repositories.0 path $DRUPAL_BUILD_ROOT/contacts_theme
composer config repositories.2 composer https://packages.drupal.org/8

# Now require contacts which will pull itself and contacts_theme from the paths.
composer require drupal/contacts dev-master
