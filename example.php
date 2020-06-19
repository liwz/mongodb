<?php

require_once "src/Logger.php";
require_once "src/Mongodb.php";

$config = [
    'hostname' => '127.0.0.1',
    'port'     => 27017,
    'database' => 'dbname',
    'username' => '',
    'password' => '',
];
$mongo  = new \msb\Mongo\Mongodb($config);

//
//$list = $mongo->collection('user')->select();
//for ($i = 0; $i < 200; $i++) {
//    $doc = [
//        'user_name' => '李文志' . $i,
//        'sex'       => rand(1, 3),
//        'age'       => rand(10, 40),
//        'address'   => '呵呵哒' . $i,
//    ];
//    var_dump($mongo->collection('user')->insert($doc));
//}
//
//return;

$list = $mongo->collection('user')
//    ->where(['_id' => '5eeb432ce5b52119a13e65f5'])
//    ->whereIn('_id', [
//        '5eeb432ce5b52119a13e65f4',
//        '5eeb432ce5b52119a13e65f3',
//        '5eeb432ce5b52119a13e65f5'
//    ])->whereGt('sex', 1)
//    ->where(['sex' => 2])
//    ->whereGte('age', 30)
    ->whereBetween('age', 1, 20)
//    ->where([])
//    ->skip(0)->limit(2)
    ->count();


var_dump($list);


return;


echo "插入一条记录\n";


echo "===插入多条记录\n";
$dataset = [];
for ($i = 0; $i < 3; $i++) {
    $dataset [] = [
        'user_name' => '李文志' . $i,
        'sex'       => rand(1, 3),
        'age'       => rand(20, 90),
        'address'   => '呵呵哒' . $i
    ];
}
var_dump($mongo->collection('user')->insertMulti($dataset));


echo "====查询1条===\n";
$info = $mongo->collection('user')->find('5ee9d4d78eaaa80e025808a3');
var_dump($info);

echo "查询多条:\n";
$list = $mongo->collection('user')->sort()->where([])->skip(0)->limit(10)->select();
var_dump($list);


echo "删除===1条\n";
$cnt = $mongo->collection('user')
    ->where(['_id' => '5ee9d88abbd14a207218d924'])
    ->where(['user_name' => '李文志1'])->delete();
var_dump($cnt);

echo "删除多条==\n";
$cnt = $mongo->collection('user')->count();
var_dump("条数:" . $cnt);
$cnt = $mongo->collection('user')->delete(3);
var_dump("删除受影响行数" . $cnt);
$cnt = $mongo->collection('user')->count();
var_dump("条数:" . $cnt);

echo "inc 操作\n";
$d = $mongo->collection('jishu')->where(['aid' => 666])
    ->inc('view', 2, ['multi' => true, 'upsert' => true]);

var_dump($d);
echo "decr 操作\n";
$d = $mongo->collection('jishu')->where(['aid' => 999])
    ->inc('view', -2, ['multi' => true, 'upsert' => true]);

var_dump($d);

echo "view去重操作:\n";
$distinct = $mongo->collection('jishu')->where([])->distinct('view');
var_dump($distinct);
echo "view最大值:\n";
$distinct = $mongo->collection('jishu')->where([])->max('view');
var_dump($distinct);

echo "view最小值:\n";
$distinct = $mongo->collection('jishu')->where([])->min('view');
var_dump($distinct);


echo "group 查询\n";


$list = $mongo->collection('user')
    ->where(['user_name' => '李文志'])
    ->whereGt('age', 13)
    ->whereGt('age', 13)
    ->group('sex', 'age', 'count');


var_dump($list);






