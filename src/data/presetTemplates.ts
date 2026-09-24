import { FestivalPreset } from '../types';

export const PRESET_TEMPLATES: FestivalPreset[] = [
  {
    id: 'diwali-delight',
    name: 'Diwali Festive Dhamaka (Golden Glow)',
    festival: 'diwali',
    description: 'Golden glowing lamps, opulent traditional greeting with flat 50% festival discount.',
    subject: '✨ Shubh Deepavali Wishes from {{company}} + Exclusive Festive Gift for {{name}}! 🪔',
    badgeColor: 'from-amber-500 to-orange-600',
    defaultDiscount: 'DIWALI50',
    html: `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Happy Diwali Wishes</title>
</head>
<body style="margin: 0; padding: 0; background-color: #120c06; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #fdf6e2;">
  <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #120c06; padding: 30px 10px;">
    <tr>
      <td align="center">
        <!-- Main Container -->
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background: linear-gradient(180deg, #2b1704 0%, #170d03 100%); border-radius: 16px; border: 1px solid #d4af37; overflow: hidden; box-shadow: 0 10px 30px rgba(212, 175, 55, 0.2);">
          
          <!-- Header Bar -->
          <tr>
            <td style="padding: 24px 30px; text-align: center; border-bottom: 1px solid rgba(212, 175, 55, 0.25);">
              <span style="font-size: 24px; font-weight: 800; letter-spacing: 2px; color: #f7d070; text-transform: uppercase;">
                ✨ {{company}} ✨
              </span>
            </td>
          </tr>

          <!-- Festival Hero Banner -->
          <tr>
            <td style="padding: 40px 30px 20px 30px; text-align: center; background: radial-gradient(circle, rgba(230, 138, 0, 0.25) 0%, rgba(18, 12, 6, 0) 70%);">
              <div style="font-size: 42px; margin-bottom: 10px;">🪔 🎆 🪔</div>
              <h1 style="margin: 0 0 12px 0; font-size: 32px; font-weight: 800; color: #ffd700; line-height: 1.2; text-shadow: 0 2px 8px rgba(255, 215, 0, 0.3);">
                Happy & Prosperous Diwali!
              </h1>
              <p style="margin: 0; font-size: 16px; color: #f5cf92; letter-spacing: 1px;">
                May this festival of lights illuminate your life with infinite joy & prosperity.
              </p>
            </td>
          </tr>

          <!-- Personal Greeting Body -->
          <tr>
            <td style="padding: 20px 35px 25px 35px; font-size: 16px; line-height: 1.7; color: #e8d9c5;">
              <p style="margin-top: 0;">
                Dear <strong style="color: #ffd700;">{{name}}</strong>,
              </p>
              <p>
                As we celebrate this auspicious festival of Diwali, the entire team at <strong>{{company}}</strong> wants to express our heartfelt gratitude for being a valued part of our journey.
              </p>
              <p>
                To make this festive season even sweeter for you and your loved ones, we are thrilled to present you with a special Diwali privilege:
              </p>

              <!-- Coupon Voucher Box -->
              <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 25px 0; background: linear-gradient(135deg, #421e05 0%, #2a1102 100%); border: 2px dashed #f59e0b; border-radius: 12px; text-align: center;">
                <tr>
                  <td style="padding: 24px 20px;">
                    <span style="font-size: 13px; text-transform: uppercase; letter-spacing: 2px; color: #fcd34d; font-weight: 600; display: block; margin-bottom: 6px;">Exclusive Festive Gift Voucher</span>
                    <div style="font-size: 30px; font-weight: 800; color: #ffffff; letter-spacing: 4px; font-family: monospace; padding: 10px 20px; background: rgba(0,0,0,0.4); border-radius: 8px; display: inline-block; border: 1px solid rgba(252, 211, 77, 0.4);">
                      {{discount}}
                    </div>
                    <p style="margin: 10px 0 0 0; font-size: 14px; color: #fde68a;">
                      Use code at checkout to unlock your special festive discount & free festive gifts!
                    </p>
                  </td>
                </tr>
              </table>

              <!-- CTA Button -->
              <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 30px 0;">
                <tr>
                  <td align="center">
                    <a href="https://example.com/festive-offer" style="display: inline-block; padding: 16px 40px; background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%); color: #180b02; font-size: 16px; font-weight: 800; text-decoration: none; border-radius: 50px; text-transform: uppercase; letter-spacing: 1.5px; box-shadow: 0 4px 20px rgba(245, 158, 11, 0.4);">
                      Claim Your Festive Gift Now →
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin-bottom: 0; font-size: 14px; color: #c2b09d;">
                Wishing you, your family, and team great health, wealth, and continued success!
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding: 24px 30px; background-color: #0d0803; border-top: 1px solid rgba(212, 175, 55, 0.2); text-align: center; font-size: 12px; color: #948270;">
              <p style="margin: 0 0 8px 0;">
                Sent with warm festive wishes from <strong>{{company}}</strong> to <strong>{{email}}</strong>.
              </p>
              <p style="margin: 0;">
                © 2026 {{company}}. All rights reserved. | <a href="#" style="color: #f59e0b; text-decoration: underline;">Unsubscribe</a> | <a href="#" style="color: #f59e0b; text-decoration: underline;">Privacy Policy</a>
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>`,
  },
  {
    id: 'eid-mubarak',
    name: 'Eid Mubarak (Emerald & Crescent Gold)',
    festival: 'eid',
    description: 'Emerald green themes, golden crescents, spiritual blessings, and festive treat code.',
    subject: '🌙 Eid Mubarak from {{company}}! Special Festive Blessings & Offer for {{name}} 🎁',
    badgeColor: 'from-emerald-500 to-teal-700',
    defaultDiscount: 'EIDBLESSINGS',
    html: `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Eid Mubarak Wishes</title>
</head>
<body style="margin: 0; padding: 0; background-color: #03150d; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #ecfdf5;">
  <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #03150d; padding: 30px 10px;">
    <tr>
      <td align="center">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background: linear-gradient(180deg, #06311e 0%, #031c11 100%); border-radius: 16px; border: 1px solid #10b981; overflow: hidden; box-shadow: 0 10px 30px rgba(16, 185, 129, 0.2);">
          
          <!-- Header -->
          <tr>
            <td style="padding: 24px 30px; text-align: center; border-bottom: 1px solid rgba(16, 185, 129, 0.2);">
              <span style="font-size: 22px; font-weight: 700; letter-spacing: 2px; color: #6ee7b7; text-transform: uppercase;">
                🕌 {{company}}
              </span>
            </td>
          </tr>

          <!-- Banner -->
          <tr>
            <td style="padding: 35px 30px 15px 30px; text-align: center;">
              <div style="font-size: 40px; margin-bottom: 10px;">🌙 🌟 ✨</div>
              <h1 style="margin: 0 0 10px 0; font-size: 30px; font-weight: 800; color: #fde047; text-shadow: 0 2px 8px rgba(253, 224, 71, 0.3);">
                Eid Mubarak!
              </h1>
              <p style="margin: 0; font-size: 15px; color: #a7f3d0;">
                May divine peace, abundant joy, and prosperity bless you and your family.
              </p>
            </td>
          </tr>

          <!-- Greeting Body -->
          <tr>
            <td style="padding: 20px 35px 25px 35px; font-size: 16px; line-height: 1.7; color: #d1fae5;">
              <p style="margin-top: 0;">
                Assalamu Alaikum / Warm Greetings <strong style="color: #fde047;">{{name}}</strong>,
              </p>
              <p>
                On this joyous occasion of Eid, everyone at <strong>{{company}}</strong> sends you our warmest thoughts and heartfelt wishes. May your days be filled with happiness and sweet moments.
              </p>
              <p>
                To celebrate together, we have prepared a special festive token of appreciation exclusively for you:
              </p>

              <!-- Voucher Box -->
              <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 25px 0; background: rgba(6, 78, 59, 0.6); border: 2px dashed #34d399; border-radius: 12px; text-align: center;">
                <tr>
                  <td style="padding: 22px;">
                    <span style="font-size: 12px; text-transform: uppercase; letter-spacing: 2px; color: #fde047; font-weight: 600; display: block; margin-bottom: 6px;">Festive Celebration Code</span>
                    <div style="font-size: 28px; font-weight: 800; color: #ffffff; letter-spacing: 3px; font-family: monospace; padding: 8px 18px; background: rgba(0,0,0,0.3); border-radius: 8px; display: inline-block;">
                      {{discount}}
                    </div>
                    <p style="margin: 8px 0 0 0; font-size: 13px; color: #a7f3d0;">
                      Apply code at checkout to enjoy special festive rewards & expedited delivery.
                    </p>
                  </td>
                </tr>
              </table>

              <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 25px 0;">
                <tr>
                  <td align="center">
                    <a href="https://example.com/eid-collection" style="display: inline-block; padding: 15px 38px; background: linear-gradient(90deg, #10b981 0%, #059669 100%); color: #ffffff; font-size: 15px; font-weight: 700; text-decoration: none; border-radius: 50px; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 4px 18px rgba(16, 185, 129, 0.4);">
                      Explore Festive Offer →
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin-bottom: 0; font-size: 14px; color: #a7f3d0;">
                Wishing you a blessed and memorable celebration!
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding: 20px 30px; background-color: #020f09; border-top: 1px solid rgba(16, 185, 129, 0.15); text-align: center; font-size: 12px; color: #6ee7b7;">
              <p style="margin: 0 0 6px 0;">
                Sent with affection from <strong>{{company}}</strong> to <strong>{{email}}</strong>.
              </p>
              <p style="margin: 0;">
                © 2026 {{company}}. All rights reserved. | <a href="#" style="color: #34d399; text-decoration: underline;">Unsubscribe</a>
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>`,
  },
  {
    id: 'christmas-newyear',
    name: 'Christmas & New Year Holiday Gala',
    festival: 'christmas',
    description: 'Festive holiday red, snowflakes, corporate holiday greetings and year-end discount.',
    subject: '🎄 Merry Christmas & Happy New Year from {{company}}! A Holiday Gift for {{name}} ❄️',
    badgeColor: 'from-rose-500 to-red-700',
    defaultDiscount: 'HOLIDAY2026',
    html: `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Merry Christmas & Happy New Year</title>
</head>
<body style="margin: 0; padding: 0; background-color: #17070a; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #ffffff;">
  <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #17070a; padding: 30px 10px;">
    <tr>
      <td align="center">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background: linear-gradient(180deg, #2b0b12 0%, #150508 100%); border-radius: 16px; border: 1px solid #e11d48; overflow: hidden; box-shadow: 0 10px 30px rgba(225, 29, 72, 0.2);">
          
          <!-- Header -->
          <tr>
            <td style="padding: 24px 30px; text-align: center; border-bottom: 1px solid rgba(225, 29, 72, 0.25);">
              <span style="font-size: 22px; font-weight: 800; letter-spacing: 2px; color: #fda4af; text-transform: uppercase;">
                🎁 {{company}}
              </span>
            </td>
          </tr>

          <!-- Banner -->
          <tr>
            <td style="padding: 35px 30px 15px 30px; text-align: center;">
              <div style="font-size: 42px; margin-bottom: 10px;">🎄 ⛄ ❄️ 🍾</div>
              <h1 style="margin: 0 0 10px 0; font-size: 32px; font-weight: 800; color: #ffffff;">
                Happy Holidays & Happy New Year!
              </h1>
              <p style="margin: 0; font-size: 15px; color: #fecdd3;">
                Wishing you peace, cheer, and wonderful times with family and friends.
              </p>
            </td>
          </tr>

          <!-- Greeting Body -->
          <tr>
            <td style="padding: 20px 35px 25px 35px; font-size: 16px; line-height: 1.7; color: #ffe4e6;">
              <p style="margin-top: 0;">
                Season's Greetings <strong style="color: #ffffff;">{{name}}</strong>,
              </p>
              <p>
                As this year comes to a festive close, we want to pause and thank you for being a cherished partner and customer at <strong>{{company}}</strong>.
              </p>
              <p>
                To celebrate the holidays and ring in an incredible New Year, we have unlocked an exclusive festive savings gift for you:
              </p>

              <!-- Coupon Box -->
              <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 25px 0; background: linear-gradient(135deg, #4c0519 0%, #1f0209 100%); border: 2px dashed #f43f5e; border-radius: 12px; text-align: center;">
                <tr>
                  <td style="padding: 24px 20px;">
                    <span style="font-size: 12px; text-transform: uppercase; letter-spacing: 2px; color: #fda4af; font-weight: 700; display: block; margin-bottom: 6px;">Holiday Cheer Voucher</span>
                    <div style="font-size: 28px; font-weight: 800; color: #ffffff; letter-spacing: 3px; font-family: monospace; padding: 10px 20px; background: rgba(0,0,0,0.4); border-radius: 8px; display: inline-block;">
                      {{discount}}
                    </div>
                    <p style="margin: 10px 0 0 0; font-size: 13px; color: #fecdd3;">
                      Valid across all holiday specials & seasonal products until midnight!
                    </p>
                  </td>
                </tr>
              </table>

              <!-- CTA -->
              <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 30px 0;">
                <tr>
                  <td align="center">
                    <a href="https://example.com/holiday-sale" style="display: inline-block; padding: 16px 40px; background: linear-gradient(90deg, #e11d48 0%, #be123c 100%); color: #ffffff; font-size: 15px; font-weight: 800; text-decoration: none; border-radius: 50px; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 4px 20px rgba(225, 29, 72, 0.4);">
                      Unwrap Your Holiday Gift 🎁
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin-bottom: 0; font-size: 14px; color: #fecdd3;">
                Here's to a prosperous, healthy, and successful New Year ahead!
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding: 24px 30px; background-color: #0d0205; border-top: 1px solid rgba(225, 29, 72, 0.2); text-align: center; font-size: 12px; color: #fda4af;">
              <p style="margin: 0 0 6px 0;">
                Sent with holiday warmth from <strong>{{company}}</strong> to <strong>{{email}}</strong>.
              </p>
              <p style="margin: 0;">
                © 2026 {{company}}. All rights reserved. | <a href="#" style="color: #fb7185; text-decoration: underline;">Unsubscribe</a>
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>`,
  },
  {
    id: 'holi-colors',
    name: 'Holi Festival of Joy & Colors',
    festival: 'holi',
    description: 'Vibrant gulal colors, celebratory energy, playful design with special festive promo.',
    subject: '🎨 Happy Holi from {{company}}! Add Vibrant Colors to Your Festive Season {{name}} 🌈',
    badgeColor: 'from-pink-500 via-purple-500 to-indigo-600',
    defaultDiscount: 'HOLIJOY2026',
    html: `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Happy Holi Festival</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0b071e; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #fdf2f8;">
  <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #0b071e; padding: 30px 10px;">
    <tr>
      <td align="center">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background: linear-gradient(180deg, #1f1147 0%, #0e0724 100%); border-radius: 16px; border: 1px solid #ec4899; overflow: hidden; box-shadow: 0 10px 30px rgba(236, 72, 153, 0.25);">
          
          <tr>
            <td style="padding: 24px 30px; text-align: center; border-bottom: 1px solid rgba(236, 72, 153, 0.3);">
              <span style="font-size: 22px; font-weight: 800; letter-spacing: 2px; color: #f472b6; text-transform: uppercase;">
                🎨 {{company}}
              </span>
            </td>
          </tr>

          <tr>
            <td style="padding: 35px 30px 15px 30px; text-align: center;">
              <div style="font-size: 42px; margin-bottom: 10px;">🌸 🌈 🎉 💦</div>
              <h1 style="margin: 0 0 10px 0; font-size: 32px; font-weight: 800; background: linear-gradient(90deg, #ec4899, #a855f7, #3b82f6); -webkit-background-clip: text; color: #f472b6;">
                Happy & Joyous Holi!
              </h1>
              <p style="margin: 0; font-size: 15px; color: #e9d5ff;">
                May your life be painted with colors of boundless laughter, success, and love!
              </p>
            </td>
          </tr>

          <tr>
            <td style="padding: 20px 35px 25px 35px; font-size: 16px; line-height: 1.7; color: #f3e8ff;">
              <p style="margin-top: 0;">
                Dear <strong style="color: #f472b6;">{{name}}</strong>,
              </p>
              <p>
                As we splash in the vibrant hues of spring and celebrate the triumph of joy, we at <strong>{{company}}</strong> want to extend our warmest festive blessings to you.
              </p>
              <p>
                Celebrate this festival of colors with our exclusive festive surprise:
              </p>

              <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 25px 0; background: linear-gradient(135deg, #3b0764 0%, #1e1b4b 100%); border: 2px dashed #ec4899; border-radius: 12px; text-align: center;">
                <tr>
                  <td style="padding: 22px;">
                    <span style="font-size: 12px; text-transform: uppercase; letter-spacing: 2px; color: #f472b6; font-weight: 700; display: block; margin-bottom: 6px;">Holi Festive Promo</span>
                    <div style="font-size: 28px; font-weight: 800; color: #ffffff; letter-spacing: 3px; font-family: monospace; padding: 10px 20px; background: rgba(0,0,0,0.3); border-radius: 8px; display: inline-block;">
                      {{discount}}
                    </div>
                    <p style="margin: 8px 0 0 0; font-size: 13px; color: #e9d5ff;">
                      Extra savings & special festive gifts on all orders this week!
                    </p>
                  </td>
                </tr>
              </table>

              <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 25px 0;">
                <tr>
                  <td align="center">
                    <a href="https://example.com/holi-bonanza" style="display: inline-block; padding: 15px 38px; background: linear-gradient(90deg, #ec4899 0%, #8b5cf6 100%); color: #ffffff; font-size: 15px; font-weight: 800; text-decoration: none; border-radius: 50px; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 4px 20px rgba(236, 72, 153, 0.4);">
                      Grab Festive Deals 🌸
                    </a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td style="padding: 20px 30px; background-color: #070313; border-top: 1px solid rgba(236, 72, 153, 0.2); text-align: center; font-size: 12px; color: #c084fc;">
              <p style="margin: 0 0 6px 0;">
                Wishing you a joyous celebration! From <strong>{{company}}</strong> to <strong>{{email}}</strong>.
              </p>
              <p style="margin: 0;">
                © 2026 {{company}}. All rights reserved. | <a href="#" style="color: #f472b6; text-decoration: underline;">Unsubscribe</a>
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>`,
  },
  {
    id: 'corporate-festival',
    name: 'Corporate Customer Appreciation (Executive Suite)',
    festival: 'custom',
    description: 'Minimalist corporate luxury, gold accents on midnight blue, gratitude and loyalty reward.',
    subject: 'A Special Festive Thank You from {{company}} to {{name}}',
    badgeColor: 'from-blue-600 to-indigo-800',
    defaultDiscount: 'VIPTHANKYOU',
    html: `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Festive Customer Appreciation</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0b0f19; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #f1f5f9;">
  <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #0b0f19; padding: 30px 10px;">
    <tr>
      <td align="center">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #111827; border-radius: 12px; border: 1px solid #1e293b; overflow: hidden; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);">
          
          <tr>
            <td style="padding: 28px 36px; border-bottom: 1px solid #1e293b;">
              <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                  <td>
                    <span style="font-size: 20px; font-weight: 700; letter-spacing: 1px; color: #ffffff;">
                      {{company}}
                    </span>
                  </td>
                  <td align="right">
                    <span style="font-size: 12px; padding: 4px 10px; background: rgba(59, 130, 246, 0.15); color: #60a5fa; border-radius: 20px; font-weight: 600; border: 1px solid rgba(59, 130, 246, 0.3);">
                      Festive Special
                    </span>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td style="padding: 36px 36px 20px 36px;">
              <h2 style="margin: 0 0 14px 0; font-size: 26px; font-weight: 700; color: #ffffff; line-height: 1.3;">
                Celebrating our partnership this festive season.
              </h2>
              <p style="margin: 0; font-size: 15px; line-height: 1.7; color: #94a3b8;">
                Dear <strong style="color: #ffffff;">{{name}}</strong>,
              </p>
              <p style="font-size: 15px; line-height: 1.7; color: #94a3b8;">
                As the festival season brings us together, we want to take a moment to express our sincere appreciation for your trust and continued collaboration with <strong>{{company}}</strong>.
              </p>
              <p style="font-size: 15px; line-height: 1.7; color: #94a3b8;">
                As a token of our gratitude, we have credited your account with an exclusive festive perk:
              </p>

              <div style="background-color: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 20px; margin: 24px 0; text-align: center;">
                <p style="margin: 0 0 8px 0; font-size: 12px; text-transform: uppercase; letter-spacing: 1.5px; color: #94a3b8;">Exclusive Loyalty Code</p>
                <div style="font-size: 24px; font-weight: 700; color: #38bdf8; font-family: monospace; letter-spacing: 2px;">
                  {{discount}}
                </div>
                <p style="margin: 8px 0 0 0; font-size: 13px; color: #64748b;">
                  Apply this code during your next order or contract renewal.
                </p>
              </div>

              <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 28px 0 10px 0;">
                <tr>
                  <td>
                    <a href="https://example.com/portal" style="display: inline-block; padding: 14px 32px; background-color: #2563eb; color: #ffffff; font-size: 14px; font-weight: 600; text-decoration: none; border-radius: 6px;">
                      Access Your Festive Perks →
                    </a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td style="padding: 24px 36px; background-color: #090d16; border-top: 1px solid #1e293b; font-size: 12px; color: #64748b;">
              <p style="margin: 0 0 6px 0;">
                This message was sent to <strong>{{email}}</strong> by {{company}}.
              </p>
              <p style="margin: 0;">
                © 2026 {{company}}. All rights reserved. | <a href="#" style="color: #94a3b8; text-decoration: underline;">Preferences</a>
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>`,
  },
  {
    id: 'business-newsletter',
    name: 'Company Newsletter & Monthly Updates 📰',
    festival: 'newsletter',
    description: 'Clean modern corporate newsletter with editorial stories, product updates, and key metrics.',
    subject: '📢 {{company}} Monthly Digest: New Updates, Features & Insights for {{name}}',
    badgeColor: 'from-blue-600 to-cyan-600',
    defaultDiscount: 'MONTHLY20',
    html: `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Company Newsletter</title>
</head>
<body style="margin:0; padding:0; background-color:#0f172a; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color:#e2e8f0;">
  <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#0f172a; padding:30px 10px;">
    <tr>
      <td align="center">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:600px; background:#1e293b; border-radius:14px; border:1px solid #334155; overflow:hidden;">
          <tr>
            <td style="padding:28px 32px; background:linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%); border-bottom:1px solid #334155;">
              <span style="font-size:22px; font-weight:800; color:#38bdf8; text-transform:uppercase; letter-spacing:1.5px;">{{company}}</span>
              <p style="margin:6px 0 0 0; font-size:12px; color:#94a3b8;">Monthly Newsletter • Volume 12</p>
            </td>
          </tr>
          <tr>
            <td style="padding:32px 32px 20px 32px;">
              <h1 style="margin:0 0 16px 0; font-size:24px; font-weight:700; color:#ffffff;">Hello {{name}},</h1>
              <p style="margin:0 0 18px 0; font-size:15px; line-height:1.6; color:#cbd5e1;">
                We're excited to share our latest achievements, updates, and upcoming product rollouts curated specially for you at {{company}}.
              </p>
              
              <div style="margin:24px 0; padding:18px; background-color:rgba(56, 189, 248, 0.08); border-left:4px solid #38bdf8; border-radius:8px;">
                <h3 style="margin:0 0 6px 0; font-size:16px; color:#38bdf8;">🚀 What's New This Month:</h3>
                <ul style="margin:6px 0 0 0; padding-left:20px; font-size:14px; line-height:1.7; color:#cbd5e1;">
                  <li><strong>Speed Improvements:</strong> 40% faster workflow execution across all accounts.</li>
                  <li><strong>Custom Variables:</strong> Seamless spreadsheets data auto-mapping.</li>
                  <li><strong>Exclusive Promo:</strong> Use promo code <code style="background:#0f172a; padding:2px 6px; border-radius:4px; color:#fde047; font-weight:bold;">{{discount}}</code> on your next upgrade.</li>
                </ul>
              </div>

              <a href="https://example.com/read" style="display:inline-block; padding:12px 28px; background:#0284c7; color:#ffffff; font-weight:600; font-size:14px; text-decoration:none; border-radius:8px; margin-top:10px;">Read Full Announcement →</a>
            </td>
          </tr>
          <tr>
            <td style="padding:20px 32px; background:#0f172a; border-top:1px solid #334155; font-size:12px; color:#64748b;">
              <p style="margin:0;">Delivered to {{email}} by {{company}}. Unsubscribe anytime.</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>`
  },
  {
    id: 'product-launch',
    name: 'New Product Launch & Big Announcement 🚀',
    festival: 'product_launch',
    description: 'High-impact conversion template for new products, features, or exclusive service launches.',
    subject: '🔥 Introducing Our Biggest Launch Yet: Exclusive Access for {{name}} | {{company}}',
    badgeColor: 'from-emerald-600 to-teal-600',
    defaultDiscount: 'LAUNCH50',
    html: `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>New Product Launch</title>
</head>
<body style="margin:0; padding:0; background-color:#0b0f19; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color:#f1f5f9;">
  <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#0b0f19; padding:30px 10px;">
    <tr>
      <td align="center">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:600px; background:#111827; border-radius:16px; border:1px solid rgba(16, 185, 129, 0.3); overflow:hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.5);">
          <tr>
            <td style="padding:35px 30px; text-align:center; background: radial-gradient(circle, rgba(16, 185, 129, 0.2) 0%, rgba(17, 24, 39, 0) 70%);">
              <span style="font-size:12px; font-weight:bold; letter-spacing:2px; text-transform:uppercase; color:#10b981; background:rgba(16, 185, 129, 0.15); padding:6px 14px; border-radius:20px; border:1px solid rgba(16, 185, 129, 0.3);">NEW ANNOUNCEMENT</span>
              <h1 style="margin:20px 0 12px 0; font-size:32px; font-weight:800; color:#ffffff; line-height:1.2;">Built for the Future. Available Today.</h1>
              <p style="margin:0; font-size:16px; color:#94a3b8;">Hey {{name}}, get ready to transform your workflow with {{company}}.</p>
            </td>
          </tr>
          <tr>
            <td style="padding:10px 32px 30px 32px;">
              <p style="font-size:15px; line-height:1.6; color:#cbd5e1; margin-bottom:24px;">
                After months of testing, we are thrilled to unveil our next-generation platform. As an esteemed customer, you get early VIP access plus a launch benefit.
              </p>
              
              <div style="background:#1f2937; padding:20px; border-radius:12px; border:1px solid #374151; text-align:center; margin-bottom:25px;">
                <span style="font-size:13px; color:#9ca3af; text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:6px;">Your VIP Launch Code:</span>
                <span style="font-size:26px; font-weight:800; color:#34d399; font-family:monospace; letter-spacing:3px;">{{discount}}</span>
                <p style="margin:8px 0 0 0; font-size:12px; color:#94a3b8;">Valid on all plans for the next 7 days.</p>
              </div>

              <div style="text-align:center;">
                <a href="https://example.com/launch" style="display:inline-block; padding:15px 36px; background:#10b981; color:#064e3b; font-weight:800; font-size:15px; text-decoration:none; border-radius:10px; box-shadow:0 6px 20px rgba(16, 185, 129, 0.3);">Explore The New Features →</a>
              </div>
            </td>
          </tr>
          <tr>
            <td style="padding:20px 32px; background:#0b0f19; text-align:center; border-top:1px solid #1f2937; font-size:12px; color:#6b7280;">
              Sent with ❤️ from {{company}} to {{email}}.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>`
  },
  {
    id: 'followup-reminder',
    name: 'Follow-Up & Payment / Action Reminder ⏰',
    festival: 'followup_reminder',
    description: 'Polite, high-converting follow-up template for invoices, appointments, pending proposals, and dues.',
    subject: 'Reminder: Action Required regarding your account with {{company}} - {{name}}',
    badgeColor: 'from-amber-600 to-yellow-600',
    defaultDiscount: 'REMIND0',
    html: `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Account Follow-Up Reminder</title>
</head>
<body style="margin:0; padding:0; background-color:#0f172a; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color:#f8fafc;">
  <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#0f172a; padding:30px 10px;">
    <tr>
      <td align="center">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:580px; background:#1e293b; border-radius:14px; border:1px solid #334155; overflow:hidden;">
          <tr>
            <td style="padding:24px 30px; border-bottom:1px solid #334155; background:#1e293b;">
              <span style="font-size:20px; font-weight:bold; color:#f59e0b;">{{company}}</span>
            </td>
          </tr>
          <tr>
            <td style="padding:32px 30px;">
              <h2 style="margin:0 0 14px 0; font-size:20px; color:#ffffff;">Dear {{name}},</h2>
              <p style="margin:0 0 16px 0; font-size:14px; line-height:1.6; color:#cbd5e1;">
                This is a quick friendly reminder regarding your pending action / invoice with {{company}}.
              </p>
              
              <div style="background:#0f172a; padding:18px; border-radius:8px; border-left:4px solid #f59e0b; margin:20px 0;">
                <p style="margin:0 0 6px 0; font-size:13px; color:#94a3b8;"><strong>Recipient:</strong> {{name}} ({{email}})</p>
                <p style="margin:0; font-size:13px; color:#cbd5e1;">Please take a moment to review and complete the pending steps before the upcoming deadline.</p>
              </div>

              <a href="https://example.com/review" style="display:inline-block; padding:12px 28px; background:#f59e0b; color:#18181b; font-weight:700; font-size:14px; text-decoration:none; border-radius:8px; margin-top:10px;">Review & Proceed →</a>
              
              <p style="margin:24px 0 0 0; font-size:13px; color:#94a3b8; line-height:1.5;">
                If you have already settled this, please feel free to disregard this note. Our support team is here if you need any assistance!
              </p>
            </td>
          </tr>
          <tr>
            <td style="padding:16px 30px; background:#0f172a; border-top:1px solid #334155; font-size:12px; color:#64748b;">
              Sent by {{company}} Billing & Client Success.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>`
  },
  {
    id: 'welcome-onboarding',
    name: 'Customer Welcome & Onboarding Guide 👋',
    festival: 'welcome_onboarding',
    description: 'Warm, engaging welcome email template for new customers, signups, or client onboardings.',
    subject: '🎉 Welcome to {{company}}, {{name}}! Here is your quick start guide',
    badgeColor: 'from-violet-600 to-purple-600',
    defaultDiscount: 'WELCOME10',
    html: `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Welcome to the Family</title>
</head>
<body style="margin:0; padding:0; background-color:#09090b; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color:#f4f4f5;">
  <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#09090b; padding:30px 10px;">
    <tr>
      <td align="center">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:600px; background:#18181b; border-radius:16px; border:1px solid #27272a; overflow:hidden;">
          <tr>
            <td style="padding:36px 30px; text-align:center; background:linear-gradient(180deg, rgba(139, 92, 246, 0.2) 0%, rgba(24, 24, 27, 0) 100%);">
              <span style="font-size:40px; display:block; margin-bottom:12px;">👋</span>
              <h1 style="margin:0 0 10px 0; font-size:28px; font-weight:800; color:#ffffff;">Welcome aboard, {{name}}!</h1>
              <p style="margin:0; font-size:15px; color:#a1a1aa;">We are thrilled to partner with you at {{company}}.</p>
            </td>
          </tr>
          <tr>
            <td style="padding:10px 32px 30px 32px;">
              <p style="font-size:15px; line-height:1.6; color:#d4d4d8; margin-bottom:20px;">
                You've taken the first step towards automating and supercharging your outreach. Here are 3 quick tips to hit the ground running:
              </p>
              
              <div style="background:#27272a; padding:18px; border-radius:10px; margin-bottom:16px;">
                <p style="margin:0 0 8px 0; font-weight:bold; color:#a78bfa;">1. Upload Your Recipient List</p>
                <p style="margin:0; font-size:13px; color:#a1a1aa;">Easily import Excel spreadsheets with custom fields like City, Order ID, etc.</p>
              </div>

              <div style="background:#27272a; padding:18px; border-radius:10px; margin-bottom:24px;">
                <p style="margin:0 0 8px 0; font-weight:bold; color:#a78bfa;">2. Use Your Welcome Bonus</p>
                <p style="margin:0; font-size:13px; color:#a1a1aa;">Enjoy your special perk code: <strong style="color:#ffffff;">{{discount}}</strong></p>
              </div>

              <div style="text-align:center;">
                <a href="https://example.com/start" style="display:inline-block; padding:14px 34px; background:#8b5cf6; color:#ffffff; font-weight:bold; font-size:14px; text-decoration:none; border-radius:8px;">Get Started Now →</a>
              </div>
            </td>
          </tr>
          <tr>
            <td style="padding:20px 32px; background:#09090b; text-align:center; border-top:1px solid #27272a; font-size:12px; color:#71717a;">
              Need help? Reply directly to this email or visit our help center.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>`
  }
];
