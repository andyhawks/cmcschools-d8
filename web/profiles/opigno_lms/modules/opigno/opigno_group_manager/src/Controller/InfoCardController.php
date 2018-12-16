<?php

namespace Drupal\opigno_group_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class InfoCardController extends ControllerBase
{
    public function hideInfoCard()
    {
        $tempstore = \Drupal::service('user.private_tempstore')->get('opigno_group_manager');
        $tempstore->set('hide_info_card', true);
        return new JsonResponse(null, Response::HTTP_OK);
    }
}
