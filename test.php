<?php namespace Test\Model;

require('vendor/autoload.php');


class User
{
    use \Heroest\LaravelModel\Traits\Model;

    protected $table = 'user';

    protected $primaryKey = 'id';

    public function __construct()
    {
        $this->addConnection('vip_base', [
            'type'      => 'mysql',
            'host'      => '192.168.40.219',
            'username'  => 'viptest',
            'password'  => 'viptest_2017',
            'db_name'   => 'music',
            'port'      => 3306,
        ]);
    }

    public function classroom()
    {
        return $this->hasMany(\Test\Model\ClassRoom::CLASS, 'student_id', 'id');
    }

    public function public_info()
    {
        return $this->hasOne(\Test\Model\PublicInfo::CLASS, 'user_id', 'id');
    }

    public function teacher()
    {
        return $this->belongsToMany(\Test\Model\Teacher::CLASS, 'class_room', ['id' => 'student_id'], ['teacher_id' => 'id']);
    }
}

class PublicInfo
{
    use \Heroest\LaravelModel\Traits\Model;

    protected $table = 'user_public_info';

    protected $primaryKey = 'id';


    public function __construct()
    {
        $this->connection('vip_base');
    }
}

class ClassRoom
{
    use \Heroest\LaravelModel\Traits\Model;

    protected $table = 'class_room';

    protected $primaryKey = 'id';

    public function __construct()
    {
        $this->connection('vip_base');
    }

    public function class_net()
    {
        return $this->hasMany(\Test\Model\ClassNet::CLASS, 'class_id', 'id');
    }
}

class ClassNet
{
    use \Heroest\LaravelModel\Traits\Model;

    protected $table = 'class_net';

    protected $primaryKey = 'id';

    public function __construct()
    {
        $this->connection('vip_base');
    }
}

class Teacher
{
    use \Heroest\LaravelModel\Traits\Model;

    public $table = 'user_teacher';

    public $primaryKey = 'id';

    public function __construct()
    {
        $this->connection('vip_base');
    }
}


$model = new \Test\Model\User();

//$list = $model->with(['ext' => function($q){ $q->where('uid', '>', 0); }])->findMany([1,2,3]);
$list = $model->with([
                    'teacher'
                ])
                ->where(function($aq){
                    $aq->where('device', 1);
                    $aq->orWhere('device', 2);
                })
                ->select('id', 'nick')
                ->limit(2)->get();
pp($list->toArray());

//vp($model->getQueryLog());
/*
$model->fill(['username' => 'abc', 'password' => 'def', 'email' => 'abc@test.com'])->save();
$model->fill(['username' => 'cba', 'password' => 'fed', 'email' => 'cba@tset.moc'])->save();

vp($model->getQueryLog());
*/
?>

