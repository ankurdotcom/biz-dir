<?php
/**
 * Documentation Generator Factory
 * Implements Factory Pattern for creating documentation generators
 */

namespace BizDirWiki\Services;

use BizDirWiki\Contracts\DocumentationGeneratorInterface;

class DocumentationGeneratorFactory
{
    /**
     * Create documentation generator based on available implementations
     */
    public static function create(array $config, string $logFile): DocumentationGeneratorInterface
    {
        // Try to create DocGenerator adapter if available
        $docGeneratorPath = dirname(__DIR__) . '/Services/DocGenerator.php';
        if (file_exists($docGeneratorPath)) {
            try {
                // Check if required dependencies are available
                if (class_exists('Illuminate\Database\Capsule\Manager')) {
                    require_once $docGeneratorPath;
                    return new DocGeneratorAdapter($config, $logFile);
                } else {
                    error_log("DocGenerator dependencies not available (Illuminate\Database\Capsule\Manager), falling back to BasicDocumentationGenerator");
                }
            } catch (\Exception $e) {
                // Log the error and fall back
                error_log("DocGenerator adapter failed: " . $e->getMessage());
            }
        }
        
        // Fall back to basic generator
        return new BasicDocumentationGenerator($config, $logFile);
    }
}
