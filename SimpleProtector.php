<?php
/**
 * OPcache 代码保护工具
 * 
 * 使用方法：
 *      CLI模式: 
 *          php protector.php --dir /path/to/src --dir /path/to/another [--file /path/to/file.php]
 *      API模式:
 *          $protector = new SimpleProtector(['/path/to/src1', '/path/to/src2']);
 *          $protector->setVerbose(true);
 *          $protector->run();
 */

namespace CodeProtector;

class SimpleProtector {
    private string $rawPhpWrapperTemplate = <<< PHP_DATA
%s
exit;
PHP_DATA;

    private array $sourceDirs = [];
    private array $sourceFiles = [];
    private string $currentScript;
    private string $cacheDir;
    private string $cacheFolder; // 缓存文件夹名称
    private array $skippedFiles = [];
    private array $processedFiles = [];
    private array $cacheWrittenFiles = [];
    private int $cachePermission = 0755;
    private int $ownerId = 1000;
    private int $groupId = 1000;
    private $progressCallback = null;
    private string $opcacheHeader = 'OPCACHE';
    private string $phpWrapper; // 编码后的伪装前缀
    private bool $verbose = false;
    private bool $debugMode = false;

    public function __construct(array $sourceDirs = [], array $sourceFiles = [], bool $debugMode = false) {
        $this->currentScript = realpath($_SERVER['SCRIPT_FILENAME']);
        $this->debugMode = $debugMode;
        $this->validateSource($sourceDirs, $sourceFiles);
        
        // 获取并验证 OPcache 配置
        $cacheDir = ini_get('opcache.file_cache');
        if (empty($cacheDir)) {
            throw new \RuntimeException("未配置 opcache.file_cache");
        }
        $this->cacheDir = $cacheDir;
        
        // 预获取缓存文件夹名称
        $this->cacheFolder = $this->getCacheFolderName();
        
        $this->generatePhpWrapper();
        $this->validateEnvironment();
        $this->setDefaultProgressCallback();
    }

    public function setDebugMode(bool $debugMode): self {
        $this->debugMode = $debugMode;
        $this->generatePhpWrapper();
        return $this;
    }

    public function setVerbose(bool $verbose): self {
        $this->verbose = $verbose;
        return $this;
    }

    private function generatePhpWrapper(): void {
        // 动态生成调试代码段
        $debugCode = $this->debugMode ? 
            "error_reporting(E_ALL);\nini_set('display_errors', 1);" : 
            "";

        // 拼接模板
        $rawWrapper = sprintf(
            $this->rawPhpWrapperTemplate,
            $debugCode
        );

        if ($this->debugMode) {
            // 调试模式：禁用 eval，直接输出明文代码
            $this->phpWrapper = "<?php\n{$rawWrapper}\n?>";
        } else {
            // 生产模式：启用 eval+base64 隐藏
            $encoded = base64_encode($rawWrapper);
            $this->phpWrapper = "<?php eval(base64_decode('{$encoded}'));?>";
        }
    }

    public function run(): void {
        try {
            $this->reportProgress("开始处理文件...", 'info');
            $this->processFiles();
            $this->reportProgress("设置缓存目录权限...", 'info');
            $this->setCachePermissions();
            $this->reportProgress("将缓存内容写回源文件...", 'info');
            $this->writeCacheToSourceFiles();
            $this->reportProgress("处理完成!", 'info');
        } catch (\Exception $e) {
            $this->reportProgress("错误: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    private function processFiles(): void {
        // 处理目录
        foreach ($this->sourceDirs as $dir) {
            $this->reportProgress("处理目录: $dir", 'info');
            foreach ($this->getPhpFiles($dir) as $file) {
                $this->processFile($file);
            }
        }
        
        // 处理单个文件
        foreach ($this->sourceFiles as $file) {
            $this->reportProgress("处理单个文件: $file", 'info');
            $this->processFile($file);
        }
    }

    private function processFile(string $file): void {
        try {
            $cacheFile = $this->getCacheFilePath($file);
            $isProtected = $this->isFileProtected($file);
    
            // 若源文件未被保护，则重新生成缓存
            if (!$isProtected) {
                // 删除旧缓存文件
                if (file_exists($cacheFile)) {
                    unlink($cacheFile);
                    $this->reportProgress("已删除旧缓存文件: $cacheFile", 'info');
                }
            
                // 重新编译文件
                if (!opcache_compile_file($file)) {
                    throw new \RuntimeException("缓存生成失败: $file");
                }
                $this->reportProgress("已重新生成缓存: $file", 'success');
                $this->cacheWrittenFiles[] = $file;
            } else {
                // 处理已保护的文件
                $this->skippedFiles[] = $file;
                $this->reportProgress("文件已受保护，跳过回写: $file", 'skip');
                
                $this->rebuildCacheFromSource($file, $cacheFile);
                $this->reportProgress("已从源文件重建缓存: $file", 'info');
            }
            
            $this->processedFiles[] = $file;
        } catch (\Throwable $e) {
            $this->reportProgress("处理文件时出错 (跳过): $file - " . $e->getMessage(), 'error');
        }
    }

    /**
     * 从已保护的源文件重建缓存文件
     */
    private function rebuildCacheFromSource(string $sourceFile, string $cacheFile): void {
        $content = file_get_contents($sourceFile);
        if ($content === false) {
            throw new \RuntimeException("无法读取源文件: $sourceFile");
        }

        // 1. 定位伪装前缀的结束位置
        $prefixLength = strlen($this->phpWrapper);
        if (substr($content, 0, $prefixLength) !== $this->phpWrapper) {
            throw new \RuntimeException("文件未被正确保护，缺少伪装前缀: $sourceFile");
        }

        // 2. 提取 OPcache 头部及字节码部分
        $opcacheStart = $prefixLength + 1; // 跳过伪装前缀后的换行符
        $opcodeWithHeader = substr($content, $opcacheStart);

        // 3. 验证 OPcache 头部（确保字节码有效性）
        $headerLength = strlen($this->opcacheHeader);
        if (substr($opcodeWithHeader, 0, $headerLength) !== $this->opcacheHeader) {
            throw new \RuntimeException("OPcache 头部验证失败: $sourceFile");
        }

        // 4. 提取真正的 OPcache 字节码（去除头部）
        $opcode = substr($opcodeWithHeader, $headerLength);

        // 5. 创建缓存目录（确保权限正确）
        $cacheDir = dirname($cacheFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, $this->cachePermission, true);
            $this->recursiveChmod($cacheDir, $this->cachePermission); // 继承权限设置
            $this->recursiveChown($cacheDir); // 继承所有者设置
        }

        // 6. 写入缓存文件（包含完整 OPcache 头部，与 opcache_compile_file 生成的格式一致）
        if (false === file_put_contents($cacheFile, $this->opcacheHeader . $opcode)) {
            throw new \RuntimeException("缓存文件写入失败: $cacheFile");
        }

        $this->reportProgress("成功从源文件重建缓存: $sourceFile", 'info');
    }

    /**
     * 检查文件是否已经被保护
     */
    private function isFileProtected(string $filePath): bool {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return false;
        }
        
        // 快速检查文件是否以伪装前缀开头
        $content = file_get_contents($filePath, false, null, 0, strlen($this->phpWrapper));
        return $content === $this->phpWrapper;
    }

    private function getCacheFilePath(string $filePath): string {
        $cacheFolder = $this->getCachePath();
        return $cacheFolder . DIRECTORY_SEPARATOR . $filePath . '.bin';
    }

    private function setCachePermissions(): void {
        $this->reportProgress("开始设置缓存目录权限...", 'info');
        
        // 递归设置目录和文件权限
        $this->recursiveChmod($this->cacheDir, $this->cachePermission);
        $this->recursiveChown($this->cacheDir);
        
        $this->reportProgress("缓存目录权限设置完成", 'info');
    }

    private function recursiveChmod(string $path, int $mode): void {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            chmod($file->getRealPath(), $mode);
        }
    }

    private function recursiveChown(string $path): void {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            chown($file->getRealPath(), $this->ownerId);
            chgrp($file->getRealPath(), $this->groupId);
        }
    }

    private function writeCacheToSourceFiles(): void {
        $this->reportProgress("开始将缓存内容写回源文件...", 'info');
        
        foreach ($this->cacheWrittenFiles as $file) {
            $cacheFile = $this->getCacheFilePath($file);
            if (file_exists($cacheFile)) {
                $cacheContent = file_get_contents($cacheFile);
                if ($cacheContent === false) {
                    throw new \RuntimeException("无法读取缓存文件: $cacheFile");
                }
                
                // 提取真正的OPcache字节码（去掉头部）
                $opcode = substr($cacheContent, strlen($this->opcacheHeader));
                
                // 组合：伪装前缀 + 原始OPcache字节码
                $protectedContent = $this->phpWrapper . "\n" . $this->opcacheHeader . $opcode;
                
                if (false === file_put_contents($file, $protectedContent)) {
                    throw new \RuntimeException("文件写入失败: $file");
                }
                
                $this->reportProgress("已将缓存内容写回: $file", 'success');
            } else {
                throw new \RuntimeException("缓存文件不存在: $cacheFile");
            }
        }
    }

    private function getPhpFiles(string $dir): array {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $dir,
                \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS
            ),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $realPath = $file->getRealPath();
                if ($realPath !== $this->currentScript) {
                    $files[] = $realPath;
                }
            }
        }

        return $files;
    }

    private function reportProgress(string $message, string $type = 'info'): void {
        if (is_callable($this->progressCallback)) {
            call_user_func($this->progressCallback, $message, $type);
        }
    }

    // 设置缓存目录和文件权限
    public function setCachePermission(int $mode): self {
        $this->cachePermission = $mode;
        return $this;
    }

    // 设置所有者ID
    public function setOwnerId(int $ownerId): self {
        $this->ownerId = $ownerId;
        return $this;
    }

    // 设置组ID
    public function setGroupId(int $groupId): self {
        $this->groupId = $groupId;
        return $this;
    }

    // 设置进度回调
    public function setProgressCallback($callback): self {
        $this->progressCallback = $callback;
        return $this;
    }

    // 获取跳过的文件列表
    public function getSkippedFiles(): array {
        return $this->skippedFiles;
    }

    // 获取处理的文件列表
    public function getProcessedFiles(): array {
        return $this->processedFiles;
    }

    // 获取源目录列表
    public function getSourceDirs(): array {
        return $this->sourceDirs;
    }

    // 获取缓存文件夹名称
    public function getCacheFolderName(): string {
        $iterator = new \DirectoryIterator($this->cacheDir);
        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isDir() && !$fileinfo->isDot()) {
                return $fileinfo->getFilename();
            }
        }
        throw new \RuntimeException("未找到缓存文件夹");
    }

    // 获取完整的缓存目录路径
    public function getCachePath(): string {
        $folderName = $this->getCacheFolderName();
        return $this->cacheDir . DIRECTORY_SEPARATOR . $folderName;
    }

    private function validateSource(array $dirs, array $files): void {
        foreach ($dirs as $dir) {
            $realPath = realpath($dir);
            if (!$realPath || !is_dir($realPath)) {
                throw new \InvalidArgumentException("无效的源目录: $dir");
            }
            $this->sourceDirs[] = $realPath;
        }
        
        foreach ($files as $file) {
            $realPath = realpath($file);
            if (!$realPath || !is_file($realPath)) {
                throw new \InvalidArgumentException("无效的源文件: $file");
            }
            $this->sourceFiles[] = $realPath;
        }
        
        if (empty($this->sourceDirs) && empty($this->sourceFiles)) {
            throw new \InvalidArgumentException("至少需要指定一个源目录或文件");
        }
    }

    private function validateEnvironment(): void {
        if (!extension_loaded('Zend OPcache')) {
            throw new \RuntimeException("OPcache 扩展未加载");
        }
        
        if (!is_writable($this->cacheDir)) {
            throw new \RuntimeException("缓存目录不可写: " . $this->cacheDir);
        }
    }

    private function setDefaultProgressCallback(): void {
        $this->progressCallback = function($message, $type) {
            if ($this->verbose || in_array($type, ['error', 'success'])) {
                echo sprintf("[%s] %s\n", strtoupper($type), $message);
            }
        };
    }
}

// CLI接口
if (php_sapi_name() === 'cli' && !defined('STDIN')) {
    define('STDIN', fopen('php://stdin', 'r'));
}

if (php_sapi_name() === 'cli' && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    try {
        $options = getopt('', ['dir:', 'file:', 'help', 'verbose', 'debug']);
        
        if (isset($options['help'])) {
            echo "Usage: php " . basename(__FILE__) . " --dir /path/to/src --file /path/to/file.php [--debug] [--verbose]\n";
            echo "Options:\n";
            echo "  --dir     指定要处理的目录，可以多次使用\n";
            echo "  --file    指定要处理的单个文件，可以多次使用\n";
            echo "  --debug   启用调试模式（明文代码+错误显示）\n";
            echo "  --verbose 显示详细处理信息\n";
            echo "  --help    显示此帮助信息\n";
            exit(0);
        }
        
        $dirs = isset($options['dir']) ? (array)$options['dir'] : [];
        $files = isset($options['file']) ? (array)$options['file'] : [];
        $debugMode = isset($options['debug']);
        $verbose = isset($options['verbose']);
        
        if (empty($dirs) && empty($files)) {
            throw new \InvalidArgumentException("请至少指定一个目录(--dir)或文件(--file)");
        }
        
        $protector = new SimpleProtector($dirs, $files, $debugMode);
        
        // 设置详细输出模式
        $isVerbose = isset($options['verbose']);
        $protector->setVerbose($isVerbose);
        
        // 设置自定义进度回调
        $protector->setProgressCallback(function($message, $type) use ($isVerbose) {
            $colors = [
                'error' => "\033[31m",  // 红色
                'success' => "\033[32m", // 绿色
                'warning' => "\033[33m", // 黄色
                'info' => "\033[34m",   // 蓝色
                'skip' => "\033[35m"    // 紫色
            ];
            $reset = "\033[0m";
            
            $coloredType = isset($colors[$type]) ? $colors[$type] . strtoupper($type) . $reset : strtoupper($type);
            
            // 只在详细模式下显示INFO和SKIP消息
            if ($isVerbose || in_array($type, ['error', 'success'])) {
                echo sprintf("[%s] %s\n", $coloredType, $message);
            }
        });
        
        $protector->run();
        
        // 输出统计信息
        echo str_repeat("-", 50) . "\n";
        echo "处理文件总数: " . count($protector->getProcessedFiles()) . "\n";
        echo "跳过回写文件数: " . count($protector->getSkippedFiles()) . "\n";
        echo str_repeat("-", 50) . "\n";
        
    } catch (\Exception $e) {
        echo "\033[31m错误: " . $e->getMessage() . "\033[0m\n";
        exit(1);
    }
}