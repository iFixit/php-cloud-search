<?php

return [
   'name' => 'CloudSearchQuery',
   'apiVersion' => '2011-02-01',
   'baseUrl' => 'http://{endpoint}',
   'description' => 'Query a domain index',
   'operations' => [
      'Query' => [
         'httpMethod' => 'GET',
         'uri' => '/{version}/search',
         'responseClass' => 'QueryResponse',
         'responseType' => 'model',
         'parameters' => [
            'q' => [
               'type' => 'string',
               'location' => 'query',
            ],
            'bq' => [
               'type' => 'string',
               'location' => 'query'
            ],
            'facet' => [
               'type' => 'string',
               'location' => 'query'
            ],
            'rank' => [
               'type' => 'string',
               'location' => 'query'
            ],
            'results-type' => [
               'type' => 'string',
               'default' => 'json',
               'location' => 'query'
            ],
            'return-fields' => [
               'type' => 'string',
               'location' => 'query'
            ],
            'start' => [
               'type' => 'integer',
               'location' => 'query',
               'minimum' => 0,
               'maximum' => 4294967295
            ],
            'size' => [
               'type' => 'integer',
               'location' => 'query',
               'minimum' => 0,
               'maximum' => 4294967295
            ]
         ],
         'additionalParameters' => [
            'location' => 'query'
         ]
      ]
   ],
   'models' => [
      'QueryResponse' => [
         'type' => 'object',
         'properties' => [
             'match-expr' => [
                 'type' => 'string',
                 'location' => 'json'
             ],
             'hits' => [
                 'type' => 'object',
                 'location' => 'json',
                 'properties' => [
                     'found' => [
                         'type' => 'integer'
                     ],
                     'start' => [
                         'type' => 'integer'
                     ],
                     'hit' => [
                         'type' => 'array',
                         'items' => [
                             'type' => 'object',
                             'properties' => [
                                 'id' => [
                                     'type' => 'string'
                                 ],
                                 'data' => [
                                     'type' => 'object',
                                     'additionalProperties' => [
                                         'type' => 'array',
                                         'fields' => [
                                             'type' => ['string', 'integer']
                                         ]
                                     ]
                                 ]
                             ]
                         ]
                     ],
                 ]
             ],
             'facets' => [
                 'type' => 'object',
                 'location' => 'json',
                 'additionalProperties' => [
                     'constraints' => [
                         'type' => 'array',
                         'fields' => [
                             'type' => 'object',
                             'properties' => [
                                 'value' => [
                                     'type' => 'string',
                                 ],
                                 'count' => [
                                     'type' => 'integer',
                                 ]
                             ]
                         ]
                     ]
                 ]
             ],
             'info' => [
                 'type' => 'object',
                 'location' => 'json',
                 'properties' => [
                     'rid' => [
                         'type' => 'string',
                     ],
                     'time-ms' => [
                         'type' => 'integer',
                     ],
                     'cpu-time-ms' => [
                         'type' => 'integer',
                     ]
                 ]
             ],
             'rank' => [
                 'type' => 'string',
                 'location' => 'json'
             ],
             'messages' => [
                 'type' => 'array',
                 'location' => 'json',
                 'fields' => [
                     'type' => 'object',
                     'properties' => [
                         'severity' => [
                             'type' => 'string',
                             'enum' => ['warning', 'error', 'fatal']
                         ],
                         'code' => [
                             'type' => 'string'
                         ],
                         'message' => [
                             'type' => 'string'
                         ]
                     ]
                 ]
             ],
         ]
      ]
   ]
];
