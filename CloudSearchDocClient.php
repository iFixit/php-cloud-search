<?php

namespace Aws\CloudSearch\Doc;

require_once __DIR__ . '/CloudSearchAbstractClient.php';

use Aws\CloudSearch\Common\CloudSearchAbstractClient;
use Guzzle\Service\Command\DefaultRequestSerializer;
use Guzzle\Service\Command\LocationVisitor\Request\JsonVisitor;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Service\Command\CommandInterface;

/**
 * A client for sending document batches to an established CloudSearch index.
 * The client requires an endpoint to make its requests to; see
 * CloudSearchAbstractClient for a description of how to specify it.
 *
 * Example:
 *
 *    $client = CloudSearchDocClient::factory([
 *       'domain' => 'mydomain'
 *    ]);
 *
 *    $client->sendBatch(['Documents' => $arrayOfSdfData]);
 *    // OR
 *    $client->sendBatchRaw(['Json' => $sdfJsonString]);
 */
class CloudSearchDocClient extends CloudSearchAbstractClient {
   const LATEST_API_VERSION = '2011-02-01';

   public static function factory($config = []) {
      $client = parent::factory([
         'service' => 'doc',
         'description_path' => __DIR__ . '/Resources/cs-doc-%s.php'
      ], $config);

      return $client;
   }
}


/**
 * Serialize a PHP array to a JSON array in the request body. This is in
 * contrast to JsonVisitor, which serializes request arguments to key/value
 * pairs in a single JSON object in the request body.
 */
class JsonArrayVisitor extends JsonVisitor {
   public function after(CommandInterface $command,
    RequestInterface $request) {
      if (isset($this->data[$command])) {
         $jsonData = $this->data[$command];
         if (count($jsonData) != 1) {
            throw new Exception(
             'JSON array request body requires exactly one parameter.');
         }
         $jsonArray = array_values($jsonData)[0];
         if (!is_array($jsonArray)) {
            throw new Exception(
             "JSON array request body requires a parameter of type 'array'.");
         }
         $this->data[$command] = $jsonArray;
         return parent::after($command, $request);
      }
   }
}

DefaultRequestSerializer::getInstance()->addVisitor('json.array',
 new JsonArrayVisitor);
