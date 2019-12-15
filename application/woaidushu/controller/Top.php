<?php
namespace app\woaidushu\controller;

use app\common\controller\Common;
use org\RedisRanking\DummyDayDataSource;
use org\RedisRanking\Ranking\DailyRanking;
use org\RedisRanking\Ranking\MonthlyRanking;
use org\RedisRanking\Ranking\TotalRanking;
use org\RedisRanking\Ranking\WeeklyRanking;
use org\RedisRanking\RankingManger;

class Top extends Base
{

    public function index ($type = 'top_list',$cid = 0){
        if (isMobileDomain()) {
            return $this->mobile($type,$cid);
        }else {
            return $this->pc();
        }
    }

    /**
     * PC排行榜
     * @return mixed
     */
    public function pc () {

        //获取所有分类
        $site_config = get_site_config();
        $category_list = $site_config['category_list'];

        $ranking_list = array();

        foreach ($category_list as $category) {
            $ranking_list[] = array(
                'cid' => $category['cid'],
                'name' => $category['name'],
                'top_list' => get_redis_cid_ranking_list($category['cid'],15,"top"),
                'collect_list' => get_redis_cid_ranking_list($category['cid'],15,"collect"),
                'block_list' => get_cid_block_list($category['cid'],15),
            );
        }

        $this->site_seo();
        $this->assign("current_cate",array('name' => "排行榜", 'alias' => "top"));
        return $this->fetch('index',['ranking_list' => $ranking_list]);
    }

    /**
     * 移动端排行榜
     * @param string $type 类型
     * @param int $cid 分类id
     * @return mixed
     */
    public function mobile ($type = 'top_list',$cid = 0) {

        //获取所有分类
        $site_config = get_site_config();
        $category_list = $site_config['category_list'];

        $ranking_nav_list = array(
            array('name' => "阅读榜",'type' => 'top_list'),
            array('name' => "收藏榜",'type' => 'collect_list'),
            array('name' => "推荐榜",'type' => 'block_list')
        );

        if ($cid == 0) {
            $cid = $category_list[0]['cid'];
        }

        $ranking_list = [];
        if ($type == "top_list") {
            $ranking_list = get_redis_cid_ranking_list($cid,10,"top");
        }else if($type == "collect_list") {
            $ranking_list =  get_redis_cid_ranking_list($cid,10,"collect");
        }else if($type == "block_list") {
            $ranking_list = get_cid_block_list($cid,10);
        }

        $this->site_seo();
        $this->assign("current_cate",array('name' => "排行榜", 'alias' => "top"));
        return $this->fetch('index',['ranking_list' => $ranking_list,'ranking_nav_list' => $ranking_nav_list,'type' => $type,'cid' => $cid]);
    }

}
