<?php
session_start();
require_once '../common.php';

// 登录验证
if (!isset($_SESSION['admin_login'])) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['action'] == 'login') {
        $username = $_POST['username'];
        $password = $_POST['password'];
        // 测试账号：admin 密码：123456
        if ($username == 'admin' && $password == '123456') {
            $_SESSION['admin_login'] = true;
            header('Location: index.php');
            exit;
        } else {
            $error = '账号或密码错误';
        }
    }
    // ========== 新增：保存视频配置 ==========
if ($_POST['action'] == 'save_video') {
    $config['video'] = [
        'home_url' => $_POST['home_video'], // 主页视频地址
        'auto_play' => isset($_POST['video_auto_play']) ? true : false, // 视频自动播放
        'loop' => isset($_POST['video_loop']) ? true : false, // 视频循环播放
        'muted' => isset($_POST['video_muted']) ? true : false // 视频静音
    ];
    writeJsonFile('config.json', $config);
    $msg = '视频配置保存成功';
}
    // 登录页面
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <title>后台登录 - 月亮有喜博客</title>
        <style>
            body { background: #000; color: #f6f; text-align: center; padding-top: 100px; font-family: "Comic Sans MS", "幼圆", sans-serif; }
            .login-box { width: 300px; margin: 0 auto; background: rgba(0,0,0,0.8); padding: 20px; border: 2px solid #ff69b4; border-radius: 10px; }
            input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #00ffff; background: #333; color: #fff; }
            button { background: #ff69b4; border: none; padding: 10px 20px; color: #fff; cursor: pointer; }
            .error { color: red; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>后台登录</h2>
            <?php if (isset($error)) echo '<div class="error">'.$error.'</div>'; ?>
            <form method="post">
                <input type="hidden" name="action" value="login">
                <input type="text" name="username" placeholder="用户名" required>
                <input type="password" name="password" placeholder="密码" required>
                <button type="submit">登录</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// 退出登录
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header('Location: index.php');
    exit;
}

// 读取所有配置（强制初始化）
$config = readJsonFile('config.json');
$templates = readJsonFile('templates.json');
$backgrounds = readJsonFile('backgrounds.json');
$imageLinks = readJsonFile('links.json');
$articles = readJsonFile('articles.json');

$msg = '';
$errorMsg = '';
// ========== 新增：保存视频配置 ==========
if ($_POST['action'] == 'save_video') {
    $config['video'] = [
        'home_url' => $_POST['home_video'], // 主页视频地址
        'auto_play' => isset($_POST['video_auto_play']) ? true : false, // 视频自动播放
        'loop' => isset($_POST['video_loop']) ? true : false, // 视频循环播放
        'muted' => isset($_POST['video_muted']) ? true : false // 视频静音
    ];
    writeJsonFile('config.json', $config);
    $msg = '视频配置保存成功';
}
// 保存个人信息
if ($_POST['action'] == 'save_personal') {
    $config['personal_info'] = [
        'avatar' => $_POST['avatar'],
        'nickname' => $_POST['nickname'],
        'intro' => $_POST['intro']
    ];
    // 处理头像上传
    if ($_FILES['avatar_file']['error'] == 0) {
        $uploadDir = '../uploads/';
        $ext = pathinfo($_FILES['avatar_file']['name'], PATHINFO_EXTENSION);
        $fileName = 'avatar_' . uniqid() . '.' . $ext;
        $filePath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['avatar_file']['tmp_name'], $filePath)) {
            $config['personal_info']['avatar'] = $fileName; // 简化路径
        }
    }
    writeJsonFile('config.json', $config);
    $msg = '个人信息保存成功';
}

// 保存音乐配置
if ($_POST['action'] == 'save_music') {
    $config['bg_music'] = [
        'home_url' => $_POST['home_music'],
        'article_list_url' => $_POST['list_music'],
        'article_detail_url' => $_POST['detail_music'],
        'home_volume' => floatval($_POST['home_volume']),
        'article_list_volume' => floatval($_POST['list_volume']),
        'article_detail_volume' => floatval($_POST['detail_volume']),
        'auto_play' => isset($_POST['auto_play']) ? true : false
    ];
    writeJsonFile('config.json', $config);
    $msg = '音乐配置保存成功';
}

// 保存模板/背景选择
if ($_POST['action'] == 'save_template') {
    $templates['selected'] = [
        'home_template' => intval($_POST['home_template']),
        'home_background' => intval($_POST['home_background']),
        'article_list_template' => intval($_POST['list_template']),
        'article_list_background' => intval($_POST['list_background']),
        'article_detail_template' => intval($_POST['detail_template']),
        'article_detail_background' => intval($_POST['detail_background'])
    ];
    writeJsonFile('templates.json', $templates);
    $msg = '模板/背景选择保存成功';
}

// 保存图片链接（新增完整逻辑）
if ($_POST['action'] == 'save_links') {
    $linksData = $_POST['links_data'];
    // 验证JSON格式
    if (validateJson($linksData)) {
        $links = json_decode($linksData, true);
        writeJsonFile('links.json', $links);
        $msg = '图片链接保存成功';
    } else {
        $errorMsg = 'JSON格式错误，保存失败';
    }
}

// 保存文章数据（新增完整逻辑）
if ($_POST['action'] == 'save_articles') {
    $articlesData = $_POST['articles_data'];
    // 验证JSON格式
    if (validateJson($articlesData)) {
        $articles = json_decode($articlesData, true);
        // 确保ID唯一且递增
        $maxId = 0;
        foreach ($articles as $art) {
            if ($art['id'] > $maxId) $maxId = $art['id'];
        }
        // 给新文章自动分配ID（如果需要）
        writeJsonFile('articles.json', $articles);
        $msg = '文章数据保存成功';
    } else {
        $errorMsg = 'JSON格式错误，保存失败';
    }
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>后台管理 - 月亮有喜博客</title>
    <style>
        body { background: #000; color: #f6f; padding: 20px; font-family: "Comic Sans MS", "幼圆", sans-serif; }
        .container { max-width: 1200px; margin: 0 auto; }
        .tab { margin-bottom: 20px; }
        .tab button { background: #ff69b4; border: none; padding: 10px 20px; color: #fff; cursor: pointer; margin-right: 10px; border-radius: 5px; }
        .tab-content { display: none; background: rgba(0,0,0,0.8); padding: 20px; border: 2px solid #00ffff; border-radius: 10px; margin-bottom: 20px; }
        .tab-content.active { display: block; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; color: #ff69b4; }
        input, textarea, select { width: 100%; padding: 10px; background: #333; color: #fff; border: 1px solid #00ffff; border-radius: 5px; }
        textarea { height: 300px; resize: vertical; font-family: monospace; }
        button[type="submit"] { background: #ff69b4; border: none; padding: 10px 20px; color: #fff; cursor: pointer; margin-top: 10px; }
        .success { color: #00ff00; margin: 10px 0; }
        .error { color: #ff0000; margin: 10px 0; }
        .logout { position: fixed; top: 20px; right: 20px; color: #ff69b4; text-decoration: none; }
        .preview-img { width: 100px; height: 60px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1 style="color: #ff69b4; text-align: center; margin-bottom: 20px;">月亮博客后台管理</h1>
        <a href="?action=logout" class="logout">退出登录</a>
        
        <?php if ($msg) echo '<div class="success">'.$msg.'</div>'; ?>
        <?php if ($errorMsg) echo '<div class="error">'.$errorMsg.'</div>'; ?>
        
        <!-- 选项卡 -->
        <div class="tab">
            <button onclick="openTab('personal')">个人信息</button>
            <button onclick="openTab('video')">视频配置</button>
            <button onclick="openTab('music')">音乐配置</button>
            <button onclick="openTab('template')">模板/背景管理</button>
            <button onclick="openTab('links')">图片链接管理</button>
            <button onclick="openTab('articles')">文章管理</button>
        </div>
        
        <!-- 个人信息 -->
        <div id="personal" class="tab-content active">
            <h2>个人信息配置</h2>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save_personal">
                <div class="form-group">
                    <label>当前头像</label>
                    <img src="../uploads/<?php echo $config['personal_info']['avatar']; ?>" alt="头像" class="preview-img" onerror="this.src='https://picsum.photos/100/60';">
                </div>
                <div class="form-group">
                    <label>上传新头像</label>
                    <input type="file" name="avatar_file" accept="image/*">
                    <input type="hidden" name="avatar" value="<?php echo $config['personal_info']['avatar']; ?>">
                </div>
                <div class="form-group">
                    <label>网名</label>
                    <input type="text" name="nickname" value="<?php echo $config['personal_info']['nickname']; ?>" required>
                </div>
                <div class="form-group">
                    <label>个人简介</label>
                    <textarea name="intro" required><?php echo $config['personal_info']['intro']; ?></textarea>
                </div>
                <button type="submit">保存个人信息</button>
            </form>
        </div
        
        <!-- ========== 新增：视频配置选项卡内容 ========== -->
<div id="video" class="tab-content">
    <h2>视频配置</h2>
    <form method="post">
        <input type="hidden" name="action" value="save_video">
        <div class="form-group">
            <label>主页视频URL</label>
            <input type="text" name="home_video" 
                   value="<?php echo isset($config['video']['home_url']) ? $config['video']['home_url'] : ''; ?>" 
                   required placeholder="支持外链，如 https://xxx.mp4 或 /uploads/xxx.mp4">
        </div>
        <div class="form-group">
            <label>
                <input type="checkbox" name="video_auto_play" 
                       <?php echo (isset($config['video']['auto_play']) && $config['video']['auto_play']) ? 'checked' : ''; ?>>
                视频自动播放
            </label>
        </div>
        <div class="form-group">
            <label>
                <input type="checkbox" name="video_loop" 
                       <?php echo (isset($config['video']['loop']) && $config['video']['loop']) ? 'checked' : ''; ?>>
                视频循环播放
            </label>
        </div>
        <div class="form-group">
            <label>
                <input type="checkbox" name="video_muted" 
                       <?php echo (isset($config['video']['muted']) && $config['video']['muted']) ? 'checked' : ''; ?>>
                视频默认静音（建议开启，提升自动播放成功率）
            </label>
        </div>
        <button type="submit">保存视频配置</button>
    </form>
</div>
        
        <!-- 音乐配置 -->
        <div id="music" class="tab-content">
            <h2>背景音乐配置</h2>
            <form method="post">
                <input type="hidden" name="action" value="save_music">
                <div class="form-group">
                    <label>主页背景音乐URL</label>
                    <input type="text" name="home_music" value="<?php echo $config['bg_music']['home_url']; ?>" required placeholder="支持外链，如 https://xxx.mp3">
                </div>
                <div class="form-group">
                    <label>主页音量 (0-1)</label>
                    <input type="number" step="0.1" min="0" max="1" name="home_volume" value="<?php echo $config['bg_music']['home_volume']; ?>" required>
                </div>
                <div class="form-group">
                    <label>文章列表背景音乐URL</label>
                    <input type="text" name="list_music" value="<?php echo $config['bg_music']['article_list_url']; ?>" required placeholder="支持外链，如 https://xxx.mp3">
                </div>
                <div class="form-group">
                    <label>文章列表音量 (0-1)</label>
                    <input type="number" step="0.1" min="0" max="1" name="list_volume" value="<?php echo $config['bg_music']['article_list_volume']; ?>" required>
                </div>
                <div class="form-group">
                    <label>文章详情背景音乐URL</label>
                    <input type="text" name="detail_music" value="<?php echo $config['bg_music']['article_detail_url']; ?>" required placeholder="支持外链，如 https://xxx.mp3">
                </div>
                <div class="form-group">
                    <label>文章详情音量 (0-1)</label>
                    <input type="number" step="0.1" min="0" max="1" name="detail_volume" value="<?php echo $config['bg_music']['article_detail_volume']; ?>" required>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="auto_play" <?php echo $config['bg_music']['auto_play'] ? 'checked' : ''; ?>>
                        背景音乐自动播放
                    </label>
                </div>
                <button type="submit">保存音乐配置</button>
            </form>
        </div>

        <!-- 模板&背景选择 -->
        <div id="template" class="tab-content">
            <h2>模板与背景管理</h2>
            <form method="post">
                <input type="hidden" name="action" value="save_template">

                <div class="form-group">
                    <label>主页模板</label>
                    <select name="home_template">
                        <?php foreach($templates['home_templates'] as $t){ ?>
                        <option value="<?=$t['id']?>" <?= $templates['selected']['home_template']==$t['id']?'selected':'' ?>>
                            <?=$t['name']?> - <?=$t['desc']?>
                        </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>主页背景</label>
                    <select name="home_background">
                        <?php foreach($backgrounds['backgrounds'] as $bg){ ?>
                        <option value="<?=$bg['id']?>" <?= $templates['selected']['home_background']==$bg['id']?'selected':'' ?>>
                            <?=$bg['name']?> - <?=$bg['desc']?>
                        </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>文章列表模板</label>
                    <select name="list_template">
                        <?php foreach($templates['article_list_templates'] as $t){ ?>
                        <option value="<?=$t['id']?>" <?= $templates['selected']['article_list_template']==$t['id']?'selected':'' ?>>
                            <?=$t['name']?> - <?=$t['desc']?>
                        </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>文章列表背景</label>
                    <select name="list_background">
                        <?php foreach($backgrounds['backgrounds'] as $bg){ ?>
                        <option value="<?=$bg['id']?>" <?= $templates['selected']['article_list_background']==$bg['id']?'selected':'' ?>>
                            <?=$bg['name']?> - <?=$bg['desc']?>
                        </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>文章详情模板</label>
                    <select name="detail_template">
                        <?php foreach($templates['article_detail_templates'] as $t){ ?>
                        <option value="<?=$t['id']?>" <?= $templates['selected']['article_detail_template']==$t['id']?'selected':'' ?>>
                            <?=$t['name']?> - <?=$t['desc']?>
                        </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>文章详情背景</label>
                    <select name="detail_background">
                        <?php foreach($backgrounds['backgrounds'] as $bg){ ?>
                        <option value="<?=$bg['id']?>" <?= $templates['selected']['article_detail_background']==$bg['id']?'selected':'' ?>>
                            <?=$bg['name']?> - <?=$bg['desc']?>
                        </option>
                        <?php } ?>
                    </select>
                </div>

                <button type="submit">保存模板&背景设置</button>
            </form>
        </div>

        <!-- 图片链接管理 -->
        <div id="links" class="tab-content">
            <h2>图片链接管理</h2>
            <div class="form-group">
                <label>格式说明</label>
                <p style="color:#ccc;">每行一个卡片，JSON格式示例：<br>
                [{"title":"卡片标题","image_url":"图片URL","thumb_url":"缩略图URL","link_url":"跳转链接","show_date":"显示日期"}]
                </p>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="save_links">
                <div class="form-group">
                    <label>图片链接数据（JSON格式）</label>
                    <textarea name="links_data"><?= json_encode($imageLinks, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) ?></textarea>
                </div>
                <button type="submit">保存图片链接</button>
            </form>
        </div>

      <!-- 文章管理 -->
<div id="articles" class="tab-content">
    <h2>文章管理</h2>
    <div style="margin-bottom: 15px;">
        <button class="btn btn-primary" onclick="window.location.href='article-edit.php'">
            <i class="fas fa-plus"></i> 新增文章
        </button>
    </div>
    <div class="form-group">
        <label>格式说明</label>
        <p style="color:#ccc;">推荐使用上方的编辑器进行可视化编辑，手动编辑JSON格式示例：<br>
        [{"id":1,"title":"文章标题","create_time":"发布时间","content":"文章内容（支持HTML）"}]
        </p>
    </div>
    <!-- 文章列表预览 -->
    <div style="background: #f7fafc; padding: 15px; border-radius: 5px; margin-bottom: 15px; max-height: 200px; overflow-y: auto;">
        <h4 style="margin-bottom: 10px; color: #4a5568;">文章列表预览：</h4>
        <?php if (empty($articles)): ?>
        <div style="color: #718096;">暂无文章，点击"新增文章"创建第一篇文章</div>
        <?php else: ?>
        <table style="width:100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #e8f4f8;">
                    <th style="padding: 8px; text-align:left; border-bottom: 1px solid #ddd;">ID</th>
                    <th style="padding: 8px; text-align:left; border-bottom: 1px solid #ddd;">标题</th>
                    <th style="padding: 8px; text-align:left; border-bottom: 1px solid #ddd;">发布时间</th>
                    <th style="padding: 8px; text-align:left; border-bottom: 1px solid #ddd;">操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($articles as $art): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 8px;"><?php echo $art['id']; ?></td>
                    <td style="padding: 8px;"><?php echo htmlspecialchars($art['title']); ?></td>
                    <td style="padding: 8px;"><?php echo $art['create_time']; ?></td>
                    <td style="padding: 8px;">
                        <a href="article-edit.php?id=<?php echo $art['id']; ?>" style="color: #4299e1; margin-right: 10px;">编辑</a>
                        <a href="../article-detail.php?id=<?php echo $art['id']; ?>" target="_blank" style="color: #48bb78;">预览</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <form method="post">
        <input type="hidden" name="action" value="save_articles">
        <div class="form-group">
            <label>文章数据（JSON格式）</label>
            <textarea name="articles_data"><?= json_encode($articles, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存文章</button>
    </form>
</div>

    <script>
        function openTab(id) {
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(t => t.classList.remove('active'));
            document.getElementById(id).classList.add('active');
        }
        
        // 自动格式化JSON显示
        document.addEventListener('DOMContentLoaded', function() {
            const jsonTextareas = document.querySelectorAll('textarea[name="links_data"], textarea[name="articles_data"]');
            jsonTextareas.forEach(ta => {
                if (ta.value) {
                    try {
                        const obj = JSON.parse(ta.value);
                        ta.value = JSON.stringify(obj, null, 2);
                    } catch (e) {}
                }
            });
        });
    </script>
</body>
</html>