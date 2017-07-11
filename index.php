<?php
/**
 * Created by PhpStorm.
 * User: Ning
 * Date: 2017/7/10
 * Time: 13:29
 */
ini_set("memory_limit", "1024M");
require dirname(__FILE__) . '/core/init.php';

/* Do NOT delete this comment */
/* 不要删除这段注释 */

$configs = array(
    'name' => '小说',
    'tasknum' => 1,
    'log_show' => false,
    'max_try' => 0,
    'domains' => array(
        'www.zwdu.com'
    ),
    'scan_urls' => array(
        'http://www.zwdu.com/book/26577'
    ),
    'export' => array(
        'type' => 'sql',
        'file' => PATH_DATA . '/zuiqiangnixi.sql'
    ),
    'content_url_regexes' => array(
        'http://www.zwdu.com/book/26577',
    ),
    'fields' => array(
        //书名
        array(
            'name' => 'name',
            'selector' => '//*[@id="info"]/h1',
        ),
        //作者
        array(
            'name' => 'author',
            'selector' => '//*[@id="info"]/p[1]',
        ),
        //描述
        array(
            'name' => 'description',
            'selector' => '//*[@id="intro"]/p[1]',
        ),
        //类型
        array(
            'name' => 'type',
            'selector' => '//*[@id="wrapper"]/div[4]/div[1]/a[2]',
        ),
        //封面图片
        array(
            'name' => 'image',
            'selector' => '//*[@id="fmimg"]/img/@src',
        ),
        //内容
        array(
            'name' => 'content',
            'selector' => '//*[@id="content"]',
        )
    )
);

$spider = new phpspider($configs);

$spider->on_extract_field = function ($fieldname, $data, $page)
{
    if ($fieldname == "author" && !empty($data['author'])) {
        $data['author'] = trim(substr(trim($data['author']), strpos($data['author'], '：') + 1));
    }

    return $data;
};

$spider->on_extract_page = function ($page, $data)
{
    $data['add_time'] = date('Y-m-d H:i:s', time());
    log::info(json_encode($data));
};

$spider->start();
