<?php
/**
 * HTTP_Session2_Container_Doctrine_Record
 *
 * PHP Version 5
 *
 * @category HTTP
 * @package  HTTP_Session2
 * @author   Till Klampaeckel <till@php.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version  SVN: $Id$
 * @link     http://pear.php.net/package/HTTP_Session2
 */

/**
 * An interface to Doctrine_Record for HTTP_Session2.
 *
 * @category HTTP
 * @package  HTTP_Session2
 * @author   Till Klampaeckel <till@php.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version  Release: @package_version@
 * @link     http://pear.php.net/package/HTTP_Session2
 * @uses     Doctrine_Record
 * @todo     Fix variable naming. ;-)
 */
class HTTP_Session2_Container_Doctrine_Record extends Doctrine_Record
{
    /**
     * Set table definition.
     *
     * @uses parent::setTableName()
     * @uses parent::hasColumn()
     *
     * @return void
     */
    public function setTableDefinition()
    {
        $this->setTableName('sessiondata');

        $this->hasColumn('id', 'string', 32, array('primary' => true));
        $this->hasColumn('expiry', 'integer');
        $this->hasColumn('data', 'text');
    }
}

