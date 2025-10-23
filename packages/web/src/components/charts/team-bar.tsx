'use client';

import { useEffect, useRef } from 'react';

interface TeamMember {
  name: string;
  value: number;
  target?: number;
  color?: string;
}

interface TeamBarProps {
  data: TeamMember[];
  title?: string;
  height?: number;
  showTarget?: boolean;
  horizontal?: boolean;
  animate?: boolean;
  valueLabel?: string;
  targetLabel?: string;
  className?: string;
}

const defaultColors = [
  '#3B82F6', // Blue
  '#10B981', // Green
  '#F59E0B', // Yellow
  '#EF4444', // Red
  '#8B5CF6', // Purple
  '#F97316', // Orange
  '#06B6D4', // Cyan
  '#84CC16', // Lime
];

export function TeamBar({
  data,
  title,
  height = 400,
  showTarget = false,
  horizontal = false,
  animate = true,
  valueLabel = 'Performance',
  targetLabel = 'Target',
  className = ''
}: TeamBarProps) {
  const canvasRef = useRef<HTMLCanvasElement>(null);
  const chartRef = useRef<any>(null);

  useEffect(() => {
    if (!canvasRef.current || data.length === 0) return;

    const ctx = canvasRef.current.getContext('2d');
    if (!ctx) return;

    // Import Chart.js dynamically to avoid SSR issues
    import('chart.js/auto').then(({ default: Chart }) => {
      // Destroy existing chart
      if (chartRef.current) {
        chartRef.current.destroy();
      }

      // Assign colors to data
      const dataWithColors = data.map((item, index) => ({
        ...item,
        color: item.color || defaultColors[index % defaultColors.length]
      }));

      const datasets = [
        {
          label: valueLabel,
          data: dataWithColors.map(item => item.value),
          backgroundColor: dataWithColors.map(item => item.color),
          borderColor: dataWithColors.map(item => item.color),
          borderWidth: 1,
          borderRadius: 4,
          borderSkipped: false,
        }
      ];

      // Add target dataset if enabled
      if (showTarget && data.some(item => item.target !== undefined)) {
        datasets.push({
          label: targetLabel,
          data: dataWithColors.map(item => item.target || 0),
          backgroundColor: 'rgba(156, 163, 175, 0.3)',
          borderColor: '#9CA3AF',
          borderWidth: 2,
          borderRadius: 4,
          borderSkipped: false,
        });
      }

      const chartData = {
        labels: dataWithColors.map(item => {
          // Truncate long names for better display
          return item.name.length > 12 ? item.name.substring(0, 12) + '...' : item.name;
        }),
        datasets
      };

      const options = {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: horizontal ? 'y' as const : 'x' as const,
        animation: animate ? {
          duration: 1000,
          easing: 'easeInOutQuart' as const
        } : false,
        interaction: {
          intersect: false,
          mode: 'index' as const,
        },
        plugins: {
          legend: {
            display: showTarget,
            position: 'top' as const,
            labels: {
              usePointStyle: true,
              padding: 20,
              font: {
                size: 12
              }
            }
          },
          tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            borderColor: '#374151',
            borderWidth: 1,
            cornerRadius: 8,
            displayColors: true,
            callbacks: {
              title: (context: any) => {
                const dataIndex = context[0].dataIndex;
                return data[dataIndex]?.name || context[0].label;
              },
              label: (context: any) => {
                const value = context.parsed[horizontal ? 'x' : 'y'];
                const suffix = context.dataset.label === valueLabel ? '%' : '';
                return `${context.dataset.label}: ${value}${suffix}`;
              }
            }
          }
        },
        scales: {
          x: {
            display: true,
            beginAtZero: true,
            max: horizontal ? undefined : 100,
            grid: {
              display: true,
              color: 'rgba(0, 0, 0, 0.05)',
              drawBorder: false,
            },
            ticks: {
              color: '#6B7280',
              font: {
                size: 11
              },
              callback: function(value: any) {
                if (horizontal) {
                  return value + '%';
                }
                return value;
              }
            }
          },
          y: {
            display: true,
            beginAtZero: true,
            max: horizontal ? undefined : 100,
            grid: {
              display: true,
              color: 'rgba(0, 0, 0, 0.05)',
              drawBorder: false,
            },
            ticks: {
              color: '#6B7280',
              font: {
                size: 11
              },
              callback: function(value: any) {
                if (!horizontal) {
                  return value + '%';
                }
                return value;
              }
            }
          }
        },
        elements: {
          bar: {
            borderRadius: 4
          }
        }
      };

      chartRef.current = new Chart(ctx, {
        type: 'bar',
        data: chartData,
        options
      });
    });

    // Cleanup function
    return () => {
      if (chartRef.current) {
        chartRef.current.destroy();
        chartRef.current = null;
      }
    };
  }, [data, showTarget, horizontal, animate, valueLabel, targetLabel]);

  if (data.length === 0) {
    return (
      <div 
        className={`flex items-center justify-center bg-gray-50 rounded-lg border-2 border-dashed border-gray-300 ${className}`}
        style={{ height }}
      >
        <div className="text-center">
          <svg className="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
          <p className="text-gray-500 text-sm">No team data available</p>
        </div>
      </div>
    );
  }

  return (
    <div className={`${className}`}>
      {title && (
        <h3 className="text-lg font-semibold text-gray-900 mb-4">{title}</h3>
      )}
      
      <div style={{ height }}>
        <canvas
          ref={canvasRef}
          role="img"
          aria-label={`Bar chart showing team performance${title ? ` for ${title}` : ''}`}
        />
      </div>

      {/* Summary Stats */}
      <div className="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
        <div>
          <div className="text-lg font-semibold text-gray-900">
            {data.length}
          </div>
          <div className="text-xs text-gray-600">Team Members</div>
        </div>
        <div>
          <div className="text-lg font-semibold text-green-600">
            {Math.round(data.reduce((sum, item) => sum + item.value, 0) / data.length)}%
          </div>
          <div className="text-xs text-gray-600">Avg Performance</div>
        </div>
        <div>
          <div className="text-lg font-semibold text-blue-600">
            {Math.max(...data.map(item => item.value))}%
          </div>
          <div className="text-xs text-gray-600">Top Performer</div>
        </div>
        <div>
          <div className="text-lg font-semibold text-gray-600">
            {data.filter(item => item.value >= 90).length}
          </div>
          <div className="text-xs text-gray-600">Above 90%</div>
        </div>
      </div>
    </div>
  );
}
