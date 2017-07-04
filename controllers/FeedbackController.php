<?php


class FeedbackController extends Controller
{

    private function log($content)
    {
        if (is_array($content)) {
            $content = json_encode($content);
        }
        $log = new Logger();
        $log->log($this->action->id, $content);
    }

    public function actionTh()
    {
        $th = new TongHuiPay();
        $type = $this->get("notify_type");
        $rs = "fail";
        if ($type == "back_notify" && $type == "back_notify1") {
            $flag = $th->feedback($_GET);
            if ($flag) {
                $rs = "success";
            }
        } else {
            $rs = "error notify type.";
        }
        echo $rs;
        exit;
    }

    public function actionXb()
    {
        $rs = "fail";
        if (!empty($_POST)) {
            $pay = new XBeiPay();
            $flag = $pay->feedback($_POST);
            if ($flag) {
                $rs = "OK";
            }
        }
        echo $rs;
        exit;
    }


    public function actionXunhui()
    {
        $rs = "fail";
        if (!empty($_REQUEST)) {
            $pay = new XunHuiPay();
            $flag = $pay->feedback($_REQUEST);
            if ($flag) {
                $rs = "success";
            }
        }
        echo $rs;
        exit;
    }

    public function actionXinbao()
    {
        $rs = "fail";
        if (!empty($_REQUEST)) {
            $pay = new XinBaoPay();
            $flag = $pay->feedback($_REQUEST);
            if ($flag) {
                $rs = "ok";
            }
        }
        echo $rs;
        exit;
    }

    public function actionYinbao()
    {
        $rs = "fail";
        if (!empty($_GET)) {
            $pay = new YinBaoPay();
            $flag = $pay->feedback($_REQUEST);
            if ($flag) {
                $rs = "ok";
            }
        }
        echo $rs;
        exit;
    }

    public function actionWorth()
    {
        $rs = "fail";
        if (!empty($_POST)) {
            $pay = new WorthPay();
            $flag = $pay->feedback($_POST);
            if ($flag) {
                $rs = "success";
            }
        }
        echo $rs;
        exit;
    }

    public function actionOneSecondPay()
    {
        $rs = "fail";
        if (!empty($_REQUEST)) {
            $pay = new OneSecondPay();
            $flag = $pay->feedback($_REQUEST);
            if ($flag) {
                $rs = "success";
            }
        }
        echo $rs;
        exit;
    }

    public function actionLefubao()
    {
        if (!empty($_POST) && $_POST["notifyType"] == 1) {
            $rs = "fail";
            $pay = new Lefubao();
            $flag = $pay->feedback($_POST);
            if ($flag) {
                $rs = "success";
            }
            echo $rs;
            exit;
        }
        $rs = new Result(true);
        $title = '提交充值成功！';
        $this->render('/home/result', array('rs' => $rs, 'title' => $title));

    }

    public function actionJeanPay()
    {
        $rs = "fail";
        if (!empty($_REQUEST)) {
          $pay = new JeanPay();
          $flag = $pay->feedback($_REQUEST);
          if ($flag) {
              $rs = "success";
          }
        }
        echo $rs;
        exit;
    }

    public function actionXun()
    {
        $rs = "fail";
        if (!empty($_POST)) {
            $pay = new XunPay();
            $flag = $pay->feedback($_POST);
            if ($flag) {
                $rs = "success";
            }
        }
        echo $rs;
        exit;
    }

    public function actionHuifubao()
    {
        if (!empty($_POST)) {
            $type = $_POST["notifyType"];
            if ($type == 1) {
                $rs = "fail";
                $pay = new HuiFuBaoPay();
                $flag = $pay->feedback($_POST);
                if ($flag) {
                    $rs = "SUCCESS";
                }
                echo $rs;
                exit;
            }
        }
        $rs = new Result(true);
        $title = '提交充值成功！';
        $this->render('/home/result', array('rs' => $rs, 'title' => $title));
    }

    public function actionFun()
    {
        $rs = "fail";
        if (!empty($_POST)) {
            $pay = new FunPay();
            $flag = $pay->feedback($_POST);
            if ($flag) {
                $rs = "success";
            }
        }
        echo $rs;
        exit;
    }

    public function actionZhiHui()
    {
        $rs = "fail";
        if (!empty($_POST)) {
            $pay = new ZhiHuiPay();
            $flag = $pay->feedback($_POST);
            if ($flag) {
                $rs = "SUCCESS";
            }
        }
//		$_POST['rs'] = $rs;
//		$this->log($_POST);
        echo $rs;
        exit;
    }

    public function actionXYWalletPay()
    {
        $rs = "fail";
        if (!empty($_POST)) {
            $pay = new XYWalletPay();
            $flag = $pay->feedback($_POST);
            if ($flag) {
                $rs = "success";
            }
        }
        echo $rs;
        exit;
    }

    public function actionDuoBaoPay()
    {
        $rs = "fail";
        if (!empty($_REQUEST)) {
            $pay = new DuoBaoPay();
            $flag = $pay->feedback($_REQUEST);
            if ($flag) {
                $rs = "opstate=0";
            }
        }
        echo $rs;
        exit;
    }

    public function actionNet()
    {
        $rs = "fail";
        if (!empty($_POST)) {
            $pay = new NetPay();
            $flag = $pay->feedback($_POST);
            if ($flag) {
                $rs = "SUCCESS";
            }
        }
        echo $rs;
        exit;
    }

    public function actionNewSafe()
    {
        $rs = "fail";
        if (!empty($_REQUEST)) {
            $pay = new NewSafePay();
            $flag = $pay->feedback($_REQUEST);
            if ($flag) {
                $rs = "success";
            }
        }
        echo $rs;
        exit;
    }

    public function actionHuaRen()
    {
        $rs = "fail";
        $getData = $_REQUEST;
        $pay_result = file_get_contents('php://input');
        if (!empty($pay_result)) {
            $getData["data"] = $pay_result;
            $pay = new HuaRenPay();
            $flag = $pay->feedback($getData);
            if ($flag) {
                $rs = "[{result:'ok'}]";
            }
        }
        echo $rs;
        exit;
    }
	
	public function actionSuperstar(){
		 $rs = "fail";
        if (!empty($_POST)) {
            $pay = new SuperstarPay();
            $flag = $pay->feedback($_POST);
            if ($flag) {
                $rs = "success";
            }
        }
		$_POST['rs'] = $rs;
		$this->log($_POST);
        echo $rs;
        exit;
	}

	public function actionBeeepay(){
		$rs = "fail";
		
        if (!empty($_POST)) {
            $pay = new BeeePay();
            $flag = $pay->feedback($_POST);
            if ($flag) {
                $rs = "success";
            }
		}

        echo $rs;
        exit;
	}
	
	public function actionEpaytrust(){
		$rs = "fail";

        if (!empty($_POST)) {
            $pay = new EpayTrust();
            $flag = $pay->feedback($_POST);
            if ($flag) {
                $rs = "success";
            }
		}

        echo $rs;
        exit;
	}
	
	public function actionUYepay(){
		$rs = "fail";
        if (!empty($_POST)) {
            $pay = new UYePay();
            $flag = $pay->feedback($_POST);
            if ($flag) {
                $rs = "OK";
            }
		}

        echo $rs;
        exit;
	}
	
    public function actionRongPay()
    {
        $rs = "fail";
        if (!empty($_POST)) {
            $pay = new RongPay();
            $flag = $pay->feedback($_POST);
            if ($flag) {
                $rs = "success";
            }
        }
        echo $rs;
        exit;
    }

    public function actionGMStonePay()
    {
        $rs = "fail";
        if (!empty($_REQUEST)) {
            $type = $_REQUEST["r9_BType"];
            if ($type == 2) {

                $pay = new GMStonePay();
                $flag = $pay->feedback($_REQUEST);
                if ($flag) {
                    $rs = "success";
                    echo $rs;
                    exit;
                }
            }else {
                if($_REQUEST["r1_Code"] == 1){
                    $rs = new Result(true);
                    $title = '提交充值成功！';
                    $this->render('/home/result', array('rs' => $rs, 'title' => $title));
                    exit;
                }
            }
        }
        echo $rs;
        exit;

    }

    public function actionTPPPay()
    {
        $data = file_get_contents('php://input');
        $this->log($data);
        $rs = "99";
        if (!empty($data)) {
            $pay = new TPPPay();
            $flag = $pay->feedback($data);
            if ($flag) {
                $rs = "1";
            }
        }
        echo $rs;
        exit;
    }

    public function actionNewTHPay()
    {
        $rs = "fail";
        if (!empty($_REQUEST)) {
            $pay = new NewTongHuiPay();
            $flag = $pay->feedback($_REQUEST);
            if ($flag) {
                $rs = "1";
            }
        }
        echo $rs;
        exit;
    }

    public function actionAloGatewayPay()
    {
        $rs = "fail";
        if (!empty($_POST)) {
            $pay = new AloGatewayPay();
            $flag = $pay->feedback($_POST);
            if ($flag) {
                $rs = "OK";
            }else if($_POST["status"] != "A0"){
                $rs = $_POST['message'];
            }
        }
        echo $rs;
        exit;
    }


}
