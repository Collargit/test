<?php
if (!defined("_GNUBOARD_")) exit; 

// [수정] 파일 업로드 함수 사용을 위해 include
include_once($board_skin_path.'/upload_file.php');

// 댓글 파일 업로드 처리 (다중 파일 지원)
if(isset($_FILES['bf_file']['name']) && is_array($_FILES['bf_file']['name'])) {
    $file_count = count($_FILES['bf_file']['name']);
    $has_upload = false; 
    
    for($i=0; $i<$file_count; $i++) {
        if($_FILES['bf_file']['name'][$i]) {
            // [중요] 단일 파일 업로드를 위해 배열 재구성
            $tmp_files = $_FILES['bf_file'];
            $_FILES['curr_file'] = array(
                'name' => $tmp_files['name'][$i],
                'type' => $tmp_files['type'][$i],
                'tmp_name' => $tmp_files['tmp_name'][$i],
                'error' => $tmp_files['error'][$i],
                'size' => $tmp_files['size'][$i]
            );

            // 'curr_file'이라는 임시 키를 사용하여 함수 호출
            $files = upload_c_file('curr_file', $bo_table);
            
            if($files['name']) {
                $has_upload = true;
                $sql_common = " bf_source = '{$files['source']}',
                                bf_file = '{$files['name']}',
                                bf_filesize = '{$files['size']}',
                                bf_width = '{$files['img'][0]}',
                                bf_height = '{$files['img'][1]}',
                                bf_type = '{$files['img'][2]}',
                                bf_datetime = '".G5_TIME_YMDHIS."' ";
                
                sql_query(" insert into {$g5['board_file_table']}
                            set wr_id = '{$comment_id}',
                                bo_table = '{$bo_table}',
                                bf_no = '{$i}',
                                {$sql_common} ");
                                
                sql_query(" update {$write_table} set wr_file = wr_file + 1 where wr_id = '{$comment_id}' ");
            }
        }
    }

    if($has_upload) {
        sql_query(" update {$write_table} set wr_type = 'UPLOAD' where wr_id = '{$comment_id}' ");
    }
}
// [수정] 댓글 작성 후 보고 있던 뷰 페이지(고유창)로 복귀
$redirect_wr_id = $write['wr_parent'] ? $write['wr_parent'] : $wr_id;
goto_url('./board.php?bo_table='.$bo_table.'&wr_id='.$redirect_wr_id.'&'.$qstr);
?>