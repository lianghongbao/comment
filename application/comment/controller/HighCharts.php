<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/20
 * Time: 10:41
 */
namespace app\comment\controller;
use think\Controller;

class HighCharts extends Controller{

    public function index(){
        return $this->fetch('highcharts/highcharts');
    }

    public function test(){
        $categories=['Apples', 'Bananas', 'Oranges','Pineapple'];
        $series=[
            ['name'=>'Jane','data'=>[1,9,4,10]],
            ['name'=>'John','data'=>[5,7,6,15]],
            ['name'=>'Test','data'=>[7,12,8,13]]
        ];
        $data=['series'=>$series,'categories'=>$categories];
        $data=json_encode($data);
        echo $data;
    }

}