import { prisma } from '@tts-pms/db';
import { getServerSession } from 'next-auth';
import { authOptions } from '@/lib/auth';
import { formatDateTime } from '@tts-pms/infra';

async function getRecentActivity(userId: string, userRole: string) {
  const recentTasks = await prisma.task.findMany({
    where: userRole === 'ADMIN' ? {} :
      userRole === 'AGENT' ? { assigneeId: userId } :
      userRole === 'MANAGER' ? { project: { managerId: userId } } :
      { project: { clientId: userId } },
    include: {
      project: {
        select: { name: true }
      },
      assignee: {
        select: { firstName: true, lastName: true }
      }
    },
    orderBy: { updatedAt: 'desc' },
    take: 5,
  });

  return recentTasks;
}

export async function RecentActivity() {
  const session = await getServerSession(authOptions);
  
  if (!session) return null;

  const activities = await getRecentActivity(session.user.id, session.user.role);

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'COMPLETED': return 'bg-green-100 text-green-800';
      case 'IN_PROGRESS': return 'bg-blue-100 text-blue-800';
      case 'IN_REVIEW': return 'bg-yellow-100 text-yellow-800';
      case 'BLOCKED': return 'bg-red-100 text-red-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  return (
    <div className="card">
      <h3 className="text-lg font-medium text-gray-900 mb-4">Recent Activity</h3>
      {activities.length === 0 ? (
        <p className="text-gray-500 text-center py-4">No recent activity</p>
      ) : (
        <div className="space-y-3">
          {activities.map((task) => (
            <div key={task.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
              <div className="flex-1">
                <h4 className="text-sm font-medium text-gray-900">{task.title}</h4>
                <p className="text-sm text-gray-500">
                  {task.project.name} • {task.assignee ? `${task.assignee.firstName} ${task.assignee.lastName}` : 'Unassigned'}
                </p>
                <p className="text-xs text-gray-400">
                  Updated {formatDateTime(task.updatedAt)}
                </p>
              </div>
              <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(task.status)}`}>
                {task.status.replace('_', ' ')}
              </span>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
