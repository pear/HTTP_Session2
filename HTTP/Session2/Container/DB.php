<?php
//
// +-----------------------------------------------------------------------+
// | Copyright (c) 2002, Alexander Radivanovich                            |
// | All rights reserved.                                                  |
// |                                                                       |
// | Redistribution and use in source and binary forms, with or without    |
// | modification, are permitted provided that the following conditions    |
// | are met:                                                              |
// |                                                                       |
// | o Redistributions of source code must retain the above copyright      |
// |   notice, this list of conditions and the following disclaimer.       |
// | o Redistributions in binary form must reproduce the above copyright   |
// |   notice, this list of conditions and the following disclaimer in the |
// |   documentation and/or other materials provided with the distribution.|
// | o The names of the authors may not be used to endorse or promote      |
// |   products derived from this software without specific prior written  |
// |   permission.                                                         |
// |                                                                       |
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS   |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT     |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR |
// | A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT  |
// | OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, |
// | SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT      |
// | LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, |
// | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY |
// | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT   |
// | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE |
// | OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.  |
// |                                                                       |
// +-----------------------------------------------------------------------+
// | Author: Alexander Radivanovich <info@wwwlab.net>                      |
// +-----------------------------------------------------------------------+
//

require_once 'HTTP/Session2/Container.php';
require_once 'DB.php';
require_once 'PEAR/Exception.php';

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
 * @author  Alexander Radivanovich <info@wwwlab.net>
 * @package HTTP_Session
 * @access  public
 */
class HTTP_Session2_Container_DB extends HTTP_Session2_Container
{

    /**
     * DB connection object
     *
     * @var object DB
     * @access private
     */
    private $db = null;

    /**
     * Session data cache id
     *
     * @var mixed
     * @access private
     */
    private $crc = false;

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
     * @access public
     * @param  array  $options The options
     * @return void
     */
    public function __construct($options)
    {
        parent::__construct();
        
	$this->options['table'] = $options['table'];
	$this->options['dsn']   = sprintf(
            '%s://%s:%s@%s/%s',
            $options['dsn']['phptype'],
            $options['dsn']['username'],
            $options['dsn']['password'],
            $options['dsn']['hostspec'],
            $options['dsn']['database']
        );
    }

    /**
     * Connect to database by using the given DSN string
     *
     * @access private
     * @param  string DSN string
     * @return mixed  Object on error, otherwise bool
     */
    private function connect($dsn)
    {
        if (is_string($dsn)) {
            $this->db = DB::connect($dsn);
        } else if (is_object($dsn) && is_a($dsn, 'db_common')) {
            $this->db = $dsn;
        } else if (is_object($dsn) && DB::isError($dsn)) {
            return new DB_Error($dsn->code, PEAR_ERROR_DIE);
        } else {
            return new PEAR_Error(
                "The given dsn was not valid in file " . __FILE__ . " at line " . __LINE__,
                41,
                PEAR_ERROR_RETURN,
                null,
                null
            );
        }
        if (DB::isError($this->db)) {
            return new DB_Error($this->db->code, PEAR_ERROR_DIE);
        }
        return true;
    }

    /**
     * Set some default options
     *
     * @access private
     */
    private function setDefaults()
    {
        $this->options['dsn']          = null;
        $this->options['table']        = 'sessiondata';
        $this->options['autooptimize'] = false;
    }

    /**
     * Establish connection to a database
     *
     */
    public function open($save_path, $session_name)
    {
        if (DB::isError($this->connect($this->options['dsn']))) {
    	    return false;
        } else {
            return true;
        }
    }

    /**
     * Free resources
     *
     */
    public function close()
    {
        return true;
    }

    /**
     * Read session data
     *
     */
    public function read($id)
    {
        $query = sprintf("SELECT data FROM %s WHERE id = %s AND expiry >= %d",$this->options['table'],$this->db->quote(md5($id)),time());
        $result = $this->db->getOne($query);
        if (DB::isError($result)) {
            new DB_Error($result->code, PEAR_ERROR_DIE);
            return false;
        }
        $this->crc = strlen($result) . crc32($result);
        return $result;
    }

    /**
     * Write session data
     *
     */
    public function write($id, $data)
    {
        if ((false !== $this->crc) && ($this->crc === strlen($data) . crc32($data))) {
            // $_SESSION hasn't been touched, no need to update the blob column
            $query = sprintf("UPDATE %s SET expiry = %d WHERE id = %s AND expiry >= %d",
                             $this->options['table'],
                             time() + ini_get('session.gc_maxlifetime'),
                             $this->db->quote(md5($id)),
                             time()
                             );
        } else {
            // Check if table row already exists
            $query = sprintf("SELECT COUNT(id) FROM %s WHERE id = '%s'",
                             $this->options['table'],
                             md5($id)
                             );
            $result = $this->db->getOne($query);
            if (DB::isError($result)) {
                new DB_Error($result->code, PEAR_ERROR_DIE);
                return false;
            }
            if (0 == intval($result)) {
                // Insert new row into table
                $query = sprintf("INSERT INTO %s (id, expiry, data) VALUES (%s, %d, %s)",
                                 $this->options['table'],
                                 $this->db->quote(md5($id)),
                                 time() + ini_get('session.gc_maxlifetime'),
                                 $this->db->quote($data)
                                 );
            } else {
                // Update existing row
                $query = sprintf("UPDATE %s SET expiry = %d, data = %s WHERE id = %s AND expiry >= %d",
                                 $this->options['table'],
                                 time() + ini_get('session.gc_maxlifetime'),
                                 $this->db->quote($data),
                                 $this->db->quote(md5($id)),
                                 time()
                                 );
            }
        }
        $result = $this->db->query($query);
        if (DB::isError($result)) {
            new DB_Error($result->code, PEAR_ERROR_DIE);
            return false;
        }

        return true;
    }

    /**
     * Destroy session data
     *
     */
    public function destroy($id)
    {
        $query = sprintf("DELETE FROM %s WHERE id = %s",
                         $this->options['table'],
                         $this->db->quote(md5($id))
                         );
        $result = $this->db->query($query);
        if (DB::isError($result)) {
            new DB_Error($result->code, PEAR_ERROR_DIE);
            return false;
        }

        return true;
    }

    /**
     * Garbage collection
     *
     */
    public function gc($maxlifetime)
    {
        $query = sprintf("DELETE FROM %s WHERE expiry < %d",
                         $this->options['table'],
                         time()
                         );
        $result = $this->db->query($query);
        if (DB::isError($result)) {
            new DB_Error($result->code, PEAR_ERROR_DIE);
            return false;
        }
        if ($this->options['autooptimize']) {
            switch($this->db->type) {
                case 'mysql':
                    $query = sprintf("OPTIMIZE TABLE %s", $this->options['table']);
                    break;
                case 'pgsql':
                    $query = sprintf("VACUUM %s", $this->options['table']);
                    break;
                default:
                    $query = null;
                    break;
            }
            if (isset($query)) {
                $result = $this->db->query($query);
                if (DB::isError($result)) {
                    new DB_Error($result->code, PEAR_ERROR_DIE);
                    return false;
                }
            }
        }

        return true;
    }
}

