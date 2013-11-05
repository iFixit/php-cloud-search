<?php

return [
   'name' => 'CloudSearch document upload',
   'apiVersion' => '2011-02-01',
   'baseUrl' => 'http://{endpoint}',
   'description' => 'Upload batches of documents to a CloudSearch domain',
   'operations' => [
      'SendBatch' => [
         'httpMethod' => 'POST',
         'uri' => '/{version}/documents/batch',
         'responseClass' => 'SendBatchResponse',
         'responseType' => 'model',
         'parameters' => [
            'Documents' => [
               'type' => 'array',
               'required' => true,
               'location' => 'json.array',
               'minItems' => 1,
               'items' => [
                  'type' => 'object',
                  'properties' => [
                     'type' => [
                        'type' => 'string',
                        'enum' => ['add', 'delete'],
                        'required' => true
                     ],
                     'id' => [
                        'type' => 'string',
                        'pattern' => '/[a-z0-9][a-z0-9_]{0,127}/',
                        'minLength' => 1,
                        'maxLength' => 128,
                        'required' => true
                     ],
                     'version' => [
                        'type' => 'number',
                        'minimum' => 1,
                        'maximum' => 4294967295,
                        'required' => true
                     ],
                     'lang' => [
                        'type' => 'string',
                        'minLength' => 2,
                        'maxLength' => 2
                     ],
                     'fields' => [
                        'type' => 'object',
                        'patternProperties' => [
                           '/[a-zA-Z0-9][a-zA-Z0-9_]{0,63}/' => [
                              'type' => 'string',
                           ]
                        ]
                     ]
                  ]
               ]
            ]
         ]
      ],
      'SendBatchRaw' => [
         'httpMethod' => 'POST',
         'uri' => '/{version}/documents/batch',
         'responseClass' => 'SendBatchResponse',
         'responseType' => 'model',
         'parameters' => [
            'Content-Type' => [
               'type' => 'string',
               'location' => 'header',
               'static' => true,
               'default' => 'application/json'
            ],
            'Json' => [
               'type' => 'string',
               'required' => true,
               'location' => 'body',
            ]
         ]
      ]
   ],
   'models' => [
      'SendBatchResponse' => [
         'type' => 'object',
         'properties' => [
            'status' => [
               'type' => 'text',
               'location' => 'json',
               'enum' => ['success', 'error'],
               'required' => true
            ],
            'adds' => [
               'type' => 'integer',
               'location' => 'json',
               'minimum' => 0,
               'required' => true
            ],
            'deletes' => [
               'type' => 'integer',
               'location' => 'json',
               'minimum' => 0,
               'required' => true
            ],
            'errors' => [
               'type' => 'array',
               'location' => 'json',
               'required' => false,
               'items' => [
                  'type' => 'object',
                  'properties' => [
                     'message' => [
                        'type' => 'string',
                        'required' => true
                     ]
                  ]
               ]
            ],
            'warnings' => [
               'type' => 'array',
               'location' => 'json',
               'required' => false,
               'items' => [
                  'type' => 'object',
                  'properties' => [
                     'message' => [
                        'type' => 'string',
                        'required' => true
                     ]
                  ]
               ]
            ]
         ]
      ]
   ]
];
