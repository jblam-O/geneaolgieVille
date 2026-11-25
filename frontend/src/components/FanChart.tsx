import { useMemo } from 'react';
import type { GenealogyDocument, GedcomPerson } from '../model/genealogy';
import { displayPerson } from '../model/genealogy';

type Direction = 'ancestors' | 'descendants';
type FanChartProps = { document: GenealogyDocument; rootId: string; direction: Direction; onPersonClick: (id: string) => void };
type Sector = { person: GedcomPerson; depth: number; startRatio: number; endRatio: number };

function polar(cx: number, cy: number, radius: number, angle: number) {
  return { x: cx + Math.cos(angle) * radius, y: cy + Math.sin(angle) * radius };
}

function sectorPath(cx: number, cy: number, inner: number, outer: number, start: number, end: number) {
  const a = polar(cx, cy, outer, start);
  const b = polar(cx, cy, outer, end);
  const c = polar(cx, cy, inner, end);
  const d = polar(cx, cy, inner, start);
  const largeArc = end - start > Math.PI ? 1 : 0;
  return `M ${a.x} ${a.y} A ${outer} ${outer} 0 ${largeArc} 1 ${b.x} ${b.y} L ${c.x} ${c.y} A ${inner} ${inner} 0 ${largeArc} 0 ${d.x} ${d.y} Z`;
}

function relatedSectors(document: GenealogyDocument, rootId: string, direction: Direction): Sector[] {
  const byPerson = new Map<string, GedcomPerson>(Object.entries(document.people));
  const related = new Map<string, string[]>();
  Object.values(document.unions).forEach((union) => {
    if (direction === 'ancestors') {
      union.children.forEach(({ personId }) => {
        const parents = union.partnerIds.filter((id) => byPerson.has(id));
        related.set(personId, [...new Set([...(related.get(personId) ?? []), ...parents])]);
      });
    } else {
      union.partnerIds.forEach((partnerId) => {
        const children = union.children.map(({ personId }) => personId).filter((id) => byPerson.has(id));
        related.set(partnerId, [...new Set([...(related.get(partnerId) ?? []), ...children])]);
      });
    }
  });

  const sectors: Sector[] = [];
  const seen = new Set<string>([rootId]);
  const queue: Array<{ id: string; depth: number; startRatio: number; endRatio: number }> = [
    { id: rootId, depth: 0, startRatio: 0, endRatio: 1 },
  ];
  while (queue.length) {
    const current = queue.shift()!;
    const directRelations = (related.get(current.id) ?? [])
      .filter((id) => !seen.has(id))
      .sort((a, b) => (byPerson.get(a)?.sex === 'M' ? -1 : 1) - (byPerson.get(b)?.sex === 'M' ? -1 : 1));
    const sectorWidth = (current.endRatio - current.startRatio) / Math.max(1, directRelations.length);

    directRelations.forEach((id, index) => {
      seen.add(id);
      const depth = current.depth + 1;
      const startRatio = current.startRatio + index * sectorWidth;
      const endRatio = startRatio + sectorWidth;
      sectors.push({ person: byPerson.get(id)!, depth, startRatio, endRatio });
      queue.push({ id, depth, startRatio, endRatio });
    });
  }
  return sectors;
}

export function FanChart({ document, rootId, direction, onPersonClick }: FanChartProps) {
  const sectors = useMemo(() => relatedSectors(document, rootId, direction), [document, rootId, direction]);
  const maxDepth = Math.max(1, ...sectors.map((sector) => sector.depth));
  const ring = 142;
  const center = 120 + maxDepth * ring;
  const size = center * 2;
  const root = document.people[rootId];

  return (
    <div className={`fan-chart fan-chart--${direction}`}>
      <svg viewBox={`0 0 ${size} ${size}`} role="img" aria-label={direction === 'ancestors' ? 'Éventail des ascendants' : 'Éventail des descendants'}>
        <defs>
          <filter id="fan-shadow" x="-20%" y="-20%" width="140%" height="140%"><feDropShadow dx="0" dy="5" stdDeviation="5" floodColor="#6d563e" floodOpacity=".16" /></filter>
        </defs>
        {sectors.map((sector) => {
          const person = displayPerson(sector.person);
          const start = (direction === 'ancestors' ? Math.PI : 0) + sector.startRatio * Math.PI;
          const end = (direction === 'ancestors' ? Math.PI : 0) + sector.endRatio * Math.PI;
          const inner = 120 + (sector.depth - 1) * ring;
          const outer = inner + ring - 5;
          const label = polar(center, center, (inner + outer) / 2, (start + end) / 2);
          return <g key={sector.person.id} className="fan-chart__sector" onClick={() => onPersonClick(sector.person.id)}><path d={sectorPath(center, center, inner, outer, start, end)} className={`fan-chart__sector-shape fan-chart__sector-shape--${sector.person.sex ?? 'X'}`} filter="url(#fan-shadow)" /><text x={label.x} y={label.y - 3} className="fan-chart__name" textAnchor="middle">{person.displayName}</text><text x={label.x} y={label.y + 13} className="fan-chart__date" textAnchor="middle">{person.birth?.date ?? ''}</text><title>{person.displayName}</title></g>;
        })}
        {root && <g className="fan-chart__root" onClick={() => onPersonClick(root.id)}><circle cx={center} cy={center} r="112" filter="url(#fan-shadow)" /><text x={center} y={center - 5} className="fan-chart__root-name" textAnchor="middle">{displayPerson(root).displayName}</text><text x={center} y={center + 17} className="fan-chart__date" textAnchor="middle">{root.birth?.date ?? ''}</text></g>}
      </svg>
    </div>
  );
}
