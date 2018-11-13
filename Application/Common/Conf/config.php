<?php
return array(
	//'配置项'=>'配置值'
	'MODULE_ALLOW_LIST'     => array('Home'),
    'TMPL_DENY_PHP'         => false,
	'DB_TYPE'   => 'mysql', // 数据库类型
    //'DB_HOST'   => '10.84.9.54', 
    'DB_HOST'   => '127.0.0.1',
    'DB_NAME'   => 'feasims', // 数据库名
    'DB_USER'   => 'root', // 用户名
    'DB_PWD'    => 'Byzoro_123456', // 密码Byzoro_123456
    'DB_PORT'   => 3306, // 端口
    'DEFAULT_MODULE'        => 'Home',
    'DEFAULT_CONTROLLER'    => 'Basicinfo',
    'DEFAULT_ACTION'        =>  'company', // 默认操作名称
    'SESSION_EXPIRE'        => 1440,
);
