<?php
/**
 * Documentation Generator Contract
 * Defines the interface for documentation generation services
 */

namespace BizDirWiki\Contracts;

interface DocumentationGeneratorInterface
{
    /**
     * Generate executive summary documentation
     */
    public function generateExecutiveSummary(): string;
    
    /**
     * Generate technical documentation
     */
    public function generateTechnicalDocumentation(): string;
    
    /**
     * Generate QA documentation
     */
    public function generateQADocumentation(): string;
    
    /**
     * Generate operations guide
     */
    public function generateOperationsGuide(): string;
    
    /**
     * Generate project documentation
     */
    public function generateProjectDocumentation(): string;
    
    /**
     * Check if the generator is available and ready
     */
    public function isAvailable(): bool;
    
    /**
     * Get generator information
     */
    public function getInfo(): array;
}
