// 在 api/index.js 开头添加
const staticExts = ['.css', '.json', '.jpg', '.png'];
const requestPath = req.url.split('?')[0];
if (staticExts.some(ext => requestPath.endsWith(ext))) {
  const staticPath = path.join(__dirname, '..', requestPath);
  if (fs.existsSync(staticPath)) {
    return res.sendFile(staticPath);
  }
}

const { execFile } = require('child_process');
const path = require('path');
const fs = require('fs');
const bodyParser = require('body-parser');

// 解析 POST 请求体
const parseBody = bodyParser.json({ extended: true });

// Vercel Serverless 主函数
module.exports = (req, res) => {
  // 第一步：解析 POST 数据
  parseBody(req, res, async () => {
    try {
      // 1. 确定要执行的 PHP 文件路径（核心适配）
      let phpScriptPath = '';
      const requestPath = req.url.split('?')[0]; // 去除 URL 参数

      // 匹配不同的 PHP 文件
      if (requestPath === '/' || requestPath === '/index.php') {
        phpScriptPath = path.join(__dirname, '../index.php');
      } else if (requestPath === '/article-list.php') {
        phpScriptPath = path.join(__dirname, '../article-list.php');
      } else if (requestPath === '/article-detail.php') {
        phpScriptPath = path.join(__dirname, '../article-detail.php');
      } else if (requestPath === '/admin' || requestPath === '/admin/index.php') {
        phpScriptPath = path.join(__dirname, '../admin/index.php');
      } else {
        // 匹配其他自定义 PHP 文件（可选）
        phpScriptPath = path.join(__dirname, '..', requestPath);
      }

      // 验证 PHP 文件是否存在
      if (!fs.existsSync(phpScriptPath)) {
        return res.status(404).send(`PHP 文件不存在：${requestPath}`);
      }

      // 2. 构造 PHP 运行环境变量（模拟原生 PHP 环境）
      const env = {
        ...process.env,
        // 核心：恢复 PHP 全局变量
        REQUEST_METHOD: req.method,
        REQUEST_URI: req.url,
        QUERY_STRING: req.url.split('?')[1] || '',
        CONTENT_TYPE: req.headers['content-type'] || '',
        POST_DATA: req.body ? JSON.stringify(req.body) : '',
        PHP_SELF: phpScriptPath,
        SCRIPT_FILENAME: phpScriptPath,
        DOCUMENT_ROOT: path.join(__dirname, '../'), // 项目根目录
        HTTP_HOST: req.headers.host || '',
        HTTPS: req.headers['x-forwarded-proto'] === 'https' ? 'on' : 'off'
      };

      // 3. 执行 PHP 脚本（调用 Vercel 内置 PHP 7.4）
      execFile(
        '/usr/bin/php', // Vercel 内置 PHP 7.4 路径
        [phpScriptPath],
        { env: env, encoding: 'utf8', timeout: 10000 }, // 超时 10 秒
        (error, stdout, stderr) => {
          if (error) {
            console.error('PHP 执行错误：', error, stderr);
            return res.status(500).send(`
              <h2>PHP 执行失败</h2>
              <p>错误信息：${stderr}</p>
              <p>文件路径：${phpScriptPath}</p>
            `);
          }

          // 4. 解析 PHP 输出（分离响应头和内容）
          const [headersStr, ...bodyParts] = stdout.split('\n\n');
          const body = bodyParts.join('\n\n');

          // 解析并设置 PHP 输出的响应头（如 Content-Type/Cookie 等）
          if (headersStr) {
            headersStr.split('\n').forEach(header => {
              const [key, value] = header.split(': ');
              if (key && value) {
                res.setHeader(key.trim(), value.trim());
              }
            });
          }

          // 5. 返回 PHP 执行结果
          res.status(200).send(body || stdout);
        }
      );
    } catch (err) {
      console.error('Node.js 中转错误：', err);
      res.status(500).send(`服务器内部错误：${err.message}`);
    }
  });
};
