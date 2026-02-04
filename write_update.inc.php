<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

include_once($board_skin_path.'/upload_file.php');

if($cookie){
	setcookie('MMB_PW', $_POST['wr_password'], 2678400); //@200907
	setcookie('MMB_NAME', $_POST['wr_name'], 2678400); //@200907
}
else {
	setcookie('MMB_PW','');
	setcookie('MMB_NAME','');
}
	$customer_sql = "";
	
	if($w!='c' && $w!='cu'){
		$sec="";
		$mem=0;
		$protect="";
		if($set_secret) {

			if($set_secret=='secret'){
				$sec="secret";
			}
			else if ($set_secret =='member') {
				$mem=1;
			}
			else if($set_secret == 'protect' && $wr_protect!=''){
				$protect=$wr_protect;
			}
		}
		$customer_sql .= " ,wr_option='$html,$sec', wr_secret='{$mem}' , wr_protect= '{$wr_protect}'";
		//@200906 변수($sec,$mem,$protect,윗줄의 $customer_sql) 위치 이동
	}
	if($game == "dice") {
		// 주사위 굴리기
		$dice1 = rand(1, 6);
		$dice2 = rand(1, 6);
		$customer_sql .= " , wr_dice1 = '{$dice1}',  wr_dice2 = '{$dice2}'";
	} 
	
	$temp = sql_fetch("select * from {$write_table}"); 
	if(!isset($temp['wr_video'])){
	sql_query(" ALTER TABLE `{$write_table}` ADD `wr_video` text NOT NULL DEFAULT '' AFTER `wr_url` ");
		}
	if(!isset($temp['wr_reply_more'])){
		sql_query(" ALTER TABLE `{$write_table}` ADD `wr_reply_more` int(11) NOT NULL DEFAULT '0' AFTER `wr_plip` ");
		} 
	if(!isset($temp['wr_text'])){
		sql_query(" ALTER TABLE `{$write_table}` ADD `wr_text` TEXT NOT NULL DEFAULT '' AFTER `wr_url` ");
		} 
	if(!isset($temp['wr_protect'])){
	sql_query(" ALTER TABLE `{$write_table}` ADD `wr_protect` varchar(255) NOT NULL DEFAULT '' AFTER `wr_url` ");
	}
	unset($temp);
	$customer_sql .= ",wr_reply_more = '{$wr_reply_more}', wr_video = '{$wr_video}', wr_text= '{$wr_text}'";

?>

