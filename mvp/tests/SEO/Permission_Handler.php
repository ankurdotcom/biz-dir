<?php

namespace BizDir\Tests\SEO;

class Permission_Handler {
    public function can_manage_business($business_id = null): bool {
        return true;
    }

    public function can_view_business($business_id): bool {
        return true;
    }

    public function can_edit_business($business_id): bool {
        return true;
    }

    public function can_delete_business($business_id): bool {
        return true;
    }

    public function can_manage_reviews($business_id = null): bool {
        return true;
    }

    public function can_add_review($business_id): bool {
        return true;
    }

    public function can_edit_review($review_id): bool {
        return true;
    }

    public function can_delete_review($review_id): bool {
        return true;
    }

    public function can_manage_seo(): bool {
        return true;
    }
}
