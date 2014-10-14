<?php

return [
   'name' => 'CloudSearch document upload',
   'apiVersion' => '2011-02-01',
   'signatureVersion' => 'v4',
   'description' => 'Upload batches of documents to a CloudSearch domain',
   'operations' => [
      'UploadDocuments' => [
         'httpMethod' => 'POST',
         'uri' => '/{version}/documents/batch',
         'responseClass' => 'SendBatchResponse',
         'responseType' => 'model',
         'parameters' => [
            'documents' => [
               'type' => 'string',
               'required' => true,
               'location' => 'body',
            ],
            'contentType' => [
               'type' => 'string',
               'required' => true,
               'location' => 'header',
               'sentAs' => 'Content-Type'
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
