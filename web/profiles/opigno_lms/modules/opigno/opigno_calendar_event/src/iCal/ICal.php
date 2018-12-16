<?php

namespace Drupal\opigno_calendar_event\iCal;
use Drupal\opigno_calendar_event\iCal\ICalendar;
use Drupal\opigno_calendar_event\iCal\ICalendarEvent;

class ICal {

  /**
   * Build iCal file for events.
   * 
   * @param array $params
   */
  public function buildICalEvent($params) {
    $event = new ICalendarEvent($params);
    $params['events'] = [$event];
    $calendar = new ICalendar($params);
    return $calendar->generateString();
  }

}