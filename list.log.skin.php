<?php
if (!defined("_GNUBOARD_")) exit;

$update_href = $delete_href = '';
if (($member['mb_id'] && ($member['mb_id'] == $list_item['mb_id'])) || $is_admin) {
	$update_href = './write.php?w=u&amp;bo_table='.$bo_table.'&amp;wr_id='.$list_item['wr_id'].'&amp;page='.$page.$qstr;
	set_session('ss_delete_token', $token = uniqid(time()));
	$delete_href ='./delete.php?bo_table='.$bo_table.'&amp;wr_id='.$list_item['wr_id'].'&amp;token='.$token.'&amp;page='.$page.urldecode($qstr);
}

// [수정] 실제 유효한(삭제되지 않은) 댓글 수 직접 카운트
$sql_cnt = " select count(*) as cnt from {$write_table} where wr_parent = '{$list_item['wr_id']}' and wr_is_comment = 1 and wr_content not like '%삭제%' ";
$row_cnt = sql_fetch($sql_cnt);
$real_comment_count = $row_cnt['cnt'];

// 이미지 처리
$slider_images = array();
$files = get_file($bo_table, $list_item['wr_id']);
for ($k=0; $k<count($files); $k++) {
    if ($files[$k]['file']) {
        $img_src = G5_DATA_URL.'/file/'.$bo_table.'/'.$files[$k]['file'];
        $slider_images[] = '<img src="'.$img_src.'" alt="image">';
    }
}
if($list_item['wr_type'] == 'URL' && $list_item['wr_url']) {
     $slider_images[] = '<img src="'.$list_item['wr_url'].'" alt="url image">';
}

// [수정] 댓글 이미지 가져오기 (삭제된 댓글 확실히 제외)
// wr_content가 '삭제'를 포함하거나, GnuBoard 시스템상 삭제된 댓글 표시인 경우 제외
$sql_cmt_img = " select a.bf_file 
                 from {$g5['board_file_table']} a 
                 left join {$write_table} b on a.wr_id = b.wr_id 
                 where a.bo_table = '$bo_table' 
                   and b.wr_parent = '{$list_item['wr_id']}' 
                   and b.wr_is_comment = 1 
                   and b.wr_content not like '%삭제%' 
                 order by b.wr_id asc, a.bf_no asc ";

$result_cmt_img = sql_query($sql_cmt_img);
while ($row_img = sql_fetch_array($result_cmt_img)) {
    $img_src = G5_DATA_URL.'/file/'.$bo_table.'/'.$row_img['bf_file'];
    $slider_images[] = '<img src="'.$img_src.'" alt="comment image">';
}
?>

<div class="item" id="log_<?=$list_item['wr_id']?>">
    <div class="item-inner">
        <div class="ui-pic">
            
            <div class="pic-header">
                <div class="title-area">
                    <span style="font-size:15px; display:block;">
                        <?php if($list_item['is_notice']) { ?>
                            <span class="notice-badge">NOTICE</span>
                        <?php } ?>
                        <?=$list_item['wr_subject']?>
                    </span>
                    <span class="date-area">
                        <?=date('Y-m-d H:i', strtotime($list_item['wr_datetime']))?>
                    </span>
                </div>
                
                <div class="admin-btns">
    <a href="javascript:void(0);" class="mod" onclick="copy_log_link('<?php echo $list_item['wr_id']; ?>');">주소복사</a>
    
    <?php if (isset($is_view_page) && $is_view_page) { ?>
    <a href="<?php echo $list_href ?>" class="mod">목록</a>
<?php } ?>

    <?php if ($update_href) { ?><a href="<?php echo $update_href ?>" class="mod">수정</a><?php } ?>
    <?php if ($delete_href) { ?><a href="<?php echo $delete_href ?>" class="del" onclick="del(this.href); return false;">삭제</a><?php } ?>
</div>
            </div>

            <div class="pic-data">
                <?php if (!empty($slider_images)) { ?>
                    <div class="swiper mySwiper">
                        <div class="swiper-wrapper">
                            <?php foreach($slider_images as $img_tag) { ?>
                                <div class="swiper-slide"><?php echo $img_tag; ?></div>
                            <?php } ?>
                        </div>
                        <?php if(count($slider_images) > 1) { ?>
                            <div class="swiper-button-next"></div>
                            <div class="swiper-button-prev"></div>
                            <div class="swiper-pagination"></div>
                        <?php } ?>
                    </div>
                <?php } ?>

                <?php 
                if($list_item['wr_type'] == 'TEXT' || $list_item['wr_content']) { 
                    $view_content = ($list_item['wr_type'] == 'TEXT') ? $list_item['wr_text'] : $list_item['wr_content'];
                    $view_content = conv_content($view_content, 1);
                    if (function_exists('emote_ev')) $view_content = emote_ev($view_content);
                    if (function_exists('autolink')) $view_content = autolink($view_content, $bo_table, $stx);
                    if (function_exists('markup_text')) $view_content = markup_text($view_content);
                ?>
                    <div class="theme-box" style="text-align:left;"><?php echo trim($view_content); ?></div>
                <?php } ?>
            </div>

        </div>

        <?php if (isset($is_view_page) && $is_view_page) { ?>
            <div class="ui-comment" style="display:block;">
                <div class="item-comment-box">
                    <?php include($board_skin_path."/view_comment.php");?>
                </div>
                
                <div class="item-comment-form-box" style="display:block;">
                    <?php include($board_skin_path."/write_comment.php");?>
                </div> 
            </div>
        <?php } else { ?>
            <div style="padding: 0 30px 20px 30px; text-align:center;">
               <a href="./board.php?bo_table=<?=$bo_table?>&wr_id=<?=$list_item['wr_id']?>&page=<?=$page?>" class="btn-cmt-toggle" style="text-decoration:none; display:block;">
    자세히 보기 (댓글 <?=$real_comment_count?>)
</a>
            </div>
        <?php } ?>

    </div>
</div>