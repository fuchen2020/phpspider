<?php
/**
 * Created by PhpStorm.
 * User: Ning
 * Date: 2017/7/20
 * Time: 15:33
 */
require dirname(__FILE__) . '/novels.php';

$redis = new Redis();
$redis->connect('119.23.43.30');
$redis->auth('2430114823');
$redis->subscribe(['novels-cocoyo'], function ($redis, $channel, $msg) {
    $msg = json_decode($msg, true);

    if (!empty($msg['url']) && !empty($msg['user_id'])) {
        $url = $msg['url'];
        $user_id = $msg['user_id'];

        $xPathSelector = [
            'name' => '//*[@id="info"]/h1',
            'author' => '//*[@id="info"]/p[1]',
            'description' => '//*[@id="intro"]',
            'type' => '//*[@id="wrapper"]/div[4]/div[1]/a[1]',
            'image' => '//*[@id="fmimg"]/img/@src',
            'chapter' => '//*[@id="list"]/dl/dd/a',
            'sort' => '//*[@id="list"]/dl/dd/a/@href'
        ];

        $novels = new novels($url, $user_id, $xPathSelector);

        $novel_id = $novels->getNovelInfo();

        //开始爬取内容
        if ($novel_id) {
            $configs = array(
                'name' => '小说',
                'tasknum' => 1,
                'domains' => array(
                    'www.zwdu.com'
                ),
                'scan_urls' => array(
                    $url
                ),
                'content_url_regexes' => array(
                    $url . '\d+.html'
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
                    Log::error('更新章节内容失败:' . $e->getMessage() . ',小说id:' . $novel_id . ',sort:' . $sort);
                }

            };

            $spider->start();
        }
    }

});


