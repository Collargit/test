<?php
if (!defined('_GNUBOARD_')) exit;
if ($is_comment_write) {
	if($w == '') $w = 'c';
?>
<aside class="bo_vc_w" id="bo_vc_w_<?=$list_item['wr_id']?>">
	<form name="fviewcomment_<?=$list_item['wr_id']?>" action="./write_comment_update.php" onsubmit="return fviewcomment_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
	<input type="hidden" name="w" value="<?php echo $w ?>" class="w">
	<input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
	<input type="hidden" name="wr_id" value="<?php echo $list_item['wr_id'] ?>" class="wr_id">
	<input type="hidden" name="comment_id" value="" class="co_id">
    <input type="hidden" name="wr_subject" value="COMMENT"/>

	<div class="input-comment"> 
        <div class="cmt-toolbar">
            <button type="button" class="btn-cmt-tool txtggu" onclick="setActiveTextarea(document.getElementById('wr_content_<?=$list_item['wr_id']?>')); openTextDeco();" title="텍스트 꾸미기">텍꾸</button>
            <button type="button" class="btn-cmt-tool emoticon" onclick="setActiveTextarea(document.getElementById('wr_content_<?=$list_item['wr_id']?>')); openEmoticon();" title="이모티콘">이모티콘</button>
        </div>

        <textarea name="wr_content" id="wr_content_<?=$list_item['wr_id']?>" placeholder="댓글을 입력하세요" 
    onfocus="setActiveTextarea(this);" onclick="setActiveTextarea(this);"></textarea>
		
        <div class="cmt_upload_box" style="margin-top:10px; background:#f5f5f5; padding:10px; border-radius:8px;">
            <p style="font-size:11px; margin-bottom:5px; color:#888;">▼ 이미지 첨부 (최대 4장)</p>
            <?php for($k=0; $k<4; $k++) { ?>
                <input type="file" name="bf_file[]" class="frm_file" style="font-size:11px; width:100%; margin-bottom:3px;">
            <?php } ?>
        </div>

		<div class="btn_confirm">
            <div style="display:flex; gap:5px; align-items:center;">
            <?php if(!$is_member) { ?>
                <input type="text" name="wr_name" placeholder="이름" required class="frm_input" style="width:80px; font-size:11px;">
                <input type="password" name="wr_password" placeholder="비번" required class="frm_input" style="width:80px; font-size:11px;">
            <?php } ?>
            </div>
			<button type="submit" class="ui-comment-submit">작성</button>
		</div>
	</div>
	</form>
</aside>
<?php } ?>