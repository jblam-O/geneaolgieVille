import { useEffect, useMemo, useRef, useState } from 'react';
import { Background, Controls, MiniMap, Panel, ReactFlow, ReactFlowProvider, useReactFlow, type NodeMouseHandler } from '@xyflow/react';
import { toPng, toSvg } from 'html-to-image';
import { jsPDF } from 'jspdf';
import { PersonNode } from './components/PersonNode';
import { UnionNode } from './components/UnionNode';
import { FanGuideNode } from './components/FanGuideNode';
import { FanPersonNode } from './components/FanPersonNode';
import { FanChart } from './components/FanChart';
import { buildLayout, type ViewMode } from './layouts/layouts';
import { demoDocument, type GedcomPerson, type GenealogyDocument } from './model/genealogy';
import { initializeOffline, loadOfflineFamily, queueMutation, synchronize, subscribe, pendingCount } from './offline';
import { OfflinePanel } from './components/OfflinePanel';
import './styles.css';
import '@xyflow/react/dist/style.css';

const modes: Array<{ id: ViewMode; label: string }> = [
  { id: 'vertical', label: 'Vertical' }, { id: 'fan', label: 'Circulaire ascendant' }, { id: 'fanDescendants', label: 'Circulaire descendant' }, { id: 'photos', label: 'Photos & fiches' }, { id: 'print', label: 'Impression' },
];

type Family = { id: number | string; name: string };
type RelationType = 'parent' | 'child';
type LifeEvent = { type: string; date?: string; place?: string; note?: string };
type PersonForm = { givenName: string; lastName: string; birthDate: string; birthPlace: string; deathDate: string; deathPlace: string; lifeEvents: LifeEvent[] };
type AncestorSlotLabel = 'Père' | 'Mère';

function ExportMenu({ canvasRef }: { canvasRef: React.RefObject<HTMLDivElement | null> }) {
  const exportImage = async (format: 'png' | 'svg' | 'pdf') => {
    if (!canvasRef.current) return;
    const options = { backgroundColor: '#fbf3e6', pixelRatio: 2 };
    if (format === 'png') download(await toPng(canvasRef.current, options), 'arbre-genealogique.png');
    if (format === 'svg') download(await toSvg(canvasRef.current, options), 'arbre-genealogique.svg');
    if (format === 'pdf') { const url = await toPng(canvasRef.current, options); const pdf = new jsPDF({ orientation: 'landscape', unit: 'px', format: [canvasRef.current.clientWidth, canvasRef.current.clientHeight] }); pdf.addImage(url, 'PNG', 0, 0, canvasRef.current.clientWidth, canvasRef.current.clientHeight); pdf.save('arbre-genealogique.pdf'); }
  };
  return <div className="export-actions"><button onClick={() => exportImage('png')}>PNG</button><button onClick={() => exportImage('svg')}>SVG</button><button onClick={() => exportImage('pdf')}>PDF</button></div>;
}

function download(url: string, name: string) { const link = document.createElement('a'); link.download = name; link.href = url; link.click(); }

function normalizeDocument(document: GenealogyDocument): GenealogyDocument {
  const people = Object.fromEntries(Object.entries(document.people ?? {}).map(([id, person]) => [String(id), { ...person, id: String(person.id) }]));
  const unions = Object.fromEntries(Object.entries(document.unions ?? {}).map(([id, union]) => [String(id), {
    ...union,
    id: String(union.id),
    partnerIds: union.partnerIds.map((personId) => String(personId)),
    children: union.children.map((child) => ({ ...child, personId: String(child.personId) })),
  }]));
  return { ...document, rootPersonId: String(document.rootPersonId ?? Object.keys(people)[0] ?? ''), people, unions };
}

function formFromPerson(person?: GedcomPerson): PersonForm {
  return { givenName: person?.givenName ?? '', lastName: person?.familyName ?? '', birthDate: person?.birth?.date ?? '', birthPlace: person?.birth?.place ?? '', deathDate: person?.death?.date ?? '', deathPlace: person?.death?.place ?? '', lifeEvents: person?.lifeEvents ?? [] };
}

function personPhotos(person?: GedcomPerson): string[] {
  if (!person) return [];
  return person.photoUrls?.length ? person.photoUrls : person.photoUrl ? [person.photoUrl] : [];
}

function dateYear(date?: string): string {
  return date?.trim().slice(0, 4) ?? '';
}

function personOptionLabel(person: GedcomPerson): string {
  const name = `${person.givenName} ${person.familyName}`.trim();
  const birth = dateYear(person.birth?.date);
  const death = dateYear(person.death?.date);
  return birth || death ? `${name} (${birth} - ${death})` : name;
}

function parentSlots(document: GenealogyDocument, personId: string): Array<{ label: string; person?: GedcomPerson }> {
  const parents = Object.values(document.unions)
    .filter((union) => union.children.some((child) => child.personId === personId))
    .flatMap((union) => union.partnerIds.map((parentId) => document.people[parentId]).filter(Boolean));
  const father = parents.find((person) => person.sex === 'M') ?? parents[0];
  const mother = parents.find((person) => person.sex === 'F') ?? parents.find((person) => person.id !== father?.id) ?? parents[1];

  return [
    { label: 'Père', person: father },
    { label: 'Mère', person: mother },
  ];
}

function GenealogyCanvas() {
  const canvasRef = useRef<HTMLDivElement>(null);
  const { fitView, setCenter } = useReactFlow();
  const [document, setDocument] = useState<GenealogyDocument>({ id: '', title: 'Votre généalogie', rootPersonId: '', people: {}, unions: {} });
  const [families, setFamilies] = useState<Family[]>([]);
  const [selectedFamilyId, setSelectedFamilyId] = useState('');
  const [mode, setMode] = useState<ViewMode>('fan');
  const [rootId, setRootId] = useState(demoDocument.rootPersonId);
  const [search, setSearch] = useState('');
  const [collapsed, setCollapsed] = useState<Set<string>>(new Set());
  const [selectedPersonId, setSelectedPersonId] = useState<string>();
  const [showPhotos, setShowPhotos] = useState(false);
  const [personForm, setPersonForm] = useState<PersonForm>(formFromPerson());
  const [relationType, setRelationType] = useState<RelationType>('child');
  const [relationPersonId, setRelationPersonId] = useState('');
  const [relationForm, setRelationForm] = useState<PersonForm>(formFromPerson());
  const [ancestorDialog, setAncestorDialog] = useState<AncestorSlotLabel>();
  const [eventForm, setEventForm] = useState<LifeEvent>({ type: 'Déménagement', date: '', place: '', note: '' });
  const [newFamilyName, setNewFamilyName] = useState('');
  const [showNewFamily, setShowNewFamily] = useState(false);
  const [actionError, setActionError] = useState('');
  const [graph, setGraph] = useState<Awaited<ReturnType<typeof buildLayout>>>({ nodes: [], edges: [] });

  const loadFamily = async (familyId?: string) => {
    const payload = await loadOfflineFamily(familyId);
    setFamilies(payload.families ?? []);
    if (payload.selectedFamilyId) setSelectedFamilyId(String(payload.selectedFamilyId));
    if (payload.document) { const next = normalizeDocument(payload.document); setDocument(next); setRootId(next.rootPersonId); setSelectedPersonId(undefined); setCollapsed(new Set()); }
  };

  useEffect(() => {
    let active = true;
    const refresh = () => loadFamily(new URLSearchParams(window.location.search).get('family') ?? undefined);
    const sync = () => { if (!pendingCount()) return Promise.resolve(); return synchronize().then(() => { if (active) return loadFamily(); }).catch((error) => { if (active) setActionError(error.message); }); };
    initializeOffline().then(refresh).then(sync).catch((error) => setActionError(error.message));
    window.addEventListener('online', sync);
    const timer = window.setInterval(sync, 30000);
    return () => { active = false; window.removeEventListener('online', sync); clearInterval(timer); };
  }, []);
  useEffect(() => { const person = selectedPersonId ? document.people[selectedPersonId] : undefined; setPersonForm(formFromPerson(person)); setEventForm({ type: 'Déménagement', date: '', place: '', note: '' }); setShowPhotos(false); }, [selectedPersonId, document]);
  useEffect(() => {
    let active = true;
    buildLayout(document, mode, rootId, collapsed, search).then((next) => { if (active) { setGraph(next); requestAnimationFrame(() => fitView({ padding: 0.18, duration: 450 })); } });
    return () => { active = false; };
  }, [document, mode, rootId, collapsed, search, fitView]);

  const nodeTypes = useMemo(() => ({ person: PersonNode, fanPerson: FanPersonNode, union: UnionNode, fanGuide: FanGuideNode }), []);
  const selectedPerson = selectedPersonId ? document.people[selectedPersonId] : undefined;
  const selectedPhotos = personPhotos(selectedPerson);
  const selectedParentSlots = selectedPerson ? parentSlots(document, selectedPerson.id) : [];
  const handleNodeClick: NodeMouseHandler = (_, node) => { if (node.data.kind === 'person') setSelectedPersonId(node.id.replace('person:', '')); };
  const handleFanPersonClick = (personId: string) => { setSelectedPersonId(personId); setRootId(personId); };
  const focusSearch = () => { const match = graph.nodes.find((node) => node.data.kind === 'person' && node.data.highlighted); if (match) setCenter(match.position.x + 100, match.position.y + 70, { zoom: 1.25, duration: 600 }); };
  const toggleCollapse = () => { if (!selectedPersonId) return; setCollapsed((current) => { const next = new Set(current); next.has(selectedPersonId) ? next.delete(selectedPersonId) : next.add(selectedPersonId); return next; }); };

  const savePerson = async () => {
    if (!selectedPersonId) return;
    try {
      await queueMutation(`/api/people/${selectedPersonId}`, 'PATCH', personForm, selectedFamilyId);
      await loadFamily(selectedFamilyId);
    } catch (error) { setActionError((error as Error).message); }
  };
  const addLifeEvent = () => {
    if (!eventForm.type.trim() || (!eventForm.date?.trim() && !eventForm.place?.trim() && !eventForm.note?.trim())) return;
    setPersonForm((current) => ({ ...current, lifeEvents: [...current.lifeEvents, { ...eventForm, type: eventForm.type.trim() }] }));
    setEventForm({ type: 'Déménagement', date: '', place: '', note: '' });
  };
  const removeLifeEvent = (index: number) => setPersonForm((current) => ({ ...current, lifeEvents: current.lifeEvents.filter((_, eventIndex) => eventIndex !== index) }));
  const openAncestorDialog = (label: string) => {
    setRelationType('parent');
    setRelationPersonId('');
    setRelationForm(formFromPerson());
    setActionError('');
    setAncestorDialog(label as AncestorSlotLabel);
  };
  const closeAncestorDialog = () => {
    setAncestorDialog(undefined);
    setRelationPersonId('');
    setRelationForm(formFromPerson());
    setActionError('');
  };
  const createRelation = async (type: RelationType = relationType) => {
    if (!selectedPersonId || (!relationPersonId && (!relationForm.givenName.trim() && !relationForm.lastName.trim()))) return;
    setActionError('');
    const sex = type === 'parent' && ancestorDialog ? (ancestorDialog === 'Père' ? 'M' : 'F') : undefined;
    const body = relationPersonId ? { type, personId: relationPersonId, sex } : { type, ...relationForm, sex };
    try {
      await queueMutation(`/api/people/${selectedPersonId}/relations`, 'POST', body, selectedFamilyId);
      closeAncestorDialog(); await loadFamily(selectedFamilyId);
    } catch (error) { setActionError((error as Error).message); }
  };
  const createFamily = async () => {
    if (!newFamilyName.trim()) return;
    try {
      const family = await queueMutation('/api/families', 'POST', { name: newFamilyName }, selectedFamilyId);
      setNewFamilyName(''); setShowNewFamily(false); await loadFamily(String(family.id));
    } catch (error) { setActionError((error as Error).message); }
  };
  const createFirstPerson = async () => {
    if (!selectedFamilyId) return;
    try {
      await queueMutation(`/api/families/${selectedFamilyId}/people`, 'POST', { givenName: 'Nouvelle', lastName: 'Personne' }, selectedFamilyId);
      await loadFamily(selectedFamilyId);
    } catch (error) { setActionError((error as Error).message); }
  };
  useEffect(() => subscribe(() => { if (pendingCount() && navigator.onLine) void synchronize().catch((error) => setActionError(error.message)); }), []);

  return <main className={`genealogy-app genealogy-app--${mode}`}>
    <OfflinePanel onSync={async () => { await synchronize(); await loadFamily(selectedFamilyId); }} />
    {actionError && <p className="action-error" role="alert">{actionError}</p>}
    <header className="app-header"><div><span className="eyebrow">Atelier généalogique</span><h1>{document.title}</h1><p>Construisez votre histoire, personne après personne.</p></div><div className="header-actions"><ExportMenu canvasRef={canvasRef} /></div></header>
    <section className="toolbar" aria-label="Outils de visualisation"><label className="family-picker">Famille<select value={selectedFamilyId} onChange={(event) => { setSelectedFamilyId(event.target.value); loadFamily(event.target.value).catch(() => undefined); }}>{families.map((family) => <option key={family.id} value={family.id}>{family.name}</option>)}</select></label><button className="new-family-button" onClick={() => setShowNewFamily(!showNewFamily)}>+ Nouvelle famille</button><label className="search-box"><span>⌕</span><input value={search} onChange={(event) => setSearch(event.target.value)} onKeyDown={(event) => event.key === 'Enter' && focusSearch()} placeholder="Rechercher une personne" /><kbd>Entrée</kbd></label><label className="root-picker">Point de départ<select value={rootId} onChange={(event) => setRootId(event.target.value)}>{Object.values(document.people).map((person) => <option key={person.id} value={person.id}>{person.givenName} {person.familyName}</option>)}</select></label><nav className="mode-switcher" role="tablist" aria-label="Design de l'arbre">{modes.map((item) => <button key={item.id} className={mode === item.id ? 'is-active' : ''} onClick={() => setMode(item.id)}>{item.label}</button>)}</nav></section>
    {showNewFamily && <section className="inline-form family-create"><input autoFocus value={newFamilyName} onChange={(event) => setNewFamilyName(event.target.value)} onKeyDown={(event) => event.key === 'Enter' && createFamily()} placeholder="Nom de la nouvelle famille" /><button onClick={createFamily}>Créer et ouvrir</button></section>}
    <div className="visualisation-layout"><div className="canvas-wrap" ref={canvasRef}>{mode === 'fan' || mode === 'fanDescendants' ? <FanChart document={document} rootId={rootId} direction={mode === 'fan' ? 'ancestors' : 'descendants'} onPersonClick={handleFanPersonClick} /> : <ReactFlow nodes={graph.nodes} edges={graph.edges} nodeTypes={nodeTypes} onNodeClick={handleNodeClick} fitView minZoom={0.12} maxZoom={2.2} nodesDraggable nodesConnectable={false} proOptions={{ hideAttribution: true }}><Background color="#d9c6aa" gap={32} size={1} /><Controls showInteractive={false} /><MiniMap nodeColor={(node) => node.data.kind === 'union' ? '#c29562' : node.data.kind === 'fanGuide' ? 'transparent' : '#9a5f2d'} pannable zoomable /><Panel position="bottom-left" className="canvas-hint">Sélectionnez une carte pour modifier la personne et ajouter un proche.</Panel></ReactFlow>}</div>
      <aside className={`person-panel ${selectedPerson || Object.keys(document.people).length === 0 ? 'person-panel--open' : ''}`} aria-label="Fiche personne">{selectedPerson ? <><div className="panel-heading"><div><span className="eyebrow">Fiche détaillée</span><h2>{selectedPerson.givenName} {selectedPerson.familyName}</h2></div><button onClick={() => setSelectedPersonId(undefined)} aria-label="Fermer">×</button></div>{selectedPhotos.length > 0 && <div className="panel-section"><div className="panel-section-title"><h3>Photos</h3><button className="text-button" onClick={() => setShowPhotos((current) => !current)}>{showPhotos ? 'Masquer' : `Afficher (${selectedPhotos.length})`}</button></div>{showPhotos && <div className="photo-grid">{selectedPhotos.map((photoUrl, index) => <a key={photoUrl} href={photoUrl} target="_blank" rel="noreferrer"><img src={photoUrl} alt={`${selectedPerson.givenName} ${selectedPerson.familyName} ${index + 1}`} /></a>)}</div>}</div>}<div className="panel-section"><h3>Identité et dates</h3><div className="form-grid"><input value={personForm.givenName} onChange={(event) => setPersonForm({ ...personForm, givenName: event.target.value })} placeholder="Prénom" /><input value={personForm.lastName} onChange={(event) => setPersonForm({ ...personForm, lastName: event.target.value })} placeholder="Nom" /><input type="date" value={personForm.birthDate} onChange={(event) => setPersonForm({ ...personForm, birthDate: event.target.value })} /><input value={personForm.birthPlace} onChange={(event) => setPersonForm({ ...personForm, birthPlace: event.target.value })} placeholder="Lieu de naissance" /><input type="date" value={personForm.deathDate} onChange={(event) => setPersonForm({ ...personForm, deathDate: event.target.value })} /><input value={personForm.deathPlace} onChange={(event) => setPersonForm({ ...personForm, deathPlace: event.target.value })} placeholder="Lieu de décès" /></div><button className="primary-button" onClick={savePerson}>Enregistrer les informations</button></div><div className="panel-section"><h3>Événements de vie</h3><div className="event-list">{personForm.lifeEvents.map((lifeEvent, index) => <div className="event-row" key={`${lifeEvent.type}-${index}`}><div><strong>{lifeEvent.type}</strong><span>{[lifeEvent.date, lifeEvent.place, lifeEvent.note].filter(Boolean).join(' · ')}</span></div><button onClick={() => removeLifeEvent(index)} aria-label={`Supprimer ${lifeEvent.type}`}>×</button></div>)}</div><div className="event-form"><input value={eventForm.type} onChange={(event) => setEventForm({ ...eventForm, type: event.target.value })} placeholder="Type : déménagement, mariage..." /><input type="date" value={eventForm.date} onChange={(event) => setEventForm({ ...eventForm, date: event.target.value })} /><input value={eventForm.place} onChange={(event) => setEventForm({ ...eventForm, place: event.target.value })} placeholder="Lieu" /><input value={eventForm.note} onChange={(event) => setEventForm({ ...eventForm, note: event.target.value })} placeholder="Note" /><button className="secondary-button" onClick={addLifeEvent}>Ajouter l'événement</button></div></div><div className="panel-section"><h3>Branche</h3><div className="ancestor-slots">{selectedParentSlots.map((slot) => slot.person ? <button key={slot.label} className="ancestor-slot ancestor-slot--filled" onClick={() => setSelectedPersonId(slot.person?.id)}><span>{slot.label}</span><strong>{personOptionLabel(slot.person)}</strong></button> : <button key={slot.label} className="ancestor-slot ancestor-slot--empty" onClick={() => openAncestorDialog(slot.label)}><span>{slot.label}</span><strong>+</strong></button>)}</div><div className="relation-actions"><button onClick={toggleCollapse}>{collapsed.has(selectedPerson.id) ? 'Déplier la branche' : 'Replier la branche'}</button></div></div></> : <div className="panel-empty"><span>✦</span><h2>Choisissez une personne</h2><p>Cliquez sur une carte pour ouvrir sa fiche, modifier ses informations ou lui ajouter un ascendant ou un descendant.</p>{actionError && <p className="action-error">{actionError}</p>}{Object.keys(document.people).length === 0 && <button className="primary-button" onClick={createFirstPerson}>Ajouter la première personne</button>}</div>}</aside></div>
    {selectedPerson && ancestorDialog && <div className="relation-modal" role="dialog" aria-modal="true" aria-labelledby="ancestor-dialog-title"><div className="relation-modal__card"><div className="relation-modal__heading"><div><span className="eyebrow">{ancestorDialog}</span><h2 id="ancestor-dialog-title">Ajouter un ascendant</h2></div><button onClick={closeAncestorDialog} aria-label="Fermer">×</button></div><div className="relation-modal__body"><label className="relation-person-picker-label">Personne existante<select className="relation-person-picker" value={relationPersonId} onChange={(event) => setRelationPersonId(event.target.value)}><option value="">Créer une nouvelle personne</option>{Object.values(document.people).filter((person) => person.id !== selectedPerson.id).map((person) => <option key={person.id} value={person.id}>{personOptionLabel(person)}</option>)}</select></label>{!relationPersonId && <div className="form-grid"><input autoFocus value={relationForm.givenName} onChange={(event) => setRelationForm({ ...relationForm, givenName: event.target.value })} placeholder="Prénom" /><input value={relationForm.lastName} onChange={(event) => setRelationForm({ ...relationForm, lastName: event.target.value })} placeholder="Nom" /><input type="date" value={relationForm.birthDate} onChange={(event) => setRelationForm({ ...relationForm, birthDate: event.target.value })} /><input value={relationForm.birthPlace} onChange={(event) => setRelationForm({ ...relationForm, birthPlace: event.target.value })} placeholder="Lieu de naissance" /></div>}{actionError && <p className="action-error">{actionError}</p>}</div><div className="relation-modal__actions"><button className="secondary-button" onClick={closeAncestorDialog}>Annuler</button><button className="primary-button" onClick={() => createRelation('parent')}>{relationPersonId ? 'Lier cette personne' : 'Créer la personne'}</button></div></div></div>}
  </main>;
}

export default function App() { return <ReactFlowProvider><GenealogyCanvas /></ReactFlowProvider>; }
