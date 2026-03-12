<?php
require_once 'common.php';

// 获取文章ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: article-list.php');
    exit;
}

// 读取配置
$config = readJsonFile('config.json');
$personalInfo = getPersonalInfo();
$articles = readJsonFile('articles.json');
$article = null;
foreach ($articles as $item) {
    if ($item['id'] == $id) {
        $article = $item;
        break;
    }
}

// 文章不存在则返回列表
if (!$article) {
    header('Location: article-list.php');
    exit;
}

// 获取选中的样式文件
$detailTemplate = getSelectedAsset('article_detail_template');
$detailBackground = getSelectedAsset('article_detail_background');
$bgMusic = $config['bg_music']['article_detail_url'];
$bgVolume = $config['bg_music']['article_detail_volume'];
$autoPlay = $config['bg_music']['auto_play'] ? 'autoplay' : '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['title']); ?> - 月亮有喜的博客</title>
    <!-- 引入图标库 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- 背景样式 -->
    <link rel="stylesheet" href="<?php echo $detailBackground; ?>">
    <!-- 详情模板样式 -->
    <link rel="stylesheet" href="<?php echo $detailTemplate; ?>">
    <style>
    
    /* 新增：日期悬浮模块样式（移除边框） */
.date-float-container {
    position: fixed;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    z-index: 9999; /* 最高层级，避免被遮挡 */
    background: rgba(0, 0, 0, 0.1);
    /* 移除边框：删除 border 这一行 */
    border-bottom-left-radius: 10px;
    border-bottom-right-radius: 10px;
    padding: 8px 20px;
    margin-top: 0;
    transition: all 0.3s ease;
}
.date-float-container:hover {
    transform: translateX(-50%) scale(1.05); /* 悬浮放大 */
    box-shadow: 0 0 15px rgba(255, 105, 180, 0.8);
}
.date-text {
    font-family: "Comic Sans MS", "幼圆", sans-serif;
    font-size: 38px;
    font-weight: bold;
    background-clip: text;
    -webkit-background-clip: text; /* 兼容webkit内核 */
    color: transparent; /* 透明文字透出渐变 */
    letter-spacing: 2px; /* 字间距更美观 */
}


/* 农历样式 - 字体大小为日期的一半 */
.lunar-text {
    font-family: "Comic Sans MS", "幼圆", sans-serif;
    font-size: 22px; /* 日期字体18px的一半 */
    background-clip: text;
    -webkit-background-clip: text;
    color: transparent;
    text-align: center;
    margin-top: 2px;
    letter-spacing: 1px;
}
/* 移动端适配 - 农历字体同步缩小 */
@media (max-width: 768px) {
    .lunar-text {
        font-size: 18px; /* 日期移动端16px的一半 */
    }
}
/* 响应式适配（和现有博客一致） */
@media (max-width: 768px) {
    .date-text {
        font-size: 16px;
    }
    .date-float-container {
        padding: 6px 15px;
    }
}
        /* 全局通用样式 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Comic Sans MS", "幼圆", sans-serif;
        }
        a {
            text-decoration: none;
        }
        /* 背景音乐控制 */
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
        .music-btn, .home-btn, .back-btn {
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
        .music-btn:hover, .home-btn:hover, .back-btn:hover {
            background: #ff87b9;
        }
        /* 个人信息样式 */
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
            width: 70px;
            height: 70px;
            border-radius: 50%;
            border: 2px solid #00ffff;
            object-fit: cover;
        }
        .personal-info .nickname {
            font-size: 18px;
            color: #ff69b4;
            margin-bottom: 5px;
        }
        .personal-info .intro {
            font-size: 14px;
            color: #ccc;
            max-width: 250px;
        }
        /* 文章详情容器 - 响应式 */
        .article-detail-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 120px 20px 50px;
            color: #fff;
        }
        .article-detail-title {
            color: #ff69b4;
            font-size: 36px;
            text-align: center;
            margin-bottom: 20px;
            text-shadow: 0 0 10px #00ffff;
            padding-bottom: 15px;
            border-bottom: 2px solid #ff69b4;
        }
        .article-detail-meta {
            font-size: 16px;
            color: #ccc;
            text-align: center;
            margin-bottom: 30px;
        }
        .article-detail-content {
            background: rgba(0,0,0,0.6);
            border: 1px solid #00ffff;
            border-radius: 10px;
            padding: 30px;
            line-height: 1.8;
            font-size: 16px;
        }
        .article-detail-content img {
            max-width: 100%;
            border-radius: 8px;
            margin: 15px 0;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        .article-detail-content video, 
        .article-detail-content audio {
            max-width: 100%;
            margin: 15px 0;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        .back-btn {
            margin: 30px auto 0;
            display: block;
            width: 150px;
            text-align: center;
            justify-content: center;
        }
        /* 响应式适配 */
        @media (max-width: 768px) {
            .personal-info {
                flex-direction: column;
                padding: 10px;
                top: auto;
                bottom: 20px;
                left: 20px;
                width: calc(100% - 40px);
                max-width: 300px;
            }
            .personal-info .intro {
                max-width: 100%;
                text-align: center;
            }
            .music-control {
                top: auto;
                bottom: 20px;
                right: 20px;
                flex-wrap: wrap;
                justify-content: center;
            }
            .article-detail-container {
                padding-top: 80px;
            }
            .article-detail-title {
                font-size: 28px;
            }
        }
    </style>
</head>
<body class="bg-container">
    <!-- 动态背景Canvas（按需加载） -->
    <?php if (mb_strpos($detailBackground, 'bg1.css') !== false || mb_strpos($detailBackground, 'bg3.css') !== false): ?>
    <canvas id="bg-canvas"></canvas>
    <?php endif; ?>
<!-- 新增：日期悬浮模块 -->
<div class="date-float-container">
    <div class="date-text" id="random-gradient-date"></div>
        <div class="lunar-text" id="lunar-date"></div>
</div>
    <!-- 个人信息 -->
    <div class="personal-info">
        <img src="uploads/<?php echo $personalInfo['avatar']; ?>" alt="头像" class="avatar" onerror="this.src='https://picsum.photos/70/70';">
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

    <!-- 文章详情容器 -->
    <div class="article-detail-container">
        <h1 class="article-detail-title"><?php echo htmlspecialchars($article['title']); ?></h1>
        <div class="article-detail-meta">发布时间：<?php echo htmlspecialchars($article['create_time']); ?></div>
        <div class="article-detail-content">
            <?php echo $article['content']; ?>
        </div>
        <button class="back-btn" onclick="window.location.href='article-list.php'">
            <i class="fas fa-arrow-left"></i> 返回列表
        </button>
    </div>

    <!-- 核心脚本（音乐+背景） -->
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
                        alert('播放失败！原因：\n1. 音乐文件路径错误\n2. 浏览器安全策略要求手动交互\n3. 音频格式不支持');
                    });
                }
            } catch (e) {
                console.error('播放控制异常:', e);
                alert('音乐播放控制出错，请检查浏览器控制台！');
            }
        }

        // 玄色光晕背景动画
        function initBg1() {
            const canvas = document.getElementById('bg-canvas');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            let width = window.innerWidth;
            let height = window.innerHeight;
            canvas.width = width;
            canvas.height = height;

            class Particle {
                constructor() {
                    this.x = Math.random() * width;
                    this.y = Math.random() * height;
                    this.radius = Math.random() * 100 + 50;
                    this.speedX = Math.random() * 0.5 - 0.25;
                    this.speedY = Math.random() * 0.5 - 0.25;
                    this.color = `rgba(${Math.random()*100}, ${Math.random()*50}, ${Math.random()*150}, ${Math.random()*0.3+0.1})`;
                }
                update() {
                    this.x += this.speedX;
                    this.y += this.speedY;
                    if (this.x < -this.radius) this.x = width + this.radius;
                    if (this.x > width + this.radius) this.x = -this.radius;
                    if (this.y < -this.radius) this.y = height + this.radius;
                    if (this.y > height + this.radius) this.y = -this.radius;
                }
                draw() {
                    ctx.beginPath();
                    ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
                    ctx.fillStyle = this.color;
                    ctx.fill();
                    ctx.closePath();
                }
            }

            const particles = [];
            for (let i = 0; i < 20; i++) {
                particles.push(new Particle());
            }

            function animate() {
                requestAnimationFrame(animate);
                ctx.fillStyle = 'rgba(0,0,0,0.1)';
                ctx.fillRect(0, 0, width, height);
                particles.forEach(particle => {
                    particle.update();
                    particle.draw();
                });
            }
            animate();
            window.addEventListener('resize', () => {
                width = window.innerWidth;
                height = window.innerHeight;
                canvas.width = width;
                canvas.height = height;
            });
        }

        // 星空粒子背景动画
        function initBg3() {
            const canvas = document.getElementById('bg-canvas');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            let width = window.innerWidth;
            let height = window.innerHeight;
            canvas.width = width;
            canvas.height = height;

            const stars = [];
            for (let i = 0; i < 500; i++) {
                stars.push({
                    x: Math.random() * width,
                    y: Math.random() * height,
                    radius: Math.random() * 1.5,
                    speed: Math.random() * 0.5
                });
            }

            function animate() {
                requestAnimationFrame(animate);
                ctx.fillStyle = 'rgba(0,0,0,0.2)';
                ctx.fillRect(0, 0, width, height);
                
                ctx.fillStyle = '#fff';
                stars.forEach(star => {
                    star.y += star.speed;
                    if (star.y > height) star.y = 0;
                    ctx.beginPath();
                    ctx.arc(star.x, star.y, star.radius, 0, Math.PI * 2);
                    ctx.fill();
                });
            }
            animate();
            window.addEventListener('resize', () => {
                width = window.innerWidth;
                height = window.innerHeight;
                canvas.width = width;
                canvas.height = height;
            });
        }
        
        
        // 补充缺失：随机颜色生成函数
function getRandomColor() {
    const colors = [
        '#ff69b4', '#00ffff', '#ff0080', '#9933ff', 
        '#ffcc00', '#00ff99', '#ff6600', '#cc66ff'
    ];
    let color1 = colors[Math.floor(Math.random() * colors.length)];
    let color2 = colors[Math.floor(Math.random() * colors.length)];
    while (color2 === color1) {
        color2 = colors[Math.floor(Math.random() * colors.length)];
    }
    return [color1, color2];
}

// 补充缺失：公历日期格式化函数
function formatDate() {
    const now = new Date();
    const year = now.getFullYear();
    const month = now.getMonth() + 1;
    const day = now.getDate();
    const weekArr = ['日', '一', '二', '三', '四', '五', '六'];
    const week = weekArr[now.getDay()];
    return `${year}年${month}月${day}日 星期${week}`;
}
// 新增：公历转农历核心函数
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
    const solarMonth = [31,28,31,30,31,30,31,31,30,31,30,31];
    const nStr1 = ['日','一','二','三','四','五','六','七','八','九','十'];
    const nStr2 = ['初','十','廿','卅'];
    const nStr3 = ['正','二','三','四','五','六','七','八','九','十','冬','腊'];
    
    let year = date.getFullYear();
    let month = date.getMonth() + 1;
    let day = date.getDate();
    
    // 计算农历日期
    let i, leap = 0, temp = 0;
    let lunarYear, lunarMonth, lunarDay;
    let offset = (Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()) - Date.UTC(1900, 0, 31)) / 86400000;
    for(i = 1900; i < 2100 && offset > 0; i++){
        temp = getLunarYearDays(i);
        offset -= temp;
    }
    if(offset < 0){
        offset += temp;
        i--;
    }
    lunarYear = i;
    leap = getLeapMonth(lunarYear);
    let isLeap = false;
    for(i = 1; i < 13 && offset > 0; i++){
        if(leap > 0 && i == (leap + 1) && isLeap == false){
            --i;
            isLeap = true;
            temp = getLeapMonthDays(lunarYear);
        }else{
            temp = getLunarMonthDays(lunarYear, i);
        }
        offset -= temp;
        if(isLeap == true && i == (leap + 1)) isLeap = false;
    }
    if(offset < 0){
        offset += temp;
        i--;
    }
    lunarMonth = i;
    lunarDay = offset + 1;
    
    // 格式化农历文字
    let lunarMonthStr = nStr3[lunarMonth - 1] + '月';
    let lunarDayStr = '';
    if(lunarDay == 10) lunarDayStr = '初十';
    else if(lunarDay == 20) lunarDayStr = '二十';
    else if(lunarDay == 30) lunarDayStr = '三十';
    else{
        lunarDayStr = nStr2[Math.floor(lunarDay / 10)] + nStr1[lunarDay % 10];
    }
    return `${lunarYear}年${lunarMonthStr}${lunarDayStr}`;
    
    // 辅助函数
    function getLunarYearDays(y) {
        let sum = 348;
        for(let i = 0x8000; i > 0x8; i >>= 1) sum += (lunarInfo[y-1900] & i) ? 1 : 0;
        return sum + getLeapMonthDays(y);
    }
    function getLeapMonth(y) {
        return lunarInfo[y-1900] & 0xf;
    }
    function getLeapMonthDays(y) {
        let lm = getLeapMonth(y);
        return lm == 0 ? 0 : (lunarInfo[y-1900] & 0x10000) ? 30 : 29;
    }
    function getLunarMonthDays(y, m) {
        return (lunarInfo[y-1900] & (0x10000 >> m)) ? 30 : 29;
    }
}

// 修改原日期初始化函数（新增农历渲染）
function initDateFloat() {
    const dateElement = document.getElementById('random-gradient-date');
    const lunarElement = document.getElementById('lunar-date'); // 新增
    if (!dateElement || !lunarElement) return;
    
    // 生成随机渐变（日期+农历共用同一种渐变）
    const [color1, color2] = getRandomColor();
    const gradient = `linear-gradient(90deg, ${color1}, ${color2})`;
    dateElement.style.backgroundImage = gradient;
    lunarElement.style.backgroundImage = gradient; // 农历同步渐变
    
    // 显示公历+农历
    const now = new Date();
    dateElement.textContent = formatDate();
    lunarElement.textContent = getLunarDate(now); // 新增
    
    // 每分钟更新
    setInterval(() => {
        const now = new Date();
        dateElement.textContent = formatDate();
        lunarElement.textContent = getLunarDate(now); // 新增
    }, 60000);
}
        // 初始化所有内容（DOM加载完成后）
        document.addEventListener('DOMContentLoaded', function() {
            // 初始化音乐按钮状态
            updateMusicBtn();
            initDateFloat();
            // 初始化背景动画
            <?php if (mb_strpos($detailBackground, 'bg1.css') !== false): ?>
            initBg1();
            <?php elseif (mb_strpos($detailBackground, 'bg3.css') !== false): ?>
            initBg3();
            <?php endif; ?>
        });
    </script>
</body>
</html>