<?php
if (!defined('_GNUBOARD_')) exit; 

for ($index=0; $index<count($comment); $index++) {

	$log_comment = $comment[$index];
	$comment_id = $log_comment['wr_id'];

	// [중요] 내용 처리 (HTML 태그 등)
	$content = $log_comment['content'];
	$content = preg_replace("/\[\<a\s.*href\=\"(http|https|ftp|mms)\:\/\/([^[:space:]]+)\.(mp3|wma|wmv|asf|asx|mpg|mpeg)\".*\<\/a\>\]/i", "<script>doc_write(obj_movie('$1://$2.$3'));</script>", $content);
 
    // 답변/수정/삭제 시 링크 처리
	if($log_comment['wr_id'] != $log_comment['wr_id'] && ($log_comment['is_reply'] || $log_comment['is_edit'] || $log_comment['is_del'])) {
		$query_string = str_replace("&", "&amp;", $_SERVER['QUERY_STRING']);
		if($w == 'cu') {
			$sql = " select wr_id, wr_content from $write_table where wr_id = '$indexd' and wr_is_comment = '1' ";
			$cmt = sql_fetch($sql);
			$c_wr_content = $cmt['wr_content'];
		}
		$c_reply_href = './board.php?'.$query_string.'&amp;c_id='.$comment_id.'&amp;w=c#bo_vc_w_'.$list_item['wr_id'];
		$c_edit_href = './board.php?'.$query_string.'&amp;c_id='.$comment_id.'&amp;w=cu#bo_vc_w_'.$list_item['wr_id'];
	}

	$is_comment_owner = false;
	$comment_owner_front = "";
	$comment_owner_behind = "";

	if(!$log_comment['wr_noname']) { 
		$log_comment['name'] = $log_comment['wr_name'];
		if($list_item['mb_id']!='' && $list_item['mb_id'] == $log_comment['mb_id']) { 
			$is_comment_owner = true;
			$comment_owner_front = $owner_front;
			$comment_owner_behind = $owner_behind;
		}
	} else {
		$is_comment_owner = false;
	}
	   
	$cmt_depth = strlen($log_comment['wr_comment_reply']) * 10;
	$has_child=sql_fetch("select wr_id from {$write_table} where wr_parent='{$list_item['wr_id']}' and wr_comment='{$log_comment['wr_comment']}' and wr_comment_reply!='' order by wr_comment_reply desc limit 1");
?>

<div class="<?=$cmt_depth>0 ? "item-reply ":"";?><?=($has_child['wr_id']&&($has_child['wr_id']!=$log_comment['wr_id'])) ? "parent " : "";?><?=$has_child['wr_id']==$log_comment['wr_id'] ? "last ": "";?>item-comment" id="c_<?php echo $comment_id ?>" style="padding-left:<?=$cmt_depth?>px;">
	<div class="co-header">
		<? if(!$log_comment['wr_noname']) { ?>
		<p <?=$is_comment_owner ? ' class="owner"' : ''?>>
			<?=$comment_owner_front?>
			<strong><?=$log_comment['ch_name'].$log_comment['name']?></strong>
			<?=$comment_owner_behind?>
		</p>
		<? } else { ?>
		<p>익명의 누군가</p>
		<? } ?>
        <div class="co-footer">
            <?php if ($log_comment['is_del'])  { ?>
                <a href="<?php echo $log_comment['del_link']; ?>" onclick="return comment_delete();" class="btn-cmt del">삭제</a>
            <?php } ?>
            <?php if ($log_comment['is_edit']) { ?>
                <a href="<?php echo $c_edit_href; ?>" onclick="comment_box('<?=$list_item['wr_id']?>','<?php echo $comment_id ?>', 'cu'); return false;" class="btn-cmt mod">수정</a>
            <?php } ?>
            <? if ($log_comment['is_reply'] && $log_comment['wr_is_comment']!=0) { ?>
                <a href="<? echo $c_reply_href; ?>" onclick="comment_box('<?=$list_item['wr_id']?>','<? echo $comment_id ?>', 'c'); return false;" class="btn-cmt re">답글</a>
            <? } ?>
            <span class="date"><?=date('Y.m.d H:i', strtotime($log_comment['wr_datetime']))?></span>	
        </div>
	</div>

	<div class="co-content">
        <div id="original_comment_show_<?php echo $comment_id ?>">
            <?if($log_comment['wr_1']){?>
                <p><span class="co-memo highlight"><font color="#97c3d1">memo</span>&nbsp;<?=$log_comment['wr_1']?></font></p>
            <?}?>
            <?if(strstr($log_comment['wr_option'],"secret")){?>
                <span class="co-secret highlight">#secret</span>
            <?}else if ($log_comment['wr_secret']==1){?>
                <span class="co-member highlight">member only</span> 
            <?}?>

            <?php
            $cmt_files = $log_comment['files'];
            $file_cnt = count($cmt_files);
            
            if ($file_cnt > 0 && $log_comment['c_view']==true) {
                // 3장일 때 grid-3 클래스 명시
                $grid_class = "grid-" . ($file_cnt > 4 ? 4 : $file_cnt);
            ?>
                <div class="comment-img-grid <?=$grid_class?>">
                    <?php foreach($cmt_files as $cfile) { 
                        $img_src = G5_DATA_URL.'/file/'.$bo_table.'/'.$cfile['bf_file'];
                    ?>
                        <img src="<?=$img_src?>" class="lightbox_trigger" alt="comment image">
                    <?php } ?>
                </div>
            <?php } ?>
			
			<?php
			if($html==2){ 
				echo autolink(emote_ev($log_comment['content']), $bo_table, $stx);
			}else{
				$log_comment['content'] = autolink($log_comment['content'], $bo_table, $stx); 
				$log_comment['content'] = emote_ev($log_comment['content']); 
				echo $log_comment['content'];
			}?>
        </div>

        <span id="edit_<? echo $comment_id ?>" class="bo_vc_w"></span>
		<span id="reply_<? echo $comment_id ?>" class="bo_vc_w"></span>
		
        <div class="modify_area" id="save_comment_<?php echo $comment_id ?>" style="display:none;"> 
			<textarea id="save_co_comment_<?php echo $comment_id ?>"><?php echo get_text($log_comment['content1'], 0) ?></textarea>
		</div>

	</div>
</div>
<? } ?>