<?php

/**
 * 好用的SEO插件<br>目前支持：百度推送、自动生成sitemap.xml、robots.txt
 * @package GoodSEO
 * @author 程序员D哥
 * @version 1.0
 * @link https://www.javatiku.cn
 */
class GoodSEO_Plugin implements Typecho_Plugin_Interface
{
    /* 激活插件方法 */
    public static function activate()
    {
        //挂载发布文章和页面的接口
        Typecho_Plugin::factory('Widget_Archive')->header = array('GoodSEO_Action', 'tongji');
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishPublish = array('GoodSEO_Action', 'send');
        Typecho_Plugin::factory('Widget_Contents_Page_Edit')->finishPublish = array('GoodSEO_Action', 'send');

        //添加网站地图功能
        Helper::addRoute('sitemap', '/sitemap.xml', 'GoodSEO_Action', 'sitemap');
        Helper::addRoute('robots', '/robots.txt', 'GoodSEO_Action', 'robots');
        return _t('请进入设置填写 <b>接口调用地址</b>');
    }

    /* 禁用插件方法 */
    public static function deactivate()
    {
        return _t('插件已禁用');
    }

    /* 插件配置方法 */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        /** 分类名称 */
        $element = new Typecho_Widget_Helper_Form_Element_Text('api', null, null, _t('接口调用地址'), '请登录百度站长平台获取');
        $form->addInput($element->addRule('required', _t('请填写接口调用地址')));

        $element = new Typecho_Widget_Helper_Form_Element_Textarea('tongji', null, null, _t('统计脚本'), '请登录统计平台获取');
        $form->addInput($element->addRule('required', _t('请填写统计脚本')));
    }

    /* 个人用户的配置方法 */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }
}
