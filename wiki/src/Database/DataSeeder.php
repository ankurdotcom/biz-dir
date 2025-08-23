<?php
/**
 * Database Seeder
 * Handles initial data population with proper validation and error handling
 */

namespace BizDirWiki\Database;

use PDO;
use PDOException;

class DataSeeder {
    private PDO $db;
    private string $logFile;
    private array $seededData = [];
    
    public function __construct(PDO $db, string $logFile) {
        $this->db = $db;
        $this->logFile = $logFile;
    }
    
    /**
     * Seed all initial data
     */
    public function seedAll(): bool {
        $this->log("Starting data seeding process");
        
        try {
            $this->db->beginTransaction();
            
            // Seed in dependency order
            $this->seedRoles();
            $this->seedUsers();
            $this->seedUserRoles();
            $this->seedCategories();
            
            $this->db->commit();
            $this->log("Data seeding completed successfully");
            
            return true;
            
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            
            $this->log("ERROR: Data seeding failed - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Seed roles table
     */
    private function seedRoles(): void {
        $this->log("Seeding roles...");
        
        $roles = [
            [
                'name' => 'executive',
                'display_name' => 'Executive',
                'description' => 'C-level executives with access to strategic information',
                'permissions' => json_encode(['read', 'edit', 'create', 'comment', 'view_all', 'executive_dashboard', 'admin']),
                'access_level' => 10
            ],
            [
                'name' => 'product_owner',
                'display_name' => 'Product Owner',
                'description' => 'Product owners and managers',
                'permissions' => json_encode(['read', 'edit', 'create', 'comment', 'view_requirements', 'manage_features']),
                'access_level' => 5
            ],
            [
                'name' => 'project_manager',
                'display_name' => 'Project Manager',
                'description' => 'Project managers and coordinators',
                'permissions' => json_encode(['read', 'edit', 'create', 'comment', 'view_timelines', 'manage_resources']),
                'access_level' => 5
            ],
            [
                'name' => 'developer',
                'display_name' => 'Developer',
                'description' => 'Software developers and engineers',
                'permissions' => json_encode(['read', 'edit', 'create', 'comment', 'view_code', 'view_api_docs']),
                'access_level' => 3
            ],
            [
                'name' => 'qa',
                'display_name' => 'QA Engineer',
                'description' => 'Quality assurance and testing team',
                'permissions' => json_encode(['read', 'edit', 'create', 'comment', 'view_test_docs', 'manage_bugs']),
                'access_level' => 3
            ],
            [
                'name' => 'operations',
                'display_name' => 'Operations',
                'description' => 'DevOps and operations team',
                'permissions' => json_encode(['read', 'edit', 'create', 'comment', 'view_infra_docs', 'manage_deployments']),
                'access_level' => 4
            ]
        ];
        
        $stmt = $this->db->prepare("
            INSERT OR REPLACE INTO roles (name, display_name, description, permissions, access_level) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($roles as $role) {
            $result = $stmt->execute([
                $role['name'],
                $role['display_name'],
                $role['description'],
                $role['permissions'],
                $role['access_level']
            ]);
            
            if (!$result) {
                throw new \Exception("Failed to insert role: " . $role['name']);
            }
            
            $this->seededData['roles'][] = $role['name'];
        }
        
        $this->log("Successfully seeded " . count($roles) . " roles");
    }
    
    /**
     * Seed users table
     */
    private function seedUsers(): void {
        $this->log("Seeding users...");
        
        $users = [
            [
                'username' => 'admin',
                'name' => 'System Administrator',
                'email' => 'admin@bizdir.local',
                'password' => 'admin123',
                'department' => 'IT',
                'job_title' => 'System Administrator'
            ],
            [
                'username' => 'ceo',
                'name' => 'Chief Executive Officer',
                'email' => 'ceo@bizdir.local',
                'password' => 'ceo123',
                'department' => 'Executive',
                'job_title' => 'CEO'
            ],
            [
                'username' => 'cto',
                'name' => 'Chief Technology Officer',
                'email' => 'cto@bizdir.local',
                'password' => 'cto123',
                'department' => 'Technology',
                'job_title' => 'CTO'
            ],
            [
                'username' => 'pm',
                'name' => 'Project Manager',
                'email' => 'pm@bizdir.local',
                'password' => 'pm123',
                'department' => 'Project Management',
                'job_title' => 'Senior Project Manager'
            ],
            [
                'username' => 'po',
                'name' => 'Product Owner',
                'email' => 'po@bizdir.local',
                'password' => 'po123',
                'department' => 'Product',
                'job_title' => 'Product Owner'
            ],
            [
                'username' => 'dev',
                'name' => 'Senior Developer',
                'email' => 'dev@bizdir.local',
                'password' => 'dev123',
                'department' => 'Engineering',
                'job_title' => 'Senior Software Engineer'
            ],
            [
                'username' => 'qa',
                'name' => 'QA Engineer',
                'email' => 'qa@bizdir.local',
                'password' => 'qa123',
                'department' => 'Quality Assurance',
                'job_title' => 'QA Engineer'
            ],
            [
                'username' => 'ops',
                'name' => 'DevOps Engineer',
                'email' => 'ops@bizdir.local',
                'password' => 'ops123',
                'department' => 'Operations',
                'job_title' => 'DevOps Engineer'
            ]
        ];
        
        $stmt = $this->db->prepare("
            INSERT OR REPLACE INTO users (username, name, email, password_hash, department, job_title, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, datetime('now'))
        ");
        
        foreach ($users as $user) {
            $result = $stmt->execute([
                $user['username'],
                $user['name'],
                $user['email'],
                password_hash($user['password'], PASSWORD_DEFAULT),
                $user['department'],
                $user['job_title']
            ]);
            
            if (!$result) {
                throw new \Exception("Failed to insert user: " . $user['username']);
            }
            
            $this->seededData['users'][] = $user['username'];
        }
        
        $this->log("Successfully seeded " . count($users) . " users");
    }
    
    /**
     * Seed user roles mapping
     */
    private function seedUserRoles(): void {
        $this->log("Seeding user roles mapping...");
        
        $userRoles = [
            ['username' => 'admin', 'role' => 'executive'],
            ['username' => 'ceo', 'role' => 'executive'],
            ['username' => 'cto', 'role' => 'executive'],
            ['username' => 'pm', 'role' => 'project_manager'],
            ['username' => 'po', 'role' => 'product_owner'],
            ['username' => 'dev', 'role' => 'developer'],
            ['username' => 'qa', 'role' => 'qa'],
            ['username' => 'ops', 'role' => 'operations']
        ];
        
        $stmt = $this->db->prepare("
            INSERT OR REPLACE INTO user_roles (user_id, role_id, assigned_at) 
            VALUES (
                (SELECT id FROM users WHERE username = ?),
                (SELECT id FROM roles WHERE name = ?),
                datetime('now')
            )
        ");
        
        foreach ($userRoles as $mapping) {
            $result = $stmt->execute([
                $mapping['username'],
                $mapping['role']
            ]);
            
            if (!$result) {
                throw new \Exception("Failed to assign role {$mapping['role']} to user {$mapping['username']}");
            }
            
            $this->seededData['user_roles'][] = $mapping;
        }
        
        $this->log("Successfully seeded " . count($userRoles) . " user role mappings");
    }
    
    /**
     * Seed categories table
     */
    private function seedCategories(): void {
        $this->log("Seeding categories...");
        
        $categories = [
            [
                'name' => 'Executive Summary',
                'slug' => 'executive-summary',
                'description' => 'High-level strategic information for executives',
                'icon' => 'chart-bar',
                'color' => '#dc2626',
                'required_role_level' => 10
            ],
            [
                'name' => 'Technical Documentation',
                'slug' => 'technical-docs',
                'description' => 'Developer and technical documentation',
                'icon' => 'code',
                'color' => '#2563eb',
                'required_role_level' => 3
            ],
            [
                'name' => 'QA & Testing',
                'slug' => 'qa-testing',
                'description' => 'Testing procedures and quality assurance',
                'icon' => 'bug',
                'color' => '#dc2626',
                'required_role_level' => 3
            ],
            [
                'name' => 'Operations',
                'slug' => 'operations',
                'description' => 'Deployment and operational procedures',
                'icon' => 'server',
                'color' => '#059669',
                'required_role_level' => 4
            ],
            [
                'name' => 'Project Management',
                'slug' => 'project-management',
                'description' => 'Project plans and management documentation',
                'icon' => 'calendar',
                'color' => '#7c3aed',
                'required_role_level' => 5
            ],
            [
                'name' => 'Product Requirements',
                'slug' => 'product-requirements',
                'description' => 'Product specifications and requirements',
                'icon' => 'package',
                'color' => '#ea580c',
                'required_role_level' => 5
            ]
        ];
        
        $stmt = $this->db->prepare("
            INSERT OR REPLACE INTO categories (name, slug, description, icon, color, required_role_level, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, datetime('now'))
        ");
        
        foreach ($categories as $category) {
            $result = $stmt->execute([
                $category['name'],
                $category['slug'],
                $category['description'],
                $category['icon'],
                $category['color'],
                $category['required_role_level']
            ]);
            
            if (!$result) {
                throw new \Exception("Failed to insert category: " . $category['name']);
            }
            
            $this->seededData['categories'][] = $category['slug'];
        }
        
        $this->log("Successfully seeded " . count($categories) . " categories");
    }
    
    /**
     * Validate seeded data
     */
    public function validateSeededData(): bool {
        $this->log("Validating seeded data...");
        
        // Check roles count
        $roleCount = $this->db->query("SELECT COUNT(*) FROM roles")->fetchColumn();
        if ($roleCount < 6) {
            $this->log("ERROR: Expected at least 6 roles, found $roleCount");
            return false;
        }
        
        // Check users count
        $userCount = $this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        if ($userCount < 8) {
            $this->log("ERROR: Expected at least 8 users, found $userCount");
            return false;
        }
        
        // Check user roles mapping
        $userRoleCount = $this->db->query("SELECT COUNT(*) FROM user_roles")->fetchColumn();
        if ($userRoleCount < 8) {
            $this->log("ERROR: Expected at least 8 user role mappings, found $userRoleCount");
            return false;
        }
        
        // Check categories count
        $categoryCount = $this->db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
        if ($categoryCount < 6) {
            $this->log("ERROR: Expected at least 6 categories, found $categoryCount");
            return false;
        }
        
        $this->log("Data validation passed successfully");
        return true;
    }
    
    /**
     * Get seeding report
     */
    public function getSeededData(): array {
        return $this->seededData;
    }
    
    /**
     * Log message with timestamp
     */
    private function log(string $message): void {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] DataSeeder: $message\n";
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}
