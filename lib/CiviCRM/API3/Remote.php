<?php

namespace CiviCRM\API3;
use CiviCRM\API3\Result as Result;

class Remote extends AbstractAPI {

  protected $uri;
  protected $siteKey;
  protected $apiKey;

  /**
   * @param array $config
   *   Configuration..
   */
  function __construct($config) {

    // Validate
    if (empty($config['server'])) {
      throw new Exception("Must specify server in config.");
    }
    if (empty($config['key'])) {
      throw new Exception("Must specify the site key ('key') from your civicrm.settings.php.");
    }
    if (empty($config['api_key'])) {
      throw new Exception("Must specify the API key for a CiviCRM user.");
    }

    // Fall back to defaults
    if (empty($config['path'])) {
      $config['path'] = '/sites/all/modules/civicrm/extern/rest.php';
    }
    elseif ('/' !== $config['path']{0}) {
      $config['path'] = '/' . $config['path'];
    }

    // Set protected properties
    $this->uri = $config['server'] . $config['path'] . '?json=1';
    $this->siteKey = $config['key'];
    $this->apiKey = $config['api_key'];
  }

  /**
   * Result object in case that we return nothing.
   */
  protected function emptyResult($entity, $action, $params) {
    return new Result\RemoteFail();
  }

  /**
   * Perform the remote REST call.
   */
  protected function apiCall($entity, $action, $params) {

    // We leave 'entity' and 'action' in the url for easier debugging.
    // For the rest we will try to use POST.
    $uri = $this->uri . "&entity=$entity&action=$action";

    $params['key'] = $this->siteKey;
    $params['api_key'] = $this->apiKey;
    $fields = array();
    foreach ($params as $k => $v) {
      $fields[] = "$k=" . urlencode($v);
    }
    $fields = implode('&', $fields);

    if (function_exists('curl_init')) {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $uri);
      curl_setopt($ch, CURLOPT_POST, count($params));
      curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

      // Execute post
      $result = curl_exec($ch);
      curl_close($ch);
    }
    else {
      // Not good, all in get when should be in post.
      $result = file_get_contents($uri . '&' . $fields);
    }

    return json_decode($result);
  }
}
