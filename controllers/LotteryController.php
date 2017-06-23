<?php

/**
 * .
 * Date: 15/1/8
 * Time: 16:53
 */
class LotteryController extends TController
{
    public function actionKeno()
    {
        $is_login = $this->is_login();
        $keno_status = 0;//Keno::getStatus($is_login);
        $pl_status = 0;//Phoenix::getStatus($is_login);
        $bbin_status = 0;//BBIN::getStatus($is_login,BBIN::LT);
        $sgwin_status = 0;
        $kg_status = 0;
        if ($is_login == false) {
            $keno_status = 1;//Keno::getStatus($is_login);
            $pl_status = 1;//Phoenix::getStatus($is_login);
            $bbin_status = 1;//BBIN::getStatus($is_login,BBIN::LT);
            $sgwin_status = 1;
            $kg_status = 1;
        }
        $this->render('keno', array('keno_status' => $keno_status, 'bbin_status' => $bbin_status,
            'pl_status' => $pl_status, 'sgwin_status' => $sgwin_status, 'kg_status' => $kg_status));
    }

    public function actionLunchBB()
    {
        $is_login = $this->is_login();
        $keno_status = 0;//Keno::getStatus($is_login);
        $pl_status = 0;//Phoenix::getStatus($is_login);
        $bbin_status = 0;//BBIN::getStatus($is_login,BBIN::LT);
        $sgwin_status = 0;
        if ($is_login == false) {
            $keno_status = 1;//Keno::getStatus($is_login);
            $pl_status = 1;//Phoenix::getStatus($is_login);
            $bbin_status = 1;//BBIN::getStatus($is_login,BBIN::LT);
            $sgwin_status = 1;
        }
        $this->render('bb', array('keno_status' => $keno_status, 'bbin_status' => $bbin_status, 'pl_status' => $pl_status, 'sgwin_status' => $sgwin_status));
    }


    public function actionLunchFH()
    {
        $is_login = $this->is_login();
        $keno_status = 0;//Keno::getStatus($is_login);
        $pl_status = 0;//Phoenix::getStatus($is_login);
        $bbin_status = 0;//BBIN::getStatus($is_login,BBIN::LT);
        $sgwin_status = 0;
        if ($is_login == false) {
            $keno_status = 1;//Keno::getStatus($is_login);
            $pl_status = 1;//Phoenix::getStatus($is_login);
            $bbin_status = 1;//BBIN::getStatus($is_login,BBIN::LT);
            $sgwin_status = 1;
        }
        $this->render('fhcai', array('keno_status' => $keno_status, 'bbin_status' => $bbin_status, 'pl_status' => $pl_status, 'sgwin_status' => $sgwin_status));
    }


    public function actionLunchSG()
    {
        $is_login = $this->is_login();
        $keno_status = 0;//Keno::getStatus($is_login);
        $pl_status = 0;//Phoenix::getStatus($is_login);
        $bbin_status = 0;//BBIN::getStatus($is_login,BBIN::LT);
        $sgwin_status = 0;
        if ($is_login == false) {
            $keno_status = 1;//Keno::getStatus($is_login);
            $pl_status = 1;//Phoenix::getStatus($is_login);
            $bbin_status = 1;//BBIN::getStatus($is_login,BBIN::LT);
            $sgwin_status = 1;
        }
        $this->render('sgwin', array('keno_status' => $keno_status, 'bbin_status' => $bbin_status, 'pl_status' => $pl_status, 'sgwin_status' => $sgwin_status));
    }

    /**
     *
     */
    public function actionKenoGame()
    {
        $is_login = $this->is_login();
        $session_token = $this->getVCToken();
        $url = urldecode(Keno::get_game_url($this->uid, $is_login, $session_token));
        $this->redirect($url);
    }

    /**
     * 凤凰彩
     */
    public function actionFh()
    {
        $type = "cqssc";
        if (isset($_GET['type'])) {
            $type = $_GET['type'];
        }
        $is_login = $this->is_login();
        if ($is_login && Phoenix::isEnable()) {
            $token = $this->getVCToken();
            $api_key = 'ySof2ADDjRZQ4SQml24UavvbC5qfJpYv6X1o3n7EnYnym0jVSvdYdtFpCqRdYVKrWoJmVOAHjkZeIJyhCaiIYcnuaa4ZPUTPEWlKjo72cnnXNBhmzrLbqAs8K1BAIeTR';
            $name = $this->getGpName();
            $serial = 'A1-001';
            $action = 'login';
            $sign = sha1($action . $name . $serial . $api_key . $token);
            $params = array(
                'name' => $name,
                'serial' => $serial,
                'action' => $action,
                'token' => $token,
                'sign' => $sign,
                'type' => $type,
            );

            $url = '';
            foreach ($params as $key => $value) {
                $url .= '&' . $key . '=' . $value;
            }
            $url = trim($url, '&');
            $this->redirect('http://fhapi.kzonlinegame.com/gameLogin?' . $url);
        }
        $this->redirect(array(
            '/home/index'
        ));

    }

    /**
     * bbin彩票
     */
    public function actionBbin()
    {
        $is_login = $this->is_login();
        $prefix = Customer::getInstance()->getPrefix();
        if ($is_login && BBIN::isEnable(BBIN::LT)) {
            $name = $this->user['playername'];
            $url = BBIN::get_login_url($prefix, $name, 'Ltlottery');
            $this->redirect($url);
        } else {
            $this->redirect(array(
                '/home/index'
            ));
        }
    }

    /**
     * 双赢彩票
     * @param string $lottery
     */
    public function actionSgwin($lottery = '')
    {
        if (!Customer::getInstance()->prefixCmp(array("d11", "a1", "f26", "f34", "a3", "c21", "c47", "e35", "a44", "c32", "h30","h28"))) {
            $rs = new Result(false, "游戏平台维护！");
            $title = "游戏平台维护";
            $this->render('/home/result', array('rs' => $rs, 'title' => $title));
        }
        $is_login = $this->is_login();
        if ($is_login && SGWIN::isEnable()) {
            $sgwin = new SGWIN($this->prefix, $this->playerName);
            $url = $sgwin->login($lottery);
            if ($url) {
                $this->redirect($url);
            }
        } else {
            $this->redirect(array(
                '/home/index'
            ));
        }
    }

    public function actionSgwinTest($lottery = '')
    {
        $is_login = $this->is_login();
        if ($is_login && SGWIN::isEnable()) {
            $sgwin = new SGWIN($this->prefix, $this->playerName);
            $response = $sgwin->logintest($lottery);
            var_dump($response);
        }
    }


    /**
     * @param int $gameType
     * @param int $stakeId
     */
    public function actionKg($gameType = 0, $stakeId = 0)
    {
        $is_login = $this->is_login();
        if ($is_login && KG::isEnable()) {
            $user = $this->user;
            $kg = new KG($user['gpn'], $user['playername'], $gameType, $stakeId, $user['acctype']);
            $url = $kg->login();
            if ($url) {
                $this->redirect($url);
            }
        }
        $this->redirect(array(
            '/home/index'
        ));
    }


    /**
     *
     */
    public function actionCpwin()
    {
        $is_login = $this->is_login();
        if ($is_login && CPWin::isEnable()) {
            $user = $this->user;
            $cpwin = new CPWin();
            $result = $cpwin::httpPost($cpwin::Domain . '/cgi/auther/login', array('username' => $user['gpn'], 'root' => $cpwin::APP_ID));
            $BOM = chr(239) . chr(187) . chr(191);
            $result = str_replace($BOM, '', $result);
            $ret = json_decode($result);
            $url = $cpwin::Domain . '/main.php?OLID=' . $ret->data->sid;
            $this->redirect($url);
        }
        $this->redirect(array(
            '/home/index'
        ));
    }

    /**获取凤凰彩票信息**/

    public function actionGetFHInfo()
    {
        $curl = new Curl();
        $curl->get('http://fhapi.kzonlinegame.com/gameIndex?serial=A1-001');
        echo $curl->raw_response;
        exit;
    }

    public function actionOpus()
    {
        $isLogin = $this->is_login();
        if ($isLogin && OPUS::isEnable()) {
            $url = "http://iframe.kzonlinegame.com/opuskeno.php?s=" . $this->getVCToken() . Net::getServerAddr();
            $this->redirect($url);
        } else {
            $this->redirect(array(
                '/home/index'
            ));
        }
    }

} 