<?php

/**
 * @file
 * Contains \Drupal\opigno_video\Controller\ImagePopup.
 */

namespace Drupal\opigno_video\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File;
use Drupal\Core\Url;

/**
 * Class ImagePopup.
 *
 * @package Drupal\opigno_video\Controller
 */
class ImagePopup extends ControllerBase {
  /**
   * Render.
   *
   * @return string
   *   Return Hello string.
   */
  public function render($fid) {
    $file = File::load($fid);

    return [
      '#theme' => 'image_style',
      '#style_name' => 'large',
      '#uri' => $file->getFileUri(),
    ];
  }

}