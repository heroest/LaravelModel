# LaravelModel, 一款可以在各个地方自由使用Laravel Eloquent风格的ORM

### 这是一款可以在各个地方自由使用Laravel Eloquent风格的ORM


###### 你是不是还在为维护Legacy Code而头疼？
###### 你是不是已经沉迷于Laravel Eloquent的优雅之中？
###### 你是不是在用别的框架的时候在想如果能用上laravel Eloquent该多好？


环境要求：
=============
PHP > 5.4.0
PDO


使用方法：
=============
composer require heroest/laravel-model

>在适当的位置添加：
<code>require vendor/autoload.php;</code>

>在你的Model中添加： 
<code>user Heroest\LaravelModel\Traits\Model;</code>

>在Model的construct或者适当的位置添加一下代码：
<code>
//这条语句的作用相当于添加了一条connection到PDO连接池， 并取名为project, 最后在Model设置默认链接为project
$this->addConnection('project', [
            'type' => 'mysql',
            'host' => 'localhost',
            'username' => 'root',
            'password' => '664664',
            'db_name' => 'laravel_test',
            'port' => 3306,
        ]);
</code>

>或者如果你可以获取PDO实例的话也可以这样
<code>
$this->addConnection('project', $pdo_object);
</code>

>范例代码
<code>
<?php namespace Test\Model;

require('vendor/autoload.php');

use Heroest\LaravelModel\Traits\Model;

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
            ->limit(3)
            ->get();


foreach($list as $user) {
    $user->username = mt_rand(100, 999);
    $user->save();
}

vp($model->getQueryLog());
$model->fill(['username' => 'abc', 'password' => 'def', 'email' => 'abc@test.com'])->save();
$model->fill(['username' => 'cba', 'password' => 'fed', 'email' => 'cba@tset.moc'])->save();
vp($model->getQueryLog());
?>
</code>


Done. Enjoy Laravel Eloquent


>>>Relationship功能正在开发中....