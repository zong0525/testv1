<?php

/**
 * .

 * Date: 15/1/12
 * Time: 15:14
 */
class MemberController extends MController
{
    public function actionIndex()
    {
        $wdpassword = ApiConfig::cfgIsON('withdrawpassword');
        $has_pwd = !empty($this->user['field1']);
        $this->render('index', array('wdpassword' => $wdpassword, 'has_pwd' => $has_pwd));
    }

    public function actionBaseInfo()
    {
        $user = Member::findById($this->uid);
        $regconfig = ApiConfig::getApiConfig(2200);
        $acpCfg = new AcpConfig($regconfig);
        $has_birthday = $acpCfg->isON('regbirthday');
        $has_qq = $acpCfg->isON('regqq');
        $has_phone = $acpCfg->isON('reqphone');
        $has_email = $acpCfg->isON('regemail');
        $can_edit = $acpCfg->isON('usereditprofile');
        $this->renderPartial('baseInfo', array('user' => $user, 'has_birthday' => $has_birthday,
            'has_qq' => $has_qq, 'has_phone' => $has_phone, 'has_email' => $has_email, 'can_edit' => $can_edit));
    }

    public function showSwitchAttr($has_profile, $content, $msg)
    {
        if ($has_profile || !empty($content)) {
            return $content;
        } else {
            return $msg;
        }
    }

    public function actionWdPassword()
    {
        $has_pwd = !empty($this->user['field1']);
        $this->renderPartial('wdPassword', array('has_pwd' => $has_pwd));
    }

    public function actionChangeWdPassword()
    {
        $old = $this->post('oldPwd');
        $new = $this->post('newPwd');
        $rs = Member::changeWdPassword($this->uid, $old, $new);
        if ($rs > 0) {
            $this->success();
        } else {
            $this->fail();
        }
    }

    public function actionChangePwd()
    {
        $this->renderPartial('changePwd');
    }

    public function actionWtdCard()
    {
        $cards = WithdrawCard::getEnableCards($this->uid, Config::USER_TYPE);
        $bankdict = Fund::getBankDict();
        $this->renderPartial('wtdCard', array('cards' => $cards, 'bankdict' => $bankdict));
    }

    public function actionAddCard()
    {
        $bankcode = StrUtil::htmlentities($this->post('bankcode'));
        $card = StrUtil::htmlentities(trim($this->post('card', '')));
        $account = $this->post('account');
        $banknode = StrUtil::htmlentities($this->post('banknode'));
        $bank = Card::get_dict_info($bankcode);
        $user = $this->user;

        if (strcasecmp($account,html_entity_decode($user['realname'])) !== 0) {
            $this->fail('开户姓名与注册姓名不符!');
        }
        $account = StrUtil::htmlentities($account);
        $result = WithdrawCard::checkCard($bankcode, $card);
        if ($result->succ) {
            $rs = 0;
            if ($bank) {
                $rs = WithdrawCard::addCard($this->uid, Config::USER_TYPE, $bank['bankcode'], $bank['bankname'], $account, $card, '', '', $banknode, WithdrawCard::STATUS_VALID, time());
            }
            if ($rs > 0) {
                $this->success();
            } else {
                $this->fail();
            }
        } else {
            $this->fail($result->msg);
        }
    }

    public function actionCryptoguard()
    {
        $sqs = Member::getQuestions();
        $this->renderPartial('cryptoguard', array('sqs' => $sqs));
    }

    public function actionMessage($curIndex = 1)
    {
        //现在时粗放处理，进入该页面，直接算全部已读
        if ($this->getUnreadCount() > 0) {
            Message::update_user_message($this->uid);
        }
        $uid = $this->uid;
        $total = Message::get_user_message_count($uid);
        $page = new Pages($curIndex, $total);
        $message = Message::get_user_message_list($uid, $page->getOffset(), $page->pageCount);
        $this->render('message', array('message' => $message, 'page' => $page));
    }


    public function actionPromotions()
    {
        $user = $this->user;
        $activities = Activity::canJoinActivity($user['agentcode'], $user['groupid']);
        $joined = Activity::joinedActivity($user['playerid']);
        Activity::calc_status($activities, $joined);
        $this->render('promotions', array('activities' => $activities, 'joined' => $joined));
    }

    /**
     * 玩家前台
     */
    public function actionReport()
    {
        $uid = $this->uid;
        $start = $this->post('start');
        $end = $this->post('end');

        $start = DateUtil::startTime($start, DateUtil::firstSecondOfMonth());
        $end = DateUtil::endTime($end, time());

        $dep = Deposit::getAllSucess($uid, '1', $start, $end);
        $dep1 = Deposit::getAdjust($uid, $start, $end);

        $wtd = Withdraw::getAllSucess($uid, '1', $start, $end);
        $fee = Fee::get_fees($uid, $start, $end);
        $result = Report::user_platform_winloss($uid, $start, $end);

        $platform = Report::get_enable_gp_names();

        $gp_info = array();
        $bet = $win = 0;
        foreach ($result as $item) {
            $bet += $item['betamt'];
            $win += $item['win'];

            if (isset($platform[$item['gp_type']])) {
                $gp_info[] = array(
                    'name' => $platform[$item['gp_type']],
                    'bet' => $item['betamt'],
                    'win' => $item['win']
                );
            }
        }
        $dep = empty($dep['amount']) ? 0 : $dep['amount'];
        $wtd = empty($wtd['amount']) ? 0 : $wtd['amount'];

        $this->renderPartial('report', array('dep' => $dep, 'wtd' => $wtd, 'start' => $start,
            'end' => $end, 'fee' => $fee, 'bet' => $bet, 'win' => $win, 'gp_info' => $gp_info));
    }

    public function actionQq()
    {
        $regconfig = ApiConfig::getApiConfig(2200);
        $acpCfg = new AcpConfig($regconfig);
        $has_qq = $acpCfg->isON('regqq');
        $can_edit = $acpCfg->isON('usereditprofile');
        $qq = $this->post('qq','');
        $rs = new Result(false, '无法编辑基本信息');
        if ($can_edit) {
            $flag = true;
            if($has_qq || !empty($qq)){
                $flag = preg_match(Pattern::QQ, $qq);
            }

            if ($flag) {
                try {
                    Member::editQQ($this->uid, $qq);
                    $rs->succ = true;
                    $rs->data = StrUtil::replace($qq, 3);
                } catch (Exception $e) {
                    $rs->msg = '未知错误！';
                }
            } else {
                $rs->msg = 'QQ格式有误';
            }
        }
        $this->echoJson($rs);
    }

    public function actionMobile()
    {
        $regconfig = ApiConfig::getApiConfig(2200);
        $acpCfg = new AcpConfig($regconfig);
        $has_phone = $acpCfg->isON('reqphone');
        $can_edit = $acpCfg->isON('usereditprofile');

        $mobile = $this->post('mobile');
        $rs = new Result(false, '无法编辑基本信息');
        if ($can_edit) {
            $flag = true;
            if($has_phone || !empty($mobile)){
                $flag = preg_match(Pattern::MOBILE, $mobile);
            }
            if ($flag) {
                $rs = Member::editMobile($this->uid, $mobile);
                if ($rs->succ) {
                    $rs->data = StrUtil::mid_replace($mobile, 3, 4);
                }
            } else {
                $rs->msg = '手机号格式有误';
            }
        }
        $this->echoJson($rs);
    }

    public function actionEmail()
    {

        $regconfig = ApiConfig::getApiConfig(2200);
        $acpCfg = new AcpConfig($regconfig);
        $has_email = $acpCfg->isON('regemail');
        $can_edit = $acpCfg->isON('usereditprofile');

        $email = $this->post('email');
        $rs = new Result(false, '无法编辑基本信息');
        if ($can_edit) {
            $flag = true;
            if($has_email || !empty($email)){
                $flag = filter_var($email, FILTER_VALIDATE_EMAIL);
            }
            if ($flag) {
                $rs = Member::editEmail($this->uid, $email);
                if ($rs->succ) {
                    $rs->data = StrUtil::email_replace($email, 2, 2);
                }
            } else {
                $rs->msg = '邮箱格式有误';
            }
        }
        $this->echoJson($rs);
    }

    public function actionBirth()
    {
        $regconfig = ApiConfig::getApiConfig(2200);
        $acpCfg = new AcpConfig($regconfig);
        $has_birthday = $acpCfg->isON('regbirthday');
        $can_edit = $acpCfg->isON('usereditprofile');

        $birth = $this->post('birth');
        $rs = new Result(false, '无法编辑基本信息');
        if ($can_edit) {
            $flag = true;
            if($has_birthday || !empty($birth)){
                $flag = Helper::check_date($birth);
            }else{
                $birth = '0000-00-00';
            }
            if ($flag) {
                try {
                    list($year, $month, $day) = explode('-', $birth);
                    Member::editBirth($this->uid, $year, $month, $day);
                    $rs->succ = true;
                    $rs->data = StrUtil::replace($birth, 3, '*', STR_PAD_LEFT);
                } catch (Exception $e) {
                    $rs->msg = '未知错误！';
                }
            } else {
                $rs->msg = '日期格式有误';
            }
        }
        $this->echoJson($rs);
    }
} 