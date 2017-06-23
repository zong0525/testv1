<?php


class TestController extends MController
{
    public function actionIndex()
    {
        echo "foo";exit;
    }

	public function actionLDGaming(){
		// echo "<head> <meta charset='UTF-8'> </head>";
		// $game = new LDGaming("a1", "testing003");
		// echo "<br/>進入遊戲 : <br/>".$game->loginLDGaming();
        
        $is_login = $this->is_login();
        $ldgaming_status = 0;
        $status = 0;
        if ($is_login == false) {
            $ldgaming_status = 1;
            $status = 1;
        }
        $this->render('LDGaming', array(
            "ldgaming_status" => $ldgaming_status,
            "status" => $status,
        ));
	}
}