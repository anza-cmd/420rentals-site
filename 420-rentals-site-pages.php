<?php
/**
 * Plugin Name: 420 Rentals Site Pages
 * Description: Creates and manages the customer-facing 420 Rentals pages (Home, Rent a Device, How It Works, Travellers, FAQ, Contact, Legal), the main menu, contact details and the WhatsApp button. Copy comes from the brand brief only - no invented prices, specifications or legal wording. Anything not yet connected is labelled as such.
 * Version: 1.0.0
 * Author: 420 Rentals
 */
if (!defined('ABSPATH')) exit;

class R420_Site_Pages {
    const OPT = 'r420_site_settings';
    const KEY = '_r420_page_key';

    public function __construct(){
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
        add_action('wp_head', [$this, 'head_css']);
        add_action('wp_footer', [$this, 'footer']);
        foreach (['home','rent','how','travellers','faq','contact','legal'] as $sc) {
            add_shortcode('r420_'.$sc, [$this, 'sc_'.$sc]);
        }
    }

    /* ---------------------------------------------------------------- settings & helpers */
    private function s($k, $default = ''){
        $o = get_option(self::OPT, []);
        return (is_array($o) && isset($o[$k]) && $o[$k] !== '') ? $o[$k] : $default;
    }
    private function find_managed($key){
        $ids = get_posts(['post_type'=>'page','post_status'=>['publish','draft','pending','private','future'],'meta_key'=>self::KEY,'meta_value'=>$key,'numberposts'=>1,'fields'=>'ids']);
        return $ids ? (int)$ids[0] : 0;
    }
    private function page_url($key, $fallback_slug){
        $id = $this->find_managed($key);
        return ($id && get_post_status($id) === 'publish') ? get_permalink($id) : home_url('/'.$fallback_slug.'/');
    }
    private function shop_url(){
        return function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
    }
    private function product_url($sku){
        if (function_exists('wc_get_product_id_by_sku')) {
            $id = wc_get_product_id_by_sku($sku);
            if ($id && get_post_status($id) === 'publish') return get_permalink($id);
        }
        return $this->shop_url();
    }
    private function wa_url(){
        $n = preg_replace('/\D+/', '', (string)$this->s('whatsapp'));
        return $n ? 'https://wa.me/'.$n : '';
    }
    private function disclaimer(){
        return '420 Rentals supplies vaporiser devices only. Cannabis and cannabis products are not supplied, sold, transported or included with any rental. Customers are responsible for complying with all applicable South African laws regarding any substance they choose to use with the device.';
    }
    private function btn($href, $label, $cls = 'r420-btn-primary'){
        return '<a class="r420-btn '.esc_attr($cls).'" href="'.esc_url($href).'">'.esc_html($label).'</a>';
    }
    private function chat_btn(){
        $wa = $this->wa_url();
        return $wa ? $this->btn($wa, 'Chat with 420 Rentals', 'r420-btn-wa') : '';
    }

    /* ---------------------------------------------------------------- front end */
    public function enqueue(){
        wp_enqueue_style('r420-site', plugin_dir_url(__FILE__).'assets/r420-site.css', [], '1.0.0');
    }
    public function head_css(){
        if (!is_page()) return;
        $id = get_queried_object_id();
        if ($id && get_post_meta($id, '_r420_hide_title', true)) {
            $c = '.page-id-'.(int)$id;
            echo '<style id="r420-hide-title">'.$c.' .entry-title,'.$c.' .page-title,'.$c.' .wp-block-post-title,'.$c.' .pagelayer-title{display:none!important}</style>';
        }
    }
    public function footer(){
        if ($this->s('show_footer_notice', '1') === '1') {
            echo '<div class="r420-foot">'.esc_html($this->disclaimer()).'</div>';
        }
        $wa = $this->wa_url();
        if ($wa) echo '<a class="r420-wa" href="'.esc_url($wa).'" rel="noopener">Chat with 420 Rentals</a>';
    }

    /* ---------------------------------------------------------------- shortcodes */
    public function sc_home(){
        ob_start(); ?>
<div class="r420">
  <section class="r420-hero">
    <p class="r420-kicker">420 Rentals &middot; South Africa</p>
    <h1>Don&rsquo;t pack it. Rent it.</h1>
    <p class="r420-lead">Premium vaporisers delivered to your South African accommodation.</p>
    <p class="r420-sub">Your trip is already packed. Your vaporiser doesn&rsquo;t need to be.</p>
    <div class="r420-btns">
      <?php echo $this->btn($this->page_url('rent','rent'), 'RENT A DEVICE'); ?>
      <?php echo $this->btn($this->shop_url(), 'SHOP DEVICES', 'r420-btn-ghost'); ?>
    </div>
  </section>

  <section class="r420-section">
    <h2>Your device. Your trip.</h2>
    <p>Travelling to South Africa? Get the device you want without travelling internationally with it.</p>
    <ul class="r420-list">
      <li>Choose your device.</li><li>Select your dates.</li><li>Tell us where you&rsquo;re staying.</li><li>We&rsquo;ll handle the delivery.</li>
    </ul>
    <p class="r420-muted">Online rental booking is still being connected. Until it is live, chat to us to arrange a rental.</p>
  </section>

  <section class="r420-section">
    <h2>Choose your device</h2>
    <div class="r420-grid">
      <div class="r420-card">
        <h3>DynaVap The M 7</h3>
        <p class="r420-tag">Simple. Discreet. Battery-free.</p>
        <?php echo $this->btn($this->product_url('420-VCM853731500F'), 'VIEW IN SHOP', 'r420-btn-ghost'); ?>
      </div>
      <div class="r420-card">
        <h3>DynaVap The M 7 XL</h3>
        <p class="r420-tag">Simple. Discreet. Battery-free. Extra length for handling.</p>
        <?php echo $this->btn($this->product_url('420-VCM853731500FXL'), 'VIEW IN SHOP', 'r420-btn-ghost'); ?>
      </div>
      <div class="r420-card r420-card-muted">
        <h3>More devices</h3>
        <p class="r420-tag">Coming soon.</p>
      </div>
    </div>
  </section>

  <section class="r420-section">
    <h2>How it works</h2>
    <?php echo $this->steps_html(); ?>
    <div class="r420-btns"><?php echo $this->btn($this->page_url('rent','rent'), 'START YOUR RENTAL'); ?></div>
  </section>

  <section class="r420-section r420-band">
    <h2>Travelling to South Africa?</h2>
    <p>Leave the expensive equipment at home. Choose your device online and have it delivered to your hotel, Airbnb, guesthouse or lodge.</p>
    <p class="r420-muted">Cape Town &middot; Johannesburg &middot; Durban &middot; Garden Route &middot; Kruger region &middot; other South African destinations. Delivery is subject to courier coverage.</p>
    <div class="r420-btns"><?php echo $this->btn($this->page_url('travellers','travelling-to-south-africa'), 'TRAVELLER INFO', 'r420-btn-ghost'); ?></div>
  </section>

  <section class="r420-section">
    <h2>Rent or buy</h2>
    <?php echo $this->rent_vs_buy_html(); ?>
    <p>Want to keep it? Buy it.</p>
    <div class="r420-btns"><?php echo $this->btn($this->shop_url(), 'SHOP DEVICES', 'r420-btn-ghost'); ?></div>
  </section>

  <p class="r420-muted"><small><?php echo esc_html($this->disclaimer()); ?></small></p>
</div>
<?php
        return ob_get_clean();
    }

    private function steps_html(){
        $steps = [
            ['01','BOOK','Choose your device and rental dates.'],
            ['02','WE DELIVER','We courier the device to your accommodation.'],
            ['03','ENJOY YOUR TRIP','Keep the device for the duration of your rental.'],
            ['04','RETURN','Send it back using the supplied return process.'],
        ];
        $h = '<ol class="r420-steps">';
        foreach ($steps as $s) {
            $h .= '<li><span class="r420-n">'.esc_html($s[0]).'</span><strong>'.esc_html($s[1]).'</strong><span>'.esc_html($s[2]).'</span></li>';
        }
        return $h.'</ol>';
    }
    private function rent_vs_buy_html(){
        return '<table class="r420-table"><thead><tr><th></th><th>RENT</th><th>BUY</th></tr></thead><tbody>'
            .'<tr><th>Ideal for</th><td>Holidays / short trips</td><td>Long-term use</td></tr>'
            .'<tr><th>Upfront cost</th><td>Lower</td><td>Higher</td></tr>'
            .'<tr><th>Keep device</th><td>No</td><td>Yes</td></tr>'
            .'<tr><th>Delivery</th><td>Yes</td><td>Yes</td></tr>'
            .'<tr><th>Return required</th><td>Yes</td><td>No</td></tr>'
            .'</tbody></table>';
    }

    public function sc_how(){
        ob_start(); ?>
<div class="r420">
  <section class="r420-section">
    <h2>Book. Delivery. Use. Return.</h2>
    <?php echo $this->steps_html(); ?>
    <div class="r420-btns"><?php echo $this->btn($this->page_url('rent','rent'), 'START YOUR RENTAL'); ?><?php echo $this->chat_btn(); ?></div>
  </section>
</div>
<?php
        return ob_get_clean();
    }

    public function sc_rent(){
        $email = $this->s('email');
        ob_start(); ?>
<div class="r420">
  <section class="r420-section">
    <h2>Rent a device</h2>
    <p>Choose a device, pick your dates, tell us where you&rsquo;re staying and we deliver it to your accommodation.</p>

    <div class="r420-notice">
      <span class="r420-badge">NOT YET CONNECTED</span>
      <strong>Online rental booking is being connected.</strong>
      <p>Date selection, availability checking, payment, security deposit handling and courier tracking are not live yet, so no booking can be made through this page. To arrange a rental now, contact us:</p>
      <div class="r420-btns">
        <?php echo $this->chat_btn(); ?>
        <?php if ($email) echo $this->btn('mailto:'.$email, strtoupper($email), 'r420-btn-ghost'); ?>
        <?php if (!$this->wa_url() && !$email) echo '<span class="r420-muted">Contact details to be confirmed.</span>'; ?>
      </div>
    </div>

    <h3>How your rental will work</h3>
    <ol class="r420-list">
      <li><strong>Choose a device</strong> &ndash; see the range below.</li>
      <li><strong>Select dates</strong> &ndash; delivery/collection date, rental period and return date.</li>
      <li><strong>Enter your accommodation</strong> &ndash; hotel, Airbnb, lodge, guesthouse or other.</li>
      <li><strong>Pay</strong> &ndash; rental fee, delivery fee where applicable, and a refundable security deposit where applicable.</li>
      <li><strong>Delivery</strong> &ndash; the device is couriered to your accommodation.</li>
      <li><strong>Use it during your trip</strong> for the agreed rental period.</li>
      <li><strong>Return</strong> it using the agreed courier/return process.</li>
    </ol>

    <h3>What we&rsquo;ll need from you</h3>
    <p>Full name, phone, email, accommodation name and address, check-in and check-out dates, and any special delivery instructions.</p>

    <h3>Pricing, deposit and return</h3>
    <p>Rental price, delivery and the refundable security deposit will be shown clearly, with your total, before you pay. Prices: to be confirmed. Rental devices remain the property of 420 Rentals and must be returned according to the rental agreement.</p>

    <h3>Adults only</h3>
    <p>Rentals are for adults. Age verification will be required; the final requirements are to be confirmed.</p>

    <h3>Available devices</h3>
    <div class="r420-grid">
      <div class="r420-card"><h3>DynaVap The M 7</h3><p class="r420-tag">Simple. Discreet. Battery-free.</p><?php echo $this->btn($this->product_url('420-VCM853731500F'), 'VIEW IN SHOP', 'r420-btn-ghost'); ?></div>
      <div class="r420-card"><h3>DynaVap The M 7 XL</h3><p class="r420-tag">Simple. Discreet. Battery-free. Extra length for handling.</p><?php echo $this->btn($this->product_url('420-VCM853731500FXL'), 'VIEW IN SHOP', 'r420-btn-ghost'); ?></div>
    </div>
  </section>
  <p class="r420-muted"><small><?php echo esc_html($this->disclaimer()); ?></small></p>
</div>
<?php
        return ob_get_clean();
    }

    public function sc_travellers(){
        ob_start(); ?>
<div class="r420">
  <section class="r420-section">
    <h2>Travelling to South Africa?</h2>
    <p>Leave the expensive equipment at home. Choose your device online and have it delivered to your hotel, Airbnb, guesthouse or lodge.</p>
    <div class="r420-grid">
      <div class="r420-card"><h3>Before your flight</h3><p>Book your device before you arrive.</p></div>
      <div class="r420-card"><h3>At your accommodation</h3><p>We deliver directly to your hotel, Airbnb, lodge or guesthouse.</p></div>
      <div class="r420-card"><h3>During your trip</h3><p>Keep it with you for the agreed rental period.</p></div>
      <div class="r420-card"><h3>Before you leave</h3><p>Return it using the supplied return process.</p></div>
    </div>
  </section>
  <section class="r420-section">
    <h2>Where we aim to deliver</h2>
    <p>Cape Town, Johannesburg, Durban, the Garden Route, the Kruger region and other South African destinations. Delivery is subject to courier coverage, which is still being confirmed, so please check with us before you book.</p>
    <div class="r420-btns"><?php echo $this->chat_btn(); ?><?php echo $this->btn($this->page_url('rent','rent'), 'RENT A DEVICE', 'r420-btn-ghost'); ?></div>
  </section>
  <section class="r420-section r420-band">
    <h2>Please note</h2>
    <p><?php echo esc_html($this->disclaimer()); ?></p>
    <p>We cannot give legal advice. Rules about substances can be complex and may change, so please check official South African government information and take independent legal advice if you are unsure.</p>
  </section>
</div>
<?php
        return ob_get_clean();
    }

    public function sc_faq(){
        $qa = [
            ['Can tourists rent a vaporiser?', 'Yes, subject to age verification, availability, delivery coverage and the rental terms.'],
            ['Where can you deliver?', 'We aim to deliver to hotels, Airbnb accommodation, guesthouses, lodges and other suitable South African addresses, subject to courier coverage.'],
            ['Can I buy a device instead?', 'Yes. Selected devices are available for purchase.'],
            ['What devices are available?', 'Currently the DynaVap The M 7 and The M 7 XL. More devices are coming soon.'],
            ['Do you supply cannabis?', 'No. 420 Rentals supplies vaporiser devices only.'],
            ['Do I need to return the device?', 'Yes. Rental devices remain the property of 420 Rentals and must be returned according to the rental agreement.'],
            ['Is there a deposit?', 'A refundable security deposit may apply. The amount will be displayed during checkout.'],
            ['Can I take the device home with me?', 'Rental devices must be returned. Customers purchasing a device are responsible for complying with applicable laws concerning possession and transport.'],
            ['What happens if I damage the device?', 'The rental terms will explain the customer\'s responsibility for damage, loss or theft.'],
        ];
        $h = '<div class="r420 r420-faq"><section class="r420-section"><h2>Frequently asked questions</h2>';
        foreach ($qa as $q) {
            $h .= '<details><summary>'.esc_html($q[0]).'</summary><p>'.esc_html($q[1]).'</p></details>';
        }
        return $h.'<div class="r420-btns">'.$this->chat_btn().'</div></section></div>';
    }

    public function sc_contact(){
        $rows = [
            'Business name' => $this->s('business_name', '420 Rentals'),
            'Email'         => $this->s('email'),
            'WhatsApp'      => $this->s('whatsapp'),
            'Telephone'     => $this->s('phone'),
            'Address'       => $this->s('address'),
            'Operating hours' => $this->s('hours'),
        ];
        $h = '<div class="r420"><section class="r420-section"><h2>Contact 420 Rentals</h2>';
        $h .= '<p>Questions about delivery, availability, last-minute bookings or returns? Chat with us.</p>';
        $h .= '<div class="r420-btns">'.$this->chat_btn().'</div>';
        $h .= '<table class="r420-table"><tbody>';
        foreach ($rows as $label => $val) {
            $shown = $val !== '' ? nl2br(esc_html($val)) : '<span class="r420-muted">To be confirmed</span>';
            $h .= '<tr><th>'.esc_html($label).'</th><td>'.$shown.'</td></tr>';
        }
        return $h.'</tbody></table></section></div>';
    }

    public function sc_legal(){
        $h = '<div class="r420"><section class="r420-section"><h2>Legal &amp; policies</h2>';
        $h .= '<p>These policies are being prepared and will be reviewed before launch.</p><ul class="r420-legal-list">';
        foreach ($this->policies() as $key => $def) {
            $id = $this->find_managed($key);
            $live = $id && get_post_status($id) === 'publish';
            $h .= '<li><span>'.esc_html($def['title']).'</span>'.($live ? '<a href="'.esc_url(get_permalink($id)).'">Read</a>' : '<span class="r420-muted">In preparation</span>').'</li>';
        }
        return $h.'</ul></section></div>';
    }

    /* ---------------------------------------------------------------- page definitions */
    private function core_pages(){
        return [
            'home'         => ['title'=>'Home', 'slug'=>'home', 'content'=>'[r420_home]', 'status'=>'publish', 'hide_title'=>1],
            'rent'         => ['title'=>'Rent a Device', 'slug'=>'rent', 'content'=>'[r420_rent]', 'status'=>'publish'],
            'how-it-works' => ['title'=>'How It Works', 'slug'=>'how-it-works', 'content'=>'[r420_how]', 'status'=>'publish'],
            'travellers'   => ['title'=>'Travelling to South Africa', 'slug'=>'travelling-to-south-africa', 'content'=>'[r420_travellers]', 'status'=>'publish'],
            'faq'          => ['title'=>'FAQ', 'slug'=>'faq', 'content'=>'[r420_faq]', 'status'=>'publish'],
            'contact'      => ['title'=>'Contact', 'slug'=>'contact', 'content'=>'[r420_contact]', 'status'=>'publish'],
            'legal'        => ['title'=>'Legal & Policies', 'slug'=>'legal', 'content'=>'[r420_legal]', 'status'=>'publish'],
        ];
    }
    private function policies(){
        $ph = 'This page is a placeholder. The final wording has not been written and must be reviewed before launch. Nothing on this page is legal advice or a binding term.';
        $out = [];
        foreach ([
            'terms'                 => ['Terms & Conditions', 'terms-and-conditions'],
            'rental-agreement'      => ['Rental Agreement', 'rental-agreement'],
            'privacy-policy'        => ['Privacy Policy', 'privacy-policy'],
            'refund-policy'         => ['Refund Policy', 'refund-policy'],
            'cancellation-policy'   => ['Cancellation Policy', 'cancellation-policy'],
            'deposit-damage-policy' => ['Deposit & Damage Policy', 'deposit-and-damage-policy'],
            'delivery-policy'       => ['Delivery Policy', 'delivery-policy'],
            'return-policy'         => ['Return Policy', 'return-policy'],
            'age-verification-policy' => ['Age Verification Policy', 'age-verification-policy'],
            'hygiene-policy'        => ['Device Cleaning & Hygiene Policy', 'device-cleaning-and-hygiene-policy'],
            'acceptable-use-policy' => ['Acceptable Use Policy', 'acceptable-use-policy'],
            'legal-disclaimer'      => ['Legal Disclaimer', 'legal-disclaimer'],
        ] as $key => $d) {
            $content = '<p>'.$ph.'</p>';
            if ($key === 'legal-disclaimer') $content .= '<p>Draft wording for review by a South African legal professional: '.$this->disclaimer().'</p>';
            $out[$key] = ['title'=>$d[0], 'slug'=>$d[1], 'content'=>$content, 'status'=>'draft'];
        }
        return $out;
    }

    /* ---------------------------------------------------------------- setup actions */
    private function create_pages(){
        $log = [];
        foreach (array_merge($this->core_pages(), $this->policies()) as $key => $def) {
            if ($this->find_managed($key)) { continue; }
            if ($key === 'privacy-policy') {
                $pp = (int)get_option('wp_page_for_privacy_policy');
                if ($pp && get_post($pp) && get_post_status($pp) !== 'trash') {
                    update_post_meta($pp, self::KEY, $key);
                    $log[] = 'Adopted the existing WordPress Privacy Policy page (content left as is).';
                    continue;
                }
            }
            $existing = get_page_by_path($def['slug'], OBJECT, 'page');
            if ($existing) {
                $log[] = 'BLOCKED: "'.$def['title'].'" needs /'.$def['slug'].'/ but an existing page ("'.$existing->post_title.'") already uses it. Move that page to Trash, then run setup again.';
                continue;
            }
            $id = wp_insert_post([
                'post_type'=>'page', 'post_title'=>wp_strip_all_tags($def['title']), 'post_name'=>$def['slug'],
                'post_content'=>$def['content'], 'post_status'=>$def['status'], 'comment_status'=>'closed',
            ], true);
            if (is_wp_error($id)) { $log[] = 'Could not create "'.$def['title'].'": '.$id->get_error_message(); continue; }
            update_post_meta($id, self::KEY, $key);
            if (!empty($def['hide_title'])) update_post_meta($id, '_r420_hide_title', 1);
            $log[] = 'Created "'.$def['title'].'" ('.$def['status'].').';
        }
        return $log ?: ['All pages already exist.'];
    }
    private function set_front_page(){
        $id = $this->find_managed('home');
        if (!$id || get_post_status($id) !== 'publish') return 'Front page not set: the Home page does not exist yet.';
        update_option('show_on_front', 'page');
        update_option('page_on_front', $id);
        return 'Home is now the site front page.';
    }
    private function build_menu(){
        $name = '420 Rentals Main';
        $menu = wp_get_nav_menu_object($name);
        $menu_id = $menu ? (int)$menu->term_id : wp_create_nav_menu($name);
        if (is_wp_error($menu_id)) return 'Menu not built: '.$menu_id->get_error_message();
        $have = [];
        foreach ((array)wp_get_nav_menu_items($menu_id) as $it) { $have[] = (int)$it->object_id; }
        $order = ['home','shop','rent','how-it-works','travellers','faq','contact'];
        $added = 0; $pos = 0;
        foreach ($order as $key) {
            $pos++;
            $pid = ($key === 'shop') ? (function_exists('wc_get_page_id') ? (int)wc_get_page_id('shop') : 0) : $this->find_managed($key);
            if ($pid <= 0 || get_post_status($pid) !== 'publish' || in_array($pid, $have, true)) continue;
            wp_update_nav_menu_item($menu_id, 0, [
                'menu-item-title'=>get_the_title($pid), 'menu-item-object'=>'page', 'menu-item-object-id'=>$pid,
                'menu-item-type'=>'post_type', 'menu-item-status'=>'publish', 'menu-item-position'=>$pos,
            ]);
            $added++;
        }
        $msg = 'Menu "'.$name.'" ready ('.$added.' item(s) added).';
        $locs = get_registered_nav_menus();
        if ($locs) {
            $pick = null;
            foreach (array_keys($locs) as $loc) { if (preg_match('/primary|main|header/i', $loc)) { $pick = $loc; break; } }
            if ($pick === null) { $keys = array_keys($locs); $pick = $keys[0]; }
            $assign = get_theme_mod('nav_menu_locations', []);
            $assign[$pick] = $menu_id;
            set_theme_mod('nav_menu_locations', $assign);
            $msg .= ' Assigned to theme location "'.$pick.'".';
        } else {
            $msg .= ' Your theme uses block navigation, so add this menu in Appearance > Editor > Navigation.';
        }
        return $msg;
    }

    /* ---------------------------------------------------------------- admin */
    public function admin_menu(){
        add_options_page('420 Rentals Site', '420 Rentals Site', 'manage_options', 'r420-site', [$this, 'admin_page']);
    }
    private function legacy_pages(){
        $managed = [];
        foreach (array_merge(array_keys($this->core_pages()), array_keys($this->policies())) as $k) { $managed[$this->find_managed($k)] = true; }
        $found = [];
        foreach (['sample-page','about','blog','services','contact','home'] as $slug) {
            $p = get_page_by_path($slug, OBJECT, 'page');
            if ($p && empty($managed[$p->ID])) $found[] = $p;
        }
        return $found;
    }
    public function admin_page(){
        if (!current_user_can('manage_options')) return;
        $msgs = [];
        if (!empty($_POST['r420_save']) && check_admin_referer('r420_save_settings')) {
            $o = [
                'business_name' => sanitize_text_field(wp_unslash($_POST['business_name'] ?? '')),
                'email'         => sanitize_email(wp_unslash($_POST['email'] ?? '')),
                'whatsapp'      => preg_replace('/\D+/', '', (string)wp_unslash($_POST['whatsapp'] ?? '')),
                'phone'         => sanitize_text_field(wp_unslash($_POST['phone'] ?? '')),
                'address'       => sanitize_textarea_field(wp_unslash($_POST['address'] ?? '')),
                'hours'         => sanitize_text_field(wp_unslash($_POST['hours'] ?? '')),
                'show_footer_notice' => !empty($_POST['show_footer_notice']) ? '1' : '0',
            ];
            update_option(self::OPT, $o);
            $msgs[] = 'Contact details saved.';
        }
        if (!empty($_POST['r420_setup']) && check_admin_referer('r420_run_setup')) {
            $msgs = array_merge($msgs, $this->create_pages(), [$this->set_front_page(), $this->build_menu()]);
        }
        echo '<div class="wrap"><h1>420 Rentals Site</h1>';
        foreach ($msgs as $m) echo '<div class="notice notice-info"><p>'.esc_html($m).'</p></div>';

        echo '<h2>1. Contact details</h2><p>Shown on the Contact page. Leave blank until confirmed &ndash; blank fields show &ldquo;To be confirmed&rdquo;. A WhatsApp number also switches on the Chat button across the site.</p>';
        echo '<form method="post">'; wp_nonce_field('r420_save_settings');
        echo '<table class="form-table"><tbody>';
        $f = function($label, $name, $type = 'text', $hint = '') {
            $v = $this->s($name, $name === 'business_name' ? '420 Rentals' : '');
            echo '<tr><th><label for="'.esc_attr($name).'">'.esc_html($label).'</label></th><td>';
            if ($type === 'textarea') echo '<textarea id="'.esc_attr($name).'" name="'.esc_attr($name).'" rows="3" class="large-text">'.esc_textarea($v).'</textarea>';
            else echo '<input id="'.esc_attr($name).'" name="'.esc_attr($name).'" type="'.esc_attr($type).'" value="'.esc_attr($v).'" class="regular-text">';
            if ($hint) echo '<p class="description">'.esc_html($hint).'</p>';
            echo '</td></tr>';
        };
        $f('Business name', 'business_name');
        $f('Email', 'email', 'email');
        $f('WhatsApp number', 'whatsapp', 'text', 'International format, digits only, e.g. 27XXXXXXXXX (no +, spaces or leading 0).');
        $f('Telephone', 'phone');
        $f('Business address', 'address', 'textarea');
        $f('Operating hours', 'hours');
        echo '<tr><th>Footer notice</th><td><label><input type="checkbox" name="show_footer_notice" value="1" '.checked($this->s('show_footer_notice','1'), '1', false).'> Show the &ldquo;devices only&rdquo; notice in the footer of every page</label><p class="description">Wording must be reviewed by a South African legal professional before launch.</p></td></tr>';
        echo '</tbody></table><p><button class="button button-primary" name="r420_save" value="1">Save contact details</button></p></form>';

        echo '<hr><h2>2. Set up the site</h2><p>Creates any missing pages, makes Home the front page and builds the main menu. Safe to run again &ndash; it never overwrites a page that already exists.</p>';
        echo '<form method="post">'; wp_nonce_field('r420_run_setup');
        echo '<p><button class="button button-primary" name="r420_setup" value="1">Create pages, set front page &amp; build menu</button></p></form>';

        echo '<h3>Pages managed by this plugin</h3><table class="widefat striped"><thead><tr><th>Page</th><th>Address</th><th>Status</th><th></th></tr></thead><tbody>';
        foreach (array_merge($this->core_pages(), $this->policies()) as $key => $def) {
            $id = $this->find_managed($key);
            echo '<tr><td>'.esc_html($def['title']).'</td><td><code>/'.esc_html($def['slug']).'/</code></td><td>'.($id ? esc_html(get_post_status($id)) : 'not created').'</td><td>';
            if ($id) echo '<a href="'.esc_url(get_edit_post_link($id)).'">Edit</a>';
            echo '</td></tr>';
        }
        echo '</tbody></table>';

        $legacy = $this->legacy_pages();
        echo '<h3>Existing pages that should be removed</h3>';
        if ($legacy) {
            echo '<p>These pages are not managed by this plugin and use addresses the new pages need or are generic starter content. &ldquo;Trash&rdquo; is reversible (Pages &gt; Trash).</p><table class="widefat striped"><tbody>';
            foreach ($legacy as $p) {
                echo '<tr><td>'.esc_html($p->post_title).'</td><td><code>/'.esc_html($p->post_name).'/</code></td><td>'.esc_html($p->post_status).'</td><td><a href="'.esc_url(get_edit_post_link($p->ID)).'">Edit</a> | <a href="'.esc_url(get_delete_post_link($p->ID)).'">Move to Trash</a></td></tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>None found.</p>';
        }
        echo '</div>';
    }
}
new R420_Site_Pages();
