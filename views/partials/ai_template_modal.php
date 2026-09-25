<?php
/**
 * AI Festival Email Designer (was src/components/AiTemplateModal.tsx), rendered by the layout on ?modal=ai.
 * Vars: $campaign, $closeUrl.
 *
 * The TSX generated the HTML and immediately applied + closed on success. A stateless PHP request can't do
 * "generate" and "apply" in one round trip the way a persistent React component can, so generating shows a
 * preview/apply step here (editor.generateAi stores the result in $_SESSION; editor.applyAi commits it and
 * clears the session; a "Discard & try again" link clears it without applying).
 */

require_once MD_ROOT . '/includes/ai.php';

md_session_start();
if (($_GET['discard'] ?? '') === '1') {
    unset($_SESSION['ai_result']);
}
$result = $_SESSION['ai_result'] ?? null;

$aiConfig = ai_config();

$engine = old('engine', $aiConfig['nvidiaConfigured'] ? 'nvidia' : 'auto');
$nvidiaModel = old('nvidiaModel', 'meta/llama-3.3-70b-instruct');

$nvidiaOptions = array_map(function ($m) {
    if (preg_match('/^(.*?)\s*\(([^)]+)\)\s*$/', $m['name'], $mm)) {
        return ['value' => $m['id'], 'label' => $mm[1], 'badge' => $mm[2], 'description' => $m['id']];
    }
    return ['value' => $m['id'], 'label' => $m['name'], 'description' => $m['id']];
}, $aiConfig['nvidiaModels']);

$showingResult = $result && !empty($result['success']);
$genError = (!$showingResult && $result && empty($result['success'])) ? (string) ($result['error'] ?? '') : '';
?>
<div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-[3px] animate-fade-in overflow-y-auto" data-modal-root>
  <div class="relative w-full max-w-3xl bg-surface border border-slate-200 rounded-2xl shadow-modal text-slate-700 p-6 md:p-7 my-6">

    <a href="<?= e($closeUrl) ?>" data-modal-close class="absolute top-4 right-4 p-2 text-slate-500 hover:text-slate-900 rounded-lg hover:bg-slate-100 transition">
      <?= icon('X', 'w-5 h-5') ?>
    </a>

    <div class="flex items-center gap-3 mb-5 border-b border-slate-200 pb-3">
      <div class="w-9 h-9 rounded-lg bg-brand-50 text-brand-700 flex items-center justify-center shrink-0">
        <?= icon('Sparkles', 'w-5 h-5') ?>
      </div>
      <div>
        <div class="flex items-center gap-2">
          <h2 class="text-lg font-semibold text-slate-900">AI Festival Email Designer</h2>
          <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-brand-50 text-brand-700 border border-brand-200">
            NVIDIA NIM &amp; Gemini
          </span>
        </div>
        <p class="text-xs text-slate-500">Generate responsive HTML email with personalized tags</p>
      </div>
    </div>

    <?php if ($showingResult): ?>

      <!-- Result: preview the generated HTML, then apply it or discard it -->
      <div class="space-y-4">
        <div class="p-3 rounded-xl bg-brand-50 border border-brand-200 text-xs text-brand-800 flex items-start gap-2">
          <?= icon('CheckCircle2', 'w-4 h-4 text-brand-600 shrink-0 mt-0.5') ?>
          <span>
            Generated with <strong><?= e($result['engine'] === 'nvidia' ? 'NVIDIA NIM' : 'Gemini') ?></strong><?= $result['model'] ? ' (' . e($result['model']) . ')' : '' ?>.
            Review the preview below, then apply it to your template.
          </span>
        </div>

        <div class="rounded-2xl overflow-hidden shadow-card border border-slate-200/80 bg-surface">
          <iframe title="AI Generated Preview" srcdoc="<?= e($result['html']) ?>" class="w-full h-[420px] border-0" sandbox="allow-same-origin"></iframe>
        </div>

        <div class="pt-3 border-t border-slate-200 flex items-center justify-between">
          <a href="<?= e(url('editor', ['modal' => 'ai', 'discard' => 1])) ?>" class="px-3.5 py-1.5 text-xs text-slate-500 hover:text-slate-900">
            Discard &amp; try again
          </a>
          <div class="flex items-center gap-2">
            <a href="<?= e($closeUrl) ?>" class="px-3.5 py-1.5 text-xs text-slate-500 hover:text-slate-900">Close</a>
            <form method="post" action="./">
              <?= form_action('editor.applyAi', url('editor')) ?>
              <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-500 text-white font-semibold text-xs rounded-lg flex items-center gap-1.5 transition shadow-button">
                <?= icon('CheckCircle2', 'w-4 h-4') ?>
                Apply to Template
              </button>
            </form>
          </div>
        </div>
      </div>

    <?php else: ?>

      <form id="ai-generate-form" method="post" action="./" class="space-y-4">
        <?= form_action('editor.generateAi', url('editor', ['modal' => 'ai'])) ?>

        <!-- AI Engine & Provider Selector -->
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 space-y-2.5">
          <div class="flex items-center justify-between">
            <label class="text-xs font-semibold text-slate-700 flex items-center gap-1.5">
              <?= icon('Cpu', 'w-3.5 h-3.5 text-brand-700') ?>
              Select AI Engine
            </label>
            <span class="text-[10px] text-slate-500">
              <?= $aiConfig['nvidiaConfigured'] ? '🟢 NVIDIA Key Detected' : '🟣 Gemini 3.8 Flash Ready' ?>
            </span>
          </div>

          <div class="grid grid-cols-3 gap-2">
            <label class="block cursor-pointer">
              <input type="radio" name="engine" id="engine-nvidia" value="nvidia" class="peer sr-only" <?= checked($engine === 'nvidia') ?>>
              <span class="peer-checked:bg-brand-50 peer-checked:border-brand-500 peer-checked:text-brand-700 peer-checked:shadow-sm py-2 px-2.5 rounded-xl border text-xs font-medium flex flex-col items-center gap-1 transition bg-surface border-slate-200 text-slate-500 hover:text-slate-900">
                <span class="flex items-center gap-1">
                  <span class="w-2 h-2 rounded-full bg-brand-500 animate-pulse"></span>
                  <span class="font-bold">NVIDIA NIM</span>
                </span>
                <span class="text-[9px] text-slate-500 font-mono">Meta Llama / Nemotron</span>
              </span>
            </label>

            <label class="block cursor-pointer">
              <input type="radio" name="engine" id="engine-gemini" value="gemini" class="peer sr-only" <?= checked($engine === 'gemini') ?>>
              <span class="peer-checked:bg-purple-50 peer-checked:border-purple-500 peer-checked:text-purple-700 peer-checked:shadow-sm py-2 px-2.5 rounded-xl border text-xs font-medium flex flex-col items-center gap-1 transition bg-surface border-slate-200 text-slate-500 hover:text-slate-900">
                <span class="flex items-center gap-1">
                  <?= icon('Sparkles', 'w-3 h-3 text-purple-600') ?>
                  <span class="font-bold">Gemini AI</span>
                </span>
                <span class="text-[9px] text-slate-500 font-mono">Gemini 3.8 Flash</span>
              </span>
            </label>

            <label class="block cursor-pointer">
              <input type="radio" name="engine" id="engine-auto" value="auto" class="peer sr-only" <?= checked($engine === 'auto') ?>>
              <span class="peer-checked:bg-blue-50 peer-checked:border-blue-500 peer-checked:text-blue-700 peer-checked:shadow-sm py-2 px-2.5 rounded-xl border text-xs font-medium flex flex-col items-center gap-1 transition bg-surface border-slate-200 text-slate-500 hover:text-slate-900">
                <span class="flex items-center gap-1">
                  <?= icon('Zap', 'w-3 h-3 text-blue-600') ?>
                  <span class="font-bold">Auto (Smart)</span>
                </span>
                <span class="text-[9px] text-slate-500 font-mono">Best Available</span>
              </span>
            </label>
          </div>

          <!-- NVIDIA Model Dropdown (shown for NVIDIA or Auto; assets/js/editor.js hides it for Gemini, purely cosmetic) -->
          <div class="pt-2 border-t border-slate-200 flex items-center justify-between gap-2 text-xs" data-show-when-engine="nvidia,auto" <?= in_array($engine, ['nvidia', 'auto'], true) ? '' : 'hidden' ?>>
            <span class="text-slate-500 text-[11px] shrink-0">NVIDIA Model:</span>
            <?php partial('select_menu', [
                'id' => 'ai-nvidia-model',
                'name' => 'nvidiaModel',
                'value' => $nvidiaModel,
                'options' => $nvidiaOptions,
                'monoDescription' => true,
                'className' => 'flex-1',
                'ariaLabel' => 'NVIDIA model',
                'mode' => 'radio',
            ]); ?>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs text-slate-500 mb-1">Festival / Occasion</label>
            <input
              type="text"
              name="festival"
              value="<?= e(old('festival', $campaign['festival'] !== '' ? $campaign['festival'] : 'Diwali')) ?>"
              placeholder="e.g. Diwali, Eid, Christmas"
              required
              class="w-full px-3 py-2 text-xs bg-surface border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10"
            >
          </div>
          <div>
            <label class="block text-xs text-slate-500 mb-1">Company / Brand Name</label>
            <input
              type="text"
              name="companyName"
              value="<?= e(old('companyName', $campaign['companyName'] !== '' ? $campaign['companyName'] : 'My Company')) ?>"
              placeholder="e.g. Nexus Corp"
              required
              class="w-full px-3 py-2 text-xs bg-surface border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10"
            >
          </div>
        </div>

        <div>
          <label class="block text-xs text-slate-500 mb-1">Festival Offer / Discount Details</label>
          <input
            type="text"
            name="campaignOffer"
            value="<?= e(old('campaignOffer', 'Special Festive 40% OFF with code FESTIVE40 + Free Gift Hamper')) ?>"
            placeholder="e.g. Flat 50% OFF on all items + Free Sweets Box with code DIWALI2026"
            required
            class="w-full px-3 py-2 text-xs bg-surface border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10"
          >
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs text-slate-500 mb-1">Audience Type</label>
            <input
              type="text"
              name="targetAudience"
              value="<?= e(old('targetAudience', 'Premium Customers & Loyal Clients')) ?>"
              placeholder="e.g. B2B Partners, VIP Shoppers"
              class="w-full px-3 py-2 text-xs bg-surface border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10"
            >
          </div>
          <div>
            <label class="block text-xs text-slate-500 mb-1">Color Style / Vibes</label>
            <input
              type="text"
              name="colorPalette"
              value="<?= e(old('colorPalette', 'Royal Gold, Deep Amber and Midnight Blue')) ?>"
              placeholder="e.g. Royal Gold, Emerald Green"
              class="w-full px-3 py-2 text-xs bg-surface border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10"
            >
          </div>
        </div>

        <div>
          <label class="block text-xs text-slate-500 mb-1">Tone &amp; Voice</label>
          <input
            type="text"
            name="tone"
            value="<?= e(old('tone', 'Celebratory, warm, prestigious and joyous')) ?>"
            placeholder="e.g. Warm, celebratory, festive, prestigious"
            class="w-full px-3 py-2 text-xs bg-surface border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10"
          >
        </div>

        <?php if ($genError !== ''): ?>
          <div class="p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700 flex flex-col gap-2">
            <div class="flex items-start gap-2">
              <?= icon('AlertCircle', 'w-4 h-4 text-red-600 shrink-0 mt-0.5') ?>
              <span class="break-words font-mono text-[11px]"><?= e($genError) ?></span>
            </div>
            <?php if (str_contains($genError, 'NVIDIA_API_KEY')): ?>
              <div class="flex items-center gap-2 mt-1">
                <span class="text-[11px] text-slate-600">Switch to Gemini 3.8 Flash:</span>
                <label for="engine-gemini" class="cursor-pointer px-2.5 py-1 bg-brand-600 hover:bg-brand-500 text-white rounded-lg text-xs font-semibold transition shadow-button">
                  Use Gemini 3.8 Flash
                </label>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <div class="pt-3 border-t border-slate-200 flex items-center justify-between">
          <span class="text-[11px] text-slate-500 flex items-center gap-1">
            <?= icon('Info', 'w-3.5 h-3.5 text-slate-400') ?>
            Includes {{name}}, {{company}}, {{discount}} tags
          </span>

          <div class="flex items-center gap-2">
            <a href="<?= e($closeUrl) ?>" class="px-3.5 py-1.5 text-xs text-slate-500 hover:text-slate-900">
              Cancel
            </a>
            <button type="submit" data-loading-label
                    class="px-4 py-2 bg-brand-600 hover:bg-brand-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold text-xs rounded-lg flex items-center gap-1.5 transition shadow-button">
              <span data-idle-label class="inline-flex items-center gap-1.5">
                <?= icon('Wand2', 'w-3.5 h-3.5') ?>
                Generate Festive HTML
              </span>
              <span data-loading-content class="hidden inline-flex items-center gap-1.5">
                <?= icon('Loader2', 'w-3.5 h-3.5 animate-spin') ?>
                <span data-loading-text>Generating with AI...</span>
              </span>
            </button>
          </div>
        </div>
      </form>

    <?php endif; ?>

  </div>
</div>
