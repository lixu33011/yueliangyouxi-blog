// api/index.js（最简稳定版）
const { execFile } = require('child_process');
const path = require('path');
const fs = require('fs');

module.exports = (req, res) => {
  try {
    // 1. 确定要执行的 PHP 文件（极简匹配）
    let phpScript = '';
    const url = req.url.split('?')[0];

    if (url === '/' || url === '/index.php') {
      phpScript = path.join(__dirname, '../index.php');
    } else if (url === '/article-list.php') {
      phpScript = path.join(__dirname, '../article-list.php');
    } else if (url === '/article-detail.php') {
      phpScript = path.join(__dirname, '../article-detail.php');
    } else if (url === '/admin' || url === '/admin/index.php') {
      phpScript = path.join(__dirname, '../admin/index.php');
    } else {
      // 静态资源直接返回（避免转发到 PHP）
      const staticPath = path.join(__dirname, '..', url);
      if (fs.existsSync(staticPath)) {
        return res.sendFile(staticPath);
      }
      return res.status(404).send('Not Found');
    }

    // 2. 验证 PHP 文件存在
    if (!fs.existsSync(phpScript)) {
      return res.status(404).send(`PHP 文件不存在: ${phpScript}`);
    }

    // 3. 构造基础环境变量（仅保留核心）
    const env = {
      REQUEST_METHOD: req.method,
      REQUEST_URI: req.url,
      QUERY_STRING: req.url.split('?')[1] || '',
      DOCUMENT_ROOT: path.join(__dirname, '../')
    };

    // 4. 执行 PHP（使用 Vercel 内置 PHP 7.4）
    execFile(
      '/usr/bin/php', // Vercel 内置 PHP 7.4 固定路径
      [phpScript],
      { env, encoding: 'utf8' },
      (err, stdout, stderr) => {
        if (err) {
          console.error('PHP 执行错误:', err, stderr);
          return res.status(500).send(`PHP 错误: ${stderr || err.message}`);
        }
        // 直接返回 PHP 输出（不解析响应头，简化逻辑）
        res.setHeader('Content-Type', 'text/html; charset=utf-8');
        res.status(200).send(stdout);
      }
    );
  } catch (globalErr) {
    console.error('函数崩溃:', globalErr);
    res.status(500).send(`服务器错误: ${globalErr.message}`);
  }
};
