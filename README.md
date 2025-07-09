# HUGOCMS.net Hugo Publish 插件

一个用于从WordPress后台发布Hugo静态网站的插件，支持一键构建Hugo项目并生成静态文件。

## 插件介绍

该插件允许您在WordPress后台直接执行Hugo命令，构建静态网站。通过简单的配置，您可以将Hugo项目与WordPress集成，实现内容管理与静态网站发布的无缝衔接。

- **版本**：1.1.1
- **作者**：HUGOCMS.net
- **许可证**：GPL-2.0+

## 安装步骤

1. 下载插件压缩包
2. 登录WordPress后台，进入「插件」→「安装插件」→「上传插件」
3. 激活插件

## 准备工作

### 1. 下载Hugo程序

Hugo是一个静态网站生成工具，本插件需要依赖Hugo程序才能正常工作：

1. 访问Hugo官方下载页面：https://gohugo.io/getting-started/installing/
2. 根据您的服务器操作系统（通常是Linux）选择对应的版本（64位）
3. 下载后解压得到可执行文件（文件名为`hugo`，无扩展名）
4. 将`hugo`文件上传到插件目录：`/wp-content/plugins/hugo-publish/`
5. 设置执行权限：
   ```bash
   chmod +x /wp-content/plugins/hugo-publish/hugo
   ```

### 2. 部署Hugo项目

1. **创建项目目录**：
   插件默认的Hugo项目目录为：
   ```
   /wp-content/hugo/_default_project/
   ```
   您可以通过FTP或SSH创建该目录。

2. **部署Hugo项目文件**：
   将您的Hugo项目文件（包括`content`、`layouts`、`themes`、`config.toml`等）上传到上述目录。项目结构示例：
   ```
   _default_project/
   ├── config.toml       # 项目配置文件
   ├── content/          # 内容文件（Markdown）
   ├── layouts/          # 模板文件
   ├── static/           # 静态资源（图片、CSS等）
   └── themes/           # 主题文件
   ```

3. **验证项目目录**：
   确保项目目录权限正确（推荐755）：
   ```bash
   chmod -R 755 /wp-content/hugo/_default_project/
   ```

## 使用方法

1. 登录WordPress后台，点击左侧菜单「Hugo发布」
2. 确认页面显示的路径信息正确：
   - Hugo程序路径：`/wp-content/plugins/hugo-publish/hugo`
   - 项目目录：`/wp-content/hugo/_default_project`
   - 发布目录：`/wp-content/hugo/_default_project/public`
3. 点击「执行Hugo发布」按钮，插件将自动执行Hugo命令
4. 查看执行输出，确认发布结果（成功会显示构建统计信息）

## 配置说明

### .htaccess 设置（可选）

如果您希望Hugo生成的静态文件优先于WordPress默认文件加载，需要手动修改WordPress根目录下的`.htaccess`文件，添加以下规则（放在`# BEGIN WordPress`之前）：

```apache
# HUGO 静态文件优先规则
RewriteEngine On

# 检查Hugo静态文件是否存在，如果存在则直接返回
RewriteCond /home/www/kids-apparel.com/html/wp-content/hugo/_default_project/public/$1 -f [OR]
RewriteCond /home/www/kids-apparel.com/html/wp-content/hugo/_default_project/public/$1 -d
RewriteRule ^(.*)$ /home/www/kids-apparel.com/html/wp-content/hugo/_default_project/public/$1 [L]

# 如果Hugo静态文件不存在，则继续WordPress处理
```

> 注意：请将路径替换为您服务器上的实际路径

## 常见问题

### 1. 发布失败，提示"Hugo程序不存在"

- 确认`hugo`文件已上传到`/wp-content/plugins/hugo-publish/`目录
- 检查文件名是否正确（必须为`hugo`，无扩展名）

### 2. 提示"权限不足"或返回码126

- 执行命令设置Hugo程序可执行权限：
  ```bash
  chmod +x /wp-content/plugins/hugo-publish/hugo
  ```
- 确保服务器用户（如www-data、lsws）对Hugo程序和项目目录有访问权限

### 3. 发布成功但静态文件不显示

- 检查.htaccess配置是否正确
- 确认Hugo生成的静态文件存在于`/wp-content/hugo/_default_project/public/`目录

### 4. 如何更新Hugo程序

1. 从Hugo官网下载最新版本的Hugo程序
2. 替换`/wp-content/plugins/hugo-publish/`目录下的`hugo`文件
3. 确保新文件仍有可执行权限

## 日志查看

插件会生成详细的执行日志，位于：
```
/wp-content/plugins/hugo-publish/hugo_publish.log
```
日志包含命令执行记录、文件权限信息和错误详情，便于排查问题。

## 相关链接

- [Hugo官方网站](https://gohugo.io/)
- [HUGOCMS.net](https://hugocms.net/)
- [WordPress官方网站](https://wordpress.org/)
