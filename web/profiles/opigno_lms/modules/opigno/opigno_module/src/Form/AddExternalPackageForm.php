<?php

namespace Drupal\opigno_module\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\h5p\H5PDrupal\H5PDrupal;
use Drupal\opigno_module\Controller\ExternalPackageController;
use Drupal\opigno_module\Controller\OpignoModuleManagerController;
use Drupal\opigno_module\Entity\OpignoActivity;

/**
 * Add External package form.
 */
class AddExternalPackageForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_external_package_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $mode = NULL) {
    $is_ppt = ($mode && $mode == 'ppt') ? TRUE : FALSE;
    if ($is_ppt) {
      $form_state->set('mode', $mode);
    }

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#required' => TRUE,
    ];

    $form['package'] = [
      '#title' => t('Package'),
      '#type' => 'file',
      '#description' => !$is_ppt ? t('Here you can upload external package. Allowed extensions: zip h5p') : t('Here you can upload PowerPoint presentation file. Allowed extensions: ppt pptx'),
    ];

    $ajax_id = "ajax-form-entity-external-package";
    $form['#attributes']['class'][] = $ajax_id;
    $form['#attached']['library'][] = 'opigno_module/ajax_form';

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Next'),
      '#ajax' => [
        'callback' => 'Drupal\opigno_module\Controller\ExternalPackageController::ajaxFormExternalPackageCallback',
        'wrapper' => $ajax_id,
        'effect' => 'fade'
      ]
    ];

    $form['actions']['submit']['#submit'][] = 'Drupal\opigno_module\Controller\ExternalPackageController::ajaxFormExternalPackageFormSubmit';

    $form['ajax_form_entity'] = [
      '#type' => 'hidden',
      '#value' => [
        'view_mode' => 'default',
        'reload' => TRUE,
        'content_selector' => ".$ajax_id",
        'form_selector' => ".$ajax_id",
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validation is optional.
    $file_field = "package";
    $storage = $form_state->getStorage();
    $is_ppt = (isset($storage['mode']) && $storage['mode'] == 'ppt') ? TRUE : FALSE;
    if (empty($_FILES['files']['name'][$file_field])) {
      // Only need to validate if the field actually has a file.
      $form_state->setError(
        $form['package'],
        $this->t("Files isn't uploaded.")
      );
    }

    // Prepare folder.
    $temporary_file_path = !$is_ppt ? 'public://external_packages' : 'public://' . ExternalPackageController::getPptConversionDir();
    file_prepare_directory($temporary_file_path, FILE_CREATE_DIRECTORY);

    // Prepare file validators.
    $extensions = !$is_ppt ? ['h5p zip'] : ['ppt pptx'];
    $validators = [
      'file_validate_extensions' => $extensions,
    ];
    // Validate file.
    if ($is_ppt) {
      $ppt_dir = ExternalPackageController::getPptConversionDir();
      $public_files_real_path = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
      $ppt_dir_real_path = $public_files_real_path . '/' . $ppt_dir;

      $file = file_save_upload($file_field, $validators, $temporary_file_path, NULL, FILE_EXISTS_REPLACE);

      // Rename uploaded file - remove special chars.
      $file_new = $file[0];
      $filename = $file_new->getFilename();
      $filename_new = preg_replace('/[^a-zA-Z0-9-_\.]/', '-', $filename);
      $file_new->setFilename($filename_new);
      $file_new->setFileUri($temporary_file_path . '/' . $filename_new);
      $file_new->save();
      rename($ppt_dir_real_path . '/' . $filename, $ppt_dir_real_path . '/' . $filename_new);

      if (!empty($file_new)) {
        // Actions on ppt(x) file upload.
        $ppt_dir = ExternalPackageController::getPptConversionDir();
        $public_files_real_path = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
        $ppt_dir_real_path = $public_files_real_path . '/' . $ppt_dir;

        $this->logger('ppt_converter')->notice('$ppt_dir_real_path: ' . $ppt_dir_real_path);

        $images = ExternalPackageController::convertPptSlidesToImages($file_new, $ppt_dir_real_path);

        if ($images) {

          \Drupal::logger('ppt_converter')->notice('$images: <pre><code>' . print_r($images, TRUE) . '</code></pre>');

          // Create H5P package in 'sites/default/files/external_packages_ppt'.
          ExternalPackageController::createH5pCoursePresentationPackage($images, $ppt_dir_real_path, $form_state->getValue('name'));
        }

        if (file_exists($temporary_file_path . '/ppt-content-import.h5p')) {
          // Replace form uploaded file with converted h5p content file.
          $file_new = File::load($file[0]->id());
          $file_new->setFilename('ppt-content-import.h5p');
          $file_new->setFileUri($temporary_file_path . '/ppt-content-import.h5p');
          $file_new->setMimeType('application/octet-stream');
          $file_new->save();

          $file[0] = $file_new;
        }
      }
    }
    else {
      $file = file_save_upload($file_field, $validators, $temporary_file_path);
    }

    if (!$file[0]) {
      return $form_state->setRebuild();
    };
    // Set file id in form state for loading on submit.
    $form_state->set('package', $file[0]->id());

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // $fid = $form_state->get('package');
    // $file = File::load($fid);
    //
    // if (!empty($file)) {
    //   // Get file extension.
    //   $extension = $this->getFileExtension($file->getFilename());
    //   switch ($extension) {
    //     case 'zip':
    //       // Check file type. Can be "scorm" or "tincan".
    //       $type = $this->checkPackageType($file);
    //
    //       if (!$type) {
    //         $this->messenger()->addError($this->t("Package does not contain required files."));
    //         return $form_state->setRebuild();
    //       }
    //       // Create activity.
    //       $activity = $this->createActivityByPackageType($file, $type, $form, $form_state);
    //       break;
    //
    //     case 'h5p':
    //       $activity = $this->createActivityByPackageType($file, 'h5p', $form, $form_state);
    //       break;
    //   }
    //   if (!$activity) {
    //     $this->messenger()->addWarning(t("Can't create activity."));
    //     return $form_state->setRedirect('<current>');
    //   };
    //   // Redirect to activity canonical path.
    //   $url = $activity->toUrl();
    //   return $form_state->setRedirect($url->getRouteName(), ['opigno_activity' => $activity->id()]);
    // }
    //
    // return $form_state->setRebuildInfo([t("Can't create an activity. File can't be uploaded.")]);
  }

  // All of this was moved into controller (ajaxify)

  // /**
  //  * Function for checking what package type was downloaded.
  //  *
  //  * @return string | boolen
  //  *   Returned 'scorm' or 'tincan' or FALSE.
  //  *
  //  */
  // protected function checkPackageType($file) {
  //   // Unzip file.
  //   $path = \Drupal::service('file_system')->realpath($file->getFileUri());
  //   $zip = new \ZipArchive();
  //   $result = $zip->open($path);
  //   if ($result === TRUE) {
  //     $extract_dir = 'public://external_package_extracted/package_' . $file->id();
  //     $zip->extractTo($extract_dir);
  //     $zip->close();
  //
  //     // This is a standard: these files must always be here.
  //     $scorm_file = $extract_dir . '/imsmanifest.xml';
  //     $tincan_file = $extract_dir . '/tincan.xml';
  //
  //     if (file_exists($scorm_file)) {
  //       $package_type = 'scorm';
  //     }
  //     elseif (file_exists($tincan_file)) {
  //       $package_type = 'tincan';
  //     }
  //     // Delete extracted archive.
  //     file_unmanaged_delete_recursive($extract_dir);
  //
  //     if (isset($package_type)) {
  //       return $package_type;
  //     }
  //
  //   }
  //   return FALSE;
  // }
  //
  // /**
  //  * Function for creating activity depending file type.
  //  *
  //  * @param \Drupal\File\entity\File $file
  //  * @param string $package_type
  //  * @param array $form
  //  * @param \Drupal\Core\Form\FormStateInterface $form_state
  //  *
  //  * @return object | boolean
  //  *   Returned activity object or FALSE
  //  *
  //  * @throws \Drupal\Core\Entity\EntityStorageException
  //  */
  // protected function createActivityByPackageType(File $file, $package_type, array &$form, FormStateInterface $form_state) {
  //
  //   switch ($package_type) {
  //     // Create Scorm_package activity.
  //     case 'scorm':
  //       $activity = OpignoActivity::create([
  //         'type' => 'opigno_scorm',
  //         'name' => $form_state->getValue('name'),
  //         'opigno_scorm_package' => [
  //           'target_id' => $file->id(),
  //         ],
  //       ]);
  //
  //       $activity->save();
  //       break;
  //
  //     // Create Tincan_package activity.
  //     case 'tincan':
  //       $has_library = opigno_tincan_api_tincanphp_is_installed();
  //       if (!$has_library) {
  //         $this->messenger()->addError(t('Impossible to create a new TinCan Package question type.'));
  //         return FALSE;
  //       };
  //       $activity = OpignoActivity::create([
  //         'type' => 'opigno_tincan',
  //         'name' => $form_state->getValue('name'),
  //         'opigno_tincan_package' => [
  //           'target_id' => $file->id(),
  //         ],
  //       ]);
  //
  //       $activity->save();
  //       break;
  //
  //     // Create Interactive content activity.
  //     case 'h5p':
  //       $h5p_content_id = $this->createH5pContent($file);
  //       if (!$h5p_content_id) {
  //         $this->messenger()->addError($this->t("Can't create h5p content. Wrong h5p package."));
  //         return FALSE;
  //       };
  //       $activity = OpignoActivity::create([
  //         'type' => 'opigno_h5p',
  //         'name' => $form_state->getValue('name'),
  //         'opigno_h5p' => [
  //           'h5p_content_id' => $h5p_content_id,
  //         ],
  //       ]);
  //
  //       $activity->save();
  //       break;
  //   }
  //
  //   return $activity;
  // }
  //
  // /**
  //  * Function for creating h5p content for Interactive content activity.
  //  *
  //  * @param \Drupal\file\Entity\File $file
  //  *
  //  * @return int | boolean
  //  *   Return h5p content id or FALSE.
  //  */
  // protected function createH5pContent(File $file) {
  //   $file_field = 'package';
  //
  //   // Prepare temp folder.
  //   $interface = H5PDrupal::getInstance('interface', $file_field);
  //   $temporary_file_path = 'public://external_packages';
  //
  //   // Tell H5P Core where to look for the files.
  //   $interface->getUploadedH5pPath(\Drupal::service('file_system')
  //     ->realpath($file->getFileUri()));
  //   $interface->getUploadedH5pFolderPath(\Drupal::service('file_system')
  //     ->realpath($temporary_file_path));
  //
  //   // Call upon H5P Core to validate the contents of the package.
  //   $validator = H5PDrupal::getInstance('validator', $file_field);
  //   $validator->isValidPackage();
  //
  //   // Store the uploaded file.
  //   $storage = H5PDrupal::getInstance('storage', $file_field);
  //
  //   $content = [
  //     'id' => NULL,
  //     'uploaded' => TRUE,
  //     'disable' => 0,
  //   ];
  //
  //   // Save and update content id.
  //   $storage->savePackage($content);
  //   $content_id = $storage->contentId;
  //
  //   if (!$content_id) {
  //     return FALSE;
  //   };
  //   return $content_id;
  // }
  //
  // /**
  //  * @param \Drupal\opigno_module\Form\string $file_name
  //  *
  //  * @return string
  //  */
  // protected function getFileExtension($file_name) {
  //   return substr(strrchr($file_name, '.'), 1);
  // }

}
