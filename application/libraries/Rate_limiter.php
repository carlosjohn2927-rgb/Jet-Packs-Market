<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Vortex Precision - File-based rate limiter.
 *
 * One JSON file per (key, window-bucket) lives under assets/logs/ratelimit/.
 * Suitable for shared hosting with low traffic.
 */
class Rate_limiter
{
    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $dir = FCPATH . 'assets/logs/ratelimit/';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
    }

    /**
     * @param string $key    Bucket key, e.g. 'rfq:1.2.3.4:email@x.com'
     * @param int    $limit  Allowed events per window
     * @param int    $window Window length in seconds
     * @return bool          TRUE if within limit, FALSE if over
     */
    public function too_many($key, $limit, $window)
    {
        $dir = FCPATH . 'assets/logs/ratelimit/';
        $file = $dir . preg_replace('/[^a-zA-Z0-9._-]/', '_', $key) . '.json';

        $now = time();
        $data = ['start' => $now, 'count' => 0];
        if (is_file($file)) {
            $raw = @file_get_contents($file);
            $d = json_decode($raw, true);
            if (is_array($d) && isset($d['start'], $d['count'])) {
                $data = $d;
            }
        }
        // Reset window if expired
        if ($now - $data['start'] >= $window) {
            $data = ['start' => $now, 'count' => 0];
        }
        $data['count']++;
        @file_put_contents($file, json_encode($data), LOCK_EX);
        return $data['count'] > $limit;
    }

    /**
     * Reset the bucket for a key (e.g. after a successful login).
     */
    public function clear($key)
    {
        $dir = FCPATH . 'assets/logs/ratelimit/';
        $file = $dir . preg_replace('/[^a-zA-Z0-9._-]/', '_', $key) . '.json';
        if (is_file($file)) @unlink($file);
        return true;
    }

    /**
     * @return int Seconds until the window resets.
     */
    public function ttl($key, $window)
    {
        $dir = FCPATH . 'assets/logs/ratelimit/';
        $file = $dir . preg_replace('/[^a-zA-Z0-9._-]/', '_', $key) . '.json';
        if (!is_file($file)) return 0;
        $d = json_decode(@file_get_contents($file), true);
        if (!$d || !isset($d['start'])) return 0;
        return max(0, $window - (time() - $d['start']));
    }
}
