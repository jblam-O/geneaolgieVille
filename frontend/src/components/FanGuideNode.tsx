import type { NodeProps } from '@xyflow/react';
import type { FanGuideNodeData } from '../layouts/layouts';

export function FanGuideNode({ data }: NodeProps & { data: FanGuideNodeData }) {
  const { radius, depth, direction } = data;
  const size = radius * 2;
  const strokeWidth = depth === 0 ? 2.5 : 2;

  return (
    <div className={`fan-guide fan-guide--depth-${depth}`} aria-hidden="true">
      <svg width={size} height={size} viewBox={`0 0 ${size} ${size}`}>
        {depth === 0 ? (
          <circle cx={radius} cy={radius} r={radius - 8} className="fan-guide__circle" strokeWidth={strokeWidth} />
        ) : (
          <path d={`M 8 ${radius} A ${radius - 8} ${radius - 8} 0 0 ${direction === 'ancestors' ? 0 : 1} ${size - 8} ${radius}`} className="fan-guide__arc" strokeWidth={strokeWidth} />
        )}
      </svg>
    </div>
  );
}
