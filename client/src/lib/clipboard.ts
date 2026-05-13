/**
 * Copy text to clipboard using the modern Clipboard API
 * @param text - The text to copy
 * @returns Promise<boolean> - True if successful, false otherwise
 */
export async function copyToClipboard(text: string): Promise<boolean> {
  try {
    if (navigator.clipboard && window.isSecureContext) {
      // Use modern Clipboard API if available (HTTPS only)
      await navigator.clipboard.writeText(text);
      return true;
    } else {
      // Fallback for non-HTTPS or older browsers
      const textArea = document.createElement('textarea');
      textArea.value = text;
      textArea.style.position = 'fixed';
      textArea.style.left = '-999999px';
      textArea.style.top = '-999999px';
      document.body.appendChild(textArea);

      const success = document.execCommand('copy');
      textArea.remove();

      return success;
    }
  } catch (error) {
    console.error('Failed to copy to clipboard:', error);
    return false;
  }
}
