$(function() {
    // 갤러리 리스트(CSS Grid)가 있으면 이 JS(기존 Masonry 로직) 실행 중지
    if ($('.gallery-wrapper').length > 0) return;

    // 레이아웃 셋팅 - 반응형
    fn_layout_setting();

    // 화면 사이즈가 변경 될 시, 레이아웃 셋팅 실행
    $(window).resize(function() {
        fn_layout_setting();
    });

    // 즐겨찾기 추가 - Ajax
    $('a[data-function="favorite"]').on('click', function() {
        var formData = new FormData();
        var idx = $(this).data('idx');
        var obj = $(this);
        formData.append("wr_id", idx);
        formData.append("bo_table", g5_bo_table);
        formData.append("mb_id", avo_mb_id);

        $.ajax({
            url:avo_board_skin_url + '/ajax/add_favorite.php'
            , data: formData
            , processData: false
            , contentType: false
            , type: 'POST'
            , success: function(data){
                if(data == 'on') { 
                    obj.removeClass('on');
                    obj.addClass(data);
                }else if(data == 'off') { 
                    obj.removeClass('on');
                }
            }
        });

        return false;
    });


    $('a.ui-open-log').on('click', function() {

        var obj = $(this).closest('.pic-data').children('div');
        var state = $(obj).hasClass('on');
        var height = $(obj).data('height');
        var original_height;
        if (height==0)
        {
            original_height='100%';
        }else original_height=height+'px'; 

        if(state){ 
            //닫기
            $(obj).stop().slideToggle(600);
            $(obj).removeClass('on');
            $(this).text("OPEN");
        } else {
            // 열기
            $(obj).stop().slideToggle(600);
            $(obj).addClass('on');
            $(this).text("CLOSE");
        }

        return false;
    });

    $('a.ui-remove-blind').on('click', function() {
        $(this).closest('.pic-data').removeClass('ui-blind');
        $(this).fadeOut();
        return false;
    });

    $('.send_memo').on('click', function() {
        var target = $(this).attr('href');
        window.open(target, 'memo', "width=500, height=300");
        return false;
    });

    $('.btn-search-guide').on('click', function() {
        $('#searc_keyword').toggleClass('on');
        return false;
    });

    $('#load_log_board').css('opacity', '1.0');

    function fn_layout_setting() {
        $('#log_list > .item').each(function(){
            var log_data_width = $(this).find('.ui-pic').data('width');
            var log_width = $(this).find('.pic-data').find('img').width();
            
            if(log_data_width < log_width && log_width > 300) { 
                log_data_width = log_width;
            }

            var comment_width = $('#log_list .item-inner').width() - log_data_width + 8;
            if(comment_width > 320) {
                $(this).removeClass('both');
                $(this).find('.ui-comment').css('width', comment_width - 20 + "px");
                $(this).find('.ui-pic').css({'max-width':$(this).find('.ui-pic').data('width'),'width':$(this).find('.ui-pic').data('width')});
                    $(this).find('.frm_info').css('display','block');
            } else {
                $(this).addClass('both');
                $(this).find('.ui-pic').css({'max-width':'100%','width':'100%'});
                $(this).find('.pic-data').css('max-width','100%');
                $(this).find('.ui-comment').css('width', "auto");
                if($(this).width()>$(this).find('.ui-pic').data('image'))
                    $(this).find('.frm_info').css('display','none');
            }
        });
    };

    $('.new_win').on('click', function() {
        var target = $(this).attr('href');
        window.open(target, 'emoticon', "width=400, height=600");
        return false;
    }); 
});