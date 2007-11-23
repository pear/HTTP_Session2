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
// | Author: Tony Bibbs <tony@geeklog.net>                                 |
// +-----------------------------------------------------------------------+
//


/**
 * PEAR::Exception
 */
require_once 'PEAR/Exception.php';

/** @const HTTP_SESSION2_STARTED - The session was started with the current request */
define('HTTP_SESSION2_STARTED', 1);
/** @const HTTP_SESSION2_STARTED - No new session was started with the current request */
define('HTTP_SESSION2_CONTINUED', 2);

/**
 * Class for managing HTTP sessions
 *
 * Provides access to session-state values as well as session-level
 * settings and lifetime management methods.
 * Based on the standart PHP session handling mechanism
 * it provides for you more advanced features such as
 * database container, idle and expire timeouts, etc.
 *
 * Expample 1:
 *
 * <code>
 * // Setting some options and detecting of a new session
 * HTTP_Session::setCookieless(false);
 * HTTP_Session::start('MySessionID');
 * HTTP_Session::set('variable', 'Tet string');
 * if (HTTP_Session::isNew()) {
 *     echo('new session was created with the current request');
 *     $visitors++; // Increase visitors count
 * }
 * </code>
 *
 * Example 2:
 *
 * <code>
 * // Using database container
 * HTTP_Session::setContainer('DB');
 * HTTP_Session::start();
 * </code>
 *
 * Example 3:
 *
 * <code>
 * // Setting timeouts
 * HTTP_Session::start();
 * HTTP_Session::setExpire(time() + 60 * 60); // expires in one hour
 * HTTP_Session::setIdle(10 * 60);            // idles in ten minutes
 * if (HTTP_Session::isExpired()) {
 *     // expired
 *     echo('Your session is expired!');
 *     HTTP_Session::destroy();
 * }
 * if (HTTP_Session::isIdle()) {
 *     // idle
 *     echo('You've been idle for too long!');
 *     HTTP_Session::destroy();
 * }
 * HTTP_Session::updateIdle();
 * </code>
 *
 * @author  Alexander Radivaniovich <info@wwwlab.net>
 * @author Tony Bibbs <tony@geeklog.net>
 * @package HTTP_Session2
 * @access  public
 */
class HTTP_Session2 {

    /**
     * Sets user-defined session storage functions
     *
     * Sets the user-defined session storage functions which are used
     * for storing and retrieving data associated with a session.
     * This is most useful when a storage method other than
     * those supplied by PHP sessions is preferred.
     * i.e. Storing the session data in a local database.
     *
     * @static
     * @access public
     * @return void
     * @see    session_set_save_handler()
     */
    public function setContainer($container, $container_options = null)
    {
        $container_class = 'HTTP_Session2_Container_' . $container;
        $container_classfile = 'HTTP/Session2/Container/' . $container . '.php';

        include_once $container_classfile;
        if (!class_exists($container_class)) {
        	throw new PEAR_Exception("Container class, $container_class, does not exist");
        }
        $container = new $container_class($container_options);

        $container->set();
    }

    /**
     * Initializes session data
     *
     * Creates a session (or resumes the current one
     * based on the session id being passed
     * via a GET variable or a cookie).
     * You can provide your own name and/or id for a session.
     *
     * @static
     * @access public
     * @param  $name  string Name of a session, default is 'SessionID'
     * @param  $id    string Id of a session which will be used
     *                       only when the session is new
     * @return void
     * @see    session_name()
     * @see    session_id()
     * @see    session_start()
     */
    public function start($name = 'SessionID', $id = null)
    {
    	self::name($name);
        if (is_null(self::detectID())) {
        	if ($id) {
        		self::id($id);
        	} else {
        		self::id(uniqid(dechex(rand())));
        	}
        }
        session_start();
        if (!isset($_SESSION['__HTTP_Session2_Info'])) {
            $_SESSION['__HTTP_Session2_Info'] = HTTP_SESSION2_STARTED;
        } else {
            $_SESSION['__HTTP_Session2_Info'] = HTTP_SESSION2_CONTINUED;
        }
    }

    /**
     * Writes session data and ends session
     *
     * Session data is usually stored after your script
     * terminated without the need to call HTTP_Session::stop(),
     * but as session data is locked to prevent concurrent
     * writes only one script may operate on a session at any time.
     * When using framesets together with sessions you will
     * experience the frames loading one by one due to this
     * locking. You can reduce the time needed to load all the
     * frames by ending the session as soon as all changes
     * to session variables are done.
     *
     * @static
     * @access public
     * @return void
     * @see    session_write_close()
     */
    public static function pause()
    {
        session_write_close();
    }

    /**
     * Frees all session variables and destroys all data
     * registered to a session
     *
     * This method resets the $_SESSION variable and
     * destroys all of the data associated
     * with the current session in its storage (file or DB).
     * It forces new session to be started after this method
     * is called. It does not unset the session cookie.
     *
     * @static
     * @access public
     * @return void
     * @see    session_unset()
     * @see    session_destroy()
     */
    public static function destroy()
    {
        session_unset();
        session_destroy();
    }

    /**
     * Free all session variables
     *
     * @todo   TODO Save expire and idle timestamps?
     * @static
     * @access public
     * @return void
     */
    public static function clear()
    {
		$info = $_SESSION['__HTTP_Session2_Info'];
        session_unset();
		$_SESSION['__HTTP_Session2_Info'] = $info;
    }

    /**
     * Tries to find any session id in $_GET, $_POST or $_COOKIE
     *
     * @static
     * @access private
     * @return string Session ID (if exists) or null
     */
    public static function detectID()
    {
        if (self::useCookies()) {
            if (isset($_COOKIE[self::name()])) {
                return $_COOKIE[self::name()];
            }
        } else {
            if (isset($_GET[self::name()])) {
                return $_GET[self::name()];
            }
            if (isset($_POST[self::name()])) {
                return $_POST[self::name()];
            }
        }
        return null;
    }

    /**
     * Sets new name of a session
     *
     * @static
     * @access public
     * @param  string $name New name of a sesion
     * @return string Previous name of a session
     * @see    session_name()
     */
    public static function name($name = null)
    {
    	if (isset($name)) {
    		return session_name($name);
    	} 
    	
    	return session_name();
    }

    /**
     * Sets new ID of a session
     *
     * @static
     * @access public
     * @param  string $id New ID of a sesion
     * @return string Previous ID of a session
     * @see    session_id()
     */
    public static function id($id = null)
    {
    	if (isset($id)) {
    		return session_id($id);
    	}
    	
    	return session_id();
    }

    /**
     * Sets the maximum expire time
     *
     * @static
     * @access public
     * @param  integer $time Time in seconds
     * @param  bool    $add  Add time to current expire time or not
     * @return void
     */
    public static function setExpire($time, $add = false)
    {
        if ($add) {
            $GLOBALS['__HTTP_Session2_Expire'] += $time;
        } else {
            $GLOBALS['__HTTP_Session2_Expire'] = $time;
        }
        if (!isset($_SESSION['__HTTP_Session2_Expire_TS'])) {
            $_SESSION['__HTTP_Session2_Expire_TS'] = time();
        }
    }

    /**
     * Sets the maximum idle time
     *
     * Sets the time-out period allowed
     * between requests before the session-state
     * provider terminates the session.
     *
     * @static
     * @access public
     * @param  integer $time Time in seconds
     * @param  bool    $add  Add time to current maximum idle time or not
     * @return void
     */
    public static function setIdle($time, $add = false)
    {
        if ($add) {
            if (isset($_SESSION['__HTTP_Session2_Idle'])) {
                $_SESSION['__HTTP_Session2_Idle'] += $time;
            } else {
                $_SESSION['__HTTP_Session2_Idle'] = $time;
            }
        } else {
            $GLOBALS['__HTTP_Session2_Idle'] = $time;
        }
        if (!isset($_SESSION['__HTTP_Session2_Idle_TS'])) {
            $_SESSION['__HTTP_Session2_Idle_TS'] = time();
        }
    }

    /**
     * Returns the time up to the session is valid
     *
     * @static
     * @access public
     * @return integer Time when the session idles
     */
    public static function sessionValidThru()
    {
        if (!isset($_SESSION['__HTTP_Session2_Idle_TS']) || !isset($GLOBALS['__HTTP_Session2_Idle'])) {
            return 0;
        } else {
            return $_SESSION['__HTTP_Session2_Idle_TS'] + $GLOBALS['__HTTP_Session2_Idle'];
        }
    }

    /**
     * Check if session is expired
     *
     * @static
     * @access public
     * @return boolean Obvious
     */
    public static function isExpired()
    {
        if ($GLOBALS['__HTTP_Session2_Expire'] > 0 && isset($_SESSION['__HTTP_Session2_Expire_TS']) &&
            ($_SESSION['__HTTP_Session2_Expire_TS'] + $GLOBALS['__HTTP_Session2_Expire']) <= time()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if session is idle
     *
     * @static
     * @access public
     * @return boolean Obvious
     */
    public static function isIdle()
    {
        if (
            isset($GLOBALS['__HTTP_Session2_Idle'])
            && $GLOBALS['__HTTP_Session2_Idle'] > 0
            && isset($_SESSION['__HTTP_Session2_Idle_TS'])
            && (
                $_SESSION['__HTTP_Session2_Idle_TS']
                + $GLOBALS['__HTTP_Session2_Idle']
            ) <= time()) {
            return true;
        }
        return false;
    }

    /**
     * Updates the idletime
     *
     * @static
     * @access public
     * @return void
     */
    public static function updateIdle()
    {
        if (isset($_SESSION['__HTTP_Session2_Idle_TS'])) {
            $_SESSION['__HTTP_Session2_Idle_TS'] = time();
        }
    }

    /**
     * If optional parameter is specified it indicates
     * whether the module will use cookies to store
     * the session id on the client side
     *
     * It returns the previous value of this property
     *
     * @static
     * @access public
     * @param  boolean $useCookies If specified it will replace the previous value
     *                             of this property
     * @return boolean The previous value of the property
     */
    public static function useCookies($useCookies = null)
    {
    	if (ini_get('session.use_cookies')) {
    		$return = true;
    	} else {
    		$return = false;
    	}
        if (isset($useCookies)) {
        	if ($useCookies) {
        		ini_set('session.use_cookies',1);
        	} else {
        		ini_set('session.use_cookies',0);
        	}
        }
        return $return;
    }

    /**
     * Gets a value indicating whether the session
     * was created with the current request
     *
     * You MUST call this method only after you have started
     * the session with the HTTP_Session::start() method.
     *
     * @static
     * @access public
     * @return boolean true if the session was created
     *                 with the current request, false otherwise
     */
    public static function isNew()
    {
        // The best way to check if a session is new is to check
        // for existence of a session data storage
        // with the current session id, but this is impossible
        // with the default PHP module wich is 'files'.
        // So we need to emulate it.
        return !isset($_SESSION['__HTTP_Session2_Info']) ||
            $_SESSION['__HTTP_Session2_Info'] == HTTP_SESSION2_STARTED;
    }

    /**
     * Register variable with the current session
     *
     * @static
     * @access public
     * @param  string $name Name of a global variable
     * @return void
     * @see    session_register()
     */
    public static function register($name)
    {
        session_register($name);
    }

    /**
     * Unregister a variable from the current session
     *
     * @static
     * @access public
     * @param  string $name Name of a global variable
     * @return void
     * @see    session_unregister()
     */
    public static function unregister($name)
    {
        session_unregister($name);
    }

    /**
     * Returns session variable
     *
     * @static
     * @access public
     * @param  string $name    Name of a variable
     * @param  mixed  $default Default value of a variable if not set
     * @return mixed  Value of a variable
     */
    public static function &get($name, $default = null)
    {
        if (!isset($_SESSION[$name]) && isset($default)) {
            $_SESSION[$name] = $default;
        }
        return $_SESSION[$name];
    }

    /**
     * Sets session variable
     *
     * @access public
     * @param  string $name  Name of a variable
     * @param  mixed  $value Value of a variable
     * @return mixed  Old value of a variable
     */
    public function set($name, $value)
    {
        $return = @$_SESSION[$name];
        if (null === $value) {
            unset($_SESSION[$name]);
        } else {
            $_SESSION[$name] = $value;
        }
        return $return;
    }

    /**
     * Returns local variable of a script
     *
     * Two scripts can have local variables with the same names
     *
     * @static
     * @access public
     * @param  string $name    Name of a variable
     * @param  mixed  $default Default value of a variable if not set
     * @return mixed  Value of a local variable
     */
    public static function &getLocal($name, $default = null)
    {
        $local = md5(self::localName());
        if (!is_array($_SESSION[$local])) {
            $_SESSION[$local] = array();
        }
        if (!isset($_SESSION[$local][$name]) && isset($default)) {
            $_SESSION[$local][$name] = $default;
        }
        return $_SESSION[$local][$name];
    }

    /**
     * Sets local variable of a script.
     * Two scripts can have local variables with the same names.
     *
     * @static
     * @access public
     * @param  string $name  Name of a local variable
     * @param  mixed  $value Value of a local variable
     * @return mixed  Old value of a local variable
     */
    public static function setLocal($name, $value)
    {
        $local = md5(self::localName());
        if (!is_array($_SESSION[$local])) {
            $_SESSION[$local] = array();
        }
        $return = $_SESSION[$local][$name];
        if (null === $value) {
            unset($_SESSION[$local][$name]);
        } else {
            $_SESSION[$local][$name] = $value;
        }
        return $return;
    }

    /**
     * set the usage of transparent SID
     *
     * @param boolean $useTransSID
     * @return boolean
     */
    public static function useTransSID($useTransSID = false)
    {
        $return = ini_get('session.use_trans_sid') ? true : false;
        if ($useTransSID === false) {
            ini_set('session.use_trans_sid', $useTransSID ? 1 : 0);
        }
        return $return;
    }

    /**
     * Sets new local name
     *
     * @static
     * @access public
     * @param  string New local name
     * @return string Previous local name
     */
    public static function localName($name = null)
    {
        $return = @$GLOBALS['__HTTP_Session2_Localname'];
        if (!empty($name)) {
            $GLOBALS['__HTTP_Session2_Localname'] = $name;
        }
        return $return;
    }

    /**
     *
     * @static
     * @access private
     * @return void
     */
    public static function init()
    {
        // Disable auto-start of a sesion
        ini_set('session.auto_start', 0);

        // Set local name equal to the current script name
        self::localName($_SERVER['SCRIPT_NAME']);
    }
}

HTTP_Session2::init();

?>
