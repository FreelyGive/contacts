<?php

// Plugin definition
$plugin = array(
  'title' => t('Contacts Dashboard'),
  'category' => t('Columns: 2'),
  'icon' => 'dashboard.png',
  'theme' => 'contacts_dashboard',
  'css' => 'dashboard.css',
  'regions' => array(
    'left' => t('Search'),
    'right' => t('View')
  ),
);
