<?php
/**
 * "Forgot your password" page — new page, same visual style as views/pages/auth.php (no TSX source).
 * Public page: only $page ('forgot') and $currentUser (always null here) are guaranteed.
 * Posts to action auth.forgot (see actions/auth.php); success shows a flash notice without revealing
 * whether the email is registered.
 */
$inputClass = 'w-full h-[42px] px-3.5 text-sm bg-surface border border-slate-300 rounded-xl text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 transition';
$labelClass = 'block text-sm font-medium text-slate-800 mb-2';
?>
<div class="min-h-screen bg-page grid lg:grid-cols-2">
  <!-- ---------- Form side ---------- -->
  <div class="flex flex-col px-5 sm:px-10">
    <div class="flex-1 flex items-center justify-center py-12">
      <div class="w-full max-w-[460px] animate-fade-in">
        <h1 class="text-4xl font-bold tracking-tight text-slate-900">Reset your password</h1>
        <p class="mt-2 text-slate-500">Enter your account email and we&#8217;ll send you a link to choose a new password.</p>

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
          <?= form_action('auth.forgot') ?>
          <div>
            <label class="<?= $labelClass ?>" for="forgot-email">Email <span class="text-red-500">*</span></label>
            <input id="forgot-email" name="email" type="email" autocomplete="email" value="<?= e(old('email')) ?>" placeholder="yourname@gmail.com" class="<?= $inputClass ?>">
          </div>

          <button type="submit" class="w-full h-[42px] inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-sm font-semibold text-white shadow-button transition">
            Send reset link
          </button>
        </form>

        <p class="mt-6 text-sm text-slate-500">
          Remembered your password?
          <a href="<?= e(url('login')) ?>" class="font-semibold text-brand-700 hover:text-brand-800 transition">Sign In</a>
        </p>
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
        <?= icon('Key', 'w-9 h-9 text-white') ?>
      </a>
      <h2 class="mt-7 text-3xl font-bold tracking-tight text-[#F2F6F8]">MailDart Pro</h2>
      <p class="mt-3 text-[#A1B2C4] leading-relaxed">
        We&#8217;ll email you a secure, time-limited link so you can choose a new password for your account.
      </p>
    </div>
  </div>
</div>
