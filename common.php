<?php
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}
// 确保目录存在
$baseDir = __DIR__; // 获取当前文件所在目录（绝对路径）
$dirs = [
    $baseDir . '/data', 
    $baseDir . '/uploads', 
    $baseDir . '/backgrounds', 
    $baseDir . '/templates/home', 
    $baseDir . '/templates/article-list', 
    $baseDir . '/templates/article-detail'
];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

// JSON文件操作函数（增强：绝对路径+强制初始化）
function readJsonFile($filename) {
    $filePath = __DIR__ . '/data/' . $filename;
    // 强制初始化默认数据（确保文件存在且有内容）
    if (!file_exists($filePath) || filesize($filePath) == 0) {
        $default = [];
        switch ($filename) {
            case 'templates.json':
                $default = [
                    "home_templates" => [
                        ["id"=>1,"name"=>"基础二次元（视频居中）","file"=>"templates/home/home1.css","preview"=>"uploads/home1_preview.jpg","desc"=>"视频居中展示，图片网格布局"],
                        ["id"=>2,"name"=>"暗黑萌系（视频左侧）","file"=>"templates/home/home2.css","preview"=>"uploads/home2_preview.jpg","desc"=>"视频左侧悬浮，图片右侧瀑布流"],
                        ["id"=>3,"name"=>"炫彩霓虹（视频全屏）","file"=>"templates/home/home3.css","preview"=>"uploads/home3_preview.jpg","desc"=>"视频全屏背景，图片悬浮卡片"]
                    ],
                    "article_list_templates" => [
                        ["id"=>1,"name"=>"列表式文章列表","file"=>"templates/article-list/list1.css","preview"=>"uploads/list1_preview.jpg","desc"=>"传统列表布局"],
                        ["id"=>2,"name"=>"卡片式文章列表","file"=>"templates/article-list/list2.css","preview"=>"uploads/list2_preview.jpg","desc"=>"卡片布局，缩略图+标题"],
                        ["id"=>3,"name"=>"瀑布流文章列表","file"=>"templates/article-list/list3.css","preview"=>"uploads/list3_preview.jpg","desc"=>"瀑布流布局"]
                    ],
                    "article_detail_templates" => [
                        ["id"=>1,"name"=>"基础详情页（居中）","file"=>"templates/article-detail/detail1.css","preview"=>"uploads/detail1_preview.jpg","desc"=>"内容居中"],
                        ["id"=>2,"name"=>"带目录详情页（左右分栏）","file"=>"templates/article-detail/detail2.css","preview"=>"uploads/detail2_preview.jpg","desc"=>"左侧内容，右侧目录"],
                        ["id"=>3,"name"=>"沉浸式详情页（全屏）","file"=>"templates/article-detail/detail3.css","preview"=>"uploads/detail3_preview.jpg","desc"=>"全屏沉浸式"]
                    ],
                    "selected" => [
                        "home_template" => 1,
                        "home_background" => 1,
                        "article_list_template" => 1,
                        "article_list_background" => 2,
                        "article_detail_template" => 1,
                        "article_detail_background" => 3
                    ]
                ];
                break;
            case 'backgrounds.json':
                $default = [
                    "backgrounds" => [
                        ["id"=>1,"name"=>"玄色随机光晕","file"=>"backgrounds/bg1.css","preview"=>"uploads/bg1_preview.jpg","type"=>"canvas","desc"=>"黑色背景+紫/蓝/粉随机光晕"],
                        ["id"=>2,"name"=>"霓虹网格背景","file"=>"backgrounds/bg2.css","preview"=>"uploads/bg2_preview.jpg","type"=>"css","desc"=>"黑色背景+霓虹色网格线条"],
                        ["id"=>3,"name"=>"星空粒子背景","file"=>"backgrounds/bg3.css","preview"=>"uploads/bg3_preview.jpg","type"=>"canvas","desc"=>"黑色背景+动态星空粒子"],
                        ["id"=>4,"name"=>"二次元渐变背景","file"=>"backgrounds/bg4.css","preview"=>"uploads/bg4_preview.jpg","type"=>"css","desc"=>"粉紫渐变+二次元纹理"]
                    ]
                ];
                break;
            case 'config.json':
                $default = [
                    "personal_info" => [
                        "avatar" => "uploads/avatar_default.png",
                        "nickname" => "二次元小主",
                        "intro" => "热爱二次元的程序员，分享网页搭建、动漫杂谈和技术干货～"
                    ],
                    "bg_music" => [
                        "home_url" => "music/home.mp3",
                        "article_list_url" => "music/list.mp3",
                        "article_detail_url" => "music/detail.mp3",
                        "home_volume" => 0.7,
                        "article_list_volume" => 0.6,
                        "article_detail_volume" => 0.5,
                        "auto_play" => true
                    ],
                    "video" => ["home_url" => "video/main.m3u8"]
                ];
                break;
            case 'links.json':
                $default = [
                    ["title"=>"测试卡片1","image_url"=>"https://picsum.photos/400/200","thumb_url"=>"","link_url"=>"#","show_date"=>"2026-03-11"],
                    ["title"=>"测试卡片2","image_url"=>"https://picsum.photos/400/201","thumb_url"=>"","link_url"=>"#","show_date"=>"2026-03-12"]
                ];
                break;
            case 'articles.json':
                $default = [
                    [
                        "id"=>1,
                        "title"=>"第一篇测试文章",
                        "create_time"=>"2026-03-11 12:00:00",
                        "content"=>"这是文章内容，支持HTML、图片、视频。<br><img src='https://picsum.photos/800/400'><br><video src='test.mp4' controls></video>"
                    ],
                    [
                        "id"=>2,
                        "title"=>"第二篇测试文章",
                        "create_time"=>"2026-03-11 13:00:00",
                        "content"=>"文章内容支持HTML代码、图片、音乐、视频。<br><audio src='music/test.mp3' controls></audio>"
                    ]
                ];
                break;
            default:
                $default = [];
        }
        // 写入默认数据
        file_put_contents($filePath, json_encode($default, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return $default;
    }
    // 读取并解析JSON
    $content = file_get_contents($filePath);
    $data = json_decode($content, true);
    // 解析失败则返回默认数据
    if ($data === null) {
        $content = json_encode([], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        file_put_contents($filePath, $content);
        return [];
    }
    return $data;
}

// 写入JSON文件（绝对路径）
function writeJsonFile($filename, $data) {
    $filePath = __DIR__ . '/data/' . $filename;
    // 确保数据是数组
    if (!is_array($data)) $data = [];
    return file_put_contents($filePath, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

// 获取选中的模板/背景文件
function getSelectedAsset($type) {
    $templates = readJsonFile('templates.json');
    $backgrounds = readJsonFile('backgrounds.json');
    
    switch ($type) {
        case 'home_template':
            $id = $templates['selected']['home_template'] ?? 1;
            foreach ($templates['home_templates'] as $tpl) {
                if ($tpl['id'] == $id) return $tpl['file'];
            }
            return 'templates/home/home1.css';
        case 'home_background':
            $id = $templates['selected']['home_background'] ?? 1;
            foreach ($backgrounds['backgrounds'] as $bg) {
                if ($bg['id'] == $id) return $bg['file'];
            }
            return 'backgrounds/bg1.css';
        case 'article_list_template':
            $id = $templates['selected']['article_list_template'] ?? 1;
            foreach ($templates['article_list_templates'] as $tpl) {
                if ($tpl['id'] == $id) return $tpl['file'];
            }
            return 'templates/article-list/list1.css';
        case 'article_list_background':
            $id = $templates['selected']['article_list_background'] ?? 2;
            foreach ($backgrounds['backgrounds'] as $bg) {
                if ($bg['id'] == $id) return $bg['file'];
            }
            return 'backgrounds/bg2.css';
        case 'article_detail_template':
            $id = $templates['selected']['article_detail_template'] ?? 1;
            foreach ($templates['article_detail_templates'] as $tpl) {
                if ($tpl['id'] == $id) return $tpl['file'];
            }
            return 'templates/article-detail/detail1.css';
        case 'article_detail_background':
            $id = $templates['selected']['article_detail_background'] ?? 3;
            foreach ($backgrounds['backgrounds'] as $bg) {
                if ($bg['id'] == $id) return $bg['file'];
            }
            return 'backgrounds/bg3.css';
        default:
            return '';
    }
}

// 获取个人信息
function getPersonalInfo() {
    $config = readJsonFile('config.json');
    return $config['personal_info'] ?? [
        "avatar" => "uploads/avatar_default.png",
        "nickname" => "二次元小主",
        "intro" => "热爱二次元的程序员～"
    ];
}

// 生成缩略图
function createThumbnail($sourcePath, $targetPath, $width = 300, $height = 200) {
    $sourcePath = __DIR__ . '/' . $sourcePath;
    $targetPath = __DIR__ . '/' . $targetPath;
    
    $info = getimagesize($sourcePath);
    if (!$info) return false;
    
    $mime = $info['mime'];
    switch ($mime) {
        case 'image/jpeg':
            $srcImg = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $srcImg = imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $srcImg = imagecreatefromgif($sourcePath);
            break;
        default:
            return false;
    }
    
    $srcWidth = imagesx($srcImg);
    $srcHeight = imagesy($srcImg);
    
    $targetImg = imagecreatetruecolor($width, $height);
    imagesavealpha($targetImg, true);
    $transparent = imagecolorallocatealpha($targetImg, 0, 0, 0, 127);
    imagefill($targetImg, 0, 0, $transparent);
    
    imagecopyresampled($targetImg, $srcImg, 0, 0, 0, 0, $width, $height, $srcWidth, $srcHeight);
    
    switch ($mime) {
        case 'image/jpeg':
            imagejpeg($targetImg, $targetPath, 80);
            break;
        case 'image/png':
            imagepng($targetImg, $targetPath, 9);
            break;
        case 'image/gif':
            imagegif($targetImg, $targetPath);
            break;
    }
    
    imagedestroy($srcImg);
    imagedestroy($targetImg);
    return true;
}

// 后台登录验证
function checkAdminLogin() {
    session_start();
    if (!isset($_SESSION['admin_login'])) {
        header('Location: index.php?action=login');
        exit;
    }
}

// 验证JSON格式
function validateJson($jsonStr) {
    json_decode($jsonStr);
    return json_last_error() === JSON_ERROR_NONE;
}
?>