<?php
global $jwt_secret_key;
global $jwt_server;
global $jwt_hashing_algorithm;

$jwt_secret_key         = md5('thesecretkey');
$jwt_server             = 'mikelinden.com';
$jwt_hashing_algorithm  = 'HS512';
