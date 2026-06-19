-- Bật/tắt hiển thị tin đăng (chủ trọ) — tách khỏi trạng thái duyệt admin
ALTER TABLE motel
    ADD COLUMN IF NOT EXISTS is_visible TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=hiển thị công khai'
    AFTER approve;
