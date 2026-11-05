<?php
/**
 * Plugin Name: 420 Rentals Catalog Importer
 * Description: Imports the 420 Rentals WooCommerce catalogue CSV and creates/updates products by SKU. Imports as Draft by default.
 * Version: 1.4.0
 * Author: 420 Rentals
 * Requires Plugins: woocommerce
 */
if (!defined('ABSPATH')) exit;

class Rentals_420_Catalog_Importer {
    public function __construct(){
        add_action('admin_menu', [$this,'menu']);
    }
    public function menu(){
        add_submenu_page('woocommerce','420 Rentals Catalog','420 Rentals Catalog','manage_woocommerce','420-rentals-catalog',[$this,'page']);
    }
    private function clean($v){ return is_string($v) ? trim($v) : $v; }
    public function page(){
        if (!current_user_can('manage_woocommerce')) return;
        $result = null; $img_result = null;
        if (!empty($_POST['rr_import']) && check_admin_referer('rr_import_catalog')) {
            $result = $this->import_csv($_FILES['catalog_csv'] ?? null);
        }
        if (!empty($_POST['rr_import_images']) && check_admin_referer('rr_import_images')) {
            $img_result = $this->import_product_images(!empty($_POST['rr_replace_images']));
        }
        echo '<div class="wrap"><h1>420 Rentals Catalog Importer</h1>';
        echo '<p>Upload the CSV supplied with the 420 Rentals catalogue. Products are imported as <strong>Draft</strong> unless the CSV Published field is explicitly set to 1.</p>';
        if ($result) echo '<div class="notice notice-info"><p>'.esc_html($result).'</p></div>';
        echo '<form method="post" enctype="multipart/form-data">';
        wp_nonce_field('rr_import_catalog');
        echo '<input type="file" name="catalog_csv" accept=".csv" required> ';
        echo '<button class="button button-primary" name="rr_import" value="1">Import / Update Catalogue</button></form>';
        echo '<h2>Safety defaults</h2><ul><li>CSV rows default to Draft.</li><li>Existing products are matched by SKU.</li><li>No orders, payments or delivery settings are changed.</li><li>Supplier cost is stored as private product metadata.</li><li>Brand is set on WooCommerce\'s native Brands taxonomy (if enabled); terms are created automatically.</li></ul>';

        echo '<hr><h2>Bundled Product Images (DynaVap)</h2>';
        echo '<p>Attaches the product photography bundled with this plugin to the 5 M 7 / M 7 XL SKUs and 9 accessory SKUs (torch, DynaMag, DynaWax, caps, CCDs, O-rings, mouthpiece) by matching on SKU. Images ship inside the plugin\'s own <code>images/</code> folder — nothing to upload here.</p>';
        if ($img_result) echo '<div class="notice notice-info"><p>'.nl2br(esc_html($img_result)).'</p></div>';
        echo '<form method="post">';
        wp_nonce_field('rr_import_images');
        echo '<label><input type="checkbox" name="rr_replace_images" value="1"> Replace images on products that already have one</label><br><br>';
        echo '<button class="button button-primary" name="rr_import_images" value="1">Import Bundled Product Images</button></form>';
        echo '<ul><li>Skips a product that already has a featured image, unless "Replace" is checked.</li><li>First image in each set becomes the featured image; the rest become the product gallery.</li><li>Safe to re-run.</li></ul></div>';
    }
    private function attach_local_image($product_id, $file_path){
        if (!file_exists($file_path)) return 0;
        require_once(ABSPATH.'wp-admin/includes/image.php');
        require_once(ABSPATH.'wp-admin/includes/file.php');
        require_once(ABSPATH.'wp-admin/includes/media.php');
        $filetype = wp_check_filetype(basename($file_path), null);
        $upload_dir = wp_upload_dir();
        $filename = wp_unique_filename($upload_dir['path'], basename($file_path));
        $new_path = trailingslashit($upload_dir['path']).$filename;
        if (!copy($file_path, $new_path)) return 0;
        $attachment = ['post_mime_type'=>$filetype['type'],'post_title'=>sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),'post_content'=>'','post_status'=>'inherit'];
        $attach_id = wp_insert_attachment($attachment, $new_path, $product_id);
        if (!$attach_id) return 0;
        $attach_data = wp_generate_attachment_metadata($attach_id, $new_path);
        wp_update_attachment_metadata($attach_id, $attach_data);
        update_post_meta($attach_id,'_wp_attachment_image_alt', get_the_title($product_id));
        return $attach_id;
    }
    private function import_product_images($replace=false){
        if (!class_exists('WooCommerce')) return 'WooCommerce is required.';
        $base = plugin_dir_path(__FILE__).'images/';
        $sets = [
            'm7' => ['m7-capped-front.png','m7-capped-back.png','m7-uncapped-front.png','m7-uncapped-back.png','m7-stem-front.png','m7-stem-back.png','m7-condenser.png','m7-tip.png','m7-breakdown.png','m7-horizontal.png'],
            'm7-xl' => ['m7-xl-capped.png','m7-xl-capped-horizontal.png','m7-xl-uncapped.png','m7-xl-mouthpiece.png','m7-xl-mouthpiece-and-condenser.png','m7-xl-condenser.png','m7-xl-breakdown.png'],
            'fat-mouthpiece' => ['fat-mouthpiece.jpg'],
            'honest-torch' => ['honest-torch-clear.jpg','honest-torch-green.jpg'],
            'dynamag' => ['dynamag.jpg'],
            'dynawax' => ['dynawax.jpg'],
            'high-temp-o-ring-kit' => ['high-temp-o-ring-kit.jpg'],
            'ccd-stainless-steel' => ['ccd-stainless-steel-3-pack.jpg'],
            'ccd-titanium' => ['ccd-titanium-3-pack.jpg'],
            'armored-cap' => ['armored-cap.jpg'],
            'captive-cap' => ['captive-cap.jpg'],
        ];
        $manifest = [
            '420-VCM853731500F'     => 'm7',
            '420-VCM853731500FEI'   => 'm7',
            '420-VCM853731500FXL'   => 'm7-xl',
            '420-VCM853731500FXLEI' => 'm7-xl',
            '420-VCM853731500FXLST' => 'm7-xl',
            '420-DV-PRT-MP-FAT'       => 'fat-mouthpiece',
            '420-DV-TRCH-HONEST'      => 'honest-torch',
            '420-DV-PRT-DYNAMAG'      => 'dynamag',
            '420-DV-PRT-DYNAWAX'      => 'dynawax',
            '420-DV-PRT-ORING-HT'     => 'high-temp-o-ring-kit',
            '420-DV-PRT-CCD-SS3'      => 'ccd-stainless-steel',
            '420-DV-PRT-CCD-TI3'      => 'ccd-titanium',
            '420-DV-PRT-CAP-ARMORED'  => 'armored-cap',
            '420-DV-PRT-CAP-CAPTIVE'  => 'captive-cap',
        ];
        $lines = [];
        foreach ($manifest as $sku => $set_key) {
            $product_id = wc_get_product_id_by_sku($sku);
            if (!$product_id) { $lines[] = "SKU {$sku}: product not found (import the catalogue CSV first) - skipped."; continue; }
            if (!$replace && has_post_thumbnail($product_id)) { $lines[] = "SKU {$sku}: already has an image - skipped."; continue; }
            $files = $sets[$set_key] ?? [];
            $gallery_ids = []; $attached = 0;
            foreach ($files as $i => $fname) {
                $attach_id = $this->attach_local_image($product_id, $base.$set_key.'/'.$fname);
                if (!$attach_id) continue;
                $attached++;
                if ($i === 0) set_post_thumbnail($product_id, $attach_id);
                else $gallery_ids[] = $attach_id;
            }
            if ($gallery_ids) update_post_meta($product_id, '_product_image_gallery', implode(',', $gallery_ids));
            $lines[] = "SKU {$sku}: attached {$attached} image(s).";
        }
        return implode("\n", $lines);
    }
    private function import_csv($file){
        if (!$file || !empty($file['error'])) return 'CSV upload failed.';
        if (!class_exists('WooCommerce')) return 'WooCommerce is required.';
        $fh=fopen($file['tmp_name'],'r'); if(!$fh) return 'Could not open CSV.';
        $header=fgetcsv($fh); if(!$header) return 'CSV has no header row.';
        $map=[]; foreach($header as $i=>$h){$map[strtolower(trim($h))]=$i;}
        foreach(['sku','name','categories','brand'] as $req){if(!isset($map[$req])){fclose($fh);return 'Missing required column: '.$req;}}
        $count=0;$created=0;$updated=0;$skipped=0;
        while(($row=fgetcsv($fh))!==false){
            $get=function($key)use($map,$row){return isset($map[$key]) ? trim((string)($row[$map[$key]]??'')) : '';};
            $sku=$get('sku');$name=$get('name'); if(!$sku||!$name){$skipped++;continue;}
            $id=wc_get_product_id_by_sku($sku);
            $product=$id ? wc_get_product($id) : new WC_Product_Simple();
            $is_new=!$id;
            $product->set_name($name); $product->set_sku($sku);
            $pub=$get('published'); $product->set_status($pub==='1'?'publish':'draft');
            $price=$get('regular price'); if($price!=='') $product->set_regular_price(wc_format_decimal($price));
            $product->set_description($get('description'));
            $product->set_catalog_visibility($get('catalog visibility') ?: 'visible');
            $product->set_stock_status($get('stock status')==='outofstock'?'outofstock':'instock');
            // Optional real stock quantity: if the CSV has a numeric "Stock quantity", WooCommerce tracks it (status follows the count).
            $qty=$get('stock quantity');
            if($qty!=='' && is_numeric($qty)){ $product->set_manage_stock(true); $product->set_stock_quantity((int)$qty); }
            $product->set_tax_status($get('tax status')==='none'?'none':'taxable');
            $saved=$product->save();
            update_post_meta($saved,'_420_rentals_brand',$get('brand'));
            update_post_meta($saved,'_420_rentals_rental_candidate',$get('rental candidate'));
            update_post_meta($saved,'_420_rentals_supplier_cost_usd',$get('supplier cost usd'));
            update_post_meta($saved,'_420_rentals_product_type',$get('product type'));
            update_post_meta($saved,'_420_rentals_external_url',$get('external url'));
            // Categories are created if needed.
            $cats=array_filter(array_map('trim',preg_split('/[,|]/',$get('categories'))));
            if($cats) wp_set_object_terms($saved,$cats,'product_cat',false);
            // Brand: use WooCommerce's native Brands taxonomy if present; created if needed.
            if (taxonomy_exists('product_brand')) {
                $brands=array_filter(array_map('trim',preg_split('/[,|]/',$get('brand'))));
                if($brands) wp_set_object_terms($saved,$brands,'product_brand',false);
            }
            $count++; if($is_new)$created++;else$updated++;
        }
        fclose($fh); return "Processed {$count} rows: {$created} created, {$updated} updated, {$skipped} skipped.";
    }
}
new Rentals_420_Catalog_Importer();
