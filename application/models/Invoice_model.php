<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * JetPacks Market — customer invoice model.
 *
 * An invoice is minted the first time a customer downloads it, for a PAID
 * card payment that belongs to one of their quotes. This keeps the PDF
 * reproducible (regenerate any time) while guaranteeing every paid order has
 * exactly one invoice.
 */
class Invoice_model extends MY_Model
{
    protected $table = 'invoices';
    protected $primary_key = 'id';
    protected $fillable = [
        'paymentId', 'quoteId', 'userId', 'invoiceNumber', 'amount',
        'currency', 'status', 'pdfUrl', 'issuedAt', 'paidAt',
    ];
    protected $order_by = ['paidAt' => 'DESC'];

    /**
     * All paid invoices for a customer, generating any missing invoice rows
     * on the fly (idempotent).
     *
     * @return array
     */
    public function list_for_user($userId)
    {
        $this->load->model('Payment_model');
        $payments = $this->db
            ->select('p.*, q.quoteNumber, q.userId AS quoteUserId')
            ->from('payments p')
            ->join('quotes q', 'q.id = p.quoteId')
            ->where('p.status', PAYMENT_PAID)
            ->where('q.userId', $userId)
            ->order_by('p.paidAt', 'DESC')
            ->get()
            ->result_array();

        $invoices = [];
        foreach ($payments as $p) {
            $invoices[] = $this->find_or_create_for_payment($p);
        }
        return $invoices;
    }

    /**
     * Return the invoice for a payment, creating + rendering its PDF if needed.
     *
     * @param array $payment A payments row (must include quoteId, amount, currency, paidAt)
     * @return array|null
     */
    public function find_or_create_for_payment(array $payment)
    {
        $existing = $this->find_one(['paymentId' => $payment['id']]);
        if ($existing) {
            return $existing;
        }

        $quote = $this->db->get_where('quotes', ['id' => $payment['quoteId']])->row_array();
        $userId = $quote['userId'] ?? null;

        $now = date('Y-m-d H:i:s');
        $row = [
            'id'            => self::uuid(),
            'paymentId'     => $payment['id'],
            'quoteId'       => $payment['quoteId'],
            'userId'        => $userId,
            'invoiceNumber' => $this->next_number(),
            'quoteNumber'   => $quote['quoteNumber'] ?? null,
            'amount'        => (float) $payment['amount'],
            'currency'      => $payment['currency'] ?: 'USD',
            'status'        => 'PAID',
            'issuedAt'      => $payment['paidAt'] ?: $now,
            'paidAt'        => $payment['paidAt'] ?: $now,
            'createdAt'     => $now,
            'updatedAt'     => $now,
        ];

        $this->db->insert($this->table, $row);
        $row['pdfUrl'] = $this->build_pdf($row);
        if ($row['pdfUrl']) {
            $this->db->where('id', $row['id'])->update($this->table, ['pdfUrl' => $row['pdfUrl']]);
        }
        return $row;
    }

    /**
     * Generate (or regenerate) the invoice PDF and persist it, returning the URL.
     *
     * @param array $invoice An invoices row
     * @return string|null
     */
    public function build_pdf(array $invoice)
    {
        $quote = $this->db->get_where('quotes', ['id' => $invoice['quoteId']])->row_array();
        if (!$quote) return null;

        $items = $this->db->get_where('quote_items', ['quoteId' => $quote['id']])->result_array();
        $customer = $invoice['userId']
            ? $this->db->get_where('users', ['id' => $invoice['userId']])->row_array()
            : null;

        $site = vp_site();
        $contact = [
            'email'   => $site['email'],
            'phone'   => $site['phone'],
            'address' => $site['address'],
        ];

        $this->load->library('pdf');

        $columns = [
            ['label' => 'Item',        'width' => 6, 'align' => 'L'],
            ['label' => 'Qty',         'width' => 1, 'align' => 'C'],
            ['label' => 'Unit price',  'width' => 2, 'align' => 'R'],
            ['label' => 'Amount',      'width' => 3, 'align' => 'R'],
        ];
        $rows = [];
        foreach ($items as $it) {
            $unit = $it['unitPrice'] !== null ? (float) $it['unitPrice'] : null;
            $rows[] = [
                $it['productName'] . ($it['specifications'] ? ' — ' . $it['specifications'] : ''),
                (string) (int) $it['quantity'],
                $unit !== null ? vp_money($unit, $invoice['currency']) : '—',
                $unit !== null ? vp_money($unit * (int) $it['quantity'], $invoice['currency']) : '—',
            ];
        }
        if (empty($rows)) {
            $rows[] = [$quote['companyName'] ?: 'Order ' . $quote['quoteNumber'], '1', '', vp_money($invoice['amount'], $invoice['currency'])];
        }

        $billTo = 'Bill to:  '
            . ($customer ? trim($customer['firstName'] . ' ' . $customer['lastName']) : $quote['contactPerson'])
            . '  /  ' . ($customer['company'] ?? $quote['companyName'])
            . '  /  ' . ($customer['email'] ?? $quote['email'])
            . ($quote['phone'] ? '  /  ' . $quote['phone'] : '')
            . ($quote['country'] ? '  /  ' . $quote['country'] : '')
            . ($quote['address'] ? "\n         " . str_replace("\n", "\n         ", $quote['address']) : '');

        $doc = [
            'title'      => 'INVOICE',
            'subtitle'   => $site['name'],
            'meta_left'  => [$site['name'], $contact['address'] ?: 'Aircraft parts marketplace'],
            'meta_right' => [
                'INVOICE  ' . $invoice['invoiceNumber'],
                'Quote: ' . $quote['quoteNumber'],
                'Issued: ' . date('Y-m-d', strtotime($invoice['issuedAt'])),
                'Paid: ' . date('Y-m-d', strtotime($invoice['paidAt'])),
            ],
            'columns'    => $columns,
            'rows'       => $rows,
            'notes'      => $billTo . "\n\nPayment method: Card (Stripe)\nThank you for your business.",
            'footer'     => $site['name'] . '  ·  ' . $contact['address'] . '  ·  ' . $contact['phone'] . '  ·  ' . $contact['email'],
        ];

        $binary = $this->pdf->build($doc);

        $dir = VP_UPLOAD_PATH . 'invoices/';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $path = $dir . $invoice['invoiceNumber'] . '.pdf';
        @file_put_contents($path, $binary);

        return VP_UPLOAD_URL . 'invoices/' . $invoice['invoiceNumber'] . '.pdf';
    }

    /** Next sequential invoice number, e.g. INV-2026-000001. */
    public function next_number()
    {
        $year = date('Y');
        $count = (int) $this->db
            ->like('invoiceNumber', 'INV-' . $year . '-', 'after')
            ->count_all_results($this->table);
        return 'INV-' . $year . '-' . str_pad((string) ($count + 1), 6, '0', STR_PAD_LEFT);
    }
}
