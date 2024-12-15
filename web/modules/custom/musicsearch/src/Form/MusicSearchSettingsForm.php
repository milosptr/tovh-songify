<?php

namespace Drupal\musicsearch\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the Spotify API configuration form.
 */
class MusicSearchSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'musicsearch_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array
  {
    return ['musicsearch.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $config = $this->config('musicsearch.settings');

    $form['spotify_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Spotify Client ID'),
      '#default_value' => $config->get('spotify_client_id'),
      '#required' => TRUE,
    ];

    $form['spotify_client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Spotify Client Secret'),
      '#default_value' => $config->get('spotify_client_secret'),
      '#required' => TRUE,
    ];

    $form['discogs_consumer_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Discogs Consumer Key'),
      '#default_value' => $config->get('discogs_consumer_key'),
      '#required' => TRUE,
    ];

    $form['discogs_consumer_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Discogs Consumer Secret'),
      '#default_value' => $config->get('discogs_consumer_secret'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $this->config('musicsearch.settings')
      ->set('spotify_client_id', $form_state->getValue('spotify_client_id'))
      ->set('spotify_client_secret', $form_state->getValue('spotify_client_secret'))
      ->set('discogs_consumer_key', $form_state->getValue('discogs_consumer_key'))
      ->set('discogs_consumer_secret', $form_state->getValue('discogs_consumer_secret'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
