import express from 'express';
import { createServer as createViteServer } from 'vite';
import path from 'path';
import { fileURLToPath } from 'url';
import nodemailer from 'nodemailer';
import { GoogleGenAI } from '@google/genai';
import dotenv from 'dotenv';

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

async function startServer() {
  const app = express();
  const PORT = process.env.PORT || 3000;
  const isProduction = process.env.NODE_ENV === 'production';

  app.use(express.json({ limit: '10mb' }));
  app.use(express.urlencoded({ extended: true, limit: '10mb' }));

  // API: Health check
  app.get('/api/health', (req, res) => {
    res.json({ status: 'ok', timestamp: new Date().toISOString() });
  });

  // API: Verify SMTP Configuration
  app.post('/api/verify-smtp', async (req, res) => {
    try {
      const { host, port, secure, auth, username, password } = req.body;
      const user = auth?.user || username;
      const pass = auth?.pass || password;

      if (!host || !user || !pass) {
        return res.status(400).json({
          success: false,
          error: 'Host, Username (Email), and Password / App Key are required.',
        });
      }

      const smtpPort = Number(port) || (secure ? 465 : 587);
      const transporter = nodemailer.createTransport({
        host,
        port: smtpPort,
        secure: secure !== undefined ? Boolean(secure) : smtpPort === 465,
        auth: {
          user,
          pass,
        },
        connectionTimeout: 12000,
        greetingTimeout: 12000,
        socketTimeout: 15000,
        tls: {
          rejectUnauthorized: false,
        },
      });

      await transporter.verify();
      return res.json({
        success: true,
        message: `SMTP connection verified successfully! Emails will be sent from ${user}.`,
      });
    } catch (error: any) {
      console.error('SMTP verify error:', error);
      return res.status(500).json({
        success: false,
        error: error.message || 'Failed to verify SMTP credentials.',
      });
    }
  });

  // API: Send Individual Email (supports live SMTP or simulated sending)
  app.post('/api/send-email', async (req, res) => {
    try {
      const {
        to,
        subject,
        html,
        fromName,
        fromEmail,
        replyTo,
        smtpConfig,
        simulate = false,
      } = req.body;

      if (!to || !subject || !html) {
        return res.status(400).json({
          success: false,
          error: 'Recipient email ("to"), subject, and HTML content are required.',
        });
      }

      // Check if real SMTP credentials are provided
      const smtpUser = smtpConfig?.username || smtpConfig?.auth?.user;
      const smtpPass = smtpConfig?.password || smtpConfig?.auth?.pass;
      const hasRealSmtp =
        !simulate &&
        smtpConfig &&
        smtpConfig.enabled !== false &&
        Boolean(smtpConfig.host) &&
        Boolean(smtpUser) &&
        Boolean(smtpPass);

      // If user configured real SMTP
      if (hasRealSmtp) {
        const port = Number(smtpConfig.port) || (smtpConfig.secure ? 465 : 587);
        const transporter = nodemailer.createTransport({
          host: smtpConfig.host,
          port,
          secure: smtpConfig.secure !== undefined ? Boolean(smtpConfig.secure) : port === 465,
          auth: {
            user: smtpUser,
            pass: smtpPass,
          },
          connectionTimeout: 15000,
          greetingTimeout: 15000,
          socketTimeout: 20000,
          tls: {
            rejectUnauthorized: false,
          },
        });

        // The email MUST be sent from the user's authentic email address
        const effectiveFromEmail = fromEmail || smtpConfig.fromEmail || smtpUser;
        const effectiveFromName = fromName || smtpConfig.fromName || 'Acme Celebrations';
        const senderFormatted = `"${effectiveFromName}" <${effectiveFromEmail}>`;

        console.log(`Dispatching real email via SMTP [${smtpConfig.host}:${port}] from: ${senderFormatted} to: ${to}`);

        const info = await transporter.sendMail({
          from: senderFormatted,
          to,
          replyTo: replyTo || effectiveFromEmail,
          subject,
          html,
        });

        return res.json({
          success: true,
          mode: 'smtp',
          sender: effectiveFromEmail,
          messageId: info.messageId,
          response: info.response,
          timestamp: new Date().toISOString(),
          message: `Email dispatched successfully from ${effectiveFromEmail} via ${smtpConfig.host}!`,
        });
      }

      // Simulated / Sandbox delivery mode (Safe testing mode with full log)
      const simulatedId = `<camp-${Date.now()}-${Math.random().toString(36).substring(2, 9)}@festivamail.internal>`;
      const simulatedSender = fromEmail || smtpConfig?.fromEmail || 'sandbox@festivamail.internal';
      return res.json({
        success: true,
        mode: 'simulated',
        sender: simulatedSender,
        messageId: simulatedId,
        message: 'Email delivered in simulation/sandbox mode (Valid HTML & recipients processed).',
        timestamp: new Date().toISOString(),
      });
    } catch (error: any) {
      console.error('Send email error:', error);
      return res.status(500).json({
        success: false,
        error: error.message || 'Failed to dispatch email.',
      });
    }
  });

  // API: Check AI Configuration & Models (NVIDIA & Gemini)
  app.get('/api/ai-config', (req, res) => {
    const hasGemini = Boolean(process.env.GEMINI_API_KEY);
    const hasNvidia = Boolean(process.env.NVIDIA_API_KEY);

    res.json({
      geminiConfigured: hasGemini,
      nvidiaConfigured: hasNvidia,
      recommendedEngine: hasNvidia ? 'nvidia' : 'gemini',
      nvidiaModels: [
        { id: 'meta/llama-3.3-70b-instruct', name: 'Meta Llama 3.3 70B (Recommended)' },
        { id: 'nvidia/llama-3.1-nemotron-70b-instruct', name: 'NVIDIA Nemotron 70B' },
        { id: 'deepseek-ai/deepseek-r1', name: 'DeepSeek R1 (Reasoning)' },
        { id: 'mistralai/mistral-large-2-instruct', name: 'Mistral Large 2' },
      ],
    });
  });

  // Helper: Call NVIDIA NIM API
  async function generateWithNvidia({
    prompt,
    model = 'meta/llama-3.3-70b-instruct',
  }: {
    prompt: string;
    model?: string;
  }): Promise<string> {
    const apiKey = process.env.NVIDIA_API_KEY;
    if (!apiKey) {
      throw new Error(
        'NVIDIA_API_KEY is not configured in environment. Please add NVIDIA_API_KEY to your Secrets / .env or choose Google Gemini.'
      );
    }

    const response = await fetch('https://integrate.api.nvidia.com/v1/chat/completions', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${apiKey.trim()}`,
      },
      body: JSON.stringify({
        model: model || 'meta/llama-3.3-70b-instruct',
        messages: [
          {
            role: 'system',
            content:
              'You are a world-class email marketing HTML designer. You create 100% production-ready, bulletproof, responsive HTML emails with inline CSS and table layout. Output ONLY the raw HTML code without markdown code fence blocks, explanations, or commentary.',
          },
          {
            role: 'user',
            content: prompt,
          },
        ],
        temperature: 0.6,
        top_p: 0.9,
        max_tokens: 4096,
      }),
    });

    if (!response.ok) {
      const errorText = await response.text();
      let errorMsg = errorText;
      try {
        const parsed = JSON.parse(errorText);
        errorMsg = parsed.error?.message || parsed.message || errorText;
      } catch {
        // use raw
      }
      throw new Error(`NVIDIA API Error (${response.status}): ${errorMsg}`);
    }

    const data = (await response.json()) as any;
    const content = data.choices?.[0]?.message?.content || '';
    return content;
  }

  // Helper: Call Google Gemini API
  async function generateWithGemini(prompt: string): Promise<string> {
    const apiKey = process.env.GEMINI_API_KEY;
    if (!apiKey) {
      throw new Error('GEMINI_API_KEY is not configured in server environment.');
    }

    const ai = new GoogleGenAI({
      apiKey,
      httpOptions: {
        headers: {
          'User-Agent': 'aistudio-build',
        },
      },
    });

    const candidateModels = ['gemini-3.8-flash', 'gemini-flash-latest', 'gemini-3.1-flash-lite'];
    let lastError: any = null;

    for (const modelName of candidateModels) {
      try {
        const response = await ai.models.generateContent({
          model: modelName,
          contents: prompt,
        });
        if (response.text) {
          return response.text;
        }
      } catch (err: any) {
        lastError = err;
        console.warn(`Model ${modelName} failed, trying next fallback:`, err.message || err);
      }
    }

    throw lastError || new Error('Failed to generate template with Gemini AI models.');
  }

  // API: AI-Powered Festival Email Template Generator (NVIDIA NIM + Google Gemini)
  app.post('/api/generate-template', async (req, res) => {
    try {
      const {
        festival,
        companyName,
        campaignOffer,
        targetAudience,
        tone,
        colorPalette,
        engine = 'auto', // 'nvidia' | 'gemini' | 'auto'
        nvidiaModel = 'meta/llama-3.3-70b-instruct',
      } = req.body;

      const prompt = `You are an expert email marketing designer and HTML email engineer.
Create a responsive, production-ready, beautifully styled HTML email template for a festival campaign with the following details:
- Festival/Occasion: ${festival || 'Festival Celebration & Special Sale'}
- Company/Brand Name: ${companyName || 'Our Company'}
- Special Offer / Discount / Message: ${campaignOffer || 'Special festive flat 40% OFF with code FESTIVE40'}
- Target Audience: ${targetAudience || 'Valued Customers'}
- Tone: ${tone || 'Festive, warm, celebratory, and action-driving'}
- Preferred Color Theme: ${colorPalette || 'Festive vibrant colors matching the festival theme'}

CRITICAL REQUIREMENTS:
1. Include standard placeholders that can be personalized per recipient:
   - {{name}} (Recipient name)
   - {{email}} (Recipient email)
   - {{company}} (Company name)
   - {{discount}} (Offer code or discount percentage)
   - {{festival}} (Festival name)
2. The HTML must be 100% email-client friendly (tables, inline CSS styles, max-width 600px, responsive, centered).
3. Include an attractive header with festive greetings and festive emoji/decorations, a hero banner section with the big offer, clear typography, warm greeting body text, an eye-catching CTA button (e.g. "Claim Festive Offer", "Shop Festival Deals"), a festive coupon box, and a professional footer with social links & unsubscribe link.
4. Output MUST be ONLY the raw HTML code. Do NOT wrap in markdown code blocks like \`\`\`html or \`\`\`. Start directly with <!DOCTYPE html> or <html> and end with </html>.`;

      let htmlOutput = '';
      let usedEngine = engine;
      let usedModel = '';

      // Determine which engine to use
      const hasNvidia = Boolean(process.env.NVIDIA_API_KEY);
      const hasGemini = Boolean(process.env.GEMINI_API_KEY);

      if (engine === 'nvidia' || (engine === 'auto' && hasNvidia)) {
        usedEngine = 'nvidia';
        usedModel = nvidiaModel || 'meta/llama-3.3-70b-instruct';
        htmlOutput = await generateWithNvidia({ prompt, model: usedModel });
      } else {
        usedEngine = 'gemini';
        usedModel = 'gemini-3.8-flash';
        htmlOutput = await generateWithGemini(prompt);
      }

      // Strip markdown code fences if present
      htmlOutput = htmlOutput
        .replace(/^```html\s*/i, '')
        .replace(/^```\s*/i, '')
        .replace(/\s*```$/i, '')
        .trim();

      return res.json({
        success: true,
        html: htmlOutput,
        engine: usedEngine,
        model: usedModel,
      });
    } catch (error: any) {
      console.error('AI Template Generation error:', error);
      return res.status(500).json({
        success: false,
        error: error.message || 'Failed to generate AI template.',
      });
    }
  });

  // Setup Vite or Static File Serving
  if (!isProduction) {
    const vite = await createViteServer({
      server: { middlewareMode: true },
      appType: 'spa',
    });
    app.use(vite.middlewares);
  } else {
    const distPath = path.resolve(__dirname, 'dist');
    app.use(express.static(distPath));
    app.get('*', (req, res) => {
      res.sendFile(path.resolve(distPath, 'index.html'));
    });
  }

  app.listen(Number(PORT), '0.0.0.0', () => {
    console.log(`FestivaMail server running on http://0.0.0.0:${PORT}`);
  });
}

startServer().catch((err) => {
  console.error('Failed to start server:', err);
  process.exit(1);
});
