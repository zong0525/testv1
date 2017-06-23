<?php

/**
 * .
 * Date: 15/1/12
 * Time: 18:58
 */
class WagerController extends MController
{
    public function actionIndex()
    {
        $this->render('index');
    }


    public function actionHistory($gpId = 0, $start = '', $end = '')
    {
        $name = $this->user['playername'];
        if (empty($name)) {
            return array();
        }
        $platforms = Report::get_enable_gp_names();
        if (!$gpId) {
            $gpId = current(array_keys($platforms));
        }
        $now = DateUtil::beginOfDay(time());
        $start = DateUtil::str2time($start, DateUtil::beginOfDay($now));
        $end = DateUtil::str2time($end, time());
        $results = Report::newBetHistory('', $name, 0, $gpId, $start, $end);
        $transIds = '';
        $imIds = '';
        $sxIds = '';
        foreach ($results as $item) {
            $gpType = $item['gp_type'];
            $betNo = $item['bet_no'];
            if ($gpType == Report::SPON) {
                $transIds .= $betNo . ',';
            } elseif ($gpType == Report::IM) {
                $imIds .= $betNo . ',';
            } elseif ($gpType == Report::SXING) {
                $sxIds .= '\'' . $betNo . '\'' . ',';
            }
        }
        $transIds = trim($transIds, ',');
        $imIds = trim($imIds, ',');
        $sxIds = trim($sxIds, ',');
        $sponParlays = Report::getSponParlay($transIds);
        $imParlays = Report::getIMParlay($imIds);
        $sxParlays = Report::getSXParlay($sxIds);
        $this->renderPartial('history', array('result' => $results, 'start' => $start,
            'end' => $end, 'gpId' => $gpId, 'platforms' => $platforms,
            'sponParlays' => $sponParlays, 'imParlays' => $imParlays, 'sxParlays' => $sxParlays,
        ));
    }


    /**
     * 投注历史
     * @param int $gpId
     * @param int $curIndex
     * @param string $start
     * @param string $end
     */
//    public function actionHistory($gpId=0,$curIndex=1,$start='',$end=''){
//        $playerId = $this->uid;
//        $now = strtotime('-7 days');
//        $start = DateUtil::str2time($start,DateUtil::beginOfDay($now));
//        $end = DateUtil::str2time($end,time());
//        $total = Wagered::historyTotal($gpId,$playerId,$start,$end);
//        $page = new Pages($curIndex,$total,15,$this->get_page_delegation());
//        $results = Wagered::betHistory($gpId,$playerId,$start,$end,$page->getOffset(),$page->pageCount);
//        $this->renderPartial('history',array('result'=>$results,'page'=>$page,'start'=>$start,'end'=>$end,'gpId'=>$gpId));
//    }


    /**
     * 红利
     * @param int $curIndex
     * @param string $start
     * @param string $end
     */
    public function actionBonus($curIndex = 1, $start = '', $end = '')
    {
        $playerId = $this->uid;
        $now = strtotime('-7 days');
        $start = DateUtil::str2time($start, DateUtil::beginOfDay($now));
        $end = DateUtil::str2time($end, time());
        $total = Bonus::bonusTotal($playerId, $start, $end);
        $page = new Pages($curIndex, $total, 15, $this->bonus_page_delegation());
        $platforms = GamePlatform::get_all_gp_dict();
        $results = Bonus::get_bonus($playerId, $start, $end, $page->getOffset(), $page->pageCount);
        $this->renderPartial('bonus', array('result' => $results, 'platforms' => $platforms, 'page' => $page, 'start' => $start, 'end' => $end));
    }


    /**
     * 投注分页
     * @return callable
     */
    public static function get_page_delegation()
    {
        return function ($params, $index, $name) {
            $start = DateUtil::format($params['start']);
            $end = DateUtil::format($params['end']);
            $gpId = $params['gpId'];
            return sprintf('<a href="javascript:void(0);" onclick="load_history(\'%d\',\'%s\',\'%s\',\'%d\');" >%s</a>', $gpId, $start, $end, $index, $name);
        };
    }

    /**
     * 红利分页
     * @return callable
     */
    public static function bonus_page_delegation()
    {
        return function ($params, $index, $name) {
            $start = DateUtil::format($params['start']);
            $end = DateUtil::format($params['end']);
            return sprintf('<a href="javascript:void(0);" onclick="bonus(\'%s\',\'%s\',\'%d\');" >%s</a>', $start, $end, $index, $name);
        };
    }

    public function actionSxingInfo()
    {
        $info = $this->post('info');
        $info = explode(':', $info);
        $rs = null;
        if (count($info) === 3) {
            $rs = SXing::getBetInfo($info[0], $info[1], $info[2]);
        }
        $this->echoJson($rs);
    }

} 