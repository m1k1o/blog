<?php
/**
 * BBCode to Markdown Converter
 * Run this script to convert all existing BBCode posts to Markdown
 * 
 * Usage: php convert_bbcode_to_markdown.php
 */

// Allow direct script access for CLI
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

// Include common.php for database and config
require_once __DIR__ . '/common.php';

class BBCodeToMarkdownConverter {
    private $db;
    
    public function __construct() {
        $this->db = DB::get_instance();
    }
    
    /**
     * Convert BBCode to Markdown
     */
    public function convert($bbcode) {
        $markdown = $bbcode;
        
        // Bold: [b]text[/b] → **text**
        $markdown = preg_replace('/\[b\](.*?)\[\/b\]/is', '**$1**', $markdown);
        
        // Italic: [i]text[/i] → *text*
        $markdown = preg_replace('/\[i\](.*?)\[\/i\]/is', '*$1*', $markdown);
        
        // Underline: [u]text[/u] → <u>text</u> (Markdown doesn't have native underline)
        $markdown = preg_replace('/\[u\](.*?)\[\/u\]/is', '<u>$1</u>', $markdown);
        
        // Strikethrough: [s]text[/s] → ~~text~~
        $markdown = preg_replace('/\[s\](.*?)\[\/s\]/is', '~~$1~~', $markdown);
        
        // Code: [code]text[/code] → `text`
        $markdown = preg_replace('/\[code\](.*?)\[\/code\]/is', '`$1`', $markdown);
        
        // Code block: [pre]text[/pre] → ```\ntext\n```
        $markdown = preg_replace('/\[pre\](.*?)\[\/pre\]/is', "```\n$1\n```", $markdown);
        
        // Links: [url=http://example.com]text[/url] → [text](http://example.com)
        $markdown = preg_replace('/\[url=(.*?)\](.*?)\[\/url\]/is', '[$2]($1)', $markdown);
        
        // Simple links: [url]http://example.com[/url] → <http://example.com>
        $markdown = preg_replace('/\[url\](.*?)\[\/url\]/is', '<$1>', $markdown);
        
        // Images: [img]url[/img] → ![](url)
        $markdown = preg_replace('/\[img\](.*?)\[\/img\]/is', '![]($1)', $markdown);
        
        // Images with alt: [img=alt]url[/img] → ![alt](url)
        $markdown = preg_replace('/\[img=(.*?)\](.*?)\[\/img\]/is', '![$1]($2)', $markdown);
        
        // Quotes: [quote]text[/quote] → > text
        $markdown = preg_replace_callback('/\[quote\](.*?)\[\/quote\]/is', function($matches) {
            $lines = explode("\n", trim($matches[1]));
            return "\n> " . implode("\n> ", $lines) . "\n";
        }, $markdown);
        
        // Named quotes: [quote=author]text[/quote] → > **author:**\n> text
        $markdown = preg_replace_callback('/\[quote=(.*?)\](.*?)\[\/quote\]/is', function($matches) {
            $author = $matches[1];
            $text = trim($matches[2]);
            $lines = explode("\n", $text);
            return "\n> **{$author}:**\n> " . implode("\n> ", $lines) . "\n";
        }, $markdown);
        
        // Lists: [list][*]item[/list] → - item
        $markdown = preg_replace_callback('/\[list\](.*?)\[\/list\]/is', function($matches) {
            $content = $matches[1];
            $content = preg_replace('/\[\*\](.*?)(?=\[\*\]|\[\/list\])/is', "- $1\n", $content);
            return "\n" . trim($content) . "\n";
        }, $markdown);
        
        // Ordered lists: [list=1][*]item[/list] → 1. item
        $markdown = preg_replace_callback('/\[list=1\](.*?)\[\/list\]/is', function($matches) {
            $content = $matches[1];
            $items = preg_split('/\[\*\]/', $content, -1, PREG_SPLIT_NO_EMPTY);
            $result = "\n";
            $counter = 1;
            foreach ($items as $item) {
                $result .= $counter . ". " . trim($item) . "\n";
                $counter++;
            }
            return $result;
        }, $markdown);
        
        // Headings: [h1]text[/h1] → # text
        $markdown = preg_replace('/\[h1\](.*?)\[\/h1\]/is', "\n# $1\n", $markdown);
        $markdown = preg_replace('/\[h2\](.*?)\[\/h2\]/is', "\n## $1\n", $markdown);
        $markdown = preg_replace('/\[h3\](.*?)\[\/h3\]/is', "\n### $1\n", $markdown);
        
        // Colors: [color=red]text[/color] → <span style="color:red">text</span>
        $markdown = preg_replace('/\[color=(.*?)\](.*?)\[\/color\]/is', '<span style="color:$1">$2</span>', $markdown);
        
        // Size: [size=14]text[/size] → (remove, not needed in Markdown)
        $markdown = preg_replace('/\[size=\d+\](.*?)\[\/size\]/is', '$1', $markdown);
        
        // Center: [center]text[/center] → <center>text</center>
        $markdown = preg_replace('/\[center\](.*?)\[\/center\]/is', '<center>$1</center>', $markdown);
        
        // Left: [left]text[/left] → (remove tags, left is default)
        $markdown = preg_replace('/\[left\](.*?)\[\/left\]/is', '$1', $markdown);
        
        // Right: [right]text[/right] → <div align="right">text</div>
        $markdown = preg_replace('/\[right\](.*?)\[\/right\]/is', '<div align="right">$1</div>', $markdown);
        
        return trim($markdown);
    }
    
    /**
     * Convert all posts
     */
    public function convertAllPosts() {
        try {
            echo "Fetching all posts from database...\n";
            
            $posts = $this->db->query("SELECT `id`, `plain_text` FROM `posts`")->all();
            
            $total = count($posts);
            $converted = 0;
            $skipped = 0;
            
            echo "Found $total posts to process\n";
            echo "Starting conversion...\n\n";
            
            foreach ($posts as $post) {
                $original_content = $post['plain_text'];
                
                // Skip if empty
                if (empty($original_content)) {
                    $skipped++;
                    continue;
                }
                
                // Check if contains BBCode tags
                $hasBBCode = preg_match('/\[(?!\/?\*\])[a-z]+(?:=[^\]]+)?\]/i', $original_content);
                
                if (!$hasBBCode) {
                    $skipped++;
                    echo "⊘ Skipped post ID {$post['id']} (no BBCode found)\n";
                    continue;
                }
                
                $converted_content = $this->convert($original_content);
                
                // Only update if content changed
                if ($original_content !== $converted_content) {
                    $this->db->query("
                        UPDATE `posts` 
                        SET `plain_text` = ?
                        WHERE `id` = ?
                    ", $converted_content, $post['id']);
                    
                    $converted++;
                    echo "✓ Converted post ID: {$post['id']}\n";
                }
            }
            
            echo "\n" . str_repeat("=", 50) . "\n";
            echo "Conversion complete!\n";
            echo str_repeat("=", 50) . "\n";
            echo "Total posts:     $total\n";
            echo "Converted:       $converted\n";
            echo "Skipped:         $skipped\n";
            echo "Unchanged:       " . ($total - $converted - $skipped) . "\n";
            echo str_repeat("=", 50) . "\n\n";
            
            Log::put("ajax_access", "BBCode to Markdown conversion completed: $converted/$total posts converted");
            
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
            Log::put("ajax_errors", "BBCode conversion failed: " . $e->getMessage());
            throw $e;
        }
    }
}

// Run converter
try {
    echo "\n";
    echo "╔═══════════════════════════════════════════════╗\n";
    echo "║   BBCode to Markdown Converter                ║\n";
    echo "╚═══════════════════════════════════════════════╝\n";
    echo "\n";
    
    $converter = new BBCodeToMarkdownConverter();
    $converter->convertAllPosts();
    
    echo "✅ Migration successful!\n\n";
    
} catch (Exception $e) {
    echo "\n❌ Fatal error: " . $e->getMessage() . "\n\n";
    exit(1);
}

exit(0);