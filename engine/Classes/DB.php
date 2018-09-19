<?php
/**
 * User: Karel Wintersky
 * Date: 26.08.2018, time: 14:25
 */

interface DBConnectionInterface {

    /**
     * Возвращает инстанс класса
     * @param null $suffix
     * @return mixed
     */
    public static function getInstance($suffix = NULL);

    /**
     * Возвращает PDO::Connection
     *
     * @param null $suffix
     * @return PDO
     */
    public static function getConnection($suffix = NULL):\PDO;

    /**
     * Инициализирует подключение
     *
     * @param null $suffix
     * @param $config
     * @return mixed
     */
    public static function init($suffix, $config);
}

/**
 * Class DB
 */
class DB implements DBConnectionInterface {
    const VERSION = '1.4/LIBDb';

    private static $_instances = [
    ];

    private static $_configs = [
    ];

    private static $_connect_states = [
    ];

    private static $_pdo_instances = [
    ];

    /**
     * Преобразовывает суффикс подключения во внутренний ключ соединений
     * @param null $suffix
     * @return string
     */
    private static function getKey($suffix = NULL)
    {
        return 'database' . ($suffix ? ":{$suffix}" : '');
    }

    /**
     * Проверяет сущестование инстанса в массиве $_instances
     * @param null $suffix
     * @return bool
     */
    private static function checkInstance($suffix = NULL) {

        $key = self::getKey($suffix);

        return ( array_key_exists($key, self::$_instances) && self::$_instances[$key] !== NULL  );
    }

    /**
     * @param null $suffix
     * @return DB
     */
    public static function getInstance($suffix = NULL):DB {

        $key = self::getKey($suffix);

        if (!self::checkInstance($suffix)) {
            self::$_instances[$key] = new self($suffix);
        }

        return self::$_instances[$key];
    }

    /**
     * Возвращает PDO::Connection
     *
     * @param null $suffix
     * @return \PDO
     */
    public static function getConnection($suffix = NULL):\PDO
    {
        $key = self::getKey($suffix);

        if (!self::checkInstance($suffix)) {
            self::$_instances[$key] = new self($suffix); // EQ self::getInstance($suffix);
        }

        return self::$_pdo_instances[$key];
    }

    /**
     * Predicted (early) initialization
     *
     * @param null $suffix
     */
    public static function init($suffix, $config)
    {
        $config_key = self::getKey($suffix);
        self::setConfig($config, $suffix);
        self::$_instances[$config_key] = new self($suffix);
    }

    /**
     * Сеттер
     * @param $config
     * @param null $suffix
     */
    private static function setConfig($config, $suffix = NULL)
    {
        $config_key = self::getKey($suffix);

        self::$_configs[ $config_key ] = $config;
    }

    /**
     * Геттер
     *
     * @param null $suffix
     * @return mixed|null
     */
    private static function getConfig($suffix = NULL)
    {
        $config_key = self::getKey($suffix);

        return array_key_exists( $config_key, self::$_configs) ? self::$_configs[ $config_key ] : NULL;
    }

    /**
     * DB constructor.
     * @param null $suffix
     */
    public function __construct($suffix = NULL)
    {
        $config_key = self::getKey($suffix);

        $config
            = is_null( self::getConfig($suffix) )
            ? Config::get( $config_key )
            : self::getConfig( $suffix );

        $dbhost = $config['hostname'];
        $dbname = $config['database'];
        $dbuser = $config['username'];
        $dbpass = $config['password'];
        $dbport = $config['port'];

        $db_charset         = $config['charset']         ?? 'utf8';
        $db_charset_collate = $config['charset_collate'] ?? 'utf8_unicode_ci';

        $dsl = "mysql:host=$dbhost;port=$dbport;dbname=$dbname";

        try {
            if ($config === NULL)
                throw new \Exception("Config section `[{$config_key}]` not declared in config workspace.\r\n" , 2);

            $dbh = new \PDO($dsl, $dbuser, $dbpass);

            $dbh->exec("SET NAMES {$db_charset} COLLATE {$db_charset_collate}");
            $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $dbh->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

            self::$_pdo_instances[ $config_key ] = $dbh;

            $connection_state = TRUE;

        } catch (\PDOException $pdo_e) {
            $message = "Unable to connect `{$dsl}`, PDO CONNECTION ERROR: " . $pdo_e->getMessage() . "\r\n" . PHP_EOL;

            $connection_state = [
                'error' =>  $message,
                'state' =>  FALSE
            ];

        } catch (\Exception $e) {
            $connection_state = [
                'error' =>  $e->getMessage(),
                'state' =>  FALSE
            ];
            self::$_configs[ $config_key ] = NULL;
        }

        if ($connection_state !== TRUE) {
            die($connection_state['error']);
        }

        self::$_connect_states[ $config_key ] = $connection_state;
        self::$_configs[ $config_key ] = $config;

        return true;
    }

    /**
     *
     *
     * @param null $suffix
     * @return null|string
     */
    public static function getTablePrefix( $suffix = NULL )
    {
        if (!self::checkInstance($suffix)) return NULL;

        $config_key = self::getKey($suffix);

        return
            array_key_exists('table_prefix', self::$_configs[$config_key] )
            ? self::$_configs[$config_key]['table_prefix']
            : '';
    }

    /**
     *
     *
     * @param $query
     * @param null $suffix
     * @return bool|\PDOStatement
     */
    public static function query($query, $suffix = NULL) {
        $state = FALSE;

        try {
            $state = DB::getConnection($suffix)->query($query);
        } catch (\PDOException $e) {

        }

        return $state;
    }

    //@todo: функция setContext($suffix) - которая устанавливает для всех дальнейших действий переданный суффикс значением по умолчанию

    /**
     *
     *
     * @param $table
     * @param null $suffix
     * @return mixed|null
     */
    public static function rowcount($table, $suffix = NULL) {
        if ($table == '')
            return null;

        return self::getConnection($suffix)->query("SELECT COUNT(*) AS cnt FROM {$table}")->fetchColumn();
    }

    /**
     * Аналог rowcound, только дает возможность выбрать поле выборки и условие
     *
     * @param $table
     * @param string $field
     * @param string $condition
     * @param null $suffix
     * @return mixed|null
     */
    public static function getRowCount($table, $field = '*', $condition = '', $suffix = NULL)
    {
        if ($table === '') return null;

        $where = ($condition !== '') ? " WHERE {$condition} " : '';
        $field = ($field !== '*') ? "`{$field}`" : "*";

        $query = "SELECT COUNT({$field}) AS rowcount FROM {$table} {$where}";

        return self::getConnection($suffix)->query($query)->fetchColumn();
    }

    /**
     * Хелпер
     *
     * @param null $suffix
     */
    public static function getLastInsertId($suffix = NULL)
    {
        self::getConnection($suffix)->lastInsertId();
    }


    /**
     * Билдер INSERT-выражения
     *
     * @param $tablename
     * @param $dataset
     * @return string
     */
    public static function makeInsertQuery($tablename, $dataset)
    {
        $query = '';
        $r = [];

        if (empty($dataset)) {
            $query = "INSERT INTO {$tablename} () VALUES (); ";
        } else {
            $query = "INSERT INTO `{$tablename}` SET ";

            foreach ($dataset as $index=>$value) {
                $r[] = "\r\n `{$index}` = :{$index}";
            }

            $query .= implode(', ', $r) . ' ;';
        }

        return $query;
    }

    /**
     * Билдер UPDATE-выражения
     *
     * @param $tablename
     * @param $dataset
     * @param string $where_condition
     * @return bool|string
     */
    public static function makeUpdateQuery($tablename, $dataset, $where_condition = '')
    {
        $query = '';
        $r = [];

        if (empty($dataset)) {
            return FALSE;
        } else {
            $query = "UPDATE `{$tablename}` SET";

            foreach ($dataset as $index=>$value) {
                $r[] = "\r\n`{$index}` = :{$index}";
            }

            $query .= implode(', ', $r);

            $query .= " \r\n" . $where_condition . " ;";
        }

        return $query;
    }


}
