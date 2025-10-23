import { prisma } from '@tts-pms/db';
import { getServerSession } from 'next-auth';
import { authOptions } from '@/lib/auth';

async function getStats(userId: string, userRole: string) {
  const baseWhere = userRole === 'ADMIN' ? {} : 
    userRole === 'MANAGER' ? { managerId: userId } :
    userRole === 'AGENT' ? { 
      tasks: { some: { assigneeId: userId } }
    } : { clientId: userId };

  const [totalProjects, activeProjects, totalTasks, completedTasks] = await Promise.all([
    prisma.project.count({ where: baseWhere }),
    prisma.project.count({ 
      where: { ...baseWhere, status: 'ACTIVE' }
    }),
    prisma.task.count({
      where: userRole === 'ADMIN' ? {} :
        userRole === 'AGENT' ? { assigneeId: userId } :
        { project: baseWhere }
    }),
    prisma.task.count({
      where: {
        status: 'COMPLETED',
        ...(userRole === 'ADMIN' ? {} :
          userRole === 'AGENT' ? { assigneeId: userId } :
          { project: baseWhere })
      }
    })
  ]);

  return {
    totalProjects,
    activeProjects,
    totalTasks,
    completedTasks,
  };
}

export async function DashboardStats() {
  const session = await getServerSession(authOptions);
  
  if (!session) return null;

  const stats = await getStats(session.user.id, session.user.role);

  const statItems = [
    {
      name: 'Total Projects',
      value: stats.totalProjects,
      icon: '📁',
      color: 'bg-blue-500',
    },
    {
      name: 'Active Projects',
      value: stats.activeProjects,
      icon: '🚀',
      color: 'bg-green-500',
    },
    {
      name: 'Total Tasks',
      value: stats.totalTasks,
      icon: '📋',
      color: 'bg-purple-500',
    },
    {
      name: 'Completed Tasks',
      value: stats.completedTasks,
      icon: '✅',
      color: 'bg-orange-500',
    },
  ];

  return (
    <div className="card">
      <h3 className="text-lg font-medium text-gray-900 mb-4">Overview</h3>
      <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
        {statItems.map((item) => (
          <div key={item.name} className="text-center">
            <div className={`inline-flex h-12 w-12 items-center justify-center rounded-full ${item.color} text-white text-xl mb-2`}>
              {item.icon}
            </div>
            <div className="text-2xl font-semibold text-gray-900">{item.value}</div>
            <div className="text-sm text-gray-500">{item.name}</div>
          </div>
        ))}
      </div>
    </div>
  );
}
