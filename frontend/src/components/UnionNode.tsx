import { Handle, Position, type NodeProps } from '@xyflow/react';
import type { UnionNodeData } from '../layouts/layouts';
import { formatDate } from '../model/genealogy';

export function UnionNode({ data }: NodeProps & { data: UnionNodeData }) {
  return (
    <div className="union-node" title={formatDate(data.union.marriage) || 'Union'}>
      <Handle type="target" position={Position.Top} />
      <span>∪</span>
      <Handle type="source" position={Position.Bottom} />
    </div>
  );
}
