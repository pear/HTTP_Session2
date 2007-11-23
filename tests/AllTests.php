<?php
//
// +---------------------------------------------------------------------------+
// | PEAR :: HTTP :: Session2                                                  |
// +---------------------------------------------------------------------------+
// | Copyright (c) 2005 Tony Bibbs <tony@geeklog.net>                          |
// +---------------------------------------------------------------------------+
// | This source file is subject to version 3.00 of the PHP License,           |
// | that is available at http://www.php.net/license/3_0.txt.                  |
// | If you did not receive a copy of the PHP license and are unable to        |
// | obtain it through the world-wide-web, please send a note to               |
// | license@php.net so we can mail you a copy immediately.                    |
// +---------------------------------------------------------------------------+
//
// $Id$
//

if (!defined('PHPUnit2_MAIN_METHOD')) {
    define('PHPUnit2_MAIN_METHOD', 'HTTP_Session2_Tests_AllTests::main');
}

require_once 'PHPUnit2/Framework/TestSuite.php';
require_once 'PHPUnit2/TextUI/TestRunner.php';

require_once 'PHPUnit2/Tests/Session2Test.php';

/**
 * @author      Tony Bibbs <tony@geeklog.net>
 * @copyright   Copyright &copy; 2005 Tony Bibbs
 * @license     http://www.php.net/license/3_0.txt The PHP License, Version 3.0
 * @category    HTTP
 * @package     HTTP_Session2
 */
class HTTP_Session2_Tests_AllTests {
    public static function main() {
        PHPUnit2_TextUI_TestRunner::run(self::suite());
    }

    public static function suite() {
        $suite = new PHPUnit2_Framework_TestSuite('HTTP Session2');

        $suite->addTestSuite('HTTP_Session2_Tests_TransformerTest');

        return $suite;
    }
}

if (PHPUnit2_MAIN_METHOD == 'HTTP_Session2_Tests_AllTests::main') {
    HTTP_Session2_Tests_AllTests::main();
}

?>