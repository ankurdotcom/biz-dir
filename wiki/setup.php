<?php
/**
 * BizDir Wiki Setup Script
 * Comprehensive installation and configuration system
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

class WikiSetup {
    private $config;
    private $db;
    private $baseDir;
    private $logFile;
    
    public function __construct() {
        $this->baseDir = __DIR__;
        $this->logFile = $this->baseDir . '/logs/setup.log';
        
        // Ensure logs directory exists
        if (!is_dir($this->baseDir . '/logs')) {
            mkdir($this->baseDir . '/logs', 0755, true);
        }
        
        $this->log("BizDir Wiki Setup Started");
    }
    
    public function run() {
        try {
            $this->displayWelcome();
            $this->checkPrerequisites();
            $this->loadConfiguration();
            $this->setupDatabase();
            $this->installComposerDependencies();
            $this->createDirectories();
            $this->importInitialData();
            $this->generateDemoContent();
            $this->setupPermissions();
            $this->createSystemdService();
            $this->runInitialSync();
            $this->displaySuccessMessage();
            
        } catch (Exception $e) {
            $this->log("SETUP FAILED: " . $e->getMessage());
            $this->displayError($e->getMessage());
            exit(1);
        }
    }
    
    private function displayWelcome() {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "             🚀 BizDir Wiki Setup System 🚀\n";
        echo "        Role-based Documentation & Executive Dashboard\n";
        echo str_repeat("=", 70) . "\n\n";
        
        echo "This setup will configure:\n";
        echo "✓ Database schema and initial data\n";
        echo "✓ Role-based access control system\n";
        echo "✓ Executive dashboard with real-time KPIs\n";
        echo "✓ Auto-sync monitoring for project changes\n";
        echo "✓ Demo accounts for all user roles\n";
        echo "✓ Complete wiki documentation system\n\n";
        
        if (!$this->confirm("Do you want to proceed with the installation?")) {
            echo "Setup cancelled.\n";
            exit(0);
        }
    }
    
    private function checkPrerequisites() {
        $this->log("Checking system prerequisites...");
        
        $requirements = [
            'PHP >= 8.0' => version_compare(PHP_VERSION, '8.0.0', '>='),
            'PDO Extension' => extension_loaded('pdo'),
            'PDO MySQL' => extension_loaded('pdo_mysql'),
            'JSON Extension' => extension_loaded('json'),
            'Composer' => $this->commandExists('composer'),
            'Git' => $this->commandExists('git'),
        ];
        
        echo "Checking prerequisites:\n";
        $allPassed = true;
        
        foreach ($requirements as $requirement => $passed) {
            $status = $passed ? "✓ PASS" : "✗ FAIL";
            $color = $passed ? "\033[32m" : "\033[31m";
            echo sprintf("  %-20s %s%s\033[0m\n", $requirement, $color, $status);
            
            if (!$passed) $allPassed = false;
        }
        
        if (!$allPassed) {
            throw new Exception("Some prerequisites are not met. Please install missing requirements.");
        }
        
        echo "\n";
        $this->log("All prerequisites satisfied");
    }
    
    private function loadConfiguration() {
        $this->log("Loading configuration...");
        
        $configFile = $this->baseDir . '/config/config.php';
        if (!file_exists($configFile)) {
            throw new Exception("Configuration file not found: $configFile");
        }
        
        $this->config = require $configFile;
        
        if (!$this->config) {
            throw new Exception("Configuration array not found in config file");
        }
        
        $this->log("Configuration loaded successfully");
    }
    
    private function setupDatabase() {
        $this->log("Setting up database...");
        
        $dbConfig = $this->config['database'];
        
        // Setup SQLite database
        try {
            $dbPath = $dbConfig['database'];
            $dbDir = dirname($dbPath);
            
            // Ensure database directory exists
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0755, true);
            }
            
            $dsn = "sqlite:" . $dbPath;
            $this->db = new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            
            echo "Database connection: \033[32m✓ Connected (SQLite)\033[0m\n";
            
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
        
        echo "Database created: \033[32m✓ " . basename($dbPath) . "\033[0m\n";
        
        // Use engineered SchemaManager for robust schema import
        require_once $this->baseDir . '/src/Database/SchemaManager.php';
        $schemaManager = new \BizDirWiki\Database\SchemaManager($this->db, $this->logFile);
        
        $schemaFile = $this->baseDir . '/database/sqlite_schema.sql';
        if (!file_exists($schemaFile)) {
            throw new Exception("SQLite schema file not found: $schemaFile");
        }
        
        if (!$schemaManager->executeSchemaFile($schemaFile)) {
            throw new Exception("Schema execution failed. Check setup log for details.");
        }
        
        if (!$schemaManager->validateSchema()) {
            throw new Exception("Schema validation failed. Check setup log for details.");
        }
        
        echo "Database schema: \033[32m✓ Imported & Validated\033[0m\n";
        $this->log("Database setup completed with engineered SchemaManager");
    }
    
    private function installComposerDependencies() {
        $this->log("Installing Composer dependencies...");
        
        echo "Installing PHP dependencies:\n";
        
        $composerFile = $this->baseDir . '/composer.json';
        if (!file_exists($composerFile)) {
            throw new Exception("composer.json not found");
        }
        
        $output = [];
        $returnCode = 0;
        
        exec("cd " . escapeshellarg($this->baseDir) . " && composer install --no-dev --optimize-autoloader 2>&1", $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Composer install failed: " . implode("\n", $output));
        }
        
        echo "Composer dependencies: \033[32m✓ Installed\033[0m\n";
        $this->log("Composer dependencies installed");
    }
    
    private function createDirectories() {
        $this->log("Creating required directories...");
        
        $directories = [
            'logs',
            'uploads',
            'backups',
            'cache',
            'public/coverage',
            'public/assets/uploads'
        ];
        
        foreach ($directories as $dir) {
            $fullPath = $this->baseDir . '/' . $dir;
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
                echo "Directory created: \033[32m✓ $dir\033[0m\n";
            }
        }
        
        $this->log("Required directories created");
    }
    
    private function importInitialData() {
        $this->log("Importing initial data...");
        
        // Use engineered DataSeeder for robust data import
        require_once $this->baseDir . '/src/Database/DataSeeder.php';
        $dataSeeder = new \BizDirWiki\Database\DataSeeder($this->db, $this->logFile);
        
        if (!$dataSeeder->seedAll()) {
            throw new Exception("Data seeding failed. Check setup log for details.");
        }
        
        if (!$dataSeeder->validateSeededData()) {
            throw new Exception("Data validation failed. Check setup log for details.");
        }
        
        $seededData = $dataSeeder->getSeededData();
        
        // Display summary
        foreach ($seededData as $type => $items) {
            $count = count($items);
            $displayType = ucfirst(str_replace('_', ' ', $type));
            echo "$displayType: \033[32m✓ $count items\033[0m\n";
        }
        
        $this->log("Initial data imported successfully with engineered DataSeeder");
    }
    
    private function generateDemoContent() {
        $this->log("Generating demo content...");
        
        // Use factory pattern to create appropriate documentation generator
        require_once $this->baseDir . '/src/Contracts/DocumentationGeneratorInterface.php';
        require_once $this->baseDir . '/src/Services/DocumentationGeneratorFactory.php';
        require_once $this->baseDir . '/src/Services/DocGeneratorAdapter.php';
        require_once $this->baseDir . '/src/Services/BasicDocumentationGenerator.php';
        
        $generator = \BizDirWiki\Services\DocumentationGeneratorFactory::create($this->config, $this->logFile);
        
        $info = $generator->getInfo();
        echo "Documentation Generator: \033[32m✓ {$info['type']}\033[0m\n";
        
        echo "Generating documentation:\n";
        
        // Generate all documentation types using the interface
        $documentTypes = [
            ['method' => 'generateExecutiveSummary', 'title' => 'Executive Summary', 'slug' => 'executive-summary', 'category' => 'executive-summary', 'roles' => ['executive']],
            ['method' => 'generateTechnicalDocumentation', 'title' => 'Technical Architecture', 'slug' => 'technical-architecture', 'category' => 'technical-docs', 'roles' => ['developer', 'operations']],
            ['method' => 'generateQADocumentation', 'title' => 'QA Procedures', 'slug' => 'qa-procedures', 'category' => 'qa-testing', 'roles' => ['qa', 'developer']],
            ['method' => 'generateOperationsGuide', 'title' => 'Operations Guide', 'slug' => 'operations-guide', 'category' => 'operations', 'roles' => ['operations', 'developer']],
            ['method' => 'generateProjectDocumentation', 'title' => 'Project Overview', 'slug' => 'project-overview', 'category' => 'project-management', 'roles' => ['project_manager', 'product_owner']]
        ];
        
        foreach ($documentTypes as $docType) {
            try {
                $content = $generator->{$docType['method']}();
                $this->createPage($docType['title'], $docType['slug'], $content, $docType['category'], $docType['roles']);
                echo "  {$docType['title']}: \033[32m✓ Generated\033[0m\n";
            } catch (\Exception $e) {
                echo "  {$docType['title']}: \033[33m⚠ Skipped ({$e->getMessage()})\033[0m\n";
                $this->log("Documentation generation warning: " . $e->getMessage());
            }
        }
        
        $this->log("Demo content generated successfully using " . $info['type']);
    }
    
    private function createBasicWelcomePage() {
        $welcomeContent = "# Welcome to BizDir Wiki

Welcome to the BizDir Wiki system - your comprehensive documentation and collaboration platform.

## Features

- **Role-based Access Control**: Six distinct user roles with specific permissions
- **Executive Dashboard**: Real-time KPIs and strategic information
- **Documentation Management**: Create, edit, and organize project documentation
- **Auto-sync Integration**: Automatic monitoring of project changes
- **Search & Categories**: Powerful search and categorization system

## User Roles

- **Executive**: C-level executives with access to strategic information
- **Product Owner**: Product managers and owners
- **Project Manager**: Project coordinators and managers
- **Developer**: Software developers and engineers
- **QA Engineer**: Quality assurance and testing team
- **Operations**: DevOps and operations team

## Getting Started

1. Log in with your assigned credentials
2. Navigate to your role-specific dashboard
3. Browse categories relevant to your role
4. Start creating and editing documentation
5. Use the search function to find information quickly

## Demo Accounts

Use the following accounts to explore different role perspectives:

- **admin/admin123** - System Administrator
- **ceo/ceo123** - Chief Executive Officer
- **cto/cto123** - Chief Technology Officer
- **pm/pm123** - Project Manager
- **po/po123** - Product Owner
- **dev/dev123** - Senior Developer
- **qa/qa123** - QA Engineer
- **ops/ops123** - DevOps Engineer

## Support

For technical support or questions, please contact your system administrator.

---

*BizDir Wiki System - Powering collaborative documentation*";

        $this->createPage('Welcome', 'welcome', $welcomeContent, 'executive-summary', ['executive', 'product_owner', 'project_manager', 'developer', 'qa', 'operations']);
        echo "  Welcome Page: \033[32m✓ Created\033[0m\n";
        
        $this->log("Basic welcome page created successfully");
    }
    
    private function createPage($title, $slug, $content, $categorySlug, $allowedRoles) {
        // Get category ID
        $stmt = $this->db->prepare("SELECT id FROM categories WHERE slug = ?");
        $stmt->execute([$categorySlug]);
        $categoryId = $stmt->fetchColumn();
        
        // Insert page
        $stmt = $this->db->prepare("
            INSERT OR REPLACE INTO pages (title, slug, content, category_id, author_id, status, created_at, updated_at) 
            VALUES (?, ?, ?, ?, 1, 'published', datetime('now'), datetime('now'))
        ");
        
        $stmt->execute([$title, $slug, $content, $categoryId]);
        
        // Get page ID
        $pageId = $this->db->lastInsertId();
        if (!$pageId) {
            $stmt = $this->db->prepare("SELECT id FROM pages WHERE slug = ?");
            $stmt->execute([$slug]);
            $pageId = $stmt->fetchColumn();
        }
        
        // Set permissions
        $this->db->prepare("DELETE FROM page_permissions WHERE page_id = ?")->execute([$pageId]);
        
        $stmt = $this->db->prepare("INSERT INTO page_permissions (page_id, role_id, permission) VALUES (?, (SELECT id FROM roles WHERE name = ?), 'read')");
        foreach ($allowedRoles as $role) {
            $stmt->execute([$pageId, $role]);
        }
    }
    
    private function setupPermissions() {
        $this->log("Setting up file permissions...");
        
        $paths = [
            $this->baseDir . '/logs' => 0755,
            $this->baseDir . '/uploads' => 0755,
            $this->baseDir . '/backups' => 0755,
            $this->baseDir . '/cache' => 0755,
            $this->baseDir . '/scripts/auto-sync.sh' => 0755
        ];
        
        foreach ($paths as $path => $permission) {
            if (file_exists($path)) {
                chmod($path, $permission);
            }
        }
        
        echo "File permissions: \033[32m✓ Set\033[0m\n";
        $this->log("File permissions configured");
    }
    
    private function createSystemdService() {
        $this->log("Creating systemd service for auto-sync...");
        
        $serviceContent = "[Unit]
Description=BizDir Wiki Auto-Sync Service
After=network.target

[Service]
Type=simple
User=" . get_current_user() . "
WorkingDirectory=" . $this->baseDir . "
ExecStart=" . $this->baseDir . "/scripts/auto-sync.sh monitor
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
";
        
        $serviceFile = '/tmp/bizdir-wiki-sync.service';
        file_put_contents($serviceFile, $serviceContent);
        
        echo "Systemd service: \033[33m⚠ Created at $serviceFile\033[0m\n";
        echo "To install: sudo cp $serviceFile /etc/systemd/system/ && sudo systemctl enable bizdir-wiki-sync\n";
        
        $this->log("Systemd service template created");
    }
    
    private function runInitialSync() {
        $this->log("Running initial project synchronization...");
        
        $syncScript = $this->baseDir . '/scripts/auto-sync.sh';
        if (file_exists($syncScript)) {
            echo "Running initial sync:\n";
            
            $output = [];
            $returnCode = 0;
            
            exec($syncScript . " sync-once 2>&1", $output, $returnCode);
            
            if ($returnCode === 0) {
                echo "Initial sync: \033[32m✓ Completed\033[0m\n";
            } else {
                echo "Initial sync: \033[33m⚠ Warning (check logs)\033[0m\n";
            }
        }
        
        $this->log("Initial synchronization completed");
    }
    
    private function displaySuccessMessage() {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "🎉 BizDir Wiki Setup Completed Successfully! 🎉\n";
        echo str_repeat("=", 70) . "\n\n";
        
        echo "Access Information:\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "📱 Web Interface: http://localhost/wiki/public/\n";
        echo "🔐 Login URL:     http://localhost/wiki/public/login\n\n";
        
        echo "Demo Accounts:\n";
        echo "┌─────────────────┬──────────────┬─────────────────────────────────┐\n";
        echo "│ Role            │ Username     │ Password                        │\n";
        echo "├─────────────────┼──────────────┼─────────────────────────────────┤\n";
        echo "│ Administrator   │ admin        │ admin123                        │\n";
        echo "│ CEO             │ ceo          │ ceo123                          │\n";
        echo "│ CTO             │ cto          │ cto123                          │\n";
        echo "│ Project Manager │ pm           │ pm123                           │\n";
        echo "│ Product Owner   │ po           │ po123                           │\n";
        echo "│ Developer       │ dev          │ dev123                          │\n";
        echo "│ QA Engineer     │ qa           │ qa123                           │\n";
        echo "│ DevOps          │ ops          │ ops123                          │\n";
        echo "└─────────────────┴──────────────┴─────────────────────────────────┘\n\n";
        
        echo "Features Available:\n";
        echo "✅ Role-based access control (6 user roles)\n";
        echo "✅ Executive dashboard with real-time KPIs\n";
        echo "✅ Auto-sync monitoring for project changes\n";
        echo "✅ Comprehensive documentation system\n";
        echo "✅ Markdown editor with live preview\n";
        echo "✅ Search and categorization\n";
        echo "✅ Comment system and version history\n";
        echo "✅ File attachments and media support\n\n";
        
        echo "Next Steps:\n";
        echo "🔧 Configure web server to serve from: " . $this->baseDir . "/public/\n";
        echo "🔄 Start auto-sync service: ./scripts/auto-sync.sh monitor\n";
        echo "📊 Access executive dashboard with CEO/CTO accounts\n";
        echo "📝 Start creating and editing documentation\n\n";
        
        echo "Support & Documentation:\n";
        echo "📁 Logs directory: " . $this->baseDir . "/logs/\n";
        echo "⚙️  Configuration: " . $this->baseDir . "/config/config.php\n";
        echo "🛠️  Auto-sync script: " . $this->baseDir . "/scripts/auto-sync.sh\n\n";
        
        $this->log("Setup completed successfully - Wiki system is ready!");
    }
    
    private function displayError($message) {
        echo "\n❌ Setup Failed: $message\n\n";
        echo "Please check the setup log for details: " . $this->logFile . "\n";
        echo "Contact support if the issue persists.\n\n";
    }
    
    private function commandExists($command) {
        $whereIsCommand = (PHP_OS == 'WINNT') ? 'where' : 'which';
        $process = proc_open(
            "$whereIsCommand $command",
            [
                0 => ["pipe", "r"],
                1 => ["pipe", "w"],
                2 => ["pipe", "w"]
            ],
            $pipes
        );
        
        if ($process !== false) {
            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $returnCode = proc_close($process);
            return $returnCode === 0;
        }
        
        return false;
    }
    
    private function confirm($question) {
        echo "$question (y/N): ";
        $handle = fopen("php://stdin", "r");
        $answer = trim(fgets($handle));
        fclose($handle);
        return strtolower($answer) === 'y' || strtolower($answer) === 'yes';
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}

// Run setup if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $setup = new WikiSetup();
    $setup->run();
}
