'use client';

import { useEffect, useRef } from 'react';

interface DepartmentData {
  name: string;
  value: number;
  color?: string;
}

interface DoughnutByDeptProps {
  data: DepartmentData[];
  title?: string;
  size?: number;
  centerText?: string;
  showLegend?: boolean;
  animate?: boolean;
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

export function DoughnutByDept({
  data,
  title,
  size = 300,
  centerText,
  showLegend = true,
  animate = true,
  className = ''
}: DoughnutByDeptProps) {
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

      const total = data.reduce((sum, item) => sum + item.value, 0);

      const chartData = {
        labels: dataWithColors.map(item => item.name),
        datasets: [
          {
            data: dataWithColors.map(item => item.value),
            backgroundColor: dataWithColors.map(item => item.color),
            borderColor: '#ffffff',
            borderWidth: 2,
            hoverBorderWidth: 3,
            hoverBorderColor: '#ffffff',
          }
        ]
      };

      const options = {
        responsive: true,
        maintainAspectRatio: false,
        animation: animate ? {
          duration: 1000,
          easing: 'easeInOutQuart' as const
        } : false,
        plugins: {
          legend: {
            display: false // We'll create a custom legend
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
              label: (context: any) => {
                const value = context.parsed;
                const percentage = ((value / total) * 100).toFixed(1);
                return `${context.label}: ${value} (${percentage}%)`;
              }
            }
          }
        },
        cutout: '60%', // Creates the doughnut hole
        elements: {
          arc: {
            borderRadius: 4
          }
        }
      };

      chartRef.current = new Chart(ctx, {
        type: 'doughnut',
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
  }, [data, animate]);

  if (data.length === 0) {
    return (
      <div 
        className={`flex items-center justify-center bg-gray-50 rounded-lg border-2 border-dashed border-gray-300 ${className}`}
        style={{ height: size }}
      >
        <div className="text-center">
          <svg className="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
          </svg>
          <p className="text-gray-500 text-sm">No department data available</p>
        </div>
      </div>
    );
  }

  const total = data.reduce((sum, item) => sum + item.value, 0);
  const dataWithColors = data.map((item, index) => ({
    ...item,
    color: item.color || defaultColors[index % defaultColors.length]
  }));

  return (
    <div className={`${className}`}>
      {title && (
        <h3 className="text-lg font-semibold text-gray-900 mb-4">{title}</h3>
      )}
      
      <div className="flex flex-col lg:flex-row items-center gap-6">
        {/* Chart */}
        <div className="relative" style={{ width: size, height: size }}>
          <canvas
            ref={canvasRef}
            role="img"
            aria-label={`Doughnut chart showing department distribution${title ? ` for ${title}` : ''}`}
          />
          
          {/* Center Text */}
          {centerText && (
            <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
              <div className="text-center">
                <div className="text-2xl font-bold text-gray-900">{total}</div>
                <div className="text-sm text-gray-600">{centerText}</div>
              </div>
            </div>
          )}
        </div>

        {/* Legend */}
        {showLegend && (
          <div className="flex-1 min-w-0">
            <div className="space-y-3">
              {dataWithColors.map((item, index) => {
                const percentage = ((item.value / total) * 100).toFixed(1);
                return (
                  <div key={index} className="flex items-center justify-between">
                    <div className="flex items-center min-w-0 flex-1">
                      <div
                        className="w-3 h-3 rounded-full flex-shrink-0 mr-3"
                        style={{ backgroundColor: item.color }}
                        aria-hidden="true"
                      />
                      <span className="text-sm font-medium text-gray-900 truncate">
                        {item.name}
                      </span>
                    </div>
                    <div className="flex items-center space-x-2 ml-4">
                      <span className="text-sm font-semibold text-gray-900">
                        {item.value}
                      </span>
                      <span className="text-xs text-gray-500">
                        ({percentage}%)
                      </span>
                    </div>
                  </div>
                );
              })}
            </div>
            
            {/* Total */}
            <div className="mt-4 pt-3 border-t border-gray-200">
              <div className="flex items-center justify-between">
                <span className="text-sm font-semibold text-gray-900">Total</span>
                <span className="text-sm font-bold text-gray-900">{total}</span>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
