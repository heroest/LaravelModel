<?php 

require('vendor/autoload.php');

use Heroest\LaravelModel\ConnectionPool;


ConnectionPool::add('vip_base', [
        'type'      => 'mysql',
        'host'      => '192.168.40.219',
        'username'  => 'viptest',
        'password'  => 'viptest_2017',
        'db_name'   => 'music',
        'port'      => 3306,
    ]);

$query = new \Heroest\LaravelModel\Query();
$list = $query->connection('vip_base')
    ->table('user')
    ->where(function($q){
        $q->where('role', '=', 0);
        $q->where(function($q){
            $q->orWhere('id', '<', 1000);
            $q->orWhere('id', '>', 2000);
        });
    })
    ->limit(20)
    ->select('nick', 'id', 'sex')
    ->get();

pp($list->toArray());

?>