MemoMind Clone Native WordPress Theme v1.0.0

INSTALL
1. Upload/activate this theme.
2. Install + activate WooCommerce if product/cart/checkout are required.
3. WordPress admin > Appearance > MemoMind Import > run the importer once.
4. Settings > Permalinks > Save once if needed.

ARCHITECTURE
- views/: processed snapshots preserving supplied DOM/CSS/JS.
- assets/: locally mirrored images/video/fonts/CSS/JS.
- routes.json: maps original Shopify routes to snapshots.
- functions.php: renderer, native search/contact/newsletter, WooCommerce cart API bridge, content importer.
- assets/compat.js: Shopify frontend compatibility shims.

NOTES
- Shopify analytics, Klaviyo, PushOwl, Consentmo and other store-specific network trackers are deliberately not executed.
- Original PageFly/theme interaction JS is preserved.
- WooCommerce checkout is native at /checkout/.
- Imported WP content records are for native admin/SEO management; the clone renderer remains source-of-truth for exact frontend parity until each route is progressively converted to native WP templates.
