<?php

namespace Drupal\opigno_h5p;

/**
 * Class H5PReport
 * @property  fillInProcessor
 */
class H5PReport {

  private static $processorMap = array(
    'compound'    => 'Drupal\opigno_h5p\TypeProcessors\CompoundProcessor',
    'fill-in'     => 'Drupal\opigno_h5p\TypeProcessors\FillInProcessor',
    'true-false'  => 'Drupal\opigno_h5p\TypeProcessors\TrueFalseProcessor',
    'matching'    => 'Drupal\opigno_h5p\TypeProcessors\MatchingProcessor',
    'choice'      => 'Drupal\opigno_h5p\TypeProcessors\ChoiceProcessor',
    'long-choice' => 'Drupal\opigno_h5p\TypeProcessors\LongChoiceProcessor',
  );

  private $processors = array();

  /**
   * Generate the proper report depending on xAPI data.
   *
   * @param object $xapiData
   * @param string $forcedProcessor Force a processor type
   * @param bool $disableScoring Disables scoring for the report
   *
   * @return string A report
   */
  public function generateReport($xapiData, $forcedProcessor = NULL, $disableScoring = FALSE) {
    $interactionType = isset($forcedProcessor) ? $forcedProcessor :
      $xapiData->interaction_type;

    if (!isset(self::$processorMap[$interactionType])) {
      return ''; // No processor found
    }

    if (!isset($this->processors[$interactionType])) {
      // Not used before. Initialize new processor
      $this->processors[$interactionType] = new self::$processorMap[$interactionType]();
    }

    // Generate and return report from xAPI data
    return $this->processors[$interactionType]
      ->generateReport($xapiData, $disableScoring);
  }

  /**
   * List of CSS stylesheets used by the processors when rendering the report.
   *
   * @return array
   */
  public function getStylesUsed() {
    $styles = [];
    // Fetch style used by each report processor.
    foreach ($this->processors as $processor) {
      $style = $processor->getStyle();
      if (!empty($style)) {
        $styles[] = $style;
      }
    }

    return $styles;
  }

  /**
   * Caches instance of report generator.
   * @return H5PReport
   */
  public static function getInstance() {
    static $instance;

    if (!$instance) {
      $instance = new H5PReport();
    }

    return $instance;
  }

}

