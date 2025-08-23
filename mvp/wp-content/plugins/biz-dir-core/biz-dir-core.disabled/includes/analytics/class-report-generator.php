<?php
/**
 * Prevent direct access
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Report Generator Class
 *
 * @package BizDir\Core\Analytics
 */

namespace BizDir\Core\Analytics;

use BizDir\Core\User\Permission_Handler;

class Report_Generator {
    const FORMAT_JSON = 'json';
    const FORMAT_CSV = 'csv';
    const FORMAT_PDF = 'pdf';

    /**
     * @var Analytics_Handler
     */
    private $analytics_handler;

    /**
     * Constructor
     *
     * @param Analytics_Handler $analytics_handler Analytics handler instance
     */
    public function __construct(Analytics_Handler $analytics_handler) {
        $this->analytics_handler = $analytics_handler;
    }

    /**
     * Initialize hooks
     */
    public function init() {
        error_log('[BizDir Reports] Initializing hooks');
        add_action('wp_ajax_generate_report', [$this, 'handle_generate_report']);
        add_action('biz_dir_daily_report', [$this, 'generate_daily_report']);
        error_log('[BizDir Reports] Hooks initialized');
    }

    /**
     * Generate business report
     *
     * @param int    $business_id Business ID
     * @param string $period Period type
     * @param string $format Output format
     * @return array|string Report data
     */
    public function generate_business_report($business_id, $period, $format = self::FORMAT_JSON) {
        error_log("[BizDir Reports] Generating business report | id: $business_id, period: $period");

        $metrics = [
            'views' => $this->analytics_handler->get_analytics('views', $period, $business_id),
            'clicks' => $this->analytics_handler->get_analytics('clicks', $period, $business_id),
            'reviews' => $this->analytics_handler->get_analytics('reviews', $period, $business_id),
            'inquiries' => $this->analytics_handler->get_analytics('inquiries', $period, $business_id)
        ];

        $data = [
            'business_id' => $business_id,
            'period' => $period,
            'generated_at' => current_time('mysql'),
            'metrics' => $metrics
        ];

        return $this->format_report($data, $format);
    }

    /**
     * Generate search trends report
     *
     * @param string $period Period type
     * @param string $format Output format
     * @return array|string Report data
     */
    public function generate_search_report($period, $format = self::FORMAT_JSON) {
        error_log("[BizDir Reports] Generating search report | period: $period");

        $data = [
            'period' => $period,
            'generated_at' => current_time('mysql'),
            'trends' => $this->analytics_handler->get_search_analytics($period)
        ];

        return $this->format_report($data, $format);
    }

    /**
     * Format report data
     *
     * @param array  $data Report data
     * @param string $format Output format
     * @return array|string Formatted report
     */
    private function format_report($data, $format) {
        switch ($format) {
            case self::FORMAT_CSV:
                return $this->format_csv($data);
            case self::FORMAT_PDF:
                return $this->format_pdf($data);
            default:
                return $data;
        }
    }

    /**
     * Format report as CSV
     *
     * @param array $data Report data
     * @return string CSV content
     */
    private function format_csv($data) {
        ob_start();
        $csv = fopen('php://output', 'w');

        // Headers
        fputcsv($csv, ['Date', 'Metric', 'Count']);

        // Data rows
        foreach ($data['metrics'] as $metric => $values) {
            foreach ($values as $row) {
                fputcsv($csv, [
                    $row->date,
                    $metric,
                    $row->count
                ]);
            }
        }

        fclose($csv);
        return ob_get_clean();
    }

    /**
     * Format report as PDF
     *
     * @param array $data Report data
     * @return string PDF content
     */
    private function format_pdf($data) {
        // Basic PDF generation - in production, use a proper PDF library
        $content = "Business Report\n\n";
        $content .= "Generated: " . $data['generated_at'] . "\n\n";

        foreach ($data['metrics'] as $metric => $values) {
            $content .= strtoupper($metric) . "\n";
            foreach ($values as $row) {
                $content .= "{$row->date}: {$row->count}\n";
            }
            $content .= "\n";
        }

        return $content;
    }

    /**
     * Generate daily system report
     */
    private function generate_daily_report() {
        error_log('[BizDir Reports] Generating daily system report');

        $data = [
            'date' => current_time('Y-m-d'),
            'metrics' => [
                'total_views' => $this->get_daily_total('views'),
                'total_searches' => $this->get_daily_total('searches'),
                'total_reviews' => $this->get_daily_total('reviews'),
                'total_inquiries' => $this->get_daily_total('inquiries')
            ]
        ];

        // Store report in options for quick access
        update_option('biz_dir_daily_report_' . $data['date'], $data);
        
        do_action('biz_dir_daily_report_generated', $data);
    }

    /**
     * Get daily total for metric
     *
     * @param string $metric Metric type
     * @return int Total count
     */
    private function get_daily_total($metric) {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
            FROM {$wpdb->prefix}biz_analytics_metrics 
            WHERE metric = %s 
            AND DATE(created_at) = CURDATE()",
            $metric
        ));
    }

    /**
     * AJAX handler for report generation
     */
    public function handle_generate_report() {
        if (!Permission_Handler::can('view_analytics')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        $business_id = isset($_GET['business_id']) ? intval($_GET['business_id']) : 0;
        $period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : 'month';
        $format = isset($_GET['format']) ? sanitize_text_field($_GET['format']) : self::FORMAT_JSON;

        if ($business_id) {
            $report = $this->generate_business_report($business_id, $period, $format);
        } else {
            $report = $this->generate_search_report($period, $format);
        }

        if ($format === self::FORMAT_JSON) {
            wp_send_json_success($report);
        } else {
            // For CSV/PDF, trigger download
            $filename = "report-" . date('Y-m-d') . "." . $format;
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo $report;
            exit;
        }
    }
}
