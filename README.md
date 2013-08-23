PHP CloudSearch
===============

PHP CloudSearch is a PHP SDK for the search and doc services provided by [CloudSearch](http://aws.amazon.com/cloudsearch/). It attempts to provide roughly the same interface that the [official SDK](http://aws.amazon.com/sdkforphp/) provides for the CloudSearch configuration API but for uploading batches of documents and querying indexes. Because these APIs perform IP-based authentication and do not have a standard endpoint location (the endpoint is specific to the index), using the SDK requires that:

   1. you have an index already configured,
   2. you are making requests from an authenticated machine,
   3. and you pass the appropriate endpoint to the client, either directly
      during construction or via a configuration file.


Installation
------------

This library assumes that you've already installed the official AWS PHP SDK, and that its classes are available via autoload. You can install this library wherever you like.


Usage
-----

After placing the official AWS SDK and this library somewhere (presumably within your application), you first need to select a way to specify an endpoint. You're free to manage endpoints any way you want; you can always just pass the appropriate one to the client's `factory` function. For (some) convenience, you can also specify endpoints by domain in an `endpoints.ini` file under the `Resources` directory. If you go this route then you just need to pass the relevant domain name to the `factory` function or nothing if you want to use the default domain (specified in `endpoints.ini`). See the example `endpoints_example.ini` file for the appropriate format.

After choosing a way to specify endpoints, you're ready to start making requests:

```php
require_once 'path/to/php-cloud-search/CloudSearchQueryClient.php';

use Aws\CloudSearch\Query\CloudSearchQueryClient.php;

$client = CloudSearchQueryClient::factory(['domain' => 'mydomain']);

$results = $client->query([
   'q' => $preparedUserQuery,
   'facet' => 'genre'
]);
```

Or, passing an endpoint explicitly:

```php
$client = CloudSearchQueryClient::factory([
   'endpoint' => 'search-mydomain-xxx.cloudsearch.amazonaws.com'
]);
```

Right now the supported methods are:

   * `CloudSearchDocClient::sendBatch`
   * `CloudSearchQueryClient::query`

Both methods support the full set of API arguments and simply return [Guzzle](http://guzzlephp.org/docs.html) models matching the API specification. Later on, both clients will probably provide some helpful methods for building requests and for processing the results.


See Also
--------

   * [Document Service API Reference](http://docs.aws.amazon.com/cloudsearch/latest/developerguide/DocumentsBatch.JSON.html)
   * [Search Service API Reference](http://docs.aws.amazon.com/cloudsearch/latest/developerguide/Search.Requests.html)
