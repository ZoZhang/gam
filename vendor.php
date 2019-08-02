<?php
/**
 * Projet Goask Me
 *
 * @author ZHANG Zhao <zo.zhang@gmail.com>
 * @demo http://gam.zhaozhang.fr
 */

//Initialiser les errors logs configs
ini_set('error_reporting', '-1');
ini_set('display_errors', 'on');

//Initialiser les repertoire
define('DEBUG', true);
define('DS', DIRECTORY_SEPARATOR);
define('PS', PATH_SEPARATOR);
define('CP', dirname(__FILE__));
define('ROOT_PATH', realpath(CP));
define('VAR_PATH', ROOT_PATH . DS . 'var');
define('LOG_PATH', VAR_PATH . DS . 'log');
define('SESSION_PATH', VAR_PATH . DS . 'sessions');
define('TEMPLATE_PATH', ROOT_PATH . DS . 'views' . DS . 'template');

//Create folder
foreach ([VAR_PATH, LOG_PATH, SESSION_PATH] as $path) {
    if (is_dir($path)) {
        continue;
    }

    if (!mkdir($path) || !is_writeable($path)) {
        die('Please check if the '. VAR_PATH .' directory has write permission !');
    }
}

session_save_path(SESSION_PATH);

$custom_directory = [
    'helper',
    'model',
    'controller',
    'views' . DS . 'template',
];

$custom_directory['vendor'] = array_diff(scandir(ROOT_PATH . DS . 'vendor'), array('..','.'));

foreach($custom_directory as $name => & $path) {

    if (!is_array($path)) {
        $path  = ROOT_PATH . DS . $path;
    } else {

        foreach($path as $subpath) {
            $custom_directory[] = $name . DS . $subpath;
        }

        $path = ROOT_PATH . DS . $name;
    }
}

$custom_directory = implode(':', $custom_directory);

$include_path_dirs = implode(':', [
    ROOT_PATH,
    $custom_directory,
    get_include_path()
]);

//Initializer les repertoir include
set_include_path($include_path_dirs);

//Autoload class file.
spl_autoload_register(function($class){

    $classNames = preg_match('/\\\(.*)/i', $class, $matchClass);

    if (!$matchClass || !isset($matchClass['1'])) {
        print('Please check if the class '. $class);
    } else {

        $classFile = str_replace('\\','/', lcfirst($matchClass['1']) . '.php');

        include_once $classFile;
    }
});

