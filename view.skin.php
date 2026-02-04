<?php
if (!defined("_GNUBOARD_")) exit; 
include_once(G5_LIB_PATH.'/thumbnail.lib.php');

// [설정] 포인트 컬러
if(isset($color['color_point']) && $color['color_point']) {
    $point_color_hex = $color['color_point'];
} else if(isset($color_point) && $color_point) { 
    $point_color_hex = $color_point;
} else {
    $point_color_hex = '#97c3d1'; 
}

// 이모티콘 설정 파일 로드
$emoticon_setting_file = $board_skin_path.'/emoticon/_setting.emoticon.php';
if(file_exists($emoticon_setting_file)) {
    include_once($emoticon_setting_file);
}

// [수정] URL 파라미터에서 현재 페이지 번호를 가져옵니다.
$current_page = (isset($_GET['page']) && (int)$_GET['page'] > 0) ? (int)$_GET['page'] : 1;

// [핵심] $qstr에 들어있을지 모르는 기존 page 정보를 정규식으로 깨끗하게 지웁니다.
// 이렇게 해야 &page=3&page=1 처럼 중복되는 현상을 막을 수 있습니다.
$clean_qstr = preg_replace('/^&page=[0-9]*/', '', $qstr); // 시작부분 page 제거
$clean_qstr = preg_replace('/&page=[0-9]*/', '', $clean_qstr); // 중간부분 page 제거

// [수정] 목록 버튼 링크 생성 (불필요한 글 위치 고정 #log_... 제거)
$list_href = './board.php?bo_table='.$bo_table.'&page='.$current_page.$clean_qstr;

// 스타일 및 스크립트 로드
add_stylesheet('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />', 0);
add_javascript('<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>', 0);
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);
add_javascript('<script src="'.$board_skin_url.'/load.board.js"></script>', 1);

$is_view_page = true;
$list_item = $view;

// [추가] 현재 보고 있는 글이 공지사항인지 확인 (배지용)
$notice_array = explode(',', trim($board['bo_notice']));
$list_item['is_notice'] = in_array($list_item['wr_id'], $notice_array);
?>

<style>
/* [수정] 모달/뷰 페이지 헤더 정렬 교정 */
.ui-pic .pic-header { margin-right: 0 !important; }

/* [수정] 본문 내용(텍스트/이모티콘) 안에서는 '크게보기' 툴팁 완전 차단 */
.theme-box img { cursor: default !important; pointer-events: auto !important; }
.pic-data:hover .theme-box img + ::after,
.theme-box *:hover::after,
.theme-box::after,
.theme-box img:hover::after { 
    content: none !important; display: none !important; background: none !important;
    opacity: 0 !important; width: 0 !important; height: 0 !important;
}

/* 라이트박스 */
#img-lightbox {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.95); z-index: 2147483646; 
    display: none; justify-content: center; align-items: center;
}
#img-lightbox .close-btn {
    position: fixed; top: -200px; left: -200px; width: 60px; height: 120px;     
    font-size: 40px; color: rgba(255, 255, 255, 0.9); cursor: pointer; z-index: 2147483647; 
    display: flex; justify-content: center; align-items: flex-start; padding-top: 10px;
    line-height: 1; margin: 0; transition: color 0.2s ease, transform 0.2s ease;
}
#img-lightbox .close-btn:hover { color: #d3393d; transform: scale(1.1); }
@media all and (max-width: 1024px) {
    #img-lightbox .close-btn { width: 40px !important; height: 40px !important; align-items: center !important; padding-top: 0 !important; background: rgba(0, 0, 0, 0.3); border-radius: 50%; }
}
.lightbox-swiper { width: 100%; height: 100%; }
.lightbox-swiper img { cursor: default; max-width: 85vw !important; max-height: 90vh !important; width: auto !important; height: auto !important; object-fit: contain; }
</style>

<input type="text" id="wr_content" value="" style="position:absolute; top:-9999px; left:-9999px; width:0; height:0; border:0; padding:0; margin:0; opacity:0;">

<div id="load_log_board" style="padding-top:20px;">
    
    <?php if ($board['bo_content_head']) { ?>
        <div id="bo_content_head" style="margin-bottom:20px; padding:0; overflow:visible; max-width:700px; margin:0 auto 20px auto;">
            <?php echo $board['bo_content_head']; ?>
        </div>
    <?php } ?>

    <div class="log-modal-content" style="margin: 0 auto; position: relative; display: block; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
        <div style="padding:0;">
            <?php include($board_skin_path.'/list.log.skin.php'); ?>
        </div>
        <div style="height:20px;"></div>
    </div>
</div>

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
var is_view_page_mode = true;
var active_comment_textarea = null;
function setActiveTextarea(element) { active_comment_textarea = element; }
function openTextDeco() { window.open('<?php echo $board_skin_url; ?>/txtggu/index.php', 'txtggu', 'width=500,height=600,scrollbars=yes'); }
function openEmoticon() { window.open('<?php echo $board_skin_url; ?>/emoticon/index.php', 'emoticon', 'width=600,height=600,scrollbars=yes'); }

$(document).ready(function() {
    var hiddenInput = document.getElementById('wr_content');
    setInterval(function(){
        if(hiddenInput && hiddenInput.value !== '') {
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
    }, 200); 
});

$(document).ready(function() {
    var swiperContainer = document.querySelector('.mySwiper');
    if(swiperContainer) {
        new Swiper(swiperContainer, {
            slidesPerView: 1, spaceBetween: 30, loop: false, autoHeight: true,
            navigation: { nextEl: swiperContainer.querySelector('.swiper-button-next'), prevEl: swiperContainer.querySelector('.swiper-button-prev') },
            pagination: { el: swiperContainer.querySelector('.swiper-pagination'), clickable: true },
            observer: true, observeParents: true,
        });
    }
});

function fviewcomment_submit(f) {
    var has_file = false;
    var file_inputs = f.querySelectorAll('input[type="file"]');
    for (var i = 0; i < file_inputs.length; i++) {
        if (file_inputs[i].value) { has_file = true; break; }
    }
    if (!f.wr_content.value && !has_file) {
        alert("내용 또는 이미지를 입력하셔야 합니다.");
        return false;
    }
    if (!f.wr_content.value && has_file) { f.wr_content.value = " "; }
    return true;
}

var lightboxSwiper = null;
function updateCloseBtnPosition() {
    var $lightbox = $('#img-lightbox');
    if ($lightbox.is(':hidden')) return;
    var $activeImg = $lightbox.find('.swiper-slide-active img');
    if ($activeImg.length === 0) return; 
    var imgElement = $activeImg[0];
    var rect = imgElement.getBoundingClientRect();
    if (rect.width === 0) return;
    var $closeBtn = $lightbox.find('.close-btn');
    var isMobile = window.innerWidth <= 1024; 
    if (isMobile) {
        $closeBtn.css({ top: rect.top + 'px', left: rect.right + 'px', marginTop: '-50px', marginLeft: '-45px' });
    } else {
        $closeBtn.css({ top: rect.top + 'px', left: rect.right + 'px', marginTop: '0px', marginLeft: '0px' });
    }
}
$(document).on('click', '.swiper-slide img, .lightbox_trigger', function(e) {
    if ($(this).closest('#img-lightbox').length > 0) return; 
    e.preventDefault(); e.stopPropagation(); 
    var clickedSrc = $(this).attr('src');
    var images = [];
    var initialSlide = 0;
    if($(this).closest('.mySwiper').length > 0) {
        var $slider = $(this).closest('.swiper-wrapper');
        $slider.find('img').each(function(index) {
            var src = $(this).attr('src'); images.push(src); if(src === clickedSrc) initialSlide = index;
        });
    } else if($(this).closest('.comment-img-grid').length > 0) {
        var $grid = $(this).closest('.comment-img-grid');
        $grid.find('img').each(function(index) {
            var src = $(this).attr('src'); images.push(src); if(src === clickedSrc) initialSlide = index;
        });
    } else { images.push(clickedSrc); }
    var slidesHtml = '';
    for(var i=0; i<images.length; i++) { slidesHtml += '<div class="swiper-slide"><img src="' + images[i] + '" onload="updateCloseBtnPosition()"></div>'; }
    $('#img-lightbox .swiper-wrapper').html(slidesHtml);
    if (images.length > 1) { $('#img-lightbox .swiper-button-next, #img-lightbox .swiper-button-prev').show(); } 
    else { $('#img-lightbox .swiper-button-next, #img-lightbox .swiper-button-prev').hide(); }
    $('#img-lightbox').css('display', 'flex').hide().fadeIn(200, function() { setTimeout(updateCloseBtnPosition, 50); });
    if(lightboxSwiper) { lightboxSwiper.destroy(); }
    lightboxSwiper = new Swiper('.lightbox-swiper', {
        initialSlide: initialSlide, slidesPerView: 1, spaceBetween: 50, observer: true, observeParents: true,
        allowTouchMove: (images.length > 1), 
        navigation: { nextEl: '#img-lightbox .swiper-button-next', prevEl: '#img-lightbox .swiper-button-prev' },
        pagination: { el: '#img-lightbox .swiper-pagination', clickable: true },
        on: { init: function() { setTimeout(updateCloseBtnPosition, 100); }, slideChangeTransitionEnd: updateCloseBtnPosition, resize: updateCloseBtnPosition }
    });
});
$('#img-lightbox').on('click', function(e) {
    if ($(e.target).hasClass('close-btn') || $(e.target).closest('.close-btn').length > 0) { $('#img-lightbox').fadeOut(); return; }
    if ($(e.target).closest('.swiper-button-next, .swiper-button-prev, .swiper-pagination').length > 0) { return; }
    if (lightboxSwiper) { var screenWidth = $(window).width(); var clickX = e.clientX; if (clickX < screenWidth / 2) { lightboxSwiper.slidePrev(); } else { lightboxSwiper.slideNext(); } }
});
$(window).on('resize', function() { if ($('#img-lightbox').is(':visible')) { updateCloseBtnPosition(); } });
</script>