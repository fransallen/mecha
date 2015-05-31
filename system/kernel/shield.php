<?php

class Shield extends Plugger {

    protected static $lot = array();

    /**
     * Do Nothing
     * ----------
     */

    protected static function s_o_d($buffer) {
        $buffer = Filter::apply('sanitize:input', $buffer);
        return Filter::apply('sanitize:output', $buffer);
    }

    /**
     * Minify HTML Output
     * ------------------
     */

    protected static function s_o($buffer) {
        $buffer = Filter::apply('sanitize:input', $buffer);
        return Filter::apply('sanitize:output', Converter::detractSkeleton($buffer));
    }

    /**
     * Default Shortcut Variables
     * --------------------------
     */

    protected static function cargo() {
        $config = Config::get();
        $token = Guardian::token();
        $message = Notify::read();
        $results = array(
            'config' => $config,
            'speak' => $config->speak,
            'articles' => $config->articles,
            'article' => $config->article,
            'pages' => $config->pages,
            'page' => $config->page,
            'responses' => $config->responses,
            'response' => $config->response,
            'files' => $config->files,
            'file' => $config->file,
            'pager' => $config->pagination,
            'manager' => Guardian::happy(),
            'token' => $token,
            'messages' => $message
        );
        Session::set(Guardian::$token, $token);
        Session::set(Notify::$message, $message);
        return array_merge($results, self::$lot);
    }

    /**
     * ==========================================================
     *  GET SHIELD PATH BY ITS NAME
     * ==========================================================
     *
     * -- CODE: -------------------------------------------------
     *
     *    echo Shield::path('article');
     *
     * ----------------------------------------------------------
     *
     */

    public static function path($name) {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $name = File::path($name) . '.' . ($extension === "" ? 'php' : $extension);
        if($path = File::exist(SHIELD . DS . Config::get('shield') . DS . ltrim($name, DS))) {
            return $path;
        } else if($path = File::exist(ROOT . DS . ltrim($name, DS))) {
            return $path;
        }
        return $name;
    }

    /**
     * ==========================================================
     *  DEFINE NEW SHORTCUT VARIABLE(S)
     * ==========================================================
     *
     * -- CODE: -------------------------------------------------
     *
     *    Shield::lot('foo', 'bar')->attach('file');
     *
     * ----------------------------------------------------------
     *
     *    Shield::lot(array(
     *        'foo' => 'bar',
     *        'baz' => 'qux'
     *    ))->attach('page');
     *
     * ----------------------------------------------------------
     *
     */

    public static function lot($key, $value = "") {
        if(is_array($key)) {
            self::$lot = array_merge(self::$lot, $key);
        } else {
            self::$lot[$key] = $value;
        }
        return new static;
    }

    /**
     * ==========================================================
     *  UNDEFINE SHORTCUT VARIABLE(S)
     * ==========================================================
     *
     * -- CODE: -------------------------------------------------
     *
     *    Shield::lot($data)->apart('foo')->attach('page');
     *
     * ----------------------------------------------------------
     *
     *    Shield::lot($data)
     *          ->apart(array('foo', 'bar'))
     *          ->attach('page');
     *
     * ----------------------------------------------------------
     *
     */

    public static function apart($data) {
        if( ! is_array($data)) $data = array($data);
        foreach($data as $d) {
            unset(self::$lot[$d]);
        }
        return new static;
    }

    /**
     * ==========================================================
     *  GET SHIELD INFO
     * ==========================================================
     *
     * -- CODE: -------------------------------------------------
     *
     *    var_dump(Shield::info('aero'));
     *
     * ----------------------------------------------------------
     *
     */

    public static function info($folder = null) {
        $config = Config::get();
        $speak = Config::speak();
        if(is_null($folder)) {
            $folder = $config->shield;
        }
        // Check whether the localized "about" file is available
        if( ! $info = File::exist(SHIELD . DS . $folder . DS . 'about.' . $config->language . '.txt')) {
            $info = SHIELD . DS . $folder . DS . 'about.txt';
        }
        $page_default = 'Title' . S . ' ' . ucwords(Text::parse($folder, '->text')) . "\n" .
            'Author' . S . ' ' . $speak->anon . "\n" .
            'URL' . S . ' #' . "\n" .
            'Version' . S . ' 0.0.0' . "\n" .
            "\n" . SEPARATOR . "\n" .
            "\n" . Config::speak('notify_not_available', $speak->description);
        return Mecha::O(Text::toPage(File::open($info)->read($page_default), 'content', 'shield:'));
    }

    /**
     * ==========================================================
     *  RENDER A PAGE
     * ==========================================================
     *
     * -- CODE: -------------------------------------------------
     *
     *    Shield::attach('article', true, false);
     *
     * ----------------------------------------------------------
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter | Type    | Description
     *  --------- | ------- | -----------------------------------
     *  $name     | string  | Name of the shield
     *  $minify   | boolean | Minify HTML output?
     *  $cache    | boolean | Create a cache file on page visit?
     *  $expire   | integer | Define cache file expiration time
     *  --------- | ------- | -----------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function attach($name, $minify = null, $cache = false, $expire = null) {
        if(is_null($minify)) {
            $minify = Config::get('html_minifier');
        }
        $G = array('data' => array(
            'name' => $name,
            'minify' => $minify,
            'cache' => $cache,
            'expire' => $expire
        ));
        Weapon::fire('before_shield_config_redefine', array($G, $G));
        extract(Filter::apply('shield:lot', self::cargo()));
        Weapon::fire('after_shield_config_redefine', array($G, $G));
        $shield = false;
        $shield_base = explode('-', $name, 2);
        if($_file = File::exist(self::path($name))) {
            $shield = $_file;
        } else if($_file = File::exist(self::path($shield_base[0]))) {
            $shield = $_file;
        } else {
            Guardian::abort(Config::speak('notify_file_not_exist', '<code>' . self::path($name) . '</code>'));
        }
        $G['data']['path'] = $shield;
        $q = ! empty($config->url_query) ? '.' . md5($config->url_query) : "";
        $cache_path = is_string($cache) ? $cache : CACHE . DS . str_replace(array('/', ':'), '.', $config->url_path) . $q . '.cache';
        self::$lot = array();
        if($G['data']['cache'] && File::exist($cache_path)) {
            if(is_null($expire) || is_int($expire) && time() - $expire < filemtime($cache_path)) {
                echo Filter::apply('shield:cache', File::open($cache_path)->read());
                exit;
            }
        }
        // Begin shield
        Weapon::fire('shield_before', array($G, $G));
        ob_start($minify ? 'self::s_o' : 'self::s_o_d');
        require Filter::apply('shield:path', $shield);
        Notify::clear();
        Guardian::forget();
        $content = ob_get_contents();
        ob_end_flush();
        $G['data']['content'] = $minify ? self::s_o($content) : self::s_o_d($content);
        if($G['data']['cache']) {
            $G['data']['cache'] = $cache_path;
            File::write($G['data']['content'])->saveTo($cache_path);
            Weapon::fire('on_cache_construct', array($G, $G));
        }
        Weapon::fire('shield_after', array($G, $G));
        // End shield
        exit;
    }

    /**
     * ==========================================================
     *  RENDER A 404 PAGE
     * ==========================================================
     *
     * -- CODE: -------------------------------------------------
     *
     *    Shield::abort();
     *
     * ----------------------------------------------------------
     *
     *    Shield::abort('404-custom');
     *
     * ----------------------------------------------------------
     *
     */

    public static function abort($name = '404', $minify = null, $cache = false, $expire = null) {
        HTTP::status(404);
        Config::set('page_type', '404');
        self::attach($name, $minify, $cache, $expire);
    }

}