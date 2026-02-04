<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
function upload_c_file($name,$table){

	$files=array();
	$tmp_file=$_FILES[$name]['tmp_name'];
	$filesize=$_FILES[$name]['size'];
	$filename=$_FILES[$name]['name'];
	$filename=get_safe_filename($filename);

	$timg = @getimagesize($tmp_file);
	// image type
	if ( preg_match("/\.({$config['cf_image_extension']})$/i", $filename) ||
		 preg_match("/\.({$config['cf_flash_extension']})$/i", $filename) ) {
		if ($timg['2'] < 1 || $timg['2'] > 16)
			continue;
	} 
	$files['img']=$timg; 
	$files['source']=$filename;
	$files['size']=$filesize;

	// 아래의 문자열이 들어간 파일은 -x 를 붙여서 웹경로를 알더라도 실행을 하지 못하도록 함
	$filename = preg_replace("/\.(php|phtm|htm|cgi|pl|exe|jsp|asp|inc)/i", "$0-x", $filename);

	$chars_array = array_merge(range(0,9), range('a','z'), range('A','Z'));
	shuffle($chars_array);
	$shuffle = implode('', $chars_array);

	// 첨부파일 첨부시 첨부파일명에 공백이 포함되어 있으면 일부 PC에서 보이지 않거나 다운로드 되지 않는 현상이 있습니다. (길상여의 님 090925)
	$file_name = abs(ip2long($_SERVER['REMOTE_ADDR'])).'_'.substr($shuffle,0,8).'_'.replace_filename($filename);

	$dest_file = G5_DATA_PATH.'/file/'.$table.'/'.$file_name;

    @move_uploaded_file($tmp_file, $dest_file);
    @chmod($dest_file, G5_FILE_PERMISSION); 
 
	$files['link']=G5_DATA_URL.'/file/'.$table.'/'.$file_name;
	$files['name']=$file_name;
	if (!get_magic_quotes_gpc()) {
        $files['source'] = addslashes($files['source']);
    }

	return $files;
}
?>
