<?php 

require('vendor/autoload.php');

class User
{
    use \Heroest\LaravelModel\Traits\CompatiableModel;

    public function __construct()
    {
        $this->injectLaravelModel()
        ->setFunctionPrefix('my_')
        ->my_addConnection('vip_base', [
            'type'      => 'mysql',
            'host'      => '192.168.40.219',
            'username'  => 'viptest',
            'password'  => 'viptest_2017',
            'db_name'   => 'music',
            'port'      => 3306,
        ])
        ->my_setParameter([
            'table' => 'user',
            'primaryKey' => 'id'
        ]);
    }

    public function classroom()
    {
        return $this->my_hasMany(ClassRoom::CLASS, 'student_id', 'id');
    }
}

class ClassRoom
{
    use \Heroest\LaravelModel\Traits\CompatiableModel;

    public function __construct()
    {
        $this->setFunctionPrefix('test_')
        ->test_connection('vip_base')
        ->test_setParameter([
            'table' => 'class_room',
            'primaryKey' => 'id'
        ]);
    }
}


$model = new User();
$list = $model->my_where('id', '!=', 0)->my_limit(2)->get();
pp($list);


?>