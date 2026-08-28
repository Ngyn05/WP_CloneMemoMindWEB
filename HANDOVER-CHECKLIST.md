# MemoMind VN — SEO/GEO & hậu triển khai

Cập nhật: 28/08/2026. Phạm vi kiểm tra: theme local và website `memomind-vn.local`.

## Kết quả tự động

- 60 route được render thật qua WordPress: HTTP, title, description, robots meta, canonical, Open Graph, favicon, đúng một H1, JSON-LD và domain cũ.
- Kết quả cuối: 0 lỗi status, 0 lỗi metadata, 0 lỗi JSON-LD, 0 lỗi H1, 0 URL/schema còn trỏ `memo-mind.com`.
- PHP 8.2 lint: tất cả file PHP không có lỗi cú pháp.
- Responsive browser smoke test: mobile 360px và desktop 1440px trên các template đại diện; đã xử lý tràn ngang và loại Shopify module/checkout bootstrap không dùng.
- URL 404 trả đúng HTTP 404 và có liên kết quay về trang chủ.

Chạy lại:

```powershell
node tools/audit-rendered.js
node tools/audit-browser.js
```

## Kết luận theo mức ưu tiên P0 / P1 / P2

Quy ước: **P0** chặn launch/nghiệm thu; **P1** phải hoàn thiện trước hoặc ngay sau launch; **P2** là tối ưu và vận hành dài hạn. “Đạt local” chỉ xác nhận phần theme/WordPress local, không thay cho kiểm tra production.

| Mức | Hạng mục | Kết quả hiện tại | Gate còn lại |
|---|---|---|---|
| P0 | Route quan trọng trả 200; URL lỗi trả 404 thật; không 5xx | ĐẠT LOCAL | Retest production sau deploy. |
| P0 | HTTPS, HTTP→HTTPS, www/non-www, mixed content, redirect chain | CHƯA THỂ XÁC NHẬN | Bắt buộc test trên host/CDN. |
| P0 | robots/noindex/X-Robots-Tag không chặn trang SEO | ĐẠT CODE, CHỜ HOST | Bật Search Engine Visibility và kiểm tra header production. |
| P0 | Canonical, title, description, đúng một H1, OG, favicon | ĐẠT 60/60 ROUTE | Retest source production. |
| P0 | Sitemap chỉ chứa URL 200, canonical, indexable | ĐẠT LOCAL: 44 URL | Submit/kiểm tra xử lý trên GSC production. |
| P0 | Schema parse được, đúng loại và không bịa dữ liệu | ĐẠT CODE | Rich Results Test production; không bật Offer khi chưa có giá/kho thật. |
| P0 | Không còn domain staging/domain cũ trong HTML render | ĐẠT 60/60 ROUTE | Retest production sau thay domain. |
| P0 | Menu/CTA/form/order hoạt động end-to-end | ĐẠT UI/JS LOCAL, CHƯA ĐẠT E2E | Bắt buộc test SMTP, email nhận, đơn WooCommerce và thanh toán trên host. |
| P0 | Không lộ debug, `.env`, backup, SQL, `.git`; admin/MFA/phân quyền | ĐẠT PHẦN THEME, CHỜ HOST | Kiểm tra webroot/server và tài khoản production. |
| P0 | Backup DB + files và thử restore | CHƯA ĐẠT | Phải cấu hình và restore thử trên môi trường an toàn. |
| P1 | Responsive, overflow, lỗi JS và local asset | ĐẠT 20 CA KIỂM THỬ | Test thêm Safari/iOS và thiết bị thật sau deploy. |
| P1 | Footer/NAP/chính sách/liên hệ nhất quán | ĐẠT CÓ ĐIỀU KIỆN | Còn thiếu tên pháp nhân, MST và điều khoản bảo hành chính thức do chủ sở hữu chưa cung cấp. |
| P1 | Ảnh alt, favicon 1:1, Apple 180, PWA 192/512, manifest | ĐẠT | Theo dõi CLS; 85 ảnh snapshot chưa có kích thước tĩnh. |
| P1 | Core Web Vitals/PageSpeed, cache, CDN, compression | CHƯA THỂ XÁC NHẬN | Đo mobile/desktop trên production sau khi gắn tag và cache. |
| P1 | GSC/Bing verify, sitemap, URL Inspection | BỎ QUA ĐẾN KHI LÊN HOST | Thực hiện trong 24 giờ đầu. |
| P1 | GA4/GTM/conversion | BỎ QUA THEO YÊU CẦU | Thực hiện trên host, tránh cài trùng gtag và GTM. |
| P2 | Merchant Center/Product Offer/feed | CHƯA ÁP DỤNG | Chỉ làm khi có giá VND, tồn kho, shipping/return thật đã duyệt. |
| P2 | IndexNow, CWV field data, theo dõi 7/30 ngày | CHỜ SAU LAUNCH | Đưa vào lịch vận hành. |

**Kết luận gate:** phần P0 có thể xử lý trong code/local đã đạt; toàn bộ P0 của website **chưa được phép ghi 100%** cho tới khi các dòng “chờ host/chưa đạt” ở trên có bằng chứng production.

## Checklist từ tài liệu SEO/GEO

| Nhóm | Trạng thái | Bằng chứng / ghi chú |
|---|---|---|
| HTTPS, www/non-www, HTTP redirect | LÊN HOST | Cần kiểm tra DNS, chứng chỉ và redirect trên production. |
| URL thường, trailing slash, redirect cũ | ĐẠT LOCAL | Router chuẩn hóa trailing slash; `/pages/*` và `/fr/*` redirect 301 sang URL Việt chuẩn. |
| Canonical | ĐẠT | Renderer sinh absolute self-canonical theo `home_url()`; audit 60/60 route đạt. |
| HTTP status, soft 404 | ĐẠT LOCAL | Route hợp lệ 200; URL test không tồn tại trả 404 thật. |
| Title, description, H1 | ĐẠT | Metadata động theo URL; bổ sung mô tả category/policy; mỗi route có đúng một H1. |
| Robots meta | ĐẠT CODE / LÊN HOST | Trang công khai index/follow; search/cart/account noindex. Production phải bật “Search engine visibility”. |
| robots.txt | ĐẠT CODE / LÊN HOST | Cho Google/bing/OAI-SearchBot; chặn vùng riêng tư; GPTBot bị chặn theo chính sách training; cần retest production/WAF. |
| Sitemap | ĐẠT LOCAL / LÊN HOST | Yoast sitemap index trả 200; cần submit GSC và kiểm tra URL production sau migration. |
| Bot/WAF/CDN/geoblock/rate limit | LÊN HOST | Không thể nghiệm thu từ theme local. Test Googlebot, OAI-SearchBot, bingbot và log 403/429 sau deploy. |
| SSR/HTML ban đầu | ĐẠT | Nội dung, heading, internal link và JSON-LD có trong response HTML. |
| WebSite schema | ĐẠT | Graph trang chủ có name, alternateName, URL và publisher ổn định. |
| OnlineStore schema | ĐẠT CÓ ĐIỀU KIỆN | Có name, URL, logo, email, hotline, hai địa chỉ, contactPoint, social. Chưa khai `legalName`/`taxID` vì chưa có dữ liệu pháp lý đã xác minh. |
| Product schema | ĐẠT KHÔNG OFFER | ProductGroup đúng type/brand/URL. Đã xóa Offer USD 30 sai và type tiếng Việt không hợp lệ. Không khai Offer vì giao diện cố ý không công bố giá. |
| Seller/manufacturer/authorized dealer | ĐẠT | Không tự nhận seller là manufacturer/đại lý ủy quyền; không bịa GTIN/MPN/review/rating. |
| Breadcrumb/schema URL | ĐẠT | Schema cũ được chuyển sang domain hiện tại; JSON-LD parse 100%. |
| Favicon/site name/OG | ĐẠT | Favicon SVG vuông, Apple PNG 180×180, PWA PNG 192/512 và manifest; OG title/description/url/image/site name và locale có đủ trên mọi route. |
| Ảnh | ĐẠT CƠ BẢN | Ảnh có alt; ảnh fallback video trang trí dùng alt rỗng; snapshot có responsive srcset và sửa descriptor lỗi. Ảnh thiếu width/height còn lại chủ yếu do component động/inline SVG và cần theo dõi CLS thực tế. |
| Mobile/overflow | ĐẠT SMOKE TEST | Kiểm tra 360px và 1440px; thêm containment chống tràn ngang. |
| Footer/NAP/chính sách | ĐẠT CÓ ĐIỀU KIỆN | Có brand, 2 địa chỉ, hotline, email, giờ hỗ trợ, copyright và link giới thiệu/liên hệ/bảo hành/đổi trả/vận chuyển/thanh toán/bảo mật/điều khoản. Cần bổ sung legal name + MST khi chủ sở hữu cung cấp. |
| Bảo hành/vận chuyển/thanh toán/đổi trả | ĐẠT | Có route nội dung nhìn thấy và internal link footer. |
| Form/CTA/order | ĐẠT CODE / LÊN HOST | Handler có sanitize, nonce ở luồng đặt hàng và phản hồi trạng thái; cần test email/SMTP/đơn WooCommerce end-to-end trên host. |
| Security headers/debug | ĐẠT LOCAL | `WP_DEBUG=false`; có nosniff, Referrer-Policy, SAMEORIGIN, Permissions-Policy; HSTS chỉ bật trên HTTPS. |
| File nhạy cảm, admin/MFA, backup/restore | LÊN HOST | Kiểm tra hạ tầng/quyền truy cập, không thể chứng minh bằng theme. |
| Core Web Vitals/PageSpeed | LÊN HOST | Chạy PSI mobile/desktop sau khi cache/CDN và tag production hoàn tất. |

## Checklist SOP hậu triển khai

| Hạng mục | Trạng thái |
|---|---|
| Search Console Domain Property, owner dự phòng, URL Inspection | BỎ QUA ĐẾN KHI LÊN HOST |
| Submit sitemap và theo dõi Page indexing | BỎ QUA ĐẾN KHI LÊN HOST |
| GA4 Realtime, ecommerce/lead events, đối soát event | BỎ QUA THEO YÊU CẦU |
| GTM container, Preview, publish/version | BỎ QUA THEO YÊU CẦU |
| Merchant Center/feed/shipping-return markup | CHƯA ÁP DỤNG vì chưa công bố giá/feed |
| Bing Webmaster Tools và IndexNow | BỎ QUA ĐẾN KHI LÊN HOST |
| WAF/CDN bot validation | BỎ QUA ĐẾN KHI LÊN HOST |
| TLS, compression, cache, PageSpeed field data | BỎ QUA ĐẾN KHI LÊN HOST |
| Backup DB/files/off-site và restore drill | BỎ QUA ĐẾN KHI LÊN HOST |
| Kiểm tra 24 giờ, 7 ngày, 30 ngày | PHẢI LẬP LỊCH SAU LAUNCH |

## Dữ liệu chủ sở hữu còn phải cung cấp

Các mục sau không được tự bịa để “đạt checklist”:

1. Tên pháp nhân đầy đủ và mã số thuế để đưa vào footer, trang Liên hệ/Giới thiệu và `OnlineStore.legalName/taxID`.
2. Căn cứ được phép dùng tuyên bố “chính hãng”, “đại lý ủy quyền” hoặc “nhà phân phối” nếu muốn công bố.
3. Giá bán VND, tình trạng kho và chính sách thương mại đã duyệt nếu muốn bật `Offer`/Merchant Center.
4. Thời hạn và điều kiện bảo hành chính thức thay cho nội dung quy trình hỗ trợ hiện tại.

## Gate trước khi ký nghiệm thu production

- Không ký nếu production còn `X-Robots-Tag: noindex`, Search Engine Visibility bị tắt, robots chặn site hoặc WAF trả 403/429 cho bot hợp lệ.
- Không ký nếu canonical/sitemap còn domain local/staging hoặc HTTP.
- Không ký nếu chưa test form/email/order end-to-end, backup/restore và URL Inspection.
- Không thêm Offer, GTIN, MPN, aggregateRating, legalName, taxID hoặc tuyên bố ủy quyền khi chưa có dữ liệu thật.
