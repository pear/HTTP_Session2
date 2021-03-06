<?php
/**
 * @package HTTP_Session2
 * @author  Lukas Smith <lsmith@php.net>
 * @version SVN: $Id$
 */
if (!extension_loaded('pdo')) {
    die('This test needs pdo, please make sure it\'s loaded.');
}
if (!extension_loaded('pdo_sqlite')) {
    die('This test needs pdo_sqlite, please make sure it\'s loaded.');
}
@include_once 'Doctrine/lib/Doctrine.php';
if (!class_exists('Doctrine')) {
    die('This test needs phpDoctrine, please make sure it\'s installed.');
}

$_tmp = '/tmp';
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
$path = '/Applications/xampp/htdocs/phpcvs/pear/HTTP_SESSION2/HTTP/Session2/Container/Doctrine';
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
        array('dsn' => "sqlite:///{$_db}"));

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
} catch (Exception $e) {
    die($e->getMessage());
}

session_write_close();

$table = Doctrine::getTable('HTTP_Session2_Container_Doctrine_Record');
$sessionvalues = $table->findAll();
foreach ($sessionvalues as $value) {
    var_dump($value->toArray());
}

unlink($_db);
//rmdir($_tmp);

