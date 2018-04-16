<?php

return [
    'adminEmail' => 'admin@example.com',
    'cas'   => [
        'cas_host' => '10.248.70.138', //CAS Server 主机
        'cas_port' => 8888, // CAS server端口
        'cas_context' => '/cas', // CAS Server 路径
        'ca_cert_way' => 'http', // CAS 认证方式 https or http
        'cas_server_ca_cert_path' => './ssoserver.cer', // <span lang="EN-US">CAS Server </span>证书
        'log_out_request' => 'true' // 是否接
    ],
];
