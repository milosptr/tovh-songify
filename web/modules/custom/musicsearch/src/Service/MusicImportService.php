<?php
namespace Drupal\musicsearch\Service;

use Drupal;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;

class MusicImportService {
  /**
   * Imports a node with the given data.
   * @param string $type
   * @param string $title
   * @param array $data
   * @return string
   * @throws \Exception
   */
  public function import(string $type, string $title, array $data): string
  {
    $baseFields = [
      'type' => $type,
      'title' => $title
    ];

    try {
      $node = Node::create($baseFields);

      foreach ($data as $key => $value) {
        $field = $this->getSpecificFieldDefinition($type, $key);
        if ($field) {
          $fieldType = $field->getType();
          if ($fieldType === 'image') {
            $value = $this->attachImage($node, $value);
            if($value) {
              $node->set($key, $value);
            }
          } else {
            $node->set($key, $value);
          }
        }
      }

      $node->save();
      return (string) $node->id();
    } catch (\Exception $e) {
      throw new \Exception('Error importing node: ' . $e->getMessage());
    }
  }

  /**
   * Flattens an array to a string.
   * @param $value
   * @return string
   */
  public function flattenArray($value): string {
    if (is_array($value)) {
      $flattened = array_map([$this, 'flattenArray'], $value);
      return implode(', ', $flattened);
    }
    return (string) $value;
  }

  /**
   * Attaches an image to a node field from a URL. Returns the file array.
   * @param $node
   * @param $image_url
   * @throws \Exception
   * @return array|null
   */
  private function attachImage($node, $image_url): ?array
  {
    $file_system = Drupal::service('file_system');
    $file_contents = file_get_contents($image_url);
    $file_name = $this->attachFileExtension(basename($image_url));
    $file_uri = 'public://' . $file_name;

    if (file_put_contents($file_system->realpath($file_uri), $file_contents)) {
      $file = File::create([
        'uri' => $file_uri,
        'status' => 1,
      ]);
      $file->save();

      return [
        'target_id' => $file->id(),
        'alt' => $node->getTitle(),
        'title' => $node->getTitle(),
      ];
    }

    return null;
  }

  /**
   * Returns the available fields for a content type.
   * @param string $contentType
   * @return string[]
   */
  public function getAvailableFieldsForContentType(string $contentType): array {
    $contentTypeFieldsDefinitions = Drupal::service('entity_field.manager')->getFieldDefinitions('node', $contentType);
    $fields = ['none' => 'None'];
    foreach ($contentTypeFieldsDefinitions as $key => $field) {
      if($field instanceof FieldConfig) {
        $fields[$key] = $field->getLabel();
      }
    }
    return $fields;
  }

  /**
   * Returns a specific field definition.
   * @param string $contentType
   * @param string $fieldName
   * @return FieldConfig|null
   */
  public function getSpecificFieldDefinition(string $contentType, string $fieldName): ?FieldConfig {
    $fields = Drupal::service('entity_field.manager')->getFieldDefinitions('node', $contentType);
    foreach($fields as $field) {
      if($field->getName() === $fieldName) {
        return $field;
      }
    }
    return null;
  }

  /**
   * Sanitizes a URL string. If the string is not a URL, it returns the original string.
   * @param string $string
   * @return string
   */
  public function sanitizeUrl(string $string): string
  {
    $regex = '/https?:\/\/[^\s,]+/';
    preg_match_all($regex, $string, $matches);

    if(!empty($matches[0])) {
      return $matches[0][0];
    }

    return $string;
  }

  /**
   * Parses field data and returns the appropriate HTML.
   * Defaults to returning the original value.
   * @param $key
   * @param $value
   * @return mixed|string
   */
  public function parseFieldData($key, $value): mixed
  {
    $possibleKeys = ['images', 'image', 'img', 'cover_image', 'thumb', 'thumbnail'];
    $isImage = false;
    foreach($possibleKeys as $possibleKey) {
      if (str_contains($key, $possibleKey)) {
        $isImage = true;
        break;
      }
    }
    if ($isImage) {
      return '<img src="' . $this->sanitizeUrl($value) . '" width="100" height="100" alt="'.$key.'" />';
    }

    if(filter_var($value, FILTER_VALIDATE_URL)) {
      return '<a href="' . $value . '" target="_blank">' . $value . '</a>';
    }

    return $value;
  }

  private function attachFileExtension($filename)
  {
    $imageExtensions = ['jpg', 'jpeg', 'png'];
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    if (in_array($extension, $imageExtensions)) {
      return $filename;
    }
    return $filename . '.jpg';
  }
}
