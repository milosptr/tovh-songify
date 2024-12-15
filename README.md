# Download files for this project
> ⚠️ Available until 18.12.2024 (7 days) [WeTransfer files link](https://we.tl/t-UsPSoIsCJc)

# Songify

Songify is a Drupal-based web application designed to showcase music albums, artists, tracks, and publishers. It integrates with the Spotify API to import top tracks and associated data, providing users with a rich musical experience.

## Table of Contents

- [Features](#features)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
  - [Cloning the Repository](#cloning-the-repository)
  - [Setting Up DDEV](#setting-up-ddev)
- [Custom Theme](#custom-theme)
- [Content Types](#content-types)
- [Custom Module: Spotify JSON Import](#custom-module-spotify-json-import)
  - [How It Works](#how-it-works)
- [Taxonomy](#taxonomy)
- [Contributing](#contributing)
- [License](#license)
- [Credits](#credits)

## Features

- Custom Drupal theme: **Songify**
- Integration with Spotify API for data import
- Custom content types for Advertisements, Albums, Artists, Publishers, and Tracks
- Genre taxonomy for music classification
- Docker-based local development environment using DDEV

## Prerequisites

- [Docker](https://www.docker.com/get-started)
- [DDEV](https://ddev.readthedocs.io/en/stable/)
- [Git](https://git-scm.com/)

## Installation

### Cloning the Repository

Clone the repository from GitHub:

```bash
git clone https://github.com/milosptr/tovh-songify.git
cd tovh-songify
```

### Setting Up DDEV

1. **Start DDEV Environment:**

   ```bash
   ddev start
   ```

2. **Install Dependencies:**

   Install Drupal and any required dependencies:

   ```bash
   ddev composer install
   ```

3. **Import Database (if applicable):**

   If you have a database dump, import it:

   ```bash
   ddev import-db --src=path/to/database.sql
   ```

4. **Access the Site:**

   Visit [https://songify.ddev.site](https://songify.ddev.site) in your browser.

## Custom Theme

The **Songify** theme is a custom Drupal theme tailored for the application.

**Theme Info File (`songify.info.yml`):**

```yaml
name: Songify
type: theme
description: 'A custom theme for Songify'
package: custom
core_version_requirement: ^9 || ^10 || ^11
version: 1.0.0
base theme: false

libraries:
  - songify/global-styling
  - songify/fonts

regions:
  header: Header
  content: Content
  footer: Footer

logo: 'assets/images/songify-logo.png'
```

## Content Types

### Advertisement

- **field_image**: Image
- **field_ad_type**: Text
- **field_ad_link**: Link

### Track

- **title**: String
- **body**: Text
- **field_image**: Image
- **field_duration**: Integer
- **field_spotify_id**: String
- **field_artist**: Reference to Artist
- **field_genres**: Reference to Taxonomy (Music Genres)

### Album

- **title**: String
- **body**: Text
- **field_image**: Image
- **field_release_date**: Date
- **field_tracks**: Reference to Tracks
- **field_genres**: Reference to Taxonomy (Music Genres)
- **field_artist**: Reference to Artist
- **field_publisher**: Reference to Publisher

### Artist

- **body**: Text
- **field_name**: String
- **field_image**: Image
- **field_website**: String
- **field_birthdate**: Date
- **field_deathdate**: Date
- **field_band_members**: Reference to Artist (for bands)

### Publisher

- **body**: Text
- **field_name**: String
- **field_image**: Image
- **field_website**: String
- **field_founded_date**: Date
- **field_closed_date**: Date

## Custom Module: Spotify JSON Import

The **Spotify JSON Import** module allows administrators to import data from the Spotify API directly into the Drupal site.

### How It Works

1. **Retrieve JSON Data from Spotify API:**

  - Use the [Get Artist's Top Tracks](https://developer.spotify.com/documentation/web-api/reference/get-an-artists-top-tracks) endpoint.
  - Replace `{id}` with the artist's Spotify ID:

    ```
    https://api.spotify.com/v1/artists/{id}/top-tracks
    ```

  - Ensure you have the necessary Spotify API credentials and permissions to access the endpoint.

2. **Access the Import Interface:**

  - Navigate to:

    ```
    Structure > Spotify JSON Import
    ```

3. **Upload JSON File:**

  - Click on the **Upload** button.
  - Select the JSON file obtained from the Spotify API.

4. **Import Process:**

  - The module parses the JSON data.
  - Creates or updates content for:
    - **Artists**
    - **Albums**
    - **Tracks**
  - Populates fields such as titles, images, durations, and Spotify IDs.
  - Associates tracks with the appropriate albums and artists.

5. **Post-Import:**

  - The imported content is now available on the site.
  - Content can be viewed, edited, or further categorized using taxonomies.

**Note:** Ensure that the JSON data matches the expected format from the Spotify API to avoid import errors.

## Taxonomy

### Genres (Music Genres)

- Used to categorize music content by genre.
- Helps in filtering and organizing content.
- Assign genres to artists, albums, and tracks for better discoverability.

## Contributing

Contributions are welcome! Please follow these steps:

1. **Fork the Repository:**

   Click the **Fork** button on GitHub.

2. **Create a Feature Branch:**

   ```bash
   git checkout -b feature/YourFeatureName
   ```

3. **Commit Your Changes:**

   ```bash
   git commit -am 'Add a new feature'
   ```

4. **Push to the Branch:**

   ```bash
   git push origin feature/YourFeatureName
   ```

5. **Open a Pull Request:**

   Submit your pull request for review.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
