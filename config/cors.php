<?php
/**
 * CORS configuration file.
 * 
 * The specified paths will be matched against the request path. 
 * @var array [] = all paths and ['api/*', 'admin/*'] = specific paths
 * 
 * The allowed origin. An asterisk (*) is a wildcard character that will match all origins.
 * @var string|array string: '*' or array:['https://example.com', ...]
 * 
 * The methods, and headers are also specified in this configuration file.
 * @var array
 * 
 * The credentials option indicates whether the response to the request can be exposed when the credentials flag is true.
 * @var bool
 * 
 * The age option indicates how long the results of a preflight request can be cached in a preflight result cache.
 * @var int
 * 
 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
 */

return [
    'paths' => [],
    'origin' => '*',
    'methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'X-XSRF-TOKEN'],
    'credentials' => 'true',
    'age' => 86400,
];