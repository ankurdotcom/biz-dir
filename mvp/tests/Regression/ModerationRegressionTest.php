<?php
/**
 * Moderation Regression Tests
 * Tests content moderation workflows, approval processes, and flagging systems
 */

require_once __DIR__ . '/RegressionTestCase.php';

class ModerationRegressionTest extends RegressionTestCase
{
    private $sampleContent;
    private $moderationRules;
    private $testModerators;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->sampleContent = [
            'business_listing' => [
                'name' => 'Test Business',
                'description' => 'A legitimate business description',
                'address' => '123 Main St, Test City',
                'phone' => '+1-555-123-4567',
                'category' => 'restaurant',
            ],
            'review' => [
                'business_id' => 1,
                'user_id' => 1,
                'rating' => 5,
                'title' => 'Great service!',
                'content' => 'Had an amazing experience here. Highly recommended!',
            ],
            'spam_content' => [
                'title' => 'FREE VIAGRA CLICK HERE!!!',
                'content' => 'Buy cheap medications online! No prescription needed! Visit our website now!!!',
            ],
            'inappropriate_content' => [
                'title' => 'Terrible place',
                'content' => 'This place is absolutely terrible and the staff are idiots who should be fired.',
            ],
        ];
        
        $this->moderationRules = [
            'spam_keywords' => ['viagra', 'cheap medications', 'no prescription', 'click here', 'free money'],
            'inappropriate_keywords' => ['idiot', 'stupid', 'hate', 'terrible service'],
            'max_caps_percentage' => 50,
            'max_exclamation_marks' => 3,
            'min_word_count' => 5,
            'max_word_count' => 1000,
            'required_fields' => ['name', 'description', 'category'],
        ];
        
        $this->testModerators = [
            ['id' => 1, 'username' => 'moderator1', 'role' => 'moderator'],
            ['id' => 2, 'username' => 'senior_mod', 'role' => 'senior_moderator'],
            ['id' => 3, 'username' => 'admin', 'role' => 'administrator'],
        ];
    }
    
    /**
     * Test content flagging system
     */
    public function testContentFlaggingSystem()
    {
        $this->startPerformanceTracking(__FUNCTION__);
        
        // Test legitimate content (should not be flagged)
        $flags = $this->checkContentFlags($this->sampleContent['business_listing']['description']);
        $this->assertEmpty($flags, 'Legitimate content should not be flagged');
        
        // Test spam content
        $spamFlags = $this->checkContentFlags($this->sampleContent['spam_content']['content']);
        $this->assertNotEmpty($spamFlags, 'Spam content should be flagged');
        $this->assertContains('spam', $spamFlags, 'Should be flagged as spam');
        
        // Test inappropriate content
        $inappropriateFlags = $this->checkContentFlags($this->sampleContent['inappropriate_content']['content']);
        $this->assertNotEmpty($inappropriateFlags, 'Inappropriate content should be flagged');
        $this->assertContains('inappropriate_language', $inappropriateFlags, 'Should be flagged for inappropriate language');
        
        // Test excessive caps
        $capsContent = 'THIS IS ALL CAPS AND SHOULD BE FLAGGED!!!';
        $capsFlags = $this->checkContentFlags($capsContent);
        $this->assertContains('excessive_caps', $capsFlags, 'Excessive caps should be flagged');
        
        // Test excessive exclamation marks
        $exclamationContent = 'Great place!!!!!!!!';
        $exclamationFlags = $this->checkContentFlags($exclamationContent);
        $this->assertContains('excessive_exclamation', $exclamationFlags, 'Excessive exclamation marks should be flagged');
        
        $this->endPerformanceTracking(__FUNCTION__);
    }
    
    /**
     * Test moderation workflow
     */
    public function testModerationWorkflow()
    {
        $this->startPerformanceTracking(__FUNCTION__);
        
        // Test content submission and auto-moderation
        $contentId = $this->submitContent($this->sampleContent['review']);
        $this->assertIsNumeric($contentId, 'Content submission should return valid ID');
        
        $status = $this->getContentStatus($contentId);
        $this->assertContains($status, ['pending', 'approved', 'flagged'], 'Content should have valid status');
        
        // Test flagged content workflow
        $flaggedContentId = $this->submitContent($this->sampleContent['spam_content']);
        $flaggedStatus = $this->getContentStatus($flaggedContentId);
        $this->assertEquals('flagged', $flaggedStatus, 'Spam content should be automatically flagged');
        
        // Test moderator review
        $reviewResult = $this->moderatorReview($flaggedContentId, $this->testModerators[0]['id'], 'reject', 'Spam content');
        $this->assertTrue($reviewResult, 'Moderator should be able to review content');
        
        $newStatus = $this->getContentStatus($flaggedContentId);
        $this->assertEquals('rejected', $newStatus, 'Content should be rejected after moderator review');
        
        // Test appeal process
        $appealId = $this->submitAppeal($flaggedContentId, 'This is not spam, please review again');
        $this->assertIsNumeric($appealId, 'Appeal submission should return valid ID');
        
        $appealStatus = $this->getAppealStatus($appealId);
        $this->assertEquals('pending', $appealStatus, 'Appeal should be pending initially');
        
        $this->endPerformanceTracking(__FUNCTION__);
    }
    
    /**
     * Test moderator permissions and roles
     */
    public function testModeratorPermissions()
    {
        $this->startPerformanceTracking(__FUNCTION__);
        
        // Test basic moderator permissions
        $basicModerator = $this->testModerators[0];
        $this->assertTrue($this->canReviewContent($basicModerator['id']), 'Basic moderator should be able to review content');
        $this->assertTrue($this->canApproveContent($basicModerator['id']), 'Basic moderator should be able to approve content');
        $this->assertTrue($this->canRejectContent($basicModerator['id']), 'Basic moderator should be able to reject content');
        $this->assertFalse($this->canDeleteContent($basicModerator['id']), 'Basic moderator should not be able to delete content');
        
        // Test senior moderator permissions
        $seniorModerator = $this->testModerators[1];
        $this->assertTrue($this->canReviewContent($seniorModerator['id']), 'Senior moderator should be able to review content');
        $this->assertTrue($this->canApproveContent($seniorModerator['id']), 'Senior moderator should be able to approve content');
        $this->assertTrue($this->canRejectContent($seniorModerator['id']), 'Senior moderator should be able to reject content');
        $this->assertTrue($this->canDeleteContent($seniorModerator['id']), 'Senior moderator should be able to delete content');
        $this->assertTrue($this->canReviewAppeals($seniorModerator['id']), 'Senior moderator should be able to review appeals');
        
        // Test administrator permissions
        $administrator = $this->testModerators[2];
        $this->assertTrue($this->canReviewContent($administrator['id']), 'Administrator should be able to review content');
        $this->assertTrue($this->canDeleteContent($administrator['id']), 'Administrator should be able to delete content');
        $this->assertTrue($this->canManageModerators($administrator['id']), 'Administrator should be able to manage moderators');
        $this->assertTrue($this->canUpdateModerationRules($administrator['id']), 'Administrator should be able to update rules');
        
        $this->endPerformanceTracking(__FUNCTION__);
    }
    
    /**
     * Test automated moderation rules
     */
    public function testAutomatedModerationRules()
    {
        $this->startPerformanceTracking(__FUNCTION__);
        
        // Test spam detection
        foreach ($this->moderationRules['spam_keywords'] as $keyword) {
            $content = "This content contains $keyword and should be flagged";
            $flags = $this->checkContentFlags($content);
            $this->assertContains('spam', $flags, "Content with '$keyword' should be flagged as spam");
        }
        
        // Test word count validation
        $shortContent = 'Too short';
        $shortFlags = $this->checkContentFlags($shortContent);
        $this->assertContains('too_short', $shortFlags, 'Content below minimum word count should be flagged');
        
        $longContent = str_repeat('word ', 1001);
        $longFlags = $this->checkContentFlags($longContent);
        $this->assertContains('too_long', $longFlags, 'Content above maximum word count should be flagged');
        
        // Test duplicate content detection
        $originalContent = 'This is unique content for testing';
        $duplicateContent = 'This is unique content for testing';
        
        $originalId = $this->submitContent(['content' => $originalContent]);
        $duplicateId = $this->submitContent(['content' => $duplicateContent]);
        
        $duplicateFlags = $this->getContentFlags($duplicateId);
        $this->assertContains('duplicate_content', $duplicateFlags, 'Duplicate content should be flagged');
        
        $this->endPerformanceTracking(__FUNCTION__);
    }
    
    /**
     * Test moderation queue management
     */
    public function testModerationQueueManagement()
    {
        $this->startPerformanceTracking(__FUNCTION__);
        
        // Create test content for queue
        $pendingIds = [];
        for ($i = 0; $i < 5; $i++) {
            $pendingIds[] = $this->submitContent([
                'title' => "Test Content $i",
                'content' => "This is test content number $i for queue testing",
            ]);
        }
        
        // Test queue retrieval
        $queue = $this->getModerationQueue();
        $this->assertIsArray($queue, 'Moderation queue should be an array');
        $this->assertGreaterThanOrEqual(5, count($queue), 'Queue should contain submitted content');
        
        // Test queue filtering
        $priorityQueue = $this->getModerationQueue(['priority' => 'high']);
        $this->assertIsArray($priorityQueue, 'Priority queue should be an array');
        
        $flaggedQueue = $this->getModerationQueue(['status' => 'flagged']);
        $this->assertIsArray($flaggedQueue, 'Flagged queue should be an array');
        
        // Test queue assignment
        $moderatorId = $this->testModerators[0]['id'];
        $assignResult = $this->assignToModerator($pendingIds[0], $moderatorId);
        $this->assertTrue($assignResult, 'Content should be assignable to moderator');
        
        $assignedQueue = $this->getModerationQueue(['assigned_to' => $moderatorId]);
        $this->assertGreaterThan(0, count($assignedQueue), 'Moderator should have assigned content');
        
        $this->endPerformanceTracking(__FUNCTION__);
    }
    
    /**
     * Test bulk moderation operations
     */
    public function testBulkModerationOperations()
    {
        $this->startPerformanceTracking(__FUNCTION__);
        
        // Create multiple test content items
        $contentIds = [];
        for ($i = 0; $i < 10; $i++) {
            $contentIds[] = $this->submitContent([
                'title' => "Bulk Test Content $i",
                'content' => "This is bulk test content number $i",
            ]);
        }
        
        // Test bulk approval
        $approvalResult = $this->bulkApprove(array_slice($contentIds, 0, 5), $this->testModerators[1]['id']);
        $this->assertTrue($approvalResult, 'Bulk approval should succeed');
        
        foreach (array_slice($contentIds, 0, 5) as $id) {
            $status = $this->getContentStatus($id);
            $this->assertEquals('approved', $status, 'Bulk approved content should have approved status');
        }
        
        // Test bulk rejection
        $rejectionResult = $this->bulkReject(array_slice($contentIds, 5, 5), $this->testModerators[1]['id'], 'Bulk rejection test');
        $this->assertTrue($rejectionResult, 'Bulk rejection should succeed');
        
        foreach (array_slice($contentIds, 5, 5) as $id) {
            $status = $this->getContentStatus($id);
            $this->assertEquals('rejected', $status, 'Bulk rejected content should have rejected status');
        }
        
        $this->endPerformanceTracking(__FUNCTION__);
    }
    
    /**
     * Test moderation analytics and reporting
     */
    public function testModerationAnalytics()
    {
        $this->startPerformanceTracking(__FUNCTION__);
        
        // Test basic statistics
        $stats = $this->getModerationStats();
        $this->assertIsArray($stats, 'Moderation stats should be an array');
        $this->assertArrayHasKey('total_submissions', $stats);
        $this->assertArrayHasKey('pending_count', $stats);
        $this->assertArrayHasKey('approved_count', $stats);
        $this->assertArrayHasKey('rejected_count', $stats);
        $this->assertArrayHasKey('flagged_count', $stats);
        
        // Test moderator performance stats
        $moderatorStats = $this->getModeratorStats($this->testModerators[0]['id']);
        $this->assertIsArray($moderatorStats, 'Moderator stats should be an array');
        $this->assertArrayHasKey('reviews_completed', $moderatorStats);
        $this->assertArrayHasKey('approvals_made', $moderatorStats);
        $this->assertArrayHasKey('rejections_made', $moderatorStats);
        $this->assertArrayHasKey('average_review_time', $moderatorStats);
        
        // Test trend analytics
        $trends = $this->getModerationTrends(30); // Last 30 days
        $this->assertIsArray($trends, 'Trends should be an array');
        $this->assertArrayHasKey('daily_submissions', $trends);
        $this->assertArrayHasKey('approval_rate', $trends);
        $this->assertArrayHasKey('common_flags', $trends);
        
        $this->endPerformanceTracking(__FUNCTION__);
    }
    
    /**
     * Test escalation workflow
     */
    public function testEscalationWorkflow()
    {
        $this->startPerformanceTracking(__FUNCTION__);
        
        // Submit controversial content
        $controversialContent = [
            'title' => 'Controversial Review',
            'content' => 'This business has some issues that need to be discussed carefully',
        ];
        
        $contentId = $this->submitContent($controversialContent);
        
        // Basic moderator flags for escalation
        $escalationResult = $this->escalateToSenior($contentId, $this->testModerators[0]['id'], 'Needs senior review');
        $this->assertTrue($escalationResult, 'Content should be escalatable to senior moderator');
        
        $status = $this->getContentStatus($contentId);
        $this->assertEquals('escalated', $status, 'Escalated content should have escalated status');
        
        // Senior moderator review
        $seniorReview = $this->moderatorReview($contentId, $this->testModerators[1]['id'], 'approve', 'Content is acceptable');
        $this->assertTrue($seniorReview, 'Senior moderator should be able to review escalated content');
        
        // Test appeal escalation
        $rejectedContentId = $this->submitContent($this->sampleContent['spam_content']);
        $this->moderatorReview($rejectedContentId, $this->testModerators[0]['id'], 'reject', 'Spam');
        
        $appealId = $this->submitAppeal($rejectedContentId, 'This is not spam');
        $appealEscalation = $this->escalateAppeal($appealId, $this->testModerators[1]['id']);
        $this->assertTrue($appealEscalation, 'Appeal should be escalatable');
        
        $this->endPerformanceTracking(__FUNCTION__);
    }
    
    // Helper methods for moderation testing
    
    private function checkContentFlags($content)
    {
        $flags = [];
        
        // Check for spam keywords
        foreach ($this->moderationRules['spam_keywords'] as $keyword) {
            if (stripos($content, $keyword) !== false) {
                $flags[] = 'spam';
                break;
            }
        }
        
        // Check for inappropriate keywords
        foreach ($this->moderationRules['inappropriate_keywords'] as $keyword) {
            if (stripos($content, $keyword) !== false) {
                $flags[] = 'inappropriate_language';
                break;
            }
        }
        
        // Check caps percentage
        $totalChars = strlen($content);
        $capsChars = strlen(preg_replace('/[^A-Z]/', '', $content));
        if ($totalChars > 0 && ($capsChars / $totalChars * 100) > $this->moderationRules['max_caps_percentage']) {
            $flags[] = 'excessive_caps';
        }
        
        // Check exclamation marks
        $exclamationCount = substr_count($content, '!');
        if ($exclamationCount > $this->moderationRules['max_exclamation_marks']) {
            $flags[] = 'excessive_exclamation';
        }
        
        // Check word count
        $wordCount = str_word_count($content);
        if ($wordCount < $this->moderationRules['min_word_count']) {
            $flags[] = 'too_short';
        }
        if ($wordCount > $this->moderationRules['max_word_count']) {
            $flags[] = 'too_long';
        }
        
        return array_unique($flags);
    }
    
    private function submitContent($content)
    {
        static $contentCounter = 1;
        
        // Simulate content submission and return ID
        $flags = $this->checkContentFlags($content['content'] ?? '');
        $status = empty($flags) ? 'pending' : 'flagged';
        
        // Store in mock database
        $this->mockDatabase['content'][$contentCounter] = [
            'id' => $contentCounter,
            'content' => $content,
            'status' => $status,
            'flags' => $flags,
            'created_at' => time(),
        ];
        
        return $contentCounter++;
    }
    
    private function getContentStatus($contentId)
    {
        return $this->mockDatabase['content'][$contentId]['status'] ?? 'unknown';
    }
    
    private function getContentFlags($contentId)
    {
        return $this->mockDatabase['content'][$contentId]['flags'] ?? [];
    }
    
    private function moderatorReview($contentId, $moderatorId, $action, $reason)
    {
        if (!isset($this->mockDatabase['content'][$contentId])) {
            return false;
        }
        
        $newStatus = $action === 'approve' ? 'approved' : 'rejected';
        $this->mockDatabase['content'][$contentId]['status'] = $newStatus;
        $this->mockDatabase['content'][$contentId]['reviewed_by'] = $moderatorId;
        $this->mockDatabase['content'][$contentId]['review_reason'] = $reason;
        $this->mockDatabase['content'][$contentId]['reviewed_at'] = time();
        
        return true;
    }
    
    private function submitAppeal($contentId, $reason)
    {
        static $appealCounter = 1;
        
        $this->mockDatabase['appeals'][$appealCounter] = [
            'id' => $appealCounter,
            'content_id' => $contentId,
            'reason' => $reason,
            'status' => 'pending',
            'created_at' => time(),
        ];
        
        return $appealCounter++;
    }
    
    private function getAppealStatus($appealId)
    {
        return $this->mockDatabase['appeals'][$appealId]['status'] ?? 'unknown';
    }
    
    private function canReviewContent($moderatorId)
    {
        return $this->hasPermission($moderatorId, 'review_content');
    }
    
    private function canApproveContent($moderatorId)
    {
        return $this->hasPermission($moderatorId, 'approve_content');
    }
    
    private function canRejectContent($moderatorId)
    {
        return $this->hasPermission($moderatorId, 'reject_content');
    }
    
    private function canDeleteContent($moderatorId)
    {
        return $this->hasPermission($moderatorId, 'delete_content');
    }
    
    private function canReviewAppeals($moderatorId)
    {
        return $this->hasPermission($moderatorId, 'review_appeals');
    }
    
    private function canManageModerators($moderatorId)
    {
        return $this->hasPermission($moderatorId, 'manage_moderators');
    }
    
    private function canUpdateModerationRules($moderatorId)
    {
        return $this->hasPermission($moderatorId, 'update_rules');
    }
    
    private function hasPermission($moderatorId, $permission)
    {
        $moderator = null;
        foreach ($this->testModerators as $mod) {
            if ($mod['id'] === $moderatorId) {
                $moderator = $mod;
                break;
            }
        }
        
        if (!$moderator) return false;
        
        $permissions = [
            'moderator' => ['review_content', 'approve_content', 'reject_content'],
            'senior_moderator' => ['review_content', 'approve_content', 'reject_content', 'delete_content', 'review_appeals'],
            'administrator' => ['review_content', 'approve_content', 'reject_content', 'delete_content', 'review_appeals', 'manage_moderators', 'update_rules'],
        ];
        
        return in_array($permission, $permissions[$moderator['role']] ?? []);
    }
    
    private function getModerationQueue($filters = [])
    {
        $queue = [];
        foreach ($this->mockDatabase['content'] ?? [] as $content) {
            if (!empty($filters['status']) && $content['status'] !== $filters['status']) {
                continue;
            }
            if (!empty($filters['assigned_to']) && ($content['assigned_to'] ?? null) !== $filters['assigned_to']) {
                continue;
            }
            $queue[] = $content;
        }
        return $queue;
    }
    
    private function assignToModerator($contentId, $moderatorId)
    {
        if (isset($this->mockDatabase['content'][$contentId])) {
            $this->mockDatabase['content'][$contentId]['assigned_to'] = $moderatorId;
            return true;
        }
        return false;
    }
    
    private function bulkApprove($contentIds, $moderatorId)
    {
        foreach ($contentIds as $id) {
            $this->moderatorReview($id, $moderatorId, 'approve', 'Bulk approval');
        }
        return true;
    }
    
    private function bulkReject($contentIds, $moderatorId, $reason)
    {
        foreach ($contentIds as $id) {
            $this->moderatorReview($id, $moderatorId, 'reject', $reason);
        }
        return true;
    }
    
    private function getModerationStats()
    {
        $content = $this->mockDatabase['content'] ?? [];
        $stats = [
            'total_submissions' => count($content),
            'pending_count' => 0,
            'approved_count' => 0,
            'rejected_count' => 0,
            'flagged_count' => 0,
        ];
        
        foreach ($content as $item) {
            $stats[$item['status'] . '_count']++;
        }
        
        return $stats;
    }
    
    private function getModeratorStats($moderatorId)
    {
        $reviewsCompleted = 0;
        $approvals = 0;
        $rejections = 0;
        
        foreach ($this->mockDatabase['content'] ?? [] as $content) {
            if (($content['reviewed_by'] ?? null) === $moderatorId) {
                $reviewsCompleted++;
                if ($content['status'] === 'approved') $approvals++;
                if ($content['status'] === 'rejected') $rejections++;
            }
        }
        
        return [
            'reviews_completed' => $reviewsCompleted,
            'approvals_made' => $approvals,
            'rejections_made' => $rejections,
            'average_review_time' => 300, // 5 minutes average
        ];
    }
    
    private function getModerationTrends($days)
    {
        return [
            'daily_submissions' => array_fill(0, $days, rand(10, 50)),
            'approval_rate' => 85.5,
            'common_flags' => ['spam', 'inappropriate_language', 'duplicate_content'],
        ];
    }
    
    private function escalateToSenior($contentId, $moderatorId, $reason)
    {
        if (isset($this->mockDatabase['content'][$contentId])) {
            $this->mockDatabase['content'][$contentId]['status'] = 'escalated';
            $this->mockDatabase['content'][$contentId]['escalated_by'] = $moderatorId;
            $this->mockDatabase['content'][$contentId]['escalation_reason'] = $reason;
            return true;
        }
        return false;
    }
    
    private function escalateAppeal($appealId, $moderatorId)
    {
        if (isset($this->mockDatabase['appeals'][$appealId])) {
            $this->mockDatabase['appeals'][$appealId]['status'] = 'escalated';
            $this->mockDatabase['appeals'][$appealId]['escalated_by'] = $moderatorId;
            return true;
        }
        return false;
    }
    
    private $mockDatabase = [
        'content' => [],
        'appeals' => [],
    ];
}
