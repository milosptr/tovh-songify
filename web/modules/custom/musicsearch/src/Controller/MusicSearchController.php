<?php

namespace Drupal\musicsearch\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Music Search admin page.
 */
class MusicSearchController extends ControllerBase {

  /**
   * Renders the admin search page.
   */
  public function searchContent(Request $request) {
    $form =  \Drupal::formBuilder()->getForm('Drupal\musicsearch\Form\MusicSearchForm');

    return [
      'form' => $form,
      '#attached' => [
        'library' => [
          'musicsearch/global-styling',
        ],
      ],
    ];
  }
}
