import type { GenealogyDocument, GedcomPerson } from './model/genealogy';
import { demoDocument } from './model/genealogy';

type Payload = { families: { id: number | string; name: string }[]; selectedFamilyId: number | string | null; document: GenealogyDocument | null };
type Operation = { id: string; url: string; method: string; body: Record<string, unknown>; temporaryId?: string };
type Store = { documents: Record<string, Payload>; queue: Operation[]; ids: Record<string, string> };
let account = '';
let state: Store = { documents: {}, queue: [], ids: {} };
let busy: Promise<void> | undefined;
let serial = Promise.resolve();
const syncListeners = new Set<() => void>();
let syncStatus: { syncing: boolean; error: string; lastSyncedAt?: number } = { syncing: false, error: '' };
export const getSyncStatus = () => syncStatus;
export const subscribeSync = (listener: () => void) => { syncListeners.add(listener); return () => { syncListeners.delete(listener); }; };
function updateSyncStatus(next: typeof syncStatus) { syncStatus = next; syncListeners.forEach((listener) => listener()); }
const listeners = new Set<() => void>();
export const subscribe = (listener: () => void) => { listeners.add(listener); return () => { listeners.delete(listener); }; };
export const pendingCount = () => state.queue.length;
const emit = () => listeners.forEach((listener) => listener());
const database = () => new Promise<IDBDatabase>((resolve, reject) => { const request = indexedDB.open('genealogy-offline', 1); request.onupgradeneeded = () => request.result.createObjectStore('accounts'); request.onsuccess = () => resolve(request.result); request.onerror = () => reject(request.error); });
async function persist() {
  const db = await database();
  await new Promise<void>((resolve, reject) => { const tx = db.transaction('accounts', 'readwrite'); tx.objectStore('accounts').put(state, account); tx.oncomplete = () => resolve(); tx.onerror = () => reject(tx.error); tx.onabort = () => reject(tx.error); });
  db.close(); emit();
}
export async function initializeOffline() {
  account = window.document.getElementById('genealogy-root')?.dataset.account ?? '';
  if (!account && import.meta.env.DEV) {
    account = 'local-demo';
    state = {
      documents: {
        demo: {
          families: [{ id: 'demo', name: demoDocument.title }],
          selectedFamilyId: 'demo',
          document: { ...structuredClone(demoDocument), rootPersonId: 'p-iris' },
        },
      },
      queue: [],
      ids: {},
    };
    return;
  }
  if (!account) throw new Error('Connectez-vous pour préparer le mode hors connexion.');
  const db = await database();
  state = await new Promise<Store>((resolve, reject) => { const request = db.transaction('accounts').objectStore('accounts').get(account); request.onsuccess = () => resolve(request.result ?? state); request.onerror = () => reject(request.error); });
  db.close(); emit();
}
function resolved(value: string) { return state.ids[value] ?? value; }
function localPerson(body: Record<string, unknown>, id: string): GedcomPerson {
  return { id, givenName: String(body.givenName || 'Nouvelle'), familyName: String(body.lastName || 'Personne'), sex: body.sex as GedcomPerson['sex'], birth: { date: String(body.birthDate ?? ''), place: String(body.birthPlace ?? '') }, death: { date: String(body.deathDate ?? ''), place: String(body.deathPlace ?? '') }, lifeEvents: body.lifeEvents as GedcomPerson['lifeEvents'] };
}
export async function loadOfflineFamily(familyId?: string): Promise<Payload> {
  const id = familyId ? (state.documents[resolved(familyId)] ? resolved(familyId) : familyId) : Object.keys(state.documents)[0];
  if (navigator.onLine && !state.queue.length) {
    try {
      const response = await fetch(`/api/genealogy${id ? `?family=${encodeURIComponent(id)}` : ''}`, { headers: { Accept: 'application/json' }, cache: 'no-store' });
      if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) throw new Error('Session expirée. Reconnectez-vous.');
      const payload: Payload = await response.json();
      state.documents[String(payload.selectedFamilyId ?? '')] = payload;
      await persist(); return payload;
    } catch (error) { if (!state.documents[id ?? '']) throw error; }
  }
  const payload = state.documents[id ?? ''];
  if (!payload) throw new Error('Ouvrez cette famille une première fois avec Internet pour la rendre disponible hors connexion.');
  return structuredClone(payload);
}
export async function queueMutation(url: string, method: string, body: Record<string, unknown>, familyId: string) {
  const task = serial.then(async () => {
    const previous = structuredClone(state);
    const temporaryId = method === 'POST' && !body.personId ? `local-${crypto.randomUUID()}` : undefined;
    url = url.split('/').map(resolved).join('/');
    if (body.personId) body = { ...body, personId: resolved(String(body.personId)) };
    const operation: Operation = { id: crypto.randomUUID(), url, method, body, temporaryId };
    const payload = state.documents[resolved(familyId)] ?? state.documents[familyId];
    let resultId = temporaryId;
    if (url === '/api/families') {
      const family = { id: temporaryId!, name: String(body.name) };
      const families = [...(payload?.families ?? Object.values(state.documents)[0]?.families ?? []), family];
      Object.values(state.documents).forEach((item) => { item.families = families; });
      state.documents[family.id] = { families, selectedFamilyId: family.id, document: { id: `family-${family.id}`, title: family.name, rootPersonId: '', people: {}, unions: {} } };
    } else {
      if (!payload?.document) throw new Error('Famille indisponible sur cet appareil.');
      const doc = payload.document;
      const personId = url.split('/')[3];
      if (method === 'PATCH') doc.people[personId] = { ...doc.people[personId], ...localPerson(body, personId), sex: doc.people[personId].sex };
      else {
        resultId = String(body.personId ?? temporaryId);
        if (!body.personId) doc.people[resultId] = localPerson(body, resultId);
        if (!doc.rootPersonId) doc.rootPersonId = resultId;
        if (url.endsWith('/relations')) {
          const child = body.type === 'parent' ? personId : resultId;
          const parent = body.type === 'parent' ? resultId : personId;
          const union = Object.values(doc.unions).find((item) => item.children.some((item) => String(item.personId) === child));
          if (union) { if (!union.partnerIds.map(String).includes(parent)) union.partnerIds.push(parent); }
          else doc.unions[`local-${operation.id}`] = { id: `local-${operation.id}`, partnerIds: [parent], children: [{ personId: child, relationship: 'biological' }] };
        }
      }
    }
    state.queue.push(operation);
    try { await persist(); } catch (error) { state = previous; throw error; }
    return { id: resultId };
  });
  serial = task.then(() => undefined, () => undefined);
  return task;
}
export function synchronize(): Promise<void> {
  if (busy) return busy;
  busy = (async () => {
    await serial;
    if (!navigator.onLine || !state.queue.length) return;
    updateSyncStatus({ ...syncStatus, syncing: true, error: '' });
    const session = await fetch('/api/offline-session', { cache: 'no-store', headers: { Accept: 'application/json' } });
    if (!session.ok || !session.headers.get('content-type')?.includes('application/json') || (await session.json()).account !== account) throw new Error('Reconnectez-vous au même compte pour synchroniser.');
    while (state.queue.length) {
      const operation = state.queue[0];
      const url = operation.url.split('/').map(resolved).join('/');
      const body = { ...operation.body };
      if (body.personId) body.personId = resolved(String(body.personId));
      const response = await fetch('/api/offline-sync', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ...operation, url, body, account }) });
      const result = await response.json().catch(() => null);
      if (!response.ok) throw new Error(result?.error ?? 'Synchronisation interrompue. Vos données restent sur cet appareil.');
      if (operation.temporaryId) state.ids[operation.temporaryId] = String(result.id);
      state.queue.shift();
      remapDocuments();
      await persist();
    }
    remapDocuments();
    await persist();
    updateSyncStatus({ syncing: false, error: '', lastSyncedAt: Date.now() });
  })().catch((error: unknown) => {
    updateSyncStatus({ ...syncStatus, syncing: false, error: error instanceof Error ? error.message : 'Synchronisation interrompue.' });
    throw error;
  }).finally(() => { busy = undefined; });
  return busy;
}

function remapDocuments() {
    // Remap local identifiers without touching user-entered text.
    for (const [key, payload] of Object.entries(state.documents)) {
      payload.selectedFamilyId = resolved(String(payload.selectedFamilyId));
      payload.families = payload.families.map((family) => ({ ...family, id: resolved(String(family.id)) }));
      if (payload.document) {
        const doc = payload.document;
        doc.rootPersonId = resolved(doc.rootPersonId);
        doc.people = Object.fromEntries(Object.entries(doc.people).map(([id, person]) => [resolved(id), { ...person, id: resolved(id) }]));
        Object.values(doc.unions).forEach((union) => { union.partnerIds = union.partnerIds.map((id) => resolved(String(id))); union.children.forEach((child) => { child.personId = resolved(String(child.personId)); }); });
      }
      if (resolved(key) !== key) { state.documents[resolved(key)] = payload; delete state.documents[key]; }
    }
}
