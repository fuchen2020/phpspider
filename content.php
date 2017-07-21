<?php
/**
 * Created by PhpStorm.
 * User: Ning
 * Date: 2017/7/21
 * Time: 14:24
 */
ini_set("memory_limit", "1024M");
ini_set('default_socket_timeout', -1);
require dirname(__FILE__) . '/core/init.php';

$redis = new Redis();
$redis->connect('119.23.43.30');
$redis->auth('2430114823');
$redis->subscribe(['novels-cocoyo'], function ($redis, $channel, $msg) {
    $novel_id = json_decode($msg, true);

    if ($novel_id['novel_id']) {
        $configs = array(
            'name' => '小说',
            'tasknum' => 1,
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

        $spider->on_extract_page = function ($page, $data) use ($novel_id)
        {
            //截取sort
            $url = $page['url'];

            $sort = substr($url, strrpos($url, '/') + 1, strrpos($url, '.') - (strrpos($url, '/') + 1));
            //写入数据库
            try{
                db::update('chapters', ['content' => $data['content']], ["sort = {$sort}"]);
            } catch (Exception $e) {
                Log::error('更新章节内容失败:' . $e->getMessage() . ',小说id:' . $novel_id['novel_id'] . ',sort:' . $sort);
            }

        };

        $spider->start();
    }
});
