<?php

namespace Drupal\opigno_module\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Module edit forms.
 *
 * @ingroup opigno_module
 */
class OpignoModuleForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $module \Drupal\opigno_module\Entity\OpignoModule */
    $module = $this->entity;
    $form = parent::buildForm($form, $form_state);
    $form['advanced'] = [
      '#type' => 'vertical_tabs',
      '#weight' => 99,
    ];
    // Module Taking options.
    $form['opigno_module_taking_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Taking options'),
      '#attributes' => ['id' => 'taking-options-fieldset'],
      '#group' => 'advanced',
      '#weight' => 1,
    ];
    $form['allow_resume']['#group'] = 'opigno_module_taking_options';
    $form['backwards_navigation']['#group'] = 'opigno_module_taking_options';
    $form['hide_results']['#group'] = 'opigno_module_taking_options';

    $form['opigno_module_taking_options']['randomization'] = [
      '#type' => 'fieldset',
      '#weight' => 3,
    ];
    $form['randomization']['#group'] = 'randomization';
    $form['random_activities']['#group'] = 'randomization';
    $form['random_activities']['#states'] = [
      'visible' => [
        ':input[name=randomization]' => ['value' => '2'],
      ],
    ];

    $form['opigno_module_taking_options']['multiple_takes'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Multiple takes'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#attributes' => ['id' => 'multiple-takes-fieldset'],
      '#description' => $this->t('Allow users to take this Module multiple times.'),
      '#weight' => 4,
    ];
    $form['takes']['#group'] = 'multiple_takes';
    $form['show_attempt_stats']['#group'] = 'multiple_takes';
    $form['keep_results']['#group'] = 'multiple_takes';
    // Module Availability options.
    $form['opigno_module_availability'] = [
      '#type' => 'details',
      '#title' => $this->t('Availability options'),
      '#attributes' => ['id' => 'availability-fieldset'],
      '#group' => 'advanced',
      '#weight' => 2,
    ];
    $form['module_always']['#group'] = 'opigno_module_availability';
    $form['open_date']['#group'] = 'opigno_module_availability';
    $form['close_date']['#group'] = 'opigno_module_availability';

    // Badges settings.
    $form['opigno_badges_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Badges settings'),
      '#attributes' => ['id' => 'badges-fieldset'],
      '#group' => 'advanced',
      '#weight' => 3,
    ];
    $form['badge_active']['#group'] = 'opigno_badges_settings';
    $form['opigno_badges_settings']['opigno_badges_settings_group'] = [
      '#type' => 'fieldset',
      '#weight' => 2,
    ];
    $form['badge_name']['#group'] = 'opigno_badges_settings_group';
    $form['badge_description']['#group'] = 'opigno_badges_settings_group';
    $form['badge_criteria']['#group'] = 'opigno_badges_settings_group';
    unset($form['badge_criteria']['widget']['#options']['_none']);
    $form['badge_media_image']['#group'] = 'opigno_badges_settings_group';

    // Module Availability options.
    $form['opigno_module_feedback'] = [
      '#type' => 'details',
      '#title' => $this->t('Result feedback'),
      '#attributes' => ['id' => 'feedback-fieldset'],
      '#group' => 'advanced',
      '#weight' => 4,
    ];
    // Module results feedback options.
    $results_options = $module->getResultsOptions();
    $form['opigno_module_feedback']['results_options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Results feedback'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#attributes' => ['id' => 'multiple-takes-fieldset'],
      '#tree' => TRUE,
    ];
    for ($i = 0; $i < 5; $i++) {
      $form['opigno_module_feedback']['results_options'][$i] = [
        '#type' => 'details',
        '#title' => $this->t('Option %option_number', ['%option_number' => $i + 1]),
        '#collapsible' => TRUE,
        '#open' => FALSE,
        '#weight' => $i,
      ];
      // Open first option fields.
      if ($i == 0) {
        $form['opigno_module_feedback']['results_options'][$i]['#open'] = TRUE;
      }
      // Result option name.
      $form['opigno_module_feedback']['results_options'][$i]['option_name'] = [
        '#type' => 'textfield',
        '#title' => t('Range title'),
        '#default_value' => isset($results_options[$i]->{'option_name'}) ? $results_options[$i]->{'option_name'} : '',
        '#maxlength' => 40,
        '#size' => 40,
        '#description' => t('e.g., "A" or "Passed"'),
      ];
      // Result option range (low and high).
      $form['opigno_module_feedback']['results_options'][$i]['option_start'] = [
        '#type' => 'textfield',
        '#title' => t('Percentage low'),
        '#description' => t('Show this result for scored Module in this range (0-100).'),
        '#default_value' => isset($results_options[$i]->{'option_start'}) ? $results_options[$i]->{'option_start'} : '',
        '#size' => 5,
      ];
      $form['opigno_module_feedback']['results_options'][$i]['option_end'] = [
        '#type' => 'textfield',
        '#title' => t('Percentage high'),
        '#description' => t('Show this result for scored Module in this range (0-100).'),
        '#default_value' => isset($results_options[$i]->{'option_end'}) ? $results_options[$i]->{'option_end'} : '',
        '#size' => 5,
      ];
      // Result option text.
      $form['opigno_module_feedback']['results_options'][$i]['option_summary'] = [
        '#type' => 'text_format',
        '#base_type' => 'textarea',
        '#title' => t('Feedback'),
        '#default_value' => isset($results_options[$i]->{'option_summary'}) ? $results_options[$i]->{'option_summary'} : '',
        '#description' => t("This is the text that will be displayed when the user's score falls in this range."),
        '#format' => isset($results_options[$i]->{'option_summary_format'}) ? $results_options[$i]->{'option_summary_format'} : NULL,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Module.', [
          '%label' => $entity->label(),
        ]));
        /* @todo Find better way to save results options */
        // Save results options.
        $entity->insertResultsOptions($form_state);
        break;

      default:
        drupal_set_message($this->t('Saved the %label Module.', [
          '%label' => $entity->label(),
        ]));
        /* @todo Find better way to save results options */
        // Save results options.
        $entity->updateResultsOptions($form_state);
    }
    $form_state->setRedirect('entity.opigno_module.canonical', ['opigno_module' => $entity->id()]);
  }

}