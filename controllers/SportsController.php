<?php

/**
 * .
 * Date: 15/1/9
 * Time: 10:47
 */
class SportsController extends TController
{
    /**
     * 188体育
     */
    public function actionLd()
    {
        $prefix = Customer::getInstance()->getPrefix();
        if ($prefix == "a21" || $prefix == "a26" || $prefix == "a30" || $prefix == "c13") {
            $this->render('index', array('url' => '/home/wh', 'type' => 'ld', 'gpid' => ''));
        } else {
            $mid = urlencode(Sport::get_mid());
            $is_login = $this->is_login();
            if ($is_login) {
                $u_name = urlencode($this->getGpName());
                $token = $this->getVCToken();
                $params = sprintf('t=%s&l=%s&g=%s&tz=%s&mid=%s', urlencode($token), $u_name, 'CHS', 'GMT+08:00', $mid);
            } else {
                $params = sprintf('t=%s&l=%s&g=%s&tz=%s&mid=%s', '', '', 'CHS', 'GMT+08:00', $mid);
            }
            $url = Sport::generateGameUrl($params);
            if (Sport::getStatus($is_login) == 2) {
                $this->render('index', array('url' => '/home/wh', 'type' => 'ld', 'gpid' => ''));
            } else {
                $this->render('index', array('url' => $url, 'type' => 'ld', 'gpid' => '62207692669952'));
            }
        }
    }

    public function actionBbin()
    {
        $is_login = $this->is_login();
        $prefix = Customer::getInstance()->getPrefix();
        $status = BBIN::getStatus($is_login, BBIN::SB);
        if ($is_login && $status == GamePlatform::NOTIFY_NORMAL) {
            $name = $this->user['playername'];
            $url = BBIN::get_login_url($prefix, $name, 'ball');
            if ($url) {
                $this->redirect($url);
            }
        }
        $this->redirect(array(
            '/home/index'
        ));
    }

    /**
     * 沙巴体育
     */
    public function actionIndex()
    {
        $is_login = $this->is_login();
        if ($is_login) {
            $token = $this->getVCToken();
            if (SPON::getStatus($is_login) == 2) {
                $this->render('index', array('url' => '/home/wh', 'type' => 'spon', 'gpid' => ''));
            } else {
                $url = 'http://iframe.kzonlinegame.com/index.php?token=' . $token . NET::getServerAddr();
                $this->render('index', array('url' => $url, 'type' => 'spon', 'gpid' => '8246252097638400'));
            }
        } else {
            $this->render('index', array('url' => 'http://mkt.kzonlinegame.com/vender.aspx?lang=zhcn', 'type' => 'spon', 'gpid' => '8246252097638400'));
        }
    }

    public function actionBb()
    {
        $is_login = $this->is_login();
        $bbin_status = BBIN::getStatus($is_login, BBIN::SB);
        $this->render('bb', array('bbin_status' => $bbin_status));
    }


    public function actionEsb()
    {
        $this->render('esb');
    }

    public function actionStag8()
    {
        $this->render('stag8');
    }

    public function actionLdGuide()
    {
        $this->render('ldGuide');
    }

    public function actionLive()
    {
        $this->render('live');
    }

    public function actionEpl()
    {
        $this->render('epl');
    }


    /**
     * 老皇冠体育
     */
    public function actionHg()
    {
        // $is_login = $this->is_login();
        // if ($is_login) {
        //     if (Crown::getStatus($is_login) == GamePlatform::NOTIFY_NORMAL) {
        //         $u_name = $this->getGpName();
        //         $url = Crown::getGameUrl($u_name,false);
        //         if($url="Error"){
        //             $url = Crown::getGameUrl($u_name,true);
        //         }
        //         $this->render('index', array('url' => '/home/wh', 'type' => 'hg', 'gpid' => '7229552204062'));
        //     }else{
        //         $this->render('index', array('url' => '/home/wh', 'type' => 'hg', 'gpid' => '7229552204062'));
        //     }
        // } else {
        //     $this->render('index', array('url' => '/home/wh', 'type' => 'hg', 'gpid' => '7229552204062'));
        // }
        // $this->render('index', array('url' => '/home/wh', 'type' => 'hg', 'gpid' => '7229552204062'));

        $is_login = $this->is_login();
        if ($is_login) {
            $user = $this->user;
            $sxing = new SXing($user['gpn']);
            $url = $sxing->getGameUrl();
            // $this->redirect($url);
            $this->render('index', array('url' => $url, 'type' => 'hgs', 'gpid' => '167695449110'));
        } else {
            // $this->redirect('http://hqo36.uv128.com/wqb/view.php');
            $this->render('index', array('url' => 'http://hqo36.uv128.com/wqb/view.php', 'type' => 'hgs', 'gpid' => '167695449110'));
        }

    }

    /**
     * 3s体育
     */
    public function actionHgs()
    {   //ac167695449110   gp167695449111
        $is_login = $this->is_login();
        if ($is_login) {
            $user = $this->user;
            $sxing = new SXing($user['gpn']);
            $url = $sxing->getGameUrl();
            // $this->redirect($url);
            $this->render('index', array('url' => $url, 'type' => 'hgs', 'gpid' => '167695449110'));
        } else {
            // $this->redirect('http://hqo36.uv128.com/wqb/view.php');
            $this->render('index', array('url' => 'http://hqo36.uv128.com/wqb/view.php', 'type' => 'hgs', 'gpid' => '167695449110'));
        }
    }

    /**
     * IM体育
     */
    public function actionIm()
    {
        $is_login = $this->is_login();
        if (IMSportsbook::getStatus($is_login) == 2) {
            $this->render('index', array('url' => '/home/wh', 'type' => 'ld', 'gpid' => ''));
        } else {
            $gpid = '5398046578160';
            $lan = 'chs';
            $syslan = FLanguage::Get_L_Code();
            if ($syslan != 'cn') {
                $lan = $syslan;
            }
            if ($is_login) {
                $token = $this->getVCToken();
                $name = $this->user['playername'];
                $token = $token . NET::getServerAddr();
                $im = new IMSportsbook($this->prefix, $name, $token);
                $result = $im->login($tm);
                if ($result->succ) {
                    $url = 'http://imsports.kzonlinegame.com/?timestamp=' . $tm . '&token=' . $token . '&LanguageCode=' . $lan;
                    $this->render('index', array('url' => $url, 'type' => 'im', 'gpid' => $gpid));
                } else {
                    $this->render('index', array('url' => 'http://imsports.kzonlinegame.com?LanguageCode=' . $lan, 'type' => 'im', 'gpid' => $gpid));
                }
            } else {
                $this->render('index', array('url' => 'http://imsports.kzonlinegame.com?LanguageCode=' . $lan, 'type' => 'im', 'gpid' => $gpid));
            }
        }
    }

    /**
     * 188体育
     */
    public function actionOee()
    {
        $canIn = Customer::getInstance()->prefixCmp(array('a1', 'd11'));

        if ($canIn) {
            $is_login = $this->is_login();//判断玩家是否登录
            $gpn = $token = 0;
            if ($is_login) {
                $user = $this->user;
                $gpn = $user['gpn'];//玩家gpn
                $token = $this->getVCToken();
            }
            $oee = new NewOeeSport($gpn, $token, $is_login);
            $url = $oee->getGameUrl();//获取进入游戏URL
            if (NewOeeSport::getStatus($is_login) == 2) {//判断游戏是否在维护中，0：代表正常，2：维护中，
                $this->render('index', array('url' => '/home/wh', 'type' => 'ld', 'gpid' => ''));
            } else {
                $this->render('index', array('url' => $url, 'type' => 'ld', 'gpid' => '61005672349801'));
            }
        } else {
            $this->render('index', array('url' => '/home/wh', 'type' => 'ld', 'gpid' => ''));
        }
    }
}
