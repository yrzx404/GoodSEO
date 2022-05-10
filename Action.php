<?php

class GoodSEO_Action extends Typecho_Widget implements Widget_Interface_Do
{

    public function action()
    {
    }

    /**
     * 生成 robots.txt
     */
    public static function robots()
    {
        $bot_list = array(
            'baidu' => '百度',
            'google' => '谷歌',
            'sogou' => '搜狗',
            'youdao' => '有道',
            'soso' => '搜搜',
            'bing' => '必应',
            'yahoo' => '雅虎',
            '360' => '360搜索'
        );
        $useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
        foreach ($bot_list as $k => $v) {
            if (strpos($useragent, ($k . '')) !== false) {
                $log['subject'] = $v;
                $log['action'] = '爬取';
                $log['object'] = 'robots.txt';
                $log['result'] = '成功';
                self::logger($log);
            }
        }
        header("Content-Type: text/plain");
        echo <<<TEXT
User-agent: *
Allow: /
Disallow: /feed/
Disallow: /admin/
TEXT;
    }

    /**
     * 自动推送+统计代码
     */
    public static function tongji()
    {
        $tongji = Helper::options()->plugin('GoodSEO')->tongji;
        echo $tongji;
    }

    /**
     * 生成sitemap
     */
    public static function sitemap()
    {
        $db = Typecho_Db::get();
        $options = Helper::options();

        $bot_list = array(
            'baidu' => '百度',
            'google' => '谷歌',
            'sogou' => '搜狗',
            'youdao' => '有道',
            'soso' => '搜搜',
            'bing' => '必应',
            'yahoo' => '雅虎',
            '360' => '360搜索'
        );

        $useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
        foreach ($bot_list as $k => $v) {
            if (strpos($useragent, ($k . '')) !== false) {
                $log['subject'] = $v;
                $log['action'] = '爬取';
                $log['object'] = 'sitemap';
                $log['result'] = '成功';
                self::logger($log);
            }
        }

        $pages = $db->fetchAll($db->select()->from('table.contents')
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.created < ?', $options->gmtTime)
            ->where('table.contents.type = ?', 'page')
            ->order('table.contents.created', Typecho_Db::SORT_DESC));

        $articles = $db->fetchAll($db->select()->from('table.contents')
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.created < ?', $options->gmtTime)
            ->where('table.contents.type = ?', 'post')
            ->order('table.contents.created', Typecho_Db::SORT_DESC));

        header("Content-Type: application/xml");
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>\n";
        foreach ($pages as $page) {
            $type = $page['type'];
            $routeExists = (NULL != Typecho_Router::get($type));
            $page['pathinfo'] = $routeExists ? Typecho_Router::url($type, $page) : '#';
            $page['permalink'] = Typecho_Common::url($page['pathinfo'], $options->index);

            echo "\t<url>\n";
            echo "\t\t<loc>" . $page['permalink'] . "</loc>\n";
            echo "\t\t<lastmod>" . date('Y-m-d', $page['modified']) . "</lastmod>\n";
            echo "\t\t<changefreq>daily</changefreq>\n";
            echo "\t\t<priority>0.8</priority>\n";
            echo "\t</url>\n";
        }
        foreach ($articles as $article) {
            $type = $article['type'];
            $article['categories'] = $db->fetchAll($db->select()->from('table.metas')
                ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
                ->where('table.relationships.cid = ?', $article['cid'])
                ->where('table.metas.type = ?', 'category')
                ->order('table.metas.order', Typecho_Db::SORT_ASC));
            $article['category'] = urlencode(current(Typecho_Common::arrayFlatten($article['categories'], 'slug')));
            $article['slug'] = urlencode($article['slug']);
            $article['date'] = new Typecho_Date($article['created']);
            $article['year'] = $article['date']->year;
            $article['month'] = $article['date']->month;
            $article['day'] = $article['date']->day;
            $routeExists = (NULL != Typecho_Router::get($type));
            $article['pathinfo'] = $routeExists ? Typecho_Router::url($type, $article) : '#';
            $article['permalink'] = Typecho_Common::url($article['pathinfo'], $options->index);

            echo "\t<url>\n";
            echo "\t\t<loc>" . $article['permalink'] . "</loc>\n";
            echo "\t\t<lastmod>" . date('Y-m-d', $article['modified']) . "</lastmod>\n";
            echo "\t\t<changefreq>daily</changefreq>\n";
            echo "\t\t<priority>0.8</priority>\n";
            echo "\t</url>\n";
        }
        echo "</urlset>";
    }

    /**
     * 准备数据
     * @param $contents 文章内容
     * @param $class 调用接口的类
     * @throws Typecho_Plugin_Exception
     */
    public static function send($contents, $class)
    {

        //获取系统配置
        $options = Helper::options();

        //判断是否配置好API
        if (is_null($options->plugin('GoodSEO')->api)) {
            throw new Typecho_Plugin_Exception(_t('api未配置'));
        }

        //获取文章类型
        $type = $contents['type'];

        //获取路由信息
        $routeExists = (NULL != Typecho_Router::get($type));

        if (!is_null($routeExists)) {
            $db = Typecho_Db::get();
            $contents['cid'] = $class->cid;
            $contents['categories'] = $db->fetchAll($db->select()->from('table.metas')
                ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
                ->where('table.relationships.cid = ?', $contents['cid'])
                ->where('table.metas.type = ?', 'category')
                ->order('table.metas.order', Typecho_Db::SORT_ASC));
            $contents['category'] = urlencode(current(Typecho_Common::arrayFlatten($contents['categories'], 'slug')));
            $contents['slug'] = urlencode($contents['slug']);
            $contents['date'] = new Typecho_Date($contents['created']);
            $contents['year'] = $contents['date']->year;
            $contents['month'] = $contents['date']->month;
            $contents['day'] = $contents['date']->day;
        }

        //生成永久连接
        $path_info = $routeExists ? Typecho_Router::url($type, $contents) : '#';
        $permalink = Typecho_Common::url($path_info, $options->index);

        //调用post方法
        self::post($permalink);
    }

    /**
     * 发送数据
     * @param $url 准备发送的url
     */
    public static function post($url)
    {
        if (preg_match("/^(http(s)?:\/\/).*(\/\.html)$/i", $url)) {
            return;
        }
        $options = Helper::options();

        //获取API
        $api = $options->plugin('GoodSEO')->api;

        //准备数据
        if (is_array($url)) {
            $urls = $url;
        } else {
            $urls = array($url);
        }

        //日志信息
        $log['subject'] = '我';
        $log['action'] = '百度收录API推送';
        $log['object'] = implode(",", $urls);

        try {
            //为了保证成功调用，老高先做了判断
            if (false == Typecho_Http_Client::get()) {
                throw new Typecho_Plugin_Exception(_t('对不起, 您的主机不支持 php-curl 扩展而且没有打开 allow_url_fopen 功能, 无法正常使用此功能'));
            }

            //发送请求
            $http = Typecho_Http_Client::get();
            $http->setData(implode("\n", $urls));
            $http->setHeader('Content-Type', 'text/plain');
            $json = $http->send($api);
            $return = json_decode($json, 1);
            if (isset($return['error'])) {
                $log['result'] = '失败';
            } else {
                $log['result'] = '成功';
            }
        } catch (Typecho_Exception $e) {
            $log['result'] = '失败：' . $e->getMessage();
        }

        self::logger($log);
    }


    public static function logger($data)
    {
        $log_dir = __TYPECHO_ROOT_DIR__ . '/usr/log/';
        if (!is_dir($log_dir)) @mkdir($log_dir, 0777);//检测缓存目录是否存在，自动创建
        //将日志记录写入文件中
        $row = date('Y-m-d H:i:s') . "\t" . $data['subject'] . "\t" . $data['action'] . "\t" . $data['object'] . "\t" . $data['result'] . "\r\n";
        @error_log($row, 3, $log_dir . date('Ymd') . '.log');
    }
}
