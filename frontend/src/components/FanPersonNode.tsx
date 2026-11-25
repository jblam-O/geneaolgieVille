import { Handle, Position, type NodeProps } from '@xyflow/react';
import type { PersonNodeData } from '../layouts/layouts';
import { formatDate } from '../model/genealogy';

export function FanPersonNode({ data }: NodeProps & { data: PersonNodeData }) {
  const { person } = data;
  const depth = data.fanDepth ?? 0;
  const descendants = data.fanDirection === 'descendants';
  const fill = depth === 0 ? '#fff9ef' : person.sex === 'F' ? '#f4dfd4' : '#f6ead9';
  const stroke = depth === 0 ? '#9a5f2d' : person.sex === 'F' ? '#b27458' : '#9a5f2d';

  return (
    <div className={`fan-person-node fan-person-node--depth-${depth} ${descendants ? 'fan-person-node--descendant' : 'fan-person-node--ancestor'}`}>
      <Handle type="target" position={Position.Top} />
      <svg className="fan-person-node__svg" width="220" height="132" viewBox="0 0 220 132" aria-label={person.displayName}>
        {depth === 0 ? (
          <circle cx="110" cy="66" r="54" fill={fill} stroke={stroke} strokeWidth="3" />
        ) : (
          <path d={descendants ? 'M 9 21 A 101 91 0 0 0 211 21 L 194 21 A 84 74 0 0 1 26 21 Z' : 'M 9 111 A 101 91 0 0 1 211 111 L 194 111 A 84 74 0 0 0 26 111 Z'} fill={fill} stroke={stroke} strokeWidth="2.5" />
        )}
        <foreignObject x="25" y={depth === 0 ? 42 : descendants ? 10 : 52} width="170" height="48">
          <div className="fan-person-node__copy">
            <strong>{person.displayName}</strong>
            <span>{formatDate(person.birth) || 'Naissance inconnue'}</span>
            {person.death && <span>{formatDate(person.death)}</span>}
          </div>
        </foreignObject>
      </svg>
      <Handle type="source" position={Position.Bottom} />
    </div>
  );
}
