<?php namespace Test\Model;

require('vendor/autoload.php');

use Heroest\LaravelModel\Traits\Model;
//use Heroest\LaravelModel\Traits\Relationship;

class TestModel
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
}



$model = new \Test\Model\TestModel();

//$list = $model->findMany([1,2,3,4,5,6,7,8]);
$model->beginTransaction();
$list = $model
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

//vpd($list);
foreach($list as $user) {
    //vp($user->toArray());
    $user->username = mt_rand(100, 999);
    $user->save();
}
$model->rollback();
//$model->commit();
vp($model->getQueryLog());
/*
$model->fill(['username' => 'abc', 'password' => 'def', 'email' => 'abc@test.com'])->save();
$model->fill(['username' => 'cba', 'password' => 'fed', 'email' => 'cba@tset.moc'])->save();

vp($model->getQueryLog());
*/
?>

