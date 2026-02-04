<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

// 댓글의 부모글(원글) ID 확인
$redirect_wr_id = $write['wr_parent'] ? $write['wr_parent'] : $write['wr_id'];

// 목록이 아닌, 글 뷰 페이지(고유창)로 리다이렉트
goto_url('./board.php?bo_table='.$bo_table.'&wr_id='.$redirect_wr_id.'&'.$qstr);
?>