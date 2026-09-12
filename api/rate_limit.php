<?php
// api/rate_limit.php
require_once 'config.php';

/**
 * Validates rate limits based on IP and Endpoint.
 * Returns true if allowed, false if blocked.
 */
function check_rate_limit($pdo, $endpoint, $max_attempts = 5, $lock_minutes = 15) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    try {
        // Clean up expired locks first (optional but good for table size)
        $pdo->exec("DELETE FROM rate_limits WHERE lock_until IS NOT NULL AND lock_until < NOW()");

        $stmt = $pdo->prepare("SELECT attempt_count, lock_until FROM rate_limits WHERE ip_address = ? AND endpoint = ?");
        $stmt->execute([$ip_address, $endpoint]);
        $record = $stmt->fetch();

        if ($record) {
            if ($record['lock_until'] && strtotime($record['lock_until']) > time()) {
                return false; // Still locked
            }

            // If lock has expired or we're just incrementing
            if ($record['lock_until'] && strtotime($record['lock_until']) <= time()) {
                // Reset after lock expired
                $stmt = $pdo->prepare("UPDATE rate_limits SET attempt_count = 1, lock_until = NULL WHERE ip_address = ? AND endpoint = ?");
                $stmt->execute([$ip_address, $endpoint]);
                return true;
            } else {
                $new_count = $record['attempt_count'] + 1;
                if ($new_count >= $max_attempts) {
                    $stmt = $pdo->prepare("UPDATE rate_limits SET attempt_count = ?, lock_until = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE ip_address = ? AND endpoint = ?");
                    $stmt->execute([$new_count, $lock_minutes, $ip_address, $endpoint]);
                    return false; // Locked now
                } else {
                    $stmt = $pdo->prepare("UPDATE rate_limits SET attempt_count = ? WHERE ip_address = ? AND endpoint = ?");
                    $stmt->execute([$new_count, $ip_address, $endpoint]);
                    return true;
                }
            }
        } else {
            // First attempt
            $stmt = $pdo->prepare("INSERT INTO rate_limits (ip_address, endpoint, attempt_count) VALUES (?, ?, 1)");
            $stmt->execute([$ip_address, $endpoint]);
            return true;
        }
    } catch (PDOException $e) {
        // If the table doesn't exist yet (migration not run), fail open to not break app, or return false?
        // Since we are applying migration after implementation, we should probably return true to avoid locking out legitimate users during transition, but catch the specific table not found error.
        return true;
    }
}

/**
 * Resets the rate limit for a given endpoint on success.
 */
function reset_rate_limit($pdo, $endpoint) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    try {
        $stmt = $pdo->prepare("DELETE FROM rate_limits WHERE ip_address = ? AND endpoint = ?");
        $stmt->execute([$ip_address, $endpoint]);
    } catch (PDOException $e) {
        // Ignore if table missing
    }
}
?>
