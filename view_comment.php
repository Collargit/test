<?php
if (!defined('_GNUBOARD_')) exit; 

$comment = array();

$is_comment_write = false;
if ($member['mb_level'] >= $board['bo_comment_level'])
	$is_comment_write = true;

$sql = " select * from $write_table where wr_parent = '{$list_item['wr_id']}' and wr_is_comment = 1 order by wr_is_comment, wr_comment, wr_comment_reply ";
$result = sql_query($sql);

for ($c_i=0; $c_row=sql_fetch_array($result); $c_i++)
{ 
	$comment[$c_i] = $c_row;
	$comment[$c_i]['c_view'] = false;

    // [추가] 리플에 첨부된 파일 가져오기
    $sql_file = " select bf_file, bf_source, bf_width, bf_height from {$g5['board_file_table']} where bo_table = '$bo_table' and wr_id = '{$c_row['wr_id']}' order by bf_no ";
    $result_file = sql_query($sql_file);
    $cmt_files = array();
    while ($row_file = sql_fetch_array($result_file)) {
        $cmt_files[] = $row_file;
    }
    $comment[$c_i]['files'] = $cmt_files; // 파일 배열 저장

	$tmp_name = get_text(cut_str($c_row['wr_name'], $config['cf_cut_name'])); 
	if ($board['bo_use_sideview'])
		$comment[$c_i]['name'] = get_sideview($c_row['mb_id'], $tmp_name, $c_row['wr_email'], $c_row['wr_homepage']);
	else
		$comment[$c_i]['name'] = '<span class="'.($c_row['mb_id']?'member':'guest').'">'.$tmp_name.'</span>';

	$html = 0;
	if (strstr($comment[$c_i]['wr_option'], 'html1'))
	$html = 1;
	else if (strstr($comment[$c_i]['wr_option'], 'html2'))
	$html = 2;  

	$comment[$c_i]['content'] = $comment[$c_i]['content1']= '비밀글 입니다.';
	if (!strstr($c_row['wr_option'], 'secret') ||
		$is_admin ||
		($write['mb_id']==$member['mb_id'] && $member['mb_id']) ||
		($c_row['mb_id']==$member['mb_id'] && $member['mb_id'])) {
		$comment[$c_i]['c_view'] = true;
		$comment[$c_i]['content1'] = $c_row['wr_content'];
		$comment[$c_i]['content'] = conv_content($c_row['wr_content'], $html, 'wr_content');
		$comment[$c_i]['content'] = search_font($stx, $comment[$c_i]['content']);
	} else {
		$ss_name = 'ss_secret_comment_'.$bo_table.'_'.$comment[$c_i]['wr_id'];

		if(!get_session($ss_name)){
			$comment[$c_i]['c_view'] = false;
			if($comment[$c_i]['mb_id']!='') 
				$comment[$c_i]['content'] = '비공개 댓글입니다.';
			else 
				$comment[$c_i]['content'] = '<a href="./password.php?w=sc&amp;bo_table='.$bo_table.'&amp;wr_id='.$comment[$c_i]['wr_id'].$qstr.'" class="s_cmt">댓글내용 확인</a>';
		}
		else {
			$comment[$c_i]['c_view'] = true;
			$comment[$c_i]['content'] = conv_content($c_row['wr_content'], $html, 'wr_content');
			$comment[$c_i]['content'] = search_font($stx, $comment[$c_i]['content']);
		}
	}

	$comment[$c_i]['datetime'] = substr($c_row['wr_datetime'],2,14);
	$comment[$c_i]['ip'] = $c_row['wr_ip'];
	if (!$is_admin)
		$comment[$c_i]['ip'] = preg_replace("/([0-9]+).([0-9]+).([0-9]+).([0-9]+)/", G5_IP_DISPLAY, $c_row['wr_ip']);

	$comment[$c_i]['is_reply'] = false;
	$comment[$c_i]['is_edit'] = false;
	$comment[$c_i]['is_del']  = false;
	if ($is_comment_write || $is_admin)
	{
		$token = '';
		if ($member['mb_id']) {
			if ($c_row['mb_id'] == $member['mb_id'] || $is_admin) {
				set_session('ss_delete_comment_'.$c_row['wr_id'].'_token', $token = uniqid(time()));
				$comment[$c_i]['del_link']  = './delete_comment.php?bo_table='.$bo_table.'&amp;comment_id='.$c_row['wr_id'].'&amp;token='.$token.'&amp;page='.$page.$qstr;
				$comment[$c_i]['is_edit']   = true;
				$comment[$c_i]['is_del']    = true;
			}
		} else {
			if (!$c_row['mb_id']) {
				$comment[$c_i]['del_link'] = './password.php?w=x&amp;bo_table='.$bo_table.'&amp;comment_id='.$c_row['wr_id'].'&amp;page='.$page.$qstr;
				$comment[$c_i]['is_del']   = true;
			}
		}

		if (strlen($c_row['wr_comment_reply']) < 5)
			$comment[$c_i]['is_reply'] = true;
	}

	if ($i > 0 && !$is_admin) {
		if ($c_row['wr_comment_reply']) {
			$tmp_comment_reply = substr($c_row['wr_comment_reply'], 0, strlen($c_row['wr_comment_reply']) - 1);
			if ($tmp_comment_reply == $comment[$c_i-1]['wr_comment_reply']) {
				$comment[$c_i-1]['is_edit'] = false;
				$comment[$c_i-1]['is_del'] = false;
			}
		}
	}
}

if ($is_admin) {
	$comment_min = $comment_max = 0;
} else {
	$comment_min = (int)$board['bo_comment_min'];
	$comment_max = (int)$board['bo_comment_max'];
}

include($board_skin_path.'/view_comment.skin.php');
?>