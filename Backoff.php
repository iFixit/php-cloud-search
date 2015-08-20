<?php

namespace Aws\CloudSearch;

require_once __DIR__ . '/CloudSearchJsonExceptionParser.php';

use Aws\CloudSearch\CloudSearchJsonExceptionParser;
use Aws\Common\Client\ThrottlingErrorChecker;
use Guzzle\Plugin\Backoff\BackoffPlugin;
use Guzzle\Plugin\Backoff\CurlBackoffStrategy;
use Guzzle\Plugin\Backoff\ExponentialBackoffStrategy;
use Guzzle\Plugin\Backoff\HttpBackoffStrategy;
use Guzzle\Plugin\Backoff\TruncatedBackoffStrategy;

/**
 * A factory class to ease the creation of backoff strategies for CloudSearch
 * requests. It creates a reasonable backoff strategy (basically the same as
 * the one created by the standard AbstractClient) by default, but allows easy
 * parameterization of the HTTP status codes that trigger a retry and the
 * maximum number of retries.
 *
 * The plugin created by this class can be passed into the client factory with
 * the `backoff` configuration setting. For example:
 *
 *    $client = CloudSearchQueryClient::factory([
 *       'base_url' => '...',
 *       'backoff' => Backoff::factory(2, [502, 503]),
 *       ...
 *    ]);
 */
class Backoff {

   /**
    * Starting from the bottom: use exponential backoff; retry requests for
    * responses with the listed HTTP status codes, for transient or
    * cURL-related errors, and for 400-level responses caused by throttling;
    * allow a maximum of 3 retries.
    */
   public static function factory($retries = 3,
    $codes = [500, 502, 503, 504, 507, 509]) {
      return
       new BackoffPlugin(
       new TruncatedBackoffStrategy($retries,
       new ThrottlingErrorChecker(new CloudSearchJsonExceptionParser(),
       new CurlBackoffStrategy(null,
       new HttpBackoffStrategy($codes,
       new ExponentialBackoffStrategy())))));
   }
}
