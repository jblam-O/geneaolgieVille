const ts = require('typescript');
const vm = require('node:vm');
const fs = require('node:fs');
const assert = require('node:assert/strict');
const records = new Map();
const indexedDB = { open() {
  const request = {};
  queueMicrotask(() => {
    request.result = { close() {}, transaction() {
      const tx = { objectStore() { return {
        get(key) { const read = {}; queueMicrotask(() => { read.result = structuredClone(records.get(key)); read.onsuccess(); }); return read; },
        put(value, key) { records.set(key, structuredClone(value)); queueMicrotask(() => tx.oncomplete()); }
      }; } }; return tx;
    } }; request.onsuccess();
  }); return request;
} };
let online = false;
let account = 'alice';
let calls = [];
let fail = false;
const context = { exports: {}, indexedDB, structuredClone, crypto: require('node:crypto').webcrypto, navigator: { get onLine() { return online; } }, window: { document: { getElementById() { return { dataset: { account: 'alice' } }; } } }, fetch: async (url, options) => {
  if (url === '/api/offline-session') return { ok: true, headers: new Headers({ 'content-type': 'application/json' }), json: async () => ({ account }) };
  if (url === '/api/offline-sync') {
    const operation = JSON.parse(options.body); calls.push(operation);
    if (fail) throw Error('Network lost');
    return { ok: true, json: async () => ({ id: calls.length }) };
  }
  throw Error('Offline');
} };
vm.runInNewContext(ts.transpileModule(fs.readFileSync('frontend/src/offline.ts', 'utf8'), { compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022 } }).outputText, context);
(async () => {
 const api = context.exports;
 const statuses = [];
 const unsubscribeSync = api.subscribeSync(() => statuses.push({ ...api.getSyncStatus() }));
 await api.initializeOffline();
 const family = await api.queueMutation('/api/families', 'POST', { name: 'Famille test' }, '');
 const person = await api.queueMutation(`/api/families/${family.id}/people`, 'POST', { givenName: 'Anne', lastName: 'Test' }, family.id);
 await api.queueMutation(`/api/people/${person.id}`, 'PATCH', { givenName: 'Anna', lastName: 'Test', lifeEvents: [] }, family.id);
 assert.equal(api.pendingCount(), 3);
 assert.equal((await api.loadOfflineFamily(family.id)).document.people[person.id].givenName, 'Anna');
 await api.initializeOffline(); assert.equal(api.pendingCount(), 3, 'queue survives reload');
 online = true; account = 'bob';
 await assert.rejects(api.synchronize(), /même compte/); assert.equal(calls.length, 0);
 assert.equal(api.getSyncStatus().syncing, false);
 assert.match(api.getSyncStatus().error, /même compte/);
 account = 'alice'; fail = true;
 await assert.rejects(api.synchronize(), /Network lost/); assert.equal(api.pendingCount(), 3);
 const retryId = calls[0].id; calls = []; fail = false;
 await api.synchronize();
 assert.equal(calls[0].id, retryId, 'retry retains idempotency key');
 assert.equal(calls[1].url, '/api/families/1/people');
 assert.equal(calls[2].url, '/api/people/2');
 assert.equal(api.pendingCount(), 0);
 assert.ok(statuses.some(status => status.syncing), 'automatic sync emits in-progress state');
 assert.equal(api.getSyncStatus().syncing, false);
 assert.equal(api.getSyncStatus().error, '');
 assert.ok(api.getSyncStatus().lastSyncedAt, 'successful sync records completion');
 unsubscribeSync();
 online = false;
 assert.equal((await api.loadOfflineFamily(family.id)).document.people['2'].givenName, 'Anna');
 console.log('PASS: durable queue, offline edits, account isolation, network retry, dependent IDs and cache remapping');
})().catch(error => { console.error(error); process.exitCode = 1; });
