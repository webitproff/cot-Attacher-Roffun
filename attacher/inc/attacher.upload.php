<?php
/**
 * Attacher plugin: upload files
 * 
 * Filename attacher.upload.php
 * @package Attacher
 * @author Roffun
 * @copyright Copyright (c) Roffun, Webitproff 2018 - 2025 
 * @license BSD License
 */
defined('COT_CODE') or die('Wrong URL');

// Отключение вывода ошибок для продакшена
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Очистка буфера вывода
ob_start();

header('Pragma: no-cache');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Content-Disposition: inline; filename="files.json"');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: OPTIONS, HEAD, GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: X-File-Name, X-File-Type, X-File-Size');

$area = cot_import('area', 'R', 'ALP');
$item = cot_import('item', 'R', 'INT');
$field = (string)cot_import('field', 'R', 'TXT');
$filename = cot_import('filename', 'R', 'TXT');

if (!empty($filename)) {
    $filename = mb_basename(stripslashes($filename));
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'OPTIONS':
        break;
    case 'HEAD':
    case 'GET':
        header('Content-type: application/json');
        ob_clean();
        echo json_encode(att_ajax_get($area, $item, $field, $filename) ?? ['success' => false, 'message' => 'No files found']);
        break;
    case 'PATCH':
    case 'PUT':
    case 'POST':
        if (isset($_REQUEST['_method']) && $_REQUEST['_method'] === 'DELETE') {
            $id = cot_import('id', 'R', 'INT');
            header('Content-type: application/json');
            ob_clean();
            echo json_encode(['success' => $id > 0 ? (bool)att_remove($id) : false]);
        } else {
            ob_clean();
            echo att_ajax_post();
        }
        break;
    default:
        header('HTTP/1.1 405 Method Not Allowed');
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
ob_end_flush();
exit;

/**
 * Fetches AJAX data for a given file or all files attached
 *
 * @param string $area Target module/plugin code
 * @param int $item Target item id
 * @param string $field If item has several attach fields
 * @param string|null $filename Name of the original file
 * @return array|null Data for JSON response
 */
function att_ajax_get($area, $item, $field = '', $filename = null)
{
    global $db, $db_attacher, $cfg, $sys, $usr;

    $whereUserId = $item == 0 ? "AND att_user = {$usr['id']}" : '';
    if ($item == 0) {
        $unikey = cot_import('unikey', 'G', 'TXT') ?? '';
        if ($unikey) {
            $db->update($db_attacher, ['att_unikey' => $unikey], "att_item = 0 AND att_user = {$usr['id']}");
        }
    }

    $params = [$area, (int)$item, $field];
    if (empty($filename)) {
        $query = "SELECT * FROM $db_attacher WHERE att_area = ? AND att_item = ? AND att_field = ? $whereUserId ORDER BY att_order";
    } else {
        $query = "SELECT * FROM $db_attacher WHERE att_area = ? AND att_item = ? AND att_field = ? AND att_filename = ? $whereUserId LIMIT 1";
        $params[] = $filename;
    }

    $res = $db->query($query, $params);
    if ($res->rowCount() == 0) {
        return null;
    }

    $files = [];
    foreach ($res->fetchAll() as $row) {
        $file = [
            'id' => $row['att_id'],
            'name' => $row['att_filename'],
            'size' => (int)$row['att_size'],
            'url' => $cfg['mainurl'] . '/' . att_path($area, $item, $row['att_id'], $row['att_ext']),
            'deleteType' => 'POST',
            'deleteUrl' => $cfg['mainurl'] . '/index.php?r=attacher&a=upload&id=' . $row['att_id'] . '&_method=DELETE&x=' . ($sys['xk'] ?? ''),
            'title' => htmlspecialchars($row['att_title']),
            'lastmod' => $row['att_lastmod'],
            'isImage' => (bool)$row['att_img'],
            'shortname' => ($cfg['plugin']['attacher']['prefix'] ?? 'att_') . $row['att_id'] . '.' . $row['att_ext']
        ];

        if ($row['att_img']) {
            $thumb = att_thumb($row['att_id']);
            $file['thumbnailUrl'] = $cfg['mainurl'] . '/' . $thumb . '?lastmod=' . $row['att_lastmod'];
            $file['thumbnail'] = $cfg['mainurl'] . '/' . $thumb;
            $file['thumbnailBigUrl'] = $cfg['mainurl'] . '/' . att_thumb($row['att_id'], $cfg['plugin']['attacher']['thumb_big_width'] ?? 800, $cfg['plugin']['attacher']['thumb_big_height'] ?? 600, $cfg['plugin']['attacher']['thumb_big_framing'] ?? 'fit') . '?lastmod=' . $row['att_lastmod'];
            $file['thumbnailBig'] = $cfg['mainurl'] . '/' . att_thumb($row['att_id'], $cfg['plugin']['attacher']['thumb_big_width'] ?? 800, $cfg['plugin']['attacher']['thumb_big_height'] ?? 600, $cfg['plugin']['attacher']['thumb_big_framing'] ?? 'fit');
        } else {
            $icon = att_icon(att_get_ext($row['att_filename']));
            $file['thumbnailUrl'] = $cfg['mainurl'] . '/' . $icon;
            $file['thumbnailBigUrl'] = $cfg['mainurl'] . '/' . $icon;
            $file['downloadUrl'] = $cfg['mainurl'] . '/index.php?r=attacher&a=dl&id=' . $row['att_id'];
        }

        foreach (cot_getextplugins('attacher.upload.row') as $pl) {
            include $pl;
        }

        $files[] = $file;
    }

    return empty($filename) ? ['files' => $files] : $files[0];
}

/**
 * Handles POST file uploads
 *
 * @return string JSON response
 */
/**
 * Handles POST file uploads (including URL uploads)
 *
 * @return string JSON response
 */
function att_ajax_post()
{
    global $cfg, $L;

    // Если передан URL и нет загружаемых файлов – обрабатываем как загрузку по ссылке
    if (isset($_POST['url']) && !empty($_POST['url']) && empty($_FILES['files']['tmp_name'][0])) {
        $url = $_POST['url'];
        $result = att_ajax_handle_url_upload($url);
        header('Content-type: application/json');
        return json_encode(['files' => [$result]]);
    }

    $param_name = 'files';
    if (!isset($_FILES[$param_name]) || empty($_FILES[$param_name]['tmp_name'])) {
        header('HTTP/1.1 400 Bad Request');
        return json_encode(['files' => [], 'error' => $L['att_err_no_file'] ?? 'No file uploaded']);
    }

    $upload = $_FILES[$param_name];
    $info = [];

    $file_name = $_SERVER['HTTP_X_FILE_NAME'] ?? null;
    $content_range = $_SERVER['HTTP_CONTENT_RANGE'] ?? null;
    $size = $content_range ? (int)(preg_split('/[^0-9]+/', $content_range)[3] ?? 0) : null;

    if (is_array($upload['tmp_name'])) {
        foreach (array_keys($upload['tmp_name']) as $index) {
            $info[] = att_ajax_handle_file_upload(
                $upload['tmp_name'][$index],
                $file_name ?: $upload['name'][$index],
                $size ?: $upload['size'][$index],
                $upload['type'][$index],
                $upload['error'][$index],
                $index,
                $content_range
            );
        }
    } else {
        $info[] = att_ajax_handle_file_upload(
            $upload['tmp_name'] ?? null,
            $file_name ?: ($upload['name'] ?? null),
            $size ?: ($upload['size'] ?? ($_SERVER['CONTENT_LENGTH'] ?? 0)),
            $upload['type'] ?? ($_SERVER['CONTENT_TYPE'] ?? ''),
            $upload['error'] ?? null,
            null,
            $content_range
        );
    }

    header('Vary: Accept');
    header('Content-type: application/json');
    return json_encode(['files' => $info]);
}

/**
 * AJAX upload handler
 *
 * @return stdClass
 */
/**
 * AJAX upload handler
 *
 * @return stdClass
 */
function att_ajax_handle_file_upload($uploaded_file, $name, $size, $type, $error, $index = null, $content_range = null)
{
    global $db, $db_attacher, $area, $item, $field, $cfg, $usr, $L, $sys;

    $file = new stdClass();
    $file->name = trim(mb_basename(stripslashes($name ?? '')));
    $file->size = (int)$size;
    $file->type = $type;
    $file->lastmod = date('Y-m-d H:i:s', $sys['now'] ?? time());

    if (!att_ajax_validate($uploaded_file, $file, $error)) {
        return $file;
    }

    $file_ext = att_get_ext($file->name);
    $is_img = in_array($file_ext, ['gif', 'jpg', 'jpeg', 'png', 'webp']);
    $file_path = ($cfg['plugin']['attacher']['folder'] ?? 'datas/attacher') . '/' . $area . '/' . $item . '/' . $file->name;

    $dir_path = dirname($file_path);
    if (!is_dir($dir_path) && !mkdir($dir_path, $cfg['dir_perms'] ?? 0755, true)) {
        $file->error = $L['att_err_dir'] ?? 'Failed to create directory';
        return $file;
    }

    $append_file = $content_range && is_file($uploaded_file) && $file->size > filesize($uploaded_file);
    if ($uploaded_file && is_uploaded_file($uploaded_file)) {
        if ($append_file) {
            file_put_contents($file_path, fopen($uploaded_file, 'r'), FILE_APPEND);
        } else {
            move_uploaded_file($uploaded_file, $file_path);
        }
    } else {
        file_put_contents($file_path, fopen('php://input', 'r'), $append_file ? FILE_APPEND : 0);
    }

    $file_size = is_file($file_path) ? filesize($file_path) : 0;
    if ($file_size !== $file->size) {
        @unlink($file_path);
        $file->error = 'abort';
        return $file;
    }

    if ($is_img && !cot_img_check_memory($file_path)) {
        @unlink($file_path);
        $file->error = $L['att_err_toobig'] ?? 'File too large';
        return $file;
    }

    $order = ((int)$db->query("SELECT MAX(att_order) FROM $db_attacher WHERE att_area = ? AND att_item = ?", [$area, $item])->fetchColumn()) + 1;
    $unikey = $item == 0 ? (cot_import('unikey', 'G', 'TXT') ?? '') : '';

    $affected = $db->insert($db_attacher, [
        'att_user' => $usr['id'],
        'att_area' => $area,
        'att_item' => $item,
        'att_field' => $field,
        'att_path' => '',
        'att_filename' => $file->name,
        'att_ext' => $file_ext,
        'att_img' => (int)$is_img,
        'att_size' => $file->size,
        'att_title' => '',
        'att_count' => 0,
        'att_order' => $order,
        'att_lastmod' => $file->lastmod,
        'att_unikey' => $unikey,
    ]);

    if ($affected !== 1) {
        @unlink($file_path);
        $file->error = $L['att_err_db'] ?? 'Database error';
        return $file;
    }

    $id = $db->lastInsertId();
    $tmpFilePath = $file_path;
    $file_path = att_path($area, $item, $id, $file_ext);
    rename($tmpFilePath, $file_path);

    // Новая обработка изображений: ресайз до 1200px и конвертация в WebP (кроме исходных WebP)
    if ($is_img) {
        // Читаем файл в память
        $image_data = @file_get_contents($file_path);
        if (!$image_data) {
            $db->delete($db_attacher, "att_id = ?", [$id]);
            @unlink($file_path);
            $file->error = 'Failed to read image file';
            return $file;
        }
        $src_image = @imagecreatefromstring($image_data);
        if (!$src_image) {
            $db->delete($db_attacher, "att_id = ?", [$id]);
            @unlink($file_path);
            $file->error = 'Failed to create image resource';
            return $file;
        }

        $width = imagesx($src_image);
        $height = imagesy($src_image);

        // Определяем новые размеры (только если ширина > 1200)
        $new_width = $width;
        $new_height = $height;
        if ($width > 1200) {
            $new_width = 1200;
            $new_height = (int)($height * 1200 / $width);
        }

        // Если исходный формат уже WebP и ресайз не требуется, оставляем как есть
        if ($file_ext == 'webp' && $new_width == $width && $new_height == $height) {
            imagedestroy($src_image);
            // ничего не делаем, файл остаётся без изменений
        } else {
            // Создаём целевое изображение с новыми размерами
            $dst_image = imagecreatetruecolor($new_width, $new_height);

            // Сохраняем прозрачность для PNG/GIF (если исходный формат поддерживает)
            if ($file_ext == 'png' || $file_ext == 'gif') {
                imagealphablending($dst_image, false);
                imagesavealpha($dst_image, true);
                $transparent = imagecolorallocatealpha($dst_image, 255, 255, 255, 127);
                imagefilledrectangle($dst_image, 0, 0, $new_width, $new_height, $transparent);
            }

            imagecopyresampled($dst_image, $src_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            imagedestroy($src_image);

            // Сохраняем в WebP с качеством 85
            $new_file_path = att_path($area, $item, $id, 'webp');
            $success = imagewebp($dst_image, $new_file_path, 85);
            imagedestroy($dst_image);
				if (!$success || !file_exists($new_file_path) || filesize($new_file_path) == 0) {
								// Ошибка конвертации – удаляем запись и все файлы
								$db->delete($db_attacher, "att_id = ?", [$id]);
								@unlink($file_path);
								@unlink($new_file_path);
								$file->error = 'Failed to convert image to WebP';
								return $file;
							}
							// Удаляем исходный файл (только если путь отличается — для WebP с ресайзом не удаляем, т.к. перезаписали)
							if ($file_path !== $new_file_path) {
								@unlink($file_path);
							}
							// Обновляем переменные для дальнейшего использования
							$file_path = $new_file_path;
							$file_ext = 'webp';
							$file->name = pathinfo($file->name, PATHINFO_FILENAME) . '.webp';
/*             if (!$success || !file_exists($new_file_path) || filesize($new_file_path) == 0) {
                // Ошибка конвертации – удаляем запись и все файлы
                $db->delete($db_attacher, "att_id = ?", [$id]);
                @unlink($file_path);
                @unlink($new_file_path);
                $file->error = 'Failed to convert image to WebP';
                return $file;
            }

            // Удаляем исходный файл
            @unlink($file_path);

            // Обновляем переменные для дальнейшего использования
            $file_path = $new_file_path;
            $file_ext = 'webp';
            $file->name = pathinfo($file->name, PATHINFO_FILENAME) . '.webp'; */
        }

        // Устанавливаем актуальный размер файла
        $file_size = filesize($file_path);
        $file->size = $file_size;
    }

    $db->update($db_attacher, [
        'att_path' => $file_path,
        'att_size' => $file_size,
        'att_ext' => $file_ext,
        'att_filename' => $file->name,
        'att_lastmod' => $file->lastmod
    ], "att_id = ?", [$id]);

    $file->isImage = $is_img;
    $file->url = $cfg['mainurl'] . '/' . $file_path;
    $file->thumbnail = $is_img ? $cfg['mainurl'] . '/' . att_thumb($id) : $cfg['mainurl'] . '/' . att_icon($file_ext);
    $file->thumbnailUrl = $is_img ? $cfg['mainurl'] . '/' . att_thumb($id) . '?lastmod=' . $file->lastmod : $cfg['mainurl'] . '/' . att_icon($file_ext);
    $file->thumbnailBig = $is_img ? $cfg['mainurl'] . '/' . att_thumb($id, $cfg['plugin']['attacher']['thumb_big_width'] ?? 800, $cfg['plugin']['attacher']['thumb_big_height'] ?? 600, $cfg['plugin']['attacher']['thumb_big_framing'] ?? 'fit') : $cfg['mainurl'] . '/' . att_icon($file_ext);
    $file->thumbnailBigUrl = $is_img ? $cfg['mainurl'] . '/' . att_thumb($id, $cfg['plugin']['attacher']['thumb_big_width'] ?? 800, $cfg['plugin']['attacher']['thumb_big_height'] ?? 600, $cfg['plugin']['attacher']['thumb_big_framing'] ?? 'fit') . '?lastmod=' . $file->lastmod : $cfg['mainurl'] . '/' . att_icon($file_ext);
    $file->id = $id;
    $file->deleteUrl = $cfg['mainurl'] . '/index.php?r=attacher&a=upload&id=' . $id . '&_method=DELETE&x=' . ($sys['xk'] ?? '');
    $file->deleteType = 'POST';
    $file->shortname = ($cfg['plugin']['attacher']['prefix'] ?? 'att_') . $id . '.' . $file_ext;
    $file->downloadUrl = $cfg['mainurl'] . '/index.php?r=attacher&a=dl&id=' . $id;

    foreach (cot_getextplugins('attacher.upload.after_save') as $pl) {
        include $pl;
    }

    return $file;
}

/**
 * Validates uploaded file
 */
function att_ajax_validate($uploaded_file, $file, $error)
{
    global $area, $item, $field, $L, $cfg;

    if (!cot_auth('plug', 'attacher', 'W')) {
        $file->error = $L['att_err_perms'] ?? 'Permission denied';
        return false;
    }

    if ($error) {
        $file->error = $error;
        return false;
    }

    if (!$file->name) {
        $file->error = 'missingFileName';
        return false;
    }

    $file_ext = att_get_ext($file->name);
    if (!att_check_file($file_ext)) {
        $file->error = $L['att_err_type'] ?? 'Invalid file type';
        return false;
    }

    $file_size = $uploaded_file && is_uploaded_file($uploaded_file) ? filesize($uploaded_file) : ($_SERVER['CONTENT_LENGTH'] ?? 0);
    $limits = att_get_limits();
    if (($limits['file'] ?? 0) && ($file_size > $limits['file'] || $file->size > $limits['file'])) {
        $file->error = $L['att_err_toobig'] ?? 'File too large';
        return false;
    }

    if ($file_size < 1) {
        $file->error = 'minFileSize';
        return false;
    }

    $params = cot_import('param', 'R', 'HTM') ?? '';
    if (!empty($params)) {
        $params = unserialize(base64_decode($params)) ?: [];
        if (!empty($params['type'])) {
            $params['type'] = json_decode($params['type'], true) ?: ['all'];
            $is_img = in_array($file_ext, ['gif', 'jpg', 'jpeg', 'png', 'webp']);
            $typeOk = in_array('all', $params['type']) || (in_array('image', $params['type']) && $is_img);
            if (!$typeOk) {
                $file->error = $L['att_err_type'] ?? 'Invalid file type';
                return false;
            }
        }
        $params['limit'] = $params['limit'] ?? ($cfg['plugin']['attacher']['items'] ?? 0);
        $params['field'] = $params['field'] ?? $field;
        if ($params['limit'] > 0 && att_count_files($area, $item, $params['field']) >= $params['limit']) {
            $file->error = $L['att_err_count'] ?? 'Too many files';
            return false;
        }
    }

    return true;
}

/**
 * Workaround for splitting basename with UTF-8 multibyte chars
 */
function mb_basename($filepath, $suffix = null)
{
    $splited = preg_split('/\//', rtrim($filepath, '/ '));
    return substr(basename('X' . end($splited), $suffix), 1);
}

/**
 * Fix for overflowing signed 32-bit integers
 */
function fix_integer_overflow($size)
{
    if ($size < 0) {
        $size += 2.0 * (PHP_INT_MAX + 1);
    }
    return $size;
}
/**
 * Handle file upload from a remote URL using cURL
 *
 * @param string $url
 * @return stdClass
 */
/**
 * Handle file upload from a remote URL using cURL
 *
 * @param string $url
 * @return stdClass
 */
function att_ajax_handle_url_upload($url)
{
    global $db, $db_attacher, $area, $item, $field, $cfg, $usr, $L, $sys;

    $file = new stdClass();
    $file->name = basename(parse_url($url, PHP_URL_PATH));
    if (empty($file->name) || !preg_match('/\.\w+$/', $file->name)) {
        $file->name = 'file_' . md5($url) . '.bin';
    }
    $file->size = 0;
    $file->type = '';
    $file->lastmod = date('Y-m-d H:i:s', $sys['now'] ?? time());

    // Скачиваем файл во временную папку
    $tmp_file = sys_get_temp_dir() . '/' . md5($url . microtime()) . '.tmp';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_FOLLOWLOCATION => 1,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Cotonti Attacher)'
    ]);
    $data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($data === false || $http_code != 200) {
        $file->error = 'Failed to download file: ' . ($error ?: "HTTP $http_code");
        return $file;
    }

    file_put_contents($tmp_file, $data);
    $file->size = filesize($tmp_file);
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $file->type = finfo_file($finfo, $tmp_file);
    finfo_close($finfo);

    // Определяем расширение
    $ext = strtolower(pathinfo($file->name, PATHINFO_EXTENSION));
    if (empty($ext)) {
        $mime_to_ext = [
            'image/jpeg' => 'jpg',
            'image/jpg'  => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
        ];
        $ext = $mime_to_ext[$file->type] ?? 'bin';
        $file->name .= '.' . $ext;
    }

    // Валидация (аналогично att_ajax_validate)
    if (!cot_auth('plug', 'attacher', 'W')) {
        $file->error = $L['att_err_perms'] ?? 'Permission denied';
        @unlink($tmp_file);
        return $file;
    }

    $file_ext = $ext;
    if (!att_check_file($file_ext)) {
        $file->error = $L['att_err_type'] ?? 'Invalid file type';
        @unlink($tmp_file);
        return $file;
    }

    $limits = att_get_limits();
    if (($limits['file'] ?? 0) && $file->size > $limits['file']) {
        $file->error = $L['att_err_toobig'] ?? 'File too large';
        @unlink($tmp_file);
        return $file;
    }

    if ($file->size < 1) {
        $file->error = 'minFileSize';
        @unlink($tmp_file);
        return $file;
    }

    // Проверка лимитов по количеству
    $params = cot_import('param', 'R', 'HTM') ?? '';
    if (!empty($params)) {
        $params = unserialize(base64_decode($params)) ?: [];
        if (!empty($params['type'])) {
            $params['type'] = json_decode($params['type'], true) ?: ['all'];
            $is_img = in_array($file_ext, ['gif', 'jpg', 'jpeg', 'png', 'webp']);
            $typeOk = in_array('all', $params['type']) || (in_array('image', $params['type']) && $is_img);
            if (!$typeOk) {
                $file->error = $L['att_err_type'] ?? 'Invalid file type';
                @unlink($tmp_file);
                return $file;
            }
        }
        $params['limit'] = $params['limit'] ?? ($cfg['plugin']['attacher']['items'] ?? 0);
        $params['field'] = $params['field'] ?? $field;
        if ($params['limit'] > 0 && att_count_files($area, $item, $params['field']) >= $params['limit']) {
            $file->error = $L['att_err_count'] ?? 'Too many files';
            @unlink($tmp_file);
            return $file;
        }
    }

    // Далее – как при обычной загрузке
    $is_img = in_array($file_ext, ['gif', 'jpg', 'jpeg', 'png', 'webp']);
    $file_path = ($cfg['plugin']['attacher']['folder'] ?? 'datas/attacher') . '/' . $area . '/' . $item . '/' . $file->name;

    $dir_path = dirname($file_path);
    if (!is_dir($dir_path) && !mkdir($dir_path, $cfg['dir_perms'] ?? 0755, true)) {
        $file->error = $L['att_err_dir'] ?? 'Failed to create directory';
        @unlink($tmp_file);
        return $file;
    }

    if (!rename($tmp_file, $file_path)) {
        $file->error = 'Failed to move file';
        @unlink($tmp_file);
        return $file;
    }

    if ($is_img && !cot_img_check_memory($file_path)) {
        @unlink($file_path);
        $file->error = $L['att_err_toobig'] ?? 'File too large';
        return $file;
    }

    $order = ((int)$db->query("SELECT MAX(att_order) FROM $db_attacher WHERE att_area = ? AND att_item = ?", [$area, $item])->fetchColumn()) + 1;
    $unikey = $item == 0 ? (cot_import('unikey', 'G', 'TXT') ?? '') : '';

    $affected = $db->insert($db_attacher, [
        'att_user' => $usr['id'],
        'att_area' => $area,
        'att_item' => $item,
        'att_field' => $field,
        'att_path' => '',
        'att_filename' => $file->name,
        'att_ext' => $file_ext,
        'att_img' => (int)$is_img,
        'att_size' => $file->size,
        'att_title' => '',
        'att_count' => 0,
        'att_order' => $order,
        'att_lastmod' => $file->lastmod,
        'att_unikey' => $unikey,
    ]);

    if ($affected !== 1) {
        @unlink($file_path);
        $file->error = $L['att_err_db'] ?? 'Database error';
        return $file;
    }

    $id = $db->lastInsertId();
    $tmpFilePath = $file_path;
    $file_path = att_path($area, $item, $id, $file_ext);
    rename($tmpFilePath, $file_path);

    // Новая обработка изображений: ресайз до 1200px и конвертация в WebP (кроме исходных WebP)
    if ($is_img) {
        // Читаем файл в память
        $image_data = @file_get_contents($file_path);
        if (!$image_data) {
            $db->delete($db_attacher, "att_id = ?", [$id]);
            @unlink($file_path);
            $file->error = 'Failed to read image file';
            return $file;
        }
        $src_image = @imagecreatefromstring($image_data);
        if (!$src_image) {
            $db->delete($db_attacher, "att_id = ?", [$id]);
            @unlink($file_path);
            $file->error = 'Failed to create image resource';
            return $file;
        }

        $width = imagesx($src_image);
        $height = imagesy($src_image);

        // Определяем новые размеры (только если ширина > 1200)
        $new_width = $width;
        $new_height = $height;
        if ($width > 1200) {
            $new_width = 1200;
            $new_height = (int)($height * 1200 / $width);
        }

        // Если исходный формат уже WebP и ресайз не требуется, оставляем как есть
        if ($file_ext == 'webp' && $new_width == $width && $new_height == $height) {
            imagedestroy($src_image);
            // ничего не делаем, файл остаётся без изменений
        } else {
            // Создаём целевое изображение с новыми размерами
            $dst_image = imagecreatetruecolor($new_width, $new_height);

            // Сохраняем прозрачность для PNG/GIF (если исходный формат поддерживает)
            if ($file_ext == 'png' || $file_ext == 'gif') {
                imagealphablending($dst_image, false);
                imagesavealpha($dst_image, true);
                $transparent = imagecolorallocatealpha($dst_image, 255, 255, 255, 127);
                imagefilledrectangle($dst_image, 0, 0, $new_width, $new_height, $transparent);
            }

            imagecopyresampled($dst_image, $src_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            imagedestroy($src_image);

            // Сохраняем в WebP с качеством 85
            $new_file_path = att_path($area, $item, $id, 'webp');
            $success = imagewebp($dst_image, $new_file_path, 85);
            imagedestroy($dst_image);

            if (!$success || !file_exists($new_file_path) || filesize($new_file_path) == 0) {
                // Ошибка конвертации – удаляем запись и все файлы
                $db->delete($db_attacher, "att_id = ?", [$id]);
                @unlink($file_path);
                @unlink($new_file_path);
                $file->error = 'Failed to convert image to WebP';
                return $file;
            }

            // Удаляем исходный файл
            @unlink($file_path);

            // Обновляем переменные для дальнейшего использования
            $file_path = $new_file_path;
            $file_ext = 'webp';
            $file->name = pathinfo($file->name, PATHINFO_FILENAME) . '.webp';
        }

        // Устанавливаем актуальный размер файла
        $file_size = filesize($file_path);
        $file->size = $file_size;
    }

    $db->update($db_attacher, [
        'att_path' => $file_path,
        'att_size' => filesize($file_path),
        'att_ext' => $file_ext,
        'att_filename' => $file->name,
        'att_lastmod' => $file->lastmod
    ], "att_id = ?", [$id]);

    // Формируем выходные данные
    $file->isImage = $is_img;
    $file->url = $cfg['mainurl'] . '/' . $file_path;
    $file->thumbnail = $is_img ? $cfg['mainurl'] . '/' . att_thumb($id) : $cfg['mainurl'] . '/' . att_icon($file_ext);
    $file->thumbnailUrl = $is_img ? $cfg['mainurl'] . '/' . att_thumb($id) . '?lastmod=' . $file->lastmod : $cfg['mainurl'] . '/' . att_icon($file_ext);
    $file->thumbnailBig = $is_img ? $cfg['mainurl'] . '/' . att_thumb($id, $cfg['plugin']['attacher']['thumb_big_width'] ?? 800, $cfg['plugin']['attacher']['thumb_big_height'] ?? 600, $cfg['plugin']['attacher']['thumb_big_framing'] ?? 'fit') : $cfg['mainurl'] . '/' . att_icon($file_ext);
    $file->thumbnailBigUrl = $is_img ? $cfg['mainurl'] . '/' . att_thumb($id, $cfg['plugin']['attacher']['thumb_big_width'] ?? 800, $cfg['plugin']['attacher']['thumb_big_height'] ?? 600, $cfg['plugin']['attacher']['thumb_big_framing'] ?? 'fit') . '?lastmod=' . $file->lastmod : $cfg['mainurl'] . '/' . att_icon($file_ext);
    $file->id = $id;
    $file->deleteUrl = $cfg['mainurl'] . '/index.php?r=attacher&a=upload&id=' . $id . '&_method=DELETE&x=' . ($sys['xk'] ?? '');
    $file->deleteType = 'POST';
    $file->shortname = ($cfg['plugin']['attacher']['prefix'] ?? 'att_') . $id . '.' . $file_ext;
    $file->downloadUrl = $cfg['mainurl'] . '/index.php?r=attacher&a=dl&id=' . $id;

    return $file;
}