<?php

return [
    'adminEmail' => 'admin@example.com',
    'cas'   => [
        'cas_host' => 'localhost', //CAS Server 主机
        'cas_port' => 8888, // CAS server端口
        'cas_context' => '/cas', // CAS Server 路径
        'cas_server_ca_cert_path' => './ssoserver.cer' // <span lang="EN-US">CAS Server </span>证书
    ],
];
