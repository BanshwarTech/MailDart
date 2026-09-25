<?php
/**
 * AI template generation (was the AI-related endpoints in server.ts: GET /api/ai-config, POST /api/generate-template).
 * Pure PHP + curl, no SDKs, no third-party HTTP client. Keys via env_secret('GEMINI_API_KEY') / env_secret('NVIDIA_API_KEY').
 */

declare(strict_types=1);

/** Selectable NVIDIA NIM models — same ids/labels as the old GET /api/ai-config response. */
function nvidia_models(): array
{
    return [
        ['id' => 'meta/llama-3.3-70b-instruct', 'name' => 'Meta Llama 3.3 70B (Recommended)'],
        ['id' => 'nvidia/llama-3.1-nemotron-70b-instruct', 'name' => 'NVIDIA Nemotron 70B'],
        ['id' => 'deepseek-ai/deepseek-r1', 'name' => 'DeepSeek R1 (Reasoning)'],
        ['id' => 'mistralai/mistral-large-2-instruct', 'name' => 'Mistral Large 2'],
    ];
}

/** Same JSON shape as the old GET /api/ai-config. */
function ai_config(): array
{
    $hasGemini = env_secret('GEMINI_API_KEY') !== null;
    $hasNvidia = env_secret('NVIDIA_API_KEY') !== null;
    return [
        'geminiConfigured' => $hasGemini,
        'nvidiaConfigured' => $hasNvidia,
        'recommendedEngine' => $hasNvidia ? 'nvidia' : 'gemini',
        'nvidiaModels' => nvidia_models(),
    ];
}

/**
 * POST one JSON request with curl (long timeout: AI generation can take a while).
 * Returns [httpCode, decodedBody(array|null), rawBody, curlErrorMessage].
 */
function ai_http_post_json(string $url, array $headers, array $body, int $timeoutSeconds = 120): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => $timeoutSeconds,
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $raw = curl_exec($ch);
    $curlErr = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = null;
    if (is_string($raw) && $raw !== '') {
        $tmp = json_decode($raw, true);
        if (is_array($tmp)) {
            $decoded = $tmp;
        }
    }
    return [$code, $decoded, (string) $raw, $curlErr];
}

/**
 * POST https://integrate.api.nvidia.com/v1/chat/completions — same system message and sampling
 * parameters (temperature 0.6, top_p 0.9, max_tokens 4096) as the old server.ts generateWithNvidia().
 */
function generate_with_nvidia(string $prompt, string $model = 'meta/llama-3.3-70b-instruct'): string
{
    set_time_limit(150);
    $apiKey = env_secret('NVIDIA_API_KEY');
    if (!$apiKey) {
        throw new RuntimeException('NVIDIA_API_KEY is not configured in environment. Please add NVIDIA_API_KEY to your Secrets / .env or choose Google Gemini.');
    }

    [$code, $decoded, $raw, $curlErr] = ai_http_post_json(
        'https://integrate.api.nvidia.com/v1/chat/completions',
        ['Content-Type: application/json', 'Authorization: Bearer ' . trim($apiKey)],
        [
            'model' => $model !== '' ? $model : 'meta/llama-3.3-70b-instruct',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a world-class email marketing HTML designer. You create 100% production-ready, bulletproof, responsive HTML emails with inline CSS and table layout. Output ONLY the raw HTML code without markdown code fence blocks, explanations, or commentary.',
                ],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.6,
            'top_p' => 0.9,
            'max_tokens' => 4096,
        ],
        120
    );

    if ($curlErr !== '') {
        throw new RuntimeException("NVIDIA API Error: $curlErr");
    }
    if ($code < 200 || $code >= 300) {
        $errorMsg = $raw;
        if ($decoded !== null) {
            $errorMsg = $decoded['error']['message'] ?? ($decoded['message'] ?? $raw);
        }
        throw new RuntimeException("NVIDIA API Error ($code): $errorMsg");
    }

    return (string) ($decoded['choices'][0]['message']['content'] ?? '');
}

/** Gemini REST generateContent, with the same candidate-model fallback list as the old server.ts generateWithGemini(). */
function generate_with_gemini(string $prompt): string
{
    set_time_limit(150);
    $apiKey = env_secret('GEMINI_API_KEY');
    if (!$apiKey) {
        throw new RuntimeException('GEMINI_API_KEY is not configured in server environment.');
    }

    $candidateModels = ['gemini-3.8-flash', 'gemini-flash-latest', 'gemini-3.1-flash-lite'];
    $lastError = null;

    foreach ($candidateModels as $modelName) {
        [$code, $decoded, $raw, $curlErr] = ai_http_post_json(
            "https://generativelanguage.googleapis.com/v1beta/models/$modelName:generateContent",
            ['Content-Type: application/json', 'x-goog-api-key: ' . trim($apiKey)],
            ['contents' => [['parts' => [['text' => $prompt]]]]],
            120
        );

        if ($curlErr !== '') {
            $lastError = new RuntimeException($curlErr);
            continue;
        }
        if ($code < 200 || $code >= 300) {
            $msg = $decoded['error']['message'] ?? $raw;
            $lastError = new RuntimeException((string) $msg);
            continue;
        }

        $text = '';
        foreach (($decoded['candidates'][0]['content']['parts'] ?? []) as $part) {
            if (isset($part['text'])) {
                $text .= $part['text'];
            }
        }
        if ($text !== '') {
            return $text;
        }
        $lastError = new RuntimeException("Model $modelName returned no text.");
    }

    throw $lastError ?? new RuntimeException('Failed to generate template with Gemini AI models.');
}

/**
 * Same prompt text, engine selection ('nvidia'|'gemini'|'auto') and markdown-fence stripping as the old
 * POST /api/generate-template. $body keys: festival, companyName, campaignOffer, targetAudience, tone,
 * colorPalette, engine, nvidiaModel. Returns ['success' => true, 'html' => ..., 'engine' => ..., 'model' => ...].
 * Throws on failure — callers turn the exception message into the old {success:false, error} shape.
 */
function generate_template(array $body): array
{
    $festival = trim((string) ($body['festival'] ?? ''));
    $companyName = trim((string) ($body['companyName'] ?? ''));
    $campaignOffer = trim((string) ($body['campaignOffer'] ?? ''));
    $targetAudience = trim((string) ($body['targetAudience'] ?? ''));
    $tone = trim((string) ($body['tone'] ?? ''));
    $colorPalette = trim((string) ($body['colorPalette'] ?? ''));
    $engine = (string) ($body['engine'] ?? 'auto');
    $nvidiaModel = (string) ($body['nvidiaModel'] ?? 'meta/llama-3.3-70b-instruct');

    $prompt = "You are an expert email marketing designer and HTML email engineer.\n"
        . "Create a responsive, production-ready, beautifully styled HTML email template for a festival campaign with the following details:\n"
        . '- Festival/Occasion: ' . ($festival !== '' ? $festival : 'Festival Celebration & Special Sale') . "\n"
        . '- Company/Brand Name: ' . ($companyName !== '' ? $companyName : 'Our Company') . "\n"
        . '- Special Offer / Discount / Message: ' . ($campaignOffer !== '' ? $campaignOffer : 'Special festive flat 40% OFF with code FESTIVE40') . "\n"
        . '- Target Audience: ' . ($targetAudience !== '' ? $targetAudience : 'Valued Customers') . "\n"
        . '- Tone: ' . ($tone !== '' ? $tone : 'Festive, warm, celebratory, and action-driving') . "\n"
        . '- Preferred Color Theme: ' . ($colorPalette !== '' ? $colorPalette : 'Festive vibrant colors matching the festival theme') . "\n"
        . "\n"
        . "CRITICAL REQUIREMENTS:\n"
        . "1. Include standard placeholders that can be personalized per recipient:\n"
        . "   - {{name}} (Recipient name)\n"
        . "   - {{email}} (Recipient email)\n"
        . "   - {{company}} (Company name)\n"
        . "   - {{discount}} (Offer code or discount percentage)\n"
        . "   - {{festival}} (Festival name)\n"
        . "2. The HTML must be 100% email-client friendly (tables, inline CSS styles, max-width 600px, responsive, centered).\n"
        . "3. Include an attractive header with festive greetings and festive emoji/decorations, a hero banner section with the big offer, clear typography, warm greeting body text, an eye-catching CTA button (e.g. \"Claim Festive Offer\", \"Shop Festival Deals\"), a festive coupon box, and a professional footer with social links & unsubscribe link.\n"
        . '4. Output MUST be ONLY the raw HTML code. Do NOT wrap in markdown code blocks like ```html or ```. Start directly with <!DOCTYPE html> or <html> and end with </html>.';

    $hasNvidia = env_secret('NVIDIA_API_KEY') !== null;

    if ($engine === 'nvidia' || ($engine === 'auto' && $hasNvidia)) {
        $usedEngine = 'nvidia';
        $usedModel = $nvidiaModel !== '' ? $nvidiaModel : 'meta/llama-3.3-70b-instruct';
        $html = generate_with_nvidia($prompt, $usedModel);
    } else {
        $usedEngine = 'gemini';
        $usedModel = 'gemini-3.8-flash';
        $html = generate_with_gemini($prompt);
    }

    $html = preg_replace('/^```html\s*/i', '', $html);
    $html = preg_replace('/^```\s*/i', '', (string) $html);
    $html = preg_replace('/\s*```$/i', '', (string) $html);
    $html = trim((string) $html);

    return ['success' => true, 'html' => $html, 'engine' => $usedEngine, 'model' => $usedModel];
}
