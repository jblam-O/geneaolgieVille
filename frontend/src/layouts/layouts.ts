import ELK from 'elkjs/lib/elk.bundled.js';
import type { Edge, Node } from '@xyflow/react';
import type { DisplayPerson, GenealogyDocument, GedcomUnion } from '../model/genealogy';
import { displayPerson } from '../model/genealogy';

export type ViewMode = 'vertical' | 'horizontal' | 'ancestors' | 'descendants' | 'radial' | 'fan' | 'fanDescendants' | 'timeline' | 'print' | 'photos';
export type NodeKind = 'person' | 'union' | 'fanGuide';

export interface PersonNodeData extends Record<string, unknown> {
  kind: 'person';
  person: DisplayPerson;
  highlighted: boolean;
  collapsed: boolean;
  showPhoto: boolean;
  fanDepth?: number;
  fanDirection?: 'ancestors' | 'descendants';
}

export interface UnionNodeData extends Record<string, unknown> {
  kind: 'union';
  union: GedcomUnion;
}

export interface FanGuideNodeData extends Record<string, unknown> {
  kind: 'fanGuide';
  depth: number;
  radius: number;
  center: { x: number; y: number };
  direction: 'ancestors' | 'descendants';
}

export type GenealogyNode = Node<PersonNodeData | UnionNodeData | FanGuideNodeData>;

export interface LayoutResult {
  nodes: GenealogyNode[];
  edges: Edge[];
}

const elk = new ELK();
const personNodeId = (id: string) => `person:${id}`;
const unionNodeId = (id: string) => `union:${id}`;

function relatedPeople(document: GenealogyDocument, rootId: string, mode: ViewMode): Set<string> {
  const ids = new Set<string>([rootId]);
  const parents = new Map<string, string[]>();
  const children = new Map<string, string[]>();
  Object.values(document.unions).forEach((union) => {
    union.children.forEach(({ personId }) => {
      children.set(personId, [...(children.get(personId) ?? []), ...union.partnerIds]);
      union.partnerIds.forEach((partnerId) => {
        parents.set(partnerId, [...(parents.get(partnerId) ?? []), ...union.children.map((child) => child.personId)]);
      });
    });
  });
  const next = mode === 'ancestors' ? children : parents;
  const queue = [rootId];
  while (queue.length) {
    const id = queue.shift()!;
    (next.get(id) ?? []).forEach((relatedId) => {
      if (!ids.has(relatedId)) { ids.add(relatedId); queue.push(relatedId); }
    });
  }
  return ids;
}

function keepIds(document: GenealogyDocument, rootId: string, mode: ViewMode): Set<string> {
  return mode === 'ancestors' || mode === 'descendants' || mode === 'fan' || mode === 'fanDescendants'
    ? relatedPeople(document, rootId, mode === 'fan' ? 'ancestors' : mode === 'fanDescendants' ? 'descendants' : mode)
    : new Set(Object.keys(document.people));
}

function rawGraph(document: GenealogyDocument, rootId: string, mode: ViewMode, collapsed: Set<string>, search: string): LayoutResult {
  const peopleIds = keepIds(document, rootId, mode);
  const nodes: GenealogyNode[] = [];
  const edges: Edge[] = [];
  const normalizedSearch = search.trim().toLocaleLowerCase();

  Object.values(document.people).forEach((person) => {
    if (!peopleIds.has(person.id)) return;
    const visiblePerson = displayPerson(person);
    const searchable = `${person.givenName} ${person.familyName}`.toLocaleLowerCase();
    nodes.push({
      id: personNodeId(person.id),
      type: 'person',
      position: { x: 0, y: 0 },
      data: { kind: 'person', person: visiblePerson, highlighted: Boolean(normalizedSearch && searchable.includes(normalizedSearch)), collapsed: collapsed.has(person.id), showPhoto: mode === 'photos' || mode === 'print' },
    });
  });

  Object.values(document.unions).forEach((union) => {
    const partners = union.partnerIds.filter((id) => peopleIds.has(id));
    const children = union.children.filter(({ personId }) => peopleIds.has(personId));
    if (partners.length < 1 || ((mode === 'ancestors' || mode === 'fan') && children.length === 0) || ((mode === 'descendants' || mode === 'fanDescendants') && partners.every((id) => id !== rootId) && children.length === 0)) return;
    const unionId = unionNodeId(union.id);
    nodes.push({ id: unionId, type: 'union', position: { x: 0, y: 0 }, data: { kind: 'union', union } });
    partners.forEach((id) => edges.push({ id: `${personNodeId(id)}-${unionId}`, source: personNodeId(id), target: unionId, type: 'smoothstep', data: { relationship: 'partner' } }));
    if (!partners.some((id) => collapsed.has(id))) {
      children.forEach(({ personId, relationship }) => edges.push({ id: `${unionId}-${personNodeId(personId)}`, source: unionId, target: personNodeId(personId), type: 'smoothstep', label: relationship === 'biological' ? undefined : relationship === 'adopted' ? 'adopté' : 'beau-enfant', animated: relationship !== 'biological' }));
    }
  });
  return { nodes, edges };
}

async function elkLayout(graph: LayoutResult, direction: 'DOWN' | 'RIGHT'): Promise<LayoutResult> {
  const layout = await elk.layout({
    id: 'genealogy',
    layoutOptions: { 'elk.algorithm': 'layered', 'elk.direction': direction, 'elk.spacing.nodeNode': '70', 'elk.layered.spacing.nodeNodeBetweenLayers': '110' },
    children: graph.nodes.map((node) => ({ id: node.id, width: node.data.kind === 'union' ? 24 : 220, height: node.data.kind === 'union' ? 24 : 142 })),
    edges: graph.edges.map((edge) => ({ id: edge.id, sources: [edge.source], targets: [edge.target] })),
  });
  const positions = new Map((layout.children ?? []).map((child) => [child.id, { x: child.x ?? 0, y: child.y ?? 0 }]));
  return { ...graph, nodes: graph.nodes.map((node) => ({ ...node, position: positions.get(node.id) ?? { x: 0, y: 0 } })) };
}

function fanLayout(graph: LayoutResult, rootId: string, direction: 'ancestors' | 'descendants'): LayoutResult {
  const people = graph.nodes.filter((node) => node.data.kind === 'person');
  const personById = new Map(people.map((node) => [node.id.replace('person:', ''), node]));
  const relatedByPerson = new Map<string, string[]>();
  Object.values(graph.nodes).forEach((node) => {
    if (node.data.kind !== 'union') return;
    const union = (node.data as UnionNodeData).union;
    union.children.forEach(({ personId }) => {
      const parents = union.partnerIds.filter((parentId) => people.some((person) => person.id === personNodeId(parentId)));
      if (direction === 'ancestors') {
        relatedByPerson.set(personId, [...(relatedByPerson.get(personId) ?? []), ...parents]);
      } else {
        parents.forEach((parentId) => relatedByPerson.set(parentId, [...(relatedByPerson.get(parentId) ?? []), personId]));
      }
    });
  });

  const levels = new Map<string, number>([[rootId, 0]]);
  const queue = [rootId];
  while (queue.length) {
    const childId = queue.shift()!;
    (relatedByPerson.get(childId) ?? []).forEach((relatedId) => {
      if (!levels.has(relatedId)) { levels.set(relatedId, (levels.get(childId) ?? 0) + 1); queue.push(relatedId); }
    });
  }

  const maxDepth = Math.max(1, ...Array.from(levels.values()));
  const center = { x: Math.max(900, people.length * 32), y: maxDepth * 260 + 220 };
  const positions = new Map<string, { x: number; y: number }>();
  positions.set(personNodeId(rootId), center);
  const peopleByDepth = new Map<number, string[]>();
  levels.forEach((depth, personId) => {
    if (depth > 0) peopleByDepth.set(depth, [...(peopleByDepth.get(depth) ?? []), personId]);
  });

  peopleByDepth.forEach((personIds, depth) => {
    const slotWidth = Math.PI / (2 ** depth);
    const radius = depth * 260;
    personIds.sort((a, b) => {
      const sexOrder = (id: string) => {
        const person = personById.get(id);
        const sex = person ? (person.data as PersonNodeData).person.sex : undefined;
        return sex === 'M' ? 0 : sex === 'F' ? 1 : 2;
      };
      return sexOrder(a) - sexOrder(b) || a.localeCompare(b);
    });
    personIds.forEach((personId, index) => {
      const angle = -Math.PI / 2 + slotWidth * (index + 0.5);
      positions.set(personNodeId(personId), {
        x: center.x + Math.sin(angle) * radius,
        y: center.y + (direction === 'ancestors' ? -1 : 1) * Math.cos(angle) * radius,
      });
    });
  });

  graph.nodes.filter((node) => node.data.kind === 'union').forEach((node) => {
    if (node.data.kind !== 'union') return;
    const relatedIds = [...node.data.union.partnerIds, ...node.data.union.children.map((child) => child.personId)]
      .map(personNodeId)
      .map((id) => positions.get(id))
      .filter((position): position is { x: number; y: number } => Boolean(position));
    if (relatedIds.length) {
      positions.set(node.id, {
        x: relatedIds.reduce((sum, position) => sum + position.x, 0) / relatedIds.length,
        y: relatedIds.reduce((sum, position) => sum + position.y, 0) / relatedIds.length,
      });
    }
  });

  const guides: GenealogyNode[] = Array.from({ length: maxDepth + 1 }, (_, depth) => {
    const radius = depth === 0 ? 150 : depth * 260;
    return {
      id: `fan-guide-${depth}`,
      type: 'fanGuide',
      position: { x: center.x - radius, y: center.y - radius },
      draggable: false,
      selectable: false,
      connectable: false,
      zIndex: -10,
      data: { kind: 'fanGuide', depth, radius, center, direction },
    };
  });

  return {
    ...graph,
    nodes: [...guides, ...graph.nodes.map((node) => {
      const position = positions.get(node.id) ?? center;
      if (node.data.kind !== 'person') return { ...node, position };
      const personId = node.id.replace('person:', '');
      return { ...node, type: 'fanPerson', position, data: { ...node.data, fanDepth: levels.get(personId) ?? 0, fanDirection: direction } };
    })],
  };
}

function geometricLayout(graph: LayoutResult, mode: 'radial' | 'timeline'): LayoutResult {
  const people = graph.nodes.filter((node) => node.data.kind === 'person');
  const unions = graph.nodes.filter((node) => node.data.kind === 'union');
  const center = { x: Math.max(560, people.length * 9), y: Math.max(390, people.length * 5) };
  const radius = mode === 'timeline' ? 0 : Math.max(250, people.length * 24);
  const sorted = [...people].sort((a, b) => {
    if (a.data.kind !== 'person' || b.data.kind !== 'person') return 0;
    return (a.data.person.birth?.date ?? '').localeCompare(b.data.person.birth?.date ?? '');
  });
  const positions = new Map<string, { x: number; y: number }>();
  sorted.forEach((node, index) => {
    const angle = (index / Math.max(1, sorted.length)) * Math.PI * 2 - Math.PI / 2;
    positions.set(node.id, mode === 'timeline' ? { x: index * 250, y: index % 2 ? 180 : 20 } : { x: center.x + Math.cos(angle) * radius, y: center.y + Math.sin(angle) * radius });
  });
  unions.forEach((node, index) => positions.set(node.id, mode === 'timeline' ? { x: index * 250 + 80, y: 100 } : { x: center.x, y: center.y }));
  return { ...graph, nodes: graph.nodes.map((node) => ({ ...node, position: positions.get(node.id) ?? center })) };
}

export async function buildLayout(document: GenealogyDocument, mode: ViewMode, rootId: string, collapsed: Set<string>, search: string): Promise<LayoutResult> {
  const graph = rawGraph(document, rootId, mode, collapsed, search);
  if (mode === 'fan') return fanLayout(graph, rootId, 'ancestors');
  if (mode === 'fanDescendants') return fanLayout(graph, rootId, 'descendants');
  if (mode === 'radial' || mode === 'timeline') return geometricLayout(graph, mode);
  return elkLayout(graph, mode === 'horizontal' ? 'RIGHT' : 'DOWN');
}
