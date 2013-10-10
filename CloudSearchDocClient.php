<?php

namespace Aws\CloudSearch\Doc

require_once __DIR__ . '/CloudSearchAbstractClient.php';

use Aws\CloudSearch\Common\CloudSearchAbstractClient;
use Guzzle\Common\Collection;
use Guzzle\Service\Description\ServiceDescription;
use Guzzle\Service\Command\DefaultRequestSerializer;
use Guzzle\Service\Command\LocationVisitor\Request\JsonVisitor;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Service\Command\CommandInterface;
use Guzzle\Service\Command\OperationCommand;

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
 *    $client->sendBatch(['Documents' => $arrayOfSdfData]);
 */
class CloudSearchDocClient extends CloudSearchAbstractClient {
   const API_VERSION = '2011-02-01';

   protected static $addedVisitor = false;

   public static function factory($config = []) {
      $client = parent::factory([
         'service' => 'doc',
         'description_path' => __DIR__ . '/Resources/cs-doc-%s.php'
      ], $config);

      if (!self::$addedVisitor) {
         DefaultRequestSerializer::getInstance()->addVisitor(
          'json.array', new JsonArrayVisitor());
         self::$addedVisitor = true;
      }

      return $client;
   }
}


/**
 * A visitor for setting the request body to a JSON array instead of a JSON
 * object.
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
