<?php
/**
 * HTTP_Session2_Container_Doctrine
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
 * HTTP_Session2_Container
 * @ignore
 */
require_once 'HTTP/Session2/Container.php';

/**
 * HTTP_Session2_Exception
 */
require_once 'HTTP/Session2/Exception.php';

/**
 * Doctrine.php
 * @ignore
 */
require_once 'Doctrine/lib/Doctrine.php';

/**
 * register the autoloader
 */
spl_autoload_register(array('Doctrine', 'autoload'));

/**
 * HTTP_Session2_Container_Doctrine_Table
 */
if (!class_exists('HTTP_Session2_Container_Doctrine_Table')) {
    include_once 'HTTP/Session2/Container/Doctrine/Table.php';
}

/**
 * Database container for session data
 *
 * Create the following table to store session data
 * <code>
 * CREATE TABLE `sessiondata` (
 *     `id` CHAR(32) NOT NULL,
 *     `expiry` INT UNSIGNED NOT NULL DEFAULT 0,
 *     `data` TEXT NOT NULL,
 *     PRIMARY KEY (`id`)
 * );
 * </code>
 *
 * @category HTTP
 * @package  HTTP_Session2
 * @author   Till Klampaeckel <till@php.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version  Release: @package_version@
 * @link     http://pear.php.net/package/HTTP_Session2
 * @uses     HTTP_Session2_Container
 * @todo     Make all private, protected.
 */
class HTTP_Session2_Container_Doctrine extends HTTP_Session2_Container
{
    /**
     * Doctrine connection object.
     * 
     * XXX depends on the used database backend, for example Mysql, etc..
     *
     * @var mixed Doctrine_Connection_XXX
     */
    private $_db = null;
    
    /**
     * Doctrine_Record
     * 
     * @var Doctrine_Record An instance of the class where we defined the
     *                      session table preferences.
     */
    private $_table = null;

    /**
     * Session data cache id
     *
     * @var mixed
     */
    private $_crc = false;

    /**
     * Constrtuctor method
     *
     * $options is an array with the options.<br>
     * The options are:
     * <ul>
     * <li>'dsn' - The DSN string</li>
     * <li>'table' - Table with session data, default is 'sessiondata'</li>
     * <li>'autooptimize' - Boolean, 'true' to optimize
     * the table on garbage collection, default is 'false'.</li>
     * </ul>
     *
     * @param array $options The options
     *
     * @return object
     */
    public function __construct($options)
    {
        parent::__construct($options);
    }

    /**
     * Connect to database by using the given DSN string
     *
     * @param string $dsn DSN string.
     *
     * @return boolean
     * @throws HTTP_Session2_Exception An exception?!
     * @see    self::open()
     */
    protected function connect($dsn)
    {
        // pseudo singleton approach
        if (is_object($this->_db)) {
            return true;
        }
        if (is_string($dsn) || is_array($dsn)) {
            try {
                $this->_db = Doctrine_Manager::connection($dsn);
            } catch (Doctrine_Exception $e) {
                throw new HTTP_Session2_Exception($e->getMessage(),
                    $e->getCode(), $e);
            }
            return true;
        }
        $msg  = "The given dsn was not valid in file ";
        $msg .= __FILE__ . " at line " . __LINE__;
        throw new HTTP_Session2_Exception($msg,
            HTTP_Session2::ERR_SYSTEM_PRECONDITION);
    }

    /**
     * Set some default options
     *
     * @return void
     */
    protected function setDefaults()
    {
        $this->options['dsn']          = null;
        $this->options['table']        = 'sessiondata';
        $this->options['autooptimize'] = false;
    }

    /**
     * Establish connection to a database
     *
     * @param string $save_path    The path to save/write sessions.
     * @param string $session_name The session name.
     *
     * @return boolean
     * @uses   self::connect();
     * @uses   self::$options
     */
    public function open($save_path, $session_name)
    {
        return $this->connect($this->options['dsn']);
    }

    /**
     * Free resources
     *
     * @return boolean
     */
    public function close()
    {
        if (is_object($this->_db)) {
            $this->_db->close();
        }
        return true;
    }

    /**
     * Read session data
     *
     * @param string $id The ID of the the session in the table.
     *
     * @return mixed
     * @throws HTTP_Session2_Exception An exception!?
     * @todo   Get rid off sprintf()
     */
    public function read($id)
    {
        try {
            $expire = new Doctrine_Expression('NOW()');
            
            $tableName = $this->options['table'];
            $session   = $this->_db->getTable('HTTP_Session2_Container_Doctrine_Table');

            //throw new HTTP_Session2_Exception(var_export(get_class_methods($this->_db), true));
            //throw new HTTP_Session2_Exception(var_export($session, true));

            $result = $session->find($id);
            if ($result === false) {
                return $result;
            }
            if ($result->expiry != time()) {
                $result = null;
            }

            if ($result === null) {
                // how did this happen?
                $msg = 'Could not read session data (race-condition?)';
                throw new HTTP_Session2_Exception($msg,
                    HTTP_Session2::ERR_SYSTEM_PRECONDITION);
            }
            
            $this->_crc = $result->data . crc32($result->data);
            return $result->data;
        
        } catch (Doctrine_Exception $e) {
            throw new HTTP_Session2_Exception($e->getMessage(),
                $e->getCode(), $e);
        }
    }

    /**
     * Write session data
     *
     * @param string $id   The id.
     * @param string $data The data.
     *
     * @return boolean
     * @todo   Remove sprintf(), they are expensive.
     */
    public function write($id, $data)
    {
        try {
            $tableName = $this->options['table'];
            $table     = new HTTP_Session2_Container_Doctrine_Table($tableName);
            
            if ((false !== $this->_crc)
                && ($this->_crc === strlen($data) . crc32($data))) {
                /* $_SESSION hasn't been touched, no need to update the blob column */
                $data = $table->find(md5($id));
                $data->merge(
                    array(
                        'expiry' => time() + ini_get('session.gc_maxlifetime')
                    )
                );
                $data->save();
                return true;
            }
            
            /* Check if table row already exists */
            $result = $table->find(md5($id));
            if ($result === null) {
                /* Insert new row into table */
                $table->id     = md5($id);
                $table->expiry = time() + ini_get('session.gc_maxlifetime');
                $table->data   = $data;
                $table->save();

                return true;
            }

            $result->merge(
                array(
                    'expiry' => time() + ini_get('session.gc_maxlifetime'),
                    'data'   => $data
                )
            );
            $result->save();

        } catch (Doctrine_Exception $e) {
            throw new HTTP_Session2_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Destroy session data
     *
     * @param string $id The id.
     *
     * @return boolean
     * @throws HTTP_Session2_Exception An exception containing the Doctrine error.
     */
    public function destroy($id)
    {
        try {
            $table   = Doctrine::getTable('HTTP_Session2_Doctrine_Table');
            $session = $table->find(md5($id));
            $session->delete();
        } catch (Doctrine_Exception $e) {
            throw new HTTP_Session2_Exception ($e->getMessage(), $e->getCode(), $e);
        }
        return true;
    }

    /**
     * Garbage collection
     *
     * Currently supported are mysql, mysqli and pgsql.
     *
     * @param int $maxlifetime The session's maximum lifetime.
     *
     * @return boolean
     * @throws HTTP_Session2_Exception An exception that contains MDB2 data.
     * @todo   Implement 'autooptimize' once Doctrine supports it.
     * @todo   Optimize the Doctrine_Query stuff (I'm probably not skilled enough).
     */
    public function gc($maxlifetime)
    {
        try {

            $tableName = $this->options['table'];
            $table     = new HTTP_Session2_Container_Doctrine_Table($tableName);
            
            $query = new Doctrine_Query;
            $query->from($table);
            $query->where('expiry < ?', time());
            
            $result = $query->execute();
            $result->delete();

        } catch (Doctrine_Exception $e) {
            throw new HTTP_Session2_Exception($e->getMessage(), $e->getCode(), $e);
        }

        if ($this->options['autooptimize']) {
            $msg = 'phpDoctrine does not support this yet.';
            throw new HTTP_Session2_Exception($msg,
                HTTP_Session2::ERR_SYSTEM_PRECONDITION);
        }
        return true;
    }

    /**
     * Replicate session data to specified target
     *
     * @param string $target Target to replicate to
     * @param string $id     Id of record to replicate,
     *                       if not specified current session id will be used
     *
     * @return boolean
     */
    public function replicate($target, $id = null)
    {
        if ($id === null) {
            $id = HTTP_Session2::id();
        }

        try {
        
            $tableName = $this->options['table'];
            $source    = new HTTP_Session2_Container_Doctrine_Table($tableName);
            $replicate = new HTTP_Session2_Container_Doctrine_Table($target);
            
            // Check if table row already exists
            $session = $source->find(md5($id));
    
            // Insert new row into dest table
            if ($session === null) {
                
                $replicate->id     = $source->id;
                $replicate->data   = $source->data;
                $replicate->expiry = $source->expiry;
                $replicate->save();
    
                return true;
            }
            
            $session->merge(
                array(
                    'data'   => $source->data,
                    'expiry' => $source->expiry
                )
            );
            $session->save();
        
        } catch (Doctrine_Exception $e) {
            throw HTTP_Session2_Exception($e->getMessage(), $e->getCode(), $e);
        }

        return true;
    }
}
