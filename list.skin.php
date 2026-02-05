<?php
if (!defined('_GNUBOARD_')) exit; 
include_once(G5_LIB_PATH.'/thumbnail.lib.php'); 

if ($is_admin && isset($_GET['cmd']) && $_GET['cmd'] == 'change_view') {
    $new_mode = ($_GET['mode'] === 'tile') ? 'tile' : 'gallery';
    // DB 업데이트
    sql_query(" update {$g5['board_table']} set bo_1 = '{$new_mode}' where bo_table = '{$bo_table}' ");
    // 페이지 새로고침
    goto_url(G5_BBS_URL.'/board.php?bo_table='.$bo_table);
}

$view_mode = (isset($board['bo_1']) && $board['bo_1']) ? $board['bo_1'] : 'gallery';

// [설정] 아보카도 에디션 포인트 컬러 가져오기 (관리자 설정 연동)
if(isset($color['color_point']) && $color['color_point']) {
    $point_color_hex = $color['color_point'];
} else if(isset($color_point) && $color_point) { 
    // 테마 버전에 따라 전역변수로 풀려있을 경우를 대비
    $point_color_hex = $color_point;
} else {
    $point_color_hex = '#97c3d1'; // 설정값이 없으면 기본 민트색
}

// 색상을 RGB로 변환 (투명도 계산용)
list($r, $g, $b) = sscanf($point_color_hex, "#%02x%02x%02x");

// [중요] 스타일시트와 스크립트는 여기서 먼저 로드해야 합니다.
add_stylesheet('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />', 0);
add_javascript('<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>', 0);
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0); 
add_stylesheet('<link rel="stylesheet" href="/css/textggu.css">', 1);

// 이모티콘 설정 파일 로드
$emoticon_setting_file = $board_skin_path.'/emoticon/_setting.emoticon.php';
if(file_exists($emoticon_setting_file)) {
    include_once($emoticon_setting_file);
}
?>

<style>

/* [수정] 모달/뷰 페이지 헤더 정렬 교정 */
/* 닫기 버튼이 사라진 자리를 메꾸기 위해 오른쪽 여백 제거 */
.ui-pic .pic-header {
    margin-right: 0 !important; 
}

/* [수정] 본문 내용(텍스트/이모티콘) 안에서는 '크게보기' 툴팁 완전 차단 */
.theme-box img {
    cursor: default !important; /* 커서 모양 기본으로 */
    pointer-events: auto !important;
}

/* style.css나 다른 곳에서 상속된 툴팁 효과를 강제로 끕니다 */
.pic-data:hover .theme-box img + ::after,
.theme-box *:hover::after,
.theme-box::after,
.theme-box img:hover::after { 
    content: none !important; 
    display: none !important; 
    background: none !important;
    opacity: 0 !important;
    width: 0 !important;
    height: 0 !important;
}

/* 카테고리/버튼 호버 */
:root {
    --point-color: <?=$point_color_hex?>;
}
#navi_category a:hover, #navi_category li.on a { background: <?=$point_color_hex?> !important; }
.ctrl-btn.txtggu:hover { color: <?=$point_color_hex?>; border-color: <?=$point_color_hex?>; }
.ui-pic .pic-header a.mod:hover { color: <?=$point_color_hex?>; }
.co-footer a.btn-cmt:hover { background: <?=$point_color_hex?>; border-color: <?=$point_color_hex?>; }

/* 배경색 + 투명도(RGBA) 적용 요소 */
.ui-paging strong { 
    background: transparent; 
}
.cate-badge { background: rgba(<?=$r?>, <?=$g?>, <?=$b?>, 0.9) !important; }
.btn_confirm .ui-comment-submit { 
    background: <?=$point_color_hex?> !important; 
    color: #333 !important; /* 글자색을 진한 회색으로 변경하여 흰 배경에서도 보이게 함 */
    box-shadow: 0 2px 5px rgba(0,0,0,0.1) !important;
    border: 1px solid rgba(0,0,0,0.1) !important; /* 테두리 추가 */
    font-weight: bold;
}

/* 스와이퍼(슬라이드) 관련 */
.swiper-button-next, .swiper-button-prev { color: <?=$point_color_hex?> !important; }
.swiper-pagination-bullet-active { background: <?=$point_color_hex?> !important; }
</style>

<div id="load_log_board">

    <?php if ($board['bo_content_head']) { ?>
        <div id="bo_content_head" style="margin-bottom:20px; padding:0; overflow:visible;">
            <?php echo $board['bo_content_head']; ?>
        </div>
    <?php } ?>
    <div class="ui-mmb-button">
        <?php if ($is_category) { ?>
        <nav id="navi_category">
            <ul><?php echo $category_option ?></ul>
        </nav>
        <?php } ?>
        
        <? if($write_pages) { ?><div class="ui-paging"><?php echo $write_pages; ?></div><? } ?>
    </div>
    
<div id="log_list" class="<?php echo ($view_mode == 'tile') ? 'tile-wrapper' : 'gallery-wrapper'; ?>">
    <?php
for ($i=0; $i<count($list); $i++) {
        $list_item = $list[$i];
        $wr_id = $list_item['wr_id'];

        // 비밀글 처리
        $is_secret = (isset($list_item['wr_option']) && strpos($list_item['wr_option'], 'secret') !== false);
        $is_protected = (!empty($list_item['wr_protect']));

        // 관리자 전용 비밀글은 관리자가 아니면 건너뛰기
        if($is_secret && !$is_admin) {
            continue;
        }
       
        // [변수 초기화]
        $col_span = 4; 
        
        $thumb_h = 600; 
        $thumb_w = 600; // 초기값 (파일 없을 경우 대비)

        $files = get_file($bo_table, $wr_id);
        $original_file = ""; 

        // 1. 원본 파일 정보 확인 및 비율 계산
        if (isset($files[0]['file']) && $files[0]['file']) {
            $original_file = G5_DATA_PATH.'/file/'.$bo_table.'/'.$files[0]['file'];
            
            if (is_file($original_file)) {
                $size = @getimagesize($original_file);
                if ($size[0] > 0 && $size[1] > 0) {
                    $org_w = $size[0];
                    $org_h = $size[1];
                    $ratio = $org_w / $org_h; // 가로 / 세로 비율
                   
                    // [타일형 Span 결정] (기존 유지)
                    if ($ratio < 0.8) { $col_span = 3; }       
                    elseif ($ratio < 1.3) { $col_span = 4; }   
                    elseif ($ratio < 1.8) { $col_span = 6; }   
                    else { $col_span = 8; }                    

                    // [핵심 수정] 썸네일 크기 안전 계산 로직
                    // 1. 원본 높이가 목표(600px)보다 작으면? -> 원본 크기 그대로 사용 (억지 확대 방지)
                    if ($org_h < $thumb_h) {
                        $thumb_h = $org_h;
                        $thumb_w = $org_w;
                    } else {
                        // 2. 원본이 충분히 크면 비율대로 계산
                        $thumb_w = intval($thumb_h * $ratio);
                    }

                    // 3. [추가] 계산된 너비가 너무 클 경우 (가로로 긴 이미지 여백 방지)
                    // 너비가 2500px을 넘어가면 서버 부하로 인해 흰 여백이 생길 수 있음 -> 강제 축소
                    if ($thumb_w > 2500) {
                        $thumb_w = 2500;
                        $thumb_h = intval($thumb_w / $ratio); // 줄어든 너비에 맞춰 높이 재계산
                    }
                }
            }
        }


        $thumb = get_list_thumbnail($bo_table, $wr_id, $thumb_w, $thumb_h, false, true); 
       
        // 썸네일 생성 실패 시 원본 사용
        if(!$thumb['src'] && $original_file) {
             $thumb['src'] = G5_URL.'/data/file/'.$bo_table.'/'.$files[0]['file'];
        }

        $img_file = ""; 
        

        // 비밀글/보호글 체크
        $is_secret = (strpos($list_item['wr_option'], 'secret') !== false);
        $is_protected = (!empty($list_item['wr_password']));
        $is_owner = ($member['mb_id'] && $member['mb_id'] == $list_item['mb_id']);
        $can_view = ($is_admin || $is_owner);

        // 보호글 비밀번호 입력 세션 체크 (비밀번호 입력 시 열람 가능)
        $has_password_session = get_session('ss_secret_'.$bo_table.'_'.$wr_id);
        $can_view_protected = ($can_view || $has_password_session);

        $thumb_content = "";

        // 비밀글인데 열람 권한이 없는 경우 (관리자/작성자만 가능)
        if($is_secret && !$can_view) {
            $thumb_content = '<div class="secret-thumb-box"><span class="secret-icon">🔒</span><span class="secret-text">비밀글 입니다.</span></div>';
        }
        // 보호글인데 열람 권한이 없는 경우 (비밀번호 입력 시 열람 가능)
        elseif($is_protected && !$can_view_protected) {
            $thumb_content = '<div class="protected-thumb-box"><span class="protected-icon">🔐</span><span class="protected-text">보호글 입니다.</span></div>';
        }
        // 정상적으로 볼 수 있는 경우
        elseif($thumb['src']) {
            $thumb_content = '<img src="'.$thumb['src'].'" alt="img">';
        } elseif ($list_item['wr_type'] == 'URL' && $list_item['wr_url']) {
            $thumb_content = '<img src="'.$list_item['wr_url'].'" alt="img">';
        } else {
            $txt_source = $list_item['wr_text'] ? $list_item['wr_text'] : $list_item['wr_content'];
            $txt_html = conv_content($txt_source, 1);
            if (function_exists('emote_ev')) $txt_html = emote_ev($txt_html);
            if (function_exists('markup_text')) $txt_html = markup_text($txt_html);
            if(trim($txt_html)) {
                $thumb_content = '<div class="text-thumb-box">'.$txt_html.'</div>';
            } else {
                $thumb_content = '<img src="'.$board_skin_url.'/img/no_image.png" alt="no image" style="opacity:0.1;">';
            }
        }
    ?>
        <div class="gallery-item item-box tile-span-<?php echo $col_span; ?>" data-protected="<?php echo $is_protected ? '1' : '0'; ?>" data-secret="<?php echo $is_secret ? '1' : '0'; ?>">
            <a href="javascript:void(0);" onclick="openLogModal('<?php echo $wr_id ?>', <?php echo $is_protected ? 'true' : 'false'; ?>);" class="item-link" title="<?php echo get_text($list_item['wr_subject']); ?>">
                
                <div class="thumb-container">
                    <?php echo $thumb_content; ?>
                    
                    <?php if($list_item['ca_name']) { ?>
                        <span class="cate-badge"><?php echo $list_item['ca_name'] ?></span>
                    <?php } ?>

                    <?php if($is_secret && $is_admin) { ?>
                        <span class="secret-badge admin-only" title="관리자 전용 비밀글">🔐</span>
                    <?php } ?>

                    <?php if($is_protected) { ?>
                        <span class="secret-badge protected" title="비밀번호 보호 게시글">🔑</span>
                    <?php } ?>
                    
                    <div class="item-overlay">
                        <div class="ov-text-wrap">
                            <span class="ov-title"><?=$list_item['wr_subject']?></span>
                            <span class="ov-date"><?=date('Y.m.d', strtotime($list_item['wr_datetime']))?></span>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div id="modal_log_<?php echo $wr_id ?>" class="log-modal-overlay" onclick="closeLogModal(event, '<?php echo $wr_id ?>');" data-protected="<?php echo $is_protected ? '1' : '0'; ?>">
            <div class="log-modal-content">
                <?php if($is_protected && !$is_admin) { ?>
                <!-- 비밀번호 보호 게시글 - 비밀번호 입력 폼 -->
                <div class="protect-password-form" id="protect_form_<?php echo $wr_id ?>">
                    <div style="padding:40px 30px; text-align:center;">
                        <div style="font-size:50px; margin-bottom:15px;">🔒</div>
                        <h3 style="margin:0 0 10px 0; font-size:16px; color:#333;">비밀번호로 보호된 게시글</h3>
                        <p style="margin:0 0 20px 0; font-size:13px; color:#888;">이 게시글을 열람하려면 비밀번호를 입력하세요.</p>
                        <div style="display:flex; justify-content:center; gap:8px; flex-wrap:wrap;">
                            <input type="password" id="protect_pw_<?php echo $wr_id ?>" class="frm_input" placeholder="비밀번호 입력" style="width:200px; padding:10px 15px; text-align:center; font-size:14px;" onkeypress="if(event.keyCode==13) checkProtectPassword('<?php echo $wr_id ?>');">
                            <button type="button" onclick="checkProtectPassword('<?php echo $wr_id ?>');" class="ui-btn point" style="padding:0 25px; border-radius:20px; font-weight:bold;">확인</button>
                        </div>
                        <p id="protect_error_<?php echo $wr_id ?>" style="margin:15px 0 0 0; font-size:12px; color:#d3393d; display:none;">비밀번호가 일치하지 않습니다.</p>
                    </div>
                </div>
                <!-- 실제 내용 (초기에는 숨김) -->
                <div class="protected-content" id="content_<?php echo $wr_id ?>" style="display:none;">
                <?php } ?>

                <div style="padding:0;">
                    <?php include($board_skin_path.'/list.log.skin.php'); ?>
                    
                    <div style="padding: 0 30px 20px 30px; margin-top: -20px;">
                        <?php
                        $prev_wr_id = isset($list[$i-1]) ? $list[$i-1]['wr_id'] : '';
                        $next_wr_id = isset($list[$i+1]) ? $list[$i+1]['wr_id'] : '';
                        ?>
                        <div style="display:flex; justify-content:space-between; margin-bottom:15px; font-size:12px; color:#999;">
                            <?php if($prev_wr_id) { ?>
                                <a href="javascript:void(0);" onclick="switchLogModal('<?=$wr_id?>', '<?=$prev_wr_id?>');" style="color:#999; text-decoration:none; display:flex; align-items:center;">
                                    <span style="font-size:14px; margin-right:5px;">‹</span> 이전 글
                                </a>
                            <?php } else { echo "<div></div>"; } ?>
                            <?php if($next_wr_id) { ?>
                                <a href="javascript:void(0);" onclick="switchLogModal('<?=$wr_id?>', '<?=$next_wr_id?>');" style="color:#999; text-decoration:none; display:flex; align-items:center;">
                                    다음 글 <span style="font-size:14px; margin-left:5px;">›</span>
                                </a>
                            <?php } else { echo "<div></div>"; } ?>
                        </div>
                        <button type="button" class="btn-cmt-toggle" onclick="toggleCommentForm('<?php echo $wr_id ?>')" style="display:none;">댓글 쓰기</button>
                    </div>
                </div>

                <?php if($is_protected && !$is_admin) { ?>
                </div><!-- .protected-content 닫기 -->
                <?php } ?>
            </div>
        </div>
    <?php } ?>
    
    <?php if (count($list) == 0) { echo "<div style='grid-column:1/-1; text-align:center; padding:50px 0; color:#999;'>등록된 로그가 없습니다.</div>"; } ?>
    </div>

<div class="bottom-control-box">
    <div class="sch_simple_box">
        <form name="fsearch" method="get">
            <input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
            <input type="hidden" name="sca" value="<?php echo $sca ?>">
            <input type="hidden" name="sop" value="and">
            <input type="hidden" name="sfl" value="wr_subject||wr_content">
            <input type="text" name="stx" class="sch_input" placeholder="SEARCH" value="<?php echo $stx ?>">
            <button type="submit" class="sch_btn" title="검색">
                <span class="material-icons" style="font-size:18px;">search</span>
            </button>
        </form>
    </div>
<div class="btn_group_right" style="display:flex; gap:5px; align-items:center;">
        <?php if ($write_href) { ?>
            <a href="<?php echo $write_href ?>" class="ctrl-btn write" title="LOG">✎</a>
        <?php } ?>
        <a href="<?php echo $list_href ?>" class="ctrl-btn refresh" title="새로고침">↻</a>
        <?php if($is_admin){ ?>
            <a href="<?php echo G5_ADMIN_URL ?>/board_form.php?bo_table=<?=$bo_table?>&w=u" class="ctrl-btn set" title="설정">⚙</a>
            
            <span style="width:1px; height:15px; background:#ddd; margin:0 5px;"></span>

            <a href="?bo_table=<?=$bo_table?>&cmd=change_view&mode=gallery" 
               class="view-toggle-btn" 
               title="갤러리형" 
               style="background:none; border:none; cursor:pointer; padding:0 5px; color:<?php echo ($view_mode=='gallery') ? $point_color_hex : '#888'; ?>;">
                <span class="material-icons" style="font-size:20px;">view_column</span>
            </a>

            <a href="?bo_table=<?=$bo_table?>&cmd=change_view&mode=tile" 
               class="view-toggle-btn" 
               title="타일형" 
               style="background:none; border:none; cursor:pointer; padding:0 5px; color:<?php echo ($view_mode=='tile') ? $point_color_hex : '#888'; ?>;">
                <span class="material-icons" style="font-size:20px;">grid_view</span>
            </a>
        <?php } ?>
    </div>

<textarea id="wr_content" style="display:none;"></textarea>

<style>
/* 라이트박스 배경 */
#img-lightbox {
    position: fixed; 
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.95);
    z-index: 2147483646; 
    display: none; 
    justify-content: center;
    align-items: center;
}

/* [PC 기본 스타일] X 버튼: 이미지 오른쪽 옆에 길게 배치 */
#img-lightbox .close-btn {
    position: fixed; 
    top: -200px; left: -200px; /* 초기 숨김 */
    
    background: transparent;
    padding: 0;
    
    /* PC: 클릭 영역을 아래로 길게 늘림 */
    width: 60px;       
    height: 120px;     
    
    font-size: 40px;
    color: rgba(255, 255, 255, 0.9); 
    cursor: pointer;
    z-index: 2147483647; 
    
    display: flex;
    justify-content: center;
    align-items: flex-start; /* 아이콘 상단 정렬 */
    padding-top: 10px;

    line-height: 1;
    transition: color 0.2s ease, transform 0.2s ease;
    
    margin-top: 0;      
    margin-left: 0;     
}

#img-lightbox .close-btn:hover {
    color: #d3393d; 
    transform: scale(1.1); 
    text-shadow: 0 0 10px rgba(211, 57, 61, 0.5);
}

/* [모바일/태블릿 대응] 1024px 이하에서는 버튼 스타일 변경 */
@media all and (max-width: 1024px) {
    #img-lightbox .close-btn {
        width: 40px !important;    /* 너비 정상화 */
        height: 40px !important;   /* 높이 정상화 (길쭉한 영역 해제) */
        align-items: center !important; /* 중앙 정렬 */
        padding-top: 0 !important;
        background: rgba(0, 0, 0, 0.3); /* 모바일은 이미지 위에 겹치므로 살짝 배경 추가 */
        border-radius: 50%; /* 둥글게 */
    }
}

/* 스와이퍼 영역 */
.lightbox-swiper {
    width: 100%;
    height: 100%; 
}

/* 이미지 스타일 */
.lightbox-swiper img {
    cursor: default;
    max-width: 85vw !important; 
    max-height: 90vh !important;
    width: auto !important;
    height: auto !important;
    object-fit: contain; 
}
</style>

<div id="img-lightbox">
    <div class="close-btn" title="닫기">&times;</div>
    
    <div class="swiper lightbox-swiper">
        <div class="swiper-wrapper"></div>
        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-pagination"></div>
    </div>
</div>

<script>
// --- 기존 댓글/이모티콘 관련 스크립트 유지 ---
var active_comment_textarea = null;
function setActiveTextarea(element) { active_comment_textarea = element; }
function openTextDeco() { window.open('<?php echo $board_skin_url; ?>/txtggu/index.php', 'txtggu', 'width=500,height=600,scrollbars=yes'); }
function openEmoticon() { window.open('<?php echo $board_skin_url; ?>/emoticon/index.php', 'emoticon', 'width=600,height=600,scrollbars=yes'); }

$(document).ready(function() {
    var hiddenInput = document.getElementById('wr_content');
    setInterval(function(){
        if(hiddenInput.value !== '') {
            if(active_comment_textarea) {
                var cursorPos = active_comment_textarea.selectionStart;
                var textBefore = active_comment_textarea.value.substring(0,  cursorPos);
                var textAfter  = active_comment_textarea.value.substring(cursorPos, active_comment_textarea.value.length);
                active_comment_textarea.value = textBefore + hiddenInput.value + textAfter;
                active_comment_textarea.focus();
            } else {
                alert("이모티콘을 넣을 댓글 입력창을 먼저 클릭해주세요!");
            }
            hiddenInput.value = ''; 
        }
    }, 500); 
});

function fviewcomment_submit(f) {
    // [수정] 이미지가 첨부되었는지 확인
    var has_file = false;
    var file_inputs = f.querySelectorAll('input[type="file"]');
    for (var i = 0; i < file_inputs.length; i++) {
        if (file_inputs[i].value) {
            has_file = true;
            break;
        }
    }

    // 내용도 없고 파일도 없으면 경고
    if (!f.wr_content.value && !has_file) {
        alert("내용 또는 이미지를 입력하셔야 합니다.");
        return false;
    }

    // 파일은 있는데 내용이 없으면, 서버 통과를 위해 공백(' ')을 강제로 입력
    if (!f.wr_content.value && has_file) {
        f.wr_content.value = " ";
    }

    return true;
}
function toggleCommentForm(wr_id) {
    var modal = document.getElementById('modal_log_' + wr_id);
    if(modal) {
        var formBox = modal.querySelector('.item-comment-form-box');
        if(formBox) {
            var currentDisplay = window.getComputedStyle(formBox).display;
            if(currentDisplay === 'none') {
                formBox.style.display = 'block';
                var textarea = formBox.querySelector('textarea');
                if(textarea) { textarea.focus(); setActiveTextarea(textarea); }
            } else {
                formBox.style.display = 'none';
            }
        }
    }
}

var save_before = ''; var save_html = '';
function comment_box(wr_id, co_id, work) {
    var el_id;
    if (co_id) { el_id = (work == 'c') ? 'reply_' + co_id : 'edit_' + co_id; } 
    else { el_id = 'bo_vc_w_' + wr_id; }

    if (save_before != el_id) {
        if (save_before) {
            document.getElementById(save_before).style.display = 'none';
            document.getElementById(save_before).innerHTML = '';
        }
        var origin_form = document.getElementById('bo_vc_w_' + wr_id);
        if(origin_form) {
            save_html = origin_form.innerHTML;
            var target_el = document.getElementById(el_id);
            if(target_el) {
                target_el.style.display = 'block';
                target_el.innerHTML = save_html;
                var new_form = target_el.querySelector('form');
                if(new_form) {
                    new_form.name = "fviewcomment_" + work + "_" + co_id; 
                    var new_textarea = new_form.querySelector('textarea');
                    if(new_textarea) {
                        new_textarea.onclick = function() { setActiveTextarea(this); };
                        new_textarea.onfocus = function() { setActiveTextarea(this); };
                    }
                    if (work == 'c') { 
                        new_form.w.value = 'c'; new_form.comment_id.value = co_id; new_form.wr_content.value = ''; 
                    } else { 
                        new_form.w.value = 'cu'; new_form.comment_id.value = co_id;
                        var saved_content_el = document.getElementById('save_co_comment_' + co_id);
                        if(saved_content_el) { new_form.wr_content.value = saved_content_el.value; }
                    }
                }
            }
            save_before = el_id;
        }
    }
}

// --- 라이트박스 로직 ---
var swipers = {};
var lightboxSwiper = null;

// [수정된 함수] 화면 크기에 따라 버튼 위치 다르게 계산
function updateCloseBtnPosition() {
    var $lightbox = $('#img-lightbox');
    if ($lightbox.is(':hidden')) return;

    var $activeImg = $lightbox.find('.swiper-slide-active img');
    if ($activeImg.length === 0) return; 

    var imgElement = $activeImg[0];
    var rect = imgElement.getBoundingClientRect();

    // 이미지 너비가 0이면 아직 로드 안된 것
    if (rect.width === 0) return;

    var $closeBtn = $lightbox.find('.close-btn');
    var isMobile = window.innerWidth <= 1024; // 1024px 이하를 모바일/태블릿으로 간주

    if (isMobile) {
        // [모바일 됨] 이미지 바깥 위쪽, 오른쪽 끝선에 정렬
        $closeBtn.css({
            top: rect.top + 'px',       // 기준: 이미지 상단
            left: rect.right + 'px',    // 기준: 이미지 우측 끝
            
            // 이미지 위로 50px 올림 (바깥 배치)
            marginTop: '-50px',
            
            // 왼쪽으로 45px 당김 (버튼 크기만큼 당겨서 이미지 오른쪽 끝선과 맞춤)
            marginLeft: '-45px'
        });
    } else {
        // [PC] 이미지 오른쪽 '옆'에 배치 (기존 유지)
        $closeBtn.css({
            top: rect.top + 'px',
            left: rect.right + 'px',
            marginTop: '0px',
            marginLeft: '0px'
        });
    }
}

// 비밀번호가 확인된 게시글 ID 저장
var unlockedPosts = {};

function openLogModal(wr_id, isProtected) {
    var modal = document.getElementById('modal_log_' + wr_id);
    var content = modal ? modal.querySelector('.log-modal-content') : null;

    if(modal && content) {
        // 애니메이션 클래스 초기화 및 부여
        modal.classList.remove('close-anim');
        content.classList.remove('close-anim');
        modal.classList.add('open-anim');
        content.classList.add('open-anim');

        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';

        // 비밀번호 보호 게시글 처리
        var protectForm = document.getElementById('protect_form_' + wr_id);
        var protectedContent = document.getElementById('content_' + wr_id);

        if(isProtected && protectForm && protectedContent) {
            // 이미 비밀번호가 확인된 경우
            if(unlockedPosts[wr_id]) {
                protectForm.style.display = 'none';
                protectedContent.style.display = 'block';
                initSwiper(modal, wr_id);
            } else {
                // 비밀번호 입력 폼 표시
                protectForm.style.display = 'block';
                protectedContent.style.display = 'none';
                // 입력 필드에 포커스
                setTimeout(function() {
                    var pwInput = document.getElementById('protect_pw_' + wr_id);
                    if(pwInput) pwInput.focus();
                }, 300);
            }
        } else {
            // 보호되지 않은 게시글
            initSwiper(modal, wr_id);
        }
    }
}

// 스와이퍼 초기화 함수 분리
function initSwiper(modal, wr_id) {
    if (!swipers[wr_id]) {
        var swiperContainer = modal.querySelector('.mySwiper');
        if(swiperContainer) {
            swipers[wr_id] = new Swiper(swiperContainer, {
                slidesPerView: 1, spaceBetween: 30, loop: false, autoHeight: true,
                navigation: { nextEl: swiperContainer.querySelector('.swiper-button-next'), prevEl: swiperContainer.querySelector('.swiper-button-prev') },
                pagination: { el: swiperContainer.querySelector('.swiper-pagination'), clickable: true },
                observer: true, observeParents: true,
            });
        }
    } else {
        swipers[wr_id].update();
    }
}

// 비밀번호 확인 함수
function checkProtectPassword(wr_id) {
    var pwInput = document.getElementById('protect_pw_' + wr_id);
    var errorMsg = document.getElementById('protect_error_' + wr_id);
    var protectForm = document.getElementById('protect_form_' + wr_id);
    var protectedContent = document.getElementById('content_' + wr_id);

    if(!pwInput || !pwInput.value) {
        if(errorMsg) {
            errorMsg.textContent = '비밀번호를 입력하세요.';
            errorMsg.style.display = 'block';
        }
        return;
    }

    // AJAX로 비밀번호 확인
    $.ajax({
        url: '<?php echo $board_skin_url; ?>/check_protect.php',
        type: 'POST',
        data: {
            bo_table: '<?php echo $bo_table; ?>',
            wr_id: wr_id,
            password: pwInput.value
        },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                // 비밀번호 일치 - 내용 표시
                unlockedPosts[wr_id] = true;
                protectForm.style.display = 'none';
                protectedContent.style.display = 'block';

                // 스와이퍼 초기화
                var modal = document.getElementById('modal_log_' + wr_id);
                initSwiper(modal, wr_id);
            } else {
                // 비밀번호 불일치
                if(errorMsg) {
                    errorMsg.textContent = response.message || '비밀번호가 일치하지 않습니다.';
                    errorMsg.style.display = 'block';
                }
                pwInput.value = '';
                pwInput.focus();
            }
        },
        error: function() {
            if(errorMsg) {
                errorMsg.textContent = '서버 오류가 발생했습니다. 다시 시도해주세요.';
                errorMsg.style.display = 'block';
            }
        }
    });
}

function closeLogModal(e, wr_id) {
    // 배경(.log-modal-overlay) 또는 닫기버튼(.btn-modal-close) 클릭 시에만 실행
    if(e == null || e.target.classList.contains('log-modal-overlay') || e.target.classList.contains('btn-modal-close')) {
        var modal = document.getElementById('modal_log_' + wr_id);
        var content = modal ? modal.querySelector('.log-modal-content') : null;

        if(modal && content) {
            // 퇴장 애니메이션 클래스 추가
            modal.classList.add('close-anim');
            content.classList.add('close-anim');

            // 애니메이션 시간(0.3초) 후 숨김 처리
            setTimeout(function() {
                modal.style.display = 'none'; 
                modal.classList.remove('open-anim', 'close-anim');
                content.classList.remove('open-anim', 'close-anim');
                document.body.style.overflow = 'auto'; 
            }, 300);
        }
    }
}

$(document).on('click', '.swiper-slide img, .lightbox_trigger', function(e) {
    // 이미 라이트박스 내부의 이미지라면 실행 중단
    if ($(this).closest('#img-lightbox').length > 0) {
        return; 
    }
    e.preventDefault(); e.stopPropagation(); 
    var clickedSrc = $(this).attr('src');
    var images = [];
    var initialSlide = 0;
    
    // 이미지 수집 로직 (기존 동일)
    if($(this).closest('.mySwiper').length > 0) {
        var $slider = $(this).closest('.swiper-wrapper');
        $slider.find('img').each(function(index) {
            var src = $(this).attr('src'); images.push(src);
            if(src === clickedSrc) initialSlide = index;
        });
    } else if($(this).closest('.comment-img-grid').length > 0) {
        var $grid = $(this).closest('.comment-img-grid');
        $grid.find('img').each(function(index) {
            var src = $(this).attr('src'); images.push(src);
            if(src === clickedSrc) initialSlide = index;
        });
    } else {
        images.push(clickedSrc);
    }

    var slidesHtml = '';
    for(var i=0; i<images.length; i++) {
        slidesHtml += '<div class="swiper-slide"><img src="' + images[i] + '" onload="updateCloseBtnPosition()"></div>';
    }
    $('#img-lightbox .swiper-wrapper').html(slidesHtml);
    
    // ▼ [추가됨] 이미지가 1장 이하면 화살표 숨김, 2장 이상이면 보임
    if (images.length > 1) {
        $('#img-lightbox .swiper-button-next, #img-lightbox .swiper-button-prev').show();
    } else {
        $('#img-lightbox .swiper-button-next, #img-lightbox .swiper-button-prev').hide();
    }
    // ▲ [여기까지 추가됨]

    $('#img-lightbox').css('display', 'flex').hide().fadeIn(200, function() {
         setTimeout(updateCloseBtnPosition, 50);
    });

    if(lightboxSwiper) { lightboxSwiper.destroy(); }
    
    lightboxSwiper = new Swiper('.lightbox-swiper', {
        initialSlide: initialSlide, slidesPerView: 1, spaceBetween: 50,
        observer: true, observeParents: true,
        // 이미지가 1개일 때 드래그 슬라이드도 막으려면 allowTouchMove: false 추가 가능
        allowTouchMove: (images.length > 1), 
        navigation: { nextEl: '#img-lightbox .swiper-button-next', prevEl: '#img-lightbox .swiper-button-prev' },
        pagination: { el: '#img-lightbox .swiper-pagination', clickable: true },
        on: {
            init: function() { setTimeout(updateCloseBtnPosition, 100); },
            slideChangeTransitionEnd: updateCloseBtnPosition,
            resize: updateCloseBtnPosition
        }
    });
});

$('#img-lightbox').on('click', function(e) {
    // 1. 닫기 버튼(X) 클릭 시 -> 닫기
    if ($(e.target).hasClass('close-btn') || $(e.target).closest('.close-btn').length > 0) {
        $('#img-lightbox').fadeOut();
        return;
    }

    // 2. 화살표/페이지네이션 클릭 시 -> 배경 클릭 로직 차단
    if ($(e.target).closest('.swiper-button-next, .swiper-button-prev, .swiper-pagination').length > 0) {
        return; 
    }

    // [중요] 여기에 있던 if ($(e.target).is('img')) { return; } 코드는 반드시 삭제해야 합니다.

    // 3. 배경(또는 이미지) 클릭 시 -> 화면 좌우 영역에 따라 이전/다음
    if (lightboxSwiper) {
        var screenWidth = $(window).width();
        var clickX = e.clientX; 
        if (clickX < screenWidth / 2) {
            lightboxSwiper.slidePrev(); 
        } else {
            lightboxSwiper.slideNext(); 
        }
    }
});

// 창 크기가 변할 때도 버튼 위치 갱신
$(window).on('resize', function() {
    if ($('#img-lightbox').is(':visible')) {
        updateCloseBtnPosition();
    }
});

// [추가] 모달 전환 함수 (이전/다음 글)
function switchLogModal(currentId, targetId) {
    var currentModal = document.getElementById('modal_log_' + currentId);
    var targetModal = document.getElementById('modal_log_' + targetId);

    if(currentModal && targetModal) {
        // 현재 모달 즉시 숨김 (애니메이션 없이 빠르게 전환)
        currentModal.style.display = 'none';
        currentModal.classList.remove('open-anim'); 
        
        // 목표 모달 열기
        openLogModal(targetId);
    }
}

</script>

<script>
// 1. 주소 복사 기능
function copy_log_link(wr_id) {
    // 현재 도메인 + 게시판 주소 + wr_id 조합
    var link = "<?php echo G5_BBS_URL; ?>/board.php?bo_table=<?php echo $bo_table; ?>&wr_id=" + wr_id;
    
    // 클립보드 복사 API
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(link).then(() => {
            alert('게시글 주소가 복사되었습니다.\n트위터에 붙여넣으면 이미지가 함께 나옵니다!');
        }).catch(err => {
            prompt('이 글의 주소입니다. 복사(Ctrl+C)해서 사용하세요.', link);
        });
    } else {
        // 구형 브라우저 호환용
        var tempInput = document.createElement("input");
        tempInput.style = "position: absolute; left: -1000px; top: -1000px";
        tempInput.value = link;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand("copy");
        document.body.removeChild(tempInput);
        alert('게시글 주소가 복사되었습니다.');
    }
}

$(document).ready(function() {
    var urlParams = new URLSearchParams(window.location.search);
    var targetWrId = urlParams.get('wr_id');


    if (targetWrId && typeof is_view_page_mode === 'undefined') {
        if ($('#modal_log_' + targetWrId).length > 0) {
            setTimeout(function(){
                openLogModal(targetWrId);
            }, 300);
        } 
    }
});
</script>

<script src="<?=$board_skin_url?>/load.board.js"></script>
