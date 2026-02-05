<?php
include_once('./_common.php');

header('Content-Type: application/json; charset=utf-8');

$bo_table = isset($_POST['bo_table']) ? clean_xss_tags($_POST['bo_table']) : '';
$wr_id = isset($_POST['wr_id']) ? (int)$_POST['wr_id'] : 0;
$password = isset($_POST['password']) ? $_POST['password'] : '';

$response = array('success' => false, 'message' => '');

if(!$bo_table || !$wr_id || !$password) {
    $response['message'] = '필수 정보가 누락되었습니다.';
    echo json_encode($response);
    exit;
}

// 게시판 테이블 확인
$board = get_board($bo_table);
if(!$board['bo_table']) {
    $response['message'] = '게시판 정보를 찾을 수 없습니다.';
    echo json_encode($response);
    exit;
}

$write_table = $g5['write_prefix'] . $bo_table;

// 게시글 조회
$sql = "SELECT wr_protect FROM {$write_table} WHERE wr_id = '{$wr_id}'";
$write = sql_fetch($sql);

if(!$write) {
    $response['message'] = '게시글을 찾을 수 없습니다.';
    echo json_encode($response);
    exit;
}

// 비밀번호 확인
if($write['wr_protect'] === $password) {
    $response['success'] = true;
    $response['message'] = '확인되었습니다.';
} else {
    $response['message'] = '비밀번호가 일치하지 않습니다.';
}

echo json_encode($response);
?>
