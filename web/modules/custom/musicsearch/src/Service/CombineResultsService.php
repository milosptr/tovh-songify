<?php

namespace Drupal\musicsearch\Service;

class CombineResultsService {
  protected int $similarityThreshold = 80;

  /**
   * Find matching Discogs track for a Spotify track.
   * @param $spotifyTrack
   * @param $discogsTracks
   * @return mixed|null
   */
  private function findMatchingDiscogsTrack($spotifyTrack, $discogsTracks): mixed
  {
    foreach ($discogsTracks as $discogsTrack) {
      $discogArtistTitle = $this->generateDiscogsArtistAndTitle($discogsTrack['title']);
      if ($this->fuzzyMatch($spotifyTrack['name'], $discogArtistTitle[1])) {
        $spotifyArtists = implode(', ', array_column($spotifyTrack['artists'], 'name'));
        if ($discogArtistTitle[0] && $this->fuzzyMatch($spotifyArtists, $discogArtistTitle[0])) {
          \Drupal::logger('musicsearch')->info('Matching Spotify track to Discogs track. name: @spotify, title: @discogs, artist: @artist', [
            '@spotify' => $spotifyTrack['name'],
            '@artist' => $discogArtistTitle[0] . ' - ' . $spotifyArtists,
            '@discogs' => $discogArtistTitle[1],
          ]);
          return $discogsTrack;
        }
      }
    }
    return null;
  }

  /**
   * Find matching Discogs album for a Spotify album.
   * @param $spotifyAlbum
   * @param $discogsAlbums
   * @return mixed|null
   */
  private function findMatchingDiscogsAlbum($spotifyAlbum, $discogsAlbums): mixed
  {
    foreach ($discogsAlbums as $discogsAlbum) {
      if ($this->fuzzyMatch($spotifyAlbum['name'], $discogsAlbum['title'])) {
        if (!empty($spotifyAlbum['release_date']) && !empty($discogsAlbum['year'])) {
          $spotifyYear = substr($spotifyAlbum['release_date'], 0, 4);
          if (abs($spotifyYear - $discogsAlbum['year']) <= 1) {
            return $discogsAlbum;
          }
        }
      }
    }
    return null;
  }

  /**
   * Find matching Discogs artist for a Spotify artist.
   * @param $spotifyArtist
   * @param $discogsArtists
   * @return mixed|null
   */
  private function findMatchingDiscogsArtist($spotifyArtist, $discogsArtists): mixed
  {
    foreach ($discogsArtists as $discogsArtist) {
      if ($this->fuzzyMatch($spotifyArtist['name'], $discogsArtist['title'])) {
        return $discogsArtist;
      }
    }
    return null;
  }

  /**
   * Combine Spotify and Discogs results.
   * @param $spotifyResults
   * @param $discogsResults
   * @param $types
   * @return array
   */
  public function combineResults($spotifyResults, $discogsResults, $types): array
  {
    $combined = [];

    // Combine tracks
    if (str_contains($types, 'track')) {
      foreach ($spotifyResults['tracks']['items'] as $spotifyTrack) {
        $discogsTrack = $this->findMatchingDiscogsTrack($spotifyTrack, $discogsResults['tracks']);
        $combined[] = [
          'type' => 'Track',
          'name' => $spotifyTrack['name'],
          'artists' => implode(', ', array_column($spotifyTrack['artists'], 'name')),
          'album' => $spotifyTrack['album']['name'],
          'release_year' => $spotifyTrack['album']['release_date'] ?? $discogsTrack['year'] ?? '',
          'genre' => $this->getGenres($spotifyTrack, $discogsTrack),
          'source' => $discogsTrack ? 'Spotify + Discogs' : 'Spotify',
          'link' => $spotifyTrack['external_urls']['spotify'],
          'id' => $this->parseIDStrings($spotifyTrack, $discogsTrack),
          'discogs_data' => $discogsTrack,
          'spotify_data' => $spotifyTrack,
        ];
      }
    }

    // Combine albums
    if (str_contains($types, 'album')) {
      foreach ($spotifyResults['albums']['items'] as $spotifyAlbum) {
        $discogsAlbum = $this->findMatchingDiscogsAlbum($spotifyAlbum, $discogsResults['albums']);
        $combined[] = [
          'type' => 'Album',
          'name' => $spotifyAlbum['name'],
          'artists' => implode(', ', array_column($spotifyAlbum['artists'], 'name')),
          'release_year' => $spotifyAlbum['release_date'] ?? $discogsAlbum['year'],
          'genre' => $this->getGenres($spotifyAlbum, $discogsAlbum),
          'source' => $discogsAlbum ? 'Spotify + Discogs' : 'Spotify',
          'link' => $spotifyAlbum['external_urls']['spotify'],
          'id' => $this->parseIDStrings($spotifyAlbum, $discogsAlbum),
          'discogs_data' => $discogsAlbum,
          'spotify_data' => $spotifyAlbum,
        ];
      }
    }

    // Combine artists
    if (str_contains($types, 'artist')) {
      foreach ($spotifyResults['artists']['items'] as $spotifyArtist) {
        $discogsArtist = $this->findMatchingDiscogsArtist($spotifyArtist, $discogsResults['artists']);
        $combined[] = [
          'type' => 'Artist',
          'name' => $spotifyArtist['name'],
          'genre' => $this->getGenres($spotifyArtist, $discogsArtist),
          'source' => $discogsArtist ? 'Spotify + Discogs' : 'Spotify',
          'link' => $spotifyArtist['external_urls']['spotify'],
          'id' => $this->parseIDStrings($spotifyArtist, $discogsArtist),
          'discogs_data' => $discogsArtist,
          'spotify_data' => $spotifyArtist,
        ];
      }
    }

    return $combined;
  }

  /**
   * Normalize a string for matching.
   * @param $string
   * @return string
   */
  private function normalizeString($string): string
  {
    return strtolower(trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $string)));
  }

  /**
   * Perform fuzzy string matching with a similarity threshold.
   * @param $string1
   * @param $string2
   * @return bool
   */
  private function fuzzyMatch($string1, $string2): bool
  {
    $percent = 0;
    similar_text($this->normalizeString($string1), $this->normalizeString($string2), $percent);
    return $percent >= $this->similarityThreshold;
  }

  /**
   * Generate artist and title from a title string.
   * @param $title
   * @return array
   */
  private function generateDiscogsArtistAndTitle($title): array
  {
    if(str_contains($title, ' - ')) {
      $split = explode(' - ', $title);
      return [$split[0], $split[1]];
    }
    return [null, $title];
  }

  /**
   * Parse Spotify and Discogs IDs into a single string.
   * @param array|null $spotify
   * @param array|null $discogs
   * @return string
   */
  private function parseIDStrings(?array $spotify, ?array $discogs): string
  {
    $spotifyID = (is_array($spotify) && isset($spotify['id'])) ? $spotify['id'] : '';
    $discogsID  = (is_array($discogs) && isset($discogs['id'])) ? $discogs['id'] : '';

    if ($spotifyID === '' && $discogsID === '') {
      return '';
    }

    if ($spotifyID === '') {
      return $discogsID;
    }

    if ($discogsID === '') {
      return $spotifyID;
    }

    return "{$spotifyID} / {$discogsID}";
  }

  /**
   * Get combined genres from Spotify and Discogs.
   * @param $spotify
   * @param $discogs
   * @return string
   */
  private function getGenres($spotify, $discogs): string
  {
    $spotifyGenres = !empty($spotify['genres']) ? $spotify['genres'] : [];
    $discogsGenres = !empty($discogs['genre']) ? $discogs['genre'] : [];

    $combinedGenres = array_merge($spotifyGenres, $discogsGenres);

    return implode(', ', $combinedGenres);
  }
}
