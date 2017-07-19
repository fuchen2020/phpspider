<?php
/**
 * Created by PhpStorm.
 * User: Ning
 * Date: 2017/7/19
 * Time: 14:32
 */
ini_set("memory_limit", "1024M");
require dirname(__FILE__) . '/core/init.php';

/* Do NOT delete this comment */
/* 不要删除这段注释 */

$configs = array(
    'name' => '最强逆袭',
    'tasknum' => 1,
    'export' => array(
        'type' => 'sql',
        'file' => PATH_DATA . '/zuiqiangnixi.sql'
    ),
    'domains' => array(
        'www.zwdu.com'
    ),
    'scan_urls' => array(
        'http://www.zwdu.com/book/23488/'
    ),
    'content_url_regexes' => array(
        'http://www.zwdu.com/book/23488/\d+.html'
    ),
    'fields' => array(
        array(
            'name' => 'content',
            'selector' => '//*[@id="content"]',
            'required' => true
        )
    )
);

$spider = new phpspider($configs);

$spider->on_extract_field = function ($fielaname, $data, $page)
{
    if ($fielaname == 'content') {
        str_replace(' ', '', $data['content']);
    }

    return $data;
};

$spider->on_extract_page = function ($page, $data)
{
    //截取sort
    $url = $page['url'];

    $sort = substr($url, strrpos($url, '/') + 1, strrpos($url, '.') - (strrpos($url, '/') + 1));
    //写入数据库
    db::update('chapter', ['content' => $data['content']], ["sort = {$sort}"]);
};

$spider->start();


