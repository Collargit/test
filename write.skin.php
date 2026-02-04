<?php
if (!defined('_GNUBOARD_')) exit; 
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);

$is_error = false;
$option = '';
$option_hidden = '';

if(!$is_error) { 
	$is_category = false;
	$category_option = '';
	if ($board['bo_use_category']) {
		$ca_name = "";
		if (isset($write['ca_name'])) $ca_name = $write['ca_name'];
		$category_option = get_category_option($bo_table, $ca_name);
		$is_category = true;
	}
?>

<div id="load_log_board">
	<section id="bo_w" class="mmb-board<?if($board['bo_use_chick']){echo " chick";}?>">
		<form name="fwrite" id="fwrite" action="<?php echo $action_url ?>" onsubmit="return fwrite_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
		<input type="hidden" name="uid" value="<?php echo get_uniqid(); ?>">
		<input type="hidden" name="w" value="<?php echo $w ?>">
		<input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
		<input type="hidden" name="wr_id" value="<?php echo $wr_id ?>">
		<input type="hidden" name="sca" value="<?php echo $sca ?>">
		<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
		<input type="hidden" name="stx" value="<?php echo $stx ?>">
		<input type="hidden" name="spt" value="<?php echo $spt ?>">
		<input type="hidden" name="sst" value="<?php echo $sst ?>">
		<input type="hidden" name="sod" value="<?php echo $sod ?>">
		<input type="hidden" name="page" value="<?php echo $page ?>">
		
        <div style="margin-bottom:15px; text-align:center;">
             <input type="text" name="wr_subject" value="<?=$write['wr_subject']?>" required class="frm_input full" placeholder="제목을 입력하세요" style="width:100%; max-width:600px; padding:10px; font-weight:bold; font-size:14px; text-align:center;">
        </div>

        <?if(!$is_member) { ?> 
        <div class="guest-box" style="margin-bottom:10px; text-align:center;">
            <input type="text" name="wr_name" value="<?=$name?>" placeholder="이름" required class="frm_input" style="width:120px;"> 
            <input type="password" name="wr_password" value="<?=$password?>" placeholder="비밀번호" required class="frm_input" style="width:120px;">
        </div> 
		<? } ?>

        <div class="txt-center option_box" style="margin-bottom:15px;">
    		<?php if ($is_category) { ?>
    			<select name="ca_name" id="ca_name" required class="required">
    			<option value="">카테고리 선택</option>
    			<?php echo $category_option ?>
    			</select> 
    		<?php } ?>
            <select name="wr_type" onchange="fn_log_type(this.value);">
                <option value="UPLOAD" <?=$write['wr_type'] == "UPLOAD" ? "selected" : ""?>>이미지 업로드</option>
                <option value="URL" <?=$write['wr_type'] == "URL" ? "selected" : ""?>>외부 이미지(URL)</option>
                <option value="TEXT" <?=$write['wr_type'] == "TEXT" ? "selected" : ""?>>텍스트(소설)</option>
            </select>
        </div>

        <div class="upload_box" style="background:#f9f9f9; padding:15px; border-radius:10px;">
			<div id="add_UPLOAD" <?=($write['wr_type'] == "UPLOAD" || $write['wr_type'] == "") ? "":"style='display: none;'"?>>
                <?php for($i=0; $is_file && $i<$file_count; $i++) { ?>
				    <div style="margin-bottom:5px;">
                        <span style="font-size:11px; color:#888;">FILE #<?=$i+1?></span>
                        <input type="file" name="bf_file[]" title="파일첨부 <?=$i+1?>" class="frm_file frm_input" />
                        <?php if($w == 'u' && $file[$i]['file']) { ?>
                            <br><input type="checkbox" id="bf_file_del<?=$i?>" name="bf_file_del[<?php echo $i;  ?>]" value="1"> <label for="bf_file_del<?=$i?>"><?php echo $file[$i]['source'].'('.$file[$i]['size'].')';  ?> 파일 삭제</label>
                        <?php } ?>
                    </div>
                <?php } ?>
			</div>
			
            <div id="add_URL" <?=$write['wr_type'] == "URL" ? "": "style='display: none;'"; ?>>
				<input type="text" name="wr_url" value="<?=$write['wr_url']?>" id="wr_url" class="frm_input full" placeholder="http://..." style="width:100%;"/>
			</div>
			
            <div id="add_TEXT" <?=$write['wr_type'] == "TEXT" ? "" : "style='display: none;'"?>>
				<textarea id="wr_text" name="wr_text" class="frm_input" style="width:100%; height:200px;" placeholder="내용을 입력하세요"><?=$write['wr_text']?></textarea>
			</div>
		</div>

        <div class="comments" style="margin-top:15px;"> 
            <textarea name="wr_content" id="wr_content" class="frm_input" style="width:100%; height:80px;" placeholder="코멘트 / 설명 (선택사항)"><?php echo $write['wr_content'] ?></textarea>
		</div>	
	
        <div class="txt-center" style="margin-top:20px; display:flex; justify-content:center; gap:5px;">
            <button type="button" onclick="openTextDeco();" class="ui-btn" style="padding:0 15px; border-radius:20px; background:#fff; border:1px solid #ddd; color:#555;">텍꾸</button>
            <button type="button" onclick="openEmoticon();" class="ui-btn" style="padding:0 15px; border-radius:20px; background:#fff; border:1px solid #ddd; color:#555;">이모티콘</button>
			
            <button type="submit" id="btn_submit" accesskey="s" class="ui-btn point" style="padding:0 30px; border-radius:20px; font-weight:bold; height:35px;"><?=$w=='u' ? "수정완료":"등록하기";?></button>
			<a href="./board.php?bo_table=<?=$bo_table?>" class="ui-btn" style="padding:0 20px; border-radius:20px; background:#eee; height:35px;">취소</a>
		</div>

		</form>
	</section>
</div>

<textarea id="wr_content_bridge" style="display:none;"></textarea>

<script>
	function fn_log_type(type) { 
		$('#add_UPLOAD').hide();
		$('#add_URL').hide();
		$('#add_TEXT').hide();
		$('#add_'+type).show();
	}
    function fwrite_submit(f) {
        document.getElementById("btn_submit").disabled = "disabled";
        return true;
    }

    // [추가] 텍꾸/이모티콘 팝업 스크립트
    function openTextDeco() {
        window.open('<?php echo $board_skin_url; ?>/txtggu/index.php', 'txtggu', 'width=500,height=600,scrollbars=yes');
    }
    function openEmoticon() {
        window.open('<?php echo $board_skin_url; ?>/emoticon/index.php', 'emoticon', 'width=600,height=600,scrollbars=yes');
    }

    // 팝업에서 선택한 값을 본문(wr_content)에 넣는 로직
    // (일부 팝업은 opener.document.getElementById('wr_content')를 직접 찾음)
    // (일부는 opener.document.getElementById('wr_content').value에 추가함)
    // 만약 팝업이 id="wr_content"를 찾으면 위에서 추가한 id 덕분에 자동 작동함.
</script>
<? } ?>