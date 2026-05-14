import emailjs from '@emailjs/nodejs';

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

type TeacherNoticeEmailInput = {
  to: string;
  firstName: string;
  lastName: string;
  noticeStartDate: string;
  lastWorkingDate: string;
};

function getEmailJSConfig():
  | { serviceId: string; templateId: string; noticeTemplateId: string | undefined; publicKey: string; privateKey: string }
  | { error: string } {
  const serviceId = process.env.EMAILJS_SERVICE_ID?.replace(/"/g, '').trim();
  const templateId = process.env.EMAILJS_TEMPLATE_ID?.replace(/"/g, '').trim();
  const noticeTemplateId = process.env.EMAILJS_NOTICE_TEMPLATE_ID?.replace(/"/g, '').trim();
  const publicKey = process.env.EMAILJS_PUBLIC_KEY?.replace(/"/g, '').trim();
  const privateKey = process.env.EMAILJS_PRIVATE_KEY?.replace(/"/g, '').trim();

  if (!serviceId) return { error: 'EMAILJS_SERVICE_ID is not configured' };
  if (!templateId) return { error: 'EMAILJS_TEMPLATE_ID is not configured' };
  if (!publicKey) return { error: 'EMAILJS_PUBLIC_KEY is not configured' };
  if (!privateKey) return { error: 'EMAILJS_PRIVATE_KEY is not configured' };

  return {
    serviceId,
    templateId,
    noticeTemplateId,
    publicKey,
    privateKey,
  };
}

/** Quick sync check — can we attempt to send email? */
export function isEmailConfigured(): boolean {
  const config = getEmailJSConfig();
  return !('error' in config);
}

export async function sendStudentEnrollmentEmail(input: StudentEnrollmentEmailInput): Promise<EmailSendResult> {
  const config = getEmailJSConfig();
  if ('error' in config) {
    return { sent: false, error: config.error };
  }

  const fullName = `${input.firstName} ${input.lastName}`.trim();
  const recipientEmail = input.to.trim();
  
  if (!recipientEmail) {
    return { sent: false, error: 'Recipient email is required' };
  }

  try {
    const response = await emailjs.send(
      config.serviceId,
      config.templateId,
      {
        user_name: fullName,
        user_email: input.loginEmail,
        user_password: input.temporaryPassword,
        user_type: 'Student',
        to_email: recipientEmail,
      },
      {
        publicKey: config.publicKey,
        privateKey: config.privateKey,
      }
    );

    return { sent: true, messageId: response.text };
  } catch (err: any) {
    const message = err.text || err.message || 'Failed to send enrollment email via EmailJS';
    return { sent: false, error: message };
  }
}

export async function sendTeacherEnrollmentEmail(input: TeacherEnrollmentEmailInput): Promise<EmailSendResult> {
  const config = getEmailJSConfig();
  if ('error' in config) {
    return { sent: false, error: config.error };
  }

  const fullName = `${input.firstName} ${input.lastName}`.trim();
  const recipientEmail = input.to.trim();
  
  if (!recipientEmail) {
    return { sent: false, error: 'Recipient email is required' };
  }

  try {
    const response = await emailjs.send(
      config.serviceId,
      config.templateId,
      {
        user_name: fullName,
        user_email: input.loginEmail,
        user_password: input.temporaryPassword,
        user_type: 'Teacher',
        to_email: recipientEmail,
      },
      {
        publicKey: config.publicKey,
        privateKey: config.privateKey,
      }
    );

    return { sent: true, messageId: response.text };
  } catch (err: any) {
    const message = err.text || err.message || 'Failed to send enrollment email via EmailJS';
    return { sent: false, error: message };
  }
}

export async function sendTeacherNoticeEmail(input: TeacherNoticeEmailInput): Promise<EmailSendResult> {
  const config = getEmailJSConfig();
  if ('error' in config) {
    return { sent: false, error: config.error };
  }

  if (!config.noticeTemplateId) {
    return { sent: false, error: 'EMAILJS_NOTICE_TEMPLATE_ID is not configured' };
  }

  const fullName = `${input.firstName} ${input.lastName}`.trim();
  const recipientEmail = input.to.trim();
  
  if (!recipientEmail) {
    return { sent: false, error: 'Recipient email is required' };
  }

  try {
    const response = await emailjs.send(
      config.serviceId,
      config.noticeTemplateId,
      {
        first_name: input.firstName,
        last_name: input.lastName,
        notice_start_date: input.noticeStartDate,
        last_working_date: input.lastWorkingDate,
        to_email: recipientEmail,
      },
      {
        publicKey: config.publicKey,
        privateKey: config.privateKey,
      }
    );

    return { sent: true, messageId: response.text };
  } catch (err: any) {
    const message = err.text || err.message || 'Failed to send notice email via EmailJS';
    return { sent: false, error: message };
  }
}
