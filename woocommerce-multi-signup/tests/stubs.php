<?php
/**
 * Minimal stand-ins for WordPress, WooCommerce and LifterLMS Groups classes that the
 * plugin references but that are not available outside of a WordPress install.
 */

namespace Automattic\WooCommerce\StoreApi\Exceptions {
    if (!class_exists(RouteException::class)) {
        class RouteException extends \Exception {
            public $error_code;
            public $http_status_code;

            public function __construct($error_code, $message, $http_status_code = 400, $additional_data = []) {
                parent::__construct($message);
                $this->error_code = $error_code;
                $this->http_status_code = $http_status_code;
            }
        }
    }
}

namespace {
    if (!class_exists('WP_Error')) {
        class WP_Error {
            public $code;
            public $message;

            public function __construct($code = '', $message = '') {
                $this->code = $code;
                $this->message = $message;
            }

            public function get_error_message() {
                return $this->message;
            }
        }
    }

    if (!class_exists('WC_Order')) {
        class WC_Order {}
    }

    if (!class_exists('WP_REST_Request')) {
        class WP_REST_Request implements ArrayAccess {
            public $method = 'POST';
            public $params = [];

            public function get_method() {
                return $this->method;
            }

            public function get_param($key) {
                return $this->params[$key] ?? null;
            }

            public function offsetExists($offset): bool {
                return isset($this->params[$offset]);
            }

            #[\ReturnTypeWillChange]
            public function offsetGet($offset) {
                return $this->params[$offset] ?? null;
            }

            public function offsetSet($offset, $value): void {
                $this->params[$offset] = $value;
            }

            public function offsetUnset($offset): void {
                unset($this->params[$offset]);
            }
        }
    }

    /**
     * Stand-in for the LifterLMS Groups model so the plugin's class_exists() check passes and
     * Mockery mocks (see createMockGroup()) extend a real class.
     */
    if (!class_exists('LLMS_Group')) {
        #[\AllowDynamicProperties]
        class LLMS_Group {
            public function __construct($model = null, $args = []) {}
            public function get($key) { return ''; }
            public function set($key, $value = '') { return true; }
            public function get_seats($use_cache = true) { return ['total' => 0, 'used' => 0, 'open' => 0]; }
        }
    }

    /**
     * Records every call so tests can assert on who was added to which group.
     */
    if (!class_exists('LLMS_Groups_Enrollment')) {
        class LLMS_Groups_Enrollment {
            public static $calls = [];
            public static $return = true;

            public static function reset() {
                self::$calls = [];
                self::$return = true;
                self::$roles = [];
                self::$role_updates = [];
            }

            /** @var array<int,array<int,string>> user ID => group ID => role, for get_role(). */
            public static $roles = [];

            public static function add($user_id, $group_id, $trigger = 'unspecified', $role = 'member') {
                self::$calls[] = compact('user_id', 'group_id', 'trigger', 'role');
                return self::$return;
            }

            public static function get_role($user_id, $group_id) {
                return self::$roles[$user_id][$group_id] ?? '';
            }

            /** @var array<int,array> Arguments of every update_role() call. */
            public static $role_updates = [];

            public static function update_role($user_id, $group_id, $role) {
                self::$role_updates[] = [$user_id, $group_id, $role];
                self::$roles[$user_id][$group_id] = 'primary_admin' === $role ? 'admin' : $role;
                return true;
            }
        }
    }

    if (!class_exists('LLMS_Groups_Profile')) {
        class LLMS_Groups_Profile {
            public static function get_tab_slug($tab) {
                return $tab;
            }
        }
    }

    if (!class_exists('LLMS_Student_Dashboard')) {
        class LLMS_Student_Dashboard {
            /** @var string[] Endpoints reported as enabled. */
            public static $enabled = ['view-groups'];

            public static function is_endpoint_enabled($endpoint) {
                return in_array($endpoint, self::$enabled, true);
            }
        }
    }
}
