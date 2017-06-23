<?php

 
class FundController extends MController
{
 
    public function actionIndex()
    {
        $this->render('index');
    }

    /**
     * 转账
     */
    public function actionTransfer()
    {
        $platforms = GamePlatform::get_all_account_dict();
        $this->renderPartial('transfer', array('platforms' => $platforms));
    }

    public function actionFinishDpt()
    {
        $status = $this->post('status');
        try {
            $result = Fund::finishDpt($this->uid, $status);
            if ($result == 1) {
                $this->success('操作成功！');
            }
        } catch (Exception $e) {
            Yii::log($e->getMessage(), 'error');
        }
        $this->fail('操作失败');
    }

    private function bank_unique($banks)
    {
        $bankcodes = array();
        $temps = array();
        foreach ($banks as $key => $val) {
            $bankcode = $val['bankcode'];
            if (in_array($bankcode, $bankcodes)) {
                continue;
            } else {
                $bankcodes[] = $bankcode;
                $temps[] = $val;
            }
        }
        return $temps;
    }

    /**
     * 存款
     */
    public function actionDeposit()
    {
        // 獲取登錄用戶ID
        $uid = $this->getUid();
        $realname = $this->getUser();
        $realname = $realname['realname'];
        $realname = StrUtil::cut($realname, 1, "**"); //substr($realname, 0,1)."**";


        // 通過用戶ID找到用戶所在的群組
        $groupId = Fund::getGroupId($uid);
        // 通過群組ID找到這個群組對應的所有銀行
        $banks = Fund::getBanks($groupId);
        // 获取所有可用银行
        $allbanks = Fund::getBankDictWY();


        $ebanks = array_filter($banks, function ($v) {
            return $v['showfield'] == 1 || $v['showfield'] == 2;
        });

        $atms = array_filter($banks, function ($v) {
            return $v['showfield'] == 1 || $v['showfield'] == 3;
        });

        $ebanks = $this->bank_unique($ebanks);
        $atms = $this->bank_unique($atms);

        // 查找用戶是否已經有存款未處理
        $process = Fund::getDepositStatus($uid);//最新的玩家未反馈的存款申请
        $process_count = Fund::getDepositProcCount($uid);//客服未审核的存款条数
        $cfg = ApiConfig::getApiCfgByKey('allowprocesscount');
        $allowcount = empty($cfg) ? 1 : (int)$cfg['itemval'];

        $allowdeposit = true;    //是否可以进行存款
        if ($allowcount > 0) {
            $allowdeposit = $allowcount > $process_count;
        }

        // 查找可用的第三方支付
        $tps = Fund::getTpAccounts($groupId);

        $cfg = ApiConfig::getApiConfig(ApiConfig::DEPOSIT_CATE);


        $emin = '100';
        $emax = '50000';
        $amin = '100';
        $amax = '50000';
        $onmin = '100';
        $onmax = '50000';

        if (!empty($cfg)) {
            $emin = $cfg['depositofebankmin'];
            $emax = $cfg['depositofebankmax'];
            $amin = $cfg['depositofatmmin'];
            $amax = $cfg['depositofatmmax'];
            $onmin = $cfg['depositofopaymin'];
            $onmax = $cfg['depositofopaymax'];
        }

        $this->renderPartial('deposit', array(
            'ebanks' => $ebanks,
            'allbanks' => $allbanks,
            'realname' => $realname,
            'atms' => $atms,
            'process' => $process,
            'tps' => $tps,
            'emin' => $emin,
            'emax' => $emax,
            'amin' => $amin,
            'amax' => $amax,
            'onmin' => $onmin,
            'onmax' => $onmax,
            'allowdeposit' => $allowdeposit,
            'prefix' => $this->prefix,
        ));
    }

    /**
     * 取款
     */
    public function actionWithdraw()
    {
        $uid = $this->getUid();
        $withdrawbanks = Fund::getWdBank($uid);
        $bankdict = Fund::getBankDict();
        $haspending = Fund::getPending($uid);

        $cfg = ApiConfig::getApiConfig(ApiConfig::WITHDRAW_CATE);
        $winfo = '';
        $wmin = '0';
        $wmax = '100';
        $wdotyn = 'ON';
        $watercheckonoff = 'OFF';
        $user = $this->user;
        $wdpassword = ApiConfig::cfgIsON('withdrawpassword');
        $wdpassword = $wdpassword && !empty($user['field1']);
        if (!empty($cfg)) {
            $winfo = '单笔取款范围' . $cfg['withdrawmin'] . '元至' . $cfg['withdrawmax'] . '元。每天最高取款次数：' . $cfg['withdrawdaylimit'] . '次';
            $wmin = $cfg['withdrawmin'];
            $wmax = $cfg['withdrawmax'];
            if (isset($cfg['withdrawdotyn'])) $wdotyn = $cfg['withdrawdotyn'];
            if (!empty($cfg['watercheckonoff'])) {
                $watercheckonoff = $cfg['watercheckonoff'];
            }
        }

        $this->renderPartial('withdraw', array(
                'wdbanks' => $withdrawbanks,
                'bankdict' => $bankdict,
                'haspending' => $haspending['c'],
                'winfo' => $winfo,
                'wmin' => $wmin,
                'wmax' => $wmax,
                'wdotyn' => $wdotyn,
                'watercheckonoff' => $watercheckonoff,
                'wdpassword' => $wdpassword,

            )
        );
    }

    public function actionWater()
    {
        $uid = $this->uid;
        $waters = Water::get_user_undone_water($uid);
        $platforms = GamePlatform::get_all_gp_dict();
        $this->renderPartial('water', array('waters' => $waters, 'platforms' => $platforms));
    }

    /**
     * @param int $curIndex
     * @param string $start
     * @param string $end
     */
    public function actionWHistory($curIndex = 1, $start = '', $end = '')
    {
        $uid = $this->uid;
        $start = !empty($start) ? DateUtil::beginOfDay($start) : 0;
        $end = !empty($end) ? DateUtil::endOfDay($end) : 0;
        $history = Withdraw::get_history_info($uid, Config::USER_TYPE, $curIndex, $start, $end);
        $this->renderPartial('wHistory', $history);
    }


    /**
     * @param int $curIndex
     * @param int $status
     * @param int $type
     * @param string $start
     * @param string $end
     */
    public function actionDHistory($curIndex = 1, $status = 0, $type = 0, $start = '', $end = '')
    {
        $uid = $this->uid;
        $start = !empty($start) ? DateUtil::beginOfDay($start) : 0;
        $end = !empty($end) ? DateUtil::endOfDay($end) : 0;
        $history = Deposit::get_history_info($uid, Config::USER_TYPE, $curIndex, $start, $end, $status, $type);
        $this->renderPartial('dHistory', $history);
    }

    /**
     * 获取转账信息
     * @param int $curIndex
     * @param $out_id
     * @param $in_id
     * @param int $start
     * @param int $end
     * @param int $status
     */
    public function actionTHistory($curIndex = 1, $out_id = 0, $in_id = 0, $start = 0, $end = 0, $status = 0)
    {
        $uid = $this->uid;
        $start = !empty($start) ? DateUtil::beginOfDay($start) : 0;
        $end = !empty($end) ? DateUtil::endOfDay($end) : 0;
        $history = Transfer::get_history_info($curIndex, $uid, $out_id, $in_id, $start, $end, $status);
        $platforms = GamePlatform::get_all_account_dict();
        $history['platforms'] = $platforms;
        $this->renderPartial('tHistory', $history);
    }


    /**
     *
     */
    public function actionAtmCards()
    {
        // 獲取登錄用戶ID
        $uid = $this->getUid();
        // 獲取保存的銀行卡信息
        $banks = Fund::getAtmCard($uid);
        // 返回JSON數組
        $this->echoJson($banks);
    }


    /**
     * 查詢转账历史
     *
     * @param int $curIndex
     */
    public function actionDepositHistory($curIndex = 1)
    {
        // 獲取登錄用戶ID
        $uid = $this->getUid();
        // 查詢記錄
        $page = Fund::getDepositHistory($curIndex, $uid);

        $this->renderPartial('history', array(
            'page' => $page,
        ));
    }

    /**
     * 第三方支付
     */
    public function actionNewPay()
    {
        $dno = $this->post('dno');
        $bank = $this->post('bank');
        $uid = $this->uid;
        $deposit = Fund::getTpType($uid, $dno);
        if ($deposit) {
            $account = $deposit['accountid'];
            $amount = $deposit['amount'];
            $dt = $deposit['created'];
            switch ($deposit['tbname']) {
                case 'api_payplugin_ips':
                    $this->payByIps($account, $amount, $dno, $dt);
                    break;
                case 'api_payplugin_dinpay':
                    $this->payByDin($account, $amount, $dno, $dt);
                    break;
                case 'api_payplugin_newdinpay':
                    $this->payNewDinPay($account, $amount, $dno, $bank);
                    break;
                case 'api_payplugin_yeehii':
                    $this->payByYeehii($account, $amount, $dno, $bank);
                    break;
                case 'api_payplugin_yeepay':
                    $this->payYeePay($account, $amount, $dno);
                    break;
                case 'api_payplugin_gopay':
                    $this->payGoPay($account, $amount, $dno, $bank);
                    break;
                case 'api_payplugin_ecpss':
                    $this->payEcpss($account, $amount, $dno);
                    break;
                case 'api_payplugin_safepay':
                    $this->paySafePay($account, $amount, $dno, $bank);
                    break;
                case 'api_payplugin_baofoo':
                    $this->payBaoFoo($account, $amount, $dno);
                    break;
                case 'api_payplugin_heepay':
//                    if (empty($bank) || !is_numeric($bank)) $bank = 20;
                    $this->payHeePay($account, $amount, $dno, $bank);
                    break;
                case 'api_payplugin_newips':
                    $this->payNewIPS($account, $amount, $dno, $bank);
                    break;
                case 'api_payplugin_ekepay':
                    $this->payEKePay($account, $amount, $dno, $bank);
                    break;
                case 'api_payplugin_bbpay':
                    $this->payBBPay($account, $amount, $dno, $bank);
                    break;
                case 'api_payplugin_yeepaycard':
                    $cardAmt = $this->post('cardAmt');
                    $frpcardNo = $this->post('frpcardNo');
                    $cardPwd = $this->post('cardPwd');
                    $this->payYeePayCard($account, $amount, $dno, $bank, $cardAmt, $frpcardNo, $cardPwd);
                    break;
                case 'api_payplugin_yfpay':
                    $cardAmt = $this->post('cardAmt');
                    $frpcardNo = $this->post('frpcardNo');
                    $cardPwd = $this->post('cardPwd');
                    $this->payYFPay($account, $amount, $dno, $bank, $cardAmt, $frpcardNo, $cardPwd);
                    break;
                case 'api_payplugin_kdpay':
                    $this->payKDPay($account, $amount, $dno, $bank);
                    break;
                case 'api_payplugin_qwpay':
                    $this->payQWPay($account, $amount, $dno, $bank);
                    break;
                case 'api_payplugin_mobao':
                    $this->payMoBaoPay($account, $amount, $dno, $bank);
                    break;
                case 'api_payplugin_yefoopay':
                    $this->payYeFooPay($account, $amount, $dno, $bank);
                    break;
                case 'api_payplugin_esepay':
                    $this->payESEPay($account, $amount, $dno, $bank);
                    break;
                case 'api_payplugin_thpay':
                    $this->payTH($account, $amount, $dno, $bank);
                    break;
                case 'api_payplugin_xbpay':
                    $this->payXB($account, $amount, $dno, $bank);
                    break;
                case 'api_payplugin_xunhuipay':
                    $this->payXunHui($account, $amount, $dno, $bank);
                    break;
                case 'api_payplugin_xinbaopay':
                    $this->payXinBao($account, $amount, $dno, $bank, false);
                    break;
                case 'api_payplugin_yinbaopay':
                    $this->payYinBao($account, $amount, $dno, $bank, false);
                    break;
                case 'api_payplugin_worthpay':
                    $this->payWorth($account, $amount, $dno, $bank, false);
                    break;
				case 'api_payplugin_superstarpay':
                    $this->paySuperstar($account, $amount, $dno, $bank, false);
                    break;
				case 'api_payplugin_beeepaypay':
                    $this->payBeeePay($account, $amount, $dno, $bank, false);
                    break;
                case 'api_payplugin_onesecondpay':
                    $this->payOneSecondPay($account, $amount, $dno, $bank, false);
                    break;
                case 'api_payplugin_lefubaopay':
                    $this->payLeFuBao($account, $amount, $dno, $bank, false);
                    break;
                case 'api_payplugin_xunpay':
                    $this->payXun($account, $amount, $dno, $bank, false);
                    break;
                case 'api_payplugin_huifubaopay':
                    $this->payHuiFuBao($account, $amount, $dno, $bank, false);
                    break;
                case 'api_payplugin_funpay':
                    $this->payFun($account, $amount, $dno, $bank, false);
                    break;
                case 'api_payplugin_zhihuipay':
                    $this->payZhiHui($account, $amount, $dno, $bank, false);
                    break;
                case 'api_payplugin_xywalletpay':
                    $this->payXYWallet($account, $amount, $dno, $bank, false);
                    break;
                case 'api_payplugin_duobaopay':
                    $this->payDuoBaoPay($account, $amount, $dno, $bank, false);
                    break;
                case 'api_payplugin_netpay':
                    $this->payNet($account, $amount, $dno, $bank, false);
                    break;
                case 'api_payplugin_newsafepay':
                    $this->payNewSafe($account, $amount, $dno, $bank, false);
                    break;
                case 'api_payplugin_rongpay':
                    $this->payRongPay($account, $amount, $dno, $bank, false);
                    break;
                case 'api_payplugin_gmstonepay':
                    $this->payGMStonePay($account, $amount, $dno, $bank, false, 2014);
                    break;
                case 'api_payplugin_gmstoneapppay':
                    $this->payGMStonePay($account, $amount, $dno, $bank, false, 2036);
                    break;
                case 'api_payplugin_huarenpay':
                    $this->payHuaRen($account, $amount, $dno, $bank, false);
                    break;
                case 'api_payplugin_newthpay':
                case 'api_payplugin_newthpaywechat':
                case 'api_payplugin_newthpayalipay':
                    $ppid = $this->post('ppid');
                    $this->payNewTHPay($account, $amount, $dno, $bank, false, $ppid);
                    break;
                case 'api_payplugin_tpppay':
                case 'api_payplugin_tpppay2020':
                case 'api_payplugin_tpppay2021':
                case 'api_payplugin_tpppay2022':
                case 'api_payplugin_tpppay2023':
                case 'api_payplugin_tpppay2024':
                case 'api_payplugin_tpppay2025':
                case 'api_payplugin_tpppay2026':
                case 'api_payplugin_tpppay2027':
                case 'api_payplugin_tpppay2028':
                case 'api_payplugin_tpppay2029':
                    $ppid = $this->post('ppid');
                    $tpppay["amount"] = $amount;
                    $tpppay["payCardNumber"] = $this->post('payCardNumber');
                    $tpppay["payUserName"] = $this->post('payUserName');
                    $tpppay["payAttach"] = $this->post('payAttach');
                    $this->payTPPPay($account, $tpppay, $dno, $bank, false, $ppid);
                    break;
                case 'api_payplugin_alogatewaypay':
                    $this->payAloGatewayPay($account, $amount, $dno, $bank, false, 2030);
                    break;
                case 'api_payplugin_alogatewaywechatpay':
                    $this->payAloGatewayPay($account, $amount, $dno, "WECHAT", false, 2031);
                    break;
                case 'api_payplugin_alogatewayalipaypay':
                    $this->payAloGatewayPay($account, $amount, $dno, "ALIPAY", false, 2032);
                    break;
                case 'api_payplugin_jeanpay':
                    $this->payJeanPay($account, $amount, $dno, $bank, false);
                    break;
				case 'api_payplugin_epaytrustpay':
					$this->payEpayTrust($account, $amount, $dno, $bank, false);
					break;
				case 'api_payplugin_uyepaypay':
					$this->payUYepay($account, $amount, $dno, $bank, false);
					break;
                default:
                    break;
            }
        } else {

        }
    }


    /**
     * @param $action
     * @return string
     */
    private function genNotifyUrl($action)
    {
        return Net::getNotifyUrl($action, $this->prefix);
    }

    /**
     *
     * @param $account
     * @param $amount
     * @param $dno
     * @param $dt
     */
    private function payByIps($account, $amount, $dno, $dt)
    {
        // 1. 獲取支付平台的URL
        $config = Fund::getIpsConfig($account);
        $suburl = $config['suburl'];
        $cert = $config['cert'];

        $date = date('Ymd', $dt);

        $amount = $this->format_amount($amount);

        // 加密數據
        $sign = "billno" . $dno . "currencytype" . "RMB" . "amount" .
            $amount . "date" . $date . "orderencodetype" . "5" . $cert;
        $md5 = md5($sign);
        $ServerUrl = $this->genNotifyUrl('ips');
        $data = array(
            'Amount' => $amount,
            'Mer_code' => trim($config['merchantcode']),
            'Billno' => $dno,
            'Date' => $date,
            'Currency_Type' => 'RMB',
            'Gateway_Type' => '01',
            'Merchanturl' => '',
            'OrderEncodeType' => '5',
            'RetEncodeType' => '17',
            'Rettype' => '1',
            'ServerUrl' => $ServerUrl,
            'SignMD5' => $md5,
        );
        $this->submitTpPay($suburl, Fund::IPS_PAY_URL, $data);
    }

    private function payNewDinPay($account, $amount, $dno, $bank)
    {
        $amount = $this->format_amount($amount);
        $cfg = Fund::getNewDinConfig($account);
        $notify_url = $this->genNotifyUrl('newdinpay');
        $return_url = Net::get_url_head() . '/wallet/deposit';
        $bank = empty($bank) ? 'direct_pay' : $bank;
        $dinpay = new DinPayNew($cfg, $dno, $amount, $bank, $notify_url, $return_url);
        $rs = $dinpay->pay();
        if ($rs) {
            $this->success($rs);
        } else {
            $this->fail();
        }
    }


    /**
     * 快汇支付
     * @param $account
     * @param $amount
     * @param $dno
     * @param $dt
     */
    private function payByDin($account, $amount, $dno, $dt)
    {
        $config = Fund::getDinConfig($account);
        $suburl = $config['suburl'];

        $amount = $this->format_amount($amount);

        $charset = 'UTF-8';
        $version = 'V3.0';
        $merchantCode = trim($config['merchantcode']);
        $notifyUrl = $this->genNotifyUrl('dinpay');
        $time = date('Y-m-d m:i:s', $dt);
        $productName = $config['productname'];
        $serviceType = 'direct_pay';
        $key = $config['cert'];
        $returnUrl = $config['returnurl'];
        $signType = 'MD5';

        $data = array(
            'service_type' => $serviceType,
            'merchant_code' => $merchantCode,
            'input_charset' => $charset,
            'notify_url' => $notifyUrl,
            'return_url' => $returnUrl,
            'client_ip' => Net::get_client_ip(),
            'interface_version' => $version,
            'order_no' => $dno,
            'order_time' => $time,
            'order_amount' => $amount,
            'product_name' => $productName,
        );
        $data['sign'] = $this->generateDinMD5($data, $key);
        $data['sign_type'] = $signType;
        $pay_url = Fund::DIN_PAY_URL . $charset;
        $this->submitTpPay($suburl, $pay_url, $data);
    }

    private function payByYeehii($account, $amount, $dno, $cardNo)
    {
        $config = Fund::getYeehiiConfig($account);
        $cert = $config['cert'];
        $merchantcode = trim($config['merchantcode']);
        $sign = md5(sprintf('customerid=%d&orderNumber=%d&key=%s', $merchantcode, $dno, $cert));
        $amount = $this->format_amount($amount);
        $pay_url = Fund::YEEHII_PAY_URL;
        $notifyUrl = $this->genNotifyUrl('yeehiii');
        $pay_url .= '?customerid=' . $merchantcode . '&orderNumber=' . $dno . '&ordermoney=' . $amount . '&sign=' . $sign . '&cardNo=' . $cardNo . '&postType=1' . '&returnurl=' . urlencode($notifyUrl);
        $this->success($pay_url);
    }

    /**
     * 易宝支付
     * @param $account
     * @param $amount
     * @param $dno
     */
    private function payYeePay($account, $amount, $dno)
    {
        $config = Fund::getYeePayConfig($account);
        $amount = $this->format_amount($amount);
        $notify_uri = $this->genNotifyUrl('yeepay');
        $yeePay = new YeePay($config, $dno, $amount, $notify_uri);
        $pay_url = YeePay::PAY_URL;
        $data = $yeePay->gen_params();
        $suburl = $config['suburl'];
        $this->submitTpPay($suburl, $pay_url, $data);
    }


    /**
     * @param $account
     * @param $amount
     * @param $dno
     * @param $frpId
     * @param $cardAmt
     * @param $cardNO
     * @param $cardPwd
     */
    private function payYeePayCard($account, $amount, $dno, $frpId, $cardAmt, $cardNO, $cardPwd)
    {
        $config = Fund::getYeePayCardConfig($account);
        $amount = $this->format_amount($amount);
        $notify_uri = $this->genNotifyUrl('yeepaycard');
        $yeePay = new YeePayCard($config, $dno, $amount, $frpId, $cardAmt, $cardNO, $cardPwd, $notify_uri);
        $data = $yeePay->get_params();
        $action = Helper::base64url_encode(base64_encode(YeePayCard::PAY_URL . '?' . http_build_query($data)));
        $this->success($action);
    }

    private function payYFPay($account, $amount, $dno, $bank, $cardAmt, $cardNO, $cardPwd)
    {
        $config = Fund::getYFPayCardConfig($account);
        $amount = $this->format_amount($amount);
        $notifyUrl = $this->genNotifyUrl('yfpay');
        $returnUrl = Net::get_url_head() . '/wallet/deposit';
        $yf = new YFPay($config, $dno, $amount, $bank, false, $notifyUrl, $returnUrl, $cardAmt, $cardNO, $cardPwd);
        $url = $yf->pay_url();
        $data = $yf->genParams();
        $data = Payment::gen_params($data, '=');
        $this->success($url . '?' . $data);
    }

    private function payKDPay($account, $amount, $dno, $bank)
    {
        $config = Fund::getKDPayCardConfig($account);
        $amount = $this->format_amount($amount);
        $notifyUrl = $this->genNotifyUrl('kdpay');
        $returnUrl = Net::get_url_head() . '/wallet/deposit';
        $kd = new KDPay($config, $dno, $amount, $bank, 0, $notifyUrl, $returnUrl);
        $url = $kd->getPayUrl();
        $this->success($url);
    }

    private function payQWPay($account, $amount, $dno, $bank)
    {
        $config = Fund::getPayCardConfig($account, 'qwpay');
        $amount = $this->format_amount($amount);
        $notifyUrl = $this->genNotifyUrl('qwpay');
        $returnUrl = Net::get_url_head() . '/wallet/deposit';
        $qw = new QWPay($config, $dno, $amount, $bank, $notifyUrl, $returnUrl);
        $url = $qw->getPayUrl();
        $this->success($url);
    }


    public function actionYeePayCard($action)
    {
        $action = base64_decode(Helper::base64url_decode($action));
        $rs = YeePayCard::submit($action);
        if ($rs->succ) {
            $title = '提交充值成功！';
        } else {
            $title = '抱歉，提交充值失败！';
        }
        $this->render('/home/result', array('rs' => $rs, 'title' => $title));
    }

    /**
     * 国付宝
     * @param $account
     * @param $amount
     * @param $dno
     * @param $bank
     */
    private function payGoPay($account, $amount, $dno, $bank)
    {
        $config = Fund::getGoPayConfig($account);
        $amount = $this->format_amount($amount);
        $notifyUrl = $this->genNotifyUrl('gopay');
        $goPay = new GoPay($config, $dno, $amount, $bank, 1, $notifyUrl);
        $data = $goPay->gen_params();
        $suburl = $config['suburl'];
        $pay_url = GoPay::GOPAY_URL;
        $this->submitTpPay($suburl, $pay_url, $data);
    }

    /**
     * 汇潮
     * @param $account
     * @param $amount
     * @param $dno
     */
    private function payEcpss($account, $amount, $dno)
    {
        $cfg = Fund::getEcpssConfig($account);
        $amount = $this->format_amount($amount);
        $notifyUrl = $this->genNotifyUrl('ecpss');
        $returnUrl = $this->getReturnUrl();
        $ecpss = new Ecpss($cfg, $dno, $amount, '', $notifyUrl, $returnUrl);
        $suburl = $cfg['suburl'];
        $this->submitTpPay($suburl, Ecpss::PAY_URL, $ecpss->params);
    }

    /**
     * Mobao
     * @param $account
     * @param $amount
     * @param $dno
     * @param $bank
     */
    private function payMoBaoPay($account, $amount, $dno, $bank)
    {

        $cfg = Fund::getMoBaoConfig($account);
        $amount = $this->format_amount($amount);
        $notifyUrl = $this->genNotifyUrl('mobao');
        $returnUrl = $this->getReturnUrl();
        $mobao = new MoBaoPay($cfg, $dno, $amount, MoBaoPay::WEB_PAY_B2C, $notifyUrl, $returnUrl);
        $suburl = $cfg['suburl'];
        $payUrl = isset($cfg['payurl']) ? $cfg['payurl'] : MoBaoPay::PAY_URL;
        $this->submitTpPay($suburl, $payUrl, $mobao->params);
    }

    /**
     * YeFoo
     * @param $account
     * @param $amount
     * @param $dno
     * @param $bank
     */
    private function payYeFooPay($account, $amount, $dno, $bank)
    {
        $cfg = Fund::getPayPlatformConfig($account, 'yefoopay');
        $amount = $this->format_amount($amount);
        $notifyUrl = $this->genNotifyUrl('yefoopay');
        $returnUrl = $this->getReturnUrl();
        $yefoo = new YeFooPay($cfg, $dno, $amount, $bank, $notifyUrl, $returnUrl);
        $this->success($yefoo->getPayUrl());
    }

    /**
     * ESE
     * @param $account
     * @param $amount
     * @param $dno
     * @param $bank
     */
    private function payESEPay($account, $amount, $dno, $bank)
    {
        $cfg = Fund::getPayPlatformConfig($account, 'esepay');
        $amount = $this->format_amount($amount);
        $notifyUrl = $this->genNotifyUrl('esepay');
        $returnUrl = $this->getReturnUrl();
        $ese = new ESEPay($cfg, $dno, $amount, $bank, $notifyUrl, $returnUrl);
        $this->success($ese->getPayUrl());
    }

    private function payTH($account, $amount, $dno, $bank)
    {
        $cfg = Fund::getPayPlatformConfig($account, 'thpay');
        $amount = $this->format_amount($amount);
        $th = new TongHuiPay();
        $returnUrl = $this->getReturnUrl();
        $th->setSubParams($cfg, $dno, $amount, $bank, $th->getFeedbackUrl(), $returnUrl);
        $url = "/fund/paySubmit?" . $th->getBase64Param();
        $this->success($url);
    }

    private function payXB($account, $amount, $dno, $bank)
    {
        $cfg = Fund::getPayPlatformConfig($account, 'xbpay');
        $amount = $this->format_amount($amount);
        $pay = new XBeiPay();
        $returnUrl = $this->getReturnUrl();
        $pay->setSubParams($cfg, $dno, $amount, $bank, $pay->getFeedbackUrl(), $returnUrl);
        $url = "/fund/paySubmit?" . $pay->getBase64Param();
        $this->success($url);
    }

    private function payXunHui($account, $amount, $dno, $bank)
    {
        $cfg = Fund::getPayPlatformConfig($account, 'xunhuipay');
        $amount = $this->format_amount($amount);
        $pay = new XunHuiPay();
        $pay->setSubParams($cfg, $dno, $amount, $bank, $pay->getFeedbackUrl());
        $rs = $pay->submitPay();
        $msg = $content = "";
        if ($rs) {
            $content = $rs->msg;
        } else {
            $msg = $rs->msg;
        }
        $title = DictUtil::get($bank, XunHuiPay::$PayType);
        $url = "/fund/pay?title=$title&content=$content&msg=$msg&amt=$amount";
        $this->success($url);
    }

    private function payXinBao($account, $amount, $dno, $bank, $isMobile)
    {
        $cfg = Fund::getPayPlatformConfig($account, 'xinbaopay');
        $amount = $this->format_amount($amount);
        $pay = new XinBaoPay();
        $pay->setSubParams($cfg, $dno, $amount, $bank, $isMobile, $pay->getFeedbackUrl(), $this->getReturnUrl());
        $this->success($pay->getPayUrl());
    }

    private function payYinBao($account, $amount, $dno, $bank, $isMobile)
    {
        $cfg = Fund::getPayPlatformConfig($account, 'yinbaopay');
        $amount = $this->format_amount($amount);
        $pay = new YinBaoPay();
        $pay->setSubParams($cfg, $dno, $amount, $bank, $isMobile, $pay->getFeedbackUrl(), $this->getReturnUrl());
        $this->success($pay->getPayUrl());
    }

    private function payWorth($account, $amount, $dno, $bank, $isMobile)
    {
        $cfg = Fund::getPayPlatformConfig($account, 'worthpay');
        $amount = $this->format_amount($amount);
        $pay = new WorthPay();
        $pay->setSubParams($cfg, $dno, $amount, $bank, $isMobile, $pay->getFeedbackUrl(), $this->getReturnUrl());
        $this->success($pay->getPayUrl());
    }
	
	private function paySuperstar($account, $amount, $dno, $bank, $isMobile){
		$cfg = Fund::getPayPlatformConfig($account, 'superstarpay');
		
		//超级星 amount 不用点。 算为分。
		$amount = $amount * 100;
		$pay = new SuperstarPay();		
		$pay->setSubParams($cfg, $dno, $amount, $bank, $isMobile, $pay->getFeedbackUrl(), $this->getReturnUrl());
		$url = "/fund/paySubmit?" . $pay->getBase64Param();
		$this->success($url);
	}
	
	private function payBeeePay($account, $amount, $dno, $bank, $isMobile){
		$cfg = Fund::getPayPlatformConfig($account, 'beeepaypay');
		$pay = new BeeePay();		
		$pay->setSubParams($cfg, $dno, $amount, $bank, $isMobile, $pay->getFeedbackUrl(), $this->getReturnUrl());
		$payType = $pay->getPayType();
		if($payType == 'WXPAY' || $payType == 'ALIPAY'){
			$qrcode = $pay->getQRCodePayUrl();
			$content = Helper::base64_encode($qrcode);
			$gateway = ($bank == "WXPAY" ? "10" : "11");
            $this->ipsScanInfo($gateway, $title, $msg);
			$url = "/fund/pay?title=".$title."&content=".$content."&msg=".$msg."&amt=".$amount;
            $this->success($url);
			
		}else{
			$url = "/fund/paySubmit?" . $pay->getBase64Param();
			$this->success($url);
		}	
	}
	
	private function payEpayTrust($account, $amount, $dno, $bank, $isMobile){
		$cfg = Fund::getPayPlatformConfig($account, 'epaytrustpay');
		$pay = new EpayTrust();		
		$pay->setSubParams($cfg, $dno, $amount, $bank, $isMobile, $pay->getFeedbackUrl(), $this->getReturnUrl());
		$url = "/fund/paySubmit?" . $pay->getBase64Param();
		$this->success($url);
	}
	
	private function payUYepay($account, $amount, $dno, $bank, $isMobile){
		$cfg = Fund::getPayPlatformConfig($account, 'uyepaypay');
		$pay = new UYePay();	
		$pay->setSubParams($cfg, $dno, $amount, $bank, $isMobile, $pay->getFeedbackUrl(), $this->getReturnUrl());
		$url = "/fund/paySubmit?" . $pay->getBase64Param();
		$this->success($url);
	}

    private function payOneSecondPay($account, $amount, $dno, $bank, $isMobile)
    {
        $cfg = Fund::getPayPlatformConfig($account, 'onesecondpay');
        $amount = $this->format_amount($amount);
        $pay = new OneSecondPay();
        $pay->setSubParams($cfg, $dno, $amount, $bank, $isMobile, $pay->getFeedbackUrl(), $this->getReturnUrl());
        $this->success($pay->getPayUrl());
    }

    private function payJeanPay($account, $amount, $dno, $bank, $isMobile)
    {
        $cfg = Fund::getPayPlatformConfig($account, 'jeanpay');
        $amount = $this->format_amount($amount);
        $pay = new JeanPay();
        $pay->setSubParams($cfg, $dno, $amount, $bank, $isMobile, $pay->getFeedbackUrl(), $this->getReturnUrl());

        if ($bank == "WXPAY" || $bank == "ALIPAY") {
            $qrcode = $pay->getQRCodePayUrl();
            $content = Helper::base64_encode($qrcode);
            $gateway = ($bank == "WXPAY" ? "10" : "11");
            $this->ipsScanInfo($gateway, $title, $msg);
            $url = "/fund/pay?title=$title&content=$content&msg=$msg&amt=$amount";
            $this->success($url);

        } else {
            $url = "/fund/paySubmit?" . $pay->getBase64Param();
            $this->success($url);
        }

    }

    private function payLeFuBao($account, $amount, $dno, $bank, $isMobile)
    {
        $cfg = Fund::getPayPlatformConfig($account, 'lefubaopay');
        $amount = $this->format_amount($amount);
        $pay = new Lefubao();
        $pay->setSubParams($cfg, $dno, $amount, $bank, $isMobile, $pay->getFeedbackUrl(), $this->getReturnUrl());
        $this->success($pay->getPayUrl());
    }

    private function payTPPPay($account, $tpppay, $dno, $bank, $isMobile, $num)
    {
        $table = ($num == 2017) ? "tpppay" : "tpppay" . trim($num);
        $cfg = Fund::getPayPlatformConfig($account, $table);
        $tpppay["amount"] = $this->format_amount($tpppay["amount"]);
        $pay = new TPPPay();
        $pay->setSubParams($cfg, $dno, $tpppay, $bank, $isMobile, $pay->getFeedbackUrl(), $this->getReturnUrl(), $num);
        $url = $pay->getPayUrl();
        if ($bank == "WXPAY" || $bank == "ALIPAY") {
            $content = Helper::base64_encode($url);
            $gateway = ($bank == "WXPAY" ? "10" : "11");
            $this->ipsScanInfo($gateway, $title, $msg);
            $url = "/fund/pay?title=$title&content=$content&msg=$msg&amt=".$tpppay["amount"];
        }
        if($pay->updateServiceTransSN($dno)) $this->success($url);
    }

    private function payAloGatewayPay($account, $amount, $dno, $bank, $isMobile, $num)
    {
        $dbName = 'alogatewaypay';
        switch ($bank) {
            case 'WECHAT':
                $dbName = 'alogatewaywechatpay';
                break;
            case 'ALIPAY':
                $dbName = 'alogatewayalipaypay';
                break;
        }
        $cfg = Fund::getPayPlatformConfig($account, $dbName);
        $amount = $this->format_amount($amount) * 100;
        $pay = new AloGatewayPay();
        $pay->setSubParams($cfg, $dno, $amount, $bank, $isMobile, $pay->getFeedbackUrl(), $this->getReturnUrl(), $num);
        if(empty($cfg['suburl'])){
            $url = "/fund/paySubmit?" . $pay->getBase64Param();
        }else {
            $url = $pay->getPayUrl();
        }
        $this->success($url);
    }

    private function payNewTHPay($account, $amount, $dno, $bank, $isMobile, $num)
    {
        $cfg = Fund::getPayPlatformConfig($account, 'newthpay'.(($num == 2034)? 'wechat':(($num == 2035)? 'alipay':'')));
        $amount = $this->format_amount($amount);
        $pay = new NewTongHuiPay();
        $pay->setSubParams($cfg, $dno, $amount, $bank, $pay->getFeedbackUrl(), $this->getReturnUrl(), Net::get_client_ip());
        if ($bank == "WEIXIN" || $bank == "ZHIFUBAO") {
            $content = Helper::base64_encode($pay->getPayUrl());
            $gateway = ($bank == "WEIXIN" ? "10" : "11");
            $this->ipsScanInfo($gateway, $title, $msg);
            $url = "/fund/pay?title=$title&content=$content&msg=$msg&amt=$amount";
            $this->success($url);
        } else {
            $this->success($pay->getPayUrl());
        }
    }


    private function payRongPay($account, $amount, $dno, $bank, $isMobile)
    {
        $amt = $this->format_amount($amount) * 100;
        $cfg = Fund::getPayPlatformConfig($account, 'rongpay');
        $pay = new RongPay();
        $pay->setSubParams($cfg, $dno, $amt, $bank, false, $pay->getFeedbackUrl(), $pay->getFeedbackUrl());
        // echo $this->getPayLink();

        // $this->success($pay->getPayUrl());
        $qrcode = $pay->getPayLink();
        $content = Helper::base64_encode($qrcode);
        $gateway = ($bank == "WXPAY" ? "10" : "11");
        $this->ipsScanInfo($gateway, $title, $msg);
        $url = "/fund/pay?title=$title&content=$content&msg=$msg&amt=$amount";
        $this->success($url);
    }

    private function payGMStonePay($account, $amount, $dno, $bank, $isMobile,$num)
    {
        $dbName = 'gmstonepay';
        switch ($num) {
            case 2036:
                $dbName = 'gmstoneapppay';
                break;
        }
        $cfg = Fund::getPayPlatformConfig($account, $dbName);
        $amount = $this->format_amount($amount);
        $pay = new GMStonePay();
        $pay->setSubParams($cfg, $dno, $amount, $bank, $isMobile, $pay->getFeedbackUrl(), $this->getReturnUrl(),$num );
        $this->success($pay->getPayUrl());
    }

    private function payXun($account, $amount, $dno, $bank, $isMobile)
    {
        $cfg = Fund::getPayPlatformConfig($account, 'xunpay');
        $amount = $this->format_amount($amount);
        $pay = new XunPay();
        $pay->setSubParams($cfg, $dno, $amount, $bank, $isMobile, $pay->getFeedbackUrl(), $this->getReturnUrl());
        $this->success($pay->getPayUrl());
    }

    private function payHuiFuBao($account, $amount, $dno, $bank, $isMobile)
    {
        $cfg = Fund::getPayPlatformConfig($account, 'huifubaopay');
        $amount = $this->format_amount($amount);
        $pay = new HuiFuBaoPay();
        $pay->setSubParams($cfg, $dno, $amount, $bank, $isMobile, $pay->getFeedbackUrl(), $this->getReturnUrl());
        if ($pay->isbank) {
            $this->success($pay->getPayUrl());
        } else {
            $json = $pay->getXML($pay);
            $content = $json['respData']['codeUrl'];
            $gateway = ($bank == "WXPAY" ? "10" : "11");
            $this->ipsScanInfo($gateway, $title, $msg);
            $url = "/fund/pay?title=$title&content=$content&msg=$msg&amt=$amount";
            $this->success($url);
        }
    }

    private function payZhiHui($account, $amount, $dno, $bank, $isMobile)
    {
        $cfg = Fund::getPayPlatformConfig($account, 'zhihuipay');
        $amount = $this->format_amount($amount);
        $pay = new ZhiHuiPay();
        $pay->setSubParams($cfg, $dno, $amount, $bank, $isMobile, $pay->getFeedbackUrl(), $this->getReturnUrl());
        if ($pay->isbank) {
            $this->success($pay->getPayUrl());
        } else {
            $content = $pay->getQrcode($pay);

            if( isset($content['response']['qrcode']) ){
                $qrcode = Helper::base64_encode($content['response']['qrcode']);
                switch ($bank) {
                    case 'WXPAY':
                        $gateway = "10";
                        break;
                    case 'ALIPAY':
                        $gateway = "11";
                        break;
                    case 'QQPAY':
                        $gateway = "12";
                        break;
                }
                $this->ipsScanInfo($gateway, $title, $msg);
                $url = "/fund/pay?title=$title&content=$qrcode&msg=$msg&amt=$amount";
                $this->success($url);
            }else {
                $errorMsg = $content['response']['resp_desc'];
                $this->fail($errorMsg);
            }
        }
    }

    private function payXYWallet($account, $amount, $dno, $bank, $isMobile)
    {
        $cfg = Fund::getPayPlatformConfig($account, 'xywalletpay');
        $amount = $this->format_amount($amount);
        $pay = new XYWalletPay();
        $pay->setSubParams($cfg, $dno, $amount, $bank, $isMobile, $pay->getFeedbackUrl(), $this->getReturnUrl());
        if ($pay->isbank) {
            $this->success($pay->getPayUrl());
        } else {
            $content = $pay->getQrcode($pay);
            $gateway = ($bank == "WXPAY" ? "10" : "11");
            $this->ipsScanInfo($gateway, $title, $msg);
            $url = "/fund/pay?title=$title&content=$content&msg=$msg&amt=$amount";
            $this->success($url);
        }
    }

    private function payFun($account, $amount, $dno, $bank, $isMobile)
    {
        $cfg = Fund::getPayPlatformConfig($account, 'funpay');
        $amount = $this->format_amount($amount);
        $pay = new FunPay();
        $pay->setSubParams($cfg, $dno, $amount, $bank, $isMobile, $pay->getFeedbackUrl(), $this->getReturnUrl());
        $url = "/fund/paySubmit?" . $pay->getBase64Param();
        $this->success($url);
    }

    private function payDuoBaoPay($account, $amount, $dno, $bank, $isMobile)
    {
        $cfg = Fund::getPayPlatformConfig($account, 'duobaopay');
        $amount = $this->format_amount($amount);
        $pay = new DuoBaoPay();
        $pay->setSubParams($cfg, $dno, $amount, $bank, $isMobile, $pay->getFeedbackUrl(), $this->getReturnUrl());
        $this->success($pay->getPayUrl());
    }

    private function payNet($account, $amount, $dno, $bank, $isMobile)
    {
		$cfg = Fund::getPayPlatformConfig($account, 'netpay');
		$pay = new NetPay();		
		$msg = '';
		$pay->setSubParams($cfg, $dno, $amount, $bank, $isMobile, $pay->getFeedbackUrl(), $this->getReturnUrl());		
		$qrcode = $pay->getQRCodePayUrl();	
		$content = Helper::base64_encode($qrcode);
		$gateway = ($bank == "WXPAY" ? "10" : "11");
        $this->ipsScanInfo($gateway, $title, $msg);			
		$url = "/fund/pay?title=".$title."&content=".$content."&msg=".$msg."&amt=".$amount;
		$this->success($url);
	}

    private function payNewSafe($account, $amount, $dno, $bank, $isMobile)
    {
        $cfg = Fund::getPayPlatformConfig($account, 'newsafepay');
        $amount = $this->format_amount($amount);
        $pay = new NewSafePay();
        $pay->setSubParams($cfg, $dno, $amount, $bank, $isMobile, $pay->getFeedbackUrl(), $this->getReturnUrl());
        $this->success($pay->getPayUrl());
    }

    private function payHuaRen($account, $amount, $dno, $bank, $isMobile)
    {
        $cfg = Fund::getPayPlatformConfig($account, 'huarenpay');
        $amount = $this->format_amount($amount);
        $pay = new HuaRenPay();
        $pay->setSubParams($cfg, $dno, $amount, $bank, $isMobile, $pay->getFeedbackUrl() . "?orderNum=" . $dno, $this->getReturnUrl() . "?orderNum=" . $dno);
        $this->success($pay->getPayUrl());
    }

    /**
     * 支付卫士
     * @param $account
     * @param $amount
     * @param $dno
     * @param $bank
     */
    private function paySafePay($account, $amount, $dno, $bank)
    {
        $config = Fund::getSafePayConfig($account);
        $amount = $this->format_amount($amount);
        $notifyUrl = $this->genNotifyUrl('safepay');
        $safePay = new SafePay($config, $this->playerName, $dno, $amount, $bank, $notifyUrl);
        $url = $safePay->sub_pay();
        if ($url) {
            $this->success($url);
        } else {
            $this->fail();
        }
    }

    /**
     * 宝付
     * @param $account
     * @param $amount
     * @param $dno
     */
    private function payBaoFoo($account, $amount, $dno)
    {
        $config = Fund::getBaoFooConfig($account);
        $amount = $this->format_amount($amount) * 100;
        $notifyUrl = $this->genNotifyUrl('baofoo');
        $returnUrl = Net::get_url_head() . '/wallet/deposit';
        $baofoo = new BaoFoo($config, $dno, $amount, $notifyUrl, $returnUrl);
        $data = $baofoo->gen_params();
        $suburl = $config['suburl'];
        $pay_url = BaoFoo::PAY_URL;
        $this->submitTpPay($suburl, $pay_url, $data);
    }

    /**
     * @param $account
     * @param $amount
     * @param $dno
     * @param $pay_type 20银行，30微信
     */
    private function payHeePay($account, $amount, $dno, $pay_type)
    {
        $config = Fund::getHeePayConfig($account);
        $amount = $this->format_amount($amount);
        $notifyUrl = $this->genNotifyUrl('heepay');
        $returnUrl = Net::get_url_head() . '/fund/index';
        $heepay = new HeePay($config, $dno, $amount, '', '', $notifyUrl, $notifyUrl, $pay_type);
        $data = $heepay->gen_params();
        $suburl = $config['suburl'];
        $pay_url = HeePay::PAY_URL;
        $this->submitTpPay($suburl, $pay_url, $data);
    }

    /**
     * 环迅4.0
     * @param $account
     * @param $amount
     * @param $dno
     * @param $gateway
     */
    private function payNewIPS($account, $amount, $dno, $gateway)
    {
        $config = Fund::getNewIPSConfig($account);
        $amount = $this->format_amount($amount);
        $notifyUrl = $this->genNotifyUrl('newips');
        $returnUrl = Net::get_url_head() . '/fund/ipsfb';
        $ips = new NewIPS($config, $dno, $amount, $gateway, $notifyUrl, $returnUrl);
        $rs = $ips->getPayUrl();
        if ($ips->isBank) {
            $this->success($rs);
        } else {
            $content = Helper::base64_encode($rs);
            $this->ipsScanInfo($gateway, $title, $msg);
            $url = "/fund/pay?title=$title&content=$content&msg=$msg&amt=$amount";
            $this->success($url);
        }
    }

    private function ipsScanInfo($gateway, &$title, &$msg)
    {
        if ($gateway == "11") {
            $title = "支付宝";
            $msg = "";
        }else if ($gateway == "10") {
            $title = "微信";
            $msg = "";
        }else if ($gateway == "12") {
            $title = "QQ手机钱包";
            $msg = "";
        }
    }

    private function payEKePay($account, $amount, $dno, $bank)
    {
        $config = Fund::getEKePayConfig($account);
        $amount = $this->format_amount($amount);
        $notifyUrl = $this->genNotifyUrl('ekepay');
        $returnUrl = Net::get_url_head() . '/fund/index#deposit';
        $ekpay = new EkePay($config, $dno, $amount, $bank, $notifyUrl);
        $data = $ekpay->gen_param();
        if ($data) {
            $this->success(EkePay::PAY_URL . '?' . http_build_query($data));
        } else {
            $this->fail();
        }
    }

    /**
     * 币币支付
     * @param $account
     * @param $amount
     * @param $dno
     * @param $bank
     */
    private function payBBPay($account, $amount, $dno, $bank)
    {
        $config = Fund::getBBPayConfig($account);
        $amount = $this->format_amount($amount) * 100;
        $notifyUrl = $this->genNotifyUrl('bbpay');
        $returnUrl = Net::get_url_head() . '/fund/index#deposit';
        $bbpay = new BBPay($config, $dno, $amount, $bank, $notifyUrl);
        $data = $bbpay->get_query();
        $this->submitTpPay($config['suburl'], BBPay::PAY_URL, $data);
    }

    private function format_amount($amount)
    {
        return sprintf('%.2f', $amount);
    }

    private function getReturnUrl()
    {
        return Net::get_url_head() . '/wallet/deposit';
    }

    /**
     * 提交第三方存款
     * @param $sub_url
     * @param $pay_url
     * @param $data
     */
    private function submitTpPay($sub_url, $pay_url, $data)
    {
        try {
            $data = $this->getParams($data);

            $size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
            $iv = StrUtil::generateRandomString($size);

            $param = 'data' . $data . 'iv' . $iv . 'url' . $pay_url;
            $sign = md5($param . Fund::api_key);

            $time = time();
            $ticket = Helper::base64url_encode(EncryptUtil::encrypt($time, Fund::key, $iv, MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC));
            $ticket = $iv . $ticket;

            $location = $sub_url . '?sub_data=' . rawurlencode(Helper::base64url_encode(EncryptUtil::encrypt($data, Fund::key, $iv, MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC))) .
                '&url=' . rawurlencode($pay_url) . '&sign=' . rawurlencode($sign) . '&ticket=' . rawurldecode($ticket);
            $this->success($location);
        } catch (Exception $e) {
            $this->fail();
        }
    }

    public function actionPaySubmit($url, $params)
    {
        $url = Helper::base64_decode($url);
        $params = json_decode(Helper::base64_decode($params), true);
        $this->renderPartial("submit", array("url" => $url, "params" => $params));
    }

    public function actionPaySubmitTest($url, $params)
    {
        $url = Helper::base64_decode($url);
        $params = json_decode(Helper::base64_decode($params), true);
        $this->renderPartial("submitTest", array("url" => $url, "params" => $params));
    }


    /**
     * @param $data
     * @return string
     */
    private function getParams($data)
    {
        $params = array();
        foreach ($data as $key => $value) {
            $params[] = $key . '=' . $value;
        }
        return implode('&', $params);
    }

    /**
     * 生成快汇的支付签名
     * @param $data
     * @param $key
     * @return string
     */
    private function generateDinMD5($data, $key)
    {
        $data = array_filter($data, function ($v) {
            return !empty($v);
        });
        ksort($data);
        return md5($this->getParams($data) . '&key=' . $key);
    }

    public function actionFlowLimitOne()
    {
        $id = $this->getUid();
        if ($id == "") {
            echo "error";
            exit;
        } else {
            $PlayerFund = new PlayerFund();
            $result = $PlayerFund->flowLimitOne($id);
            $entitys = array();
            $num = 0;
            foreach ($result as $v) {
                $num = $num + 1;
                //btype,ptype,ddno,amount,created,remark,actname,playername,realname
                $data = array(
                    'ddno' => $v['ddno'],
                    'wid' => $v['wid'],
                    'gpid' => $v['gpid'],
                    'amount' => $v['amount'],
                    'gpname' => $v ['gpname'],
                    'btype' => DictUtil::get($v['btype'], Config::$BTYPE),
                    'ptype' => DictUtil::get($v['ptype'], Config::$PTYPE),
                    'actname' => $v['actname'],
                    'createdtoString' => PlayerFund::str2time($v['created']),
                    'created' => $v['created']
                );
                $entitys [] = $data;
            }
            echo json_encode($entitys);
        }
    }

    public function actionNewIps($pGateWayReq)
    {
        $this->renderPartial('newIps', array('pGateWayReq' => $pGateWayReq));
    }

    public function actionIpsfb()
    {
        $paymentResult = $this->post('paymentResult');
        $curl = new Curl();
        $curl->setOpt(CURLOPT_TIMEOUT, 1);
        $curl->post($this->genNotifyUrl('newips'), array('paymentResult' => $paymentResult));
        $rs = new Result(true);
        $title = '提交充值成功！';
        $this->render('/home/result', array('rs' => $rs, 'title' => $title));
    }

    public function actionQrcode($content)
    {
        require dirname(__FILE__) . "/../../../lib/phpqrcode/qrlib.php";
        QRcode::png($content, false, "L", 6, 2);
    }

    public function actionPay($title = "", $content = "", $msg = "", $amt = 0)
    {
        if ($content) {
            $this->renderPartial("pay", array("title" => $title, "content" => $content, "amount" => $amt));
        } else {
            $rs = new Result(false, $msg);
            $title = '充值信息有误，请联系管理员！';
            $this->render('/home/result', array('rs' => $rs, 'title' => $title));
        }
    }

}
