import React from 'react';
import { X, CheckCircle, Clock, ShieldCheck, Mail, Sparkles, AlertCircle } from 'lucide-react';

interface GuideModalProps {
  isOpen: boolean;
  onClose: () => void;
}

export const GuideModal: React.FC<GuideModalProps> = ({ isOpen, onClose }) => {
  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fade-in">
      <div className="relative w-full max-w-3xl max-h-[90vh] overflow-y-auto bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl text-slate-200 p-6 md:p-8">
        
        {/* Close Button */}
        <button
          onClick={onClose}
          className="absolute top-5 right-5 p-2 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition"
        >
          <X className="w-5 h-5" />
        </button>

        {/* Modal Header */}
        <div className="flex items-center gap-3 mb-6 border-b border-slate-800 pb-4">
          <div className="p-3 bg-gradient-to-tr from-sky-500 via-indigo-500 to-emerald-500 rounded-xl text-white shadow-lg shadow-sky-500/20">
            <Sparkles className="w-6 h-6" />
          </div>
          <div>
            <h2 className="text-xl md:text-2xl font-bold text-white">
              मेलफ्लो प्रो ईमेल कैम्पेन गाइड (MailFlow Pro Campaign Guide)
            </h2>
            <p className="text-sm text-slate-400">
              How to create custom HTML emails (Festivals, Newsletters, Invoices & Outreach) & run 5-minute staggered campaigns
            </p>
          </div>
        </div>

        {/* Body Content */}
        <div className="space-y-6 text-sm md:text-base leading-relaxed text-slate-300">
          
          {/* Step 1 */}
          <div className="p-4 rounded-xl bg-slate-850 border border-slate-800">
            <h3 className="flex items-center gap-2 text-base md:text-lg font-semibold text-amber-400 mb-2">
              <Mail className="w-5 h-5" /> 1. मनचाहा HTML कोड या रेडीमेड टेम्पलेट (Custom HTML Template)
            </h3>
            <p className="text-slate-300">
              आप <strong className="text-white">Email Template Studio</strong> में अपना कोई भी raw HTML कोड पेस्ट कर सकते हैं, 
              या पहले से बने खूबसूरत <strong className="text-amber-300">Diwali, Eid, Christmas, Holi</strong> टेम्पलेट्स चुन सकते हैं। 
              साथ ही <em>AI Generate</em> बटन से चुटकियों में नया टेम्पलेट बनवा सकते हैं।
            </p>
            <div className="mt-3 flex flex-wrap gap-2 text-xs font-mono bg-slate-900 p-2.5 rounded-lg border border-slate-750">
              <span className="text-emerald-400 font-semibold">उपलब्ध वेरिएबल्स:</span>
              <span className="bg-slate-800 px-2 py-0.5 rounded text-amber-300">{'{{name}}'} → ग्राहक का नाम</span>
              <span className="bg-slate-800 px-2 py-0.5 rounded text-sky-300">{'{{company}}'} → आपकी कंपनी का नाम</span>
              <span className="bg-slate-800 px-2 py-0.5 rounded text-pink-300">{'{{discount}}'} → डिस्काउंट कोड</span>
              <span className="bg-slate-800 px-2 py-0.5 rounded text-emerald-300">{'{{email}}'} → रिसीवर ईमेल</span>
            </div>
          </div>

          {/* Step 2: Excel Upload & Custom Variables */}
          <div className="p-4 rounded-xl bg-slate-850 border border-slate-800">
            <h3 className="flex items-center gap-2 text-base md:text-lg font-semibold text-emerald-400 mb-2">
              <CheckCircle className="w-5 h-5 text-emerald-400" /> 2. एक्सेल फाइल अपलोड और कस्टम वेरिएबल्स (Excel & Custom Variables)
            </h3>
            <p className="text-slate-300 mb-2">
              हाथ से टाइप करने की कोई आवश्यकता नहीं है! आप सीधे <strong className="text-white">.xlsx, .xls या .csv</strong> फाइल अपलोड कर सकते हैं:
            </p>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs mb-3">
              <div className="p-2 bg-slate-900 rounded border border-slate-750">
                <span className="text-emerald-400 font-bold block mb-0.5">⭐️ Email (अनिवार्य / Required)</span>
                <span className="text-slate-400">कॉलम नाम: <code>Email</code>, <code>Mail</code>, या <code>ईमेल</code></span>
              </div>
              <div className="p-2 bg-slate-900 rounded border border-slate-750">
                <span className="text-sky-400 font-bold block mb-0.5">⭐️ Name (नाम)</span>
                <span className="text-slate-400">कॉलम नाम: <code>Name</code>, <code>Full Name</code> (टैग: {'{{name}}'})</span>
              </div>
            </div>
            <p className="text-slate-300 text-xs">
              <strong className="text-amber-300">कस्टम वेरिएबल्स की आज़ादी:</strong> आपकी एक्सेल फाइल में यदि <code>City</code>, <code>Gift Item</code>, <code>Phone</code>, या कोई अन्य कॉलम है, तो सिस्टम स्वतः उसे पहचानकर टेम्पलेट टैग (जैसे <code>{'{{city}}'}</code>, <code>{'{{gift_item}}'}</code>) बना देता है! आप <strong className="text-white">"Excel Format"</strong> बटन से तैयार फॉर्मेट भी डाउनलोड कर सकते हैं।
            </p>
          </div>

          {/* Step 3: 5-Minute Staggering Explanation */}
          <div className="p-4 rounded-xl bg-slate-850 border border-slate-800">
            <h3 className="flex items-center gap-2 text-base md:text-lg font-semibold text-sky-400 mb-2">
              <Clock className="w-5 h-5" /> 3. हर 5 मिनट में ईमेल क्यों जाता है? (5-Minute Throttling)
            </h3>
            <p className="text-slate-300 mb-2">
              जब कोई कंपनी एक ही सेकंड में सैकड़ों या हजारों ईमेल भेजती है, तो <strong>Gmail, Yahoo, Outlook</strong> के स्पैम फिल्टर्स 
              उसे स्पैम या बॉट मानकर ब्लॉक कर देते हैं और ईमेल इनबॉक्स के बजाय स्पैम फोल्डर में चला जाता है।
            </p>
            <p className="text-slate-300">
              <strong className="text-white">FestivaMail का ऑटोमेटेड डिस्पैचर</strong> हर 5 मिनट (300 सेकंड) का गैप रखकर एक-एक यूजर को ईमेल भेजता है। 
              इससे ईमेल सर्वर पर कोई लोड नहीं आता और 100% ईमेल ग्राहक के <strong>Primary Inbox</strong> में पहुंचते हैं।
            </p>
            <div className="mt-2 text-xs text-sky-300 bg-sky-950/40 p-2 rounded border border-sky-800/40 flex items-center gap-2">
              <CheckCircle className="w-4 h-4 shrink-0" />
              <span>नोट: यदि आप टेस्टिंग कर रहे हैं, तो आप इंटरवल को 1 मिनट या 30 सेकंड भी सेट कर सकते हैं या 'Send Next Now' दबा सकते हैं!</span>
            </div>
          </div>

          {/* Step 3: Recipients & Delivery */}
          <div className="p-4 rounded-xl bg-slate-850 border border-slate-800">
            <h3 className="flex items-center gap-2 text-base md:text-lg font-semibold text-emerald-400 mb-2">
              <ShieldCheck className="w-5 h-5" /> 3. ईमेल डिलीवरी सेटिंग्स (Simulation vs Real SMTP)
            </h3>
            <ul className="space-y-2 list-disc list-inside text-slate-300">
              <li>
                <strong className="text-white">Sandbox / Simulation Mode (बाय डिफ़ॉल्ट):</strong> बिना किसी पासवर्ड या क्रेडेंशियल के पूरी तरह सुरक्षित तरीके से पूरे 5-मिनट डिस्पैच फ्लो, टाइमर, और डिलीवरी लॉग्स का अभ्यास करें।
              </li>
              <li>
                <strong className="text-white">Real SMTP Mode:</strong> अगर आप असली ईमेल भेजना चाहते हैं, तो Settings में जाकर अपना <strong className="text-emerald-300">Elastic Email (सबसे सस्ता), Gmail App Password, Brevo, SendGrid, या Custom SMTP</strong> दर्ज करें।
              </li>
            </ul>
          </div>

          {/* Step 4: Controls */}
          <div className="p-4 rounded-xl bg-slate-850 border border-slate-800">
            <h3 className="flex items-center gap-2 text-base md:text-lg font-semibold text-purple-400 mb-2">
              <AlertCircle className="w-5 h-5" /> 4. आसान कैम्पेन नियंत्रण (Full Campaign Controls)
            </h3>
            <p className="text-slate-300">
              कैम्पेन शुरू करने के बाद आप कभी भी <strong>Pause</strong> कर सकते हैं, <strong>Resume</strong> कर सकते हैं, 
              अगला ईमेल तुरंत भेजने के लिए <strong>Send Next Now</strong> दबा सकते हैं, और पूरा कैम्पेन खत्म होने पर 
              <strong>CSV रिपोर्ट डाउनलोड</strong> कर सकते हैं।
            </p>
          </div>

        </div>

        {/* Modal Footer */}
        <div className="mt-6 pt-4 border-t border-slate-800 flex justify-end">
          <button
            onClick={onClose}
            className="px-6 py-2.5 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-semibold rounded-xl shadow-lg transition"
          >
            समझ गया, कैम्पेन शुरू करें! (Got it, Let's Start)
          </button>
        </div>

      </div>
    </div>
  );
};
