<?php
/**
 * Business Module Initialization
 *
 * @package BizDir\Core\Business
 */

namespace BizDir\Core\Business;

class Init {
    /**
     * @var Business_Manager
     */
    private $business_manager;

    /**
     * Constructor
     *
     * @param Business_Manager $business_manager
     */
    public function __construct(Business_Manager $business_manager) {
        $this->business_manager = $business_manager;
    }

    /**
     * Initialize the business module
     */
    public function init() {
        error_log('[BizDir Business] Initializing Business Module');
        $this->business_manager->init();
        error_log('[BizDir Business] Business Module initialized successfully');
    }
}
