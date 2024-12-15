<?php

namespace Drupal\musicsearch\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\musicsearch\Service\CombineResultsService;
use Drupal\musicsearch\Service\DiscogsLookupService;
use Drupal\musicsearch\Service\SpotifyLookupService;
use \Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the Music Search form.
 */
class MusicSearchForm extends FormBase {
  protected SpotifyLookupService $spotifyLookup;
  protected DiscogsLookupService $discogsLookup;
  protected CombineResultsService $combineResultsService;

  public function __construct(SpotifyLookupService $spotifyLookup, DiscogsLookupService $discogsLookup, CombineResultsService $combineResultsService) {
    $this->spotifyLookup = $spotifyLookup;
    $this->discogsLookup = $discogsLookup;
    $this->combineResultsService = $combineResultsService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): MusicSearchForm
  {
    return new static(
      $container->get('musicsearch.spotify_lookup'),
      $container->get('musicsearch.discogs_lookup'),
      $container->get('musicsearch.combine_results')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'music_search_form';
  }

  public function formFields($form)
  {
    $hasKeys = $this->validateAPIkeys();

    $form['description'] = [
      '#markup' => $this->t('Search for music on Spotify and Discogs that you can import.'),
    ];

    $form['search'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search'),
      '#description' => $this->t('Enter a song, album, or artist name.'),
      '#placeholder' => 'Search for music...',
      '#required' => TRUE,
      '#disabled' => !$hasKeys,
    ];

    $form['type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Search within types:'),
      '#options' => [
        'track' => $this->t('Track'),
        'album' => $this->t('Album'),
        'artist' => $this->t('Artist'),
      ],
      '#required' => TRUE,
      '#attributes' => [
        'class' => ['musicsearch-types'],
      ],
      '#default_value' => 'track',
      '#disabled' => !$hasKeys,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#disabled' => !$hasKeys,
    ];

    return $form;
  }

  public function displayResults($form, $form_state) {
    if ($results = $form_state->get('results')) {
      $form['search-results-subtitle'] = [
        '#type' => 'item',
        '#markup' => '<div>Search results for: <strong>'. $form_state->get('search_query') .'</strong></div>',
      ];

      $header = [
        'name' => $this->t('Name'),
        'type' => $this->t('Type'),
        'artists' => $this->t('Artists'),
        'release_year' => $this->t('Release Year'),
        'genre' => $this->t('Genre'),
        'source' => $this->t('Source'),
        'id' => $this->t('Spotify ID / Discog ID'),
        'link' => $this->t('Details'),
        'actions' => $this->t('Actions'),
      ];

      $rows = [];

      foreach ($results as $result) {
        if ($result['id']) {
          $rows[] = [
            'name' => $result['name'] ?? $this->t('Unknown'),
            'type' => $result['type'] ?? $this->t('Unknown'),
            'artists' => $result['artists'] ?? $this->t('N/A'),
            'release_year' => $result['release_year'] ?? $this->t('N/A'),
            'genre' => $result['genre'] ?? $this->t('N/A'),
            'source' => $result['source'] ?? $this->t('N/A'),
            'id' => $result['id'],
            'link' => [
              'data' => [
                '#type' => 'link',
                '#title' => $this->t('View on Spotify'),
                '#url' => Url::fromUri($result['link'], [
                  'attributes' => [
                    'target' => '_blank',
                  ],
                ]),
              ],
            ],
            'actions' => [
              'data' => [
                '#type' => 'link',
                '#title' => $this->t('Import'),
                '#url' => Url::fromRoute('musicsearch.import_preview', [
                  'row_id' => str_replace(' / ', ':', $result['id']),
                ]),
              ],
            ],
          ];
        }
      }

      $form['results'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => $this->t('No results found.'),
        '#attributes' => [
          'class' => ['musicsearch-results'],
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = $this->formFields($form);
    $form = $this->displayResults($form, $form_state);

    return $form;
  }


  /**
   * Validates the API keys. If any of the keys are missing, an error message is displayed.
   * @return bool
   */
  public function validateAPIkeys(): bool
  {
    $config = \Drupal::config('musicsearch.settings');
    $spotify_client_id = $config->get('spotify_client_id');
    $spotify_client_secret = $config->get('spotify_client_secret');
    $discogs_consumer_key = $config->get('discogs_consumer_key');
    $discogs_consumer_secret = $config->get('discogs_consumer_secret');

    if (!$spotify_client_id || !$spotify_client_secret || !$discogs_consumer_key || !$discogs_consumer_secret) {
      $this->messenger()->addError($this->t('API keys are not set. Please configure the keys in the Settings page.'));
      return false;
    }

    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): bool
  {
    if(!$this->validateAPIkeys()) {
      return false;
    }

    $search_query = $form_state->getValue('search');
    $form_types = $form_state->getValue('type');
    $this->spotifyLookup->setSearchTypes($form_types);

    $spotifyResults = $this->spotifyLookup->search($search_query);
    $discogsResults = $this->discogsLookup->search($search_query, $form_types);

    $searchResults = $this->combineResultsService->combineResults($spotifyResults, $discogsResults, $form_types);

    if(isset($searchResults['error'])) {
      $this->messenger()->addError($this->t('Error: @error', ['@error' => $searchResults['error']]));
      return false;
    }

    if(!empty($searchResults)) {
      $form_state->set('results', $searchResults);
      $form_state->set('search_query', $search_query);
      $session = \Drupal::service('tempstore.private')->get('musicsearch');
      $session->set('search_results', $searchResults);
    }

    $form_state->setRebuild();
    return true;
  }
}
