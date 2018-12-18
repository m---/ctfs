<?php
/**
 * CTF Tiny web Framework
 *
 * @author  mage
 * @version 1.1.0
 */

class CTF
{
    /**
     * _app
     *
     * @var array
     */
    private $_app = [];

    /**
     * base path for render
     *
     * @var string
     */
    private $_base = '';

    /**
     * _param
     *
     * @var array
     */
    private $_param = [];

    /**
     * __construct
     *
     * @param string $base
     */
    public function __construct($base)
    {
        $this->_base = $base;
    }

    /**
     * __set
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->_param[$key] = $value;
    }

    /**
     * __get
     *
     * @param string $key
     * @return _param|null
     */
    public function __get($key)
    {
        return (isset($this->_param[$key]) === true) ? $this->_param[$key] : null;
    }

    /**
     * __call
     *
     * @param string $name
     * @param array $args
     * @return void
     */
    public function __call($name, $args)
    {
        $this->_app[$name][$args[0]] = $args[1];
    }

    /**
     * input
     *
     * @param INPUT_GET|INPUT_POST $type
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function input($type, $key, $default = null)
    {
        $input = filter_input($type, $key, FILTER_SANITIZE_SPECIAL_CHARS);
        if ($input === false || $input === null) {
            if ($default === null) {
                die(sprintf('Required "%s"', $key));
            }
            return $default;
        }

        return $input;
    }

    /**
     * redirect
     *
     * @param string $url
     * @return void
     */
    public function redirect($url)
    {
        header('Location:' . $url);
        exit();
    }

    /**
     * notfound
     *
     * @return void
     */
    public function notfound()
    {
        header('HTTP/1.0 404 Not Found');
        die("\xF0\x9F\x98\x87");
    }

    /**
     * render
     *
     * @param string $path
     * @return string $contents
     */
    public function render($path)
    {
        ob_start();
        include($this->_base . DIRECTORY_SEPARATOR . $path);
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }

    /**
     * _optimizeUri
     *
     * @param string $uri
     * @return string $path
     */
    private function _optimizeUri($uri)
    {
        $url = parse_url($uri);
        $path = preg_replace('/\/{2,}/', '/', $url['path']);
        $path = trim($path, '/');
        return $path;
    }

    /**
     * run
     *
     * @return void
     */
    public function run()
    {
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        $path = '/' . $this->_optimizeUri($_SERVER['REQUEST_URI']);

        // pre route
        if (isset($this->_app['*']['*']) === true) {
            $this->_app['*']['*']($path);
        }
        if (isset($this->_app[$method]['*']) === true) {
            $this->_app[$method]['*']($path);
        }

        // route
        if (isset($this->_app[$method][$path]) === true) {
            echo $this->_app[$method][$path]();
        } else {
            $this->notfound();
        }
    }
}

