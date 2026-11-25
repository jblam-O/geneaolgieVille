import { useEffect, useRef, useState, useSyncExternalStore } from 'react';
import { getSyncStatus, pendingCount, subscribe, subscribeSync } from '../offline';
interface InstallEvent extends Event { prompt(): Promise<void>; userChoice: Promise<{ outcome: string }> }
export function OfflinePanel({ onSync }: { onSync: () => Promise<void> }) {
  const [online, setOnline] = useState(navigator.onLine);
  const [pending, setPending] = useState(pendingCount());
  const [install, setInstall] = useState<InstallEvent>();
  const [installed, setInstalled] = useState(window.matchMedia('(display-mode: standalone)').matches);
  const [message, setMessage] = useState('');
  const guideRef = useRef<HTMLDialogElement>(null);
  const [guidePlatform, setGuidePlatform] = useState<'ios' | 'android' | 'desktop'>(() => /iPhone|iPad|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1) ? 'ios' : /Android/.test(navigator.userAgent) ? 'android' : 'desktop');
  const { syncing, error: syncError, lastSyncedAt } = useSyncExternalStore(subscribeSync, getSyncStatus);
  const status = !online ? 'offline' : syncing ? 'syncing' : syncError ? 'error' : 'online';
  useEffect(() => {
    const connection = () => setOnline(navigator.onLine);
    const offer = (event: Event) => { event.preventDefault(); setInstall(event as InstallEvent); };
    const complete = () => { setInstalled(true); setInstall(undefined); };
    window.addEventListener('online', connection); window.addEventListener('offline', connection);
    window.addEventListener('beforeinstallprompt', offer); window.addEventListener('appinstalled', complete);
    const unsubscribe = subscribe(() => setPending(pendingCount()));
    return () => { unsubscribe(); window.removeEventListener('online', connection); window.removeEventListener('offline', connection); window.removeEventListener('beforeinstallprompt', offer); window.removeEventListener('appinstalled', complete); };
  }, []);
  return <section className="offline-panel" aria-label="Installation et synchronisation">
    <div className="connection-status" role="status" aria-live="polite" aria-atomic="true">
      <span className={`connection-badge connection-badge--${status}`}><span className="connection-dot" aria-hidden="true" />{!online ? 'Hors connexion' : syncing ? 'Synchronisation en cours…' : 'Connecté'}</span>
      <span className="connection-detail">{!online
        ? `${pending ? `${pending} modification(s) sauvegardée(s) sur cet appareil. ` : ''}Synchronisation automatique au retour du réseau.`
        : syncing
          ? `Envoi de vos données enregistrées sur cet appareil · ${pending} modification(s) restante(s).`
          : syncError
            ? `Synchronisation en attente. ${syncError}`
            : pending
              ? `${pending} modification(s) sauvegardée(s) sur cet appareil, en attente d’envoi.`
              : lastSyncedAt ? 'Toutes les modifications ont été synchronisées.' : 'Aucune modification en attente.'}</span>
    </div>
    {pending > 0 && <button disabled={!online || syncing} onClick={async () => { try { await onSync(); setMessage(''); } catch (error) { setMessage((error as Error).message); } }}>{syncing ? 'Synchronisation…' : 'Synchroniser maintenant'}</button>}
    {!installed && <div><span>Installez l’atelier pour retrouver vos arbres et travailler sans connexion. </span><button onClick={async () => { if (install) { await install.prompt(); await install.userChoice; setInstall(undefined); } else guideRef.current?.showModal(); }}>Installer l’application</button></div>}
    <dialog className="install-guide" ref={guideRef} aria-labelledby="install-guide-title" aria-describedby="install-guide-description" onClick={(event) => { if (event.target === event.currentTarget) guideRef.current?.close(); }}>
      <div className="install-guide__heading"><div><span className="eyebrow">À portée de main</span><h2 id="install-guide-title">Installer l’atelier</h2></div><button type="button" autoFocus aria-label="Fermer le guide d’installation" onClick={() => guideRef.current?.close()}>×</button></div>
      <p id="install-guide-description">Retrouvez votre généalogie sur votre écran d’accueil et travaillez même sans connexion.</p>
      <div className="install-guide__platforms" role="group" aria-label="Votre appareil">{([{ id: 'ios', label: 'iPhone / iPad' }, { id: 'android', label: 'Android' }, { id: 'desktop', label: 'Ordinateur' }] as const).map((platform) => <button type="button" key={platform.id} aria-pressed={guidePlatform === platform.id} onClick={() => setGuidePlatform(platform.id)}>{platform.label}</button>)}</div>
      {guidePlatform === 'ios' ? <>
        <p>Dans <strong>Safari</strong>, suivez ces trois étapes. Si vous êtes dans une autre application, ouvrez d’abord ce site dans Safari.</p>
        <ol className="install-guide__steps">
          <li><div className="install-guide__picture" aria-hidden="true"><span className="install-guide__browser">aA · genealogie.rose-it.fr</span><span className="install-guide__browserbar"><span>‹</span><span>›</span><span className="install-guide__highlight"><svg width="28" height="30" viewBox="0 0 28 30" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M6 13H3v14h22V13h-3M14 20V2m-6 6 6-6 6 6" /></svg></span><span>▢</span></span></div><h3>1. Ouvrez Partager</h3><p>Touchez le carré avec une flèche vers le haut. Selon votre version de Safari, il se trouve dans la barre d’outils ou dans le menu « … ».</p></li>
          <li><div className="install-guide__picture" aria-hidden="true"><span className="install-guide__sheet-handle" /><span className="install-guide__menurow">Copier le lien <span>▧</span></span><span className="install-guide__menurow install-guide__highlight">Sur l’écran d’accueil <span>⊞</span></span></div><h3>2. Choisissez l’écran d’accueil</h3><p>Faites défiler les actions et touchez <strong>Sur l’écran d’accueil</strong>. L’intitulé peut aussi être « Ajouter à l’écran d’accueil ».</p></li>
          <li><div className="install-guide__picture" aria-hidden="true"><span className="install-guide__menurow">Annuler <strong className="install-guide__highlight">Ajouter</strong></span><img src="/genealogy/icon-192.png" width="48" height="48" alt="" /><span>Atelier généalogique</span></div><h3>3. Confirmez l’ajout</h3><p>Touchez <strong>Ajouter</strong>, puis ouvrez l’atelier depuis sa nouvelle icône sur votre écran d’accueil.</p></li>
        </ol><small>Illustrations indicatives : l’emplacement des commandes peut varier selon la version d’iOS.</small>
      </> : guidePlatform === 'android' ? <>
        <p>Dans <strong>Chrome</strong>, ouvrez le menu en haut à droite.</p>
        <ol className="install-guide__steps">
          <li><div className="install-guide__picture" aria-hidden="true"><span className="install-guide__menurow">genealogie.rose-it.fr <strong className="install-guide__highlight">⋮</strong></span></div><h3>1. Ouvrez le menu ⋮</h3><p>Touchez les trois points dans la barre du navigateur.</p></li>
          <li><div className="install-guide__picture" aria-hidden="true"><span className="install-guide__menurow install-guide__highlight">Installer l’application ⊞</span></div><h3>2. Choisissez Installer</h3><p>Touchez <strong>Installer l’application</strong> ou <strong>Ajouter à l’écran d’accueil</strong>, selon votre navigateur.</p></li>
          <li><div className="install-guide__picture" aria-hidden="true"><img src="/genealogy/icon-192.png" width="48" height="48" alt="" /><strong className="install-guide__highlight">Installer</strong></div><h3>3. Confirmez</h3><p>Validez l’installation. Vous retrouverez l’atelier parmi vos applications.</p></li>
        </ol>
      </> : <p>Dans Chrome ou Edge, ouvrez le menu <strong>⋮</strong> ou <strong>…</strong>, puis choisissez l’option pour installer ce site comme application. Dans Safari sur Mac, utilisez <strong>Fichier → Ajouter au Dock</strong>.</p>}
      <p className="install-guide__tip">Avant de partir sans réseau, ouvrez une première fois votre famille avec Internet pour la conserver sur cet appareil.</p>
      <div className="install-guide__footer"><button type="button" onClick={() => guideRef.current?.close()}>J’ai compris</button></div>
    </dialog>
    {message && <p role="status">{message}</p>}
    <small>Les données sont conservées dans ce navigateur. Sur un appareil partagé, utilisez votre propre profil. Effacer les données du navigateur supprime les modifications non synchronisées.</small>
  </section>;
}
