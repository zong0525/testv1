<?php

/**
 * .
 * Date: 15/1/9
 * Time: 10:51
 */
class GameController extends TController
{
    public function actionIndex()
    {
        $is_login = $this->is_login();
        $mg_status = 0;//MG::getStatus($is_login);
        $bbin_status = 0;//BBIN::getStatus($is_login,BBIN::CH);
        $pt_status = 0;//PT::getStatus($is_login,PT::PT_RE);
        $nt_status = 0;
		$cd_status = 0;
        if ($is_login == false) {
            $mg_status = 1;
            $bbin_status = 1;
            $pt_status = 1;
            $nt_status = 1;
			$cd_status = 1;
        }
        $this->render('index', array('mg_status' => $mg_status, 'bbin_status' => $bbin_status, 'pt_status' => $pt_status, 'nt_status' => $nt_status, 'cd_status' => $cd_status));
    }

    public function actionFish()
    {
        $is_login = $this->is_login();
        $mg_status = 0;//MG::getStatus($is_login);
        $bbin_status = 0;//BBIN::getStatus($is_login,BBIN::CH);
        $pt_status = 0;//PT::getStatus($is_login,PT::PT_RE);
        $nt_status = 0;
        if ($is_login == false) {
            $mg_status = 1;
            $bbin_status = 1;
            $pt_status = 1;
            $nt_status = 1;
        }
        $this->render('fish', array('mg_status' => $mg_status, 'bbin_status' => $bbin_status, 'pt_status' => $pt_status, 'nt_status' => $nt_status));
    }

    public function actionALL()
    {
        $is_login = $this->is_login();
        $allStatus = $this->getAllStatus($is_login);
        $this->render('all', $allStatus);
    }

    private function getAllStatus($isLogin)
    {
        $gps = array(
            'mg_status' => 0,
            'bbin_status' => 0,
            'pt_status' => 0,
            'ag_status' => 0,
            'allbet_status' => 0,
            'gd_status' => 0,
            'sgwin_status' => 0,
        );
        if (!$isLogin) {
            array_walk($gps, function (&$v) {
                $v = 1;
            });
        }
        return $gps;
    }

    public function actionLunchPT()
    {
        $is_login = $this->is_login();
        $pt_status = 0;//PT::getStatus($is_login,PT::PT_RE);
        if ($is_login == false) {
            $pt_status = 1;
        }
        $this->render('pt', array('pt_status' => $pt_status));
    }

    public function actionLunchBB()
    {
        $is_login = $this->is_login();
        $bbin_status = 0;//BBIN::getStatus($is_login,BBIN::CH);
        if ($is_login == false) {
            $bbin_status = 1;
        }
        $this->render('bb', array('bbin_status' => $bbin_status));
    }

    public function actionLunchAG()
    {
        $is_login = $this->is_login();
        $ag_status = 0;
        if ($is_login == false) {
            $ag_status = 1;
        }
        $this->render('ag', array('ag_status' => $ag_status));
    }

    public function actionLunchFishing()
    {
        $is_login = $this->is_login();
        $status = 0;
        if ($is_login == false) {
            $status = 1;
        }
        $this->render('fishing', array('status' => $status));
    }

    public function actionLunchMG()
    {
        $is_login = $this->is_login();
        $mg_status = 0;//MG::getStatus($is_login);
        if ($is_login == false) {
            $mg_status = 1;
        }
        $this->render('mg', array('mg_status' => $mg_status));
    }

    public function actionLunchNT()
    {
        $is_login = $this->is_login();
        $nt_status = 0;
        if ($is_login == false) {
            $nt_status = 1;
        }
        $this->render('nt', array('nt_status' => $nt_status));
    }

    /**
     * 进入ag
     */
    public function actionAg($gameType = 0)
    {
        $is_login = $this->is_login();

        if ($is_login) {
            $gpName = $this->gpName;
            $prefix = $this->customer->getPrefix();
            if (strcasecmp($prefix, 'ibo') == 0) {//IBO平台玩家前缀
                $gpName = 'AG1_' . $gpName;
            }
            $ag_url = AsiaGames::getSingleGameUrl($this->uid, $gpName, $gameType);
            $this->redirect($ag_url);
        } else {
            $this->redirect(array(
                '/home/index'
            ));
        }
    }

    /**
     * 进入ag试玩
     */
    public function actionAgDemo($gameType = 0)
    {
        $is_login = $this->is_login();

        if ($is_login) {
            $gpName = $this->gpName;
            $prefix = $this->customer->getPrefix();
            if (strcasecmp($prefix, 'ibo') == 0) {//IBO平台玩家前缀
                $gpName = 'AG1_' . $gpName;
            }
            $ag_url = AsiaGames::getDemoGameUrl($this->uid, $gpName, $gameType);
            $this->redirect($ag_url);
        } else {
            $this->redirect(array(
                '/home/index'
            ));
        }
    }

//    public function actionMg($gameType = '', $gameId = '', $free = '')
//    {
//        if (empty($free)) {
//            if ($this->is_login()) {
//                $url = MG::launchGame($this->uid, $this->gpName, $gameId, $gameType);
//            } else {
//                echo 'please login.';
//            }
//        } else {
//            $url = MG::launchDemoGame($gameId);
//        }
//        $this->redirect($url);
//    }

    public function actionMg($gameId, $free = '')
    {
        $gameId = strtolower($gameId);
        $demoMode = empty($free) ? 'false' : 'true';
        if ($this->is_login()) {
            if (isset(MG::$launch_game_dict[$gameId])) {
                $url = MG::launchGameNew($this->uid, $this->gpName, MG::$launch_game_dict[$gameId], $demoMode);
            } else {
                echo 'game id error.';
                exit;
            }
        } else {
            echo 'please login.';
            exit;
        }
        $this->redirect($url);
    }

    /**
     * 进入bbin电子游戏
     * @param $gametype
     */
    public function actionBbin($gametype)
    {
        $is_login = $this->is_login();
        $prefix = Customer::getInstance()->getPrefix();
        if ($is_login) {

            $name = $this->user['playername'];
            $gametype = BBIN::fixGameType($gametype);
            if ($gametype == 15022 || $gametype == 15030) {
                $type = 15;
                $url = BBIN::get_login_url($prefix, $name, 'game', '');
            } else {
                $type = 5;
                $url = BBIN::play_game($prefix, $name, $type, $gametype);
            }

            if ($url) {
                $this->redirect($url);
            }
        } else {
            $this->redirect(array(
                '/home/index'
            ));
        }
    }

    /**
     * 进入PT电子游戏
     * @param $gameCode
     */
    public function actionPt($gameCode)
    {
        // $is_login = $this->is_login();
        // if ($is_login && PT::isEnable(PT::PT_RE)) {
        //     $prefix = Customer::getInstance()->getPrefix();
        //     $name = $this->getPlayerName();
        //     $url = PT::login_url($prefix, $name, $gameCode);
        //     $customer = Customer::getInstance();
        //     $acpid = $customer->getPrefix();
        //     //if($acpid=="a1"){
        //     //$url = str_replace("login.php","login1.php",$url);
        //     //}
        //     if ($url) {
        //         $this->redirect($url);
        //     }
        // }
        // $this->redirect(array(
        //     '/home/index'
        // ));
        $is_login = $this->is_login();
        if ($is_login && PT::isEnable(PT::PT_RE)) {
            $prefix = Customer::getInstance()->getPrefix();
            $user = $this->user;
            $name = $user['gpn'];

            $url = PT::login_url('KY', $name, $gameCode);
            $customer = Customer::getInstance();
            $acpid = $customer->getPrefix();
            //if($acpid=="a1"){
            //$url = str_replace("login.php","login1.php",$url);
            //}
            if ($url) {
                if(FLanguage::Get_L_Code() == 'en'){
                     $this->redirect($url.'&lan=EN');
                }else{
                    $this->redirect($url);
                }
            }
        }
        $this->redirect(array(
            '/home/index'
        ));
    }

    /**
     * 进入PT电子游戏
     * @param $gameCode
     */
    public function actionNPt($gameCode)
    {
        $is_login = $this->is_login();
        if ($is_login && PT::isEnable(PT::PT_RE)) {
            $prefix = Customer::getInstance()->getPrefix();
            $user = $this->user;
            $name = $user['gpn'];

            $url = PT::login_url('KY', $name, $gameCode);
            $customer = Customer::getInstance();
            $acpid = $customer->getPrefix();
            //if($acpid=="a1"){
            //$url = str_replace("login.php","login1.php",$url);
            //}
            if ($url) {
                $this->redirect($url);
            }
        }
        $this->redirect(array(
            '/home/index'
        ));
    }

        /**
     * 进入PT电子游戏
     * @param $gameCode
     */
    public function actionTPt($gameCode)
    {
        $is_login = $this->is_login();
        if ($is_login && TPT::isEnable(TPT::PT_RE)) {
            $prefix = Customer::getInstance()->getPrefix();
            $user = $this->user;
            $name = $user['gpn'];

            $url = TPT::login_url('KX', $name, $gameCode);
            $customer = Customer::getInstance();
            $acpid = $customer->getPrefix();
            //if($acpid=="a1"){
            //$url = str_replace("login.php","login1.php",$url);
            //}
            if ($url) {
                $this->redirect($url);
            }
        }
        $this->redirect(array(
            '/home/index'
        ));
    }

    /**
     * 进入PT电子游戏
     * @param $gameCode
     */
    public function actionPtNoCDN($gameCode)
    {
        $is_login = $this->is_login();
        if ($is_login && PT::isEnable(PT::PT_RE)) {
            $prefix = Customer::getInstance()->getPrefix();
            $name = $this->getPlayerName();
            $url = PT::login_url($prefix, $name, $gameCode);
            if ($url) {
                $this->redirect($url);
            }
        }
        $this->redirect(array(
            '/home/index'
        ));
    }

    /**
     * NT电游
     * @param $game
     */
    public function actionNt($game)
    {
        $is_login = $this->is_login();
        if ($is_login && NT::isEnable()) {
            $nt = new NT($this->prefix, $this->playerName);
            $url = $nt->get_game_url($game);
            if ($url) {
                $this->redirect($url);
            }
        }
        $this->redirect(array(
            '/home/index'
        ));
    }

    /**
     * NT电游
     * @param $game
     */
    public function actionNtLogout()
    {
        $is_login = $this->is_login();
        if ($is_login) {
            $nt = new NT($this->prefix, $this->playerName);
            if($nt->close_session()){
                echo 'true';
            }else{
                echo 'ok';
            }
        }else{
            $this->redirect(array(
            '/home/index'
            ));
        }
        
    }

    public function actionFishing($gameCode = "imfishing10001")
    {
        $user = $this->user;
        $gpn = $user['gpn'];
        $fishing = new IMFishing($gpn);
        $url = $fishing->launchGame($gameCode);
        $this->redirect($url);
//        echo "<a href='$url' target='_blank'>fishing</a>";
    }

    /**
     * 获取PT信息
     */
    public function actionPtinfo()
    {
        $url = 'http://ws-keryxr.imapi.net/casino/getjackpotlist/currency/CNY/producttype/0';
        $curl = new Curl();
        $curl->setHeader('merchantcode', 'hwKG7uGREvTjQoqXT1vhiG2uykS8zB6X');
        $curl->setHeader('merchantname', 'kzingprod');
        $curl->get($url);
        print_r(json_encode($curl->response));
//echo 'ok';
//echo $curl->http_status_code == 200 ? $curl->response : 'error';
    }


    public function actionImoslot($gameCode)
    {
        $is_login = $this->is_login();
        if ($is_login && IMOne::isEnable('550223423201')) {
            $imone = new IMOne($this->prefix, $this->playerName);
            $url = $imone->loginImoslot($gameCode);
            if ($url!=999) {
                $this->redirect($url);
                exit;
            }
        }
        $this->redirect(array(
            '/home/index'
        ));
    }


    public function actionImoslotDebug($gameCode)
    {
        $is_login = $this->is_login();
        if ($is_login && IMOne::isEnable('550223423201')) {
            $imone = new IMOne($this->prefix, $this->playerName);
            var_dump($imone->loginImoslotDebug($gameCode));
            exit;
        }
        $this->redirect(array(
            '/home/index'
        ));
    }


    public function actionImoslotfree($gameCode)
    {
        if (IMOne::isEnable('550223423201')) {
            $imone = new IMOne($this->prefix, '');
            $url = $imone->loginImoslotFree($gameCode);
            if ($url!=999) {
                $this->redirect($url);
                exit;
            }
        }
        $this->redirect(array(
            '/home/index'
        ));
    }





    public function actionImogg()
    {
        $is_login = $this->is_login();
        if ($is_login && IMOne::isEnable('550123423101')) {
            $imone = new IMOne($this->prefix, $this->playerName);
            $url = $imone->loginImogg();
            if ($url!=999) {
                $this->redirect($url);
                exit;
            }
        }
        $this->redirect(array(
            '/home/index'
        ));
    }


    public function actionImopt($gameCode = null)
    {
        $is_login = $this->is_login();
        if ($is_login && IMOne::isEnable('550323423302') && $gameCode!=null) {
            $imone = new IMOne($this->prefix, $this->playerName);
            $url = $imone->loginImopt($gameCode);
            if ($url!=999) {
                $this->redirect($url);
                exit;
            }
        }
        $this->redirect(array(
            '/home/index'
        ));
    }

        public function actionResetPwd()
    {
        $is_login = $this->is_login();
        if ($is_login) {
            $pwd = $this->post('pwd');
            $imone = new IMOne($this->prefix, $this->playerName);
            $result = $imone->reset_pwd($pwd);
            if ($result) {
                $result = json_decode($result, true);
                if ($result['Code'] == 0) {
                    $this->success();
                } else {
                    $this->fail($result['Message']);
                }
            } else {
                $this->fail();
            }
        }
        $this->fail('未登陆！');
    }
} 