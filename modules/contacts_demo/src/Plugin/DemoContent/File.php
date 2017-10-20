<?php

namespace Drupal\contacts_demo\Plugin\DemoContent;

use Drupal\contacts_demo\DemoFile;

/**
 * @DemoContent(
 *   id = "file",
 *   label = @Translation("File"),
 *   source = "content/entity/file.yml",
 *   entity_type = "file"
 * )
 */
class File extends DemoFile {

}
