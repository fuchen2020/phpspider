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

$url = "http://www.zwdu.com/book/23488/";

$nameSelect = "//*[@id=\"info\"]/h1";
$authorSelect = "//*[@id=\"info\"]/p[1]";
$descriptionSelect = "//*[@id=\"intro\"]";
$typeSelect = "//*[@id=\"wrapper\"]/div[4]/div[1]/a[1]";
$imageSelect = "//*[@id=\"fmimg\"]/img/@src";
$chapterSelect = "//*[@id=\"list\"]/dl/dd/a";
$sortSelect = "//*[@id=\"list\"]/dl/dd/a/@href";
//开始抓取
$html = requests::get($url);

$data['name'] = selector::select($html, $nameSelect);
$data['author'] = selector::select($html, $authorSelect);
$data['description'] = selector::select($html, $descriptionSelect);
$data['type'] = selector::select($html, $typeSelect);
$data['image'] = selector::select($html, $imageSelect);
$chapters = selector::select($html, $chapterSelect);
$sorts = selector::select($html, $sortSelect);

$data['author'] = trim(substr($data['author'], strpos($data['author'], '：') + 3), '');
$data['description'] = str_replace(' ', '', $data['description']);
$data['created_at'] = date('Y-m-d H:i:s', time());

db::begin_tran();
$res = db::insert('novels', $data);

if (false === $res) {
    db::rollback();
    return log::error('写入数据失败,信息:描述写入失败');
}

for ($i = 0; $i < count($chapters); $i++) {
    $chapter = db::insert('chapters', [
                'novel_id' => $res,
                'chapter' => explode('章', $chapters[$i])[0] . '章',
                'description' => ltrim(explode('章', $chapters[$i])[1]),
                'sort' => substr($sorts[$i], strrpos($sorts[$i], '/') + 1, strpos($sorts[$i], '.') - (strrpos($sorts[$i], '/') + 1)),
                'created_at' => date('Y-m-d H:i:s', time())
            ]);
    if (false === $chapter) {
        db::rollback();
        return log::error('写入数据失败,信息:描述写入内容');
    }
}
db::commit();


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

$spider->on_extract_page = function ($page, $data)
{
    //截取sort
    $url = $page['url'];

    $sort = substr($url, strrpos($url, '/') + 1, strrpos($url, '.') - (strrpos($url, '/') + 1));
    //写入数据库
    db::update('chapters', ['content' => $data['content']], ["sort = {$sort}"]);
};

$spider->start();
