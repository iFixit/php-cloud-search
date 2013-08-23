<?php

namespace Aws\CloudSearch\Query;

require_once __DIR__ . '/CloudSearchAbstractClient.php';

use Aws\CloudSearch\Common\CloudSearchAbstractClient;
use Guzzle\Common\Collection;
use Guzzle\Service\Description\ServiceDescription;

/**
 * A client for making query requests to an established CloudSearch index.  The
 * client requires an endpoint to make its requests to; see
 * CloudSearchAbstractClient for a description of how to specify it.
 *
 * Example:
 *
 *    $client = CloudSearchQueryClient::factory([
 *       'domain' => 'mydomain'
 *    ]);
 *    $results = $client->query(['q' => 'some query text']);
 *
 * Possible future query interface:
 *
 *    $q = new CloudSearchQuery();
 *    $q->booleanQuery(
 *       $q->and_(
 *          $q->field('title', $userQuery),
 *          $q->field('public', true),
 *          $q->or_(
 *             $q->filter('year', $q->range(1990, NULL)),
 *             $q->field('timeless', true))));
 *    $q->facet('genre');
 *    $results = $client->query($q);
 */
class CloudSearchQueryClient extends CloudSearchAbstractClient {
   const LATEST_API_VERSION = '2011-02-01';

   public static function factory($config = []) {
      return parent::factory([
         'service' => 'search',
         'description_path' => __DIR__ . '/Resources/cs-query-%s.php'
      ], $config);
   }
}
