#!/bin/sh

# 启动服务
nginx
php-fpm

# 配置参数
WATCH_DIR="/home/WeEngine/addons/"
CACHE_BASE="/usr/tmp/php_cache/"
PROTECTOR_SCRIPT="/home/WeEngine/SimpleProtector.php"
EVENT_MERGE_DELAY=2  # 事件合并延迟（秒）
PHP_INI="/usr/local/etc/php.ini"  # PHP配置文件路径

# 新的opcache配置
OPCACHE_CONFIG=$(cat <<EOF
[Zend Opcache]
opcache.enable=1
opcache.enable_cli=1
opcache.file_cache=/usr/tmp/php_cache
opcache.file_cache_only=1
opcache.validate_timestamps=0
opcache.max_accelerated_files=10000
EOF
)

# 日志函数
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
}

# 错误处理函数
error() {
    log "ERROR: $1"
    exit 1
}

# 更新opcache配置
update_opcache_config() {
    # 创建并配置缓存基础目录
    mkdir -p "$CACHE_BASE" || error "无法创建缓存目录 $CACHE_BASE"
    chmod 755 "$CACHE_BASE" || error "无法设置缓存目录权限"
    chown 1000:1000 "$CACHE_BASE" || error "无法设置缓存目录所有者"

    # 检查php.ini文件是否存在
    if [ ! -f "$PHP_INI" ]; then
        log "警告：PHP配置文件 $PHP_INI 不存在，创建新配置文件"
        echo "$OPCACHE_CONFIG" > "$PHP_INI"
        return
    fi
    
    # 临时文件
    TEMP_INI="/tmp/php.ini.$$"
    
    # 找到[Zend Opcache]部分并删除直到下一个[section]或文件末尾
    awk '/\[Zend Opcache\]/{f=1; next} f && /^\[/{f=0} !f' "$PHP_INI" > "$TEMP_INI"
    
    # 移除可能存在的末尾空行
    awk 'NF || f {print; f=1}' "$TEMP_INI" > "${TEMP_INI}.tmp"
    mv "${TEMP_INI}.tmp" "$TEMP_INI"
    
    # 添加新的opcache配置，确保前后各有一个空行
    echo >> "$TEMP_INI"  # 添加空行
    echo "$OPCACHE_CONFIG" >> "$TEMP_INI"
    echo >> "$TEMP_INI"  # 添加空行
    
    # 替换原文件
    mv "$TEMP_INI" "$PHP_INI"
    
    log "PHP OPcache配置已更新"
}

# 检查保护工具脚本是否存在
[ ! -f "$PROTECTOR_SCRIPT" ] && error "代码保护工具脚本不存在: $PROTECTOR_SCRIPT"

# 输出启动信息
log "---------------------------"
log "PHP缓存监控脚本已启动"
log "监控目录：$WATCH_DIR"
log "保护工具：$PROTECTOR_SCRIPT"
log "---------------------------"

# 更新opcache配置
update_opcache_config

# 初始化项目缓存
php ${PROTECTOR_SCRIPT} --dir ${WATCH_DIR} >/dev/null 2>&1
if [ $? -ne 0 ]; then
    error "项目缓存初始化失败，无法继续"
fi
log "项目缓存初始化已完成"

# 获取缓存目录并确保格式正确
CACHE_DIR=$(find "$CACHE_BASE" -mindepth 1 -maxdepth 1 -type d -print -quit)
[ -z "$CACHE_DIR" ] && error "初始化后仍未找到缓存子目录！"
case "$CACHE_DIR" in
    */) ;;
    *) CACHE_DIR="$CACHE_DIR/" ;;
esac

# log "缓存目录：$CACHE_DIR"

# 存储后台进程PID的变量
INOTIFY_PROJECT_PID=
INOTIFY_CACHE_PID=
EVENT_PROCESSOR_PIDS=""
EVENT_TIMERS_FILE="/tmp/event_timers.$$"

# 添加PID到列表
add_pid() {
    EVENT_PROCESSOR_PIDS="${EVENT_PROCESSOR_PIDS} $1"
}

# 创建命名管道
create_fifo() {
    local fifo_path="$1"
    [ ! -p "$fifo_path" ] && rm -f "$fifo_path" && mkfifo "$fifo_path" || error "无法创建命名管道 $fifo_path"
}

# 安全更新计时器文件
update_timer_file() {
    local temp_file="/tmp/event_timers_temp.$$"
    cp "$EVENT_TIMERS_FILE" "$temp_file"
    grep -v "^${1}|" "$temp_file" > "$EVENT_TIMERS_FILE"
    rm -f "$temp_file"
}

# 安排延迟执行的任务
schedule_task() {
    local file_path="$1"
    local action="$2"
    
    # 已有计划任务则跳过
    [ -f "$EVENT_TIMERS_FILE" ] && grep -q "^${file_path}|" "$EVENT_TIMERS_FILE" && return
    
    (
        sleep "$EVENT_MERGE_DELAY"
        process_cache "$action" "$file_path"
        [ -f "$EVENT_TIMERS_FILE" ] && update_timer_file "$file_path"
    ) &
    
    local timer_pid=$!
    echo "${file_path}|${timer_pid}" >> "$EVENT_TIMERS_FILE"
    add_pid "$timer_pid"
}

# 规范化路径
normalize_path() {
    echo "$1" | sed 's:/\+:/:g'
}

# 缓存操作函数
process_cache() {
    local source_file=$(normalize_path "$2")
    local relative_path="${source_file#$WATCH_DIR}"
    local cache_file="${CACHE_DIR}${relative_path}.bin"
    
    [ ! -f "$source_file" ] && return 1
    
    log "生成缓存: $cache_file"
    php ${PROTECTOR_SCRIPT} --file ${source_file} >/dev/null 2>&1 || return 1
}

# 处理项目目录事件
process_project_event() {
    local full_path=$(normalize_path "$(echo "$1" | sed 's:/$::')/$3")
    case "$full_path" in
        "$WATCH_DIR"*/*.php) [ -f "$full_path" ] && schedule_task "$full_path" "create" ;;
    esac
}

# 处理缓存目录事件
process_cache_event() {
    local full_cache_path=$(normalize_path "$(echo "$1" | sed 's:/$::')/$3")
    case "$full_cache_path" in
        "$CACHE_DIR"*/*.bin)
            local relative_cache="${full_cache_path#$CACHE_DIR}"
            [ "$relative_cache" = "${relative_cache#home/wwwroot/default/}" ] || relative_cache="${relative_cache#home/wwwroot/default/}"
            local source_file="${WATCH_DIR}${relative_cache%.bin}"
            [ -f "$source_file" ] && schedule_task "$source_file" "rebuild"
            ;;
    esac
}

# 终止所有子进程
terminate_all() {
    [ -f "$EVENT_TIMERS_FILE" ] && while IFS= read -r line; do
        kill -TERM $(echo "$line" | cut -d'|' -f2) 2>/dev/null
    done < "$EVENT_TIMERS_FILE"
    
    for pid in $EVENT_PROCESSOR_PIDS; do
        kill -TERM "$pid" 2>/dev/null
    done
    
    [ -n "$INOTIFY_PROJECT_PID" ] && kill -TERM "$INOTIFY_PROJECT_PID" 2>/dev/null
    [ -n "$INOTIFY_CACHE_PID" ] && kill -TERM "$INOTIFY_CACHE_PID" 2>/dev/null
    
    rm -f /tmp/project_events /tmp/cache_events "$EVENT_TIMERS_FILE"
}

# 优雅退出
cleanup() {
    log "正在清理并退出..."
    terminate_all
    exit 0
}

# 信号处理
trap cleanup INT TERM HUP

# 创建管道
create_fifo /tmp/project_events
create_fifo /tmp/cache_events

# 初始化
> "$EVENT_TIMERS_FILE"

# 启动监控
inotifywait -m -r -e modify,create,move --format '%w %e %f' \
    "$WATCH_DIR" > /tmp/project_events 2>/dev/null &
INOTIFY_PROJECT_PID=$!

inotifywait -m -r -e delete,move --format '%w %e %f' \
    "$CACHE_DIR" > /tmp/cache_events 2>/dev/null &
INOTIFY_CACHE_PID=$!

# 启动处理循环
{ while read -r dir event file; do process_project_event "$dir" "$event" "$file"; done; } < /tmp/project_events &
add_pid $!

{ while read -r dir event file; do process_cache_event "$dir" "$event" "$file"; done; } < /tmp/cache_events &
add_pid $!

log "脚本已启动，正在监控文件系统变化..."
wait