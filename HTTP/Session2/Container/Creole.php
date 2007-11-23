<?php
//
// +-----------------------------------------------------------------------+
// | Copyright (c) 2004, Tony Bibbs                                        |
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
// | Author: Tony Bibbs <tony@geeklog.net                                  |
// +-----------------------------------------------------------------------+
//

/**
 * Abstract container class
 */
require_once 'HTTP/Session2/Container.php';

/**
 * Creole library
 */
require_once 'creole/Creole.php';

/**
 * PEAR::Exception
 */
require_once 'PEAR/Exception.php';

/**
 * Creole container for session data
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
 * @author  Tony Bibbs <tony@geeklog.net>
 * @package HTTP_Session2
 * @access  public
 */
class HTTP_Session2_Container_Creole extends HTTP_Session2_Container
{

    /**
     * Creole connection object
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
    	$this->options = $options;
        /*$this->setDefaults();
        if (is_array($options)) {
            $this->parseOptions($options);
        } else {
            $this->options['dsn'] = $options;
        }*/
    }

    /**
     * Connect to database by using the given DSN string
     *
     * @access private
     * @param  string DSN string or DSN Array (See http://creole.phpdb.org/wiki/index.php?node=5#A2)
     * 
     */
    private function connect($dsn)
    {    	
        if (is_string($dsn) OR is_array($dsn)) {
            $this->db = Creole::getConnection($dsn);
        } else {
        	throw new PEAR_Exception('Invalid DSN Given');
        }        
    }

    /**
     * Set some default options
     *
     * @access private
     */
    private function setDefaults()
    {
        $this->options['dsn']           = null;
        $this->options['table']         = 'sessiondata';
        $this->options['autooptimize']  = false;
    }

    /**
     * Establish connection to a database
     *
     */
    public function open($save_path, $session_name)
    {
    	$this->connect($this->options['dsn']);        
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
    	$sql = sprintf('SELECT data FROM %s WHERE id = ? AND expiry >= ?', $this->options['table']);
    	$prepStmt = $this->db->prepareStatement($sql);
    	$args = array(md5($id),time());
    	$rs = $prepStmt->executeQuery($args,ResultSet::FETCHMODE_NUM);
    	if ($rs->getRecordCount() == 0) {
    		return '';
    		throw new PEAR_Exception("No record in session table for id: $id");
    	}
    	$rs->next();
    	$result = $rs->getString(0);
        $this->crc = strlen($result) . crc32($result);
        return $result;
    }

    /**
     * Write session data
     *
     */
    public function write($id, $data)
    {
    	if ((false !== $this->crc) AND ($this->crc === strlen($data) . crc32($data))) {
			// $_SESSION hasn't been touched, no need to update the blob column
            $sql = sprintf('UPDATE %s SET expiry = ? WHERE id = ? AND expiry >= ?', 
            	$this->options['table']);
            $prepStmt = $this->db->prepareStatement($sql);
            $args = array(time() + ini_get('session.gc_maxlifetime'),
            				md5($id), time());
        } else {
        	// Check if table row already exists
            $sql = sprintf('SELECT COUNT(id) FROM %s WHERE id = ?', $this->options['table']);
            $prepStmt = $this->db->prepareStatement($sql);
            $args = array(md5($id));
            $rs = $prepStmt->executeQuery($args);
            $rs->next();
            $result = $rs->getInt(1);
            if (0 == intval($result)) {
            	print 'doing insert'; exit;
                // Insert new row into table
                $sql = sprintf('INSERT INTO %s (id, expiry, data) VALUES (?, ?, ?)', 
                	$this->options['table']);
                $prepStmt = $this->db->prepareStatement($sql);
                $args = array(md5($id), time() + ini_get('session.gc_maxlifetime'), $data); 
            } else {
            	print 'hows this possible'; exit;
            	// Update existing row
            	$sql = sprintf('UPDATE %s SET expiry = ?, data = ? WHERE id = ? AND expiry >= ?', 
            		$this->options['table']);
            	$prepStmt = $this->db->prepareStatement($sql);
                $args = array(time() + ini_get('session.gc_maxlifetime'), 
                	$data, md5(id), time()); 
            }
        }
        
        $result = $prepStmt->executeQuery($args);
        
        return true;
    }

    /**
     * Destroy session data
     *
     */
    public function destroy($id)
    {
    	$sql = sprintf('DELETE FROM %s WHERE id = ?', $this->options['table']);
    	$prepStmt = $this->db->prepareStatement($sql);
    	$args = array(md5($id));
    	$result = $prepStmt->executeQuery($args);
        return true;
    }

    /**
     * Garbage collection
     *
     */
    public function gc($maxlifetime)
    {
    	$sql = sprintf('DELETE FROM %s WHERE expiry < ?', $this->options['table']);
    	$prepStmt = $this->db->prepareStatement($sql);
    	$args = array(time());
        $result = $prepStmt->executeQuery($args);
        /*if ($this->options['autooptimize']) {
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
        }*/

        return true;
    }

}

?>