<?php
declare(strict_types = 1);

namespace Civi\Charitylookup;

use CRM_Charitylookup_ExtensionUtil as E;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

/**
 * Thin client for the Charity Commission "Register of Charities" API.
 *
 * @see https://api-portal.charitycommission.gov.uk/
 */
class Client {

  private ClientInterface $http;

  private string $apiKey;

  private string $baseUrl;

  public function __construct(?ClientInterface $http = NULL, ?string $apiKey = NULL, ?string $baseUrl = NULL) {
    $this->http = $http ?? new HttpClient(['timeout' => 20]);
    $this->apiKey = trim((string) ($apiKey ?? \Civi::settings()->get('charitylookup_api_key')));
    $this->baseUrl = rtrim((string) ($baseUrl ?? \Civi::settings()->get('charitylookup_api_base_url')), '/');
    if ($this->baseUrl === '') {
      $this->baseUrl = 'https://api.charitycommission.gov.uk/register/api';
    }
  }

  /**
   * Split a user-entered registered number into [number, suffix].
   *
   * Accepts "1234567", " 1234567 ", "1234567-1", "1234567/1".
   *
   * @throws \CRM_Core_Exception
   */
  public static function parseNumber(string $input): array {
    $input = preg_replace('/\s+/', '', $input);
    if (!preg_match('/^(\d{1,8})(?:[-\/](\d{1,3}))?$/', $input, $m)) {
      throw new \CRM_Core_Exception(E::ts('"%1" is not a valid registered charity number.', [1 => $input]));
    }
    return [(int) $m[1], (int) ($m[2] ?? 0)];
  }

  /**
   * GetAllCharityDetailsV2: main details, trustees, classifications etc.
   *
   * @throws \CRM_Core_Exception
   *   If the charity is not found or the API cannot be used.
   */
  public function getAllCharityDetails(string $registeredNumber): array {
    [$number, $suffix] = self::parseNumber($registeredNumber);
    $details = $this->request("allcharitydetailsV2/$number/$suffix");
    if (empty($details['reg_charity_number'])) {
      throw new \CRM_Core_Exception(E::ts('No charity found on the register with number %1.', [1 => $registeredNumber]));
    }
    return $details;
  }

  /**
   * GetCharityOverview: activities and latest annual return figures.
   *
   * @throws \CRM_Core_Exception
   */
  public function getCharityOverview(string $registeredNumber): array {
    [$number, $suffix] = self::parseNumber($registeredNumber);
    return $this->request("charityoverview/$number/$suffix");
  }

  /**
   * @throws \CRM_Core_Exception
   */
  private function request(string $path): array {
    if ($this->apiKey === '') {
      throw new \CRM_Core_Exception(E::ts('The Charity Commission API key has not been set. Go to Administer > System Settings > Charity Commission Lookup.'));
    }
    try {
      $response = $this->http->request('GET', $this->baseUrl . '/' . $path, [
        'headers' => [
          'Ocp-Apim-Subscription-Key' => $this->apiKey,
          'Accept' => 'application/json',
          'Cache-Control' => 'no-cache',
        ],
        'http_errors' => TRUE,
      ]);
    }
    catch (ConnectException $e) {
      throw new \CRM_Core_Exception(E::ts('Could not connect to the Charity Commission API: %1', [1 => $e->getMessage()]), 0, [], $e);
    }
    catch (RequestException $e) {
      $status = $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;
      if ($status === 401 || $status === 403) {
        throw new \CRM_Core_Exception(E::ts('The Charity Commission API rejected the API key (HTTP %1). Check the key in the Charity Commission Lookup settings.', [1 => $status]), 0, [], $e);
      }
      if ($status === 404) {
        return [];
      }
      if ($status === 429) {
        throw new \CRM_Core_Exception(E::ts('The Charity Commission API rate limit was reached. Try again shortly.'), 0, [], $e);
      }
      throw new \CRM_Core_Exception(E::ts('Charity Commission API error (HTTP %1).', [1 => $status ?: '?']), 0, [], $e);
    }

    $body = (string) $response->getBody();
    if (trim($body) === '' || trim($body) === 'null') {
      return [];
    }
    $data = json_decode($body, TRUE);
    if (!is_array($data)) {
      throw new \CRM_Core_Exception(E::ts('Unexpected response from the Charity Commission API.'));
    }
    // Some operations return a single-item list rather than an object.
    if (array_is_list($data)) {
      $data = $data[0] ?? [];
    }
    return is_array($data) ? $data : [];
  }

}
