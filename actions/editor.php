<?php
/**
 * Template Studio actions (was the live state updates in src/components/TemplateEditor.tsx and the
 * generate/apply calls in src/components/AiTemplateModal.tsx). Each handler receives the logged-in user row.
 */

require_once __DIR__ . '/../includes/ai.php';

return [
    /** Subject + discount code + HTML template, all saved together (was three live onChange handlers in the TSX). */
    'save' => function (array $user): void {
        $campaign = campaign_load((int) $user['id'], false);
        campaign_update($campaign['id'], [
            'subject' => mb_substr(input('subject'), 0, 998),
            'discountCode' => mb_substr(input('discountCode'), 0, 191),
            'htmlTemplate' => input_raw('htmlTemplate'),
        ]);
        redirect_back('editor');
    },

    /** Load a festival preset (was handleSelectPreset; the confirm() prompt is post_button's data-confirm). */
    'applyPreset' => function (array $user): void {
        $campaign = campaign_load((int) $user['id'], false);
        $presetId = input('presetId');
        $preset = null;
        foreach (preset_templates() as $p) {
            if ($p['id'] === $presetId) {
                $preset = $p;
                break;
            }
        }
        if (!$preset) {
            flash('error', 'Unknown template.');
            redirect_back('editor');
        }
        campaign_update($campaign['id'], [
            'festival' => $preset['festival'],
            'htmlTemplate' => $preset['html'],
            'subject' => $preset['subject'],
            'discountCode' => $preset['defaultDiscount'],
        ]);
        redirect_back('editor');
    },

    /**
     * Add a custom {{tag}} (was handleCreateCustomVariable). There is no cursor position on the server, so the
     * tag is inserted right before </body> (falling back to the end of the template), matching the same
     * "auto-insert before </body>" convention campaign_import_excel() already uses for Excel variables.
     */
    'addVariable' => function (array $user): void {
        $campaign = campaign_load((int) $user['id']);
        $key = strtolower(trim(input('varName')));
        $key = preg_replace('/[^\w-]/', '_', $key);
        $defaultVal = trim(input('varDefaultVal'));

        if ($key === '') {
            redirect('editor');
        }

        if ($defaultVal !== '' && $campaign['recipients']) {
            foreach ($campaign['recipients'] as $r) {
                $cd = is_array($r['customData'] ?? null) ? $r['customData'] : [];
                $existing = (string) ($cd[$key] ?? '');
                $cd[$key] = $existing !== '' ? $existing : $defaultVal;
                recipient_update($campaign['id'], $r['id'], ['customData' => $cd]);
            }
        }

        $tag = '{{' . $key . '}}';
        $template = $campaign['htmlTemplate'];
        $pos = strpos($template, '</body>');
        $template = $pos !== false ? substr_replace($template, "\n{$tag}\n</body>", $pos, 7) : rtrim($template) . "\n{$tag}\n";
        campaign_update($campaign['id'], ['htmlTemplate' => $template]);

        redirect('editor');
    },

    /**
     * Generate an AI template (was handleGenerate's fetch to /api/generate-template). The old TSX applied the
     * result and closed immediately; here the result is kept in the session so the modal can show a preview
     * before the user commits to it (see editor.applyAi and views/partials/ai_template_modal.php).
     */
    'generateAi' => function (array $user): void {
        keep_old_input();
        md_session_start();
        try {
            $_SESSION['ai_result'] = generate_template($_POST);
        } catch (Throwable $e) {
            $_SESSION['ai_result'] = ['success' => false, 'error' => $e->getMessage()];
        }
        redirect_back('editor');
    },

    /** Apply the AI result stored in the session to the campaign template, then close the modal. */
    'applyAi' => function (array $user): void {
        $campaign = campaign_load((int) $user['id'], false);
        md_session_start();
        $result = $_SESSION['ai_result'] ?? null;
        if ($result && !empty($result['success']) && !empty($result['html'])) {
            campaign_update($campaign['id'], ['htmlTemplate' => $result['html']]);
            flash('success', 'AI template applied to your editor.');
        } else {
            flash('error', 'Nothing to apply yet. Generate a template first.');
        }
        unset($_SESSION['ai_result']);
        redirect('editor');
    },
];
