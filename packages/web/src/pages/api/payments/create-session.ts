import { NextApiRequest, NextApiResponse } from 'next';
import Stripe from 'stripe';
import { prisma } from '../../../lib/prisma';
import { getServerSession } from 'next-auth';
import { authOptions } from '../auth/[...nextauth]';

const stripe = new Stripe(process.env.STRIPE_SECRET_KEY!, {
  apiVersion: '2023-10-16',
});

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Method not allowed' });
  }

  try {
    const session = await getServerSession(req, res, authOptions);
    if (!session?.user) {
      return res.status(401).json({ error: 'Unauthorized' });
    }

    const { 
      amount, 
      currency = 'USD', 
      paymentMethod = 'stripe',
      description,
      metadata = {},
      successUrl,
      cancelUrl
    } = req.body;

    // Validate required fields
    if (!amount || amount <= 0) {
      return res.status(400).json({ error: 'Invalid amount' });
    }

    // Get payment gateway settings
    const paymentSettings = await prisma.systemSettings.findFirst({
      where: { key: 'payment_gateways' }
    });

    if (!paymentSettings) {
      return res.status(500).json({ error: 'Payment gateways not configured' });
    }

    const gateways = JSON.parse(paymentSettings.value);
    const gateway = gateways.find((g: any) => g.id === paymentMethod && g.enabled);

    if (!gateway) {
      return res.status(400).json({ error: 'Payment method not available' });
    }

    let paymentSession;

    switch (paymentMethod) {
      case 'stripe':
        paymentSession = await createStripeSession({
          amount,
          currency,
          description,
          metadata: {
            ...metadata,
            userId: session.user.id,
            gateway: 'stripe'
          },
          successUrl: successUrl || `${process.env.NEXTAUTH_URL}/payments/success`,
          cancelUrl: cancelUrl || `${process.env.NEXTAUTH_URL}/payments/cancel`
        });
        break;

      case 'paypal':
        paymentSession = await createPayPalSession({
          amount,
          currency,
          description,
          metadata,
          successUrl,
          cancelUrl
        });
        break;

      default:
        return res.status(400).json({ error: 'Unsupported payment method' });
    }

    // Create payment record in database
    const payment = await prisma.payment.create({
      data: {
        userId: session.user.id,
        amount: amount,
        currency: currency.toUpperCase(),
        status: 'PENDING',
        paymentMethod: paymentMethod.toUpperCase(),
        sessionId: paymentSession.id,
        description: description || 'Payment',
        metadata: JSON.stringify(metadata)
      }
    });

    res.status(200).json({
      success: true,
      sessionId: paymentSession.id,
      paymentId: payment.id,
      checkoutUrl: paymentSession.url,
      clientSecret: paymentSession.client_secret
    });

  } catch (error) {
    console.error('Payment session creation error:', error);
    res.status(500).json({ 
      error: 'Failed to create payment session',
      details: process.env.NODE_ENV === 'development' ? error.message : undefined
    });
  }
}

async function createStripeSession(params: {
  amount: number;
  currency: string;
  description: string;
  metadata: Record<string, any>;
  successUrl: string;
  cancelUrl: string;
}) {
  const session = await stripe.checkout.sessions.create({
    payment_method_types: ['card'],
    line_items: [
      {
        price_data: {
          currency: params.currency.toLowerCase(),
          product_data: {
            name: params.description,
          },
          unit_amount: Math.round(params.amount * 100), // Convert to cents
        },
        quantity: 1,
      },
    ],
    mode: 'payment',
    success_url: `${params.successUrl}?session_id={CHECKOUT_SESSION_ID}`,
    cancel_url: params.cancelUrl,
    metadata: params.metadata,
    payment_intent_data: {
      metadata: params.metadata,
    },
  });

  return {
    id: session.id,
    url: session.url,
    client_secret: session.payment_intent as string
  };
}

async function createPayPalSession(params: {
  amount: number;
  currency: string;
  description: string;
  metadata: Record<string, any>;
  successUrl?: string;
  cancelUrl?: string;
}) {
  // PayPal implementation would go here
  // This is a placeholder for PayPal SDK integration
  throw new Error('PayPal integration not implemented yet');
}
