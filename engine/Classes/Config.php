<?php
/**
 * User: Karel Wintersky
 * Date: 08.08.2018, time: 21:25
 */

/**
 * Простейший Config-класс для упрощения доступа к конфигу.
 * Базируется на ArrisFramework/App class
 *
 * Предполагает инициализацию вида
 *
 * Config::init( include  'config/config.php' );
 *
 * Class Config
 */
class Config {
    const VERSION = '1.0';

    const GLUE = '/';
    private static $config = [];


    public static function init_once($data)
    {
        self::$config = $data;
    }

    public static function init($configs_set)
    {
        if (is_array($configs_set)) {
            foreach ($configs_set as $config_key => $config_file) {
                $config_subpath = (is_int($config_key) || ($config_key == '/') || ($config_key == '')) ? '' : $config_key;

                self::config_append( $config_file , $config_subpath );
            }
        } elseif (is_string($configs_set)) {
            self::config_append( $configs_set );
        }

    }

    public static function get($parents, $default_value = null)
    {
        if ($parents === '') {
            return $default_value;
        }

        if (!is_array($parents)) {
            $parents = explode(self::GLUE, $parents);
        }

        $ref = &self::$config;

        foreach ((array) $parents as $parent) {
            if (is_array($ref) && array_key_exists($parent, $ref)) {
                $ref = &$ref[$parent];
            } else {
                return $default_value;
            }
        }
        return $ref;

    }

    public static function set($parents, $value)
    {
        if (!is_array($parents)) {
            $parents = explode(self::GLUE, (string) $parents);
        }

        if (empty($parents)) return false;

        $ref = &self::$config;

        foreach ($parents as $parent) {
            if (isset($ref) && !is_array($ref)) {
                $ref = array();
            }

            $ref = &$ref[$parent];
        }

        $ref = $value;
        return true;
    }

    public static function d()
    {
        echo '<pre>';
        print_r(self::$config);
    }

    public static function dd()
    {
        self::d();
        die;
    }



    /**
     *
     */

    public static function config_append($file, $subpath = '')
    {
        $new_config = include $file;

        if ($subpath == "" || $subpath == self::GLUE) {

            foreach ($new_config as $key => $part) {

                if (array_key_exists($key, self::$config)) {
                    self::$config[$key] = array_merge(self::$config[$key], $part);
                } else {
                    self::$config[$key] = $part;
                }
            }

        } else {
            self::$config["{$subpath}"] = $new_config;
        }

        unset($new_config);
    }

}
