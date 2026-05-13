<?php
require_once 'common.php';

// 1. 读取数据（清除缓存，确保加载后台最新修改）
$config = readJsonFile('config.json');
$personalInfo = getPersonalInfo();
$imageLinks = readJsonFile('links.json');
$articles = readJsonFile('articles.json');

// 2. 处理图片分类：按 category 字段分组
$linkGroups = [];
if (!empty($imageLinks)) {
    foreach ($imageLinks as $item) {
        $type = isset($item['category']) && $item['category'] ? trim($item['category']) : '未分类';
        if (!isset($linkGroups[$type])) {
            $linkGroups[$type] = [];
        }
        $linkGroups[$type][] = $item;
    }
}

// 3. 排序文章
if (!empty($articles)) {
    usort($articles, function($a, $b) {
        $timeA = isset($a['create_time']) ? strtotime($a['create_time']) : 0;
        $timeB = isset($b['create_time']) ? strtotime($b['create_time']) : 0;
        return $timeB - $timeA;
    });
    $articles = array_slice($articles, 0, 5);
}

// 4. 处理配置路径（修复音乐/头像路径）
$homeTemplate = getSelectedAsset('home_template');
$homeBackground = getSelectedAsset('home_background');
// 修复音乐路径：去除首尾空格 + 拼接完整路径
$bgMusic = $config['bg_music']['home_url'];
$bgVolume = $config['bg_music']['home_volume'];
$autoPlay = $config['bg_music']['auto_play'] ? 'autoplay' : '';
// 修复头像路径：拼接/uploads前缀
$avatarUrl = '/uploads/' . ltrim($personalInfo['avatar'] ?? '', '/');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>月亮有喜的博客</title>
    <link rel="stylesheet" href="/js/all.min.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($homeBackground); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($homeTemplate); ?>">
    <style>
/* 农历样式 */
.lunar-text {
    font-family: "Comic Sans MS", "幼圆", sans-serif;
    font-size: 19px;
    background-clip: text;
    -webkit-background-clip: text;
    color: transparent;
    text-align: center;
    margin-top: 2px;
    letter-spacing: 1px;
}
@media (max-width: 768px) {
    .lunar-text { font-size: 18px; }
}

/* 日期悬浮模块 */
.date-float-container {
    position: fixed;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    z-index: 9999;
    background: rgba(0, 0, 0, 0.1);
    border-bottom-left-radius: 10px;
    border-bottom-right-radius: 10px;
    padding: 8px 20px;
    margin-top: 0;
    transition: all 0.3s ease;
}
.date-float-container:hover {
    transform: translateX(-50%) scale(1.05);
    box-shadow: 0 0 15px rgba(255, 105, 180, 0.3);
}
.date-text {
    font-family: "Comic Sans MS", "幼圆", sans-serif;
    font-size: 38px;
    font-weight: bold;
    background-clip: text;
    -webkit-background-clip: text;
    color: transparent;
    letter-spacing: 2px;
}

/* 全局样式 */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Comic Sans MS", "幼圆", sans-serif;
}
a { text-decoration: none; }

/* 音乐控制 */
.music-control {
    position: fixed;
    top: 20px;
    right: 20px;
    background: rgba(0,0,0,0.7);
    border: 2px solid #00ffff;
    border-radius: 50px;
    padding: 10px 20px;
    color: #fff;
    z-index: 100;
    display: flex;
    gap: 10px;
    align-items: center;
}
.music-btn, .home-btn {
    background: #ff69b4;
    border: none;
    color: #fff;
    padding: 8px 15px;
    border-radius: 5px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 14px;
}
.music-btn:hover, .home-btn:hover {
    background: #ff87b9;
}

/* 个人信息 */
.personal-info {
    position: fixed;
    top: 20px;
    left: 20px;
    z-index: 99;
    display: flex;
    align-items: center;
    gap: 15px;
    background: rgba(0,0,0,0.7);
    border: 2px solid #ff69b4;
    border-radius: 10px;
    padding: 15px;
    color: #fff;
}
.personal-info .avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: 2px solid #00ffff;
    object-fit: cover;
}
.personal-info .nickname {
    font-size: 20px;
    color: #ff69b4;
    margin-bottom: 5px;
}
.personal-info .intro {
    font-size: 14px;
    color: #ccc;
    max-width: 300px;
    white-space: pre-line;
}

/* 主布局 */
.main-layout {
    max-width: 1400px;
    margin: 0 auto;
    padding: 120px 20px 50px;
}

/* 视频容器 */
.video-container {
    width: 70%;
    margin-bottom: 40px;
    border-radius: 10px;
    overflow: hidden;
    border: 3px solid #ff69b4;
    background: #000;
}
#main-video {
    width: 100%;
    height: auto;
    display: block;
}
.video-loading {
    text-align: center;
    color: #fff;
    padding: 20px;
    font-size: 16px;
}

/* 图片分类布局 */
.link-category-container {
    margin-bottom: 40px;
}
.category-title {
    color: #ff69b4;
    font-size: 24px;
    font-weight: bold;
    margin: 20px 0 10px;
    padding-bottom: 5px;
    border-bottom: 2px solid #00ffff;
    display: inline-block;
}
.link-row {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
    align-items: center;
}
.link-item {
    flex: 0 0 auto;
    width: 200px;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    transition: transform 0.3s ease;
}
.link-item:hover {
    transform: translateY(-5px);
}
.link-item img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    display: block;
}
.link-title {
    background: rgba(0,0,0,0.7);
    color: #fff;
    padding: 8px;
    font-size: 14px;
    text-align: center;
    transition: background 0.3s ease;
}
.link-item:hover .link-title {
    background: rgba(255, 105, 180, 0.8);
}

/* 文章栏 */
.article-corner {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
    background: rgba(0,0,0,0.6);
    border-radius: 10px;
    border: 2px solid #00ffff;
    color: #fff;
}
.article-corner h3 {
    color: #ff69b4;
    font-size: 20px;
    margin-bottom: 15px;
    text-align: center;
    border-bottom: 1px solid #ccc;
    padding-bottom: 10px;
}
.article-item {
    margin-bottom: 10px;
    padding: 8px;
    border-radius: 5px;
    transition: background 0.3s ease;
}
.article-item a {
    color: #fff;
    display: block;
}
.article-item:hover {
    background: rgba(255, 105, 180, 0.3);
}
.article-item a:hover {
    color: #00ffff;
    padding-left: 5px;
    transition: padding 0.3s ease;
}

/* ======================
   移动端最终修复：全部不悬浮、不固定、流式布局
====================== */
@media (max-width: 768px) {
    /* 日期：取消固定，正常布局 */
    .date-float-container {
        position: relative;
        top: auto;
        left: auto;
        transform: none;
        z-index: 1;
        padding: 10px;
        text-align: center;
        background: none;
    }
    .date-text { font-size: 16px; }

    /* 音乐：取消固定，正常布局 */
    .music-control {
        position: relative;
        top: auto;
        right: auto;
        transform: none;
        width: 90%;
        margin: 10px auto;
        justify-content: center;
        z-index: 1;
    }

    /* 个人信息：取消固定，正常布局 */
    .personal-info {
        position: relative;
        top: auto;
        left: auto;
        transform: none;
        flex-direction: column;
        width: 90%;
        margin: 10px auto;
        padding: 10px;
        text-align: center;
        z-index: 1;
    }

    /* 主体内容：顶距归零 */
    .main-layout {
        padding: 20px 10px 50px;
    }

    /* 视频宽度100% */
    .video-container {
        width: 90%;
        margin: 20px auto;
    }

    /* 文章列表：正常布局 */
    .article-corner {
        position: relative;
        margin-top: 20px;
        width: 90%;
        margin-left: auto;
        margin-right: auto;
    }

    .link-item { width: calc(50% - 7.5px); }
    .category-title { font-size: 20px; }
}

@media (max-width: 480px) {
    .link-item { width: 100%; }
}
    </style>
    <script src="/js/hls.min.js"></script>
</head>
<body class="bg-container">
    <!-- 动态背景Canvas -->

   <?php if (mb_strpos($homeBackground, 'bg') !== false): ?>
<canvas id="bg-canvas"></canvas>
<?php endif; ?>
    <!-- 日期悬浮模块 -->
    <div class="date-float-container" onclick="window.location.href='/wnl/index.html' " style="cursor: pointer;">
        <div class="date-text" id="random-gradient-date"></div>
        <div class="lunar-text" id="lunar-date"></div>
    </div>  
    
    <!-- 个人信息（修复头像路径） -->
    <div class="personal-info">
        <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="头像" class="avatar" onerror="this.src='https://picsum.photos/80/80';">
        <div class="info">
            <div class="nickname"><?php echo htmlspecialchars($personalInfo['nickname']); ?></div>
            <div class="intro"><?php echo htmlspecialchars($personalInfo['intro']); ?></div>
        </div>
    </div>

     <!-- 背景音乐 -->
    <audio id="bg-music" loop>
        <source src="<?php echo htmlspecialchars($bgMusic); ?>" type="audio/mpeg">
        您的浏览器不支持音频播放
    </audio>

    <!-- 背景音乐控制 + 返回主页按钮 -->
    <div class="music-control">
        <button id="music-toggle" class="music-btn" onclick="toggleMusic()"></button>
        <button class="home-btn" onclick="window.location.href='index.php'">
            <i class="fas fa-home"></i> 返回主页
        </button>
    </div>

    <!-- 主内容 -->
    <div class="main-layout">
        <!-- 视频播放器 -->
        <div class="video-container">
            <div class="video-loading" id="videoLoading">视频加载中，请稍候...</div>
            <video id="main-video" controls preload="auto" style="display: none;" 
                <?php echo $config['video']['auto_play'] ? 'autoplay' : ''; ?>
                <?php echo $config['video']['loop'] ? 'loop' : ''; ?>
                <?php echo $config['video']['muted'] ? 'muted' : ''; ?>>
                您的浏览器不支持HLS流媒体播放，请升级浏览器！
            </video>
        </div>

        <!-- 图片分类展示 -->
        <div class="link-category-container">
            <?php if (empty($linkGroups)): ?>
                <div style="color: #fff; text-align: center; padding: 20px;">
                    暂无链接数据（可通过后台添加）
                </div>
            <?php else: ?>
                <?php foreach ($linkGroups as $type => $items): ?>
                    <div class="category-title"><?php echo htmlspecialchars($type); ?></div>
                    <div class="link-row">
                        <?php foreach ($items as $item): ?>
                            <a href="<?php echo htmlspecialchars($item['link_url'] ?? '#'); ?>" class="link-item" target="_blank">
                                <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'https://picsum.photos/200/120'); ?>" 
                                     alt="<?php echo htmlspecialchars($item['title'] ?? '卡片'); ?>" 
                                     onerror="this.src='https://picsum.photos/200/120';">
                                <div class="link-title"><?php echo htmlspecialchars($item['title'] ?? '未命名'); ?></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- 文章列表 -->
    <div class="article-corner">
        <h3>文章分享</h3>
        <?php if (empty($articles)): ?>
            <div style="text-align:center; padding: 10px; color: #fff;">
                暂无文章数据（可通过后台添加）
            </div>
        <?php else: ?>
            <?php foreach ($articles as $article): ?>
                <div class="article-item">
                    <a href="article-detail.php?id=<?php echo intval($article['id'] ?? 0); ?>">
                        <?php echo htmlspecialchars($article['title'] ?? '未命名文章'); ?>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <div style="text-align:center; margin-top:15px; padding-top:10px; border-top:1px solid #ccc;">
            <a href="article-list.php" style="color:#ff69b4; margin-right:15px;">
                <i class="fas fa-list"></i> 查看更多
            </a>
            <a href="admin/" style="color:#ff69b4;">
                <i class="fas fa-cog"></i> 后台管理
            </a>
        </div>
    </div>

    <!-- 脚本（修复音乐播放逻辑） -->
    <script>
       // 音乐播放核心逻辑
        var bgMusic = document.getElementById('bg-music');
        var isPlaying = false; // 初始化为未播放（规避自动播放限制）
        
        // 设置音量（页面加载时就设置）
        if (bgMusic) {
            bgMusic.volume = <?php echo floatval($bgVolume); ?>;
            // 监听播放/暂停状态变化
            bgMusic.onplay = function() { 
                isPlaying = true; 
                updateMusicBtn(); 
            };
            bgMusic.onpause = function() { 
                isPlaying = false; 
                updateMusicBtn(); 
            };
            // 监听音频加载失败
            bgMusic.onerror = function() {
                console.error('音乐加载失败，请检查文件路径:', '<?php echo htmlspecialchars($bgMusic); ?>');
                alert('背景音乐加载失败，请检查配置的音乐文件路径是否正确！');
            };
        }

        // 更新音乐按钮状态
        function updateMusicBtn() {
            const btn = document.getElementById('music-toggle');
            if (!btn) return; // 防止按钮不存在的情况
            if (isPlaying) {
                btn.innerHTML = '<i class="fas fa-pause"></i> 暂停音乐';
            } else {
                btn.innerHTML = '<i class="fas fa-play"></i> 播放音乐';
            }
        }

        // 切换音乐播放状态（用户手动触发，符合浏览器策略）
        function toggleMusic() {
            if (!bgMusic) return;
            
            try {
                if (isPlaying) {
                    bgMusic.pause();
                } else {
                    // 手动触发播放，解决自动播放限制
                    bgMusic.play().catch(function(err) {
                        console.error('播放失败:', err);
                        alert('播放失败！原因:\n1. 音乐文件路径错误\n2. 浏览器安全策略要求手动交互\n3. 音频格式不支持');
                    });
                }
            } catch (e) {
                console.error('播放控制异常:', e);
                alert('音乐播放控制出错，请检查浏览器控制台！');
            }
        }

        // 视频播放逻辑
        const video = document.getElementById('main-video');
        const videoLoading = document.getElementById('videoLoading');
        const videoUrl = '<?php echo htmlspecialchars($config['video']['home_url'] ?? ''); ?>';
        let hls = null;
        let retryCount = 0;
        const MAX_RETRY = 3;

        function toggleVideoLoading(show) {
            videoLoading.style.display = show ? 'block' : 'none';
            video.style.display = show ? 'none' : 'block';
        }

        function resetVideoPlayer() {
            if (video) video.pause();
            if (hls) hls.destroy();
            hls = null;
            retryCount = 0;
        }

        function playVideo(url) {
            resetVideoPlayer();
            toggleVideoLoading(true);
            const trimUrl = url.trim();

            if (!trimUrl) {
                toggleVideoLoading(false);
                videoLoading.textContent = '视频地址未配置';
                return;
            }

            if (trimUrl.endsWith('.m3u8')) {
                if (typeof Hls !== 'undefined' && Hls.isSupported()) {
                    hls = new Hls({
                        maxBufferLength: 30,
                        maxMaxBufferLength: 60,
                        startLevel: -1
                    });

                    hls.on(Hls.Events.ERROR, function(event, data) {
                        console.error('视频播放错误:', data);
                        if (data.fatal && retryCount < MAX_RETRY) {
                            retryCount++;
                            setTimeout(() => playVideo(url), 1000 * retryCount);
                        } else {
                            toggleVideoLoading(false);
                            videoLoading.textContent = '视频播放失败，请刷新页面';
                        }
                    });

                    hls.on(Hls.Events.MANIFEST_PARSED, function() {
                        toggleVideoLoading(false);
                    });

                    hls.loadSource(url);
                    hls.attachMedia(video);
                } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                    video.src = url;
                    video.addEventListener('loadedmetadata', function() {
                        toggleVideoLoading(false);
                    }, { once: true });
                    video.addEventListener('error', function() {
                        toggleVideoLoading(false);
                        videoLoading.textContent = '视频加载失败';
                    }, { once: true });
                } else {
                    toggleVideoLoading(false);
                    videoLoading.textContent = '您的浏览器不支持m3u8格式';
                }
            } else if (trimUrl.endsWith('.mp4')) {
                video.src = url;
                video.addEventListener('loadedmetadata', function() {
                    toggleVideoLoading(false);
                }, { once: true });
                video.addEventListener('error', function() {
                    toggleVideoLoading(false);
                    videoLoading.textContent = 'MP4视频加载失败';
                }, { once: true });
            } else {
                toggleVideoLoading(false);
                videoLoading.textContent = '不支持的视频格式';
            }
        }

        // 日期和农历逻辑
        function getRandomColor() {
            const colors = ['#ff69b4','#00ffff','#ff0080','#9933ff','#ffcc00','#00ff99','#ff6600','#cc66ff'];
            let color1 = colors[Math.floor(Math.random() * colors.length)];
            let color2 = colors[Math.floor(Math.random() * colors.length)];
            while (color2 === color1) color2 = colors[Math.floor(Math.random() * colors.length)];
            return [color1, color2];
        }

        function formatDate() {
            const now = new Date();
            const year = now.getFullYear();
            const month = now.getMonth() + 1;
            const day = now.getDate();
            const weekArr = ['日','一','二','三','四','五','六'];
            const week = weekArr[now.getDay()];
            return `${year}年${month}月${day}日 星期${week}`;
        }

        function getLunarDate(date) {
            const lunarInfo = [0x04bd8,0x04ae0,0x0a570,0x054d5,0x0d260,0x0d950,0x16554,0x056a0,0x09ad0,0x055d2,
                0x04ae0,0x0a5b6,0x0a4d0,0x0d250,0x1d255,0x0b540,0x0d6a0,0x0ada2,0x095b0,0x14977,
                0x04970,0x0a4b0,0x0b4b5,0x06a50,0x06d40,0x1ab54,0x02b60,0x09570,0x052f2,0x04970,
                0x06566,0x0d4a0,0x0ea50,0x06e95,0x05ad0,0x02b60,0x186e3,0x092e0,0x1c8d7,0x0c950,
                0x0d4a0,0x1d8a6,0x0b550,0x056a0,0x1a5b4,0x025d0,0x092d0,0x0d2b2,0x0a950,0x0b557,
                0x06ca0,0x0b550,0x15355,0x04da0,0x0a5b0,0x14573,0x052b0,0x0a9a8,0x0e950,0x06aa0,
                0x0aea6,0x0ab50,0x04b60,0x0aae4,0x0a570,0x05260,0x0f263,0x0d950,0x05b57,0x056a0,
                0x096d0,0x04dd5,0x04ad0,0x0a4d0,0x0d4d4,0x0d250,0x0d558,0x0b540,0x0b6a0,0x195a6,
                0x095b0,0x049b0,0x0a974,0x0a4b0,0x0b27a,0x06a50,0x06d40,0x0af46,0x0ab60,0x09570,
                0x04af5,0x04970,0x064b0,0x074a3,0x0ea50,0x06b58,0x055c0,0x0ab60,0x096d5,0x092e0,
                0x0c960,0x0d954,0x0d4a0,0x0da50,0x07552,0x056a0,0x0abb7,0x025d0,0x092d0,0x0cab5,
                0x0a950,0x0b4a0,0x0baa4,0x0ad50,0x055d9,0x04ba0,0x0a5b0,0x15176,0x052b0,0x0a930,
                0x07954,0x06aa0,0x0ad50,0x05b52,0x04b60,0x0a6e6,0x0a4e0,0x0d260,0x0ea65,0x0d530,
                0x05aa0,0x076a3,0x096d0,0x04bd7,0x04ad0,0x0a4d0,0x1d0b6,0x0d250,0x0d520,0x0dd45,
                0x0b5a0,0x056d0,0x055b2,0x049b0,0x0a577,0x0a4b0,0x0aa50,0x1b255,0x06d20,0x0ada0];
            const nStr1 = ['日','一','二','三','四','五','六','七','八','九','十'];
            const nStr2 = ['初','十','廿','卅'];
            const nStr3 = ['正','二','三','四','五','六','七','八','九','十','冬','腊'];
            
            let year = date.getFullYear();
            let month = date.getMonth() + 1;
            let day = date.getDate();
            
            function getLunarYearDays(y) {
                let sum = 348;
                for(let i = 0x8000; i > 0x8; i >>= 1) sum += (lunarInfo[y-1900] & i) ? 1 : 0;
                return sum + getLeapMonthDays(y);
            }
            function getLeapMonth(y) { return lunarInfo[y-1900] & 0xf; }
            function getLeapMonthDays(y) {
                let lm = getLeapMonth(y);
                return lm == 0 ? 0 : (lunarInfo[y-1900] & 0x10000) ? 30 : 29;
            }
            function getLunarMonthDays(y, m) {
                return (lunarInfo[y-1900] & (0x10000 >> m)) ? 30 : 29;
            }
            
            let i, leap = 0, temp = 0;
            let lunarYear, lunarMonth, lunarDay;
            let offset = (Date.UTC(year, month-1, day) - Date.UTC(1900, 0, 31)) / 86400000;
            
            for(i = 1900; i < 2100 && offset > 0; i++){
                temp = getLunarYearDays(i);
                offset -= temp;
            }
            if(offset < 0){ offset += temp; i--; }
            lunarYear = i;
            leap = getLeapMonth(lunarYear);
            let isLeap = false;
            
            for(i = 1; i < 13 && offset > 0; i++){
                if(leap > 0 && i == (leap + 1) && isLeap == false){
                    --i; isLeap = true; temp = getLeapMonthDays(lunarYear);
                }else{ temp = getLunarMonthDays(lunarYear, i); }
                offset -= temp;
                if(isLeap == true && i == (leap + 1)) isLeap = false;
            }
            if(offset < 0){ offset += temp; i--; }
            lunarMonth = i;
            lunarDay = offset + 1;
            
            let lunarMonthStr = nStr3[lunarMonth - 1] + '月';
            let lunarDayStr = '';
            if(lunarDay == 10) lunarDayStr = '初十';
            else if(lunarDay == 20) lunarDayStr = '二十';
            else if(lunarDay == 30) lunarDayStr = '三十';
            else lunarDayStr = nStr2[Math.floor(lunarDay / 10)] + nStr1[lunarDay % 10];
            
            return `${lunarYear}年${lunarMonthStr}${lunarDayStr}`;
        }

        function initDateFloat() {
            const dateEl = document.getElementById('random-gradient-date');
            const lunarEl = document.getElementById('lunar-date');
            if (!dateEl || !lunarEl) return;
            
            const [c1, c2] = getRandomColor();
            const gradient = `linear-gradient(90deg, ${c1}, ${c2})`;
            dateEl.style.backgroundImage = gradient;
            lunarEl.style.backgroundImage = gradient;
            
            function updateDate() {
                dateEl.textContent = formatDate();
                lunarEl.textContent = getLunarDate(new Date());
            }
            updateDate();
            setInterval(updateDate, 60000);
        }

        // 页面初始化
        document.addEventListener('DOMContentLoaded', function() {
            playVideo(videoUrl);
            initDateFloat();
            updateMusicBtn();

            // 初始化背景动画
            if (typeof initBg === 'function') {
                initBg();
            }
        });
    </script>

<?php
// 自动加载对应背景JS
if (!empty($homeBackground)) {
    $bg_js = str_replace('.css', '.js', $homeBackground);
    echo '<script src="'.$bg_js.'"></script>';
}
?>
</body>
</html>