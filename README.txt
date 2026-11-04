420 Rentals Catalog Importer

Installation
1. WordPress Admin -> Plugins -> Add New -> Upload Plugin.
2. Upload 420-rentals-catalog-importer.zip.
3. Activate it. WooCommerce must already be active.
4. Go to WooCommerce -> 420 Rentals Catalog.
5. Upload the supplied 420_Rentals_WooCommerce_Import_2026-09-17.csv.
6. Import / Update Catalogue.

The importer intentionally creates/updates products as Draft unless the CSV Published field is 1. The supplied CSV has products unpublished so you can review pricing, legal/compliance, images, descriptions and availability first.

The plugin does not process payments, orders, delivery, age verification or rental scheduling. It is a catalogue importer only.

v1.1.0: Brand is now assigned to WooCommerce's native Brands taxonomy (product_brand) if it is enabled on the site, creating new brand terms automatically. If Brands isn't enabled, brand still gets stored as private product metadata only, same as before.

v1.2.0: Adds a second tool on the same admin page, "Import Bundled Product Images". Attaches the DynaVap product photography bundled in this plugin's images/ folder to the 5 M 7 / M 7 XL SKUs (standard finish - The M 7, The M 7 (INTL), The M 7 XL, The M 7 XL (INTL), The M 7 XL - Stealth) by matching on SKU, no manual upload needed. Skips products that already have an image unless "Replace" is checked. Run the CSV import first so the products exist, then run this.
