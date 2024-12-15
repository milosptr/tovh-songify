<?php
namespace Drupal\musicsearch\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

class DiscogsLookupService {
  protected string $api_url = 'https://api.discogs.com/database/search';
  protected string $consumer_key;
  protected string $consumer_secret;
  protected ClientInterface $client;
  protected ConfigFactoryInterface $configFactory;

  public function __construct(ConfigFactoryInterface $configFactory, ClientInterface $httpClient) {
    $config = \Drupal::config('musicsearch.settings');
    $this->consumer_key = $config->get('discogs_consumer_key');
    $this->consumer_secret = $config->get('discogs_consumer_secret');
    $this->configFactory = $configFactory;
    $this->client = $httpClient;
  }

  private function searchByParams(array $params): array {
    try {
      $params['key'] = $this->consumer_key;
      $params['secret'] = $this->consumer_secret;
      $url = $this->api_url . '?' . http_build_query($params) . '&per_page=15';
      $response = $this->client->get($url);
      $data = json_decode($response->getBody()->getContents(), TRUE);
      return $data['results'] ?? [];
    } catch (RequestException $e) {
      \Drupal::logger('musicsearch')->error($e->getMessage());
      return [];
    }
  }

  public function searchTracks($q): array {
    return $this->searchByParams([
      'track' => $q,
      'type' => 'release',
    ]);
  }

  public function searchArtists($q): array {
    return $this->searchByParams([
      'q' => $q,
      'type' => 'artist',
    ]);
  }

  public function searchAlbums($q): array {
    return $this->searchByParams([
      'release_title' => $q,
      'type' => 'release',
    ]);
  }

  public function search($query, $types) {
    $tracks = [];
    $artists = [];
    $albums = [];

    if(str_contains($types, 'track')) {
      $tracks = $this->searchTracks($query);
    }
    if(str_contains($types, 'artist')) {
      $artists = $this->searchArtists($query);
    }
    if(str_contains($types, 'album')) {
      $albums = $this->searchAlbums($query);
    }

    return [
      'tracks' => $tracks,
      'artists' => $artists,
      'albums' => $albums,
    ];
  }
}
