<?php
/**
 * HTTP_Session2_Container_Doctrine_Table
 *
 * PHP Version 5
 *
 * @category HTTP
 * @package  HTTP_Session2
 * @author   Till Klampaeckel <till@php.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version  CVS: $Id$
 * @link     http://pear.php.net/package/HTTP_Session2
 */

/**
 * An interface to Doctrine_Table for HTTP_Session2.
 *
 * @category HTTP
 * @package  HTTP_Session2
 * @author   Till Klampaeckel <till@php.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version  Release: @package_version@
 * @link     http://pear.php.net/package/HTTP_Session2
 * @uses     Doctrine_Record
 */
class HTTP_Session2_Container_Doctrine_Table extends Doctrine_Record
{
    protected $tillTable;

    /**
     * __construct
     * 
     * @param string $tableName The name of the table
     * 
     * @return HTTP_Session2_Container_Doctrine_Table
     * @uses   parent::__construct()
     */
    public function __construct($tableName = 'sessiondata')
    {
        $this->tillTable = $tableName;
        parent::__construct($tableName);
    }

    public function setTableDefinition()
    {
        $this->setTableName('sessiondata');

        $this->hasColumn('id', 'string', 32, array('primary' => true));
        $this->hasColumn('expire', 'integer');
        $this->hasColumn('data', 'text');
    }
}
