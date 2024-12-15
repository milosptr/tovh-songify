<?php
namespace Drupal\musicsearch\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Exception;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Http\Client\Exception\RequestException;

class SpotifyLookupService {
  protected string $client_id;
  protected string $client_secret;
  protected string $base_url = 'https://api.spotify.com/v1/';
  protected string $token_url = 'https://accounts.spotify.com/api/token';
  protected string $types;
  protected ClientInterface $client;
  protected ConfigFactoryInterface $configFactory;

  /**
   * @param ConfigFactoryInterface $configFactory
   * @param ClientInterface $httpClient
   */
  public function __construct(ConfigFactoryInterface $configFactory, ClientInterface $httpClient)
  {
    $config = \Drupal::config('musicsearch.settings');
    $client_id = $config->get('spotify_client_id');
    $client_secret = $config->get('spotify_client_secret');

    $this->client_id = $client_id;
    $this->client_secret = $client_secret;

    $this->configFactory = $configFactory;
    $this->client = $httpClient;
  }

  /**
   * @param $types
   * @return $this
   */
  public function setSearchTypes($types): static
  {
    $this->types = '&type=' . urlencode($types);
    return $this;
  }

  /**
   * @throws Exception|GuzzleException
   * @return string
   */
  public function getAccessToken(): string
  {
    $url = $this->token_url . '?grant_type=client_credentials';
    $url = $url . '&client_id=' . $this->client_id . '&client_secret=' . $this->client_secret;
    $headers = [
      'Content-Type' => 'application/x-www-form-urlencoded',
    ];

    try {
      $response = $this->client->post($url, [
        'headers' => $headers,
      ]);
      $data = json_decode($response->getBody());
      return $data->access_token;
    } catch (RequestException $e) {
      throw new Exception('Failed to get access token: ' . $e->getMessage());
    }
  }

  /**
   * Searches for tracks on Spotify based on the given query.
   *
   * @param string $query The search query string.
   * @param int $limit The number of results to return. Default is 10.
   * @return array The search results or an error message.
   */
  public function search(string $query, int $limit = 15): array
  {
    if($query == '') {
      return [];
    }

    if(!isset($this->client_id) || !isset($this->client_secret)) {
      return [
        'error' => 'API keys are not set. Please configure the keys in the Settings page.',
      ];
    }

    try {
      $url = $this->base_url . 'search?q=' . urlencode($query) . '&limit=' . $limit . $this->types;

      \Drupal::logger('SpotifyLookupService')->info('Searching for ' . $query . ' with URL: ' . $url);
      $headers = [
        'headers' => [
            'Authorization' => 'Bearer ' . $this->getAccessToken(),
        ]
      ];

      $response = $this->client->get($url, $headers);

      if($response->getStatusCode() == 200) {
        return json_decode($response->getBody(), TRUE);
      } else {
        throw new Exception('Failed to search for ' . $query . ': ' . $response->getBody());
      }
    } catch (Exception $e) {
      \Drupal::logger('SpotifyLookupService')->error('Failed to search for ' . $query . ': ' . $e->getMessage());
      return [
        'error' => 'No results found. Check the logs for more information.',
      ];
    }
  }
}
