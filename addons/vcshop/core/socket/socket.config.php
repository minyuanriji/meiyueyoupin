<?php

/**
 * socket server配置文件，重启后生效
 */

// 开发模式开关
define('SOCKET_SERVER_DEBUG', false);

// 设置服务端IP
define('SOCKET_SERVER_IP', 'localhost');

// 设置服务端端口
define('SOCKET_SERVER_PORT', '9501');

// 设置是否启用SSL
define('SOCKET_SERVER_SSL', false);

// 设置SSL KEY文件路径
define('SOCKET_SERVER_SSL_KEY_FILE', '/www/server/panel/vhost/cert/www.meiyueyoupin.com/privkey.pem');
//define('SOCKET_SERVER_SSL_KEY_FILE', '');

// 设置SSL CERT文件路径
define('SOCKET_SERVER_SSL_CERT_FILE', '/www/server/panel/vhost/cert/www.meiyueyoupin.com/fullchain.pem');
//define('SOCKET_SERVER_SSL_CERT_FILE', '');

// 设置启动的worker进程数
define('SOCKET_SERVER_WORKNUM', 8);

// 设置客户端请求IP
define('SOCKET_CLIENT_IP', '120.24.80.229');   //请将域名换成你自己的www.meiyueyoupin.com
//define('SOCKET_CLIENT_IP', 'www.meiyueyoupin.com');   //请将域名换成你自己的www.meiyueyoupin.com