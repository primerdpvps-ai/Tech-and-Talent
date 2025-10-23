'use client';

import { useSession } from 'next-auth/react';
import { redirect } from 'next/navigation';
import { useState } from 'react';

// Mock training data
const trainingModules = [
  {
    id: 1,
    title: 'Company Overview & Policies',
    description: 'Learn about TTS PMS culture, values, and workplace policies',
    duration: '15 minutes',
    videoUrl: '/training/module1.mp4',
    completed: false,
    quiz: {
      questions: [
        {
          question: 'What are TTS PMS core values?',
          options: ['Quality, Integrity, Innovation', 'Speed, Cost, Efficiency', 'Growth, Profit, Scale'],
          correct: 0
        },
        {
          question: 'What is the minimum daily work requirement?',
          options: ['4 hours', '6 hours', '8 hours'],
          correct: 1
        }
      ]
    }
  },
  {
    id: 2,
    title: 'Data Security & Confidentiality',
    description: 'Understanding data protection, client confidentiality, and security protocols',
    duration: '20 minutes',
    videoUrl: '/training/module2.mp4',
    completed: false,
    quiz: {
      questions: [
        {
          question: 'What should you do if you accidentally access wrong client data?',
          options: ['Ignore it', 'Report immediately to supervisor', 'Delete the data'],
          correct: 1
        },
        {
          question: 'Can you share client information with family members?',
          options: ['Yes, if they sign NDA', 'No, never', 'Only general information'],
          correct: 1
        }
      ]
    }
  },
  {
    id: 3,
    title: 'Work Tools & Time Tracking',
    description: 'How to use company tools, time tracking software, and productivity systems',
    duration: '25 minutes',
    videoUrl: '/training/module3.mp4',
    completed: false,
    quiz: {
      questions: [
        {
          question: 'How often should you take screenshots during work?',
          options: ['Every 5 minutes', 'Every 10 minutes', 'As configured by system'],
          correct: 2
        },
        {
          question: 'What happens if you are inactive for more than 40 seconds?',
          options: ['Nothing', 'Timer pauses automatically', 'You get a warning'],
          correct: 1
        }
      ]
    }
  }
];

export default function NewEmployeeDashboard() {
  const { data: session, status } = useSession();
  const [currentModule, setCurrentModule] = useState<number | null>(null);
  const [currentQuiz, setCurrentQuiz] = useState<number | null>(null);
  const [quizAnswers, setQuizAnswers] = useState<number[]>([]);
  const [completedModules, setCompletedModules] = useState<number[]>([]);
  const [showVideo, setShowVideo] = useState(false);

  if (status === 'loading') {
    return <div className="p-6">Loading...</div>;
  }

  if (!session || session.user.role !== 'NEW_EMPLOYEE') {
    redirect('/');
  }

  const handleStartModule = (moduleId: number) => {
    setCurrentModule(moduleId);
    setShowVideo(true);
  };

  const handleVideoComplete = () => {
    setShowVideo(false);
    setCurrentQuiz(currentModule);
    setQuizAnswers([]);
  };

  const handleQuizAnswer = (questionIndex: number, answerIndex: number) => {
    const newAnswers = [...quizAnswers];
    newAnswers[questionIndex] = answerIndex;
    setQuizAnswers(newAnswers);
  };

  const handleQuizSubmit = () => {
    if (!currentQuiz) return;

    const module = trainingModules.find(m => m.id === currentQuiz);
    if (!module) return;

    // Check if all answers are correct
    const allCorrect = module.quiz.questions.every((q, index) => 
      quizAnswers[index] === q.correct
    );

    if (allCorrect) {
      setCompletedModules([...completedModules, currentQuiz]);
      setCurrentQuiz(null);
      setCurrentModule(null);
      alert('Module completed successfully!');
    } else {
      alert('Some answers are incorrect. Please review the training material and try again.');
      setCurrentQuiz(null);
      setCurrentModule(null);
    }
  };

  const allModulesCompleted = completedModules.length === trainingModules.length;
  const completionPercentage = Math.round((completedModules.length / trainingModules.length) * 100);

  return (
    <div className="p-6">
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900">
          Welcome to TTS PMS, {session.user.fullName}!
        </h1>
        <p className="mt-2 text-gray-600">
          Complete your mandatory training to start working. Your role will be updated to Employee after first payroll.
        </p>
      </div>

      {/* Progress Overview */}
      <div className="bg-white rounded-xl shadow-lg p-6 mb-8">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-xl font-bold text-gray-900">Training Progress</h2>
          <span className="text-2xl font-bold text-blue-600">{completionPercentage}%</span>
        </div>
        
        <div className="w-full bg-gray-200 rounded-full h-3 mb-4">
          <div 
            className="bg-blue-600 h-3 rounded-full transition-all duration-300"
            style={{ width: `${completionPercentage}%` }}
          ></div>
        </div>
        
        <p className="text-sm text-gray-600">
          {completedModules.length} of {trainingModules.length} modules completed
        </p>

        {allModulesCompleted && (
          <div className="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
            <div className="flex">
              <svg className="w-5 h-5 text-green-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <div>
                <h3 className="text-sm font-medium text-green-800">Training Complete!</h3>
                <p className="text-sm text-green-700 mt-1">
                  Congratulations! You've completed all training modules. You can now start working. 
                  Your role will be updated to Employee after your first payroll.
                </p>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Video Modal */}
      {showVideo && currentModule && (
        <div className="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg p-6 max-w-4xl w-full mx-4">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-lg font-bold text-gray-900">
                {trainingModules.find(m => m.id === currentModule)?.title}
              </h3>
              <button
                onClick={() => setShowVideo(false)}
                className="text-gray-400 hover:text-gray-600"
              >
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            
            <div className="aspect-video bg-gray-900 rounded-lg mb-4 flex items-center justify-center">
              <div className="text-center text-white">
                <svg className="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293H15M9 10V9a2 2 0 012-2h2a2 2 0 012 2v1M9 10v5a2 2 0 002 2h2a2 2 0 002-2v-5" />
                </svg>
                <p className="text-lg">Training Video</p>
                <p className="text-sm text-gray-300">
                  Duration: {trainingModules.find(m => m.id === currentModule)?.duration}
                </p>
              </div>
            </div>
            
            <div className="flex justify-end">
              <button
                onClick={handleVideoComplete}
                className="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors"
              >
                Video Complete - Take Quiz
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Quiz Modal */}
      {currentQuiz && (
        <div className="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg p-6 max-w-2xl w-full mx-4">
            <h3 className="text-lg font-bold text-gray-900 mb-4">
              Quiz: {trainingModules.find(m => m.id === currentQuiz)?.title}
            </h3>
            
            <div className="space-y-6">
              {trainingModules.find(m => m.id === currentQuiz)?.quiz.questions.map((question, qIndex) => (
                <div key={qIndex}>
                  <p className="font-medium text-gray-900 mb-3">
                    {qIndex + 1}. {question.question}
                  </p>
                  <div className="space-y-2">
                    {question.options.map((option, oIndex) => (
                      <label key={oIndex} className="flex items-center">
                        <input
                          type="radio"
                          name={`question-${qIndex}`}
                          value={oIndex}
                          checked={quizAnswers[qIndex] === oIndex}
                          onChange={() => handleQuizAnswer(qIndex, oIndex)}
                          className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                        />
                        <span className="ml-3 text-gray-700">{option}</span>
                      </label>
                    ))}
                  </div>
                </div>
              ))}
            </div>
            
            <div className="flex justify-end space-x-4 mt-6">
              <button
                onClick={() => setCurrentQuiz(null)}
                className="px-6 py-2 text-gray-600 hover:text-gray-800"
              >
                Cancel
              </button>
              <button
                onClick={handleQuizSubmit}
                disabled={quizAnswers.length !== trainingModules.find(m => m.id === currentQuiz)?.quiz.questions.length}
                className="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Submit Quiz
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Training Modules */}
      <div className="space-y-6">
        {trainingModules.map((module) => {
          const isCompleted = completedModules.includes(module.id);
          const isLocked = module.id > 1 && !completedModules.includes(module.id - 1);
          
          return (
            <div key={module.id} className={`bg-white rounded-xl shadow-lg p-6 ${isLocked ? 'opacity-60' : ''}`}>
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <div className="flex items-center mb-2">
                    <div className={`w-8 h-8 rounded-full flex items-center justify-center mr-3 ${
                      isCompleted 
                        ? 'bg-green-100 text-green-600' 
                        : isLocked 
                          ? 'bg-gray-100 text-gray-400'
                          : 'bg-blue-100 text-blue-600'
                    }`}>
                      {isCompleted ? (
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                        </svg>
                      ) : isLocked ? (
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                      ) : (
                        module.id
                      )}
                    </div>
                    <h3 className="text-lg font-semibold text-gray-900">{module.title}</h3>
                  </div>
                  
                  <p className="text-gray-600 mb-4">{module.description}</p>
                  
                  <div className="flex items-center text-sm text-gray-500">
                    <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{module.duration}</span>
                    <span className="mx-2">•</span>
                    <span>{module.quiz.questions.length} quiz questions</span>
                  </div>
                </div>
                
                <div className="ml-6">
                  {isCompleted ? (
                    <div className="bg-green-100 text-green-800 px-4 py-2 rounded-lg text-sm font-medium">
                      ✓ Completed
                    </div>
                  ) : isLocked ? (
                    <div className="bg-gray-100 text-gray-500 px-4 py-2 rounded-lg text-sm font-medium">
                      🔒 Locked
                    </div>
                  ) : (
                    <button
                      onClick={() => handleStartModule(module.id)}
                      className="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors font-medium"
                    >
                      Start Module
                    </button>
                  )}
                </div>
              </div>
            </div>
          );
        })}
      </div>

      {/* Help Section */}
      <div className="mt-8 bg-yellow-50 border border-yellow-200 rounded-lg p-6">
        <div className="flex">
          <div className="flex-shrink-0">
            <svg className="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div className="ml-3">
            <h3 className="text-sm font-medium text-yellow-800">Training Requirements</h3>
            <div className="mt-2 text-sm text-yellow-700">
              <ul className="list-disc list-inside space-y-1">
                <li>Complete all training modules in order</li>
                <li>Pass the quiz for each module with 100% score</li>
                <li>You can retake quizzes if needed</li>
                <li>Training must be completed before starting work</li>
                <li>Contact support if you have technical issues</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
