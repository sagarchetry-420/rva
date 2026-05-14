import nodemailer from 'nodemailer';

const ENROLLMENT_SENDER_EMAIL = 'rvasupport@gmail.com';

type StudentEnrollmentEmailInput = {
  to: string;
  firstName: string;
  lastName: string;
  loginEmail: string;
  temporaryPassword: string;
};

type TeacherEnrollmentEmailInput = {
  to: string;
  firstName: string;
  lastName: string;
  loginEmail: string;
  temporaryPassword: string;
};

type EmailSendResult = {
  sent: boolean;
  error?: string;
  messageId?: string;
};

function getGmailConfig():
  { user: string; appPassword: string; from: string; replyTo: string }
  | { error: string } {
  const user = process.env.GMAIL_USER?.trim() || ENROLLMENT_SENDER_EMAIL;
  const appPassword = process.env.GMAIL_APP_PASSWORD?.trim();
  if (!appPassword) {
    return { error: 'GMAIL_APP_PASSWORD is not configured' };
  }

  return {
    user,
    appPassword,
    from: `Rose Valley Academy <${user}>`,
    replyTo: user,
  };
}

export async function sendStudentEnrollmentEmail(input: StudentEnrollmentEmailInput): Promise<EmailSendResult> {
  const smtpConfig = getGmailConfig();
  if ('error' in smtpConfig) {
    return { sent: false, error: smtpConfig.error };
  }

  const fullName = `${input.firstName} ${input.lastName}`.trim();
  const recipientEmail = input.to.trim();
  if (!recipientEmail) {
    return { sent: false, error: 'Recipient email is required' };
  }

  const textBody = [
    `Hi ${fullName},`,
    '',
    'Welcome to Rose Valley Academy. Your enrollment is confirmed.',
    '',
    'Your login credentials:',
    `Name: ${fullName}`,
    `Email: ${input.loginEmail}`,
    `Temporary Password: ${input.temporaryPassword}`,
    '',
    'Note: Student should change password after first login.',
    '',
    'Regards,',
    'Rose Valley Academy',
  ].join('\n');

  const htmlBody = `
    <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #111827; max-width: 640px; margin: 0 auto;">
      <h2 style="margin-bottom: 8px;">Welcome to Rose Valley Academy</h2>
      <p style="margin-top: 0;">Hi ${fullName}, your enrollment is confirmed.</p>
      <p>Please use the following credentials to sign in:</p>
      <table cellpadding="8" cellspacing="0" style="border-collapse: collapse; width: 100%; max-width: 520px;">
        <tr>
          <td style="border: 1px solid #e5e7eb; font-weight: 600; width: 180px;">Name</td>
          <td style="border: 1px solid #e5e7eb;">${fullName}</td>
        </tr>
        <tr>
          <td style="border: 1px solid #e5e7eb; font-weight: 600;">Email</td>
          <td style="border: 1px solid #e5e7eb;">${input.loginEmail}</td>
        </tr>
        <tr>
          <td style="border: 1px solid #e5e7eb; font-weight: 600;">Temporary Password</td>
          <td style="border: 1px solid #e5e7eb;">${input.temporaryPassword}</td>
        </tr>
      </table>
      <p style="margin-top: 16px; font-weight: 600;">Note: Student should change password after first login.</p>
      <p>Regards,<br />Rose Valley Academy</p>
    </div>
  `;

  try {
    const transporter = nodemailer.createTransport({
      service: 'gmail',
      auth: {
        user: smtpConfig.user,
        pass: smtpConfig.appPassword,
      },
    });

    const payload: {
      from: string;
      to: string;
      subject: string;
      html: string;
      text: string;
      replyTo: string;
    } = {
      from: smtpConfig.from,
      to: recipientEmail,
      subject: 'Your Rose Valley Academy login credentials',
      html: htmlBody,
      text: textBody,
      replyTo: smtpConfig.replyTo,
    };

    const info = await transporter.sendMail(payload);
    return { sent: true, messageId: info.messageId };
  } catch (err) {
    const message = err instanceof Error ? err.message : 'Failed to send enrollment email';
    return { sent: false, error: message };
  }
}

export async function sendTeacherEnrollmentEmail(input: TeacherEnrollmentEmailInput): Promise<EmailSendResult> {
  const smtpConfig = getGmailConfig();
  if ('error' in smtpConfig) {
    return { sent: false, error: smtpConfig.error };
  }

  const fullName = `${input.firstName} ${input.lastName}`.trim();
  const recipientEmail = input.to.trim();
  if (!recipientEmail) {
    return { sent: false, error: 'Recipient email is required' };
  }

  const textBody = [
    `Hi ${fullName},`,
    '',
    'Welcome to Rose Valley Academy. Your teacher account is confirmed.',
    '',
    'Your login credentials:',
    `Name: ${fullName}`,
    `Email: ${input.loginEmail}`,
    `Temporary Password: ${input.temporaryPassword}`,
    '',
    'Note: Teacher should change password after first login.',
    '',
    'Regards,',
    'Rose Valley Academy',
  ].join('\n');

  const htmlBody = `
    <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #111827; max-width: 640px; margin: 0 auto;">
      <h2 style="margin-bottom: 8px;">Welcome to Rose Valley Academy</h2>
      <p style="margin-top: 0;">Hi ${fullName}, your teacher account is confirmed.</p>
      <p>Please use the following credentials to sign in:</p>
      <table cellpadding="8" cellspacing="0" style="border-collapse: collapse; width: 100%; max-width: 520px;">
        <tr>
          <td style="border: 1px solid #e5e7eb; font-weight: 600; width: 180px;">Name</td>
          <td style="border: 1px solid #e5e7eb;">${fullName}</td>
        </tr>
        <tr>
          <td style="border: 1px solid #e5e7eb; font-weight: 600;">Email</td>
          <td style="border: 1px solid #e5e7eb;">${input.loginEmail}</td>
        </tr>
        <tr>
          <td style="border: 1px solid #e5e7eb; font-weight: 600;">Temporary Password</td>
          <td style="border: 1px solid #e5e7eb;">${input.temporaryPassword}</td>
        </tr>
      </table>
      <p style="margin-top: 16px; font-weight: 600;">Note: Teacher should change password after first login.</p>
      <p>Regards,<br />Rose Valley Academy</p>
    </div>
  `;

  try {
    const transporter = nodemailer.createTransport({
      service: 'gmail',
      auth: {
        user: smtpConfig.user,
        pass: smtpConfig.appPassword,
      },
    });

    const payload: {
      from: string;
      to: string;
      subject: string;
      html: string;
      text: string;
      replyTo: string;
    } = {
      from: smtpConfig.from,
      to: recipientEmail,
      subject: 'Your Rose Valley Academy login credentials',
      html: htmlBody,
      text: textBody,
      replyTo: smtpConfig.replyTo,
    };

    const info = await transporter.sendMail(payload);
    return { sent: true, messageId: info.messageId };
  } catch (err) {
    const message = err instanceof Error ? err.message : 'Failed to send enrollment email';
    return { sent: false, error: message };
  }
}
