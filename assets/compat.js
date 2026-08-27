(function(){
'use strict';
// Keep internal links on the WordPress host and repair captured .html URLs.
document.addEventListener('click',function(e){
  var a=e.target.closest && e.target.closest('a[href]'); if(!a)return;
  try{
    var u=new URL(a.href,location.href);
    if(u.origin!==location.origin)return;
    if(/(?:^|\/)index\.htm$/i.test(u.pathname))u.pathname=u.pathname.replace(/(?:^|\/)index\.htm$/i,'/');
    else if(/\.html$/i.test(u.pathname))u.pathname=u.pathname.replace(/\.html$/i,'/');
    else if(/\/account\/?$/i.test(u.pathname))u.pathname=u.pathname.replace(/\/account\/?$/i,'/my-account/');
    a.href=u.toString();
  }catch(_e){}
},true);
// Shopify themes expect this global even outside Shopify.
window.Shopify=window.Shopify||{}; Shopify.routes=Shopify.routes||{}; Shopify.routes.root=new URL('./',document.baseURI).pathname;
// Buy-now should land at WooCommerce checkout after the original add-to-cart succeeds.
document.addEventListener('click',function(e){
  var b=e.target.closest&&e.target.closest('[id*="pre-glass-sku-buy"], [name="checkout"]'); if(!b)return;
  window.__mmBuyNow=true; setTimeout(function(){ if(window.__mmBuyNow) location.href='/checkout/'; },900);
},true);
// Replace stale Shopify checkout links.
document.querySelectorAll('a[href*="/checkouts/"],a[href="/checkout"]').forEach(function(a){a.href='/checkout/';});
})();
