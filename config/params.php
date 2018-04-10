<?php

return [
    'adminEmail' => 'admin@example.com',
    'cas'   => [
        'cas_host' => '172.16.116.136', //CAS Server 主机
        'cas_port' => 9091, // CAS server端口
        'cas_context' => '/cas', // CAS Server 路径
        'cas_server_ca_cert_path' => './ssoserver.cer' // <span lang="EN-US">CAS Server </span>证书
    ],
];
