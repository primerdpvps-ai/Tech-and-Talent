'use client';

import { useState, useEffect } from 'react';

interface CountdownTimerProps {
  initialSeconds: number;
  onComplete?: () => void;
  format?: 'mm:ss' | 'ss';
}

export function CountdownTimer({ initialSeconds, onComplete, format = 'mm:ss' }: CountdownTimerProps) {
  const [seconds, setSeconds] = useState(initialSeconds);

  useEffect(() => {
    if (seconds <= 0) {
      onComplete?.();
      return;
    }

    const timer = setInterval(() => {
      setSeconds(prev => prev - 1);
    }, 1000);

    return () => clearInterval(timer);
  }, [seconds, onComplete]);

  const formatTime = (totalSeconds: number) => {
    if (format === 'ss') {
      return totalSeconds.toString();
    }
    
    const minutes = Math.floor(totalSeconds / 60);
    const remainingSeconds = totalSeconds % 60;
    return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
  };

  return (
    <span className={`font-mono font-semibold ${seconds <= 30 ? 'text-red-600' : 'text-gray-600'}`}>
      {formatTime(seconds)}
    </span>
  );
}
