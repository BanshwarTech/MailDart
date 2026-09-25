<?php
/**
 * Sign in / Sign up page (was src/components/AuthPage.tsx), rendered for both $page === 'login' and 'register'.
 * Public page: only $page and $currentUser (always null here — index.php redirects logged-in users away) are guaranteed.
 * Posts to actions auth.login / auth.register (see actions/auth.php). The Google button + "Or" divider appear when
 * GOOGLE_CLIENT_ID/SECRET are configured (google-auth.php); reCAPTCHA v3 protects the form when its keys are set. Password generator / show-hide / strength meter / rules text and the
 * live "passwords match" check are tiny vanilla JS in assets/js/auth.js (the server re-validates password rules anyway).
 */
$isLogin = $page === 'login';
$next = (string) ($_GET['next'] ?? '');
$nextParams = $next !== '' ? ['next' => $next] : [];
$pageScripts[] = 'js/auth.js';
$googleOn = google_enabled();
if (recaptcha_enabled()) {
    array_push($pageScripts, ...recaptcha_scripts());
    $pageScripts[] = 'js/recaptcha.js';
}

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
        <h1 class="text-4xl font-bold tracking-tight text-slate-900"><?= $isLogin ? 'Sign In' : 'Sign Up' ?></h1>
        <p class="mt-2 text-slate-500">
          <?= $isLogin ? 'Enter your email and password to sign in!' : 'Enter your details to create your MailDart account!' ?>
        </p>

        <?php if ($googleOn): ?>
          <a
            href="<?= e(url('google-auth', ['from' => $isLogin ? 'login' : 'register'] + $nextParams)) ?>"
            class="mt-7 w-full h-[42px] inline-flex items-center justify-center gap-3 rounded-xl bg-slate-100 hover:bg-slate-200/80 ring-1 ring-inset ring-slate-300 text-sm font-medium text-slate-900 transition disabled:opacity-60"
          >
            <svg viewBox="0 0 48 48" class="w-5 h-5" aria-hidden="true">
              <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z" />
              <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z" />
              <path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-7.9l-6.5 5C9.5 39.6 16.2 44 24 44z" />
              <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.1-4.1 5.6l6.2 5.2C36.9 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z" />
            </svg>
            <?= $isLogin ? 'Sign in with Google' : 'Sign up with Google' ?>
          </a>

          <div class="my-6 flex items-center gap-4">
            <div class="h-px flex-1 bg-slate-200"></div>
            <span class="text-xs text-slate-400">Or</span>
            <div class="h-px flex-1 bg-slate-200"></div>
          </div>
        <?php endif; ?>

        <?php foreach (take_flashes() as $f): ?>
          <?php if ($f['type'] === 'error'): ?>
            <div class="<?= $googleOn ? 'mb-5' : 'mt-7' ?> flex items-start gap-2.5 rounded-xl bg-red-50 ring-1 ring-inset ring-red-200 px-4 py-3 text-sm text-red-700">
              <?= icon('AlertCircle', 'w-4 h-4 mt-0.5 shrink-0') ?>
              <span><?= e($f['message']) ?></span>
            </div>
          <?php else: ?>
            <div class="<?= $googleOn ? 'mb-5' : 'mt-7' ?> flex items-start gap-2.5 rounded-xl bg-sky-50 ring-1 ring-inset ring-sky-200 px-4 py-3 text-sm text-sky-700">
              <?= icon('CheckCircle2', 'w-4 h-4 mt-0.5 shrink-0') ?>
              <span><?= e($f['message']) ?></span>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>

        <form method="post" action="./" class="<?= $googleOn ? '' : 'mt-7 ' ?>space-y-5" data-loading-submit novalidate<?= recaptcha_form_attrs($isLogin ? 'login' : 'register') ?>>
          <?= form_action($isLogin ? 'auth.login' : 'auth.register') ?>
          <?= recaptcha_field() ?>
          <input type="hidden" name="next" value="<?= e($next) ?>">

          <?php if ($isLogin): ?>
            <div>
              <label class="<?= $labelClass ?>" for="auth-email">Email <span class="text-red-500">*</span></label>
              <input id="auth-email" name="email" type="email" autocomplete="email" value="<?= e(old('email')) ?>" placeholder="yourname@gmail.com" class="<?= $inputClass ?>">
            </div>
          <?php else: ?>
            <div class="grid sm:grid-cols-2 gap-4">
              <div>
                <label class="<?= $labelClass ?>" for="auth-name">Full Name <span class="text-red-500">*</span></label>
                <input id="auth-name" name="name" type="text" autocomplete="name" value="<?= e(old('name')) ?>" placeholder="Enter your name" class="<?= $inputClass ?>">
              </div>
              <div>
                <label class="<?= $labelClass ?>" for="auth-email">Email <span class="text-red-500">*</span></label>
                <input id="auth-email" name="email" type="email" autocomplete="email" value="<?= e(old('email')) ?>" placeholder="yourname@gmail.com" class="<?= $inputClass ?>">
              </div>
            </div>
          <?php endif; ?>

          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="block text-sm font-medium text-slate-800" for="auth-password">Password <span class="text-red-500">*</span></label>
              <?php if (!$isLogin): ?>
                <button type="button" data-generate-btn class="inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:text-brand-800 transition">
                  <span data-generate-idle class="inline-flex items-center gap-1">
                    <?= icon('Wand2', 'w-3.5 h-3.5') ?>
                    Generate Password
                  </span>
                  <span data-generate-done class="hidden inline-flex items-center gap-1">
                    <?= icon('Check', 'w-3.5 h-3.5') ?>
                    Generated &amp; copied
                  </span>
                </button>
              <?php endif; ?>
            </div>
            <div class="relative">
              <input
                id="auth-password"
                name="password"
                type="password"
                autocomplete="<?= $isLogin ? 'current-password' : 'new-password' ?>"
                placeholder="Enter your password"
                class="<?= $inputClass ?> pr-12"
                data-password-input
                <?= $isLogin ? '' : 'data-strength-input' ?>
              >
              <button type="button" data-password-toggle class="absolute right-1.5 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-slate-400 hover:text-slate-800 hover:bg-slate-100 transition" aria-label="Show password" title="Show password">
                <span data-icon-hidden><?= icon('Eye', 'w-5 h-5') ?></span>
                <span data-icon-visible class="hidden"><?= icon('EyeOff', 'w-5 h-5') ?></span>
              </button>
            </div>
            <?php if (!$isLogin): ?>
              <div class="mt-2.5 hidden items-center gap-3" data-strength-group>
                <div class="flex-1 grid grid-cols-3 gap-1.5">
                  <span class="h-1.5 rounded-full bg-slate-200" data-strength-bar="1"></span>
                  <span class="h-1.5 rounded-full bg-slate-200" data-strength-bar="2"></span>
                  <span class="h-1.5 rounded-full bg-slate-200" data-strength-bar="3"></span>
                </div>
                <span class="text-xs font-semibold text-slate-600" data-strength-label></span>
              </div>
              <p class="mt-2 text-xs text-slate-400">Must be at least 8 characters, with uppercase, lowercase, a number and a symbol.</p>
            <?php endif; ?>
          </div>

          <?php if (!$isLogin): ?>
            <div>
              <label class="<?= $labelClass ?>" for="auth-confirm">Confirm Password <span class="text-red-500">*</span></label>
              <input
                id="auth-confirm"
                name="confirm"
                type="password"
                autocomplete="new-password"
                placeholder="Confirm your password"
                class="<?= $inputClass ?>"
                data-password-input
                data-confirm-input
              >
              <p class="mt-1.5 text-xs text-red-600 hidden" data-confirm-error>Passwords do not match.</p>
            </div>
          <?php endif; ?>

          <?php if ($isLogin): ?>
            <div class="flex items-center justify-between">
              <label class="inline-flex items-center gap-2.5 text-sm text-slate-700 cursor-pointer select-none">
                <input type="hidden" name="remember" value="0">
                <input type="checkbox" name="remember" value="1" class="w-4 h-4 rounded accent-[#557392]"<?= checked(old('remember', '1') === '1') ?>>
                Keep me logged in
              </label>
              <a href="<?= e(url('forgot')) ?>" class="text-sm font-medium text-brand-700 hover:text-brand-800 transition">Forgot password?</a>
            </div>
          <?php endif; ?>

          <button type="submit" data-submit-button class="w-full h-[42px] inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-sm font-semibold text-white shadow-button transition disabled:opacity-60">
            <span data-loading-spinner class="hidden w-4 h-4 rounded-full border-2 border-white/40 border-t-white animate-spin"></span>
            <?= $isLogin ? 'Sign In' : 'Create Account' ?>
          </button>
        </form>

        <p class="mt-6 text-sm text-slate-500">
          <?= $isLogin ? 'Don’t have an account?' : 'Already have an account?' ?>
          <a href="<?= e(url($isLogin ? 'register' : 'login', $nextParams)) ?>" class="font-semibold text-brand-700 hover:text-brand-800 transition">
            <?= $isLogin ? 'Sign Up' : 'Sign In' ?>
          </a>
        </p>
      </div>
    </div>
  </div>

  <!-- ---------- Brand side ---------- -->
  <div class="hidden lg:flex lg:sticky lg:top-0 lg:h-screen relative overflow-hidden items-center justify-center bg-gradient-to-br from-[#112A46] via-[#0E2440] to-[#1B3B5A] border-l border-slate-200/80">
    <!-- Grid of squares with a few lit tiles -->
    <div class="absolute inset-0 [background-image:linear-gradient(rgb(122_140_166/0.14)_1px,transparent_1px),linear-gradient(90deg,rgb(122_140_166/0.14)_1px,transparent_1px)] [background-size:64px_64px] [mask-image:radial-gradient(ellipse_at_center,transparent_22%,black_75%)]" aria-hidden="true"></div>
    <span class="absolute top-[64px] right-[128px] w-16 h-16 bg-[#34506D]/35" aria-hidden="true"></span>
    <span class="absolute top-[128px] right-[192px] w-16 h-16 bg-[#34506D]/35" aria-hidden="true"></span>
    <span class="absolute bottom-[128px] left-[128px] w-16 h-16 bg-[#34506D]/35" aria-hidden="true"></span>
    <span class="absolute bottom-[64px] left-[64px] w-16 h-16 bg-[#34506D]/35" aria-hidden="true"></span>
    <span class="absolute top-[256px] left-[64px] w-16 h-16 bg-[#34506D]/35" aria-hidden="true"></span>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[520px] h-[520px] rounded-full bg-[radial-gradient(closest-side,rgb(85_115_146/0.35),transparent)]" aria-hidden="true"></div>

    <div class="relative max-w-md px-8 text-center">
      <a href="<?= e(url('home')) ?>" class="mx-auto w-20 h-20 rounded-full bg-gradient-to-br from-[#6A87A6] to-[#34506D] flex items-center justify-center shadow-[0_20px_50px_-15px_rgb(0_0_0/0.6)] ring-8 ring-[#557392]/15" aria-label="Back to MailDart home">
        <svg viewBox="0 0 24 24" fill="none" class="w-9 h-9" aria-hidden="true">
          <path d="M2.5 15.5h3.5M3.5 19h4.5" stroke="white" stroke-opacity="0.6" stroke-width="1.6" stroke-linecap="round"></path>
          <path d="M21 3 9.6 21l-2.4-7.8L21 3Z" fill="white"></path>
          <path d="M21 3 7.2 13.2 2.8 11.4 21 3Z" fill="white" fill-opacity="0.8"></path>
        </svg>
      </a>
      <h2 class="mt-7 text-3xl font-bold tracking-tight text-[#F2F6F8]">MailDart Pro</h2>
      <p class="mt-3 text-[#A1B2C4] leading-relaxed">
        Smart, staggered email campaigns that reach the inbox, sent from your own email account and personalised for every recipient.
      </p>
      <ul class="mt-8 space-y-3 text-left inline-block">
        <li class="flex items-center gap-3 text-sm text-[#E1E8F0]">
          <span class="w-8 h-8 rounded-lg bg-white/5 ring-1 ring-inset ring-white/10 flex items-center justify-center">
            <?= icon('Timer', 'w-4 h-4 text-[#C4D2E1]') ?>
          </span>
          One email at a time, anti-spam sending
        </li>
        <li class="flex items-center gap-3 text-sm text-[#E1E8F0]">
          <span class="w-8 h-8 rounded-lg bg-white/5 ring-1 ring-inset ring-white/10 flex items-center justify-center">
            <?= icon('FileSpreadsheet', 'w-4 h-4 text-[#C4D2E1]') ?>
          </span>
          Import recipients from Excel in seconds
        </li>
        <li class="flex items-center gap-3 text-sm text-[#E1E8F0]">
          <span class="w-8 h-8 rounded-lg bg-white/5 ring-1 ring-inset ring-white/10 flex items-center justify-center">
            <?= icon('ShieldCheck', 'w-4 h-4 text-[#C4D2E1]') ?>
          </span>
          Practise safely in Sandbox mode
        </li>
      </ul>
    </div>
  </div>
</div>
