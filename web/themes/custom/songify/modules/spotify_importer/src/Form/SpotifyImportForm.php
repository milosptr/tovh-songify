<?php

namespace Drupal\spotify_importer\Form;

use Drupal\Core\File\FileExists;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

class SpotifyImportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'spotify_import_form';
  }

  /**
   * {@inheritdoc}
   */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $upload_directory = 'public://spotify_json/';
        \Drupal::service('file_system')->prepareDirectory($upload_directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY | \Drupal\Core\File\FileSystemInterface::MODIFY_PERMISSIONS);


        $form['spotify_json'] = [
            '#type' => 'managed_file',
            '#title' => $this->t('Upload Spotify JSON File'),
            '#description' => $this->t('Upload the JSON file obtained from the Spotify API response.'),
            '#upload_location' => $upload_directory,
            '#required' => TRUE,
            '#upload_validators' => [
                'FileExtension' => ['extensions' => 'json'],
            ],
        ];

        $form['actions'] = [
            '#type' => 'actions',
        ];

        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Import'),
            '#button_type' => 'primary',
        ];

        return $form;
    }

    /**
   * {@inheritdoc}
   */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        try {
            $fid = $form_state->getValue('spotify_json')[0];

            if ($fid) {
                $file = \Drupal\file\Entity\File::load($fid);
                $file->setPermanent();
                $file->save();

                $data = file_get_contents($file->getFileUri());
                $json = json_decode($data, TRUE);

                if ($json && isset($json['tracks'])) {
                    foreach ($json['tracks'] as $track_data) {
                        $this->processTrack($track_data);
                    }
                    $this->messenger()->addStatus($this->t('Import completed successfully.'));
                } else {
                    $this->messenger()->addError($this->t('Invalid JSON file.'));
                }
            } else {
                $this->messenger()->addError($this->t('File upload failed.'));
            }
        } catch (FileExists $e) {
            $this->messenger()->addError($this->t('File upload failed!!! Am I ever here?'));
        }
    }

    /**
   * Processes individual track data.
   */
  private function processTrack($track_data) {
    // Process Artist
    foreach ($track_data['artists'] as $artist_data) {
      $artist_node = $this->getOrCreateArtist($artist_data);
    }

    // Process Album
    $album_data = $track_data['album'];
    $album_node = $this->getOrCreateAlbum($album_data, $artist_node);

    // Process Track
    $track_node = $this->createTrack($track_data, $artist_node, $album_node);
  }

    /**
     * Gets or creates an Artist node.
     */
    private function getOrCreateArtist($artist_data) {
        $artist_name = $artist_data['name'];

        $query = \Drupal::entityQuery('node')
            ->condition('type', 'artist')
            ->condition('field_name', $artist_name)
            ->accessCheck(FALSE);

        $nids = $query->execute();

        if (!empty($nids)) {
            $artist_node = Node::load(reset($nids));
        }
        else {
            $artist_node = Node::create([
                'type' => 'artist',
                'title' => $artist_data['name'],
                'field_name' => $artist_data['name'],
                'field_spotify_id' => $artist_data['id'],
            ]);
            $artist_node->save();
        }
        return $artist_node;
    }

  /**
   * Gets or creates an Album node.
   */
  private function getOrCreateAlbum($album_data, $artist_node) {
    $album_id = $album_data['id'];
    $album_name = $album_data['name'];

    $query = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'album')
      ->condition('field_name', $album_name)
      ->range(0, 1);
    $nids = $query->execute();

    if (!empty($nids)) {
      $album_node = Node::load(reset($nids));
    }
    else {
      $album_node = Node::create([
        'type' => 'album',
        'title' => $album_data['name'],
        'field_name' => $album_data['name'],
        'field_spotify_id' => $album_id,
        'field_artist' => [
          'target_id' => $artist_node->id(),
        ],
        'field_release_date' => $album_data['release_date'],
      ]);

      if (!empty($album_data['images'][0]['url'])) {
        $this->attachImage($album_node, 'field_image', $album_data['images'][0]['url']);
      }

      $album_node->save();
    }
    return $album_node;
  }

  /**
   * Creates a Track node.
   */
  private function createTrack($track_data, $artist_node, $album_node) {

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'tracks')
      ->condition('title', $track_data['name'])
      ->accessCheck(FALSE)
      ->range(0, 1);
    $nids = $query->execute();

    if (!empty($nids)) {
      $track_node = Node::load(reset($nids));
    }
    else {
      $track_node = Node::create([
        'type' => 'tracks',
        'title' => $track_data['name'],
        'field_duration' => $track_data['duration_ms'],
        'field_artist' => [
          'target_id' => $artist_node->id(),
        ],
      ]);

      if (!empty($track_data['album']['images'][0]['url'])) {
        $this->attachImage($track_node, 'field_image', $track_data['album']['images'][0]['url']);
      }

      $track_node->save();

      $album_node->field_tracks[] = ['target_id' => $track_node->id()];
      $album_node->save();
    }
    return $track_node;
  }

  /**
   * Attaches an image to a node field from a URL.
   */
  private function attachImage($node, $field_name, $image_url) {
    $file_system = \Drupal::service('file_system');
    $file_contents = file_get_contents($image_url);
    $file_name = basename($image_url);
    $file_uri = 'public://' . $file_name;

    if (file_put_contents($file_system->realpath($file_uri), $file_contents)) {
      $file = \Drupal\file\Entity\File::create([
        'uri' => $file_uri,
        'status' => 1,
      ]);
      $file->save();

      $node->set($field_name, [
        'target_id' => $file->id(),
        'alt' => $node->getTitle(),
        'title' => $node->getTitle(),
      ]);
    }
  }
}
