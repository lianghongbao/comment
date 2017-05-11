<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/6
 * Time: 15:16
 */
namespace app\comment\controller;

use think\Controller;
use think\Db;
class Comment extends Controller
{
    public $comment    = array();
    public $hotComment = array();

    public function index()
    {
        //最新评论
        $new = $this->newComment();
        $this->assign('list', $new);
        //统计评论数目
        $total = $this->countComment();
        $this->assign('total', $total);
        //热门评论
        $hot = $this->hotComment();
        $this->assign('hot', $hot);
        return $this->fetch('comment/comment');
    }

    //分析组装数据
    public function analyse($result, $com,$cID=null)
    {
        foreach ($result as $key => $value) {
            if(!is_null($cID)&&$cID>$value['cID']){
                break;
            }
            if (!isset($com[$value['cID']])) {
                $com[$value['cID']] = [
                    'comment' => [
                        'c_id'      => $value['cID'],
                        'c_name'    => $value['c_username'],
                        'c_content' => $value['c_content'],
                        'c_time'    => $value['create_time'],
                        'c_support' => $value['support_count'],
                        'theme_id'  => $value['theme_id'],
                    ],
                    'reply'   => [],
                ];
            }
            if ($value['id']) {
                $com[$value['cID']]['reply'][$value['id']] = [
                    'r_id'      => $value['id'],
                    'r_name'    => $value['r_username'],
                    'r_content' => $value['r_content'],
                    'r_time'    => $value['reply_time'],
                ];
            }
        }
        return $com;
    }

    //查看更多评论
    public function moreComment($num){
        $result=Db::table('comment')
            ->field('id')
            ->order('id desc')
            ->limit($num['num'])
            ->select();

        $last=end($result);
        return $last['id'];//用id判断analyse函数是否要停止循环（达到分页效果）
    }

    //最新评论
    public function newComment()
    {
        $num   = $_POST;
        if($num){
            $count = Db::table('comment')
                ->field('id')
                ->count('*');
            $count = $count + 10;
            if ($num['num'] >= $count) {
                echo $count;
                return null;
            }
            $commentID=$this->moreComment($num);
        }

        $result = Db::table('comment')
            ->field('a.id as cID,a.*,b.*')
            ->alias('a')
            ->join('reply b', 'a.id=b.comment_id', 'left')
            ->order('a.id desc')
            ->order('b.id desc')
            ->select();

        if(isset($commentID)){
            $new    = $this->analyse($result, $this->comment,$commentID);
        }else{
            $new    = $this->analyse($result, $this->comment);
        }
        return $new;

        /*//默认查询10条
        $limit = 10;
        //异步传输过来的分页数据
        $num   = $_POST;
        if ($num) {
            $count = Db::table('comment')
                ->field('a.id as cID,a.*,b.*')
                ->alias('a')
                ->join('reply b', 'a.id=b.comment_id', 'left')
                ->count('*');
            $count = $count + 10;
            if ($num['num'] >= $count) {
                echo $count;
                return null;
            } else {
                $limit = $num['num'];
            }
        }

        $result = Db::table('comment')
            //->fetchSql(true)//调试sql
            ->field('a.id as cID,a.*,b.*')
            ->alias('a')
            ->join('reply b', 'a.id=b.comment_id', 'left')
            ->order('a.id desc')
            ->order('b.id desc')
            ->limit($limit)
            ->select();
        $new    = $this->analyse($result, $this->comment);
        return $new;*/
    }

    //热门评论
    public function hotComment()
    {
        $result = Db::table('comment')
            ->field('a.id as cID,a.*,b.*')
            ->alias('a')
            ->join('reply b', 'a.id=b.comment_id', 'left')
            ->order('a.support_count desc')
            ->order('b.id desc')
            ->select();

        $hot = $this->analyse($result, $this->hotComment);
        return $hot;
    }

    //评论入库
    public function addComment()
    {
        $data   = $_POST;//接收ajax传来的消息
        $result = Db::table('comment')->insertGetId(
            [
                'c_username'    => $data['name'],
                'c_content'     => $data['content'],
                'create_time'   => $data['time'],
                'support_count' => (int)$data['support'],
                'theme_id'      => (int)$data['theme'],
            ]
        );

        return $result;
    }

    //点赞
    public function getCommentId()
    {
        $supportData = $_POST;

        $result = Db::table('comment')->where('id', $supportData['id'])->find();
        $result['support_count'] += 1;
        Db::table('comment')->where('id', $supportData['id'])->update(['support_count' => $result['support_count']]);
        $a = Db::table('comment')->where('id', $supportData['id'])->find();

        $a = json_encode($a);
        echo $a;
    }

    //回复入库
    public function addReply()
    {
        $data = $_POST;
        Db::table('reply')->insert(
            [
                'r_username' => $data['name'],
                'r_content'  => $data['content'],
                'reply_time' => $data['time'],
                'comment_id' => $data['commentID'],
            ]
        );
    }

    //评论总数
    public function countComment()
    {
        $count = Db::table('comment')->count();
        return $count;
    }

}