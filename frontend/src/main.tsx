import { createRoot } from 'react-dom/client';
import App from './App';

const root = document.getElementById('genealogy-root');
if (root) createRoot(root).render(<App />);

if ('serviceWorker' in navigator && window.isSecureContext) {
  navigator.serviceWorker.register('/sw.js').then(async () => {
    const registration = await navigator.serviceWorker.ready;
    const account = root?.dataset.account;
    if (account) registration.active?.postMessage({ type: 'account', account });
  }).catch(() => console.warn('Le mode hors connexion n’a pas pu être préparé.'));
}
