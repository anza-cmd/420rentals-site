<?php
/**
 * Plugin Name: 420 Rentals Catalog Importer
 * Description: Imports the 420 Rentals WooCommerce catalogue CSV and creates/updates products by SKU. Imports as Draft by default.
 * Version: 1.1.0
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
        $result = null;
        if (!empty($_POST['rr_import']) && check_admin_referer('rr_import_catalog')) {
            $result = $this->import_csv($_FILES['catalog_csv'] ?? null);
        }
        echo '<div class="wrap"><h1>420 Rentals Catalog Importer</h1>';
        echo '<p>Upload the CSV supplied with the 420 Rentals catalogue. Products are imported as <strong>Draft</strong> unless the CSV Published field is explicitly set to 1.</p>';
        if ($result) echo '<div class="notice notice-info"><p>'.esc_html($result).'</p></div>';
        echo '<form method="post" enctype="multipart/form-data">';
        wp_nonce_field('rr_import_catalog');
        echo '<input type="file" name="catalog_csv" accept=".csv" required> ';
        echo '<button class="button button-primary" name="rr_import" value="1">Import / Update Catalogue</button></form>';
        echo '<h2>Safety defaults</h2><ul><li>CSV rows default to Draft.</li><li>Existing products are matched by SKU.</li><li>No orders, payments or delivery settings are changed.</li><li>Supplier cost is stored as private product metadata.</li><li>Brand is set on WooCommerce\'s native Brands taxonomy (if enabled); terms are created automatically.</li></ul></div>';
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
