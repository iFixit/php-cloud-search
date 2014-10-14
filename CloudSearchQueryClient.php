<?php

namespace Aws\CloudSearchQuery;

require_once __DIR__ . '/CloudSearchClientBuilder.php';
require_once __DIR__ . '/CloudSearchQuery.php';

use Aws\Common\Client\AbstractClient;
use Aws\Common\Enum\ClientOptions as Options;
use Aws\CloudSearch\CloudSearchClientBuilder;

/**
 * A client for making query requests to an established CloudSearch index. The
 * client requires an endpoint (specified via the `base_url` config parameter)
 * to make its requests to.
 *
 * Example:
 *
 *    $client = CloudSearchQueryClient::factory([
 *       'base_url' => 'mydomain'
 *    ]);
 *
 *    $results = $client->query(['q' => 'some query text']);
 */
class CloudSearchQueryClient extends AbstractClient {
   const LATEST_API_VERSION = '2011-02-01';
   const DESCRIPTION_NAME = 'cs-query-%s.php';

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

   public function newQuery() {
      return new CloudSearchQuery;
   }

   public function query($args = []) {
      return $this->prepareQueryCommand($args)->getResult();
   }

   public function multiQuery($batchArgs) {
      $commands = [];
      foreach ($batchArgs as $args)
         $commands[] = $this->prepareQueryCommand($args);

      $commands = $this->execute($commands);

      return array_map(function ($command) {
         return $command->getResult();
      }, $commands);
   }

   protected function prepareQueryCommand($args) {
      if (is_a($args, '\Aws\CloudSearchQuery\CloudSearchQuery'))
         $args = $args->build();

      return $this->getCommand('query', $args);
   }
}
