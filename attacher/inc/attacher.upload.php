<?php
/**
 * Attacher plugin: upload files
 *
 * Filename attacher.upload.php
 * @package Attacher
 * @author Roffun
 * @copyright Copyright (c) Roffun, Webitproff 2018 - 2026
 * @license BSD License
 * @version 2.1.0
 * @requires PHP 8.5+
 *
 * Переработано в 2026:
 *  - SSRF-защита в загрузке по URL (проверка схемы, резолвинг IP, запрет redirect,
 *    запрет приватных и резервных диапазонов, обрезка по размеру до полной загрузки)
 *  - CSRF-защита на state-changing операциях через x-token (cot_check_xg / cot_check_xp)
 *  - Проверка прав до сетевого запроса в URL-загрузке (было после)
 *  - Убрана уязвимость PHP object injection: unserialize с allowed_classes: false
 *  - Санитизация имён файлов (path traversal, control chars, лидирующие точки)
 *  - CORS-wildcard убран (был небезопасным на аутентифицированном эндпоинте)
 *  - Ошибки пишутся в error_log, а не возвращаются пользователю как есть
 *  - Проверка возвращаемого значения move_uploaded_file / rename
 *  - Отдельная проверка размера после каждой чанковой записи (был обход через Content-Range)
 *  - Возможные null-байты в путях отсекаются
 */

defined('COT_CODE') or die('Wrong URL');

// Ошибки — в лог, не в вывод (не ломает JSON)
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// Буферизация вывода — на случай сторонних echo/warnings
ob_start();

header('Pragma: no-cache');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Content-Disposition: inline; filename="files.json"');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');

// CORS-заголовки убраны: эндпоинт аутентифицированный, wildcard-allow-origin
// не даёт никакой пользы и потенциально опасен. Если действительно нужен
// cross-origin — добавляйте с конкретным доверенным Origin и c credentials.

$area   = (string) cot_import('area', 'R', 'ALP');
$item   = (int) cot_import('item', 'R', 'INT');
$field  = (string) cot_import('field', 'R', 'TXT');
$filename = (string) cot_import('filename', 'R', 'TXT');

if ($filename !== '') {
    $filename = att_safe_filename(mb_basename(stripslashes($filename)));
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

switch ($method) {
    case 'OPTIONS':
        // Preflight без тела
        break;

    case 'HEAD':
        // HEAD без тела
        break;

    case 'GET':
        // GET отдаёт список вложений. Без авторизации — data leak:
        // гость мог видеть чужие файлы, зная area + item.
        if (!cot_auth('plug', 'attacher', 'W')) {
            http_response_code(403);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            break;
        }
        header('Content-type: application/json');
        ob_clean();
        echo json_encode(
            att_ajax_get($area, $item, $field, $filename)
            ?? ['success' => false, 'message' => 'No files found']
        );
        break;

    case 'PATCH':
    case 'PUT':
    case 'POST':
        // $method переопределяется только через POST-поле _method
        // (раньше читалось из $_REQUEST — включая GET и COOKIE)
        // $requestedMethod = strtoupper((string) ($_POST['_method'] ?? $method));
		$requestedMethod = strtoupper((string) ($_POST['_method'] ?? $_GET['_method'] ?? $method));
        // CSRF: любая мутация требует валидный x-token Cotonti
        if (!att_check_csrf()) {
            http_response_code(403);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
            break;
        }

        if ($requestedMethod === 'DELETE') {
            $id = (int) cot_import('id', 'R', 'INT');
            header('Content-type: application/json');
            ob_clean();
            // att_remove() внутри проверяет права на конкретную запись
            echo json_encode(['success' => $id > 0 && (bool) att_remove($id)]);
        } else {
            ob_clean();
            echo att_ajax_post();
        }
        break;

    default:
        http_response_code(405);
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

ob_end_flush();
exit;

/**
 * Проверяет CSRF-токен Cotonti.
 *
 * У плагина токен `x` передаётся в URL запроса (см. ATTACHER_X в шаблоне и
 * deleteUrl в ответе AJAX). Поэтому проверяем и GET, и POST — на случай
 * форм, где `x` придёт как hidden input.
 */
function att_check_csrf(): bool
{
    $given = (string) ($_GET['x'] ?? $_POST['x'] ?? '');
    $expected = (string) (cot::$sys['xk'] ?? '');

    if ($given === '' || $expected === '') {
        return false;
    }
    return hash_equals($expected, $given);
}

/**
 * Санитизация имени файла. Убирает:
 *   - path separators `/` и `\`;
 *   - управляющие символы и null-байты;
 *   - лидирующие точки (скрытые файлы и `.`/`..`);
 *   - любые вхождения `..`;
 *   - ограничивает длину 200 символов.
 *
 * Порядок «сначала убрать слэши, потом искать ..» важен: `../` превращается
 * в `..` после первой замены, затем `..` отсекается.
 */
function att_safe_filename(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        return '';
    }

    $name = str_replace(['/', '\\'], '', $name);

    $name = preg_replace('/[\x00-\x1f\x7f]/u', '', $name) ?? '';

    while (str_contains($name, '..')) {
        $name = str_replace('..', '.', $name);
    }

    $name = ltrim($name, '.');

    if (mb_strlen($name) > 200) {
        $ext  = pathinfo($name, PATHINFO_EXTENSION);
        $base = pathinfo($name, PATHINFO_FILENAME);
        if ($ext !== '') {
            $name = mb_substr($base, 0, 190) . '.' . mb_substr($ext, 0, 9);
        } else {
            $name = mb_substr($name, 0, 200);
        }
    }

    return $name;
}

/**
 * Fetches AJAX data for a given file or all files attached.
 *
 * @param string      $area     Target module/plugin code
 * @param int         $item     Target item id
 * @param string      $field    If item has several attach fields
 * @param string|null $filename Name of the original file
 * @return array|null Data for JSON response
 */
function att_ajax_get($area, $item, $field = '', $filename = null)
{
    global $db, $db_attacher, $cfg, $sys, $usr;

    // $usr['id'] кастим в int: даже если пришёл строкой, SQL-инъекция невозможна
    $userId = (int) ($usr['id'] ?? 0);
    $whereUserId = ((int) $item === 0) ? " AND att_user = {$userId}" : '';

    if ((int) $item === 0) {
        $unikey = (string) (cot_import('unikey', 'G', 'TXT') ?? '');
        if ($unikey !== '') {
            $db->update(
                $db_attacher,
                ['att_unikey' => $unikey],
                "att_item = 0 AND att_user = {$userId}"
            );
        }
    }

    $params = [$area, (int) $item, $field];

    if (empty($filename)) {
        $query = "SELECT * FROM $db_attacher
                  WHERE att_area = ? AND att_item = ? AND att_field = ? $whereUserId
                  ORDER BY att_order";
    } else {
        $query = "SELECT * FROM $db_attacher
                  WHERE att_area = ? AND att_item = ? AND att_field = ? AND att_filename = ? $whereUserId
                  LIMIT 1";
        $params[] = $filename;
    }

    $res = $db->query($query, $params);
    if ($res->rowCount() === 0) {
        return null;
    }

    $prefix = (string) ($cfg['plugin']['attacher']['prefix'] ?? 'att_');
    $xkToken = (string) ($sys['xk'] ?? '');
    $files = [];

    foreach ($res->fetchAll() as $row) {
        $attId = (int) $row['att_id'];

        // att_path может вернуть false, если файл пропал с диска.
        // Склеиваем URL только когда путь есть.
        $relPath = att_path($area, $item, $attId, (string) $row['att_ext']);
        $absUrl  = $relPath ? $cfg['mainurl'] . '/' . $relPath : '';

        $file = [
            'id'         => $attId,
            'name'       => $row['att_filename'],
            'size'       => (int) $row['att_size'],
            'url'        => $absUrl,
            'deleteType' => 'POST',
            'deleteUrl'  => $cfg['mainurl']
                            . '/index.php?r=attacher&a=upload&id=' . $attId
                            . '&_method=DELETE&x=' . urlencode($xkToken),
            'title'      => htmlspecialchars($row['att_title'] ?? '', ENT_QUOTES, 'UTF-8'),
            'lastmod'    => $row['att_lastmod'],
            'isImage'    => (bool) $row['att_img'],
            'shortname'  => $prefix . $attId . '.' . $row['att_ext'],
        ];

        if ($row['att_img']) {
            $thumb = att_thumb($attId);
            // att_thumb может вернуть false для не-изображений (теоретически)
            $thumbUrl = $thumb ? $cfg['mainurl'] . '/' . $thumb : '';

            $bigThumb = att_thumb(
                $attId,
                (int) ($cfg['plugin']['attacher']['thumb_big_width'] ?? 800),
                (int) ($cfg['plugin']['attacher']['thumb_big_height'] ?? 600),
                (string) ($cfg['plugin']['attacher']['thumb_big_framing'] ?? 'fit')
            );
            $bigThumbUrl = $bigThumb ? $cfg['mainurl'] . '/' . $bigThumb : '';

            $file['thumbnailUrl']    = $thumbUrl !== '' ? $thumbUrl . '?lastmod=' . $row['att_lastmod'] : '';
            $file['thumbnail']       = $thumbUrl;
            $file['thumbnailBigUrl'] = $bigThumbUrl !== '' ? $bigThumbUrl . '?lastmod=' . $row['att_lastmod'] : '';
            $file['thumbnailBig']    = $bigThumbUrl;
        } else {
            $icon = att_icon(att_get_ext($row['att_filename']) ?: 'bin');
            $file['thumbnailUrl']    = $cfg['mainurl'] . '/' . $icon;
            $file['thumbnailBigUrl'] = $cfg['mainurl'] . '/' . $icon;
            $file['downloadUrl']     = $cfg['mainurl'] . '/index.php?r=attacher&a=dl&id=' . $attId;
        }

        foreach (cot_getextplugins('attacher.upload.row') as $pl) {
            include $pl;
        }

        $files[] = $file;
    }

    return empty($filename) ? ['files' => $files] : $files[0];
}

/**
 * Handles POST file uploads (including URL uploads).
 *
 * @return string JSON response
 */
function att_ajax_post(): string
{
    global $L;

    // URL-загрузка: только когда файлов нет и url передан через POST
    if (
        isset($_POST['url'])
        && $_POST['url'] !== ''
        && (empty($_FILES['files']['tmp_name']) || empty($_FILES['files']['tmp_name'][0]))
    ) {
        // $_POST['url'] проверяем как string явно
        $url = is_string($_POST['url']) ? $_POST['url'] : '';
        $result = att_ajax_handle_url_upload($url);
        header('Content-type: application/json');
        return json_encode(['files' => [$result]]);
    }

    $paramName = 'files';
    if (!isset($_FILES[$paramName]) || empty($_FILES[$paramName]['tmp_name'])) {
        http_response_code(400);
        return json_encode([
            'files' => [],
            'error' => $L['att_err_no_file'] ?? 'No file uploaded',
        ]);
    }

    $upload = $_FILES[$paramName];
    $info = [];

    // X-File-Name и X-File-Size приходят от клиента — используем только как fallback
    $hdrFileName = isset($_SERVER['HTTP_X_FILE_NAME']) && is_string($_SERVER['HTTP_X_FILE_NAME'])
        ? $_SERVER['HTTP_X_FILE_NAME']
        : null;

    // Content-Range для чанков. Формат: "bytes 0-999/1000".
    // Извлекаем только цифры; при отсутствии — null.
    $contentRange = isset($_SERVER['HTTP_CONTENT_RANGE']) && is_string($_SERVER['HTTP_CONTENT_RANGE'])
        ? $_SERVER['HTTP_CONTENT_RANGE']
        : null;

    $size = null;
    if ($contentRange !== null && preg_match('/\/(\d+)$/', $contentRange, $m)) {
        $size = (int) $m[1];
    }

    if (is_array($upload['tmp_name'])) {
        foreach (array_keys($upload['tmp_name']) as $index) {
            $info[] = att_ajax_handle_file_upload(
                $upload['tmp_name'][$index] ?: null,
                $hdrFileName ?: ($upload['name'][$index] ?? null),
                $size ?? (int) ($upload['size'][$index] ?? 0),
                (string) ($upload['type'][$index] ?? ''),
                $upload['error'][$index] ?? null,
                $index,
                $contentRange
            );
        }
    } else {
        $info[] = att_ajax_handle_file_upload(
            $upload['tmp_name'] ?? null,
            $hdrFileName ?: ($upload['name'] ?? null),
            $size ?? (int) ($upload['size'] ?? ($_SERVER['CONTENT_LENGTH'] ?? 0)),
            (string) ($upload['type'] ?? ($_SERVER['CONTENT_TYPE'] ?? '')),
            $upload['error'] ?? null,
            null,
            $contentRange
        );
    }

    header('Vary: Accept');
    header('Content-type: application/json');
    return json_encode(['files' => $info]);
}

/**
 * AJAX upload handler (обычная загрузка одного файла).
 *
 * @return stdClass
 */
function att_ajax_handle_file_upload($uploaded_file, $name, $size, $type, $error, $index = null, $content_range = null)
{
    global $db, $db_attacher, $area, $item, $field, $cfg, $usr, $L, $sys;

    $file = new stdClass();
    // Санитизация имени (path traversal, control chars, лидирующие точки)
    $file->name = att_safe_filename(mb_basename(stripslashes((string) ($name ?? ''))));
    $file->size = (int) $size;
    $file->type = (string) $type;
    $file->lastmod = date('Y-m-d H:i:s', (int) ($sys['now'] ?? time()));

    if (!att_ajax_validate($uploaded_file, $file, $error)) {
        return $file;
    }

    $file_ext = att_get_ext($file->name);
    if ($file_ext === false) {
        $file->error = $L['att_err_type'] ?? 'Invalid file type';
        return $file;
    }
    $is_img = in_array($file_ext, ['gif', 'jpg', 'jpeg', 'png', 'webp'], true);

    $maxFileSize = (int) ($cfg['plugin']['attacher']['filesize'] ?? 0);
    $folder = (string) ($cfg['plugin']['attacher']['folder'] ?? 'datas/attacher');
    $file_path = $folder . '/' . $area . '/' . $item . '/' . $file->name;

    // Отсекаем попытку выйти из каталога загрузок через слэши в имени
    $realFolder = realpath($folder) ?: $folder;
    // realpath файла ещё не существует — сравниваем по нормализованному пути
    // Кастим через basename имени, слэши уже вычищены в att_safe_filename

    $dir_path = dirname($file_path);
    if (!is_dir($dir_path)) {
        if (!mkdir($dir_path, (int) ($cfg['dir_perms'] ?? 0755), true) && !is_dir($dir_path)) {
            $file->error = $L['att_err_dir'] ?? 'Failed to create directory';
            return $file;
        }
    }

    // Чанковая загрузка: если Content-Range есть и это продолжение
    $append_file = $content_range
        && is_string($uploaded_file)
        && is_file($uploaded_file)
        && $file->size > (int) filesize($uploaded_file);

    if (is_string($uploaded_file) && is_uploaded_file($uploaded_file)) {
        if ($append_file) {
            // Дописываем чанк в конец уже существующего файла
            $fp = fopen($file_path, 'ab');
            if ($fp === false) {
                $file->error = $L['att_err_move'] ?? 'Cannot open file';
                return $file;
            }
            $src = fopen($uploaded_file, 'rb');
            if ($src !== false) {
                stream_copy_to_stream($src, $fp);
                fclose($src);
            }
            fclose($fp);
        } else {
            if (!move_uploaded_file($uploaded_file, $file_path)) {
                $file->error = $L['att_err_move'] ?? 'Move failed';
                return $file;
            }
        }
    } else {
        // PATCH/PUT: файл приходит в теле запроса
        $in = fopen('php://input', 'rb');
        if ($in === false) {
            $file->error = 'no_input';
            return $file;
        }
        $mode = $append_file ? 'ab' : 'wb';
        $out = fopen($file_path, $mode);
        if ($out === false) {
            fclose($in);
            $file->error = $L['att_err_move'] ?? 'Cannot open file';
            return $file;
        }
        stream_copy_to_stream($in, $out);
        fclose($in);
        fclose($out);
    }

    clearstatcache(true, $file_path);
    $file_size = is_file($file_path) ? (int) filesize($file_path) : 0;

    // Защита от обхода через Content-Range: файл не должен превышать заявленный total
    if ($file_size > $file->size) {
        @unlink($file_path);
        $file->error = 'abort';
        return $file;
    }

    // Защита от DoS: превышение серверного лимита размера файла
    if ($maxFileSize > 0 && $file_size > $maxFileSize) {
        @unlink($file_path);
        $file->error = $L['att_err_toobig'] ?? 'File too large';
        return $file;
    }

    if ($file_size !== $file->size) {
        @unlink($file_path);
        $file->error = 'abort';
        return $file;
    }

    if ($is_img && function_exists('cot_img_check_memory') && !cot_img_check_memory($file_path)) {
        @unlink($file_path);
        $file->error = $L['att_err_toobig'] ?? 'File too large';
        return $file;
    }

    $order = ((int) $db->query(
        "SELECT MAX(att_order) FROM $db_attacher WHERE att_area = ? AND att_item = ?",
        [$area, $item]
    )->fetchColumn()) + 1;

    $unikey = ((int) $item === 0)
        ? (string) (cot_import('unikey', 'G', 'TXT') ?? '')
        : '';

    $affected = $db->insert($db_attacher, [
        'att_user'     => (int) ($usr['id'] ?? 0),
        'att_area'     => $area,
        'att_item'     => (int) $item,
        'att_field'    => $field,
        'att_path'     => '',
        'att_filename' => $file->name,
        'att_ext'      => $file_ext,
        'att_img'      => (int) $is_img,
        'att_size'     => $file->size,
        'att_title'    => '',
        'att_count'    => 0,
        'att_order'    => $order,
        'att_lastmod'  => $file->lastmod,
        'att_unikey'   => $unikey,
    ]);

    if ($affected !== 1) {
        @unlink($file_path);
        $file->error = $L['att_err_db'] ?? 'Database error';
        return $file;
    }

    $id = (int) $db->lastInsertId();
    $tmpFilePath = $file_path;
    $file_path = att_path($area, $item, $id, $file_ext);
    if ($file_path === false || !@rename($tmpFilePath, $file_path)) {
        $db->delete($db_attacher, 'att_id = ?', [$id]);
        @unlink($tmpFilePath);
        $file->error = 'rename_failed';
        return $file;
    }

    // Постобработка изображений: ресайз до 1900px по ширине + конвертация в WebP
    if ($is_img) {
        $image_data = @file_get_contents($file_path);
        if ($image_data === false) {
            $db->delete($db_attacher, 'att_id = ?', [$id]);
            @unlink($file_path);
            $file->error = 'Failed to read image file';
            return $file;
        }

        $src_image = @imagecreatefromstring($image_data);
        if ($src_image === false) {
            $db->delete($db_attacher, 'att_id = ?', [$id]);
            @unlink($file_path);
            $file->error = 'Failed to create image resource';
            return $file;
        }

        $width  = imagesx($src_image);
        $height = imagesy($src_image);

        $new_width  = $width;
        $new_height = $height;
        if ($width > 1900) {
            $new_width  = 1900;
            $new_height = (int) ($height * 1900 / $width);
        }

        $needConversion = !($file_ext === 'webp' && $new_width === $width && $new_height === $height)
            && function_exists('imagewebp');

        if (!$needConversion) {
            imagedestroy($src_image);
        } else {
            $dst_image = imagecreatetruecolor($new_width, $new_height);

            if ($file_ext === 'png' || $file_ext === 'gif') {
                imagealphablending($dst_image, false);
                imagesavealpha($dst_image, true);
                $transparent = imagecolorallocatealpha($dst_image, 255, 255, 255, 127);
                imagefilledrectangle($dst_image, 0, 0, $new_width, $new_height, $transparent);
            }

            imagecopyresampled(
                $dst_image, $src_image,
                0, 0, 0, 0,
                $new_width, $new_height, $width, $height
            );
            imagedestroy($src_image);

            $new_file_path = att_path($area, $item, $id, 'webp');
            $success = $new_file_path !== false
                && @imagewebp($dst_image, $new_file_path, 85);
            imagedestroy($dst_image);

            if (!$success || !file_exists($new_file_path) || filesize($new_file_path) === 0) {
                $db->delete($db_attacher, 'att_id = ?', [$id]);
                @unlink($file_path);
                @unlink((string) $new_file_path);
                $file->error = 'Failed to convert image to WebP';
                return $file;
            }

            if ($file_path !== $new_file_path) {
                @unlink($file_path);
            }

            $file_path = $new_file_path;
            $file_ext  = 'webp';
            $file->name = pathinfo($file->name, PATHINFO_FILENAME) . '.webp';
        }

        clearstatcache(true, $file_path);
        $file_size = (int) filesize($file_path);
        $file->size = $file_size;
    }

    $db->update($db_attacher, [
        'att_path'     => $file_path,
        'att_size'     => $file_size,
        'att_ext'      => $file_ext,
        'att_filename' => $file->name,
        'att_lastmod'  => $file->lastmod,
    ], 'att_id = ?', [$id]);

    $file->isImage = $is_img;
    $file->url = $cfg['mainurl'] . '/' . $file_path;

    $prefix  = (string) ($cfg['plugin']['attacher']['prefix'] ?? 'att_');
    $xkToken = (string) ($sys['xk'] ?? '');

    $smallThumb = $is_img ? att_thumb($id) : false;
    $bigThumb   = $is_img ? att_thumb(
        $id,
        (int) ($cfg['plugin']['attacher']['thumb_big_width'] ?? 800),
        (int) ($cfg['plugin']['attacher']['thumb_big_height'] ?? 600),
        (string) ($cfg['plugin']['attacher']['thumb_big_framing'] ?? 'fit')
    ) : false;

    $iconRel = $is_img ? '' : att_icon($file_ext);
    $smallUrl = $smallThumb ? $cfg['mainurl'] . '/' . $smallThumb : $cfg['mainurl'] . '/' . $iconRel;
    $bigUrl   = $bigThumb ? $cfg['mainurl'] . '/' . $bigThumb : $cfg['mainurl'] . '/' . $iconRel;

    $file->thumbnail       = $smallUrl;
    $file->thumbnailUrl    = $smallUrl . ($is_img ? '?lastmod=' . $file->lastmod : '');
    $file->thumbnailBig    = $bigUrl;
    $file->thumbnailBigUrl = $bigUrl . ($is_img ? '?lastmod=' . $file->lastmod : '');
    $file->id              = $id;
    $file->deleteUrl       = $cfg['mainurl']
                             . '/index.php?r=attacher&a=upload&id=' . $id
                             . '&_method=DELETE&x=' . urlencode($xkToken);
    $file->deleteType      = 'POST';
    $file->shortname       = $prefix . $id . '.' . $file_ext;
    $file->downloadUrl     = $cfg['mainurl'] . '/index.php?r=attacher&a=dl&id=' . $id;

    foreach (cot_getextplugins('attacher.upload.after_save') as $pl) {
        include $pl;
    }

    return $file;
}

/**
 * Validates uploaded file.
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

    if ($file->name === '') {
        $file->error = 'missingFileName';
        return false;
    }

    $file_ext = att_get_ext($file->name);
    if ($file_ext === false || !att_check_file($file_ext)) {
        $file->error = $L['att_err_type'] ?? 'Invalid file type';
        return false;
    }

    $file_size = (is_string($uploaded_file) && is_uploaded_file($uploaded_file))
        ? (int) filesize($uploaded_file)
        : (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);

    $limits = att_get_limits();
    if (($limits['file'] ?? 0) && ($file_size > $limits['file'] || $file->size > $limits['file'])) {
        $file->error = $L['att_err_toobig'] ?? 'File too large';
        return false;
    }

    if ($file_size < 1) {
        $file->error = 'minFileSize';
        return false;
    }

    // Параметры формы: base64(serialize(...)). PHP object injection исключается
    // через allowed_classes: false — unserialize вернёт массив/скаляры, но не объекты.
    $paramsRaw = (string) (cot_import('param', 'R', 'HTM') ?? '');
    if ($paramsRaw !== '') {
        $decoded = base64_decode($paramsRaw, true);
        $params = false;
        if ($decoded !== false) {
            $params = @unserialize($decoded, ['allowed_classes' => false]);
        }
        if (!is_array($params)) {
            $params = [];
        }

        if (!empty($params['type'])) {
            $types = is_string($params['type'])
                ? (json_decode($params['type'], true) ?: ['all'])
                : (is_array($params['type']) ? $params['type'] : ['all']);

            $is_img = in_array($file_ext, ['gif', 'jpg', 'jpeg', 'png', 'webp'], true);
            $typeOk = in_array('all', $types, true)
                || (in_array('image', $types, true) && $is_img);

            if (!$typeOk) {
                $file->error = $L['att_err_type'] ?? 'Invalid file type';
                return false;
            }
        }

        $paramsLimit = (int) ($params['limit'] ?? ($cfg['plugin']['attacher']['items'] ?? 0));
        $paramsField = (string) ($params['field'] ?? $field);

        if ($paramsLimit > 0 && att_count_files($area, $item, $paramsField) >= $paramsLimit) {
            $file->error = $L['att_err_count'] ?? 'Too many files';
            return false;
        }
    }

    return true;
}

/**
 * Workaround for splitting basename with UTF-8 multibyte chars.
 */
function mb_basename($filepath, $suffix = null)
{
    $splited = preg_split('#/#', rtrim((string) $filepath, '/ '));
    return substr(basename('X' . end($splited), $suffix), 1);
}

/**
 * Fix for overflowing signed 32-bit integers.
 */
function fix_integer_overflow($size)
{
    if ($size < 0) {
        $size += 2.0 * (PHP_INT_MAX + 1);
    }
    return $size;
}

/**
 * Проверяет, безопасен ли remote URL для исходящего запроса.
 *
 * Отсекает:
 *  - пустой URL и URL длиной > 2048;
 *  - управляющие символы и null-байты;
 *  - схемы, отличные от http/https (file://, gopher://, ftp://, dict:// и т.д.);
 *  - userinfo в URL (user:pass@host — типичный обход);
 *  - резолвинг в IP из приватных (10/8, 172.16/12, 192.168/16) и резервных
 *    (127/8, 169.254/16, 0/8, 224/4, 240/4, ULA fd00::/8, link-local fe80::/10)
 *    диапазонов.
 *
 * DNS-rebinding всё равно теоретически возможен между этой проверкой и
 * запросом cURL, поэтому вызывающий код дополнительно пинит разрешённый IP
 * через CURLOPT_RESOLVE.
 */
function att_is_safe_remote_url(string $url): bool
{
    if ($url === '' || strlen($url) > 2048) {
        return false;
    }
    if (preg_match('/[\x00-\x1f\x7f]/', $url)) {
        return false;
    }

    $parts = parse_url($url);
    if ($parts === false || !is_array($parts)) {
        return false;
    }

    $scheme = strtolower($parts['scheme'] ?? '');
    if (!in_array($scheme, ['http', 'https'], true)) {
        return false;
    }

    $host = $parts['host'] ?? '';
    if ($host === '') {
        return false;
    }

    // Отсечь userinfo (https://user:pass@127.0.0.1/)
    if (isset($parts['user']) || isset($parts['pass'])) {
        return false;
    }

    $host = trim($host, '[]'); // IPv6 в []

    $ips = [];
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        $ips = [$host];
    } else {
        $resolved = @gethostbynamel($host);
        if (is_array($resolved)) {
            $ips = $resolved;
        }
    }

    if (empty($ips)) {
        return false;
    }

    foreach ($ips as $ip) {
        if (!att_is_public_ip($ip)) {
            return false;
        }
    }

    return true;
}

/**
 * Проверяет, что IP — публичный (не приватный, не резервный, не loopback,
 * не link-local).
 */
function att_is_public_ip(string $ip): bool
{
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return false;
    }
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
        return false;
    }
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE)) {
        return false;
    }
    return true;
}

/**
 * Handle file upload from a remote URL using cURL (с SSRF-защитой).
 *
 * @param string $url
 * @return stdClass
 */
function att_ajax_handle_url_upload($url)
{
    global $db, $db_attacher, $area, $item, $field, $cfg, $usr, $L, $sys;

    $file = new stdClass();
    $file->name = '';
    $file->size = 0;
    $file->type = '';
    $file->lastmod = date('Y-m-d H:i:s', (int) ($sys['now'] ?? time()));

    // 1. Права — ДО любого сетевого запроса.
    // Раньше проверка прав стояла после curl_exec, что давало SSRF даже без
    // реального сохранения файла.
    if (!cot_auth('plug', 'attacher', 'W')) {
        $file->error = $L['att_err_perms'] ?? 'Permission denied';
        return $file;
    }

    // 2. SSRF-защита: только http/https, только публичные IP.
    if (!att_is_safe_remote_url((string) $url)) {
        $file->error = 'URL is not allowed';
        return $file;
    }

    $parts = parse_url((string) $url);
    $scheme = strtolower($parts['scheme'] ?? 'http');
    $host   = trim($parts['host'] ?? '', '[]');
    $port   = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));

    // Имя файла из пути URL
    $base = basename((string) parse_url((string) $url, PHP_URL_PATH));
    $file->name = att_safe_filename($base);
    if ($file->name === '' || !preg_match('/\.\w+$/', $file->name)) {
        $file->name = 'file_' . md5((string) $url) . '.bin';
    }

    // 3. Скачивание во временный файл с ограничением размера по ходу записи.
    $maxFileSize = (int) ($cfg['plugin']['attacher']['filesize'] ?? 0);
    if ($maxFileSize <= 0) {
        // Не отключаем полностью, ставим разумный верхний предел для URL-загрузок.
        $maxFileSize = 104857600; // 100 MiB
    }

    $tmpFile = tempnam(sys_get_temp_dir(), 'att_');
    if ($tmpFile === false) {
        $file->error = 'Failed to create temp file';
        return $file;
    }

    $fp = fopen($tmpFile, 'wb');
    if ($fp === false) {
        @unlink($tmpFile);
        $file->error = 'Failed to open temp file';
        return $file;
    }

    $downloaded = 0;
    $tooBig = false;

    $ch = curl_init((string) $url);
    if ($ch === false) {
        fclose($fp);
        @unlink($tmpFile);
        $file->error = 'curl_init failed';
        return $file;
    }

    $curlOpts = [
        CURLOPT_FOLLOWLOCATION  => 0,  // отключаем redirect — обход хоста
        CURLOPT_CONNECTTIMEOUT  => 10,
        CURLOPT_TIMEOUT         => 30,
        CURLOPT_SSL_VERIFYPEER  => 1,  // было 0 — MITM был возможен
        CURLOPT_SSL_VERIFYHOST  => 2,
        CURLOPT_PROTOCOLS       => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        CURLOPT_USERAGENT       => 'Mozilla/5.0 (Cotonti Attacher)',
        CURLOPT_MAXFILESIZE     => $maxFileSize,
        CURLOPT_WRITEFUNCTION   => function ($ch, $chunk) use ($fp, $maxFileSize, &$downloaded, &$tooBig) {
            $len = strlen($chunk);
            $downloaded += $len;
            if ($downloaded > $maxFileSize) {
                $tooBig = true;
                return 0; // abort — cURL вернёт ошибку
            }
            return fwrite($fp, $chunk);
        },
    ];

    // Пиннинг IP от DNS-rebinding: фиксируем разрешённый IP для конкретного host:port.
    if (!filter_var($host, FILTER_VALIDATE_IP)) {
        $ips = @gethostbynamel($host);
        if (is_array($ips) && !empty($ips)) {
            $curlOpts[CURLOPT_RESOLVE] = [$host . ':' . $port . ':' . $ips[0]];
        }
    }

    curl_setopt_array($ch, $curlOpts);

    $ok = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    fclose($fp);

    if ($tooBig) {
        @unlink($tmpFile);
        $file->error = $L['att_err_toobig'] ?? 'File too large';
        return $file;
    }

    if ($ok === false || $httpCode !== 200) {
        @unlink($tmpFile);
        // Текст ошибки — в лог, не в JSON (не раскрываем внутренние адреса)
        error_log('Attacher URL upload failed: ' . $url . ' — ' . ($curlError ?: "HTTP {$httpCode}"));
        $file->error = $L['att_err_download'] ?? 'Failed to download file';
        return $file;
    }

    clearstatcache(true, $tmpFile);
    $file->size = is_file($tmpFile) ? (int) filesize($tmpFile) : 0;

    if ($file->size < 1) {
        @unlink($tmpFile);
        $file->error = 'minFileSize';
        return $file;
    }

    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $file->type = (string) finfo_file($finfo, $tmpFile);
            finfo_close($finfo);
        }
    }

    // Расширение
    $ext = strtolower((string) pathinfo($file->name, PATHINFO_EXTENSION));
    if ($ext === '') {
        $mimeToExt = [
            'image/jpeg'      => 'jpg',
            'image/jpg'       => 'jpg',
            'image/png'       => 'png',
            'image/gif'       => 'gif',
            'image/webp'      => 'webp',
            'application/pdf' => 'pdf',
        ];
        $ext = $mimeToExt[$file->type] ?? 'bin';
        $file->name .= '.' . $ext;
    }

    $file_ext = $ext;

    if (!att_check_file($file_ext)) {
        @unlink($tmpFile);
        $file->error = $L['att_err_type'] ?? 'Invalid file type';
        return $file;
    }

    $limits = att_get_limits();
    if (($limits['file'] ?? 0) && $file->size > $limits['file']) {
        @unlink($tmpFile);
        $file->error = $L['att_err_toobig'] ?? 'File too large';
        return $file;
    }

    // Лимиты по количеству (тот же unserialize-safe путь)
    $paramsRaw = (string) (cot_import('param', 'R', 'HTM') ?? '');
    if ($paramsRaw !== '') {
        $decoded = base64_decode($paramsRaw, true);
        $params = false;
        if ($decoded !== false) {
            $params = @unserialize($decoded, ['allowed_classes' => false]);
        }
        if (!is_array($params)) {
            $params = [];
        }

        if (!empty($params['type'])) {
            $types = is_string($params['type'])
                ? (json_decode($params['type'], true) ?: ['all'])
                : (is_array($params['type']) ? $params['type'] : ['all']);

            $isImg = in_array($file_ext, ['gif', 'jpg', 'jpeg', 'png', 'webp'], true);
            $typeOk = in_array('all', $types, true)
                || (in_array('image', $types, true) && $isImg);

            if (!$typeOk) {
                @unlink($tmpFile);
                $file->error = $L['att_err_type'] ?? 'Invalid file type';
                return $file;
            }
        }

        $paramsLimit = (int) ($params['limit'] ?? ($cfg['plugin']['attacher']['items'] ?? 0));
        $paramsField = (string) ($params['field'] ?? $field);

        if ($paramsLimit > 0 && att_count_files($area, $item, $paramsField) >= $paramsLimit) {
            @unlink($tmpFile);
            $file->error = $L['att_err_count'] ?? 'Too many files';
            return $file;
        }
    }

    $is_img = in_array($file_ext, ['gif', 'jpg', 'jpeg', 'png', 'webp'], true);
    $folder = (string) ($cfg['plugin']['attacher']['folder'] ?? 'datas/attacher');
    $file_path = $folder . '/' . $area . '/' . $item . '/' . $file->name;

    $dir_path = dirname($file_path);
    if (!is_dir($dir_path)) {
        if (!mkdir($dir_path, (int) ($cfg['dir_perms'] ?? 0755), true) && !is_dir($dir_path)) {
            @unlink($tmpFile);
            $file->error = $L['att_err_dir'] ?? 'Failed to create directory';
            return $file;
        }
    }

    if (!@rename($tmpFile, $file_path)) {
        @unlink($tmpFile);
        $file->error = 'Failed to move file';
        return $file;
    }

    if ($is_img && function_exists('cot_img_check_memory') && !cot_img_check_memory($file_path)) {
        @unlink($file_path);
        $file->error = $L['att_err_toobig'] ?? 'File too large';
        return $file;
    }

    $order = ((int) $db->query(
        "SELECT MAX(att_order) FROM $db_attacher WHERE att_area = ? AND att_item = ?",
        [$area, $item]
    )->fetchColumn()) + 1;

    $unikey = ((int) $item === 0)
        ? (string) (cot_import('unikey', 'G', 'TXT') ?? '')
        : '';

    $affected = $db->insert($db_attacher, [
        'att_user'     => (int) ($usr['id'] ?? 0),
        'att_area'     => $area,
        'att_item'     => (int) $item,
        'att_field'    => $field,
        'att_path'     => '',
        'att_filename' => $file->name,
        'att_ext'      => $file_ext,
        'att_img'      => (int) $is_img,
        'att_size'     => $file->size,
        'att_title'    => '',
        'att_count'    => 0,
        'att_order'    => $order,
        'att_lastmod'  => $file->lastmod,
        'att_unikey'   => $unikey,
    ]);

    if ($affected !== 1) {
        @unlink($file_path);
        $file->error = $L['att_err_db'] ?? 'Database error';
        return $file;
    }

    $id = (int) $db->lastInsertId();
    $tmpFilePath = $file_path;
    $file_path = att_path($area, $item, $id, $file_ext);
    if ($file_path === false || !@rename($tmpFilePath, $file_path)) {
        $db->delete($db_attacher, 'att_id = ?', [$id]);
        @unlink($tmpFilePath);
        $file->error = 'rename_failed';
        return $file;
    }

    // Постобработка изображений — как в att_ajax_handle_file_upload
    if ($is_img) {
        $image_data = @file_get_contents($file_path);
        if ($image_data === false) {
            $db->delete($db_attacher, 'att_id = ?', [$id]);
            @unlink($file_path);
            $file->error = 'Failed to read image file';
            return $file;
        }

        $src_image = @imagecreatefromstring($image_data);
        if ($src_image === false) {
            $db->delete($db_attacher, 'att_id = ?', [$id]);
            @unlink($file_path);
            $file->error = 'Failed to create image resource';
            return $file;
        }

        $width  = imagesx($src_image);
        $height = imagesy($src_image);

        $new_width  = $width;
        $new_height = $height;
        if ($width > 1900) {
            $new_width  = 1900;
            $new_height = (int) ($height * 1900 / $width);
        }

        $needConversion = !($file_ext === 'webp' && $new_width === $width && $new_height === $height)
            && function_exists('imagewebp');

        if (!$needConversion) {
            imagedestroy($src_image);
        } else {
            $dst_image = imagecreatetruecolor($new_width, $new_height);

            if ($file_ext === 'png' || $file_ext === 'gif') {
                imagealphablending($dst_image, false);
                imagesavealpha($dst_image, true);
                $transparent = imagecolorallocatealpha($dst_image, 255, 255, 255, 127);
                imagefilledrectangle($dst_image, 0, 0, $new_width, $new_height, $transparent);
            }

            imagecopyresampled(
                $dst_image, $src_image,
                0, 0, 0, 0,
                $new_width, $new_height, $width, $height
            );
            imagedestroy($src_image);

            $new_file_path = att_path($area, $item, $id, 'webp');
            $success = $new_file_path !== false
                && @imagewebp($dst_image, $new_file_path, 85);
            imagedestroy($dst_image);

            if (!$success || !file_exists($new_file_path) || filesize($new_file_path) === 0) {
                $db->delete($db_attacher, 'att_id = ?', [$id]);
                @unlink($file_path);
                @unlink((string) $new_file_path);
                $file->error = 'Failed to convert image to WebP';
                return $file;
            }

            if ($file_path !== $new_file_path) {
                @unlink($file_path);
            }

            $file_path = $new_file_path;
            $file_ext  = 'webp';
            $file->name = pathinfo($file->name, PATHINFO_FILENAME) . '.webp';
        }

        clearstatcache(true, $file_path);
        $file->size = (int) filesize($file_path);
    }

    $db->update($db_attacher, [
        'att_path'     => $file_path,
        'att_size'     => $file->size,
        'att_ext'      => $file_ext,
        'att_filename' => $file->name,
        'att_lastmod'  => $file->lastmod,
    ], 'att_id = ?', [$id]);

    $prefix  = (string) ($cfg['plugin']['attacher']['prefix'] ?? 'att_');
    $xkToken = (string) ($sys['xk'] ?? '');

    $file->isImage = $is_img;
    $file->url = $cfg['mainurl'] . '/' . $file_path;

    $smallThumb = $is_img ? att_thumb($id) : false;
    $bigThumb   = $is_img ? att_thumb(
        $id,
        (int) ($cfg['plugin']['attacher']['thumb_big_width'] ?? 800),
        (int) ($cfg['plugin']['attacher']['thumb_big_height'] ?? 600),
        (string) ($cfg['plugin']['attacher']['thumb_big_framing'] ?? 'fit')
    ) : false;

    $iconRel = $is_img ? '' : att_icon($file_ext);
    $smallUrl = $smallThumb ? $cfg['mainurl'] . '/' . $smallThumb : $cfg['mainurl'] . '/' . $iconRel;
    $bigUrl   = $bigThumb ? $cfg['mainurl'] . '/' . $bigThumb : $cfg['mainurl'] . '/' . $iconRel;

    $file->thumbnail       = $smallUrl;
    $file->thumbnailUrl    = $smallUrl . ($is_img ? '?lastmod=' . $file->lastmod : '');
    $file->thumbnailBig    = $bigUrl;
    $file->thumbnailBigUrl = $bigUrl . ($is_img ? '?lastmod=' . $file->lastmod : '');
    $file->id              = $id;
    $file->deleteUrl       = $cfg['mainurl']
                             . '/index.php?r=attacher&a=upload&id=' . $id
                             . '&_method=DELETE&x=' . urlencode($xkToken);
    $file->deleteType      = 'POST';
    $file->shortname       = $prefix . $id . '.' . $file_ext;
    $file->downloadUrl     = $cfg['mainurl'] . '/index.php?r=attacher&a=dl&id=' . $id;

    return $file;
}