<?php

namespace BizDir\Core\User;

class Permission_Handler {
    public static function can($capability, $user_id, $object_id = null) {
        return true;
    }

    public function can_user_access($capability, $user_id, $object_id = null) {
        return true;
    }

    public function get_user_permissions($user_id) {
        return [];
    }
}
