<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregSanitizationService {
    /**
     * Sanitize email templates using wp_kses.
     *
     * @param string $template The email template content.
     * @return string The sanitized email template.
     */
    public static function sanitizeEmailTemplate($template) {
         // Remove backslashes
         $template = str_replace('\\', '', $template);

        return wp_kses($template, []);
    }

    /**
     * Validate a registration code. Registration codes are always generated as
     * substr(md5(microtime()), 0, 10), i.e. a 10 character lowercase hex string.
     *
     * @param mixed $code The value to validate.
     * @return bool True when the value is a valid registration code.
     */
    public static function isValidRegistrationCode($code) {
        return is_string($code) && preg_match('/^[0-9a-f]{10}$/', $code) === 1;
    }

    /**
     * Safely resolve a user supplied file name inside a trusted base directory.
     * Guards against path traversal: the file name must be a bare file name
     * (no directory separators, no "..") and must resolve to a path that is
     * still contained within $baseDir.
     *
     * @param string $baseDir  Trusted base directory.
     * @param string $fileName User supplied file name.
     * @return string|false The absolute path when safe, otherwise false.
     */
    public static function resolvePathInsideBase($baseDir, $fileName) {
        if (!is_string($fileName)) {
            return false;
        }

        $fileName = wp_unslash($fileName);

        // Only allow a bare file name - reject separators and traversal.
        if ($fileName === '' || basename($fileName) !== $fileName || strpos($fileName, '..') !== false) {
            return false;
        }

        $realBase = realpath($baseDir);
        if ($realBase === false) {
            return false;
        }

        $realTarget = realpath($realBase . DIRECTORY_SEPARATOR . $fileName);
        if ($realTarget === false) {
            return false;
        }

        // Ensure the resolved target is still inside the base directory.
        if (strpos($realTarget, $realBase . DIRECTORY_SEPARATOR) !== 0) {
            return false;
        }

        return $realTarget;
    }
}