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
     * Records every call so tests can assert on who was added to which group.
     */
    if (!class_exists('LLMS_Groups_Enrollment')) {
        class LLMS_Groups_Enrollment {
            public static $calls = [];
            public static $return = true;

            public static function reset() {
                self::$calls = [];
                self::$return = true;
            }

            public static function add($user_id, $group_id, $trigger = 'unspecified', $role = 'member') {
                self::$calls[] = compact('user_id', 'group_id', 'trigger', 'role');
                return self::$return;
            }
        }
    }
}
