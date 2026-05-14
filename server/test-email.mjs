import nodemailer from 'nodemailer';
import dotenv from 'dotenv';
import { resolve } from 'path';

dotenv.config({ path: resolve('.', '.env') });

const user = process.env.GMAIL_USER?.replace(/"/g, '').trim();
const pass = process.env.GMAIL_APP_PASSWORD?.replace(/"/g, '').trim();

console.log('GMAIL_USER:', user || '(NOT SET)');
console.log('GMAIL_APP_PASSWORD:', pass ? `${pass.substring(0, 4)}...${pass.substring(pass.length - 4)}` : '(NOT SET)');

if (!user || !pass) {
  console.error('\n❌ Gmail credentials are missing in .env');
  process.exit(1);
}

console.log('\nCreating transporter...');
const transporter = nodemailer.createTransport({
  service: 'gmail',
  auth: { user, pass },
  connectionTimeout: 10000,
  greetingTimeout: 10000,
  socketTimeout: 15000,
});

console.log('Verifying SMTP connection...');
try {
  await transporter.verify();
  console.log('✅ SMTP connection verified successfully!');
} catch (err) {
  console.error('❌ SMTP verification failed:', err.message);
  console.error('\nPossible causes:');
  console.error('  1. Gmail App Password is invalid or revoked');
  console.error('  2. 2-Step Verification is not enabled on the Gmail account');
  console.error('  3. Google blocked the sign-in (check https://myaccount.google.com/security)');
  process.exit(1);
}

console.log('\nSending test email to chetrysagar122@gmail.com...');
try {
  const info = await transporter.sendMail({
    from: `Rose Valley Academy <${user}>`,
    to: 'chetrysagar122@gmail.com',
    subject: 'RVA Email Test',
    text: 'If you receive this, the email system is working correctly.',
    html: '<p>If you receive this, the email system is <strong>working correctly</strong>.</p>',
  });
  console.log('✅ Email sent successfully! Message ID:', info.messageId);
} catch (err) {
  console.error('❌ Failed to send email:', err.message);
}

process.exit(0);
