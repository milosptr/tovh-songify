# Music Search Module

The **Music Search** module provides a seamless way to search for music content from external services like **Spotify** and **Discogs**, preview the results, and selectively import tracks, albums, and artists as Drupal nodes.

---

## Key Features

1. **Search Functionality**:
  - Perform searches using Spotify and Discogs APIs for tracks, albums, and artists.
  - Combine results from both APIs into a single interface.

2. **Import Functionality**:
  - Select individual items for import from search results.
  - Preview data to be imported with a field-mapping interface.
  - Save data as Drupal nodes with customizable field mappings.

3. **Configurable API Integration**:
  - Manage Spotify and Discogs API keys via a dedicated settings page.

4. **User-Friendly Interface**:
  - Step-by-step import process with field selection and preview.
  - Responsive and intuitive UI for administrative users.

---

## Installation

1. Place the module directory in your `modules/custom` folder:
   ```bash
   mv musicsearch /path/to/drupal/modules/custom/
   ```
2. Enable the module:
   ```bash
   drush en musicsearch
   ```
3. Run database updates:
   ```bash
   drush updb
   ```
4. Clear caches:
   ```bash
   drush cr
   ```

---

## Configuration

1. **API Keys**:
  - Navigate to **Configuration** > **Web Services** > **Music Search Settings**, or access the **Settings** tab under the **Music Search Import** page.
  - Enter your Spotify and Discogs API credentials:
    - Spotify: Client ID and Client Secret.
    - Discogs: Consumer Key and Consumer Secret.
  - Save the configuration.

2. **Permissions**:
  - Ensure the required permission, `administer site configuration`, is assigned to appropriate roles.

---

## Usage

### Searching for Music

1. Navigate to **Administration** > **Music Search Import**.
2. Enter a search query (e.g., "Blinding Lights") and select the types (track, album, artist) to search within.
3. View combined results from Spotify and Discogs displayed in a table.

### Previewing and Importing Data

1. Click the **Import** button next to a result to open the **Import Preview** page.
2. On the Import Preview page:
  - Select the target content type for the data.
  - Map fields between API data and Drupal fields.
3. Confirm the import to save the data as a Drupal node.

### Viewing Imported Data

- Successfully imported data is saved as a node in the selected content type.
- A link to the created node is provided after the import process.

---

## Module Structure

The module is organized as follows:

```
.
├── css
│   └── style.css                            # Custom CSS for styling the module interface.
├── musicsearch.info.yml                     # Module metadata and dependencies.
├── musicsearch.libraries.yml                # Defines libraries like CSS and JS.
├── musicsearch.links.menu.yml               # Adds menu links for Search and Settings pages.
├── musicsearch.links.task.yml               # Adds tabs for Search and Settings.
├── musicsearch.module                       # Module hook implementations.
├── musicsearch.routing.yml                  # Defines routes for search, settings, and import preview.
├── musicsearch.services.yml                 # Registers services like Spotify, Discogs, and import handling.
└── src
    ├── Controller
    │   ├── MusicImportController.php        # Handles import preview and process pages.
    │   └── MusicSearchController.php        # Handles the search interface and results display.
    ├── Form
    │   ├── MusicImportForm.php              # Handles the multi-step import process.
    │   ├── MusicSearchForm.php              # Handles the search functionality.
    │   └── MusicSearchSettingsForm.php      # Manages the settings page for API keys.
    └── Service
        ├── CombineResultsService.php        # Combines and processes results from Spotify and Discogs.
        ├── DiscogsLookupService.php         # Handles Discogs API integration.
        ├── MusicImportService.php           # Manages importing data into Drupal nodes.
        └── SpotifyLookupService.php         # Handles Spotify API integration.
```

---

## Dependencies

- **Core**:
  - Drupal 10 or 11.
- **Modules**:
  - System module (core).
- **External**:
  - Spotify API.
  - Discogs API.

---

## Customization

1. **Field Mapping**:
  - Extend `MusicImportService::getAvailableFieldsForContentType` to add custom field mappings.
2. **Styling**:
  - Add custom CSS in `css/style.css` and register it in `musicsearch.libraries.yml`.

---

## License

This module is licensed under the GPL-2.0-or-later license. See [LICENSE.txt](LICENSE.txt) for more information.

---

## Support

For issues and contributions, contact the module developer or submit issues via your preferred repository.

