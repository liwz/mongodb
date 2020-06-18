<?php


namespace liwenzhi\Mongodb;

use MongoDB\Driver\Query;
use MongoDB\Driver\WriteResult;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\WriteConcern;
use Think\Exception;

class Mongodb
{

    private $config = [];

    private $hostname;
    private $port = 27017;
    private $database;
    private $username;
    private $password;


    private $collection = '';


    private $wheres = [];
    private $selects = [];
    private $sorts = [];
    private $skip = 0;
    private $limit = 1000;

    private $pk = '_id';

    private $result;


    /**
     * @var Logger
     */
    private $logger;


    /*  @var $_manager \MongoDB\Driver\Manager */
    private $_manager;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->preConfig();
        $this->connect();
        $this->logger = new Logger();
    }


    private function preConfig()
    {
        $this->hostname = $this->config['hostname'];
        $this->port     = $this->config['port'];
        $this->username = $this->config['username'];
        $this->password = $this->config['password'];
        $this->database = $this->config['database'];
    }

    private function connect()
    {
        try {
            $dsn     = "mongodb://{$this->hostname}:{$this->port}/{$this->database}";
            $options = [];
            if ($this->username) {
                $options['username'] = $this->username;
            }
            if ($this->password) {
                $options['password'] = $this->password;
            }
            $this->_manager = new \MongoDB\Driver\Manager($dsn, $options);
        } catch (\Exception $ex) {
            $this->logger->error($ex->getTrace());
        }
    }


    /**
     * 初始化成员
     * @return $this
     */
    public function initialize()
    {
        $this->wheres  = [];
        $this->limit   = 1000;
        $this->skip    = 0;
        $this->sorts   = [];
        $this->selects = [];
        return $this;
    }

    /**
     * @param $collection
     * @return $this
     */
    public function collection($collection)
    {
        $this->collection = $collection;
        //初始化
        return $this->initialize();
    }


    /**
     * 选择db
     * @param $database
     * @return $this
     */
    public function database($database)
    {
        $this->database = $database;
        return $this;
    }

    /**
     * @param array $doc
     * @param array $option
     * @return bool|int|null
     */
    public function insert(array $doc, array $option = [
        'w'        => \MongoDB\Driver\WriteConcern::MAJORITY,
        'wtimeout' => 1000,
    ])
    {
        try {
            $writeConcern = new \MongoDB\Driver\WriteConcern($option['w'], $option['wtimeout']);
            $bulkWrite    = new \MongoDB\Driver\BulkWrite();
            $bulkWrite->insert($doc);
            $dbc          = $this->database . '.' . $this->collection;
            $this->result = $this->_manager->executeBulkWrite($dbc, $bulkWrite, $writeConcern);
            return $this->result->getInsertedCount();
        } catch (\Exception $ex) {
            $this->logger->error($ex->getTrace());
        }
        return false;
    }

    /**
     * 插入多条数据
     * @param array $dataset
     * @param array $option
     * @return int|false
     */
    public function insertMulti(array $dataset, array $option = [
        'w'        => \MongoDB\Driver\WriteConcern::MAJORITY,
        'wtimeout' => 1000,
    ])
    {
        try {
            $writeConcern = new WriteConcern($option['w'], $option['wtimeout']);
            $bulkWrite    = new BulkWrite();
            foreach ($dataset as $doc) {
                $bulkWrite->insert($doc);
            }
            $dbc          = $this->database . '.' . $this->collection;
            $this->result = $this->_manager->executeBulkWrite($dbc, $bulkWrite, $writeConcern);
            return $this->result->getInsertedCount();
        } catch (\Exception $ex) {
            $this->logger->error($ex->getTrace());
        }
        return false;
    }


    /**
     * 删除
     * @param int $limit 0-删除全部;大于0 ,按照查询条件
     * @param array $option
     */
    public function delete($limit = 1, $option = [
        'w'        => \MongoDB\Driver\WriteConcern::MAJORITY,
        'wtimeout' => 1000,
    ])
    {
        try {
            $writeConcern = new WriteConcern($option['w'], $option['wtimeout']);
            $bulkWrite    = new BulkWrite();
            $filter       = $this->wheres;
            if (count($filter) < 1 && $limit == 1) {
                throw new \Exception('where array is err!');
            }
            $bulkWrite->delete($filter, [
                'limit' => $limit,
            ]);
            $dbc          = $this->database . '.' . $this->collection;
            $this->result = $this->_manager->executeBulkWrite($dbc, $bulkWrite, $writeConcern);
            return $this->result->getDeletedCount();

        } catch (\Exception $ex) {
            $this->logger->error($ex->getTrace());
        }
        return false;
    }


    /**
     * @param array $data
     * @param array $updateOptions multi =>true ,更新多条
     * @param array $option
     * @return int|null
     */
    public function update(array $data,
                           array $updateOptions = ['multi' => false, 'upsert' => false],
                           array $option = ['w' => \MongoDB\Driver\WriteConcern::MAJORITY, 'wtimeout' => 1000])
    {
        if (empty($data)) {
            return false;
        }
        try {
            $writeConcern = new \MongoDB\Driver\WriteConcern($option['w'], $option['wtimeout']);
            $bulkWrite    = new \MongoDB\Driver\BulkWrite();
            $filter       = $this->wheres;
            if (count($filter) < 1 && $updateOptions['multi'] === false) {
                throw new \Exception('filter is error!');
            }
            $bulkWrite->update($filter, $data, $updateOptions);
            $dbc          = $this->database . '.' . $this->collection;
            $this->result = $this->_manager->executeBulkWrite($dbc, $bulkWrite, $writeConcern);
            return $this->result->getModifiedCount();
        } catch (\Exception $ex) {
            $this->logger->error($ex->getTrace());
        }
        return false;
    }


    /**
     * @param null $_id
     * @return array|mixed
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function find($_id = null)
    {
        try {
            if ($_id != null) {
                $this->where([$this->pk => $_id]);
            }
            $option       = [
                'projection' => $this->selects,
                "sort"       => $this->sorts,
                "skip"       => 0,
                "limit"      => 1,
            ];
            $query        = new \MongoDB\Driver\Query($this->wheres, $option);
            $dbc          = $this->database . '.' . $this->collection;
            $cursor       = $this->_manager->executeQuery($dbc, $query);
            $this->result = $cursor;
            $returns      = [];
            foreach ($cursor as $document) {
                $bson    = \MongoDB\BSON\fromPHP($document);
                $returns = json_decode(\MongoDB\BSON\toJSON($bson), true);
            }
            return $returns;

        } catch (\Exception $ex) {
            $this->logger->error($ex->getTrace());
        }
        return [];
    }


    /**
     * count
     * @return mixed
     */
    public function count()
    {
        //当需要分页显示，排序，和忽略字段
        $options      = [
            'skip'       => $this->skip,
            'limit'      => $this->limit,
            'sort'       => $this->sorts,
            'projection' => $this->selects,
        ];
        $query        = new Query($this->wheres, $options);
        $commands     = new \MongoDB\Driver\Command(
            [
                "count" => $this->collection,
                "query" => $query
            ]
        );
        $this->result = $this->command($this->database, $commands);
        $response     = $this->result->toArray()[0];
        return $response->n;
    }


    /**
     * inc
     * @param array|string $fields
     * @param int $value 可带符号
     * @param bool[] $updateOptions
     * @return bool|int|null
     */
    public function inc($fields, $value = 0, $updateOptions = ['multi' => true, 'upsert' => true])
    {
        $updates = [];
        if (is_string($fields)) {
            $updates['$inc'][$fields] = $value;
        } elseif (is_array($fields)) {
            foreach ($fields as $field => $value) {
                $updates['$inc'][$field] = $value;
            }
        }
        return $this->update($updates, $updateOptions);
    }


    /**
     * set
     * @param array $fields
     * @param bool[] $updateOptions
     * @return bool|int|null
     */
    public function set($fields = [], $updateOptions = ['multi' => true, 'upsert' => true])
    {
        $updates = [];
        foreach ($fields as $field => $value) {
            $updates['$set'][$field] = $value;
        }
        return $this->update($updates, $updateOptions);
    }


    /**
     * distinct
     * @param $key
     * @return mixed
     */
    public function distinct($key)
    {
        //当需要分页显示，排序，和忽略字段
        $options      = [
            'skip'       => $this->skip,
            'limit'      => $this->limit,
            'sort'       => $this->sorts,
            'projection' => $this->selects,
        ];
        $query        = new Query($this->wheres, $options);
        $commands     = new \MongoDB\Driver\Command(
            [
                'distinct' => $this->collection,
                'key'      => $key,
                'query'    => $query
            ]
        );
        $this->result = $this->command($this->database, $commands);
        return current($this->result->toArray())->values;
    }


    /**
     * max
     * @param string $field
     * @return int|mixed
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function max($field = '')
    {
        $this->sort([$field => -1]);
        $info = $this->find();
        if (empty($info)) {
            return 0;
        }
        return $info[$field];
    }


    /**
     * min
     * @param string $field
     * @return int|mixed
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function min($field = '')
    {
        $this->sort([$field => 1]);
        $info = $this->find();
        if (empty($info)) {
            return 0;
        }
        return $info[$field];
    }

    /**
     * @param $fields
     * @param $value
     * @param bool[] $updateOptions
     * @return bool|int|null
     */
    public function mul($fields, $value, $updateOptions = ['multi' => true, 'upsert' => true])
    {
        $updates = [];
        if (is_string($fields)) {
            $updates ['$mul'][$fields] = $value;
        } elseif (is_array($fields)) {
            foreach ($fields as $field => $value) {
                $updates['$mul'][$field] = $value;
            }
        }
        return $this->update($updates, $updateOptions);
    }


    /**
     * command
     * @param $db
     * @param $commands
     * @return mixed
     */
    public function command($db, $commands)
    {
        try {
            return $this->_manager->executeCommand($db, $commands);
        } catch (\Exception $e) {
            $this->logger->error($e->getTrace());
        }
    }


    public function skip($skip = 0)
    {
        $this->skip = $skip;
        return $this;
    }

    public function limit($limit = 10)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * 多条查询
     * @return array
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function select()
    {
        try {

            $option       = [
                'projection' => $this->selects,
                "sort"       => $this->sorts,
                "skip"       => $this->skip,
                "limit"      => $this->limit,
            ];
            $query        = new \MongoDB\Driver\Query($this->wheres, $option);
            $dbc          = $this->database . '.' . $this->collection;
            $this->result = $this->_manager->executeQuery($dbc, $query);
            $returns      = [];
            foreach ($this->result as $document) {
                $bson       = \MongoDB\BSON\fromPHP($document);
                $returns [] = json_decode(\MongoDB\BSON\toJSON($bson), true);
            }
            return $returns;

        } catch (\Exception $ex) {
            $this->logger->error($ex->getTrace());
        }
        return [];
    }

    public function sort($fields = [])
    {

        foreach ($fields as $col => $val) {
            if ($val == -1 || $val === FALSE || strtolower($val) == 'desc') {
                $this->sorts[$col] = -1;
            } else {
                $this->sorts[$col] = 1;
            }
        }
        return $this;
    }

    public function where($where)
    {
        if (isset($where[$this->pk])) {
            $where[$this->pk] = new \MongoDB\BSON\ObjectID($where[$this->pk]);
        }

        $this->wheres = array_merge($this->wheres, $where);
        return $this;
    }

    public function whereIn($field, array $values)
    {
        $this->wheres[$field]['$in'] = $values;
        return $this;
    }

    public function whereInAll($field, array $values)
    {
        $this->wheres[$field]['$all'] = $values;
        return $this;
    }

    /**
     * @param array $where
     * [
     *  'age'=>'13',
     *  'name'=>'liwz'
     * ]
     * @return $this
     */
    public function whereOr(array $where = [])
    {
        foreach ($where as $wh => $val) {
            $this->wheres['$or'][] = array($wh => $val);
        }
        return $this;
    }

    /**
     * not in
     * @param $field
     * @param array $values
     * @return $this
     */
    public function whereNotIn($field, array $values)
    {
        $this->wheres[$field]['$nin'] = $values;
        return $this;
    }


    /**
     * 大于
     * @param $field
     * @param $value
     * @return $this
     */
    public function whereGt($field, $value)
    {
        $this->wheres[$field]['$gt'] = $value;
        return $this;
    }

    /**
     * 小于
     * @param $field
     * @param $value
     * @return $this
     */
    public function whereLt($field, $value)
    {
        $this->wheres[$field]['$lt'] = $value;
        return $this;
    }

    /**
     * 小于等于
     * @param $field
     * @param $value
     * @return $this
     */
    public function whereLte($field, $value)
    {
        $this->wheres[$field]['$lt'] = $value;
        return $this;
    }

    /**
     * 大于等于
     * @param $field
     * @param $value
     * @return $this
     */
    public function whereGte($field, $value)
    {
        $this->wheres[$field]['$gte'] = $value;
        return $this;
    }

    /**
     * @param $field
     * @param $value1
     * @param $value2
     * @param bool $hasBoundary 是否包含边界值
     * @return $this
     */
    public function whereBetween($field, $value1, $value2, $hasBoundary = true)
    {
        if ($hasBoundary) {
            $this->wheres[$field]['$gte'] = $value1;
            $this->wheres[$field]['$lte'] = $value2;
        } else {
            $this->wheres[$field]['$gt'] = $value1;
            $this->wheres[$field]['$lt'] = $value2;
        }

        return $this;
    }

    /**
     * 不等于
     * @param $field
     * @param $value
     * @return $this
     */
    public function whereNotEqual($field, $value)
    {
        $this->wheres[$field]['$ne'] = $value;
        return $this;
    }


    /**
     * @TODO 未完成
     * @param $field
     * @param $value
     * @return $this
     */
    public function whereLike($field, $value)
    {
        $this->where([$field => "/$value/"]);
        return $this;
    }


}