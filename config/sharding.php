<?php

return [
    'enabled' => env('DB_SHARDING', false),
    'connections_config' => env('DB_SHARDING_CONNECTIONS_CONFIG', null),
];
