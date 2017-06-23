<?php

/**
 * .
 * Date: 16/3/11 
 * Time: 上午10:12 
 */
class WalletController extends MController
{


    public function actionIndex()
    {
        $this->render('index');
    }

    public function actionDeposit()
    {
        $uid = $this->getUid();

        // 查找用戶是否已經有存款未處理
        $process = Fund::getDepositStatus($uid);//最新的玩家未反馈的存款申请
        $process_count = Fund::get_untreated_count($uid);//客服未审核的存款条数
        $cfg = ApiConfig::getApiConfig(ApiConfig::DEPOSIT_CATE);
        $acpCfg = new AcpConfig($cfg);
        $allow_count = $acpCfg->getInt('allowprocesscount', 1);
        $allow_deposit = true;    //是否可以进行存款
        if ($allow_count > 0) {
            $allow_deposit = $allow_count > $process_count;
        }
        $all_banks = $real_name = null;
        $has_hee_pay = false;
        $ebank = $atm_bank = array();
        $user = $this->getUser();
        if ($allow_deposit) {
            $real_name = StrUtil::cut($user['realname'], 1, "**");
            $tps = Fund::getWebPayPlatforms($user['groupid']);
            $all_banks = Fund::getBanks($user['groupid']);
            $all_banks = $this->all_pay_type($all_banks, $tps, $has_hee_pay, $ebank, $atm_bank);
        }

        $bank_list = Fund::getBankDictWY();
        $this->sortInBankList($bank_list);
        $bank_json = PaymentHelper::getBankList($has_hee_pay, $user['groupid'], $this->prefix);
        $params = $this->get_deposit_cfg($cfg);
        $this->getDepositTitle($acpCfg, $eBankName, $atmName);
        $params = $params + array(
                'all_banks' => $all_banks,
                'real_name' => $real_name,
                'process' => $process,
                'allow_deposit' => $allow_deposit,
                'prefix' => $this->prefix,
                'bank_json' => $bank_json,
                'ebank' => $ebank,
                'amt_bank' => $atm_bank,
                'bank_list' => $bank_list,
                'eBankName' => $eBankName,
                'atmName' => $atmName,
            );
        $this->render('deposit', $params);
    }

    private function sortInBankList(&$bankList)
    {
        $sortArr = array(
            "zfb" => 1,
            "icbc" => 2,
            "ccb" => 3,
            "boc" => 4,
            "cmbc" => 5,
            "abc" => 6,
            "cib" => 7,
            "bcm" => 8,
            "other" => 100
        );
        usort($bankList, function ($a, $b) use ($sortArr) {
            $aValue = isset($sortArr[$a['bankcode']]) ? $sortArr[$a['bankcode']] : 10;
            $bValue = isset($sortArr[$b['bankcode']]) ? $sortArr[$b['bankcode']] : 10;
            if ($aValue == $bValue) {
                return 0;
            } else {
                return $aValue > $bValue ? 1 : -1;
            }
        });
    }

    /**
     * @param $acpCfg
     * @param $eBankName
     * @param $atmName
     */
    private function getDepositTitle($acpCfg, &$eBankName, &$atmName)
    {
        $eBankName = FLanguage::Get_L('deposit_from_company_ebank');
        $atmName = FLanguage::Get_L('deposit_from_company_atm');
        $eBankName = $acpCfg->get("deposit_eBank_title", $eBankName);
        $atmName = $acpCfg->get("deposit_atm_title", $atmName);
    }

    /**
     * @param $all_banks
     * @param $pay_platforms
     * @param bool|false $has_hee_pay
     * @param array $ebank
     * @param array $atm_bank
     * @return array
     */
    public function all_pay_type($all_banks, $pay_platforms, &$has_hee_pay = false, &$ebank, &$atm_bank)
    {
        $tp = array('cft' => 0, 'wxzf' => 0, 'zfb' => 0);
        $rs = array();
        foreach ($all_banks as $item) {
            $bank_code = $item['bankcode'];
            $is_tp = isset($tp[$bank_code]);//是否互联网支付
            $bank = array(
                'id' => $item['bcid'],
                'code' => $bank_code,
                'name' => $item['bankname'],
                'css' => $item['bankcss'],
                'type' => 'bank',
                'cls' => '',
                'desc' => $is_tp ? 'tp' : 'bank',
                'order' => $item['displayorder'],
            );

            $show_field = $item['showfield'];
            if ($is_tp && $show_field != 2) {
                $rs[] = $bank;
            } else {
                if ($show_field == 1) {
                    $ebank[] = $bank;
                    $atm_bank[] = $bank;
                } elseif ($show_field == 2) {
                    $ebank[] = $bank;
                } elseif ($show_field == 3) {
                    $atm_bank[] = $bank;
                }
            }
        }

        $ebank = $this->bank_unique($ebank);
        $atm_bank = $this->bank_unique($atm_bank);


        foreach ($pay_platforms as $item) {
            $tbname = $item['tbname'];
            $code = str_replace('api_payplugin_', '', $tbname);
            $id = $item['id'];
            if ($id == 1007) $has_hee_pay = true;
            $rs[] = array(
                'id' => $item['id'],
                'code' => $code,
                'name' => $item['ptalias'],
                'css' => $code . 'css',
                'type' => 'others',
                'cls' => '',
                'desc' => $code == 'yeepaycard' ? 'card' : 'others',
                'order' => $item['displayorder'],
            );
        }
        usort($rs, function ($a, $b) {
            if ($a['order'] == $b['order']) {
                return 0;
            } else {
                return $a['order'] > $b['order'] ? -1 : 1;
            }
        });
        if ($rs) $rs[0]['cls'] = 'current';
        return $rs;
    }

    private function bank_unique($banks)
    {
        $bank_codes = array();
        $temps = array();
        foreach ($banks as $key => $val) {
            $bankcode = $val['code'];
            if (in_array($bankcode, $bank_codes)) {
                continue;
            } else {
                $bank_codes[] = $bankcode;
                $temps[] = $val;
            }
        }
        return $temps;
    }


    /**
     * 获取存款相关配置参数
     * @param $cfg
     * @return array
     */
    private function get_deposit_cfg($cfg)
    {
        $emin = $amin = $onmin = $atpmin = $gcardmin = '100';
        $emax = $amax = $onmax = $atpmax = $gcardmax = '50000';
        if (!empty($cfg)) {
            //网银
            $emin = $cfg['depositofebankmin'];
            $emax = $cfg['depositofebankmax'];
            //ATM
            $amin = $cfg['depositofatmmin'];
            $amax = $cfg['depositofatmmax'];
            //三方支付
            $onmin = $cfg['depositofopaymin'];
            $onmax = $cfg['depositofopaymax'];

            //微信，财付通
            $atpmin = DictUtil::get('depositofatmtpmin', $cfg);
            $atpmax = DictUtil::get('depositofatmtpmax', $cfg);
            $atpmin = $atpmin > 0 ? $atpmin : $amin;
            $atpmax = $atpmax > 0 ? $atpmax : $amax;

            //点卡
            $gcardmin = DictUtil::get('depositofatmtpmin', $cfg);
            $gcardmax = DictUtil::get('depositofatmtpmax', $cfg);
            $gcardmin = $gcardmin > 0 ? $gcardmin : $onmin;
            $gcardmax = $gcardmax > 0 ? $gcardmax : $onmax;

        }
        return array(
            'emin' => $emin, 'emax' => $emax,
            'amin' => $amin, 'amax' => $amax,
            'onmin' => $onmin, 'onmax' => $onmax,
            'atpmin' => $atpmin, 'atpmax' => $atpmax,
            'gcardmin' => $gcardmin, 'gcardmax' => $gcardmax,
        );
    }

    public function actionFinishDpt()
    {
        $status = $this->post('status');
        try {
            $result = Fund::finishDpt($this->uid, $status);
            $process = Fund::getDepositStatus($this->uid);//最新的玩家未反馈的存款申请
            $process_count = Fund::getDepositProcCount($this->uid);//客服未审核的存款条数
            $cfg = ApiConfig::getApiConfig(ApiConfig::DEPOSIT_CATE);
            $allow_count = empty($cfg['allowprocesscount']) ? 1 : (int)$cfg['allowprocesscount'];
            $allow_deposit = true;    //是否可以进行存款
            if ($allow_count > 0) {
                $allow_deposit = $allow_count > $process_count;
            }
            $allow = !$process && $allow_deposit;
            $process = empty($process) ? 0 : 1;
            $allow = empty($allow) ? 0 : 1;

            if ($result == 1) {
                $this->success(array('process' => $process, 'allow' => $allow), '本次存款申请状态变更已经提交客服审核，您可以再次存款！');
            } else {
                $this->success(array('process' => $process, 'allow' => $allow), '您之前的存款已被审核，您可以再次存款！');
            }
        } catch (Exception $e) {
            Yii::log($e->getMessage(), 'error');
        }
        $this->fail('操作失败');
    }

    /**
     * 转账
     */
    public function actionTransfer()
    {
        $platforms = GamePlatform::get_all_account_dict();
        $this->render('transfer', array('platforms' => $platforms));
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
            $syslan = FLanguage::Get_L_Code();
            if($syslan != 'cn'){
              $winfo = 'Transactions only between $' . $cfg['withdrawmin'] . ' to $' . $cfg['withdrawmax'] . '. Times of transactions per day：' . $cfg['withdrawdaylimit'];
            }
            else{
              $winfo = '单笔取款范围' . $cfg['withdrawmin'] . '元至' . $cfg['withdrawmax'] . '元。每天最高取款次数：' . $cfg['withdrawdaylimit'] . '次';
            }
            $wmin = $cfg['withdrawmin'];
            $wmax = $cfg['withdrawmax'];
            if (isset($cfg['withdrawdotyn'])) $wdotyn = $cfg['withdrawdotyn'];
            if (!empty($cfg['watercheckonoff'])) {
                $watercheckonoff = $cfg['watercheckonoff'];
            }
        }

        $this->render('withdraw', array(
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
}
