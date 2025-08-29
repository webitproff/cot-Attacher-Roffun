<?php
/**
 * Attacher plugin: English translation
 *
 * @package Attacher
 * @author Roffun
 * @copyright Copyright (c) Roffun, Webitproff 2018 - 2025 | https://github.com/webitproff
 * @license BSD License
 */

defined('COT_CODE') or die('Wrong URL');

$L['info_name'] = 'Attacher';
$L['info_desc'] = 'Image and file upload. File attachment, generation of various sizes, and on-the-fly image processing in articles, with insertion into the visual editor';

/**
 * CONFIGURATION
 */
$L['cfg_folder'] = 'Directory for files';
$L['cfg_prefix'] = 'File name prefix';
$L['cfg_exts'] = 'Allowed file types (comma-separated, no dots or spaces)';
$L['cfg_items'] = 'Max number of attachments per post';
$L['cfg_accept'] = ['Allowed MIME types in file selection dialog, comma-separated.', 'Empty value allows all types. Special types like image/*, audio/*, video/* can be used'];
$L['cfg_filesize'] = 'Max file size in bytes';
$L['cfg_chunkSize'] = ['Upload files in chunks of (bytes)', 'Large files can be uploaded in smaller parts. This allows uploading files larger than the $_POST upload limits. (Leave empty to disable)'];
$L['cfg_filespace'] = 'Total disk space per user';
$L['cfg_autoupload'] = 'Start uploading automatically';
$L['cfg_sequential'] = 'Sequential uploading instead of concurrent';
$L['cfg_css'] = 'Include styles';

/**
 * IMAGE UPLOAD SETTINGS
 */
$L['cfg_sep_orig'] = 'IMAGE UPLOAD SETTINGS';
$L['cfg_img_resize'] = ['Resize uploaded images', 'Uploaded images will be proportionally resized according to the following parameters'];
$L['cfg_img_maxwidht'] = 'Reduce image width to';
$L['cfg_img_maxheight'] = 'Reduce image height to';
$L['cfg_imageconvert'] = 'Convert all images to JPG on upload';
$L['cfg_quality'] = 'JPEG quality in %';

/**
 * THUMBNAIL CREATION SETTINGS
 */
$L['cfg_sep_thumbs'] = 'THUMBNAIL CREATION SETTINGS';
$L['cfg_thumbs'] = 'Show image thumbnails?';
$L['cfg_upscale'] = 'Upscale images smaller than thumbnail size';
$L['cfg_thumb_x'] = 'Default thumbnail width';
$L['cfg_thumb_y'] = 'Default thumbnail height';
$L['cfg_thumb_framing'] = 'Default thumbnail framing mode';
$L['cfg_thumb_framing_params'] = [
    'height' => 'By height',
    'width' => 'By width',
    'auto' => 'Auto',
    'crop' => 'Crop'
];
$L['cfg_thumb_big_width'] = 'Large thumbnail width';
$L['cfg_thumb_big_height'] = 'Large thumbnail height';
$L['cfg_thumb_big_framing'] = 'Large thumbnail framing mode';

/**
 * WATERMARK AND BACKGROUND TEXTURE
 */
$L['cfg_sep_wmark_bg'] = 'WATERMARK AND BACKGROUND TEXTURE';
$L['cfg_thumb_watermark'] = ['Watermark for thumbnails', 'Path to the watermark file. PNG with transparency is supported. (Leave empty to disable watermarks)'];
$L['cfg_thumb_wm_x'] = ['Watermark. Min. width', 'Watermark will be applied to thumbnails only if their width exceeds the specified value'];
$L['cfg_thumb_wm_y'] = ['Watermark. Min. height', 'Watermark will be applied to thumbnails only if their height exceeds the specified value'];
$L['cfg_bg_image'] = 'Path to the background texture file';
$L['cfg_bg_space'] = 'Padding size for texture fill';
$L['cfg_bg_space_hint'] = '0 to disable';

/**
 * ON-THE-FLY PROCESSING IN ARTICLES
 */
$L['cfg_sep_replacement'] = 'ON-THE-FLY PROCESSING IN ARTICLES';
$L['cfg_thumb_clickable'] = 'How to display images in articles';
$L['cfg_thumb_clickable_params'] = ['Default', 'All as links', 'All as images'];
$L['cfg_thumb_alt_to_title'] = ['Create title for links from alt content', '<i>works only when link display mode is enabled</i>'];
$L['cfg_thumb_strip_style'] = 'Remove style attribute from images';
$L['cfg_thumb_to_picture'] = 'Convert all images to adaptive PICTURE';
$L['cfg_thumb_wrapper'] = 'Create container (span)';
$L['cfg_thumb_wrapper_class'] = 'Class names for the span container';
$L['att_add_pict_files'] = 'Images and files';
$L['att_add'] = 'Add files';
$L['att_attach'] = 'Attach files';
$L['att_attachment'] = 'Attached file';
$L['att_attachments'] = 'Attachments';
$L['att_cancel'] = 'Cancel upload';
$L['att_cleanup'] = 'Cleanup';
$L['att_allthumbs_remove'] = 'Remove all thumbnails';
$L['att_cleanup_confirm'] = 'This will remove all files attached to non-existent posts. Continue?';
$L['att_allthumbs_remove_confirm'] = 'This will remove all created image thumbnails; they will be regenerated from the original on demand. Continue?';
$L['att_delete'] = 'Delete';
$L['att_downloads'] = 'Downloads';
$L['att_ensure'] = 'Are you sure?';
$L['att_free'] = 'free';
$L['att_filename'] = 'File name';
$L['att_gallery'] = 'Gallery';
$L['att_processing'] = 'Processing';
$L['att_info'] = 'Information';
$L['att_item'] = 'Item';
$L['att_items_removed'] = 'Items removed';
$L['att_thumbs_removed'] = 'Thumbnails removed';
$L['att_kb'] = 'KB';
$L['att_kb_left_of'] = 'KB left, files up to';
$L['att_maxsize'] = 'max file size';
$L['att_of'] = 'of';
$L['att_remove_all'] = 'Remove all';
$L['att_replace'] = 'Replace';
$L['att_show_info'] = 'Show item information';
$L['att_size'] = 'Size';
$L['att_slideshow'] = 'Slideshow';
$L['att_start'] = 'Start';
$L['att_start_upload'] = 'Start upload';
$L['att_title'] = 'Title';
$L['att_title_here'] = 'Place title here';
$L['att_total'] = 'total';
$L['att_type'] = 'Type';
$L['att_used'] = 'used';
$L['att_user'] = 'User';
$L['att_your_space'] = 'Your space';
$L['att_drag_here'] = 'Drag files here';
$L['att_check'] = 'Check';
$L['att_check_all'] = 'Check all';

// Errors
$L['att_err_db'] = 'Database error';
$L['att_err_delete'] = 'Unable to delete attachment';
$L['att_err_move'] = 'Unable to move uploaded file';
$L['att_err_noitems'] = 'No items found';
$L['att_err_nospace'] = 'Insufficient personal disk space';
$L['att_err_perms'] = 'You lack permissions for this action';
$L['att_err_replace'] = 'Unable to replace file';
$L['att_err_thumb'] = 'Unable to create image preview';
$L['att_err_title'] = 'File title is empty';
$L['att_err_toobig'] = 'File is too large';
$L['att_err_type'] = 'This file type is not allowed';
$L['att_err_upload'] = 'Failed to upload file';
$L['att_err_count'] = 'Maximum number of files exceeded';

$L['att_button_small_title'] = (cot::$cfg['plugin']['attacher']['thumb_x'] ?? 100) . '*' . (cot::$cfg['plugin']['attacher']['thumb_y'] ?? 100);
$L['att_button_big_title'] = (cot::$cfg['plugin']['attacher']['thumb_big_width'] ?? 800) . '*' . (cot::$cfg['plugin']['attacher']['thumb_big_height'] ?? 600);
$L['visual_only'] = 'Insertion is available in visual mode, exit code mode!';

$L['att_url_placeholder'] = 'Enter image URL';
$L['att_url_empty'] = 'Please enter an image URL';
$L['att_upload_url'] = 'Upload by URL';
$L['att_url_error'] = 'Failed to load image from URL';