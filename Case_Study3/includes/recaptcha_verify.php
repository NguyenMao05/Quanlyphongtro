<?php

/**
 * Xác minh reCAPTCHA v2 checkbox với Google siteverify API.
 * @return array{ok: bool, error?: string, codes?: array}
 */
function verify_recaptcha_response(?string $response, string $secret_key): array
{
    if ($secret_key === '' || $secret_key === 'YOUR_RECAPTCHA_SECRET_KEY') {
        return ['ok' => false, 'error' => 'secret_not_configured'];
    }

    if ($response === null || trim($response) === '') {
        return ['ok' => false, 'error' => 'missing_response'];
    }

    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $post = http_build_query([
        'secret'   => $secret_key,
        'response' => $response,
    ]);

    $raw = false;
    $transport = 'none';

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $post,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $raw = curl_exec($ch);
        if ($raw === false) {
            $curlErr = curl_error($ch);
            curl_close($ch);
            return ['ok' => false, 'error' => 'verify_request_failed', 'detail' => $curlErr];
        }
        curl_close($ch);
        $transport = 'curl';
    } else {
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => $post,
                'timeout' => 15,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        $transport = 'file_get_contents';
    }

    if ($raw === false) {
        return ['ok' => false, 'error' => 'verify_request_failed'];
    }

    $json = json_decode($raw, true);
    if (!is_array($json) || empty($json['success'])) {
        return [
            'ok'    => false,
            'error' => 'verify_failed',
            'codes' => $json['error-codes'] ?? [],
            'transport' => $transport,
        ];
    }

    return ['ok' => true, 'transport' => $transport];
}

/**
 * Thông báo lỗi tiếng Việt từ mã Google.
 */
function recaptcha_error_message(array $verify): string
{
    $code = $verify['error'] ?? '';

    if ($code === 'missing_response') {
        return 'CAPTCHA chưa được xác nhận. Hãy tick ô "Tôi không phải người máy" trước khi đăng nhập.';
    }

    if ($code === 'verify_request_failed') {
        return 'Không kết nối được máy chủ Google để kiểm tra CAPTCHA. Kiểm tra mạng hoặc OpenSSL trên XAMPP.';
    }

    if ($code === 'secret_not_configured') {
        return 'Chưa cấu hình secret key reCAPTCHA trong config/recaptcha.php.';
    }

    $codes = $verify['codes'] ?? [];
    if (in_array('invalid-input-secret', $codes, true) || in_array('invalid-keys', $codes, true)) {
        return 'Secret key reCAPTCHA không hợp lệ. Kiểm tra lại config/recaptcha.php (loại v2 checkbox).';
    }
    if (in_array('invalid-input-response', $codes, true) || in_array('timeout-or-duplicate', $codes, true)) {
        return 'CAPTCHA đã hết hạn. Vui lòng tick lại ô xác nhận và đăng nhập ngay.';
    }
    if (in_array('bad-request', $codes, true)) {
        return 'Yêu cầu CAPTCHA không hợp lệ. Đảm bảo bạn dùng reCAPTCHA v2 (checkbox), không phải v3.';
    }

    return 'Xác minh CAPTCHA thất bại. Kiểm tra domain (localhost / 127.0.0.1) trong Google reCAPTCHA Admin.';
}
