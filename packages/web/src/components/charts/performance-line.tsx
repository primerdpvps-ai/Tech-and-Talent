'use client';

import { useEffect, useRef } from 'react';

interface PerformanceDataPoint {
  date: string;
  value: number;
  label?: string;
}

interface PerformanceLineProps {
  data: PerformanceDataPoint[];
  title?: string;
  height?: number;
  color?: string;
  backgroundColor?: string;
  showGrid?: boolean;
  showPoints?: boolean;
  animate?: boolean;
  className?: string;
}

export function PerformanceLine({
  data,
  title,
  height = 300,
  color = '#3B82F6',
  backgroundColor = 'rgba(59, 130, 246, 0.1)',
  showGrid = true,
  showPoints = true,
  animate = true,
  className = ''
}: PerformanceLineProps) {
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

      const chartData = {
        labels: data.map(point => {
          const date = new Date(point.date);
          return date.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric' 
          });
        }),
        datasets: [
          {
            label: title || 'Performance',
            data: data.map(point => point.value),
            borderColor: color,
            backgroundColor: backgroundColor,
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointRadius: showPoints ? 4 : 0,
            pointHoverRadius: 6,
            pointBackgroundColor: color,
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointHoverBackgroundColor: color,
            pointHoverBorderColor: '#ffffff',
            pointHoverBorderWidth: 2,
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
        interaction: {
          intersect: false,
          mode: 'index' as const,
        },
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            borderColor: color,
            borderWidth: 1,
            cornerRadius: 8,
            displayColors: false,
            callbacks: {
              title: (context: any) => {
                const dataIndex = context[0].dataIndex;
                return data[dataIndex]?.label || context[0].label;
              },
              label: (context: any) => {
                return `${context.dataset.label}: ${context.parsed.y}%`;
              }
            }
          }
        },
        scales: {
          x: {
            display: true,
            grid: {
              display: showGrid,
              color: 'rgba(0, 0, 0, 0.05)',
              drawBorder: false,
            },
            ticks: {
              color: '#6B7280',
              font: {
                size: 12
              }
            }
          },
          y: {
            display: true,
            beginAtZero: true,
            max: 100,
            grid: {
              display: showGrid,
              color: 'rgba(0, 0, 0, 0.05)',
              drawBorder: false,
            },
            ticks: {
              color: '#6B7280',
              font: {
                size: 12
              },
              callback: function(value: any) {
                return value + '%';
              }
            }
          }
        },
        elements: {
          point: {
            hoverRadius: 8
          }
        }
      };

      chartRef.current = new Chart(ctx, {
        type: 'line',
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
  }, [data, title, color, backgroundColor, showGrid, showPoints, animate]);

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
          <p className="text-gray-500 text-sm">No performance data available</p>
        </div>
      </div>
    );
  }

  return (
    <div className={`relative ${className}`}>
      {title && (
        <h3 className="text-lg font-semibold text-gray-900 mb-4">{title}</h3>
      )}
      <div style={{ height }}>
        <canvas
          ref={canvasRef}
          role="img"
          aria-label={`Performance line chart${title ? ` showing ${title}` : ''}`}
        />
      </div>
    </div>
  );
}
