<?php

Weapon::add('shield_before', function() {
    $config = Config::get();
    if( ! isset($config->defaults->article_css)) { // < 1.2.0
        $config->defaults->article_css = $config->defaults->article_custom_css;
        $config->defaults->article_js = $config->defaults->article_custom_js;
    }
    if( ! isset($config->defaults->page_css)) { // < 1.2.0
        $config->defaults->page_css = $config->defaults->page_custom_css;
        $config->defaults->page_js = $config->defaults->page_custom_js;
    }
    if( ! isset($config->keywords_spam)) { // < 1.2.0
        $config->keywords_spam = $config->spam_keywords;
        Config::set('keywords_spam', $config->keywords_spam);
    }
    if( ! is_object($config->author)) { // < 1.2.0
        $config->author = (object) array(
            'name' => $config->author,
            'email' => $config->author_email,
            'url' => $config->author_profile_url
        );
        Config::set('author', $config->author);
        if($config->page_type === 'manager') {
            Notify::info('<strong>1.2.0</strong> &mdash; In your <a href="' . $config->url . '/' . $config->manager->slug . '/shield">shield</a> files, change all <code>$config->author</code> data to <code>$config->author->name</code>, <code>$config->author_email</code> data to <code>$config->author->email</code> and <code>$config->author_profile_url</code> data to <code>$config->author->url</code>. Then go to the <a href="' . $config->url . '/' . $config->manager->slug . '/config">configuration manager page</a> to kill this message by pressing the <strong>Update</strong> button.');
        }
    }
}, 1);

Weapon::add('on_config_update', function() {
    // Self destruct ...
    File::open(__FILE__)->delete();
});