<?php
// api/rate_limit.php
require_once 'config.php';

/**
 * Resolves the true client IP address taking into account Cloudflare,
 * reverse proxies, and load balancers.
 */
function get_client_ip() {
    $remote = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // If Cloudflare connecting IP is provided, validate format
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $cfIp = trim($_SERVER['HTTP_CF_CONNECTING_IP']);
        if (filter_var($cfIp, FILTER_VALIDATE_IP)) {
            return $cfIp;
        }
    }

    // Only trust X-Forwarded-For if request came from loopback or internal proxy
    $isLocalProxy = in_array($remote, ['127.0.0.1', '::1', 'localhost']) || 
                    filter_var($remote, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;

    if ($isLocalProxy && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $forwarded = trim($ips[0]);
        if (filter_var($forwarded, FILTER_VALIDATE_IP)) {
            return $forwarded;
        }
    }

    return filter_var($remote, FILTER_VALIDATE_IP) ? $remote : '0.0.0.0';
}

/**
 * Validates rate limits based on IP and Endpoint.
 * Returns true if allowed, false if blocked.
 */
function check_rate_limit($pdo, $endpoint, $max_attempts = 30, $lock_minutes = 15) {
    $ip_address = get_client_ip();
    
    try {
        // Auto-cleanup expired locks regularly
        if (random_int(1, 20) === 1) {
            $pdo->exec("DELETE FROM rate_limits WHERE lock_until IS NOT NULL AND lock_until < NOW()");
        }

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
        return true;
    }
}

/**
 * Resets the rate limit for a given endpoint on success.
 */
function reset_rate_limit($pdo, $endpoint, $ip_override = null) {
    $ip_address = $ip_override ?: get_client_ip();
    try {
        $stmt = $pdo->prepare("DELETE FROM rate_limits WHERE ip_address = ? AND endpoint = ?");
        $stmt->execute([$ip_address, $endpoint]);
    } catch (PDOException $e) {
        // Ignore if table missing
    }
}
