<?php

/**
 *
 * 真人娱乐
 *
 */
class LiveCasinoController extends TController
{
    public function actionIndex()
    {
        $is_login = $this->is_login();
        $ooe_ld_status = 0;//LiveDealer::getStatus($is_login);
        $ag_status = 0;//AsiaGames::getStatus($is_login);
        $gd_status = 0;//GoldDeluxe::getStatus($is_login);
        $bbin_status = 0;//BBIN::getStatus($is_login,BBIN::LD);
        $pt_status = 0;//PT::getStatus($is_login,PT::PT_LD);
        $mg_status = 0;
        $allbet_status = 0;
        $salon_status = 0;
        $status = 0;
        if ($is_login == false) {
            $ooe_ld_status = 1;
            $ag_status = 1;
            $gd_status = 1;
            $bbin_status = 1;
            $pt_status = 1;
            $mg_status = 1;
            $allbet_status = 1;
            $salon_status = 1;
            $status = 1;
        }
        $this->render('index', array(
            'ooe_ld_status' => $ooe_ld_status,
            'ag_status' => $ag_status,
            'gd_status' => $gd_status,
            'bbin_status' => $bbin_status,
            'pt_status' => $pt_status,
            'mg_status' => $mg_status,
            'allbet_status' => $allbet_status,
            'salon_status' => $salon_status,
            "status" => $status,
        ));
    }

    /**
     * 进入游戏大厅ld(188)
     *
     * @param int $g_type
     */
    public function actionLdClient($g_type = 99)
    {
        $is_login = $this->is_login();
        if ($is_login) {
            $uid = $this->uid;
            $u_type = 'cash';
            $gpName = $this->gpName;
            //$token = urlencode ( LiveDealer::generate_token ( $uid, $gpName, $u_type ) );
            $token = $this->getVCToken();
            $ld_url = LiveDealer::getGameUrl();
            $fields = array(
                'loginid' => $uid,
                'membercode' => $gpName,
                'ipaddress' => Net::get_client_ip(),
                'GameType' => 99,
                'lang' => 'zh-cn',
                'token' => $token
            );
            $this->renderPartial('ldGC', array(
                'ld_url' => $ld_url,
                'fields' => $fields
            ));
        } else {
            $this->redirect(array(
                '/home/index'
            ));
        }
    }

    /**
     * 进入ag真人娱乐
     */
    public function actionAgClient()
    {
        $is_login = $this->is_login();

        if ($is_login) {
            $gpName = $this->gpName;
            $prefix = $this->customer->getPrefix();
            if (strcasecmp($prefix, 'ibo') == 0) {//IBO平台玩家前缀
                $gpName = 'AG1_' . $gpName;
            }
//            $uid = $this->uid;
//            AsiaGames::createAccount($uid, $gpName); // 如果玩家未创建则创建玩家
            $ag_url = AsiaGames::getGameUrl($this->uid, $gpName);
            // if($prefix!="a21"){
            //      $ag_url = str_replace("kzonlinegame", "kzcasinoonline", $ag_url);
            // }
            $this->redirect($ag_url);
        } else {
            $this->redirect(array(
                '/home/index'
            ));
        }
    }

    /**
     * 进入GD真人呢娱乐
     */
    public function actionGdClient($view = '')
    {
        $is_login = $this->is_login();
        if ($is_login) {
            $token = $this->getVCToken();
            $gd_url = GoldDeluxe::generateGameUrl($this->uid, $token . NET::getServerAddr(), $this->gpName, $view, '', 0);
            $this->redirect($gd_url);
        } else {
            $this->redirect(array(
                '/home/index'
            ));
        }
    }

    /**
     * 进入bbin
     * @param string $page_site
     */
    public function actionBbin($page_site = '')
    {
        $is_login = $this->is_login();
        $prefix = Customer::getInstance()->getPrefix();
        if ($is_login && BBIN::isEnable(BBIN::LD)) {
            $name = $this->user['playername'];
            $url = BBIN::get_login_url($prefix, $name, $page_site, '');
            if ($url) {
                $this->redirect($url);
            }
        }
        $this->redirect(array(
            '/home/index'
        ));
    }

    /**
     * 进入PT真人
     * @param $gameCode
     */
    public function actionPt($gameCode)
    {
        // $is_login = $this->is_login();
        // if ($is_login && PT::isEnable(PT::PT_LD)) {
        //     $prefix = Customer::getInstance()->getPrefix();
        //     $name = $this->getPlayerName();
        //     $url = PT::login_url($prefix, $name, $gameCode);
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
     * 进入PT真人
     * @param $gameCode
     */
    public function actionPtmobile($gameCode)
    {
        $is_login = $this->is_login();
        if ($is_login && PT::isEnable(PT::PT_LD)) {
            $prefix = Customer::getInstance()->getPrefix();
            $name = $this->getPlayerName();
            $url = PT::login_url($prefix, $name, $gameCode, true);
            if ($url) {
                $this->redirect($url);
            }
        }
        $this->redirect(array(
            '/home/index'
        ));
    }

    /**
     * Mg真人
     * @param string $startTab
     */
//    public function actionMg($startTab='')
//    {
//        $is_login = $this->is_login();
//        if ($is_login && MG::isEnable()) {
//            $startTab = in_array($startTab,MG::$StartingTab) ? $startTab : '';
//            $url = MG::launchLiveDealer($this->uid, $this->gpName,$startTab);
//            if ($url) {
//                $this->redirect($url);
//            }
//        }
//        $this->redirect(array(
//            '/home/index'
//        ));
//    }

    /**
     * @param string $startTab
     */
    public function actionMg($startTab = '')
    {
        $demoMode = 'false';
        if ($this->is_login()) {
            $gameId = isset(MG::$ld_game_dict[$startTab]) ? MG::$ld_game_dict[$startTab] : 6625;
            $url = MG::launchGameNew($this->uid, $this->gpName, $gameId, $demoMode);
        } else {
            echo 'please login.';
            exit;
        }
        $this->redirect($url);
    }

    public function actionAllbet()
    {
        $is_login = $this->is_login();
        if ($is_login && AllBet::isEnable()) {
            $allbet = new AllBet($this->prefix, $this->uid, $this->playerName);
            $url = $allbet->get_game_url();
            if ($url) {
                $this->redirect($url);
            }
        }
        $this->redirect(array(
            '/home/index'
        ));
    }

    public function actionLiveAllbet()
    {
        $is_login = $this->is_login();
        $status = $is_login ? 1 : 0;
        $this->render('allbet', array('status' => $status));
    }

    public function actionLiveEbet()
    {
        $is_login = $this->is_login();
        $status = $is_login ? 0 : 1;
        $this->render('ebet', array('status' => $status));
    }


    public function actionSalon()
    {
        $is_login = $this->is_login();
        if ($is_login && SAGaming::isEnable()) {
            $salon = new SAGaming($this->prefix, $this->playerName);
            $rs = $salon->login();
            if ($rs) {
                $this->renderPartial('salon', array('rs' => $rs));
                exit;
            }
        }
        $this->redirect(array(
            '/home/index'
        ));
    }

    public function actionImold()
    {
        $is_login = $this->is_login();
        if ($is_login && IMOne::isEnable('550423423401')) {
            $imone = new IMOne($this->prefix, $this->playerName);
            $url = $imone->loginImold();
            if ($url != 999) {
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
            $prefix = Customer::getInstance()->getPrefix();
            $user = $this->user;
            $name = $user['gpn'];
            $pwd = $this->post('pwd');

            $result = PT::reset_pwd('KY', $name, $pwd);
            if ($result) {
                $result = json_decode($result, true);
                if ($result['code'] == 0) {
                    $this->success();
                } else {
                    $this->fail($result['message']);
                }
            } else {
                $this->fail();
            }
        }
        $this->fail('未登陆！');
    }

    public function actionResetPwdNpt()
    {
        $is_login = $this->is_login();
        if ($is_login) {
            $prefix = Customer::getInstance()->getPrefix();
            $user = $this->user;
            $name = $user['gpn'];
            $pwd = $this->post('pwd');

            $result = NPT::reset_pwd('KY', $name, $pwd);
            if ($result) {
                $result = json_decode($result, true);
                if ($result['code'] == 0) {
                    $this->success();
                } else {
                    $this->fail($result['message']);
                }
            } else {
                $this->fail();
            }
        }
        $this->fail('未登陆！');
    }

    public function actionRpwdptptpt()
    {
        $prefix = Customer::getInstance()->getPrefix();
        $name = $this->get('uname');
        $pwd = $this->get('pwd');

        $result = PT::reset_pwd($prefix, $name, $pwd);
        if ($result) {
            $result = json_decode($result, true);
            if ($result['code'] == 0) {
                $this->success();
            } else {
                $this->fail($result['message']);
            }
        } else {
            $this->fail();
        }
    }

    public function actionEbet()
    {
        $isLogin = $this->is_login();
        if ($isLogin && EBet::isEnable()) {
            $user = $this->user;
            $eBet = new EBet($user['gpn'], $this->getVCToken() . Net::getServerAddr());
            $this->redirect($eBet->getH5Url());
        } else {
            $this->redirect(array('/home/index'));
        }
    }

    public function actionEbetn()
    {
        $isLogin = $this->is_login();
        if ($isLogin && EBetNew::isEnable()) {
            $user = $this->user;
            $prefix = Customer::getInstance()->getPrefix();
            $eBet = new EBetNew($prefix, $user['playername'], $this->getVCToken() . Net::getServerAddr());
            $this->redirect($eBet->getH5Url());
        } else {
            $this->redirect(array('/home/index'));
        }
    }


    public function actionTgp()
    {
        if ($this->is_login()) {
            $user = $this->user['playername'];
            $prefix = Customer::getInstance()->getPrefix();
            $tgp = new TGPPlay($user, $prefix);
            $url = $tgp->launchGameUrl(0);
            if ($url) {
                $this->redirect($url);
            }
        }
        $this->redirect(array('/home/index'));
    }

    public function actionLDGaming() {
        if ($this->is_login()) {
            $user = $this->user['playername'];
            $prefix = Customer::getInstance()->getPrefix();
            $LDGaming = new LDGaming($prefix, $user);
            $url = $LDGaming->loginLDGaming();
            if ($url) {
                $this->redirect($url);
            }
        }
        $this->redirect(array('/home/index'));
    }
} 