'use client';

interface Step {
  id: number;
  title: string;
  description?: string;
  optional?: boolean;
}

interface StepperProps {
  steps: Step[];
  currentStep: number;
  completedSteps?: number[];
  orientation?: 'horizontal' | 'vertical';
  size?: 'sm' | 'md' | 'lg';
  showDescription?: boolean;
  className?: string;
}

export function Stepper({
  steps,
  currentStep,
  completedSteps = [],
  orientation = 'horizontal',
  size = 'md',
  showDescription = true,
  className = ''
}: StepperProps) {
  const isStepCompleted = (stepId: number) => completedSteps.includes(stepId);
  const isStepActive = (stepId: number) => stepId === currentStep;
  const isStepAccessible = (stepId: number) => stepId <= currentStep || isStepCompleted(stepId);

  const sizeClasses = {
    sm: {
      circle: 'w-6 h-6 text-xs',
      connector: 'h-0.5',
      title: 'text-sm',
      description: 'text-xs'
    },
    md: {
      circle: 'w-8 h-8 text-sm',
      connector: 'h-0.5',
      title: 'text-base',
      description: 'text-sm'
    },
    lg: {
      circle: 'w-10 h-10 text-base',
      connector: 'h-1',
      title: 'text-lg',
      description: 'text-base'
    }
  };

  const classes = sizeClasses[size];

  if (orientation === 'vertical') {
    return (
      <div className={`space-y-4 ${className}`} role="progressbar" aria-valuenow={currentStep} aria-valuemin={1} aria-valuemax={steps.length}>
        {steps.map((step, index) => {
          const isCompleted = isStepCompleted(step.id);
          const isActive = isStepActive(step.id);
          const isAccessible = isStepAccessible(step.id);
          const isLast = index === steps.length - 1;

          return (
            <div key={step.id} className="relative flex items-start">
              {/* Connector Line */}
              {!isLast && (
                <div className="absolute left-4 top-8 w-0.5 h-16 -ml-px">
                  <div
                    className={`w-full h-full ${
                      isCompleted || (isActive && index < steps.length - 1)
                        ? 'bg-blue-600'
                        : 'bg-gray-300'
                    }`}
                  />
                </div>
              )}

              {/* Step Circle */}
              <div
                className={`relative flex items-center justify-center ${classes.circle} rounded-full border-2 font-medium transition-colors ${
                  isCompleted
                    ? 'bg-blue-600 border-blue-600 text-white'
                    : isActive
                    ? 'bg-blue-50 border-blue-600 text-blue-600'
                    : isAccessible
                    ? 'bg-white border-gray-300 text-gray-500 hover:border-gray-400'
                    : 'bg-gray-100 border-gray-300 text-gray-400'
                }`}
                aria-label={`Step ${step.id}: ${step.title}${isCompleted ? ' (completed)' : isActive ? ' (current)' : ''}`}
              >
                {isCompleted ? (
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                  </svg>
                ) : (
                  <span>{step.id}</span>
                )}
              </div>

              {/* Step Content */}
              <div className="ml-4 min-w-0 flex-1">
                <div
                  className={`font-medium ${classes.title} ${
                    isActive
                      ? 'text-blue-600'
                      : isCompleted
                      ? 'text-gray-900'
                      : isAccessible
                      ? 'text-gray-700'
                      : 'text-gray-400'
                  }`}
                >
                  {step.title}
                  {step.optional && (
                    <span className="ml-2 text-xs text-gray-500 font-normal">(Optional)</span>
                  )}
                </div>
                {showDescription && step.description && (
                  <div
                    className={`mt-1 ${classes.description} ${
                      isActive
                        ? 'text-blue-500'
                        : isAccessible
                        ? 'text-gray-600'
                        : 'text-gray-400'
                    }`}
                  >
                    {step.description}
                  </div>
                )}
              </div>
            </div>
          );
        })}
      </div>
    );
  }

  // Horizontal orientation
  return (
    <nav
      className={`flex items-center justify-between ${className}`}
      role="progressbar"
      aria-valuenow={currentStep}
      aria-valuemin={1}
      aria-valuemax={steps.length}
      aria-label="Progress"
    >
      {steps.map((step, index) => {
        const isCompleted = isStepCompleted(step.id);
        const isActive = isStepActive(step.id);
        const isAccessible = isStepAccessible(step.id);
        const isLast = index === steps.length - 1;

        return (
          <div key={step.id} className="flex items-center">
            {/* Step */}
            <div className="flex flex-col items-center">
              {/* Step Circle */}
              <div
                className={`flex items-center justify-center ${classes.circle} rounded-full border-2 font-medium transition-colors ${
                  isCompleted
                    ? 'bg-blue-600 border-blue-600 text-white'
                    : isActive
                    ? 'bg-blue-50 border-blue-600 text-blue-600'
                    : isAccessible
                    ? 'bg-white border-gray-300 text-gray-500 hover:border-gray-400'
                    : 'bg-gray-100 border-gray-300 text-gray-400'
                }`}
                aria-label={`Step ${step.id}: ${step.title}${isCompleted ? ' (completed)' : isActive ? ' (current)' : ''}`}
              >
                {isCompleted ? (
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                  </svg>
                ) : (
                  <span>{step.id}</span>
                )}
              </div>

              {/* Step Content */}
              <div className="mt-3 text-center max-w-24">
                <div
                  className={`font-medium ${classes.title} ${
                    isActive
                      ? 'text-blue-600'
                      : isCompleted
                      ? 'text-gray-900'
                      : isAccessible
                      ? 'text-gray-700'
                      : 'text-gray-400'
                  }`}
                >
                  {step.title}
                  {step.optional && (
                    <span className="block text-xs text-gray-500 font-normal mt-1">(Optional)</span>
                  )}
                </div>
                {showDescription && step.description && (
                  <div
                    className={`mt-1 ${classes.description} ${
                      isActive
                        ? 'text-blue-500'
                        : isAccessible
                        ? 'text-gray-600'
                        : 'text-gray-400'
                    }`}
                  >
                    {step.description}
                  </div>
                )}
              </div>
            </div>

            {/* Connector */}
            {!isLast && (
              <div className="flex-1 mx-4">
                <div
                  className={`${classes.connector} transition-colors ${
                    isCompleted || (isActive && index < currentStep - 1)
                      ? 'bg-blue-600'
                      : 'bg-gray-300'
                  }`}
                  role="presentation"
                />
              </div>
            )}
          </div>
        );
      })}
    </nav>
  );
}

// Helper component for responsive stepper
export function ResponsiveStepper(props: StepperProps) {
  return (
    <>
      {/* Desktop: Horizontal */}
      <div className="hidden md:block">
        <Stepper {...props} orientation="horizontal" />
      </div>
      
      {/* Mobile: Vertical */}
      <div className="md:hidden">
        <Stepper {...props} orientation="vertical" size="sm" />
      </div>
    </>
  );
}
