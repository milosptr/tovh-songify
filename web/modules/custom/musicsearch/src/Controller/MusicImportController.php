<?php

namespace Drupal\musicsearch\Controller;

use Drupal\Core\Controller\ControllerBase;

class MusicImportController extends ControllerBase {
  public function preview($row_id)
  {
    $row_id = str_replace(':', ' / ', $row_id);
    $session = \Drupal::service('tempstore.private')->get('musicsearch');
    $results = $session->get('search_results');

    if(!$results) {
      return $this->redirect('musicsearch.admin_search');
    }

    $importResult = null;
    foreach ($results as $result) {
      if ($result['id'] == $row_id) {
        $importResult = $result;
        break;
      }
    }

    if(!$importResult) {
      return $this->redirect('musicsearch.admin_search');
    }

    $form = \Drupal::formBuilder()->getForm('Drupal\musicsearch\Form\MusicImportForm', $row_id);

    return [
      '#markup' => $this->t('<h2>Import Preview</h2><p>Choose the fields you want to import for this record:</p>'),
      'form' => $form,
    ];
  }
}
