<?php
/**
 * Created by PhpStorm.
 * User: zhangjincheng
 * Date: 16-7-15
 * Time: 下午4:49
 */
$config['mysql']['enable'] = true;
$config['mysql']['active'] = 'strack';
$config['mysql']['strack']['host'] = 'localhost';
$config['mysql']['strack']['port'] = '3306';
$config['mysql']['strack']['user'] = 'root';
$config['mysql']['strack']['password'] = 'Strack2016!';
$config['mysql']['strack']['database'] = 'stracklog';
$config['mysql']['strack']['charset'] = 'utf8';
$config['mysql']['asyn_max_count'] = 10;

return $config;