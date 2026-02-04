<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

$it = array();
$customer_sql = "";
$temp_wr_id = $wr_id;
if(!$wr_num) $wr_num = $write['wr_num'];



if($write['wr_ing'] != '1' || $w == 'u') { 

	// 췩이 아니거나 글이 수정하고 난 뒤일때 
	include_once($board_skin_path.'/write_update.inc.php');


	if($wr_type=='TEXT') {
		$customer_sql .= ",wr_width = '{$t_width}', wr_height = '{$t_height}' ";
	}

	//$customer_sql .= " , wr_ing = 0 ";
	$sql = " update {$write_table}
				set wr_id = '{$wr_id}'
				{$customer_sql}
			  where wr_id = '{$wr_id}' ";
	sql_query($sql);

}


if ($file_upload_msg) {
	alert($file_upload_msg, G5_HTTP_BBS_URL.'/board.php?bo_table='.$bo_table);
} else {
	goto_url(G5_HTTP_BBS_URL.'/board.php?bo_table='.$bo_table.$qstr."#log_".$wr_id);
}
?>
