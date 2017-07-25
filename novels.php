<?php
/**
 * Created by PhpStorm.
 * User: Ning
 * Date: 2017/7/19
 * Time: 14:32
 */
ini_set("memory_limit", "1024M");
ini_set('default_socket_timeout', -1);
require dirname(__FILE__) . '/core/init.php';

class novels
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @var array
     */
    protected $xPathSelectors = [];

    /**
     * @var integer
     */
    protected $user_id;

    /**
     * novels constructor.
     * @param $url
     * @param $user_id
     * @param array $xPathSelectors
     */
    public function __construct($url, $user_id, $xPathSelectors = [])
    {
        $this->url = $url;
        $this->xPathSelectors = $xPathSelectors;
        $this->user_id = $user_id;
    }

    /**
     * 获取小说信息
     * @return bool
     */
    public function getNovelInfo()
    {
        if ($this->xPathSelectors) {
            $html = $this->getHtml();

            foreach ($this->xPathSelectors as $key => $xPathSelector) {
                $data[$key] = selector::select($html, $xPathSelector);
            }

            return $this->handelData($data);
        }

        return false;
    }

    /**\
     * 处理数据
     * @param array $data
     * @return bool|void
     */
    protected function handelData(array $data)
    {
        $data['author'] = trim(substr($data['author'], strpos($data['author'], '：') + 3), '');
        $data['description'] = str_replace(' ', '', $data['description']);

        $res = db::insert('novels', [
            'name' => $data['name'],
            'author' => $data['author'],
            'user_id' => $this->user_id,
            'description' => $data['description'],
            'type' => $data['type'],
            'image' => $data['image'],
            'url' => $this->url,
            'created_at' => date('Y-m-d H:i:s', time())
        ]);

        if (false === $res) {
            return false;
        }

        //写入章节
        $this->chapter([$res, $data['sort'], $data['chapter']]);

        return $res;
    }

    /**
     * 处理文章章节
     * @param array $data
     * @return bool
     */
    protected function chapter(array $data)
    {
        list($novel_id, $sort, $chapter) = $data;

        for ($i = 0; $i < count($chapter); $i++) {
            try{
                $chapterRes = db::insert('chapters', [
                    'novel_id' => $novel_id,
                    'chapter' => explode('章', $chapter[$i])[0] . '章',
                    'description' => ltrim(explode('章', $chapter[$i])[1]),
                    'sort' => substr($sort[$i], strrpos($sort[$i], '/') + 1, strpos($sort[$i], '.') - (strrpos($sort[$i], '/') + 1)),
                    'created_at' => date('Y-m-d H:i:s', time())
                ]);
            } catch (Exception $exception) {
                log::error('写入文章章节数据错误,文章id:' . $novel_id . ', 数据1:' . $chapter[$i] . '数据2:' . $sort[$i]);
            }
        }
    }

    /**
     * 获取页面
     * @return mixed|null|string
     */
    protected function getHtml()
    {
        return requests::get($this->url);
    }
}


