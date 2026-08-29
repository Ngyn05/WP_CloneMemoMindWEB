<?php
if (!defined('ABSPATH')) exit;

/**
 * MemoMind Floating Contact Widget & Office System Modal
 */
function mm_get_floating_contact_markup() {
    ob_start();
    ?>
    <!-- MemoMind Floating Contact Widget -->
    <div id="mm-floating-widget" class="mm-floating-widget" aria-label="Liên hệ nhanh MemoMind">
      <!-- Popover Menu -->
      <div id="mm-floating-popover" class="mm-floating-popover" role="dialog" aria-modal="false" aria-labelledby="mm-floating-popover-title" hidden>
        <div class="mm-floating-popover__header">
          <h4 id="mm-floating-popover-title">Liên hệ với MemoMind VN</h4>
          <button type="button" class="mm-floating-popover__close" id="mm-floating-popover-close" aria-label="Đóng bảng liên hệ">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>
        <div class="mm-floating-popover__body">
          <!-- Item 1: Hotline -->
          <a href="tel:1900638400" class="mm-floating-item mm-floating-item--hotline">
            <div class="mm-floating-item__icon mm-floating-item__icon--hotline" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
              </svg>
            </div>
            <div class="mm-floating-item__content">
              <span class="mm-floating-item__title">Số điện thoại Hotline</span>
              <span class="mm-floating-item__subtitle mm-floating-item__subtitle--highlight">1900.63.8400</span>
            </div>
          </a>

          <!-- Item 2: Zalo -->
          <a href="https://zalo.me/0917834532" target="_blank" rel="noopener noreferrer" class="mm-floating-item mm-floating-item--zalo">
            <div class="mm-floating-item__icon mm-floating-item__icon--zalo" aria-hidden="true">
              <svg viewBox="0 0 48 48" width="24" height="24">
                <path d="M41.8 22.8c0-8.8-8-16-17.8-16S6.2 14 6.2 22.8c0 5 2.6 9.5 6.6 12.4l-1.7 6.4c-.2.8.5 1.5 1.3 1.2l7.6-3.8c1.3.3 2.7.5 4 .5 9.8 0 17.8-7.2 17.8-16.7z" fill="#ffffff"/>
                <path d="M15.5 27.5h6.6v-2.1h-4.2l4.2-5.4v-1.5h-6.4v2.1h4.1l-4.3 5.4v1.5zm8.8 0h2.4v-9h-2.4v9zm5 0h2.4v-5.2c0-1.1.7-1.7 1.8-1.7s1.7.6 1.7 1.7v5.2h2.4v-5.5c0-2.3-1.4-3.6-3.4-3.6-1.5 0-2.5.8-3 1.9v-1.8h-1.9v8.9zm10.7-9.2c-2.7 0-4.8 2.1-4.8 4.7s2.1 4.7 4.8 4.7 4.8-2.1 4.8-4.7-2.1-4.7-4.8-4.7zm0 7.1c-1.4 0-2.4-1.1-2.4-2.4s1-2.4 2.4-2.4 2.4 1.1 2.4 2.4-1 2.4-2.4 2.4z" fill="#0068FF"/>
              </svg>
            </div>
            <div class="mm-floating-item__content">
              <span class="mm-floating-item__title">Chat qua Zalo</span>
              <span class="mm-floating-item__subtitle mm-floating-item__subtitle--zalo">Nhắn tin tư vấn ngay</span>
            </div>
          </a>

          <!-- Item 3: Văn phòng -->
          <button type="button" class="mm-floating-item mm-floating-item--office" id="mm-open-office-modal-btn">
            <div class="mm-floating-item__icon mm-floating-item__icon--office" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                <circle cx="12" cy="10" r="3"></circle>
              </svg>
            </div>
            <div class="mm-floating-item__content">
              <span class="mm-floating-item__title">Công ty MemoMind Việt Nam</span>
              <span class="mm-floating-item__subtitle">Xem hệ thống văn phòng</span>
            </div>
          </button>
        </div>
      </div>

      <!-- Trigger Button -->
      <button type="button" id="mm-floating-trigger-btn" class="mm-floating-trigger" aria-label="Mở menu tư vấn & liên hệ">
        <span class="mm-floating-trigger__pulse"></span>
        <span class="mm-floating-trigger__icon mm-floating-trigger__icon--phone">
          <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="#ffffff" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
          </svg>
        </span>
        <span class="mm-floating-trigger__icon mm-floating-trigger__icon--close" hidden>
          <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="#ffffff" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </span>
      </button>
    </div>

    <!-- Office System Modal -->
    <div id="mm-office-modal" class="mm-office-modal" role="dialog" aria-modal="true" aria-labelledby="mm-office-modal-title" hidden inert>
      <div class="mm-office-modal__backdrop" id="mm-office-modal-backdrop"></div>
      <div class="mm-office-modal__dialog">
        <!-- Modal Header -->
        <div class="mm-office-modal__header">
          <div class="mm-office-modal__header-info">
            <span class="mm-office-modal__badge-brand">MEMOMIND VIỆT NAM</span>
            <h3 id="mm-office-modal-title" class="mm-office-modal__title">Hệ thống văn phòng</h3>
          </div>
          <button type="button" class="mm-office-modal__close" id="mm-office-modal-close" aria-label="Đóng cửa sổ">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <!-- Modal Body (2 Columns) -->
        <div class="mm-office-modal__body">
          <div class="mm-office-modal__grid">
            <!-- Column 1: Hà Nội -->
            <article class="mm-office-card">
              <div class="mm-office-card__head">
                <span class="mm-office-card__tag">HÀ NỘI</span>
                <span class="mm-office-card__region">Chi nhánh miền Bắc</span>
              </div>
              <h4 class="mm-office-card__name">Công ty MemoMind Việt Nam – Chi nhánh Hà Nội</h4>
              
              <ul class="mm-office-card__list">
                <li class="mm-office-card__item">
                  <span class="mm-office-card__icon" aria-hidden="true">📍</span>
                  <div class="mm-office-card__item-text">
                    <strong>Địa chỉ</strong>
                    <p>Số 226 Đường Láng, Phường Thịnh Quang, Quận Đống Đa, Hà Nội</p>
                    <a href="https://maps.google.com/?q=226+Đường+Láng,+Phường+Thịnh+Quang,+Đống+Đa,+Hà+Nội" target="_blank" rel="noopener noreferrer" class="mm-office-card__map-link">Xem trên Google Maps →</a>
                  </div>
                </li>
                <li class="mm-office-card__item">
                  <span class="mm-office-card__icon" aria-hidden="true">📞</span>
                  <div class="mm-office-card__item-text">
                    <strong>Điện thoại</strong>
                    <p><a href="tel:02473053268" class="mm-office-card__phone-link">024.7305.3268</a></p>
                  </div>
                </li>
                <li class="mm-office-card__item">
                  <span class="mm-office-card__icon" aria-hidden="true">✉️</span>
                  <div class="mm-office-card__item-text">
                    <strong>Email</strong>
                    <p><a href="mailto:contact@memomind.vn" class="mm-office-card__email-link">contact@memomind.vn</a></p>
                  </div>
                </li>
                <li class="mm-office-card__item">
                  <span class="mm-office-card__icon" aria-hidden="true">🕒</span>
                  <div class="mm-office-card__item-text">
                    <strong>Giờ làm việc</strong>
                    <p>7:00 – 21:00 · Tất cả các ngày trong tuần (GMT+7)</p>
                  </div>
                </li>
              </ul>
            </article>

            <!-- Column 2: TP. Hồ Chí Minh -->
            <article class="mm-office-card">
              <div class="mm-office-card__head">
                <span class="mm-office-card__tag">TP. HỒ CHÍ MINH</span>
                <span class="mm-office-card__region">Chi nhánh miền Nam</span>
              </div>
              <h4 class="mm-office-card__name">Công ty MemoMind Việt Nam – Chi nhánh Hồ Chí Minh</h4>
              
              <ul class="mm-office-card__list">
                <li class="mm-office-card__item">
                  <span class="mm-office-card__icon" aria-hidden="true">📍</span>
                  <div class="mm-office-card__item-text">
                    <strong>Địa chỉ</strong>
                    <p>Số 137 Đường Hòa Hưng, Phường Hòa Hưng, TP. Hồ Chí Minh</p>
                    <a href="https://maps.google.com/?q=137+Đường+Hòa+Hưng,+Phường+Hòa+Hưng,+TP+Hồ+Chí+Minh" target="_blank" rel="noopener noreferrer" class="mm-office-card__map-link">Xem trên Google Maps →</a>
                  </div>
                </li>
                <li class="mm-office-card__item">
                  <span class="mm-office-card__icon" aria-hidden="true">📞</span>
                  <div class="mm-office-card__item-text">
                    <strong>Điện thoại</strong>
                    <p><a href="tel:02873053268" class="mm-office-card__phone-link">028.7305.3268</a></p>
                  </div>
                </li>
                <li class="mm-office-card__item">
                  <span class="mm-office-card__icon" aria-hidden="true">✉️</span>
                  <div class="mm-office-card__item-text">
                    <strong>Email</strong>
                    <p><a href="mailto:contact@memomind.vn" class="mm-office-card__email-link">contact@memomind.vn</a></p>
                  </div>
                </li>
                <li class="mm-office-card__item">
                  <span class="mm-office-card__icon" aria-hidden="true">🕒</span>
                  <div class="mm-office-card__item-text">
                    <strong>Giờ làm việc</strong>
                    <p>8:00 – 20:30 · Thứ 2 đến Thứ 7 (GMT+7)</p>
                  </div>
                </li>
              </ul>
            </article>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="mm-office-modal__footer">
          <div class="mm-office-modal__footer-text">
            <strong>Cần tư vấn ngay?</strong>
            <span>Đội ngũ MemoMind luôn sẵn sàng hỗ trợ bạn</span>
          </div>
          <a href="tel:1900638400" class="mm-office-modal__hotline-btn">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
            </svg>
            <span>HOTLINE 1900.63.8400</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Styles -->
    <style id="mm-floating-contact-style">
      /* Floating Widget Container */
      .mm-floating-widget {
        position: fixed;
        bottom: 26px;
        right: 26px;
        z-index: 999980;
        font-family: Manrope, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      }
      .mm-floating-widget * {
        box-sizing: border-box;
      }

      /* Trigger Floating Button */
      .mm-floating-trigger {
        position: relative;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #942727 0%, #761a1a 100%);
        border: 2px solid rgba(229, 217, 197, 0.4);
        box-shadow: 0 8px 24px rgba(148, 39, 39, 0.45);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        outline: none;
        transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.25s ease, background 0.25s ease;
      }
      .mm-floating-trigger:hover {
        transform: scale(1.08);
        box-shadow: 0 12px 30px rgba(148, 39, 39, 0.6);
      }
      .mm-floating-trigger.is-active {
        background: #222;
        border-color: #555;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
      }

      /* Pulse wave effect */
      .mm-floating-trigger__pulse {
        position: absolute;
        inset: -6px;
        border-radius: 50%;
        border: 2px solid rgba(148, 39, 39, 0.55);
        animation: mm-floating-pulse 2s infinite cubic-bezier(0.25, 1, 0.5, 1);
        pointer-events: none;
      }
      .mm-floating-trigger.is-active .mm-floating-trigger__pulse {
        display: none;
      }

      @keyframes mm-floating-pulse {
        0% { transform: scale(0.95); opacity: 0.9; }
        70% { transform: scale(1.35); opacity: 0; }
        100% { transform: scale(1.4); opacity: 0; }
      }

      .mm-floating-trigger__icon {
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s ease;
      }
      .mm-floating-trigger:not(.is-active) .mm-floating-trigger__icon--phone svg {
        animation: mm-phone-wiggle 3.5s infinite ease-in-out;
      }

      @keyframes mm-phone-wiggle {
        0%, 80%, 100% { transform: rotate(0deg); }
        83% { transform: rotate(-14deg); }
        86% { transform: rotate(14deg); }
        89% { transform: rotate(-10deg); }
        92% { transform: rotate(10deg); }
        95% { transform: rotate(0deg); }
      }

      /* Popover Card */
      .mm-floating-popover {
        position: absolute;
        bottom: 74px;
        right: 0;
        width: 320px;
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 20px 48px rgba(0, 0, 0, 0.18), 0 4px 12px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(229, 217, 197, 0.7);
        overflow: hidden;
        animation: mm-popover-slide-in 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        transform-origin: bottom right;
      }
      .mm-floating-popover[hidden] {
        display: none !important;
      }

      @keyframes mm-popover-slide-in {
        from { opacity: 0; transform: scale(0.88) translateY(16px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
      }

      .mm-floating-popover__header {
        background: linear-gradient(135deg, #942727 0%, #761a1a 100%);
        padding: 16px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: #ffffff;
      }
      .mm-floating-popover__header h4 {
        margin: 0;
        font-size: 15.5px;
        font-weight: 700;
        letter-spacing: 0.2px;
        color: #ffffff;
      }
      .mm-floating-popover__close {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: #ffffff;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.2s ease, transform 0.2s ease;
        padding: 0;
      }
      .mm-floating-popover__close:hover {
        background: rgba(255, 255, 255, 0.35);
        transform: scale(1.06);
      }

      .mm-floating-popover__body {
        padding: 14px 14px 8px;
        background: #fdfcfb;
      }

      /* Item Cards */
      .mm-floating-item {
        display: flex;
        align-items: center;
        gap: 13px;
        padding: 12px 14px;
        margin-bottom: 10px;
        background: #ffffff;
        border: 1px solid #eee8df;
        border-radius: 14px;
        text-decoration: none;
        color: inherit;
        width: 100%;
        text-align: left;
        cursor: pointer;
        transition: all 0.2s ease;
      }
      .mm-floating-item:hover {
        border-color: #c9b48f;
        background: #faf8f5;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
      }

      .mm-floating-item__icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
      }
      .mm-floating-item__icon--hotline {
        background: linear-gradient(135deg, #942727 0%, #761a1a 100%);
        color: #ffffff;
        box-shadow: 0 4px 10px rgba(148, 39, 39, 0.25);
      }
      .mm-floating-item__icon--zalo {
        background: #0068FF;
        color: #ffffff;
        box-shadow: 0 4px 10px rgba(0, 104, 255, 0.25);
      }
      .mm-floating-item__icon--office {
        background: #ede8df;
        color: #942727;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
      }

      .mm-floating-item__content {
        display: flex;
        flex-direction: column;
        gap: 2px;
      }
      .mm-floating-item__title {
        font-size: 13.5px;
        font-weight: 700;
        color: #2b2b2b;
      }
      .mm-floating-item__subtitle {
        font-size: 13px;
        color: #777777;
        font-weight: 500;
      }
      .mm-floating-item__subtitle--highlight {
        color: #942727;
        font-weight: 800;
        font-size: 14.5px;
      }
      .mm-floating-item__subtitle--zalo {
        color: #0068FF;
        font-weight: 700;
      }

      /* Modal Office System */
      .mm-office-modal {
        position: fixed;
        inset: 0;
        z-index: 999999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        font-family: Manrope, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      }
      .mm-office-modal[hidden] {
        display: none !important;
      }
      .mm-office-modal * {
        box-sizing: border-box;
      }

      .mm-office-modal__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.65);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        animation: mm-fade-in 0.2s ease;
      }

      .mm-office-modal__dialog {
        position: relative;
        width: 100%;
        max-width: 860px;
        max-height: 90vh;
        background: #ffffff;
        border-radius: 24px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.3);
        animation: mm-modal-scale-up 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        z-index: 1;
      }

      @keyframes mm-fade-in {
        from { opacity: 0; }
        to { opacity: 1; }
      }
      @keyframes mm-modal-scale-up {
        from { opacity: 0; transform: scale(0.92) translateY(20px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
      }

      .mm-office-modal__header {
        background: linear-gradient(135deg, #942727 0%, #761a1a 100%);
        padding: 24px 30px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: #ffffff;
      }
      .mm-office-modal__badge-brand {
        display: inline-block;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 1.5px;
        color: #e5d9c5;
        margin-bottom: 4px;
      }
      .mm-office-modal__title {
        margin: 0;
        font-size: 22px;
        font-weight: 800;
        color: #ffffff;
        letter-spacing: -0.3px;
      }
      .mm-office-modal__close {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: #ffffff;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.2s, transform 0.2s;
        padding: 0;
      }
      .mm-office-modal__close:hover {
        background: rgba(255, 255, 255, 0.35);
        transform: scale(1.08);
      }

      .mm-office-modal__body {
        padding: 26px 30px;
        overflow-y: auto;
        background: #fbf9f6;
      }

      .mm-office-modal__grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
      }

      .mm-office-card {
        background: #ffffff;
        border: 1px solid #ede8df;
        border-radius: 18px;
        padding: 22px 24px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03);
        display: flex;
        flex-direction: column;
      }
      .mm-office-card__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
      }
      .mm-office-card__tag {
        background: #fceeee;
        color: #942727;
        font-size: 11.5px;
        font-weight: 800;
        padding: 4px 10px;
        border-radius: 999px;
        letter-spacing: 0.5px;
      }
      .mm-office-card__region {
        font-size: 12px;
        color: #888888;
        font-weight: 600;
      }
      .mm-office-card__name {
        margin: 0 0 18px;
        font-size: 16px;
        font-weight: 800;
        color: #1a1a1a;
        line-height: 1.35;
      }

      .mm-office-card__list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 14px;
      }
      .mm-office-card__item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        font-size: 13.5px;
        line-height: 1.45;
      }
      .mm-office-card__icon {
        font-size: 16px;
        flex-shrink: 0;
        margin-top: 1px;
      }
      .mm-office-card__item-text strong {
        display: block;
        font-size: 12px;
        color: #888888;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 2px;
      }
      .mm-office-card__item-text p {
        margin: 0;
        color: #333333;
      }
      .mm-office-card__map-link {
        display: inline-block;
        margin-top: 4px;
        color: #942727;
        font-size: 12.5px;
        font-weight: 700;
        text-decoration: none;
      }
      .mm-office-card__map-link:hover {
        text-decoration: underline;
      }
      .mm-office-card__phone-link,
      .mm-office-card__email-link {
        color: #942727;
        font-weight: 700;
        text-decoration: none;
      }
      .mm-office-card__phone-link:hover,
      .mm-office-card__email-link:hover {
        text-decoration: underline;
      }

      .mm-office-modal__footer {
        background: #ffffff;
        border-top: 1px solid #ebe5dc;
        padding: 18px 30px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
      }
      .mm-office-modal__footer-text strong {
        display: block;
        font-size: 14.5px;
        color: #1a1a1a;
        font-weight: 800;
      }
      .mm-office-modal__footer-text span {
        font-size: 13px;
        color: #666666;
      }
      .mm-office-modal__hotline-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: linear-gradient(135deg, #942727 0%, #761a1a 100%);
        color: #ffffff;
        padding: 11px 22px;
        border-radius: 999px;
        font-size: 14px;
        font-weight: 800;
        text-decoration: none;
        box-shadow: 0 4px 16px rgba(148, 39, 39, 0.35);
        transition: transform 0.2s, box-shadow 0.2s;
      }
      .mm-office-modal__hotline-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(148, 39, 39, 0.5);
        color: #ffffff;
      }

      /* Responsive */
      @media (max-width: 768px) {
        .mm-floating-widget {
          bottom: 20px;
          right: 20px;
        }
        .mm-floating-trigger {
          width: 54px;
          height: 54px;
        }
        .mm-floating-popover {
          width: calc(100vw - 40px);
          max-width: 320px;
          bottom: 68px;
        }
        .mm-office-modal__grid {
          grid-template-columns: 1fr;
          gap: 16px;
        }
        .mm-office-modal__header,
        .mm-office-modal__body,
        .mm-office-modal__footer {
          padding: 18px 20px;
        }
        .mm-office-modal__footer {
          flex-direction: column;
          align-items: stretch;
          text-align: center;
        }
        .mm-office-modal__hotline-btn {
          justify-content: center;
        }
      }
    </style>

    <!-- Script -->
    <script id="mm-floating-contact-script">
      (function() {
        var triggerBtn = document.getElementById('mm-floating-trigger-btn');
        var popover = document.getElementById('mm-floating-popover');
        var popoverClose = document.getElementById('mm-floating-popover-close');
        var phoneIcon = triggerBtn ? triggerBtn.querySelector('.mm-floating-trigger__icon--phone') : null;
        var closeIcon = triggerBtn ? triggerBtn.querySelector('.mm-floating-trigger__icon--close') : null;

        var officeModal = document.getElementById('mm-office-modal');
        var openOfficeBtn = document.getElementById('mm-open-office-modal-btn');
        var officeModalClose = document.getElementById('mm-office-modal-close');
        var officeModalBackdrop = document.getElementById('mm-office-modal-backdrop');

        function togglePopover(show) {
          if (!popover || !triggerBtn) return;
          var isHidden = typeof show === 'boolean' ? !show : !popover.hidden;
          popover.hidden = isHidden;
          if (isHidden) {
            triggerBtn.classList.remove('is-active');
            if (phoneIcon) phoneIcon.hidden = false;
            if (closeIcon) closeIcon.hidden = true;
          } else {
            triggerBtn.classList.add('is-active');
            if (phoneIcon) phoneIcon.hidden = true;
            if (closeIcon) closeIcon.hidden = false;
          }
        }

        if (triggerBtn) {
          triggerBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            togglePopover();
          });
        }

        if (popoverClose) {
          popoverClose.addEventListener('click', function(e) {
            e.stopPropagation();
            togglePopover(false);
          });
        }

        // Close popover on click outside
        document.addEventListener('click', function(e) {
          if (popover && !popover.hidden && !popover.contains(e.target) && !triggerBtn.contains(e.target)) {
            togglePopover(false);
          }
        });

        // Office modal handlers
        function openOfficeModal() {
          togglePopover(false);
          if (officeModal) {
            officeModal.hidden = false;
            officeModal.removeAttribute('inert');
            document.body.style.overflow = 'hidden';
          }
        }
        }

        function closeOfficeModal() {
          if (officeModal) {
            officeModal.hidden = true;
            officeModal.setAttribute('inert', '');
            document.body.style.overflow = '';
          }
        }
        }

        if (openOfficeBtn) {
          openOfficeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openOfficeModal();
          });
        }

        if (officeModalClose) {
          officeModalClose.addEventListener('click', closeOfficeModal);
        }

        if (officeModalBackdrop) {
          officeModalBackdrop.addEventListener('click', closeOfficeModal);
        }

        // Escape key closes modal & popover
        document.addEventListener('keydown', function(e) {
          if (e.key === 'Escape') {
            if (officeModal && !officeModal.hidden) {
              closeOfficeModal();
            } else if (popover && !popover.hidden) {
              togglePopover(false);
            }
          }
        });
      })();
    </script>
    <?php
    return ob_get_clean();
}
