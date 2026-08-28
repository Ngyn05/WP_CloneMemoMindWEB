<?php get_header(); ?>
<main style="min-height:70vh;padding:clamp(5rem,12vw,10rem) 2rem;text-align:center;font-family:Manrope,Arial,sans-serif">
    <p style="margin:0 0 1rem;color:#777;font-size:1rem">Lỗi 404</p>
    <h1 style="margin:0 0 1rem;font-size:clamp(2rem,5vw,4rem)">Không tìm thấy trang</h1>
    <p style="margin:0 auto 2rem;max-width:36rem;color:#555;font-size:1.1rem;line-height:1.7">Đường dẫn có thể đã thay đổi hoặc nội dung không còn tồn tại.</p>
    <a href="<?php echo esc_url(home_url('/')); ?>" style="display:inline-flex;min-height:48px;align-items:center;padding:0 1.5rem;border-radius:999px;background:#111;color:#fff;text-decoration:none">Về trang chủ</a>
</main><?php get_footer(); ?>
