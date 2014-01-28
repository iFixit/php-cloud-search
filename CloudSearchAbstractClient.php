<?php

namespace Aws\CloudSearch\Common;

use Guzzle\Common\Collection;
use Guzzle\Service\Description\ServiceDescription;

/**
 * An abstract base class for CloudSearch clients that send requests to a
 * CloudSearch service endpoint: batch upload and search. This class provides
 * functionality for loading the appropriate endpoint and service description
 * in order to construct a client.
 *
 * The endpoint may be specified via either of the following methods:
 *
 *    1) An entry in Resources/endpoints.ini
 *
 *    2) Being passed directly under the 'endpoint' config option
 *
 * With method (1), the endpoints.ini file may specify endpoints for multiple
 * domains. The domain to use may be selected via the 'domain' config option;
 * if this option is empty then the 'default_domain' setting in endpoints.ini
 * will be used. See Resources/example_endpoints.ini for an example of a valid
 * endpoints.ini file.
 *
 * With method (2) the domain name is implied by the endpoint, and so there is
 * no need to explicitly specify a domain. Note that endpoints should not
 * include the protocol (e.g., http:// or https://), the API version, or a
 * specific path.
 */
abstract class CloudSearchAbstractClient extends \Guzzle\Service\Client {

   public static function factory($clientOptions = [], $config = []) {
      $required = ['service', 'description_path'];
      $clientOptions = Collection::fromConfig($clientOptions, [], $required);

      $default = [
         'version' => static::LATEST_API_VERSION,
         'domain' => NULL,
         'endpoint' => NULL
      ];
      $required = ['version'];
      $config = Collection::fromConfig($config, $default, $required);

      // Get the appropriate endpoint from a Resourses/endpoints.ini in the
      // case that it's not passed in directly.
      if (is_null($config['endpoint'])) {
         $iniPath = __DIR__ . '/Resources/endpoints.ini';
         if (file_exists($iniPath)) {
            $ini = parse_ini_file($iniPath, /* process_sections = */ true);
            if (is_null($config['domain'])) {
               $config['domain'] = $ini['default_domain'];
            }
            $endpoints = $ini[$config['domain']];
            $config['endpoint'] = $endpoints[$clientOptions['service']];
         }
      }

      // Endpoints are CloudSearch's terminology, but we're using an endpoint
      // (including the protocol, optional port, etc.) as Guzzle's baseUrl.
      // Most of the client configuration happens in the call to
      // setDescription, but I couldn't find a way to have the configuration
      // set a baseUrl *with* a port (because substituting config values into
      // baseUrl causes them to be url-encoded). So we leave baseUrl out of the
      // service description and set it directly when instantiating the client.
      $baseUrl = $config['endpoint'];
      unset($config['endpoint']);

      $client = new static($baseUrl, $config);

      // Fill in the missing API version in the description path; the path
      // should contain '%s' where the API version is to be inserted.
      $descriptionFile = sprintf($clientOptions['description_path'],
       static::LATEST_API_VERSION);

      // Configure the client according to the service description.
      $client->setDescription(ServiceDescription::factory($descriptionFile));

      return $client;
   }
}
