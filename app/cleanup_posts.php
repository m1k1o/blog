<?php
/**
 * Cleanup Posts Script
 * Run this script periodically to clean up orphaned images and old data
 * 
 * Usage: php cleanup_posts.php
 * Or set up as a cron job: 0 2 * * * php /path/to/cleanup_posts.php
 */

// Include required files
require_once __DIR__ . '/config.class.php';
require_once __DIR__ . '/db.class.php';
require_once __DIR__ . '/image.class.php';
require_once __DIR__ . '/log.class.php';

// Only allow CLI execution
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

// Initialize
try {
    echo "Starting cleanup process...\n";
    echo "----------------------------\n\n";
    
    // Load configuration
    $config = new Config();
    echo "✓ Configuration loaded\n";
    
    // Connect to database
    $db = DB::getInstance($config);
    echo "✓ Database connected\n";
    
    // Initialize classes
    $image = new Image($config);
    $log = new Log($config);
    echo "✓ Classes initialized\n\n";
    
    // 1. Clean up orphaned images
    echo "1. Cleaning up orphaned images...\n";
    $deleted_images = $image->cleanupOrphanedImages($db->getPDO());
    echo "   Deleted $deleted_images orphaned image(s)\n\n";
    
    // 2. Clean up old log files
    echo "2. Cleaning up old log files...\n";
    $log_retention_days = $config->get('log_retention_days', 30);
    $deleted_logs = $log->cleanOldLogs($log_retention_days);
    echo "   Deleted $deleted_logs old log file(s)\n\n";
    
    // 3. Rotate large log files
    echo "3. Rotating large log files...\n";
    $log_files = $log->getLogFiles();
    $rotated_count = 0;
    foreach ($log_files as $log_file) {
        if ($log->rotateLog($log_file)) {
            $rotated_count++;
            echo "   Rotated: $log_file\n";
        }
    }
    echo "   Rotated $rotated_count log file(s)\n\n";
    
    // 4. Clean up deleted user data
    echo "4. Cleaning up deleted user data...\n";
    $stmt = $db->prepare("
        DELETE FROM posts 
        WHERE user_id NOT IN (SELECT id FROM users)
    ");
    $stmt->execute();
    $deleted_posts = $stmt->rowCount();
    echo "   Deleted $deleted_posts orphaned post(s)\n\n";
    
    // 5. Optimize database (SQLite only)
    if ($db->getType() === 'sqlite') {
        echo "5. Optimizing database...\n";
        $db->exec('VACUUM');
        $db->exec('ANALYZE');
        echo "   Database optimized\n\n";
    }
    
    // 6. Database statistics
    echo "6. Database statistics:\n";
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    $user_count = $stmt->fetchColumn();
    echo "   Total users: $user_count\n";
    
    $stmt = $db->query("SELECT COUNT(*) FROM posts");
    $post_count = $stmt->fetchColumn();
    echo "   Total posts: $post_count\n";
    
    $stmt = $db->query("SELECT COUNT(*) FROM posts WHERE is_sticky = 1");
    $sticky_count = $stmt->fetchColumn();
    echo "   Sticky posts: $sticky_count\n\n";
    
    // Log cleanup completion
    $log->log("Cleanup completed: $deleted_images images, $deleted_logs logs, $deleted_posts orphaned posts");
    
    echo "----------------------------\n";
    echo "Cleanup completed successfully!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    if (isset($log)) {
        $log->error("Cleanup failed: " . $e->getMessage());
    }
    exit(1);
}

exit(0);
