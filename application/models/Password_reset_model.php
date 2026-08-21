<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Vortex Precision - Password reset tokens.
 *
 * Tokens are random 32-byte strings, hashed (HMAC) in the DB so a leaked DB
 * cannot be used to reset passwords. Expires after 1 hour, single use.
 */
class Password_reset_model extends MY_Model
{
    protected $table = 'password_resets';
    protected $fillable = ['userId', 'email', 'token', 'expiresAt', 'usedAt', 'ipAddress'];
    protected $order_by = ['createdAt' => 'DESC'];
    protected $timestamps = false;

    /** How long a reset link stays valid. */
    const TTL_SECONDS = 3600;

    /**
     * Create a fresh reset token for $email. Invalidates any prior unused tokens.
     * @return string|false  The plaintext token (to be emailed), or false on error.
     */
    public function create_for_email($email, $userId, $ip = null)
    {
        $email = strtolower(trim((string) $email));
        // Invalidate prior tokens for this email
        $this->db->update('password_resets', ['usedAt' => date('Y-m-d H:i:s')], [
            'email'  => $email,
            'usedAt' => null,
        ]);

        $token = vp_random_token(32);
        $hash  = vp_hmac_sign($token); // store the HMAC, not the token
        $row = [
            'id'        => MY_Model::uuid(),
            'userId'    => $userId,
            'email'     => $email,
            'token'     => $hash,
            'expiresAt' => date('Y-m-d H:i:s', time() + self::TTL_SECONDS),
            'ipAddress' => $ip,
            'createdAt' => date('Y-m-d H:i:s'),
        ];
        $this->db->insert('password_resets', $row);
        return $token;
    }

    /**
     * Find a valid (unexpired, unused) token row by its plaintext token.
     * @return array|null
     */
    public function find_valid($token)
    {
        if (!$token) return null;
        $hash = vp_hmac_sign($token);
        $row = $this->db->get_where('password_resets', ['token' => $hash])->row_array();
        if (!$row) return null;
        if ($row['usedAt']) return null;
        if (strtotime($row['expiresAt']) < time()) return null;
        return $row;
    }

    /**
     * Mark a token as used.
     */
    public function mark_used($id)
    {
        return $this->db->update('password_resets', ['usedAt' => date('Y-m-d H:i:s')], ['id' => $id]);
    }

    /**
     * Purge expired tokens. Call from cron in production.
     */
    public function purge_expired()
    {
        return $this->db->delete('password_resets', ['expiresAt <' => date('Y-m-d H:i:s')]);
    }
}
