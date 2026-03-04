<?php
/**
 * Attacher plugin: URL upload handler (server-side)
 * 
 * Filename attacher.url.php
 * @package Attacher
 * @author Roffun
 * @copyright Copyright (c) Roffun, Webitproff 2018 - 2025 
 * @license BSD License
 */
defined('COT_CODE') or die('Wrong URL');

require_once cot_incfile('attacher', 'plug');

$url = cot_import('url', 'P', 'TXT');
$area = cot_import('area', 'P', 'ALP');
$item = cot_import('item', 'P', 'INT');
$field = cot_import('field', 'P', 'TXT');
$param = cot_import('param', 'P', 'TXT');
$x = cot_import('x', 'P', 'TXT');

$response = ['files' => []];

if (empty($url)) {
    $response['files'][] = ['error' => 'URL is empty', 'name' => ''];
    cot_sendheaders('application/json');
    echo json_encode($response);
    exit;
}

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 5,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    CURLOPT_HEADERFUNCTION => function($curl, $header_line) {
        if (stripos($header_line, 'Content-Disposition:') !== false) {
            if (preg_match('/filename[^;=\n]*=(([\'"]).*?\2|[^;\n]*)/', $header_line, $matches)) {
                $GLOBALS['curl_filename'] = trim($matches[1], '"\'');
            }
        }
        if (stripos($header_line, 'Content-Type:') !== false) {
            $GLOBALS['curl_content_type'] = trim(substr($header_line, 13));
        }
        return strlen($header_line);
    }
]);

$data = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = $GLOBALS['curl_content_type'] ?? curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
curl_close($ch);

if ($httpCode !== 200 || !$data) {
    $response['files'][] = ['error' => "Failed to fetch URL (HTTP $httpCode)", 'name' => $url];
    cot_sendheaders('application/json');
    echo json_encode($response);
    exit;
}

$filename = $GLOBALS['curl_filename'] ?? '';
if (empty($filename)) {
    $path = parse_url($effectiveUrl, PHP_URL_PATH);
    $filename = basename($path);
    if (empty($filename) || !strpos($filename, '.')) {
        $ext = '';
        if (strpos($contentType, 'jpeg') !== false || strpos($contentType, 'jpg') !== false) $ext = 'jpg';
        elseif (strpos($contentType, 'png') !== false) $ext = 'png';
        elseif (strpos($contentType, 'gif') !== false) $ext = 'gif';
        elseif (strpos($contentType, 'webp') !== false) $ext = 'webp';
        elseif (strpos($contentType, 'svg') !== false) $ext = 'svg';
        else $ext = 'bin';
        $filename = 'file_' . time() . '_' . md5($url) . '.' . $ext;
    }
}

$tmpDir = COT_UPLOAD_DIR ?: 'datas/tmp';
if (!is_dir($tmpDir)) {
    mkdir($tmpDir, 0777, true);
}

$tmpFile = $tmpDir . '/attacher_tmp_' . uniqid() . '_' . $filename;
file_put_contents($tmpFile, $data);

$_FILES['files'] = [
    'name' => $filename,
    'type' => $contentType,
    'tmp_name' => $tmpFile,
    'error' => 0,
    'size' => filesize($tmpFile)
];

$_SERVER['CONTENT_LENGTH'] = filesize($tmpFile);
$_POST['param'] = $param;
$_POST['x'] = $x;
$_GET['area'] = $area;
$_GET['item'] = $item;
$_GET['field'] = $field;

require_once cot_incfile('attacher', 'plug', 'upload');
exit;
?>