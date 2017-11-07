<?php namespace Test\Model;

require('vendor/autoload.php');

use Heroest\LaravelModel\Traits\Model;
//use Heroest\LaravelModel\Traits\Relationship;

class User
{
    use Model;

    protected $table = 'user';

    protected $primaryKey = 'id';

    protected $fillable = ['username', 'password', 'email'];

    protected $updated_at  = 'updated_at';

    protected $hidden = ['password'];

    public function __construct()
    {
        $this->addConnection('project', [
            'type' => 'mysql',
            'host' => 'localhost',
            'username' => 'root',
            'password' => '664664',
            'db_name' => 'laravel_test',
            'port' => 3306,
        ]);
    }

    public function ext()
    {
        return $this->hasOne(\Test\Model\UserExt::CLASS, 'uid', 'id');
    }

    public function post()
    {
        return $this->hasMany(\Test\Model\Post::CLASS, 'poster_id', 'id');
    }
}

class UserExt
{
    use Model;

    protected $table = 'user_ext';

    protected $primaryKey = 'id';

    protected $fillable = ['uid', 'title'];

    protected $updated_at  = 'updated_at';

    public function __construct()
    {
        $this->addConnection('project', [
            'type' => 'mysql',
            'host' => 'localhost',
            'username' => 'root',
            'password' => '664664',
            'db_name' => 'laravel_test',
            'port' => 3306,
        ]);
    }
}


$model = new \Test\Model\User();

//$list = $model->with(['ext' => function($q){ $q->where('uid', '>', 0); }])->findMany([1,2,3]);
$model->beginTransaction();

$list = $model
            ->with(['ext' => function($q){
                $q->where(function($qu){
                    $qu->where('uid', '>', 1);
                    $qu->orWhere(function($quu){
                        $quu->where('uid', '<', 11);
                        $quu->where('uid', '>', 3);
                    });
                });
            }])
            ->leftJoin('user_ext e1', function($join){
                $join->on('e1.uid', '=', 'user.id');
                $join->on('e1.uid', '!=', 0);
                $join->orOn(function($join){
                    $join->on('e1.title', '!=', '');
                });
            })
            ->leftJoin('user_ext e2', function($join){
                $join->on('e2.uid', '=', 'user.id');
                $join->on('e2.uid', 0);
            })
            ->select('user.*')
            ->limit(3)
            ->get();

//ppd($list);
vp($list);
foreach($list as $user) {
    vp($user->toArray());
    //$user->username = mt_rand(100, 999);
    $user->save();
}
//$model->rollback();
$model->commit();
vp($model->getQueryLog());
/*
$model->fill(['username' => 'abc', 'password' => 'def', 'email' => 'abc@test.com'])->save();
$model->fill(['username' => 'cba', 'password' => 'fed', 'email' => 'cba@tset.moc'])->save();

vp($model->getQueryLog());
*/
?>

