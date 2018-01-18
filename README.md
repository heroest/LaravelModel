# LaravelModel

## 这是一款可以在各个地方自由使用Laravel Eloquent风格的ORM


###### 你是不是还在为维护Legacy Code而头疼？
###### 你是不是已经沉迷于Laravel Eloquent的优雅之中？
###### 你是不是在用别的框架的时候在想如果能用上Eloquent该多好？


原生开发，无需依赖包，尽可能保持原有框架的完整性。
--------------------------------------------------

环境要求：
=============
* PHP >= 5.4.0
* PDO


使用方法：
=============
>首先安装composer包

<code>composer require heroest/laravel-model dev-master</code>
<br>

>在适当的位置添加：
``` PHP 
require vendor/autoload.php; 
```

>然后在你的Model中添加以下Trait： 
``` PHP
use Heroest\LaravelModel\Traits\Model;
```

>或者添加兼容型的Trait（需要在原有的Eloquent风格的变量和方法前需要添加下划线 “_“ ， 用来与框架自身封装的方法区分开来
>[重构中， 暂时无法使用]
```PHP
use Heroest\LaravelModel\Traits\Compatiable\Model;
```


<br>
<br>

>在Model的构造器或者适当的位置添加一下代码：
```PHP
/* 
    这条语句的作用相当于添加了一条connection到PDO连接池
    并取名为project, 最后在Model设置默认链接为project 
*/
$this->addConnection('project', [
            'type' => 'mysql',
            'host' => 'localhost',
            'username' => 'root',
            'password' => '664664',
            'db_name' => 'laravel_test',
            'port' => 3306,
        ]);
```
>如果你可以获取PDO实例的话也可以这样
```PHP
$this->addConnection('project', $pdo_object);
```

<br />
<br />
Done. Enjoy Laravel Eloquent


范例代码, 或者查看test.php的代码：
---------

```PHP
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
                    $qu->orWhere('uid', '<', 10);
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
```

