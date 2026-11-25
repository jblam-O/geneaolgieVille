import { Handle, Position, type NodeProps } from '@xyflow/react';
import type { PersonNodeData } from '../layouts/layouts';
import { formatDate } from '../model/genealogy';

export function PersonNode({ data }: NodeProps & { data: PersonNodeData }) {
  const { person } = data;
  return (
    <article className={`person-node ${data.fanDepth !== undefined ? `person-node--fan person-node--fan-depth-${data.fanDepth}` : ''} ${person.sex === 'F' ? 'person-node--female' : ''} ${data.highlighted ? 'person-node--highlighted' : ''}`}>
      <Handle type="target" position={Position.Top} />
      {data.showPhoto && <div className="person-node__portrait">{person.photoUrl ? <img src={person.photoUrl} alt="" /> : <span>{person.givenName.slice(0, 1)}</span>}</div>}
      <div className="person-node__copy">
        <strong>{person.displayName}</strong>
        <span>{formatDate(person.birth) || 'Date de naissance inconnue'}</span>
        {person.death && <span>{formatDate(person.death)}</span>}
      </div>
      {data.collapsed && <span className="person-node__collapse">+</span>}
      <Handle type="source" position={Position.Bottom} />
    </article>
  );
}
