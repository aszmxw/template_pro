<?php
namespace app\huasheng\controller;

use org\Page;

class Article extends Base
{

    protected function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub

    }

    public function index($primary_id = 0)
    {
        //分割变量
        $primary_ids = explode("_",$primary_id);
        $page = 1;

        if (count($primary_ids) > 1) {
            $primary_id = $primary_ids[0];
            $page = $primary_ids[1];
        }

        //章节列表分页大小
        $chapter_page_size = 500;
        if (!empty($this->site_config['extend']['chapter_page_list_num'])) {
            $chapter_page_size = $this->site_config['extend']['chapter_page_list_num'];
        }

        $article = model('article')->where('PrimaryId','eq',get_cut_value($primary_id))->cache()->find()->toArray();

        //手动更新
        if ($article['OneSelf'] == 1) {
            if ($this->site_config['extend']['chapter_page_list_switch'] == 'on') {
                $all_chapter_list = get_custom_all_chapter_list($article['_id'],$article['SourceList'],$page,$chapter_page_size);
            }else {
                $all_chapter_list = get_custom_all_chapter_list($article['_id'],$article['SourceList'],0,0);
            }
        }else {
            if ($this->site_config['extend']['chapter_page_list_switch'] == 'on') {
                $all_chapter_list = get_all_chapter_list($article['_id'],$article['SourceList'],$page,$chapter_page_size);
            }else {
                $all_chapter_list = get_all_chapter_list($article['_id'],$article['SourceList'],0,0);
            }
        }

        //获取当前分类
        $article['Category'] = get_category($article['Cid']);
        $article['SourceUrl'] = "";

        //获取当前源地址
        foreach ($article['SourceList'] as $url) {
            if (get_flag_name($url) == $article['LastChapterFlag']){
                $article['SourceUrl'] = $url;
                break;
            }
        }

        //修复小说表没有 LastChapterFlag 标记
        if ($article['SourceUrl'] == "" && count($article['SourceList']) > 0) {
            $article['SourceUrl'] = $article['SourceList'][0];
        }

        $article['LastUpdateChapterList'] = $all_chapter_list['last_update_chapter_list'];
        $article['ChapterList'] = $all_chapter_list['chapter_list'];

        //最后更新章节id和标题
        $article['LastChapterSort'] = $all_chapter_list['last_chapter_sort'];
        $article['LastChapterTitle'] = $all_chapter_list['last_chapter_title'];
        $article['LastChapterFlag'] = $all_chapter_list['last_chapter_flag'];

        //阅读首章
        if (count($article['ChapterList']) > 0) {
            $article['FirstChapterUrl'] = url("/chapter/" . $article['PrimaryId'] . "/" . get_offset_sort($article['ChapterList'][0]['Sort'],$article['SourceList'],$article['ChapterList'][0]['Url']),"","html",true);
            $article['FirstChapterTitle'] = $article['ChapterList'][0]['Title'];
        }else {
            $article['FirstChapterUrl'] = "javascript:void(0);";
            $article['FirstChapterTitle'] = "暂无章节";
        }

        //分页页码
        $article['page'] = $page;

        //获取最后章节
        $last_update_chapter_list = $article['LastUpdateChapterList'];
        $last_chapter = array_shift($last_update_chapter_list);

        //分页
        if ($this->site_config['extend']['chapter_page_list_switch'] == 'on') {
            $page = new Page(sprintf("book/%d_{PAGE}",$primary_id),$page,$all_chapter_list['chapter_total'],$chapter_page_size);
            if (isMobileDomain()) {
                $page->setConfig("theme",'%NO_PREV% %UP_PAGE% %DOWN_PAGE% %HEADER%');
            }
            $show = $page->show();
            $this->assign("pages",$show);
        }else {
            $this->assign("pages","");
        }

        //排行榜计数
        add_redis_cid_ranking(get_cut_value($article['PrimaryId']),$article['Category']['cid'],"top");
        //设置小说点击量
        set_novel_click(get_cut_value($article['PrimaryId']));
        //获取小说点击量
        $article['Click'] = get_novel_click(get_cut_value($article['PrimaryId']));

        $this->site_seo('details',array('details' => $article,'category_name' => $article['Category']['name']));
        return $this->fetch("index",['current_cate' => $article['Category'] , 'novel' => $article,'last_chapter' => $last_chapter]);
    }

    /**
     * 作者页面
     * @param string $name
     * @return mixed
     * @throws
     */
    public function author($name = '')
    {
        if (empty($name)) {
            $this->error("作者不能为空!");
        }

        $search_list = model("article")->whereIn("Cid",get_all_sub_cid_list())->where('Author','like',$name)->order('UpdateTime','desc')->cache()->select()->toArray();

        $this->site_seo('author',array('author' => $name));
        return $this->fetch('author', [ 'author' => $name,'list' => $search_list]);
    }


    /**
     * 移动端小说列表
     * @param int $id
     * @return mixed
     * @throws
     */
    public function mulu ($id = 0) {
        $article = model('article')->where('PrimaryId','eq',get_cut_value($id))->cache()->find()->toArray();
        $category = get_category($article['Cid']);

        $all_chapter_list = get_all_chapter_list($article['_id'],$article['SourceList'],0,0);
        $article['chapter_list'] = $all_chapter_list['chapter_list'];

        $article['page'] = "";
        $this->site_seo('details',array('details' => $article,'category_name' => $category['name']));

        return $this->fetch("mulu_list",['novel' => $article]);
    }


}

