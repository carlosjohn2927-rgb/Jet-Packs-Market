<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Vortex Precision — AI chat assistant.
 *
 * Answers visitor questions about the business. Two modes:
 *
 *  1. "local" (default) — a self-contained knowledge assistant that answers
 *     from the site's own content (FAQs, products, industries, contact info).
 *     Requires no external service or API key, so it works out of the box on
 *     shared hosting.
 *
 *  2. Any other value + an API key — delegates to an OpenAI-compatible
 *     chat-completions endpoint (configurable via settings group "CHAT").
 *
 * The response never leaks system prompts or sensitive configuration.
 */
class Vp_assistant
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /**
     * Produce a reply to a visitor message.
     *
     * @param  string $message
     * @return string
     */
    public function reply($message)
    {
        $message = trim((string) $message);
        if ($message === '') return 'I did not catch that — could you rephrase your question?';

        $smalltalk = $this->smalltalk_reply($message);
        if ($smalltalk !== null) return $smalltalk;

        $config = vp_chat_config();

        // External LLM first when configured; fall back to local on any failure.
        if ($config['provider'] !== 'local' && $config['api_key'] !== '') {
            $remote = $this->reply_remote($message, $config);
            if ($remote !== null) return $remote;
        }

        return $this->reply_local($message);
    }

    /* ------------------------------------------------------------------ */
    /* Local knowledge assistant                                          */
    /* ------------------------------------------------------------------ */

    protected function reply_local($message)
    {
        $msg = $this->normalize($message);

        // Greetings / small talk are handled before optional remote AI in reply().

        // Exact-ish FAQ match is the strongest, most accurate source.
        $faq = $this->match_faq($msg);
        if ($faq !== null) return $faq;

        // Specific product lookup (e.g. "do you sell ball valves?")
        $product = $this->match_product($msg);
        if ($product !== null) return $product;

        // Category lookup (e.g. "pumps", "what filtration do you have?")
        $category = $this->match_category($msg);
        if ($category !== null) return $category;

        // Price / quote / buying intent
        if ($this->has_any($msg, ['price', 'pricing', 'cost', 'how much', 'quote', 'quotation', 'rfq', 'buy', 'purchase', 'order', 'payment', 'discount', 'offer'])) {
            return $this->quote_answer();
        }

        // Delivery / lead time / availability
        if ($this->has_any($msg, ['delivery', 'deliver', 'shipping', 'ship', 'lead time', 'lead-time', 'stock', 'available', 'availability', 'how long', 'when can', 'dispatch'])) {
            return "Delivery times vary by product and configuration. Standard items are typically dispatched within 2–4 weeks, while engineered-to-order equipment can take 8–12 weeks. Share your target delivery date in a quote request and we'll confirm a schedule. You can start here: " . $this->link('rfq', 'Request a Quote') . '.';
        }

        // Industries / applications
        if ($this->has_any($msg, ['industry', 'industries', 'sector', 'application', 'oil', 'gas', 'chemical', 'pharma', 'power generation', 'water', 'food', 'beverage', 'what do you serve', 'markets'])) {
            return $this->industries_answer();
        }

        // Contact / location
        if ($this->has_any($msg, ['contact', 'phone', 'call', 'email', 'e-mail', 'address', 'location', 'where are you', 'office', 'reach you', 'talk to', 'speak to', 'human', 'representative', 'support'])) {
            return $this->contact_answer();
        }

        // Careers / jobs
        if ($this->has_any($msg, ['career', 'careers', 'job', 'jobs', 'hiring', 'vacancy', 'vacancies', 'work for', 'apply', 'employment', 'internship'])) {
            return "We're always looking for talented engineers, sales and operations professionals. Browse open roles and apply online: " . $this->link('careers', 'Careers') . '.';
        }

        // Generic product/catalogue intent (before the company catch-all, so
        // "tell me about your pumps" answers with the catalogue, not the bio)
        if ($this->has_any($msg, ['product', 'products', 'catalogue', 'catalog', 'range', 'manufacture', 'make', 'sell', 'offer', 'solutions', 'equipment', 'valve', 'pump', 'exchanger', 'vessel', 'filter', 'instrument'])) {
            return $this->catalog_answer();
        }

        // About the company
        if ($this->has_any($msg, ['about us', 'about you', 'about your', 'who are you', 'your company', 'the company', 'history', 'founded', 'experience', 'headquarters', 'certified', 'certification', 'iso', 'quality'])) {
            return $this->about_answer();
        }

        // Fallback: helpful + escalation path
        return "I'm not sure I have a precise answer to that, but I can definitely help with our products, industries, pricing, quotes, delivery and contact details. You can also email our team at " . $this->email() . ' or call ' . $this->phone() . " — a real engineer will get back to you promptly.";
    }


    /** Quick conversational replies that should work in local and remote modes. */
    protected function smalltalk_reply($message)
    {
        $msg = $this->normalize($message);
        if (preg_match('/^(hi|hello|hey|hiya|howdy|good\s*(morning|afternoon|evening)|greetings)[\s!.?]*$/', $msg)) {
            return $this->greeting();
        }
        if (preg_match('/(thank|thanks|cheers|appreciate)/', $msg)) {
            return "You're very welcome! If you need anything else — a product detail, a quote, or delivery information — just ask.";
        }
        if (preg_match('/^(ok|okay|alright|all right|fine|great|good|nice|cool|sure|yes|yeah|yep|that s good|thats good|that is good|that is fine|sounds good)[\s!.?]*$/', $msg)) {
            return "Great — glad that helps. If you need anything else, you can ask about products, pricing, delivery, quotes, or contact details.";
        }
        if (preg_match('/(bye|goodbye|see you|good night)/', $msg)) {
            return 'Thanks for stopping by! Feel free to message again anytime, or reach our sales team by email. Have a great day!';
        }
        return null;
    }

    /* ---------------------- local answer builders ---------------------- */

    protected function greeting()
    {
        return "Hi there! 👋 I'm " . vp_chat_config()['bot_name'] . ", the " . vp_chat_config()['title'] . ". Ask me about our products, the industries we serve, pricing, delivery times, or how to request a quote.";
    }

    protected function quote_answer()
    {
        return "Great question! Pricing depends on the exact specification, quantity and certifications you need. The fastest way to get a firm price is to submit a Request for Quote (RFQ) — our engineering team usually responds within 2 business days. Start here: " . $this->link('rfq', 'Request a Quote') . ". If you'd like, tell me the product and quantity and I'll point you in the right direction.";
    }

    protected function contact_answer()
    {
        $phone = $this->phone();
        $email = $this->email();
        $address = $this->address();
        $out = "Here's how to reach us:\n";
        if ($phone !== '') $out .= "• Phone: " . $phone . "\n";
        if ($email !== '') $out .= "• Email: " . $email . "\n";
        if ($address !== '') $out .= "• Address: " . $address . "\n";
        $out .= "You can also use our contact form: " . $this->link('contact', 'Contact us') . '.';
        return rtrim($out);
    }

    protected function industries_answer()
    {
        $names = $this->industry_names();
        if (!empty($names)) {
            return "We serve " . implode(', ', $names) . ", supplying valves, pumps, heat exchangers, pressure vessels and filtration systems for demanding applications. Explore them here: " . $this->link('industries', 'Industries') . '.';
        }
        return "We design and manufacture equipment for oil & gas, chemical processing, power generation, water & wastewater, pharmaceutical and food & beverage. Explore: " . $this->link('industries', 'Industries') . '.';
    }

    protected function about_answer()
    {
        return "We're " . (function_exists('vp_site') ? vp_site('name') : ($this->CI->config->item('site_name') ?: 'an industrial manufacturer')) . " — a manufacturer of precision-engineered industrial valves, pumps, heat exchangers, pressure vessels and filtration systems. We've been a trusted partner to operators worldwide for over three decades. Learn more: " . $this->link('about', 'About us') . '.';
    }

    protected function catalog_answer()
    {
        $cats = $this->category_names();
        if (!empty($cats)) {
            return "Our catalogue includes " . implode(', ', $cats) . '. Browse the full range: ' . $this->link('products', 'Products') . ". Tell me which one you're interested in and I can give you more detail.";
        }
        return "Our product range covers valves, pumps, heat exchangers, pressure vessels and filtration systems. Browse the full catalogue: " . $this->link('products', 'Products') . '.';
    }

    /* ---------------------- knowledge retrieval ------------------------ */

    protected function match_faq($msg)
    {
        if (!$this->CI->db->table_exists('faqs')) return null;

        $rows = $this->CI->db->where('isActive', 1)->get('faqs')->result_array();
        if (empty($rows)) return null;

        $tokens = $this->tokenize($msg);
        if (empty($tokens)) return null;

        $best = null;
        $bestScore = 0;
        foreach ($rows as $row) {
            $hay = $this->normalize($row['question'] . ' ' . strip_tags($row['answer']));
            $hayTokens = $this->tokenize($hay);
            $overlap = count(array_intersect($tokens, $hayTokens));
            $coverage = count($hayTokens) ? $overlap / count($hayTokens) : 0;
            $score = $overlap * 2 + $coverage * 10;

            // Bonus for matching question wording closely
            if (stripos($this->normalize($row['question']), $msg) !== false) $score += 20;

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = strip_tags($row['answer']);
            }
        }

        // Threshold: require a couple of meaningful token hits.
        return $bestScore >= 8 ? trim($best) : null;
    }

    protected function match_product($msg)
    {
        if (!$this->CI->db->table_exists('products')) return null;

        $this->CI->db->where('isActive', 1);
        $this->CI->db->limit(50);
        $rows = $this->CI->db->get('products')->result_array();
        if (empty($rows)) return null;

        $tokens = $this->tokenize($msg);
        $matches = [];
        foreach ($rows as $p) {
            $nameTokens = $this->tokenize($p['name'] . ' ' . $p['sku']);
            $hay   = array_merge($nameTokens, $this->tokenize((string) $p['shortDescription']));
            $score = count(array_intersect($tokens, $hay));

            // A single distinctive word from the product name ("actuator",
            // "AAP-500") is a match too — visitors rarely type full names.
            if ($score < 2) {
                foreach (array_intersect($tokens, $nameTokens) as $hit) {
                    if (mb_strlen($hit) >= 4) { $score = 2; break; }
                }
            }
            if ($score >= 2) $matches[] = ['score' => $score, 'row' => $p];
        }
        if (empty($matches)) return null;

        usort($matches, function ($a, $b) { return $b['score'] <=> $a['score']; });
        $top = array_slice($matches, 0, 3);

        $lines = ["Yes — we have " . count($top) . " matching product" . (count($top) > 1 ? 's' : '') . " that may fit:"];
        foreach ($top as $m) {
            $p = $m['row'];
            $lines[] = "• " . $p['name'] . ($p['shortDescription'] ? ' — ' . $p['shortDescription'] : '') . ' ' . $this->link('products/' . $p['slug'], 'View');
        }
        $lines[] = "For a firm price and delivery schedule, submit an RFQ: " . $this->link('rfq', 'Request a Quote') . '.';
        return implode("\n", $lines);
    }

    /**
     * Answer "what pumps do you have?" style questions by listing the products
     * inside the matching catalogue category.
     */
    protected function match_category($msg)
    {
        if (!$this->CI->db->table_exists('categories') || !$this->CI->db->table_exists('products')) return null;

        $tokens = $this->tokenize($msg);
        if (empty($tokens)) return null;

        $cats = $this->CI->db->where('isActive', 1)->order_by('sortOrder', 'ASC')->get('categories')->result_array();
        $best = null;
        $bestScore = 0;
        foreach ($cats as $c) {
            $catTokens = $this->tokenize($c['name']);
            // Singular/plural tolerance: valves ↔ valve
            $expanded = [];
            foreach ($catTokens as $t) {
                $expanded[] = $t;
                $expanded[] = rtrim($t, 's');
                $expanded[] = $t . 's';
            }
            $score = count(array_intersect($tokens, array_unique($expanded)));
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $c;
            }
        }
        if (!$best || $bestScore < 1) return null;

        $products = $this->CI->db->where(['categoryId' => $best['id'], 'isActive' => 1])
                                 ->order_by('featured', 'DESC')->order_by('views', 'DESC')
                                 ->limit(4)->get('products')->result_array();

        $lines = ['Yes — ' . strtolower($best['name']) . ' are part of our range.'];
        foreach ($products as $p) {
            $lines[] = '• ' . $p['name'] . ($p['shortDescription'] ? ' — ' . $p['shortDescription'] : '')
                . ' ' . $this->link('products/' . $p['slug'], 'View');
        }
        $lines[] = 'See the whole category: ' . $this->link('products?category=' . rawurlencode($best['slug']), $best['name'])
            . '. For pricing and delivery, submit an RFQ: ' . $this->link('rfq', 'Request a Quote') . '.';

        return implode("\n", $lines);
    }

    protected function industry_names()
    {
        if (!$this->CI->db->table_exists('industries')) return [];
        $this->CI->db->where('isActive', 1)->order_by('sortOrder', 'ASC')->limit(8);
        $rows = $this->CI->db->get('industries')->result_array();
        return array_map(function ($r) { return $r['name']; }, $rows);
    }

    protected function category_names()
    {
        if (!$this->CI->db->table_exists('categories')) return [];
        $this->CI->db->where('isActive', 1)->order_by('sortOrder', 'ASC')->limit(8);
        $rows = $this->CI->db->get('categories')->result_array();
        return array_map(function ($r) { return strtolower($r['name']); }, $rows);
    }

    /* ---------------------- external LLM ------------------------------- */

    protected function reply_remote($message, $config)
    {
        if (!function_exists('curl_init')) return null;

        $system = $config['system_prompt'] !== ''
            ? $config['system_prompt']
            : 'You are a helpful assistant for ' . ($this->CI->config->item('site_name') ?: 'an industrial manufacturer')
              . '. Answer concisely and professionally. Keep answers short (under 120 words) and use plain text. '
              . 'You may direct users to request a quote for pricing.';

        $payload = [
            'model'       => $config['model'] ?: 'gpt-4o-mini',
            'messages'    => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $message],
            ],
            'temperature' => 0.4,
        ];

        $ch = curl_init($config['api_url']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $config['api_key'],
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 8,
        ]);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            log_message('error', 'Vp_assistant: remote LLM request failed - ' . $err);
            return null;
        }

        $data = json_decode($response, true);
        $content = $data['choices'][0]['message']['content'] ?? null;
        if (!is_string($content) || trim($content) === '') return null;

        return trim($content);
    }

    /* ---------------------- helpers ------------------------------------ */

    protected function normalize($text)
    {
        $text = strtolower((string) $text);
        $text = preg_replace('/[^a-z0-9\s\/&%.-]/i', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    protected function tokenize($text)
    {
        $text = $this->normalize($text);
        $tokens = preg_split('/\s+/', $text);
        $stop = ['the', 'a', 'an', 'and', 'or', 'of', 'to', 'for', 'with', 'in', 'on', 'at', 'is', 'are', 'do', 'does', 'you', 'your', 'i', 'me', 'my', 'we', 'our', 'us', 'can', 'could', 'would', 'what', 'how', 'when', 'where', 'who', 'which', 'please', 'tell', 'info', 'information', 'need', 'want', 'have', 'has'];
        $tokens = array_diff($tokens, $stop);
        $tokens = array_filter($tokens, function ($t) { return mb_strlen($t) > 1; });
        return array_values(array_unique($tokens));
    }

    protected function has_any($msg, array $needles)
    {
        foreach ($needles as $n) {
            if (strpos($msg, $n) !== false) return true;
        }
        return false;
    }

    protected function link($path, $label)
    {
        return rtrim($this->CI->config->item('base_url'), '/') . '/' . ltrim($path, '/') . ' (' . $label . ')';
    }

    protected function email()
    {
        // Dashboard-managed values win over the .env/config defaults.
        return function_exists('vp_site') ? (string) vp_site('email', (string) $this->CI->config->item('contact_email'))
            : (string) $this->CI->config->item('contact_email');
    }

    protected function phone()
    {
        return function_exists('vp_site') ? (string) vp_site('phone', (string) $this->CI->config->item('phone'))
            : (string) $this->CI->config->item('phone');
    }

    protected function address()
    {
        return function_exists('vp_site') ? (string) vp_site('address', (string) $this->CI->config->item('address'))
            : (string) $this->CI->config->item('address');
    }
}
