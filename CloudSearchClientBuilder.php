<?php

namespace Aws\CloudSearch;

// We don't directly use the exception class here, but it needs to be defined
// so that it will be found by the NamespaceExceptionFactory in the event of an
// error.
require_once __DIR__ . '/CloudSearchQueryInfoException.php';

require_once __DIR__ . '/Backoff.php';

use Aws\Common\Client\ClientBuilder;
use Aws\Common\Client\UserAgentListener;
use Aws\Common\Credentials\Credentials;
use Aws\Common\Credentials\CredentialsInterface;
use Aws\Common\Credentials\NullCredentials;
use Aws\Common\Enum\ClientOptions as Options;
use Aws\Common\Exception\ExceptionListener;
use Aws\Common\Exception\InvalidArgumentException;
use Aws\Common\Exception\NamespaceExceptionFactory;
use Guzzle\Common\Collection;
use Guzzle\Http\Url;
use Guzzle\Service\Description\ServiceDescription;

// This is our own Backoff factory.
use Aws\CloudSearch\Backoff;

/**
 * Builder for creating CloudSearch clients. This builder is used by the
 * CloudSearchQueryClient and the CloudSearchDocClient to create instances of
 * those classes. As a consumer of this library, there shouldn't be any need to
 * use it directly.
 *
 * This is an adaptation of a similar builder from the new version of the SDK
 * (2.7.0). The new version of the SDK is tailored to the 2013 version of
 * CloudSearch API, so we can't use it directly while we're still using the
 * 2011 API. So, this is largely a copy with a few modifications to work with
 * the rest of this library. If you're using a newer version of the API, don't
 * use this library; use the new CloudSearchDomainClient class provided by the
 * SDK.
 */
class CloudSearchClientBuilder extends ClientBuilder {
   protected static $commonConfigDefaults = [
      Options::SCHEME => 'https',
   ];

   public function build() {
      // Resolve configuration
      $config = Collection::fromConfig(
         $this->config,
         array_merge(self::$commonConfigDefaults, $this->configDefaults),
         $this->configRequirements
      );

      // Make sure base_url is correctly set
      if (!($baseUrl = $config->get(Options::BASE_URL))) {
         throw new InvalidArgumentException(
          'You must provide the endpoint for the CloudSearch domain.');
      } elseif (strpos($baseUrl, 'http') !== 0) {
         $config->set(Options::BASE_URL, Url::buildUrl([
            'scheme' => $config->get(Options::SCHEME),
            'host'   => $baseUrl,
         ]));
      }

      // Determine the region from the endpoint
      $endpoint = Url::factory($config->get(Options::BASE_URL));
      $endpointParts = explode('.', $endpoint->getHost());
      if (count($endpointParts) >= 2) {
         $region = $endpointParts[1];
      } else {
         $region = 'us-east-1';
      }
      $config[Options::REGION] = $config[Options::SIGNATURE_REGION] = $region;

      // Create dependencies
      $description = ServiceDescription::factory(sprintf(
         $config->get(Options::SERVICE_DESCRIPTION),
         $config->get(Options::VERSION)
      ));
      $signature = $this->getSignature($description, $config);
      $credentials = $this->getCredentials($config);

      // Resolve backoff strategy
      $backoff = $config->get(Options::BACKOFF);
      if ($backoff === null) {
         $config->set(Options::BACKOFF, Backoff::factory());
      }
      if ($backoff) {
         $this->addBackoffLogger($backoff, $config);
      }

      // Determine service and class name
      $clientClass = 'Aws\Common\Client\DefaultClient';
      if ($this->clientNamespace) {
         $serviceName = substr($this->clientNamespace,
          strrpos($this->clientNamespace, '\\') + 1);
         $clientClass = $this->clientNamespace . '\\' . $serviceName .
          'Client';
      }

      // Create client
      $client = new $clientClass($credentials, $signature, $config);
      $client->setDescription($description);

      // Add a subscriber that can throw more specific exceptions
      if ($this->clientNamespace) {
         $exceptionFactory = new NamespaceExceptionFactory(
            $this->exceptionParser,
            "{$this->clientNamespace}\\Exception"
         );
         $client->addSubscriber(new ExceptionListener($exceptionFactory));
      }

      // Add the UserAgentPlugin to append to the User-Agent header of requests
      $client->addSubscriber(new UserAgentListener);

      // Filters used for the cache plugin
      $client->getConfig()->set(
         'params.cache.key_filter',
         'header=date,x-amz-date,x-amz-security-token,x-amzn-authorization'
      );

      // Disable parameter validation if needed
      if ($config->get(Options::VALIDATION) === false) {
         $params = $config->get('command.params') ?: array();
         $params['command.disable_validation'] = true;
         $config->set('command.params', $params);
      }

      return $client;
   }

   protected function getCredentials(Collection $config) {
      $credentials = $config->get(Options::CREDENTIALS);
      if ($credentials === false) {
         $credentials = new NullCredentials();
      } elseif (!$credentials instanceof CredentialsInterface) {
         $credentials = Credentials::factory($config);
      }

      return $credentials;
   }
}
