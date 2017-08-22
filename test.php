<?php namespace Test\Model;

require('vendor/autoload.php');

use Heroest\LaravelModel\Traits\Compatiable\Model;
//use Heroest\LaravelModel\Traits\Relationship;

class TestModel
{
    use Model;

    protected $_table = 'user';

    protected $_primaryKey = 'id';

    protected $_fillable = ['username', 'password', 'email'];

    protected $_updated_at  = 'updated_at';

    protected $_hidden = ['password'];

    public function __construct()
    {
        $this->_addConnection('project', [
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
$model->_beginTransaction();
$list = $model
            ->_leftJoin('user_ext e1', function($join){
                $join->_on('e1.uid', '=', 'user.id');
                $join->_on('e1.uid', '!=', 0);
                $join->_orOn(function($join){
                    $join->_on('e1.title', '!=', '');
                });
            })
            ->_leftJoin('user_ext e2', function($join){
                $join->_on('e2.uid', '=', 'user.id');
                $join->_on('e2.uid', 0);
            })
            ->_select('user.*')
            ->_limit(3)
            ->_get();

//vpd($list);
foreach($list as $user) {
    //vp($user->toArray());
    $user->username = mt_rand(100, 999);
    $user->_save();
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

