<?php
/**
 * Database Schema Manager
 * Handles SQLite schema creation and validation with proper transaction management
 */

namespace BizDirWiki\Database;

use PDO;
use PDOException;

class SchemaManager {
    private PDO $db;
    private string $logFile;
    private array $executedStatements = [];
    
    public function __construct(PDO $db, string $logFile) {
        $this->db = $db;
        $this->logFile = $logFile;
        
        // Enable foreign key constraints
        $this->db->exec('PRAGMA foreign_keys = ON');
    }
    
    /**
     * Execute schema file with proper transaction management
     */
    public function executeSchemaFile(string $schemaFile): bool {
        if (!file_exists($schemaFile)) {
            $this->log("ERROR: Schema file not found: $schemaFile");
            return false;
        }
        
        $this->log("Starting schema execution from: $schemaFile");
        
        try {
            // Begin transaction
            $this->db->beginTransaction();
            
            $schema = file_get_contents($schemaFile);
            $statements = $this->parseStatements($schema);
            
            $this->log("Parsed " . count($statements) . " SQL statements");
            
            foreach ($statements as $index => $statement) {
                $this->executeStatement($statement, $index + 1);
            }
            
            // Validate schema was created properly
            if (!$this->validateSchema()) {
                throw new \Exception("Schema validation failed");
            }
            
            // Commit transaction
            $this->db->commit();
            $this->log("Schema execution completed successfully");
            
            return true;
            
        } catch (\Exception $e) {
            // Rollback on any error
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            
            $this->log("ERROR: Schema execution failed - " . $e->getMessage());
            $this->log("Rolling back all changes");
            
            return false;
        }
    }
    
    /**
     * Parse SQL statements from schema file
     */
    private function parseStatements(string $schema): array {
        // Remove comments and empty lines
        $lines = explode("\n", $schema);
        $cleanLines = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            // Skip comments and empty lines
            if (empty($line) || str_starts_with($line, '--') || str_starts_with($line, '/*')) {
                continue;
            }
            $cleanLines[] = $line;
        }
        
        $cleanSchema = implode("\n", $cleanLines);
        
        // Split by semicolons but be careful about quoted strings
        $statements = [];
        $currentStatement = '';
        $inQuotes = false;
        $quoteChar = '';
        
        for ($i = 0; $i < strlen($cleanSchema); $i++) {
            $char = $cleanSchema[$i];
            
            if (!$inQuotes && ($char === '"' || $char === "'")) {
                $inQuotes = true;
                $quoteChar = $char;
            } elseif ($inQuotes && $char === $quoteChar) {
                $inQuotes = false;
                $quoteChar = '';
            }
            
            $currentStatement .= $char;
            
            if (!$inQuotes && $char === ';') {
                $stmt = trim($currentStatement);
                if (!empty($stmt) && $stmt !== ';') {
                    $statements[] = $stmt;
                }
                $currentStatement = '';
            }
        }
        
        // Add final statement if it doesn't end with semicolon
        if (!empty(trim($currentStatement))) {
            $statements[] = trim($currentStatement);
        }
        
        return $statements;
    }
    
    /**
     * Execute a single SQL statement with error handling
     */
    private function executeStatement(string $statement, int $index): void {
        try {
            $this->log("Executing statement $index: " . substr($statement, 0, 100) . "...");
            
            $result = $this->db->exec($statement);
            
            $this->executedStatements[] = [
                'index' => $index,
                'statement' => $statement,
                'success' => true,
                'affected_rows' => $result
            ];
            
        } catch (PDOException $e) {
            $this->executedStatements[] = [
                'index' => $index,
                'statement' => $statement,
                'success' => false,
                'error' => $e->getMessage()
            ];
            
            throw new \Exception("Statement $index failed: " . $e->getMessage());
        }
    }
    
    /**
     * Validate that the schema was created correctly
     */
    public function validateSchema(): bool {
        $this->log("Validating schema integrity...");
        
        // Expected tables
        $expectedTables = [
            'roles', 'users', 'user_roles', 'categories', 'pages',
            'page_permissions', 'page_history', 'comments', 'notifications', 'sessions'
        ];
        
        // Get actual tables
        $stmt = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
        $actualTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $this->log("Expected tables: " . implode(', ', $expectedTables));
        $this->log("Actual tables: " . implode(', ', $actualTables));
        
        // Check if all expected tables exist
        $missingTables = array_diff($expectedTables, $actualTables);
        if (!empty($missingTables)) {
            $this->log("ERROR: Missing tables: " . implode(', ', $missingTables));
            return false;
        }
        
        // Validate specific table structures
        $validationErrors = [];
        
        // Validate roles table
        if (!$this->validateTableStructure('roles', ['id', 'name', 'display_name', 'access_level'])) {
            $validationErrors[] = "roles table structure invalid";
        }
        
        // Validate users table
        if (!$this->validateTableStructure('users', ['id', 'username', 'email', 'password_hash', 'name'])) {
            $validationErrors[] = "users table structure invalid";
        }
        
        // Validate pages table
        if (!$this->validateTableStructure('pages', ['id', 'title', 'slug', 'content', 'author_id'])) {
            $validationErrors[] = "pages table structure invalid";
        }
        
        if (!empty($validationErrors)) {
            $this->log("ERROR: Validation errors: " . implode(', ', $validationErrors));
            return false;
        }
        
        $this->log("Schema validation passed");
        return true;
    }
    
    /**
     * Validate table structure
     */
    private function validateTableStructure(string $tableName, array $requiredColumns): bool {
        try {
            $stmt = $this->db->query("PRAGMA table_info($tableName)");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1); // Get column names
            
            $missingColumns = array_diff($requiredColumns, $columns);
            if (!empty($missingColumns)) {
                $this->log("ERROR: Table $tableName missing columns: " . implode(', ', $missingColumns));
                return false;
            }
            
            return true;
            
        } catch (PDOException $e) {
            $this->log("ERROR: Failed to validate table $tableName: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get execution report
     */
    public function getExecutionReport(): array {
        return [
            'total_statements' => count($this->executedStatements),
            'successful' => count(array_filter($this->executedStatements, fn($s) => $s['success'])),
            'failed' => count(array_filter($this->executedStatements, fn($s) => !$s['success'])),
            'statements' => $this->executedStatements
        ];
    }
    
    /**
     * Log message with timestamp
     */
    private function log(string $message): void {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] SchemaManager: $message\n";
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}
