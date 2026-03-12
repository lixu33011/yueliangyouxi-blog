<?php
session_start();
require_once '../common.php';

// 登录验证
if (!isset($_SESSION['admin_login'])) {
    header('Location: index.php');
    exit;
}

// 读取文章数据
$articles = readJsonFile('articles.json');
$articleId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$currentArticle = [
    'id' => $articleId ?: (count($articles) + 1),
    'title' => '',
    'create_time' => date('Y-m-d H:i:s'),
    'content' => ''
];

// 编辑已有文章
if ($articleId > 0) {
    foreach ($articles as $art) {
        if ($art['id'] == $articleId) {
            $currentArticle = $art;
            break;
        }
    }
}

// 保存文章
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['action'] == 'save_article') {
    $id = intval($_POST['article_id']);
    $title = trim($_POST['article_title']);
    $content = $_POST['article_content'];
    $createTime = trim($_POST['create_time']) ?: date('Y-m-d H:i:s');
    
    if (empty($title)) {
        $errorMsg = '文章标题不能为空';
    } else {
        $found = false;
        for ($i = 0; $i < count($articles); $i++) {
            if ($articles[$i]['id'] == $id) {
                $articles[$i]['title'] = $title;
                $articles[$i]['create_time'] = $createTime;
                $articles[$i]['content'] = $content;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $articles[] = [
                'id' => $id,
                'title' => $title,
                'create_time' => $createTime,
                'content' => $content
            ];
        }
        writeJsonFile('articles.json', $articles);
        $successMsg = $articleId ? '文章编辑成功' : '文章新增成功';
        if (isset($_POST['save_and_list'])) {
            header('Location: index.php#articles');
            exit;
        }
    }
}

// 删除文章
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['action'] == 'delete_article') {
    $id = intval($_POST['article_id']);
    $newArticles = [];
    foreach ($articles as $art) {
        if ($art['id'] != $id) $newArticles[] = $art;
    }
    writeJsonFile('articles.json', $newArticles);
    header('Location: index.php#articles');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文章编辑 - 博客后台</title>
    <style>
        /* 全局样式 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Microsoft YaHei", sans-serif;
        }
        body {
            background: #f4f5f7;
            color: #1d2129;
        }

        /* 顶部工具栏（仿CSDN） */
        .editor-toolbar {
            background: #fff;
            border-bottom: 1px solid #e5e6eb;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            position: sticky;
            top: 0;
            z-index: 100;
            flex-wrap: wrap;
        }
        .toolbar-btn {
            background: transparent;
            border: none;
            color: #4e5969;
            padding: 6px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .toolbar-btn:hover {
            background: #f2f3f5;
            color: #1d2129;
        }
        .toolbar-divider {
            width: 1px;
            height: 20px;
            background: #e5e6eb;
            margin: 0 4px;
        }

        /* 主容器 */
        .main-container {
            display: flex;
            min-height: calc(100vh - 52px);
        }

        /* 左侧目录栏 */
        .sidebar {
            width: 260px;
            background: #fff;
            border-right: 1px solid #e5e6eb;
            padding: 20px 16px;
        }
        .sidebar h3 {
            font-size: 14px;
            color: #4e5969;
            margin-bottom: 12px;
            font-weight: 500;
        }
        .sidebar .tip {
            font-size: 13px;
            color: #86909c;
            margin-top: 40px;
            line-height: 1.5;
        }

        /* 右侧编辑区 */
        .edit-area {
            flex: 1;
            padding: 30px 40px;
            background: #fff;
            max-width: calc(100% - 260px);
        }

        /* 标题输入 */
        .title-input {
            width: 100%;
            font-size: 28px;
            font-weight: 500;
            border: none;
            outline: none;
            color: #1d2129;
            padding: 0 0 10px 0;
            margin-bottom: 20px;
            border-bottom: 1px solid #f0f0f0;
            background: transparent;
        }
        .title-input::placeholder {
            color: #c9cdd4;
        }

        /* 富文本编辑框（核心） */
        .content-editor {
            width: 100%;
            min-height: 500px;
            border: 1px solid #e5e6eb;
            border-radius: 4px;
            padding: 20px;
            outline: none;
            line-height: 1.8;
            font-size: 16px;
            margin-bottom: 80px;
        }
        /* 编辑框内样式 */
        .content-editor h1 { font-size: 24px; margin: 20px 0; }
        .content-editor h2 { font-size: 22px; margin: 18px 0; }
        .content-editor h3 { font-size: 20px; margin: 16px 0; }
        .content-editor pre {
            background: #f6f8fa;
            padding: 16px;
            border-radius: 4px;
            margin: 10px 0;
            overflow-x: auto;
            font-family: Consolas, Monaco, monospace;
        }
        .content-editor blockquote {
            border-left: 4px solid #e5e6eb;
            padding: 0 16px;
            color: #86909c;
            margin: 10px 0;
        }

        /* 底部状态栏 */
        .footer-bar {
            position: fixed;
            bottom: 0;
            left: 260px;
            right: 0;
            background: #fff;
            border-top: 1px solid #e5e6eb;
            padding: 12px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 99;
        }
        .word-count {
            font-size: 14px;
            color: #4e5969;
        }
        .btn-group {
            display: flex;
            gap: 12px;
        }
        .btn {
            padding: 8px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            border: none;
        }
        .btn-draft {
            background: #fff;
            border: 1px solid #d0d3d9;
            color: #4e5969;
        }
        .btn-draft:hover {
            background: #f2f3f5;
        }
        .btn-publish {
            background: #ff7d00;
            color: #fff;
        }
        .btn-publish:hover {
            background: #ff9500;
        }

        /* 提示框 */
        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-success {
            background: #e8f4f8;
            color: #48bb78;
            border: 1px solid #c6e6e8;
        }
        .alert-error {
            background: #fef2f2;
            color: #e53e3e;
            border: 1px solid #fed7d7;
        }

        /* 响应式适配 */
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .edit-area { max-width: 100%; padding: 20px; }
            .footer-bar { left: 0; padding: 12px 20px; }
            .title-input { font-size: 24px; }
        }
    </style>
</head>
<body>
    <!-- 顶部工具栏 -->
    <div class="editor-toolbar">
        <button class="toolbar-btn" onclick="formatDoc('bold')">
            <b>B</b>
        </button>
        <button class="toolbar-btn" onclick="formatDoc('italic')">
            <i>I</i>
        </button>
        <button class="toolbar-btn" onclick="formatDoc('underline')">
            <u>U</u>
        </button>
        <div class="toolbar-divider"></div>
        <button class="toolbar-btn" onclick="formatDoc('formatBlock', '<h1>')">H1</button>
        <button class="toolbar-btn" onclick="formatDoc('formatBlock', '<h2>')">H2</button>
        <button class="toolbar-btn" onclick="formatDoc('formatBlock', '<h3>')">H3</button>
        <div class="toolbar-divider"></div>
        <button class="toolbar-btn" onclick="formatDoc('insertUnorderedList')">● 列表</button>
        <button class="toolbar-btn" onclick="formatDoc('insertOrderedList')">1. 列表</button>
        <button class="toolbar-btn" onclick="formatDoc('formatBlock', '<blockquote>')">引用</button>
        <button class="toolbar-btn" onclick="insertCode()">代码</button>
        <div class="toolbar-divider"></div>
        <button class="toolbar-btn" onclick="insertImage()">图片</button>
        <button class="toolbar-btn" onclick="formatDoc('createLink')">链接</button>
        <button class="toolbar-btn" onclick="formatDoc('insertHorizontalRule')">分割线</button>
    </div>

    <!-- 主容器 -->
    <div class="main-container">
        <!-- 左侧目录栏 -->
        <div class="sidebar">
            <h3>文章目录</h3>
            <div id="article-toc"></div>
            <div class="tip">
                编辑区添加H1/H2/H3标题<br>
                此处将自动生成目录
            </div>
        </div>

        <!-- 右侧编辑区 -->
        <div class="edit-area">
            <!-- 提示信息 -->
            <?php if (isset($successMsg)): ?>
                <div class="alert alert-success">✅ <?php echo $successMsg; ?></div>
            <?php endif; ?>
            <?php if (isset($errorMsg)): ?>
                <div class="alert alert-error">❌ <?php echo $errorMsg; ?></div>
            <?php endif; ?>

            <!-- 编辑表单 -->
            <form method="post" id="articleForm">
                <input type="hidden" name="action" value="save_article">
                <input type="hidden" name="article_id" value="<?php echo $currentArticle['id']; ?>">
                <input type="hidden" name="create_time" value="<?php echo $currentArticle['create_time']; ?>">
                
                <!-- 标题 -->
                <input type="text" class="title-input" name="article_title" 
                       value="<?php echo htmlspecialchars($currentArticle['title']); ?>"
                       placeholder="请输入文章标题（5~100字）" maxlength="100">
                
                <!-- 富文本编辑框 -->
                <div class="content-editor" id="contentEditor" contenteditable="true">
                    <?php echo $currentArticle['content']; ?>
                </div>
                
                <!-- 隐藏域存储内容 -->
                <textarea name="article_content" id="hiddenContent" style="display: none;"></textarea>
            </form>

            <!-- 删除表单 -->
            <form id="deleteForm" method="post" style="display: none;">
                <input type="hidden" name="action" value="delete_article">
                <input type="hidden" name="article_id" value="<?php echo $articleId; ?>">
            </form>
        </div>
    </div>

    <!-- 底部状态栏 -->
    <div class="footer-bar">
        <div class="word-count">
            字数统计：<span id="wordCount">0</span> 字
        </div>
        <div class="btn-group">
            <?php if ($articleId): ?>
                <button class="btn btn-draft" onclick="deleteArticle()">删除文章</button>
            <?php endif; ?>
            <button class="btn btn-draft" onclick="saveArticle(false)">保存草稿</button>
            <button class="btn btn-publish" onclick="saveArticle(true)">发布文章</button>
        </div>
    </div>

    <!-- 图片上传弹窗（隐藏） -->
    <div id="imageModal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); z-index: 999;">
        <h3 style="margin-bottom: 15px; font-size: 16px;">上传图片</h3>
        <input type="file" id="imageFile" accept="image/*" style="margin-bottom: 15px;">
        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <button class="btn btn-draft" onclick="document.getElementById('imageModal').style.display='none'">取消</button>
            <button class="btn btn-publish" onclick="uploadImage()">上传</button>
        </div>
    </div>
    <div id="modalMask" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 998;"></div>

    <script>
        // 初始化
        window.onload = function() {
            // 回显内容后更新字数统计
            updateWordCount();
            // 监听编辑内容变化
            document.getElementById('contentEditor').addEventListener('input', updateWordCount);
            // 生成目录
            generateToc();
        };

        // 富文本格式化（核心功能）
        function formatDoc(cmd, value = null) {
            document.execCommand(cmd, false, value);
            // 聚焦编辑框
            document.getElementById('contentEditor').focus();
            // 更新目录
            generateToc();
        }

        // 插入代码块
        function insertCode() {
            const selection = window.getSelection();
            const selectedText = selection.toString();
            const codeText = prompt('请输入代码内容：', selectedText);
            if (codeText !== null) {
                const pre = document.createElement('pre');
                const code = document.createElement('code');
                code.textContent = codeText;
                pre.appendChild(code);
                
                // 替换选中内容
                if (selection.rangeCount > 0) {
                    const range = selection.getRangeAt(0);
                    range.deleteContents();
                    range.insertNode(pre);
                } else {
                    document.getElementById('contentEditor').appendChild(pre);
                }
                updateWordCount();
            }
        }

        // 插入图片弹窗
        function insertImage() {
            document.getElementById('imageModal').style.display = 'block';
            document.getElementById('modalMask').style.display = 'block';
        }

        // 上传图片（模拟，实际需对接upload.php）
        function uploadImage() {
            const fileInput = document.getElementById('imageFile');
            if (!fileInput.files.length) {
                alert('请选择要上传的图片！');
                return;
            }
            
            // 模拟上传成功（实际项目中替换为AJAX请求）
            const file = fileInput.files[0];
            const reader = new FileReader();
            reader.onload = function(e) {
                // 在编辑框插入图片
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.maxWidth = '100%';
                img.style.margin = '10px 0';
                
                const selection = window.getSelection();
                if (selection.rangeCount > 0) {
                    const range = selection.getRangeAt(0);
                    range.deleteContents();
                    range.insertNode(img);
                } else {
                    document.getElementById('contentEditor').appendChild(img);
                }
                
                // 关闭弹窗
                document.getElementById('imageModal').style.display = 'none';
                document.getElementById('modalMask').style.display = 'none';
                fileInput.value = '';
                updateWordCount();
            };
            reader.readAsDataURL(file);
        }

        // 字数统计
        function updateWordCount() {
            const editor = document.getElementById('contentEditor');
            const text = editor.innerText || '';
            document.getElementById('wordCount').textContent = text.length;
        }

        // 生成文章目录
        function generateToc() {
            const editor = document.getElementById('contentEditor');
            const headings = editor.querySelectorAll('h1, h2, h3');
            const tocContainer = document.getElementById('article-toc');
            tocContainer.innerHTML = '';
            
            if (headings.length === 0) {
                tocContainer.innerHTML = '<div style="color:#86909c; font-size:13px;">暂无标题</div>';
                return;
            }
            
            const ul = document.createElement('ul');
            ul.style.listStyle = 'none';
            ul.style.paddingLeft = '0';
            
            headings.forEach((heading, index) => {
                const li = document.createElement('li');
                li.style.margin = '8px 0';
                li.style.paddingLeft = `${(parseInt(heading.tagName.charAt(1)) - 1) * 10}px`;
                
                const a = document.createElement('a');
                a.href = `#heading-${index}`;
                a.textContent = heading.textContent;
                a.style.color = '#4e5969';
                a.style.textDecoration = 'none';
                a.onclick = function(e) {
                    e.preventDefault();
                    heading.scrollIntoView({ behavior: 'smooth' });
                };
                a.onmouseover = function() {
                    this.style.color = '#ff7d00';
                };
                a.onmouseout = function() {
                    this.style.color = '#4e5969';
                };
                
                // 给标题添加标识
                heading.id = `heading-${index}`;
                
                li.appendChild(a);
                ul.appendChild(li);
            });
            
            tocContainer.appendChild(ul);
        }

        // 保存文章
        function saveArticle(isPublish) {
            const title = document.querySelector('.title-input').value.trim();
            // 验证标题
            if (title.length < 5) {
                alert('文章标题至少需要5个字！');
                return;
            }
            
            // 把编辑内容同步到隐藏域
            const editor = document.getElementById('contentEditor');
            document.getElementById('hiddenContent').value = editor.innerHTML;
            
            // 发布按钮添加跳转标识
            const form = document.getElementById('articleForm');
            if (isPublish) {
                let input = document.querySelector('input[name="save_and_list"]');
                if (!input) {
                    input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'save_and_list';
                    input.value = '1';
                    form.appendChild(input);
                }
            }
            
            // 提交表单
            form.submit();
        }

        // 删除文章
        function deleteArticle() {
            if (confirm('确定删除这篇文章吗？删除后无法恢复！')) {
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>
