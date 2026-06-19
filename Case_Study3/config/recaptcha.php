<?php
/**
 * Google reCAPTCHA v2 (checkbox "Tôi không phải người máy").
 * Tạo key tại: https://www.google.com/recaptcha/admin — chọn reCAPTCHA v2, KHÔNG chọn v3.
 * Trong Domains thêm: localhost và 127.0.0.1
 */
return [
    'site_key'     => '6Le6mfksAAAAAKM9vCZDu-28o08DFmO7RcH1Wka-',
    'secret_key'   => '6Le6mfksAAAAAJTCzkxtDb3dvvlTexiwgksOT5yU',
    'min_attempts' => 3,
    'type'         => 'v2_checkbox',
];
