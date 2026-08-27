
    (function() {
      var preconnectOrigins = ["https://cdn.shopify.com"];
      var scripts = ["/cdn/shopifycloud/checkout-web/assets/c1/polyfills-legacy.B6bbmry-.js","/cdn/shopifycloud/checkout-web/assets/c1/app-legacy.BfCudTQN.js","/cdn/shopifycloud/checkout-web/assets/c1/esnext-vendor-legacy.e4Kpoeuf.js","/cdn/shopifycloud/checkout-web/assets/c1/context-browser-legacy.DRjG5-_Z.js","/cdn/shopifycloud/checkout-web/assets/c1/utilities-previous-legacy.DWADRhp5.js","/cdn/shopifycloud/checkout-web/assets/c1/receipt-mapper-load-recovery-legacy.C348h3B0.js","/cdn/shopifycloud/checkout-web/assets/c1/receipt-eager-mappers-legacy.BNrcc48c.js","/cdn/shopifycloud/checkout-web/assets/c1/utilities-errors-legacy.DOPbQAOv.js","/cdn/shopifycloud/checkout-web/assets/c1/checkout-proposal-legacy.COYvm-dW.js","/cdn/shopifycloud/checkout-web/assets/c1/utilities-stopwatch-legacy.CKITBvmp.js","/cdn/shopifycloud/checkout-web/assets/c1/NotFound-legacy.Ci3629ug.js","/cdn/shopifycloud/checkout-web/assets/c1/hooks-useReplaceShopPayInHistory-legacy.CZCqUKDB.js","/cdn/shopifycloud/checkout-web/assets/c1/mobile-checkout-sdk-MobileCheckoutSdkClient-legacy.Ceh0x4GY.js","/cdn/shopifycloud/checkout-web/assets/c1/consent-manager-shared-legacy.CikKLZ9k.js","/cdn/shopifycloud/checkout-web/assets/c1/utilities-extension-execution-errors-legacy.ClbSOEk5.js","/cdn/shopifycloud/checkout-web/assets/c1/extensions-rpc-legacy.TEgbISdY.js","/cdn/shopifycloud/checkout-web/assets/c1/error-logger-report-graphql-error-legacy.Cu_prViC.js","/cdn/shopifycloud/checkout-web/assets/c1/shop-pay-normalizeBuyerDetails-legacy.CQiP-f8_.js","/cdn/shopifycloud/checkout-web/assets/c1/hooks-useShopPayCheckoutGqlVersion-legacy.CiFu6Sfy.js","/cdn/shopifycloud/checkout-web/assets/c1/utilities-shopCashMoney-legacy.CeVq1Pnr.js","/cdn/shopifycloud/checkout-web/assets/c1/hydrate-legacy.Ck4PQ4VX.js","/cdn/shopifycloud/checkout-web/assets/c1/locale-fr-legacy.p9W-mOWD.js","/cdn/shopifycloud/checkout-web/assets/c1/OnePage-legacy.BiTOdXFv.js","/cdn/shopifycloud/checkout-web/assets/c1/hooks-useUnauthenticatedErrorModal-legacy.DCIwXv7I.js","/cdn/shopifycloud/checkout-web/assets/c1/hooks-usePostPurchase-legacy.DP_rKQJL.js","/cdn/shopifycloud/checkout-web/assets/c1/components-DeliveryTransition-legacy.D2rBnt5p.js","/cdn/shopifycloud/checkout-web/assets/c1/hooks-useShowShopPayOptin-legacy.D33pHtGx.js","/cdn/shopifycloud/checkout-web/assets/c1/color-contrast-colorContrast-legacy.DnGotXzf.js","/cdn/shopifycloud/checkout-web/assets/c1/remember-me-hooks-legacy.cijPkveF.js","/cdn/shopifycloud/checkout-web/assets/c1/ChangeCompanyLocationLink-legacy.BooHmgjA.js","/cdn/shopifycloud/checkout-web/assets/c1/BillingAddressForm-legacy.DUkht3zb.js","/cdn/shopifycloud/checkout-web/assets/c1/PhoneField-legacy.BFZIn0ut.js","/cdn/shopifycloud/checkout-web/assets/c1/hooks-useCanChangeCompanyLocation-legacy.C2txAKyo.js","/cdn/shopifycloud/checkout-web/assets/c1/components-RedirectionNotice.module-legacy.CG8bUgWW.js","/cdn/shopifycloud/checkout-web/assets/c1/Popover-legacy.CkNYReFx.js","/cdn/shopifycloud/checkout-web/assets/c1/Choice-legacy.BIqsTrkf.js","/cdn/shopifycloud/checkout-web/assets/c1/Checkbox-legacy.Curq4j8C.js","/cdn/shopifycloud/checkout-web/assets/c1/hooks-useForceShopPayUrl-legacy.D8KNUB6f.js","/cdn/shopifycloud/checkout-web/assets/c1/shipping-methods-grouping-legacy.Cv3HOBT_.js","/cdn/shopifycloud/checkout-web/assets/c1/hooks-useEcpSpiDebugLog-legacy.CMlNv4gz.js","/cdn/shopifycloud/checkout-web/assets/c1/ShopPayLogo-legacy.KGaLdSab.js","/cdn/shopifycloud/checkout-web/assets/c1/Monorail-monorailMetric-wallets-legacy.CY8hhSkR.js","/cdn/shopifycloud/checkout-web/assets/c1/shop-pay-installments-monorail-legacy.fJiz5V3K.js","/cdn/shopifycloud/checkout-web/assets/c1/EmptyState-legacy.FEpiMm4G.js","/cdn/shopifycloud/checkout-web/assets/c1/AutocompleteField-hooks-legacy.DTghFO15.js","/cdn/shopifycloud/checkout-web/assets/c1/PendingShipping-legacy.CRR10PHz.js","/cdn/shopifycloud/checkout-web/assets/c1/components-useVaultedMsiInstallments-legacy.xiF2b2Bj.js","/cdn/shopifycloud/checkout-web/assets/c1/PaymentIcon-legacy.zZ2nKeW8.js","/cdn/shopifycloud/checkout-web/assets/c1/shop-cash-context-legacy.4daCCKIx.js","/cdn/shopifycloud/checkout-web/assets/c1/hooks-useGeneralPaymentErrorMessage-legacy.BJATBUzy.js","/cdn/shopifycloud/checkout-web/assets/c1/PaymentLine-legacy.D6sf_xbD.js","/cdn/shopifycloud/checkout-web/assets/c1/useShopPayButtonClassName-legacy.Xbpsl0Ne.js","/cdn/shopifycloud/checkout-web/assets/c1/cvv-cvvBridge-legacy.DMPsdKTe.js","/cdn/shopifycloud/checkout-web/assets/c1/hooks-useFilteredShopPayAvailablePaymentMethods-legacy.BUDnFu9c.js","/cdn/shopifycloud/checkout-web/assets/c1/Section-legacy.Bz899qF-.js","/cdn/shopifycloud/checkout-web/assets/c1/MobileOrderSummary-legacy.VB2YTXSh.js","/cdn/shopifycloud/checkout-web/assets/c1/useShopPaySessionTokenStorage-legacy.CP3ZXTLz.js","/cdn/shopifycloud/checkout-web/assets/c1/hooks-useOnePageFormSubmit-legacy.Ckmtf5O_.js","/cdn/shopifycloud/checkout-web/assets/c1/PaymentButtons-legacy.BZkemB9x.js","/cdn/shopifycloud/checkout-web/assets/c1/shop-pay-installments-types-legacy.DhUMHvNc.js","/cdn/shopifycloud/checkout-web/assets/c1/IncentiveBadge-legacy.CvVr7ixH.js","/cdn/shopifycloud/checkout-web/assets/c1/utils-useViolationsHandler-legacy.D_v7Ko4X.js","/cdn/shopifycloud/checkout-web/assets/c1/hooks-payment-button-legacy.BjaYxzGV.js","/cdn/shopifycloud/checkout-web/assets/c1/hooks-useStableHostMethodsReferences-legacy.BoAz0-sM.js","/cdn/shopifycloud/checkout-web/assets/c1/shop-cash-monorail-legacy.B4EB50XN.js","/cdn/shopifycloud/checkout-web/assets/c1/BillingAddressSelector-legacy.Da1bQrgI.js","/cdn/shopifycloud/checkout-web/assets/c1/PaymentErrorBanner-legacy.M0oBHQt6.js","/cdn/shopifycloud/checkout-web/assets/c1/Switch-legacy.MWtSgEdB.js","/cdn/shopifycloud/checkout-web/assets/c1/hooks-useAvailableShopPromotionDiscounts-legacy.406V6t2P.js","/cdn/shopifycloud/checkout-web/assets/c1/Middot-legacy.Cn6dYiDc.js","/cdn/shopifycloud/checkout-web/assets/c1/EstimatedDeliveryContent-legacy.CCBlYvns.js","/cdn/shopifycloud/checkout-web/assets/c1/ShippingMethodRateLabel-legacy.VRrB7pEI.js","/cdn/shopifycloud/checkout-web/assets/c1/shipping-methods-consolidated-included-legacy.CIK9RTZF.js","/cdn/shopifycloud/checkout-web/assets/c1/sandbox-helpers-legacy.PSpA26dH.js","/cdn/shopifycloud/checkout-web/assets/c1/ShippingLines-legacy.kwAEmXdl.js","/cdn/shopifycloud/checkout-web/assets/c1/ShipmentBreakdown-legacy.Wetri53T.js","/cdn/shopifycloud/checkout-web/assets/c1/MerchandiseModal-legacy.m-bTk0Zi.js","/cdn/shopifycloud/checkout-web/assets/c1/ShippingMethodSelector-legacy.C9Fd5piZ.js","/cdn/shopifycloud/checkout-web/assets/c1/TextArea-legacy.CPwzCqc6.js","/cdn/shopifycloud/checkout-web/assets/c1/SubscriptionPriceBreakdown-legacy.Do0Nmc46.js","/cdn/shopifycloud/checkout-web/assets/c1/StockProblems-StockProblemsLineItemList-legacy.CCbJ8Aij.js","/cdn/shopifycloud/checkout-web/assets/c1/hooks-useShopPayNewSignupLoginExperiment-legacy.DUNWGDW9.js"];
      var styles = [];
      var fontPreconnectUrls = [];
      var fontPrefetchUrls = [];
      var imgPrefetchUrls = ["https://cdn.shopify.com/s/files/1/0656/0173/2721/files/MemoMind_logo_black_x320.png?v=1767165227"];

      function preconnect(url, callback) {
        var link = document.createElement('link');
        link.rel = 'dns-prefetch preconnect';
        link.href = url;
        link.crossOrigin = '';
        link.onload = link.onerror = callback;
        document.head.appendChild(link);
      }

      function preconnectAssets() {
        var resources = preconnectOrigins.concat(fontPreconnectUrls);
        var index = 0;
        (function next() {
          var res = resources[index++];
          if (res) preconnect(res, next);
        })();
      }

      function prefetch(url, as, callback) {
        var link = document.createElement('link');
        if (link.relList.supports('prefetch')) {
          link.rel = 'prefetch';
          link.fetchPriority = 'low';
          link.as = as;
          if (as === 'font') link.type = 'font/woff2';
          link.href = url;
          link.crossOrigin = '';
          link.onload = link.onerror = callback;
          document.head.appendChild(link);
        } else {
          var xhr = new XMLHttpRequest();
          xhr.open('GET', url, true);
          xhr.onloadend = callback;
          xhr.send();
        }
      }

      function prefetchAssets() {
        var resources = [].concat(
          scripts.map(function(url) { return [url, 'script']; }),
          styles.map(function(url) { return [url, 'style']; }),
          fontPrefetchUrls.map(function(url) { return [url, 'font']; }),
          imgPrefetchUrls.map(function(url) { return [url, 'image']; })
        );
        var index = 0;
        function run() {
          var res = resources[index++];
          if (res) prefetch(res[0], res[1], next);
        }
        var next = (self.requestIdleCallback || setTimeout).bind(self, run);
        next();
      }

      function onLoaded() {
        try {
          if (parseFloat(navigator.connection.effectiveType) > 2 && !navigator.connection.saveData) {
            preconnectAssets();
            prefetchAssets();
          }
        } catch (e) {}
      }

      if (document.readyState === 'complete') {
        onLoaded();
      } else {
        addEventListener('load', onLoaded);
      }
    })();
  