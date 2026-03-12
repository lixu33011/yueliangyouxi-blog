<?php
/**
 * 图片上传接口
 * 适配Simditor编辑器的上传需求
 */
header('Content-Type: application/json');

// 允许的文件类型
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
// 最大文件大小（5MB）
$maxSize = 5 * 1024 * 1024;

// 检查是否有文件上传
if (!isset($_FILES['upload_file'])) {
    exit(json_encode(['success' => false, 'msg' => '请选择要上传的文件']));
}

$file = $_FILES['upload_file'];

// 检查上传错误
if ($file['error'] !== UPLOAD_ERR_OK) {
    exit(json_encode(['success' => false, 'msg' => '文件上传失败：' . $file['error']]));
}

// 检查文件大小
if ($file['size'] > $maxSize) {
    exit(json_encode(['success' => false, 'msg' => '文件大小不能超过5MB']));
}

// 检查文件类型
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
if (!in_array($mime, $allowedTypes)) {
    exit(json_encode(['success' => false, 'msg' => '仅支持上传JPG、PNG、GIF、WEBP格式的图片']));
}

// 创建上传目录
$uploadDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// 生成唯一文件名
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'article_' . date('YmdHis') . '_' . uniqid() . '.' . $ext;
$filepath = $uploadDir . $filename;

// 移动上传文件
if (move_uploaded_file($file['tmp_name'], $filepath)) {
    // 返回Simditor需要的格式
    exit(json_encode([
        'success' => true,
        'msg' => '上传成功',
        'file_path' => '/uploads/' . $filename // 前台可访问的路径
    ]));
} else {
    exit(json_encode(['success' => false, 'msg' => '文件保存失败']));
}
