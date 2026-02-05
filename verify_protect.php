<?php
include_once('./_common.php');

$bo_table = isset($_POST['bo_table']) ? clean_xss_tags($_POST['bo_table']) : '';
$wr_id = isset($_POST['wr_id']) ? (int)$_POST['wr_id'] : 0;
$password = isset($_POST['password']) ? $_POST['password'] : '';
$redirect = isset($_POST['redirect']) ? $_POST['redirect'] : '';

if(!$bo_table || !$wr_id || !$password) {
    alert('필수 정보가 누락되었습니다.', G5_BBS_URL);
    exit;
}

// 게시판 테이블 확인
$board = get_board($bo_table);
if(!$board['bo_table']) {
    alert('게시판 정보를 찾을 수 없습니다.', G5_BBS_URL);
    exit;
}

$write_table = $g5['write_prefix'] . $bo_table;

// 게시글 조회
$sql = "SELECT wr_protect FROM {$write_table} WHERE wr_id = '{$wr_id}'";
$write = sql_fetch($sql);

if(!$write) {
    alert('게시글을 찾을 수 없습니다.', G5_BBS_URL);
    exit;
}

// 비밀번호 확인
if($write['wr_protect'] === $password) {
    // 세션에 인증 정보 저장
    $session_key = 'protect_verified_'.$bo_table.'_'.$wr_id;
    $_SESSION[$session_key] = true;

    // 리다이렉트
    if($redirect) {
        goto_url($redirect);
    } else {
        goto_url(G5_BBS_URL.'/board.php?bo_table='.$bo_table.'&wr_id='.$wr_id);
    }
} else {
    // 비밀번호 불일치 - 에러 파라미터와 함께 리다이렉트
    if($redirect) {
        $separator = (strpos($redirect, '?') !== false) ? '&' : '?';
        goto_url($redirect.$separator.'error=password');
    } else {
        goto_url(G5_BBS_URL.'/board.php?bo_table='.$bo_table.'&wr_id='.$wr_id.'&error=password');
    }
}
?>
