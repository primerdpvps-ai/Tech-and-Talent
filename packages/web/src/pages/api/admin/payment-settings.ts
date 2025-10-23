import { NextApiRequest, NextApiResponse } from 'next';
import { getServerSession } from 'next-auth';
import { authOptions } from '../auth/[...nextauth]';
import { prisma } from '../../../lib/prisma';

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  const session = await getServerSession(req, res, authOptions);
  
  if (!session?.user || session.user.role !== 'ADMIN') {
    return res.status(403).json({ error: 'Unauthorized' });
  }

  if (req.method === 'GET') {
    try {
      const settings = await prisma.systemSettings.findFirst({
        where: { key: 'payment_gateways' }
      });

      const gateways = settings ? JSON.parse(settings.value) : [];
      res.status(200).json({ gateways });
    } catch (error) {
      res.status(500).json({ error: 'Failed to fetch payment settings' });
    }
  } else if (req.method === 'POST') {
    try {
      const { gateways } = req.body;

      await prisma.systemSettings.upsert({
        where: { key: 'payment_gateways' },
        update: { value: JSON.stringify(gateways) },
        create: {
          key: 'payment_gateways',
          value: JSON.stringify(gateways),
          description: 'Payment gateway configurations',
          category: 'payment'
        }
      });

      res.status(200).json({ success: true });
    } catch (error) {
      res.status(500).json({ error: 'Failed to save payment settings' });
    }
  } else {
    res.status(405).json({ error: 'Method not allowed' });
  }
}
