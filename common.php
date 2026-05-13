<?php
// 兼容PHP7的str_contains函数
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}

// 安全配置：禁用777权限，使用更安全的权限值
define('DIR_PERMISSION', 0755); // 仅所有者可读写执行，其他只读
define('FILE_PERMISSION', 0644); // 仅所有者可读写，其他只读

// 确保目录存在（安全权限）
$baseDir = __DIR__;
$dirs = [
    $baseDir . '/data', 
    $baseDir . '/uploads', 
    $baseDir . '/backgrounds', 
    $baseDir . '/templates/home', 
    $baseDir . '/templates/article-list', 
    $baseDir . '/templates/article-detail',
    $baseDir . '/data/backup',
    $baseDir . '/data/articles' // 新增：文章内容单独存储目录
];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, DIR_PERMISSION, true);
        chmod($dir, DIR_PERMISSION); // 强制设置安全权限
    }
}

// 安全读取JSON文件（核心：清除缓存+路径校验+不覆盖原文件）
function readJsonFile($filename) {
    $filePath = __DIR__ . '/data/' . $filename;
    $backupPath = __DIR__ . '/data/backup/' . $filename . '.bak';

    // 关键修复：强制清除PHP文件缓存（确保读取最新数据）
    clearstatcache(true, $filePath);

    // 1. 优先读取备份文件（如果原文件损坏，不影响后台编辑）
    if (file_exists($backupPath) && (!file_exists($filePath) || filesize($filePath) == 0)) {
        copy($backupPath, $filePath);
        chmod($filePath, FILE_PERMISSION);
    }

    // 2. 初始化默认数据（仅当文件不存在时，后台编辑后会覆盖）
    if (!file_exists($filePath)) {
        $default = getDefaultData($filename);
        safeFilePutContents($filePath, json_encode($default, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        chmod($filePath, FILE_PERMISSION);
        // 创建初始备份（不影响后台编辑）
        copy($filePath, $backupPath);
        chmod($backupPath, FILE_PERMISSION);
        return $default;
    }

    // 3. 读取文件（处理读取失败）
    $content = file_get_contents($filePath);
    if ($content === false) {
        error_log("读取文件失败: $filePath");
        return getDefaultData($filename);
    }

    // 4. 解析JSON（失败时返回默认值，不覆盖原文件）
    $data = json_decode($content, true);
    if ($data === null) {
        error_log("JSON解析失败: $filePath，错误码: " . json_last_error_msg());
        return getDefaultData($filename);
    }

    // 调试日志（上线后可删除，不影响功能）
    error_log("成功读取 $filename，数据条数: " . count($data));
    return $data;
}

// 安全写入JSON文件（核心：非空校验+原子写入+保留后台编辑）
function writeJsonFile($filename, $data) {
    // 1. 非空校验：拒绝空数据（但保留后台合法编辑的空数组，仅过滤完全空的情况）
    if (!is_array($data)) {
        error_log("拒绝写入非数组数据到 $filename");
        return false;
    }

    $filePath = __DIR__ . '/data/' . $filename;
    $backupPath = __DIR__ . '/data/backup/' . $filename . '.bak';
    $tempPath = $filePath . '.tmp'; // 临时文件（原子写入）

    // 2. 生成合法的JSON字符串
    $jsonStr = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($jsonStr === false) {
        error_log("JSON编码失败: $filename");
        return false;
    }

    // 3. 原子写入：先写临时文件，成功后替换（不丢失后台编辑数据）
    $writeResult = safeFilePutContents($tempPath, $jsonStr);
    if (!$writeResult) {
        error_log("写入临时文件失败: $tempPath");
        return false;
    }

    // 4. 备份原文件（写入前备份，防止后台编辑数据丢失）
    if (file_exists($filePath)) {
        copy($filePath, $backupPath);
        chmod($backupPath, FILE_PERMISSION);
    }

    // 5. 替换原文件+清除缓存
    if (rename($tempPath, $filePath)) {
        chmod($filePath, FILE_PERMISSION);
        clearstatcache(true, $filePath); // 清除缓存，确保立即加载
        error_log("成功写入 $filename，数据条数: " . count($data));
        return true;
    } else {
        error_log("替换文件失败: $filePath");
        unlink($tempPath); // 删除临时文件
        return false;
    }
}

// 获取默认数据（适配拆分存储结构）
function getDefaultData($filename) {
    switch ($filename) {
        case 'templates.json':
            return [
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
        case 'backgrounds.json':
            return [
                "backgrounds" => [
                    ["id"=>1,"name"=>"玄色随机光晕","file"=>"backgrounds/bg1.css","preview"=>"uploads/bg1_preview.jpg","type"=>"canvas","desc"=>"黑色背景+紫/蓝/粉随机光晕"],
                    ["id"=>2,"name"=>"霓虹网格背景","file"=>"backgrounds/bg2.css","preview"=>"uploads/bg2_preview.jpg","type"=>"css","desc"=>"黑色背景+霓虹色网格线条"],
                    ["id"=>3,"name"=>"星空粒子背景","file"=>"backgrounds/bg3.css","preview"=>"uploads/bg3_preview.jpg","type"=>"canvas","desc"=>"黑色背景+动态星空粒子"],
                    ["id"=>4,"name"=>"二次元渐变背景","file"=>"backgrounds/bg4.css","preview"=>"uploads/bg4_preview.jpg","type"=>"css","desc"=>"粉紫渐变+二次元纹理"],
                 ["id"=>5,"name"=>"二次元视频背景","file"=>"backgrounds/bg5.css","preview"=>"uploads/bg4_preview.jpg","type"=>"css","desc"=>"粉紫渐变+二次元视频"]
                ]
            ];
        case 'config.json':
            return [
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
        case 'links.json':
            return [
                ["title"=>"测试卡片1","image_url"=>"https://picsum.photos/400/200","thumb_url"=>"","link_url"=>"#","show_date"=>"2026-03-11"],
                ["title"=>"测试卡片2","image_url"=>"https://picsum.photos/400/201","thumb_url"=>"","link_url"=>"#","show_date"=>"2026-03-12"]
            ];
        case 'articles.json':
            // 默认数据适配拆分存储：仅保留元信息，内容存储到单独文件
            $defaultArticles = [
                [
                    "id"=>1,
                    "title"=>"第一篇测试文章",
                    "create_time"=>"2026-03-11 12:00:00",
                    "summary"=>"这是第一篇测试文章的摘要，简短介绍文章内容～", // 新增摘要字段
                    "content_path"=>"articles/article_1.json" // 内容文件路径
                ],
                [
                    "id"=>2,
                    "title"=>"第二篇测试文章",
                    "create_time"=>"2026-03-11 13:00:00",
                    "summary"=>"文章内容支持HTML代码、图片、音乐、视频～", // 新增摘要字段
                    "content_path"=>"articles/article_2.json" // 内容文件路径
                ]
            ];
            
            // 初始化默认文章内容文件
            $content1 = ["content" => "这是文章内容，支持HTML、图片、视频。<br><img src='https://picsum.photos/800/400'><br><video src='test.mp4' controls></video>"];
            $content2 = ["content" => "文章内容支持HTML代码、图片、音乐、视频。<br><audio src='music/test.mp3' controls></audio>"];
            safeFilePutContents(__DIR__ . '/data/articles/article_1.json', json_encode($content1, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            safeFilePutContents(__DIR__ . '/data/articles/article_2.json', json_encode($content2, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            
            return $defaultArticles;
        default:
            return [];
    }
}

// 安全写入文件（处理权限）
function safeFilePutContents($filePath, $content) {
    $result = file_put_contents($filePath, $content);
    if ($result !== false) {
        chmod($filePath, FILE_PERMISSION);
        return true;
    }
    error_log("写入文件失败: $filePath，权限或磁盘问题");
    return false;
}

// 获取选中的模板/背景文件（添加默认值）
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

// 获取个人信息（添加默认值）
function getPersonalInfo() {
    $config = readJsonFile('config.json');
    return $config['personal_info'] ?? [
        "avatar" => "uploads/avatar_default.png",
        "nickname" => "二次元小主",
        "intro" => "热爱二次元的程序员～"
    ];
}

// 生成缩略图（添加错误处理）
function createThumbnail($sourcePath, $targetPath, $width = 300, $height = 200) {
    $sourcePath = __DIR__ . '/' . $sourcePath;
    $targetPath = __DIR__ . '/' . $targetPath;
    
    if (!file_exists($sourcePath)) {
        error_log("缩略图源文件不存在: $sourcePath");
        return false;
    }
    
    $info = getimagesize($sourcePath);
    if (!$info) {
        error_log("不是有效图片: $sourcePath");
        return false;
    }
    
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
            error_log("不支持的图片格式: $mime");
            return false;
    }
    
    if (!$srcImg) {
        error_log("创建图片资源失败: $sourcePath");
        return false;
    }
    
    $srcWidth = imagesx($srcImg);
    $srcHeight = imagesy($srcImg);
    
    $targetImg = imagecreatetruecolor($width, $height);
    imagesavealpha($targetImg, true);
    $transparent = imagecolorallocatealpha($targetImg, 0, 0, 0, 127);
    imagefill($targetImg, 0, 0, $transparent);
    
    imagecopyresampled($targetImg, $srcImg, 0, 0, 0, 0, $width, $height, $srcWidth, $srcHeight);
    
    $result = false;
    switch ($mime) {
        case 'image/jpeg':
            $result = imagejpeg($targetImg, $targetPath, 80);
            break;
        case 'image/png':
            $result = imagepng($targetImg, $targetPath, 9);
            break;
        case 'image/gif':
            $result = imagegif($targetImg, $targetPath);
            break;
    }
    
    if ($result) {
        chmod($targetPath, FILE_PERMISSION);
    } else {
        error_log("写入缩略图失败: $targetPath");
    }
    
    imagedestroy($srcImg);
    imagedestroy($targetImg);
    return $result;
}

// 后台登录验证（添加Session安全配置）
function checkAdminLogin() {
    session_start([
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict'
    ]);
    if (!isset($_SESSION['admin_login']) || $_SESSION['admin_login'] !== true) {
        header('Location: index.php?action=login');
        exit;
    }
}

// 验证JSON格式
function validateJson($jsonStr) {
    json_decode($jsonStr);
    return json_last_error() === JSON_ERROR_NONE;
}

// 手动备份数据（后台可调用，不影响编辑）
function backupDataFile($filename) {
    $filePath = __DIR__ . '/data/' . $filename;
    $backupPath = __DIR__ . '/data/backup/' . $filename . '.' . date('YmdHis') . '.bak';
    if (file_exists($filePath)) {
        $result = copy($filePath, $backupPath);
        if ($result) {
            chmod($backupPath, FILE_PERMISSION);
            error_log("备份文件成功: $backupPath");
        } else {
            error_log("备份文件失败: $backupPath");
        }
        return $result;
    }
    return false;
}

// ====================== 核心：文章拆分存储适配函数（兼容原有调用）======================

/**
 * 读取文章内容文件（内部函数）
 * @param string $contentPath 内容文件路径（如 articles/article_1.json）
 * @return string 文章内容（失败返回空字符串）
 */
function readArticleContent($contentPath) {
    $filePath = __DIR__ . '/data/' . $contentPath;
    if (!file_exists($filePath)) {
        error_log("文章内容文件不存在: $filePath");
        return '';
    }
    
    $content = file_get_contents($filePath);
    if ($content === false) {
        error_log("读取文章内容文件失败: $filePath");
        return '';
    }
    
    $data = json_decode($content, true);
    if ($data === null || !isset($data['content'])) {
        error_log("文章内容文件解析失败: $filePath");
        return '';
    }
    
    return $data['content'];
}

/**
 * 写入文章内容文件（内部函数）
 * @param string $contentPath 内容文件路径
 * @param string $content 文章内容
 * @return bool 是否写入成功
 */
function writeArticleContent($contentPath, $content) {
    $filePath = __DIR__ . '/data/' . $contentPath;
    $data = ["content" => $content];
    $jsonStr = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
    return safeFilePutContents($filePath, $jsonStr);
}

/**
 * 迁移原有文章数据到拆分存储（首次运行自动执行）
 */
function migrateOldArticleData() {
    $articles = readJsonFile('articles.json');
    $needMigrate = false;
    
    // 检查是否需要迁移（存在content字段则需要）
    foreach ($articles as $article) {
        if (isset($article['content']) && !isset($article['content_path'])) {
            $needMigrate = true;
            break;
        }
    }
    
    if (!$needMigrate) return true;
    
    // 执行迁移：拆分content到单独文件
    foreach ($articles as &$article) {
        if (isset($article['content'])) {
            $articleId = $article['id'];
            $contentPath = "articles/article_{$articleId}.json";
            
            // 写入内容文件
            writeArticleContent($contentPath, $article['content']);
            
            // 生成摘要（取前200个字符）
            $plainContent = strip_tags($article['content']);
            $article['summary'] = mb_substr($plainContent, 0, 50, 'UTF-8') . (mb_strlen($plainContent) > 50 ? '...' : '');
            
            // 保留元信息，删除content字段，添加content_path
            unset($article['content']);
            $article['content_path'] = $contentPath;
        }
    }
    unset($article);
    
    // 保存迁移后的articles.json
    return writeJsonFile('articles.json', $articles);
}

// 首次运行自动迁移原有数据
migrateOldArticleData();

/**
 * 获取文章列表（兼容原有调用方式）
 */
function getArticleList($page = 1, $pageSize = 10) {
    $articles = readJsonFile('articles.json');
    $total = count($articles);

    // 按时间倒序
    usort($articles, function($a, $b) {
        return strcmp($b['create_time'], $a['create_time']);
    });

    $page = max(1, (int)$page);
    $offset = ($page - 1) * $pageSize;
    $list = array_slice($articles, $offset, $pageSize);

    return [
        'total' => $total,
        'list' => $list,
        'page' => $page,
        'pageSize' => $pageSize,
        'totalPages' => ceil($total / $pageSize)
    ];
}

/**
 * 根据ID获取文章（兼容原有调用，自动拼接content字段）
 */
function getArticleById($id) {
    $articles = readJsonFile('articles.json');
    foreach ($articles as $article) {
        if ($article['id'] == $id) {
            // 兼容原有结构：读取内容并添加content字段
            $article['content'] = readArticleContent($article['content_path']);
            return $article;
        }
    }
    return null;
}

/**
 * 新增文章（兼容原有调用）
 * @param string $title 标题
 * @param string $content 内容
 * @param string $summary 摘要（可选，为空则自动生成）
 * @return bool 是否新增成功
 */
function addArticle($title, $content, $summary = '') {
    $articles = readJsonFile('articles.json');
    $maxId = 0;
    foreach ($articles as $a) $maxId = max($maxId, $a['id']);
    $newId = $maxId + 1;

    // 生成内容文件路径
    $contentPath = "articles/article_{$newId}.json";
    
    // 自动生成摘要（如果未提供）
    if (empty($summary)) {
        $plainContent = strip_tags($content);
        $summary = mb_substr($plainContent, 0, 50, 'UTF-8') . (mb_strlen($plainContent) > 50 ? '...' : '');
    }

    // 写入内容文件
    if (!writeArticleContent($contentPath, $content)) {
        error_log("新增文章失败：写入内容文件失败");
        return false;
    }

    // 添加元信息到articles.json
    $articles[] = [
        'id' => $newId,
        'title' => $title,
        'create_time' => date('Y-m-d H:i:s'),
        'summary' => $summary,
        'content_path' => $contentPath
    ];

    return writeJsonFile('articles.json', $articles);
}

/**
 * 更新文章（兼容原有调用）
 */
function updateArticle($id, $title, $content, $summary = '') {
    $articles = readJsonFile('articles.json');
    $articleIndex = -1;
    $contentPath = '';

    // 查找文章并获取内容路径
    foreach ($articles as $key => $a) {
        if ($a['id'] == $id) {
            $articleIndex = $key;
            $contentPath = $a['content_path'];
            break;
        }
    }

    if ($articleIndex == -1) {
        error_log("更新文章失败：ID=$id 的文章不存在");
        return false;
    }

    // 自动生成摘要（如果未提供）
    if (empty($summary)) {
        $plainContent = strip_tags($content);
        $summary = mb_substr($plainContent, 0, 50, 'UTF-8') . (mb_strlen($plainContent) > 50 ? '...' : '');
    }

    // 更新内容文件
    if (!writeArticleContent($contentPath, $content)) {
        error_log("更新文章失败：写入内容文件失败");
        return false;
    }

    // 更新元信息
    $articles[$articleIndex]['title'] = $title;
    $articles[$articleIndex]['summary'] = $summary;

    return writeJsonFile('articles.json', $articles);
}

/**
 * 删除文章（兼容原有调用，同时删除内容文件）
 */
function deleteArticle($id) {
    $articles = readJsonFile('articles.json');
    $newArticles = [];
    $contentPath = '';

    // 分离要删除的文章，获取内容路径
    foreach ($articles as $a) {
        if ($a['id'] == $id) {
            $contentPath = $a['content_path'];
        } else {
            $newArticles[] = $a;
        }
    }

    if (empty($contentPath)) {
        error_log("删除文章失败：ID=$id 的文章不存在");
        return false;
    }

    // 删除内容文件
    $contentFilePath = __DIR__ . '/data/' . $contentPath;
    if (file_exists($contentFilePath)) {
        unlink($contentFilePath);
        error_log("删除文章内容文件：$contentFilePath");
    }

    // 更新articles.json
    return writeJsonFile('articles.json', $newArticles);
}
?>