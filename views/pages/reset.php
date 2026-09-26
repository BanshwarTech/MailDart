<?php
/**
 * "Choose a new password" page — new page, same visual style as views/pages/auth.php (no TSX source).
 * Public page: only $page ('reset') and $currentUser (always null here) are guaranteed.
 * Reads $_GET['token']; shows an invalid/expired state when auth_find_reset() fails, else a new-password form
 * (with the same strength meter / rules text / show-hide as Sign Up, via assets/js/auth.js) posting to auth.reset.
 */
$token = (string) ($_GET['token'] ?? '');
$resetRow = $token !== '' ? auth_find_reset($token) : null;
$pageScripts[] = 'js/auth.js';

$inputClass = 'w-full h-[42px] px-3.5 text-sm bg-surface border border-slate-300 rounded-xl text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 transition';
$labelClass = 'block text-sm font-medium text-slate-800 mb-2';
?>
<div class="min-h-screen bg-page grid lg:grid-cols-2">
  <!-- Tailwind class safelist for JS-driven states (assets/js/auth.js): these never appear literally above -->
  <span class="hidden bg-red-500 bg-amber-500 bg-[#A1B2C4] border-red-300 focus:border-red-400 focus:ring-red-500/10" aria-hidden="true"></span>

  <!-- ---------- Form side ---------- -->
  <div class="flex flex-col px-5 sm:px-10">
    <div class="flex-1 flex items-center justify-center py-12">
      <div class="w-full max-w-[460px] animate-fade-in">
        <?php if (!$resetRow): ?>
          <h1 class="text-4xl font-bold tracking-tight text-slate-900">Link invalid or expired</h1>
          <p class="mt-2 text-slate-500">This password reset link is no longer valid. Please request a new one.</p>

          <div class="mt-7 flex items-start gap-2.5 rounded-xl bg-red-50 ring-1 ring-inset ring-red-200 px-4 py-3 text-sm text-red-700">
            <?= icon('AlertCircle', 'w-4 h-4 mt-0.5 shrink-0') ?>
            <span>This reset link is invalid or has expired. Please request a new one.</span>
          </div>

          <a href="<?= e(url('forgot')) ?>" class="mt-7 w-full h-[42px] inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-sm font-semibold text-white shadow-button transition">
            Request a new link
          </a>

          <p class="mt-6 text-sm text-slate-500">
            Remembered your password?
            <a href="<?= e(url('login')) ?>" class="font-semibold text-brand-700 hover:text-brand-800 transition">Sign In</a>
          </p>
        <?php else: ?>
          <h1 class="text-4xl font-bold tracking-tight text-slate-900">Choose a new password</h1>
          <p class="mt-2 text-slate-500">Choose a strong new password for <strong class="text-slate-800 font-medium"><?= e($resetRow['email']) ?></strong>.</p>

          <?php foreach (take_flashes() as $f): ?>
            <?php if ($f['type'] === 'error'): ?>
              <div class="mt-7 flex items-start gap-2.5 rounded-xl bg-red-50 ring-1 ring-inset ring-red-200 px-4 py-3 text-sm text-red-700">
                <?= icon('AlertCircle', 'w-4 h-4 mt-0.5 shrink-0') ?>
                <span><?= e($f['message']) ?></span>
              </div>
            <?php else: ?>
              <div class="mt-7 flex items-start gap-2.5 rounded-xl bg-sky-50 ring-1 ring-inset ring-sky-200 px-4 py-3 text-sm text-sky-700">
                <?= icon('CheckCircle2', 'w-4 h-4 mt-0.5 shrink-0') ?>
                <span><?= e($f['message']) ?></span>
              </div>
            <?php endif; ?>
          <?php endforeach; ?>

          <form method="post" action="./" class="mt-7 space-y-5" novalidate>
            <?= form_action('auth.reset') ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">

            <div>
              <div class="flex items-center justify-between mb-2">
                <label class="block text-sm font-medium text-slate-800" for="reset-password">New Password <span class="text-red-500">*</span></label>
                <button type="button" data-generate-btn class="inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:text-brand-800 transition">
                  <span data-generate-idle class="inline-flex items-center gap-1">
                    <?= icon('Wand2', 'w-3.5 h-3.5') ?>
                    Generate Password
                  </span>
                  <span data-generate-loading class="inline-flex items-center gap-1" hidden>
                    <?= icon('Loader2', 'w-3.5 h-3.5 animate-spin') ?>
                    Generating...
                  </span>
                  <span data-generate-done class="inline-flex items-center gap-1" hidden>
                    <?= icon('Check', 'w-3.5 h-3.5') ?>
                    Generated &amp; copied
                  </span>
                </button>
              </div>
              <div class="relative">
                <input id="reset-password" name="password" type="password" autocomplete="new-password" placeholder="Enter your new password" class="<?= $inputClass ?> pr-12" data-password-input data-strength-input>
                <button type="button" data-password-toggle class="absolute right-1.5 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-slate-400 hover:text-slate-800 hover:bg-slate-100 transition" aria-label="Show password" title="Show password">
                  <span data-icon-hidden><?= icon('Eye', 'w-5 h-5') ?></span>
                  <span data-icon-visible class="hidden"><?= icon('EyeOff', 'w-5 h-5') ?></span>
                </button>
              </div>
              <div class="mt-2.5 hidden items-center gap-3" data-strength-group>
                <div class="flex-1 grid grid-cols-3 gap-1.5">
                  <span class="h-1.5 rounded-full bg-slate-200" data-strength-bar="1"></span>
                  <span class="h-1.5 rounded-full bg-slate-200" data-strength-bar="2"></span>
                  <span class="h-1.5 rounded-full bg-slate-200" data-strength-bar="3"></span>
                </div>
                <span class="text-xs font-semibold text-slate-600" data-strength-label></span>
              </div>
              <p class="mt-2 text-xs text-slate-400">Must be at least 8 characters, with uppercase, lowercase, a number and a symbol.</p>
            </div>

            <div>
              <label class="<?= $labelClass ?>" for="reset-confirm">Confirm Password <span class="text-red-500">*</span></label>
              <input id="reset-confirm" name="confirm" type="password" autocomplete="new-password" placeholder="Confirm your new password" class="<?= $inputClass ?>" data-password-input data-confirm-input>
              <p class="mt-1.5 text-xs text-red-600 hidden" data-confirm-error>Passwords do not match.</p>
            </div>

            <button type="submit" data-submit-button class="w-full h-[42px] inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-sm font-semibold text-white shadow-button transition disabled:opacity-60">
              <span data-loading-spinner class="hidden w-4 h-4 rounded-full border-2 border-white/40 border-t-white animate-spin"></span>
              Save new password
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ---------- Brand side ---------- -->
  <div class="hidden lg:flex lg:sticky lg:top-0 lg:h-screen relative overflow-hidden items-center justify-center bg-gradient-to-br from-[#112A46] via-[#0E2440] to-[#1B3B5A] border-l border-slate-200/80">
    <div class="absolute inset-0 [background-image:linear-gradient(rgb(122_140_166/0.14)_1px,transparent_1px),linear-gradient(90deg,rgb(122_140_166/0.14)_1px,transparent_1px)] [background-size:64px_64px] [mask-image:radial-gradient(ellipse_at_center,transparent_22%,black_75%)]" aria-hidden="true"></div>
    <span class="absolute top-[64px] right-[128px] w-16 h-16 bg-[#34506D]/35" aria-hidden="true"></span>
    <span class="absolute top-[128px] right-[192px] w-16 h-16 bg-[#34506D]/35" aria-hidden="true"></span>
    <span class="absolute bottom-[128px] left-[128px] w-16 h-16 bg-[#34506D]/35" aria-hidden="true"></span>
    <span class="absolute bottom-[64px] left-[64px] w-16 h-16 bg-[#34506D]/35" aria-hidden="true"></span>
    <span class="absolute top-[256px] left-[64px] w-16 h-16 bg-[#34506D]/35" aria-hidden="true"></span>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[520px] h-[520px] rounded-full bg-[radial-gradient(closest-side,rgb(85_115_146/0.35),transparent)]" aria-hidden="true"></div>

    <div class="relative max-w-md px-8 text-center">
      <a href="<?= e(url('home')) ?>" class="mx-auto w-20 h-20 rounded-full bg-gradient-to-br from-[#6A87A6] to-[#34506D] flex items-center justify-center shadow-[0_20px_50px_-15px_rgb(0_0_0/0.6)] ring-8 ring-[#557392]/15" aria-label="Back to MailDart home">
        <?= icon('ShieldCheck', 'w-9 h-9 text-white') ?>
      </a>
      <h2 class="mt-7 text-3xl font-bold tracking-tight text-[#F2F6F8]">MailDart Pro</h2>
      <p class="mt-3 text-[#A1B2C4] leading-relaxed">
        Choose a strong new password to keep your campaigns and SMTP settings secure.
      </p>
    </div>
  </div>
</div>
