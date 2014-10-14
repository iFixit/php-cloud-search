<?php

namespace Aws\CloudSearchDoc;

require_once __DIR__ . '/CloudSearchClientBuilder.php';

use Aws\CloudSearch\CloudSearchClientBuilder;
use Aws\Common\Client\AbstractClient;
use Aws\Common\Enum\ClientOptions as Options;

/**
 * A client for sending document batches to an established CloudSearch index.
 *  The client requires an endpoint (specified via the `base_url` config
 *  parameter) to make its requests to.
 *
 * Example:
 *
 *    $client = CloudSearchDocClient::factory([
 *       'base_url' => 'mydomain'
 *    ]);
 *
 *    $client->uploadDocuments([
 *       'documents' => $sdfJsonString,
 *       'contentType' => 'application/json'
 *    ]);
 */
class CloudSearchDocClient extends AbstractClient {
   const LATEST_API_VERSION = '2011-02-01';
   const DESCRIPTION_NAME = 'cs-doc-%s.php';

   public static function factory($config = []) {
      return CloudSearchClientBuilder::factory(__NAMESPACE__)
       ->setConfig($config)
       ->setConfigDefaults([
            Options::VERSION => self::LATEST_API_VERSION,
            Options::SERVICE_DESCRIPTION =>
             __DIR__ . '/Resources/' . self::DESCRIPTION_NAME
         ])
       ->build();
   }
}
