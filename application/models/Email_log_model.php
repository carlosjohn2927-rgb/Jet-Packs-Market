<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Email_log_model extends MY_Model
{
    protected $table = 'email_logs';
    protected $fillable = ['to','subject','template','status','providerId','dedupeKey','errorMessage','sentAt','retryCount','relatedQuoteId'];
    protected $order_by = ['createdAt' => 'DESC'];
    protected $timestamps = false;
}
