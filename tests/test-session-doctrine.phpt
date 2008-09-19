--TEST--
HTTP_Session2 with phpDoctrine container (and sqlite) write and read
--SKIPIF--
<?php 
if (!extension_loaded('pdo')) {
    die('This test needs pdo, please make sure it\'s loaded.');
}
if (!extension_loaded('pdo_sqlite')) {
    die('This test needs pdo_sqlite, please make sure it\'s loaded.');
}
include_once 'Doctrine/lib/Doctrine.php';
if (!class_exists('Doctrine')) {
    die('This test needs phpDoctrine, please make sure it\'s installed.');
}
?>
--FILE--
<?php
$_tmp = dirname(__FILE__) . '/tmp';
$_db  = $_tmp . '/test-doctrine.db';

require_once 'HTTP/Session2.php';

/**
 * This is a hack.
 *
 * @param string $_db Path to the db.
 *
 * @return void
 */
function createDB($db)
{
    require_once 'Doctrine/lib/Doctrine.php';
    spl_autoload_register(array('Doctrine', 'autoload'));

    try {
        $db   = Doctrine_Manager::connection("sqlite:///$db");
        $path = '@include_path@/HTTP/Session2/Container/Doctrine';
        $path = '/usr/local/share/pear/HTTP/Session2/Container/Doctrine';
        $sql  = Doctrine::generateSqlFromModels($path);
        $db->execute($sql);
    } catch (Doctrine_Exception $e) {
        if (!strstr($e->getMessage(), 'already exists')) {
            die("createDB sql error: {$e->getMessage()} ({$e->getCode()})");
        }
    }
}

if (!file_exists($_tmp)) {
    mkdir($_tmp);
}
createDB($_db);

try {
    HTTP_Session2::useCookies(false);
    HTTP_Session2::setContainer('Doctrine',
        array('dsn' => "sqlite:///{$_db}",
            'table' => 'sessiondata'));

    HTTP_Session2::start('testSession');
    HTTP_Session2::id('sessionTest');

    $nCount = 0;
    while (++$nCount <= 2) {
        $_var = HTTP_Session2::get('test', 'bar');
        if ($_var == 'bar') {
            var_dump("Setting..");
            HTTP_Session2::set('test', 'foobar');
        } else {
            var_dump("Retrieving..");
            var_dump(HTTP_Session2::get('test'));
        }
    }
} catch (HTTP_Session2_Exception $e) {
    die($e->getMessage());
}
--CLEAN--
<?php
$_tmp = dirname(__FILE__) . '/tmp';
$_db  = $_tmp . '/test.db';
unlink($_db);
rmdir($_tmp);

require_once 'Doctrine/lib/Doctrine.php';
spl_autoload_register(array('Doctrine', 'autoload'));

$db = Doctrine_Manager::connection("sqlite:///$db");
var_dump(get_class_methods($db));
//$db->dropTable('session_data');

--EXPECT--
string(9) "Setting.."
string(12) "Retrieving.."
string(6) "foobar"

