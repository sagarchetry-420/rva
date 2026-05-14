import { supabase } from '@/integrations/supabase/client';

const API_URL = import.meta.env.VITE_API_URL || '';

async function getAuthHeaders(options: RequestInit = {}): Promise<HeadersInit> {
  // Use getUser() to force a token refresh if expired, then read the updated session
  await supabase.auth.getUser();
  const { data: { session } } = await supabase.auth.getSession();

  const headers: HeadersInit = {
    ...(session?.access_token ? { Authorization: `Bearer ${session.access_token}` } : {})
  };

  // Only add Content-Type for requests with a JSON body (skip for FormData uploads)
  const method = (options.method || 'GET').toUpperCase();
  const isFormData = options.body instanceof FormData;
  if (method !== 'GET' && method !== 'DELETE' && !isFormData) {
    headers['Content-Type'] = 'application/json';
  }

  return headers;
}

async function apiFetch<T>(path: string, options: RequestInit = {}): Promise<T> {
  const headers = await getAuthHeaders(options);
  const response = await fetch(`${API_URL}${path}`, {
    ...options,
    headers: { ...headers, ...options.headers }
  });
  if (!response.ok) {
    let errorMessage = `API error: ${response.status}`;
    let errorBody = {};
    try {
      errorBody = await response.json();
      errorMessage = errorBody.error || errorMessage;
    } catch (e) {
      // Response was not JSON
    }
    console.error(`[API] Error on ${path}:`, { status: response.status, body: errorBody });
    const error = new Error(errorMessage);
    (error as any).status = response.status;
    (error as any).body = errorBody;
    throw error;
  }
  return response.json();
}

export const api = {
  get: <T>(path: string) => apiFetch<T>(path),
  post: <T>(path: string, body: unknown) => apiFetch<T>(path, { method: 'POST', body: JSON.stringify(body) }),
  put: <T>(path: string, body: unknown) => apiFetch<T>(path, { method: 'PUT', body: JSON.stringify(body) }),
  delete: <T>(path: string) => apiFetch<T>(path, { method: 'DELETE' }),
  /** Upload FormData (multipart/form-data) — browser auto-sets Content-Type with boundary */
  upload: <T>(path: string, formData: FormData) => {
    return apiFetch<T>(path, {
      method: 'POST',
      body: formData,
    });
  },
};
