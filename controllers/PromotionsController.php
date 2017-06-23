<?php
/**
 * .

 * Date: 15/1/8
 * Time: 19:15
 */

class PromotionsController extends TController {
    public function actionIndex(){
        $types = Activity::getAllTypes();
        $acts = Activity::getAllActivity();
        array_walk($acts,function(&$v){
            if($v['lbtime']){
                $v['created'] = $v['lbtime'];
            }
        });
        $img_host = Net::get_url_head();
        $this->render('index',array('types'=>$types,'acts'=>$acts,'img_host'=>$img_host));
    }

} 