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

require_once 'PHPUnit2/Framework/TestCase.php';
require_once 'HTTP/Session2.php';

/**
 * @author      Tony Bibbs <tony@geeklog.net>
 * @copyright   Copyright &copy; 2005 Tony Bibbs <tony@geeklog.net>
 * @license     http://www.php.net/license/3_0.txt The PHP License, Version 3.0
 * @category    HTTP
 * @package     HTTP_Session2
 */
class HTTP_Session2_Tests_Session2Test extends PHPUnit2_Framework_TestCase {
    public function  setUp() 
    {
    	$this->dsn = array('phptype' => 'mysql',
             'hostspec' => 'localhost',
             'username' => 'root',
             'password' => '',
             'database' => 'Geeklog_2');
             
        $this->table = 'gl2_session';
             
    	HTTP_Session2::setContainer('Creole', array('dsn'=>$this->dsn,'table'=>$this->table));
    }

    /*public function testSessionStart() {
    	HTTP_Session2::start('HTTP_SESSION2_TEST_NAME', '1111111111');
        //$container = $GLOBALS['HTTP_Session2_Container'];
        $conn = Creole::getConnection($this->dsn);
        $rs = $conn->executeQuery('SELECT count(*) FROM ' . $this->table . " WHERE id = '1111111111'");
        $rs->next();
        $this->assertEquals(
        	$rs->getString('theCount'),
        	'1111111111');
    }*/
    
    public function testSessionSet() {
    	HTTP_Session2::start('HTTP_SESSION2_TEST_NAME', '1111111111');
    	HTTP_Session2::set('fname','Tony');
    	$this->assertEquals(
    		$_SESSION['fname'],
    		'Tony');
    }

/*    public function testRecursion() {
        $this->s->overloadNamespace(
          '&MAIN',
          new TestNamespace
        );

        $this->assertEquals(
          '<p><b>text</b></p>',

          $this->s->transform(
            '<p><boldbold>text</boldbold></p>'
          )
        );
    }

    public function testSelfReplacing() {
        $this->s->overloadNamespace(
          '&MAIN',
          new TestNamespace
        );

        $this->assertEquals(
          '<html><body>text</body></html>',

          $this->s->transform(
            '<html><body/></html>'
          )
        );
    }

    public function testNamespace() {
        $this->s->overloadNamespace(
          'test',
          new TestNamespace
        );

        $this->assertEquals(
          '<p><b>text</b></p>',

          $this->s->transform(
            '<p><test:bold>text</test:bold></p>'
          )
        );
    }

    public function testNamespaceURI() {
        $this->s->overloadNamespace(
          'test',
          new TestNamespace
        );

        $this->assertEquals(
          '<p><b>text</b></p>',

          $this->s->transform(
            '<p><test:bold>text</test:bold></p>'
          )
        );
    }*/
}

?>
