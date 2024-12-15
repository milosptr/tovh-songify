<?php
namespace Drupal\musicsearch\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\musicsearch\Service\MusicImportService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the Music Import form.
 */
class MusicImportForm extends FormBase {
  protected MusicImportService $musicImportService;
  protected string $row_id;
  protected array $ignoredFields = ['available_markets', 'markets', 'height', 'width', 'barcode', 'label', 'formats'];

  public function __construct(MusicImportService $musicImportService) {
    $this->musicImportService = $musicImportService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): MusicImportForm
  {
    return new static(
      $container->get('musicsearch.import_service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'music_import_form';
  }

  /**
   * Build the form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $row_id = NULL): array
  {
    // Final step of the form, user is prompted to view the imported content
    if($form_state->get('node_id')) {
      $url = Url::fromRoute('entity.node.canonical', ['node' => $form_state->get('node_id')])->toString();
      $form['node_id'] = [
        '#type' => 'item',
        '#title' => '<h4>Content imported successfully. View the content <a href="'.$url.'">here</a></h4>',
      ];

      $form['actions'] = [
        '#type' => 'actions',
      ];

      $form['actions']['cancel'] = [
        '#type' => 'link',
        '#title' => $this->t('Back to search'),
        '#url' => Url::fromRoute('musicsearch.admin_search'),
        '#attributes' => ['class' => ['button', 'button--secondary']],
      ];

      return $form;
    }

    $this->row_id = $row_id;
    $session = Drupal::service('tempstore.private')->get('musicsearch');
    $results = $session->get('search_results');

    $data = null;
    foreach ($results as $result) {
      if ($result['id'] == $row_id) {
        $data = $result;
        break;
      }
    }

    if(!$data) {
      $this->messenger()->addMessage($this->t('Row ID @row_id not found.', ['@row_id' => $this->row_id]));
      return $form;
    }

    $form['page_title'] = [
      '#type' => 'item',
      '#title' => '<h4>Importing <u>' . $data['name'] . '</u> of type <u>' . $data['type'] . '</u> with ID <u>' . $data['id'] . '</u></h4>',
    ];

    // This is step 1 of the form, user is prompted to select the content type to import the data
    // I'm doing this because I want to show the user the fields available for import based on the content type
    if(!$form_state->get('type')) {
      $types = Drupal::entityTypeManager()
        ->getStorage('node_type')
        ->loadMultiple();

      $options = [];

      foreach ($types as $type) {
        $options[$type->id()] = $type->label();
      }

      $form['type'] = [
        '#type' => 'select',
        '#title' => $this->t('Type'),
        '#description' => $this->t('Choose the content type where to import the data.'),
        '#options' => $options,
        '#required' => TRUE,
        '#empty_option' => $this->t('Select a content type'),
        '#default_value' => strtolower($data['type'])
      ];

      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Next'),
      ];

      return $form;
    }

    // This is step 2 of the form, user is prompted to select the fields to import
    $availableFields = $this->musicImportService->getAvailableFieldsForContentType($form_state->get('type'));

    $form['field_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t(ucfirst($form_state->get('type')) . ' Title'),
      '#description' => $this->t('Enter the title for the content.'),
      '#required' => TRUE,
      '#default_value' => $data['name']
    ];

    $header = [
      'label' => $this->t('Label'),
      'data' => $this->t('Data'),
      'source' => $this->t('Source'),
      'import' => $this->t('Import to'),
    ];

    $form['fields_to_import'] = [
      '#type' => 'table',
      '#header' => $header,
      '#empty' => $this->t('No results found.'),
      '#attributes' => [
        'class' => ['musicsearch-import-fields'],
      ],
    ];

    $spotifyData = $data['spotify_data'];
    $discogsData = $data['discogs_data'];

    foreach($spotifyData as $key => $spotify) {
      $form = $this->parseTableFields($key, $spotify, $availableFields, $form, 'Spotify');
    }

    foreach($discogsData as $key => $discogs) {
      $form = $this->parseTableFields($key, $discogs, $availableFields, $form, 'Discogs');
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('musicsearch.admin_search'),
      '#attributes' => ['class' => ['button', 'button--secondary']],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
      '#button_type' => 'primary'
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    if(!$form_state->get('type')) {
      $form_state->set('type', $form_state->getValue('type'));
      $form_state->setRebuild();
      return;
    }

    $selectedFields = [];
    $formFields = $form_state->getValue('fields_to_import');

    foreach($formFields as $field) {
      if($field['import'] && $field['import'] != 'none') {
        $selectedFields[$field['import']] = $field['value'];
      }
    }

    try {
      $node_id = $this->musicImportService->import($form_state->get('type'), $form_state->getValue('field_title'), $selectedFields);
      $this->messenger()->addMessage($this->t('Content imported successfully.'));
      $form_state->set('node_id', $node_id);
      $form_state->setRebuild();
    } catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error importing content: @error', ['@error' => $e->getMessage()]));
    }
  }

  private function parseTableFields($key, $data, $availableFields, $form, $source)
  {
    if(!is_array($data)) {
      $flattenData = $this->musicImportService->flattenArray($data);
      if(!$flattenData) {
        return $form;
      }
      $form['fields_to_import'][$key]['label'] = [
        '#markup' => $key,
      ];
      $form['fields_to_import'][$key]['data'] = [
        '#markup' => $this->musicImportService->parseFieldData($key, $flattenData),
      ];
      $form['fields_to_import'][$key]['source'] = [
        '#markup' => $this->t($source),
      ];
      $form['fields_to_import'][$key]['import'] = [
        '#type' => 'select',
        '#title' => $this->t('Import to'),
        '#title_display' => 'invisible',
        '#options' => $availableFields,
        '#default_value' => '',
      ];
      $form['fields_to_import'][$key]['value'] = [
        '#type' => 'hidden',
        '#value' => $this->musicImportService->sanitizeUrl($flattenData),
      ];

      return $form;
    }

    foreach($data as $subkey => $subitem) {
      if(in_array($subkey, $this->ignoredFields) || in_array($key, $this->ignoredFields)) {
        continue;
      }
      $combinedKey = $key . '_' . $subkey;
      $form = $this->parseTableFields($combinedKey, $subitem, $availableFields, $form, $source);
    }

    return $form;
  }

}
