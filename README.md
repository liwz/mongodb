# mongodb class

```
composer require liwenzhi/mongodb
```

##查询一条

```php
$config = [
    'hostname' => '127.0.0.1',
    'port'     => 27017,
    'database' => 'dbname',
    'username' => '',
    'password' => '',
];
$mongo = new Mongodb($config);
//方式1
$mongo->collection('tablename')->where(['_id'=>'xxx'])->find();
//方式2
$mongo->collection('tablename')->find('xxx');


```

##查询多条

```php
$mongo = new Mongodb($config);
$mongo->collection('tablename')->where(['city'=>'xxx'])->sort(['age'=>1])->skip(0)->limit(10)->select();

```



##添加一条

```php
$doc = [
	'name'=>'liwz',
	'age'=>12
];
$mongo = new Mongodb($config);
$mongo->collection('tablename')->insert($doc);
```

##添加多条

```php
$docs =[
	 [
		'name'=>'wangsan',
		'age'=>18
	], 
	[
		'name'=>'liwz',
		'age'=>12
	]
];
$mongo = new Mongodb($config);
$mongo->collection('tablename')->insertMulti($docs);
```

##删除数据

```php
$mongo = new Mongodb($config);
$mongo->collection('tablename')->where(['age'=>12])->delete();
```

##修改数据

```php
$mongo = new Mongodb($config);
$mongo->collection('tablename')->where(['_id'=>12])->update(['coin'=>9999]);
```

##其他函数

```php
$mongo->max()//最大值
$mongo->min()//最小值
$mongo->inc()//累加/减操作
$mongo->count()//统计count
$mongo->distinct()//去重
  
  
  //条件函数
  
$mongo->where()//普通where条件
$mongo->whereIn()
$mongo->whereInAll()
$mongo->whereOr()
$mongo->whereNotIn()
$mongo->whereGt()
$mongo->whereLt()
$mongo->whereLte()
$mongo->whereGte()
$mongo->whereBetween()
$mongo->whereNotEqual()
```

