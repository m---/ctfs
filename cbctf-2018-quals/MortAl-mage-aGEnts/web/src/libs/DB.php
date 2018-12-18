<?php
/**
 * DB
 * 
 * PHP version 5.3.3-
 */

class DB
{
    /**
     * link 
     * 
     * @var resource
     */
    public $link = null;

    /**
     * _host
     *
     * @var string
     */
    protected $_host = '';

    /**
     * _username
     *
     * @var string
     */
    protected $_username = '';

    /**
     * _passwd
     *
     * @var string
     */
    protected $_passwd = '';

    /**
     * _dbname
     *
     * @var string
     */
    protected $_dbname = '';

    /**
     * _timeout
     *
     * @var int
     */
    protected $_timeout = 0;

    /**
     * __construct
     * 
     * @return void
     */
    public function __construct($host, $username, $passwd, $dbname, $timeout = 0)
    {
        $this->_host = $host;
        $this->_username = $username;
        $this->_passwd = $passwd;
        $this->_dbname = $dbname;
        $this->_timeout = $timeout;
        $this->link = $this->connect();
    }

    /**
     * connect
     *
     * @return resource
     */
    public function connect()
    {
        return mysqli_connect('p:' . $this->_host, $this->_username, $this->_passwd, $this->_dbname);
    }

    /**
     * fetch
     * 
     * @param string $sql 
     * @param array $parma 
     * @return array|false
     */
    public function fetch($sql, $param = array())
    {
        $result = $this->query($sql, $param);
        if ($result === false) {
            return false;
        }

        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_free_result($result);
        return ($row !== null) ? $row : false;
    }

    /**
     * fetchAll
     * 
     * @param string $sql 
     * @param array $param 
     * @return array
     */
    public function fetchAll($sql, $param = array())
    {
        $result = $this->query($sql, $param);
        if ($result === false) {
            return false;
        }

        $rows = array();
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $rows[] = $row;
        }
		mysqli_free_result($result);
        return $rows;
    }

    /**
     * query
     * 
     * @param mixed $sql 
     * @param mixed $param 
     * @return mysqli_result
     */
    public function query($sql, $param = array())
    {
        $search = [];
        $replace = [];
        foreach ($param as $key => $value) {
            $search[] = $key;
            $replace[] = sprintf("'%s'", mysqli_real_escape_string($this->link, $value));
        }
        $sql = str_replace($search, $replace, $sql);

        if ($this->_timeout === 0) {
            $result = mysqli_query($this->link, $sql);
        } else {
            mysqli_query($this->link, $sql, MYSQLI_ASYNC);
            $links = $errors = $rejects = array($this->link);
            if (mysqli_poll($links, $errors, $rejects, $this->_timeout) > 0) {
                $result = mysqli_reap_async_query($this->link);
            } else {
                $kill = $this->connect();
                mysqli_query($kill, 'KILL QUERY ' . mysqli_thread_id($this->link));
                mysqli_close($kill);
                $this->link = $this->connect();
                $result = false;
            }
        }
        return $result;
    }

    /**
     * lastInsertId
     * 
     * @return int 
     */
    public function lastInsertId()
    {
        return mysqli_insert_id($this->link);
    }
}

