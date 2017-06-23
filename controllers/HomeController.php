<?php


class HomeController extends TController
{
    public function filters()
    {
        return array(
            'accessControl',
        );
    }

    public function actionLogin()
    {
        if (!$this->non_login()) {
            $this->redirect('/home/index');
        } else {
            $this->set_login_layout();
            $this->render('login');
        }
    }


    private function set_login_layout()
    {
        if ($this->isLoginLayoutCustomer()) {
            $this->layout = 'login';
        }
    }

    private function isLoginLayoutCustomer()
    {
        return isset(PrefixFilter::$LoginLayout[$this->prefix]);
    }

    /**
     * 访问规则
     * @return array
     */
    public function accessRules()
    {
        return array(
            array('deny',
                'actions' => array('fetchPassword', 'resetPassword', 'send', 'check'),
                'expression' => array($this, 'is_login'),
            ),
        );
    }

    /**
     * 系统维护页面
     */
    public function actionMaintain()
    {
        $this->renderPartial('maintain');
    }

    public function actionC()
    {
        $this->render('linecheck');
    }


    public function actionPhoneGame()
    {
        $this->render('phonegame');
    }
    
    //pina add 全新的页面
    public function actionAwards()
    {
        $this->renderPartial('awards');
    }

    public function actionIndex()
    {
        $customer = Customer::getInstance();

        $code = $this->get("code") ? $this->get("code") : "";
        $lng = $this->get("lng") ? $this->get("lng") : "";

        $status = $this->get_game_status();

        //单独处理
        $acpid = $customer->getPrefix();
        if ($acpid == 'c11' && $this->is_login()) {
            $this->redirect('/lottery/keno');
        }

        $is_mobile = Net::is_mobile_request();//是否使用手机访问
        $domain = $_SERVER['HTTP_HOST'];
        //如果是代理的固定域名则跳转到合营
        $agentd = "ag." . $customer->getTLD();
        if ($domain == $agentd) {
            $this->redirect('/agent/home/index');
        }
        if ($is_mobile) {
            $mobilecode = '/account/reg?code=' . $code;
            if ($code == "") $mobilecode = '';


            $mobileDomain = $customer->getMDomain();
            if (!empty($mobileDomain)) {
                $dcode = AgentManage::getAgentCodeByDomain($domain);
                if ($dcode != 10000) {
                    $mobilecode = '/account/reg?code=' . $dcode;
                }
                $this->redirect('http://' . $mobileDomain . $mobilecode);
            }
        }


        if ($code == "") {

            //如果没有code的情况，且在开关打开的情况下，要考虑是否是第一次访问，如果是的话则需要跳转到注册页
            $autoredirect = ApiConfig::getApiCfgByKey('autoredirect');
            $autoredirect = empty($autoredirect) ? 'OFF' : $autoredirect['itemval'];
            if ($autoredirect == 'ON') {
                $cookie = Yii::app()->request->getCookies();
                $first = isset($cookie['_first']) ? $cookie['_first']->value : 'true';
                if ($first == 'true') {
                    $cookie = new CHttpCookie('_first', 'false');
                    $cookie->expire = time() + 60 * 60 * 24 * 12;
                    Yii::app()->request->cookies['_first'] = $cookie;
                    $this->redirect('/home/register');
                }
            }

            if ($this->isLoginLayoutCustomer() && $this->non_login()) {
                $this->redirect(array('/home/login'));
            }

            if ($acpid == "a17") {
                $is_login = $this->is_login();
                $pl_status = Phoenix::getStatus($is_login);
                $status['pl_status'] = $pl_status;
                $this->render('index', $status);
            } else if ($acpid == "a18") {
                if ($lng == "") {
                    $this->render('index', $status);
                } else {
                    $this->render($lng);
                }
            } else {
                $this->render('index', $status);
            }
        } else {
            $this->redirect('/home/register?code=' . $code);
        }


    }

    private function get_game_status()
    {
        $is_login = $this->is_login();
        $mg_status = $bbin_status = $pt_status = $nt_status = $ag_status = 0;
        if ($is_login == false) {
            $mg_status = $bbin_status = $pt_status = $nt_status = $ag_status = 1;
        }
        return array('mg_status' => $mg_status,
            'bbin_status' => $bbin_status,
            'pt_status' => $pt_status,
            'nt_status' => $nt_status,
            'ag_status' => $ag_status);
    }

    /**
     *
     */
    public function actionUuid()
    {
        if ($this->is_login()) {
            $uuid_key = '__uuid';
            $uuid = Helper::get_cookie($uuid_key);
            if (empty($uuid)) {
                $uuid = md5(StrUtil::uuid());
                Helper::set_cookie($uuid_key, $uuid, 31536000, Net::get_tld());
            }
            UUID::insert($this->uid, $uuid, 1, time());
        }
    }


    private function set_reg_layout()
    {
        if ($this->isLoginLayoutCustomer()) {
            $this->layout = 'reg';
        }
    }

    /**
     * @param string $code
     */
    public function actionRegister($code = '')
    {
        //$questions = Member::getQuestions();
		$is_login = $this->is_login();
		if($is_login){
			$this->redirect('/member/index');
		}
		
        $domain = $_SERVER['HTTP_HOST'];
        $dcode = AgentManage::getAgentCodeByDomain($domain);

        $code = $this->getCode($code);
        if ($dcode != 10000) {
            $code = $dcode;
        }
        $this->set_reg_layout();
        $view = $this->get_reg_view();
        $regconfig = ApiConfig::getApiConfig(2200);
        $wdpasswordcfg = ApiConfig::getApiCfgByKey('withdrawpassword');

        $regbirthday = 'OFF';
        $regqq = 'OFF';
        $reqphone = 'OFF';
        $regemail = 'OFF';
        $wdpassword = 'OFF';
        $checkfullname = 'OFF';

        if (!empty($regconfig['regbirthday'])) {
            $regbirthday = $regconfig['regbirthday'];
        }
        if (!empty($regconfig['regqq'])) {
            $regqq = $regconfig['regqq'];
        }
        if (!empty($regconfig['reqphone'])) {
            $reqphone = $regconfig['reqphone'];
        }
        if (!empty($regconfig['regemail'])) {
            $regemail = $regconfig['regemail'];
        }
        if (!empty($regconfig['checkrealname'])) {
            $checkfullname = $regconfig['checkrealname'];
        }
        if ($wdpasswordcfg) {
            $wdpassword = $wdpasswordcfg['itemval'];
        }
        $this->render($view, array(
            //'questions'=>$questions,
            'code' => $code,
            'regbirthday' => $regbirthday,
            'regqq' => $regqq,
            'reqphone' => $reqphone,
            'regemail' => $regemail,
            'wdpassword' => $wdpassword,
            'checkfullname' => $checkfullname,
        ));
    }

    /**
     * 获取玩家注册页面模板
     * @return string
     */
    private function get_reg_view()
    {
        if (!in_array($this->prefix, array('a9', 'a15', 'a29'))) {
            $view = '_register';
        } else {
            $view = Customer::getInstance()->getTheme();
        }
        return 'register/' . $view;
    }

    /**
     * 玩家注册，获取代理编码
     * @param $code
     * @return string
     */
    private function getCode($code)
    {
        $codeKey = '_code_cookie';
        if ($code) {
            $cookie = new CHttpCookie($codeKey, $code);
            $cookie->expire = time() + 60 * 60 * 24;  //有限期30天
            Yii::app()->request->cookies[$codeKey] = $cookie;
            return $code;
        } else {
            $cookie = Yii::app()->request->getCookies();
            return isset($cookie[$codeKey]) ? $cookie[$codeKey]->value : '';
        }
    }

    /**
     * 玩家取回密码界面
     */
    public function actionFetchPassword()
    {
        $this->set_reg_layout();
        $mail = ApiConfig::getMailConf();
        $cs_func = $this->getCsFunc();
        $this->render('fetchPassword', array('mail' => $mail, 'cs_func' => $cs_func));
    }

    /**
     * 玩家取回密码
     * @param $name
     * @param $email
     */
    public function actionCheck($name, $email)
    {
        $resetPwd = new ResetPwd($name, $email, ResetPwd::TYPE_USER);
        $tag = $resetPwd->cacheCheck($msg);
        if ($tag == ResetPwd::CACHE_CHECK_FAIL) {
            $flag = Member::validNameEmail($name, $email);
            if ($flag) {
                $resetPwd->setPwdCache();
                $this->success('邮件已经发送到邮箱！');
            } else {
                $this->fail('用户名或者邮箱地址错误！');
            }
        } elseif ($tag == ResetPwd::SEND_OVER_COUNT) {
            $this->fail($msg);
        } elseif ($tag == ResetPwd::CACHE_CHECK_SUCCESS) {
            $this->success('邮件已经发送到邮箱！');
        }

    }

    /**
     * 发送重置密码邮件
     */
    public function actionSend()
    {
        $name = $this->post('name');
        $email = $this->post('email');
        $resetPwd = new ResetPwd($name, $email, ResetPwd::TYPE_USER);
        $flag = $resetPwd->emailCountCheck($msg);
        if ($flag) {
            $token = $resetPwd->getToken();
            $model = array('name' => $name, 'token' => $token, 'type' => ResetPwd::TYPE_USER);
            $pathViews = 'webroot.themes.' . $this->customer->getTheme() . '.views';
            $pathLayouts = 'webroot.themes.' . $this->customer->getTheme() . '.views.layouts';
            MailHelper::sendHtmlMail($email, $model, $pathViews, $pathLayouts, ResetPwd::EMAIL_VIEW);
            $this->success('邮件已经发送，请注意查收！');
        } else {
            $this->fail($msg);
        }
    }

    public function actionResetPassword($token)
    {
        $this->render('resetPassword', array('token' => $token));
    }

    /**
     * 重置密码
     */
    public function actionReset()
    {
        $token = $this->post('token');
        $pwd = $this->post('pwd');
        $redis = RedisUtil::getInstance();
        $resetKey = $redis->get($token);
        if ($resetKey) {
            $resetInfo = $redis->hGetAll($resetKey);
            $now = time();
            if ($resetInfo && $resetInfo['token'] == $token && ($resetInfo['time'] - $now) < ResetPwd::RESET_PWD_TIMEOUT) {
                $name = $resetInfo['name'];
                $email = $resetInfo['email'];
                $resetPwd = new ResetPwd($name, $email, ResetPwd::TYPE_USER);
                $flag = $resetPwd->resetPwd($token, $pwd);
                if ($flag) {
                    $this->success('密码重置成功！');
                } else {
                    $this->fail('取回密码失败！');
                }
            } else {
                $this->fail('密码重置连接失效，请重新获取！');
            }
        } else {
            $this->fail('密码重置连接失效，请重新获取！');
        }
    }

    /**
     * 获取加密key
     */
    public function actionGetKey()
    {
        $redis = RedisUtil::getInstance();
        $pub_key = $redis->hGet('reset_pwd_key', 'pubKey');
        if (!$pub_key) {
            $res = openssl_pkey_new();
            openssl_pkey_export($res, $pri_key);
            $d = openssl_pkey_get_details($res);
            $pub_key = $d['key'];//公钥
            $redis->hSet('reset_pwd_key', 'pubKey', $pub_key);
            $redis->hSet('reset_pwd_key', 'priKey', $pri_key);
        }
        $this->echoJson(array('pubKey' => $pub_key));
    }

    public function actionEnableName($joiname)
    {
        if (substr($joiname, 0, 5) == "wl24a") {
            echo 'false';
        } else {

            $forbiddennames = Member::forbiddennames();
            if ($forbiddennames != "") {
                $arraynames = explode(',', $forbiddennames);
                if (in_array($joiname, $arraynames)) {
                    echo 'false';
                    exit;
                }
            }

            echo Member::enable_name($joiname) ? 'true' : 'false';
        }
    }


    public function actionCheckFullname($fullname)
    {
        echo Member::enable_fullname($fullname) ? 'true' : 'false';
    }


    public function actionEnableMobile($uphone)
    {
        echo Member::enable_mobile($uphone, Config::USER_TYPE) ? 'true' : 'false';
    }

    public function actionEnableEmail($email)
    {
        echo Member::enable_email($email, Config::USER_TYPE) ? 'true' : 'false';
    }

    public function actionCheckRealName($name)
    {
        $user = $this->user;
        if ($user) {
            echo $user['realname'] == trim($name) ? 'true' : 'false';
            exit;
        }
        echo 'false';
        exit;
    }

    public function actionLogout()
    {
        AuthUser::logout($this->uid);
        $this->redirect('/home/index');
    }

    public function actionGame()
    {
        $url = "";
        if (isset($_POST['url'])) {
            $url = $_POST['url'];
        }
        $this->renderPartial('loadgame', array("url" => $url));
    }

    public function actionLunch()
    {
        $is_login = $this->is_login();
        if ($is_login) {
            $url = $this->get("url") ? $this->get("url") : "";
            $gpid = $this->get("gpid") ? $this->get("gpid") : "";
            $accid = $this->get("accid") ? $this->get("accid") : "";

            if ($url == "" && $gpid == "") {
                echo "error";
                exit;
            }
            $this->renderPartial('lunch', array("url" => $url, "gpid" => $gpid, "accid" => $accid));
        } else {
            $this->redirect('/home/index');
        }

    }

    //获取未读短信条数
    public function actionUnRead()
    {
        echo $this->getUnreadCount();
    }

    public function actionError()
    {
        if ($error = Yii::app()->errorHandler->error) {
            if (Yii::app()->request->isAjaxRequest)
                echo $error['message'];
            else
                $this->render('error', $error);
        }
    }

    public function actionForbidden()
    {
        $this->renderPartial('forbidden');
    }

    public function actionWh()
    {
        $this->renderPartial('wh');
    }

    public function actionTicker()
    {
        $this->renderPartial('ticker');
    }


    public function actionTicker2()
    {
        $this->renderPartial('ticker2');
    }

    public function actionInfo()
    {
        $this->renderPartial('info');
    }

    public function actionAgentNotice()
    {
        $this->renderPartial('agentNotice');
    }

    public function actionMobile()
    {
        $prefix = $this->getPrefix();
        $user = $this->getUser();
        if ($user) {
            $name = $user["playername"];
        } else {
            $name = "玩家名称";
        }

        $this->render('mobile', array("prefix" => $prefix, "name" => $name));
    }


    public function actionEnableAgent($agc)
    {
        if ($agc == "true") {
            echo 'true';
        } else {
            if (!preg_match(Pattern::AGENT_CODE, $agc)) {
                echo 'false';
            } else {
                $status = Agent::getStatusByCodes($agc);
                if ($status != 99 && $status != 12) {
                    echo 'false';
                } else {
                    echo 'true';
                }
            }
        }
    }


    public function actionGs()
    {
        $gpid = $gpid = $this->post("g") ? $this->post("g") : "";
        if ($gpid == "") {
            echo "false";
            exit;
        } else {
            $is_login = $this->is_login();
            if ($is_login) {
                $status = "false";
                switch ($gpid) {
                    case '285467739648'://'凤凰彩票'
                        $status = Phoenix::getStatus($is_login);
                        break;
                    case '451819984710'://'PT真人'
                        $status = PT::getStatus($is_login, PT::PT_LD);
                        break;
                    case '451819984711'://'PT电子游戏'
                        $status = PT::getStatus($is_login, PT::PT_RE);
                        break;
                    case '39500154618880'://'小金厅'
                        $status = LiveDealer::getStatus($is_login);
                        break;
                    case '350808494186210'://'BB体育'
                        $status = BBIN::getStatus($is_login, BBIN::SB);
                        break;
                    case '350808494186211'://'BB真人'
                        $status = BBIN::getStatus($is_login, BBIN::LD);
                        break;
                    case '350808494186212'://'BB彩票'
                        $status = BBIN::getStatus($is_login, BBIN::LT);
                        break;
                    case '350808494186213'://'BB3D'
                        $status = BBIN::getStatus($is_login, BBIN::TD);
                        break;
                    case '350808494186214'://'BB机率'
                        $status = BBIN::getStatus($is_login, BBIN::CH);
                        break;
                    case '3277767810617344'://'GD厅'
                        $status = GoldDeluxe::getStatus($is_login);
                        break;
                    case '5707231341449216'://'Libianc快乐彩'
                        $status = Keno::getStatus($is_login);
                        break;
                    case '8246252097638400'://'沙巴体育'
                        $status = SPON::getStatus($is_login);
                        break;
                    case '11964220589608960'://'MG电子游戏'
                        $status = MG::getStatus($is_login);
                        break;
                    case '38712217599873024'://'AG厅'
                        $status = AsiaGames::getStatus($is_login);
                        break;
                    case '62207692669952'://'188体育'
                        $status = Sport::getStatus($is_login);
                        break;
                    case '72295522040621'://'皇冠体育'
                        $status = Crown::getStatus($is_login);
                        break;
                }


                if ($status == 0) {
                    echo "true";
                } else {
                    echo "false";
                }
                exit;

            } else {
                $this->redirect('/home/index');
            }
        }
    }


    public function actionLoginInfo()
    {
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            echo 'HTTP_CLIENT_IP';
            echo var_dump($_SERVER['HTTP_CLIENT_IP']);
            echo '<br>';
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            echo 'HTTP_X_FORWARDED_FOR';
            echo var_dump($_SERVER['HTTP_X_FORWARDED_FOR']);
            echo '<br>';
        }
        if (isset($_SERVER['HTTP_X_FORWARDED'])) {
            echo 'HTTP_X_FORWARDED';
            echo var_dump($_SERVER['HTTP_X_FORWARDED']);
            echo '<br>';
        }
        if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            echo 'HTTP_FORWARDED_FOR';
            echo var_dump($_SERVER['HTTP_FORWARDED_FOR']);
            echo '<br>';
        }
        if (isset($_SERVER['HTTP_FORWARDED'])) {
            echo 'HTTP_FORWARDED';
            echo var_dump($_SERVER['HTTP_FORWARDED']);
            echo '<br>';
        }
        if (isset($_SERVER['REMOTE_ADDR'])) {
            echo 'REMOTE_ADDR';
            echo var_dump($_SERVER['REMOTE_ADDR']);
            echo '<br>';
        }

        //echo 'lastip:'.$ip."<br>";


        $customer = Customer::getInstance();
        $acpid = $customer->getPrefix();//接入商前缀


        $cc = 'cc' . $acpid;
        $vc = 'vc' . $acpid;
        $flag = false;
        if (!empty($_COOKIE[$cc]) && !empty($_COOKIE[$vc])) {
            $token = $_COOKIE[$cc];

            $salt = substr($token, 0, 6);
            $token = substr($token, 6);
            $token = str_replace('_', '+', $token);


            $token = EncryptUtil::authcode($token, 'DECODE', 'fad6e56f3be09200aaca65e76c' . $salt);


            $temp = preg_split('/\s+/', $token);
            if (count($temp) == 3) {
                list($acpid, $id, $atime) = $temp;//atime为最后登录时间
                $redis = RedisUtil::getInstance();
                $member = $redis->hGetAll($acpid . ':' . 'user:' . $id);
                $c_token = substr($_COOKIE[$vc], 0, 46);//
                if ($member && $member['uotoken'] == $c_token) {
                    $ckey = $acpid . ':uotoken:' . $c_token;//获取token中保存的信息
                    $t_info = $redis->hGetAll($ckey);

                    if ($t_info) {
                        $lastIp = $t_info['lastip'];
                        $lastlogin = $t_info['lastlogin'];
                        $lastactivity = $t_info['lastactivity'];

                        $ip = Net::get_client_ip();
                        echo '$Net::get_client_ip():' . $ip;
                        echo '<br>$lastIp:' . $lastIp;
                        echo '<br>$temp:';
                        echo var_dump($temp);

                        echo '<br>$lastlogin:' . $lastlogin;
                        echo '<br>$atime:' . $temp[2];
                        echo '<br>$time:' . time();
                        echo '<br>$lastactivity:' . $lastactivity;

                        if ($lastIp == Net::get_client_ip()) {
                            echo '<br>ipok';
                        } else {
                            echo '<br>ipnotok';
                        }

                        if ($lastlogin == $atime) {
                            echo '<br>lastloginok';
                        } else {
                            echo '<br>lastloginnotok';
                        }

                        if ((time() - $lastactivity) < 1800) {
                            echo '<br>logintimeok';
                        } else {
                            echo '<br>logintimenotok';
                        }

                        exit;
                    } else {
                        echo 'noinfo:' . $ckey;
                        exit;
                    }
                }
            }
        }
    }

    /**
     * @param $type
     * @param $id
     */
    public function actionGetTeamInfo($type, $id)
    {

        if (empty($type) || empty($id)) {
            echo null;
            exit;
        }
        if (Net::isJPN()) {
            $url = 'http://192.168.128.47?type=' . $type . "&id=" . $id;
        } else {
            $url = 'http://106.185.38.103?type=' . $type . "&id=" . $id;
        }
        $result = $this->request($url);
        echo $result;
    }

    private function request($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

} 