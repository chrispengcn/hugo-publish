<?php
/**
 * Plugin Name: HUGOCMS.net Hugo Publish
 * Plugin URI: https://hugocms.net/
 * Description: 从WordPress后台发布Hugo网站（仅修改Hugo程序路径）
 * Version: 1.1.1
 * Author: HUGOCMS.net
 * Author URI: https://hugocms.net/
 * License: GPL-2.0+
 */

if (!defined('ABSPATH')) {
    exit;
}

// 仅修改Hugo程序路径，保持项目目录不变
define('PLUGIN_DIR', plugin_dir_path(__FILE__)); // 插件目录
define('HUGO_EXE_PATH', PLUGIN_DIR . 'hugo'); // Hugo程序位于插件目录下
define('HUGO_PROJECT_DIR', ABSPATH . 'wp-content/hugo/_default_project'); // 保持原项目路径
define('HUGO_PUBLIC_DIR', HUGO_PROJECT_DIR . '/public'); // 保持原发布路径
define('STDERR_REDIRECT', ' 2>&1');
define('HUGO_LOG_FILE', PLUGIN_DIR . 'hugo_publish.log'); // 日志在插件目录下

// 定义发布命令（保持项目路径不变）
define("PUBLISH_COMMAND", 
    HUGO_EXE_PATH . 
    " --cleanDestinationDir" . 
    " -DEF" . 
    " -s " . HUGO_PROJECT_DIR . 
    " -d " . HUGO_PUBLIC_DIR . 
    STDERR_REDIRECT
);

class HugoPublish {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_hugo_publish', array($this, 'ajax_hugo_publish'));
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        $this->init_log();
    }
    
    private function init_log() {
        if (!file_exists(dirname(HUGO_LOG_FILE))) {
            mkdir(dirname(HUGO_LOG_FILE), 0755, true);
        }
    }
    
    // 详细日志记录（包含路径、权限、命令）
    private function log($message, $command = '', $file_path = '') {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] $message\n";
        
        if (!empty($command)) {
            $log_entry .= "[执行命令] $command\n";
        }
        
        if (!empty($file_path)) {
            $exists = file_exists($file_path) ? "存在" : "不存在";
            $perms = file_exists($file_path) ? substr(sprintf('%o', fileperms($file_path)), -4) : "N/A";
            $log_entry .= "[文件状态] 路径: $file_path | 是否存在: $exists | 权限: $perms\n";
        }
        
        $log_entry .= "----------------------------------------\n";
        file_put_contents(HUGO_LOG_FILE, $log_entry, FILE_APPEND);
    }
    
    private function is_exec_available() {
        if (!function_exists('exec')) {
            $this->log("exec函数未被PHP支持");
            return false;
        }
        
        $disabled = array_map('trim', explode(',', ini_get('disable_functions')));
        if (in_array('exec', $disabled)) {
            $this->log("exec函数被禁用");
            return false;
        }
        
        return true;
    }
    
    public function activate_plugin() {
        $dirs = [
            PLUGIN_DIR, // 确保插件目录存在
            dirname(HUGO_EXE_PATH), // 确保Hugo程序所在目录存在
            HUGO_PROJECT_DIR,
            HUGO_PUBLIC_DIR
        ];
        
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
                $this->log("激活时创建目录", '', $dir);
            }
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Hugo发布',
            'Hugo发布',
            'manage_options',
            'hugo-publish',
            array($this, 'render_admin_page'),
            'dashicons-cloud-upload',
            30
        );
    }
    
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('您没有权限访问此页面。');
        }
        ?>
        <div class="wrap">
            <h1>Hugo网站发布</h1>
            
            <div class="notice notice-info">
                <p><strong>Hugo程序路径:</strong> <code><?php echo esc_html(HUGO_EXE_PATH); ?></code></p>
                <p><strong>项目源目录:</strong> <code><?php echo esc_html(HUGO_PROJECT_DIR); ?></code></p>
                <p><strong>发布目录:</strong> <code><?php echo esc_html(HUGO_PUBLIC_DIR); ?></code></p>
                <p><strong>调试日志:</strong> <code><?php echo esc_html(HUGO_LOG_FILE); ?></code></p>
            </div>
            
            <div class="notice notice-warning">
                <p><strong>.htaccess配置规则：</strong></p>
                <pre style="background:#f9f9f9; padding:10px; border:1px solid #ddd;"># 放在# BEGIN WordPress之前
RewriteEngine On
RewriteCond <?php echo HUGO_PUBLIC_DIR; ?>/$1 -f [OR]
RewriteCond <?php echo HUGO_PUBLIC_DIR; ?>/$1 -d
RewriteRule ^(.*)$ <?php echo HUGO_PUBLIC_DIR; ?>/$1 [L]</pre>
            </div>
            
            <div id="result-success" class="updated notice-success" style="display:none;">
                <p>发布成功！</p>
            </div>
            
            <div id="result-error" class="error notice-error" style="display:none;">
                <p>发布失败！请查看日志获取详情。</p>
            </div>
            
            <button id="publish-btn" class="button button-primary">执行Hugo发布</button>
            
            <div id="output" style="margin-top:20px; padding:10px; background:#f1f1f1; display:none;">
                <h4>执行输出：</h4>
                <pre id="output-content" style="max-height:400px; overflow:auto; white-space:pre-wrap;"></pre>
            </div>
            
            <script>
                jQuery(function($) {
                    $('#publish-btn').click(function() {
                        $(this).prop('disabled', true).text('发布中...');
                        $('#result-success, #result-error').hide();
                        $('#output').show();
                        $('#output-content').text('开始执行发布命令...');
                        
                        $.post(ajaxurl, {action: 'hugo_publish'}, function(res) {
                            $('#output-content').text(res.output);
                            if (res.success) {
                                $('#result-success').show();
                            } else {
                                $('#result-error').show();
                            }
                            $('#publish-btn').prop('disabled', false).text('执行Hugo发布');
                        });
                    });
                });
            </script>
        </div>
        <?php
    }
    
    public function ajax_hugo_publish() {
        $output = [];
        $output[] = "=== " . date('Y-m-d H:i:s') . " 开始发布 ===";
        $output[] = "Hugo程序路径: " . HUGO_EXE_PATH;
        
        // 检查exec可用性
        if (!$this->is_exec_available()) {
            $output[] = "错误：exec函数不可用，无法执行命令";
            wp_send_json(['success' => false, 'output' => implode("\n", $output)]);
        }
        
        // 检查Hugo程序是否存在
        if (!file_exists(HUGO_EXE_PATH)) {
            $output[] = "错误：Hugo程序不存在，请确认是否已将Hugo程序放置在插件目录下";
            $this->log("Hugo程序不存在", '', HUGO_EXE_PATH);
            wp_send_json(['success' => false, 'output' => implode("\n", $output)]);
        }
        
        // 检查是否可执行
        if (!is_executable(HUGO_EXE_PATH)) {
            $output[] = "错误：Hugo程序不可执行（权限不足）";
            $output[] = "当前权限：" . substr(sprintf('%o', fileperms(HUGO_EXE_PATH)), -4);
            $output[] = "建议执行命令：chmod +x " . HUGO_EXE_PATH;
            $this->log("Hugo程序不可执行", '', HUGO_EXE_PATH);
            wp_send_json(['success' => false, 'output' => implode("\n", $output)]);
        }
        
        // 检查项目目录是否存在
        if (!file_exists(HUGO_PROJECT_DIR)) {
            $output[] = "错误：项目源目录不存在，请确认路径：" . HUGO_PROJECT_DIR;
            $this->log("项目源目录不存在", '', HUGO_PROJECT_DIR);
            wp_send_json(['success' => false, 'output' => implode("\n", $output)]);
        }
        
        // 执行发布命令
        $publish_cmd = PUBLISH_COMMAND;
        $output[] = "执行发布命令：" . $publish_cmd;
        $this->log("开始执行发布命令", $publish_cmd, HUGO_EXE_PATH);
        
        exec($publish_cmd, $cmd_output, $return_code);
        $output = array_merge($output, $cmd_output);
        $output[] = "=== 执行完成 ===";
        $output[] = "返回码：" . $return_code . "（0表示成功）";
        
        $this->log("发布命令执行完成，返回码：$return_code", $publish_cmd);
        
        wp_send_json([
            'success' => $return_code === 0,
            'output' => implode("\n", $output)
        ]);
    }
}

new HugoPublish();
