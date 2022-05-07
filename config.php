<?php
$settings = [
  'steam_api_key' => '', // https://steamcommunity.com/dev/apikey
  'db' => [
      'host' => '127.0.0.1',
      'port' => 3306,
      'database' => '',
      'username' => '',
      'password' => ''
  ],
  'cache' => [
      'cache.default' => 'file', // 'file', 'redis', 'memcached' or 'array' (no cache)
      'database.redis' => [
          'cluster' => false,
          'default' => [
              'host' => '127.0.0.1',
              'password' => null,
              'port' => 6379,
              'database' => 0
          ]
      ],
      'cache.stores.memcached' => [
          'servers' => [
              [
        				'host'   => '172.17.0.1',
        				'port'   => 11211,
        				'weight' => 100
              ]
    			]
      ]
  ],
  'session_lifetime' => 60 * 60 * 24 * 7 * 4, // seconds * minutes * hours * days * weeks
  'development_mode' => false
];
