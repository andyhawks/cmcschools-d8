<?php

namespace Drupal\opigno_moxtra\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\opigno_moxtra\Entity\Workspace;
use Drupal\opigno_moxtra\MoxtraServiceInterface;
use Drupal\opigno_moxtra\OpignoServiceInterface;
use Drupal\opigno_moxtra\WorkspaceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Render\FormattableMarkup;

class WorkspaceController extends ControllerBase {

  /** @var \Drupal\opigno_moxtra\OpignoServiceInterface $opignoService */
  protected $opignoService;

  /** @var \Drupal\opigno_moxtra\MoxtraServiceInterface $moxtraService */
  protected $moxtraService;

  /**
   * Creates new WorkspaceController instance.
   *
   * @param \Drupal\opigno_moxtra\OpignoServiceInterface $opigno_service
   *   Opigno API service.
   * @param \Drupal\opigno_moxtra\MoxtraServiceInterface $moxtra_service
   *   Moxtra API service.
   */
  public function __construct(
    OpignoServiceInterface $opigno_service,
    MoxtraServiceInterface $moxtra_service
  ) {
    $this->opignoService = $opigno_service;
    $this->moxtraService = $moxtra_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('opigno_moxtra.opigno_api'),
      $container->get('opigno_moxtra.moxtra_api')
    );
  }

  /**
   * Returns index page for the collaborative workspace.
   *
   * @param WorkspaceInterface $opigno_moxtra_workspace
   *   The Collaborative Workspace.
   *
   * @return array
   *   Render array.
   */
  public function index(WorkspaceInterface $opigno_moxtra_workspace) {
    $config = $this->config('opigno_moxtra.settings');
    $client_id = $config->get('client_id');
    $org_id = $config->get('org_id');

    $user = $this->currentUser();
    $access_token = $this->opignoService->getToken($user->id());
    $binder_id = $opigno_moxtra_workspace->getBinderId();

    return [
      '#type' => 'container',
      'workspace_container' => [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'collaborative_workspace_container',
        ],
      ],
      '#attached' => [
        'library' => [
          'opigno_moxtra/moxtra.js',
          'opigno_moxtra/workspace',
        ],
        'drupalSettings' => [
          'opignoMoxtra' => [
            'mode' => 'production',
            'clientId' => $client_id,
            'orgId' => $org_id,
            'accessToken' => $access_token,
            'binderId' => $binder_id,
          ],
        ],
      ],
    ];
  }

  public function learningPath(WorkspaceInterface $opigno_moxtra_workspace)
  {
    $config = $this->config('opigno_moxtra.settings');
    $client_id = $config->get('client_id');
    $org_id = $config->get('org_id');

    $user = $this->currentUser();
    $access_token = $this->opignoService->getToken($user->id());
    $binder_id = $opigno_moxtra_workspace->getBinderId();
    $current_workspace_id = $opigno_moxtra_workspace->id();

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['row'],
      ],
      '#attached' => [
        'library' => [
          'opigno_moxtra/moxtra.js',
          'opigno_moxtra/workspace',
        ],
        'drupalSettings' => [
          'opignoMoxtra' => [
            'mode' => 'production',
            'clientId' => $client_id,
            'orgId' => $org_id,
            'accessToken' => $access_token,
            'binderId' => $binder_id,
          ],
        ],
      ],
      [
        '#type' => 'html_tag',
        '#tag' => 'div',
        'content' => $this->workspaceList($current_workspace_id, TRUE),
        '#attributes' => [
          'class' => ['col-md-4'],
        ],
      ],
      [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'collaborative_workspace_container',
          'class' => ['col-md-8'],
        ],
      ],
    ];
  }

  /**
   * Returns list of the collaborative workspaces available to the current user.
   *
   * @return array
   *   Render array.
   */
  public function workspaceList($current_workspace_id = NULL, $learning_path_iframe = NULL) {
    $user = $this->currentUser();
    $workspace_ids = $this->entityTypeManager()
      ->getStorage('opigno_moxtra_workspace')
      ->getQuery()
      ->condition('members', $user->id())
      ->execute();
    $workspaces = Workspace::loadMultiple($workspace_ids);
    if (!empty($current_workspace_id)) {
      $current_workspace = $workspaces[$current_workspace_id];
      unset($workspaces[$current_workspace_id]);
      array_unshift($workspaces, $current_workspace);
    }
    $items = array_map(function ($workspace) use ($learning_path_iframe) {
      if ($workspace->access('edit')) {
        if (!$learning_path_iframe) {
          $workspace_link = Link::createFromRoute(
            $workspace->getName(),
            'opigno_moxtra.workspace',
            ['opigno_moxtra_workspace' => $workspace->id()],
            ['attributes' => ['class' => ['mouseover-effect']]]
          )->toRenderable();
        } else {
          $workspace_link = Link::createFromRoute(
            $workspace->getName(),
            'opigno_moxtra.workspace.iframe',
            ['opigno_moxtra_workspace' => $workspace->id()],
            ['attributes' => ['class' => ['mouseover-effect']]]
          )->toRenderable();
        }

        $workspace_link_rendered = drupal_render($workspace_link);
        $workspace_edit = Link::createFromRoute(
          t('edit'),
          'entity.opigno_moxtra_workspace.edit_form',
          ['opigno_moxtra_workspace' => $workspace->id()],
          ['attributes' => ['class' => ['edit-link']]]
        )->toRenderable();
        $workspace_edit_rendered = drupal_render($workspace_edit);

        return new FormattableMarkup('<div class="d-flex edit">' . $workspace_link_rendered . $workspace_edit_rendered . '</div>', []);
      } else {

        /** @var \Drupal\opigno_moxtra\WorkspaceInterface $workspace */
        if (!$learning_path_iframe) {
          $link = Link::createFromRoute(
            $workspace->getName(),
            'opigno_moxtra.workspace',
            ['opigno_moxtra_workspace' => $workspace->id()],
            ['attributes' => ['class' => ['mouseover-effect']]]
          )->toRenderable();
        } else {
          $link = Link::createFromRoute(
            $workspace->getName(),
            'opigno_moxtra.workspace.iframe',
            ['opigno_moxtra_workspace' => $workspace->id()],
            ['attributes' => ['class' => ['mouseover-effect']]]
          )->toRenderable();
        }

        return $link;
      }
    }, $workspaces);

    $data = [
      'list' => [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#title' => 'Collaborative Workspaces',
        '#items' => $items,
      ],
    ];

    if ($user->hasPermission('add workspace entities')) {
      $data['create_prefix'] = [
        '#type' => 'markup',
        '#markup' => '<div class="actions mt-3"><h3>' . t('Actions') . '</h3><div class="link-wrapper">',
      ];
      $data['create'] = Link::createFromRoute(
        'Create collaborative workspace',
        'entity.opigno_moxtra_workspace.create_form'
      )->toRenderable();
      $data['create_suffix'] = [
        '#type' => 'markup',
        '#markup' => '</div></div>',
      ];
    }

    return $data;
  }

}
