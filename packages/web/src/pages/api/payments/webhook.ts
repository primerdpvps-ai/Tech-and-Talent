import { NextApiRequest, NextApiResponse } from 'next';
import Stripe from 'stripe';
import { prisma } from '../../../lib/prisma';
import { generateInvoicePDF } from '../../../lib/invoice-generator';

const stripe = new Stripe(process.env.STRIPE_SECRET_KEY!, {
  apiVersion: '2023-10-16',
});

const endpointSecret = process.env.STRIPE_WEBHOOK_SECRET!;

export const config = {
  api: {
    bodyParser: {
      sizeLimit: '1mb',
    },
  },
};

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Method not allowed' });
  }

  const sig = req.headers['stripe-signature'] as string;
  let event: Stripe.Event;

  try {
    const body = JSON.stringify(req.body);
    event = stripe.webhooks.constructEvent(body, sig, endpointSecret);
  } catch (err) {
    console.error('Webhook signature verification failed:', err);
    return res.status(400).json({ error: 'Invalid signature' });
  }

  try {
    switch (event.type) {
      case 'checkout.session.completed':
        await handleCheckoutCompleted(event.data.object as Stripe.Checkout.Session);
        break;

      case 'payment_intent.succeeded':
        await handlePaymentSucceeded(event.data.object as Stripe.PaymentIntent);
        break;

      case 'payment_intent.payment_failed':
        await handlePaymentFailed(event.data.object as Stripe.PaymentIntent);
        break;

      case 'invoice.payment_succeeded':
        await handleInvoicePaymentSucceeded(event.data.object as Stripe.Invoice);
        break;

      default:
        console.log(`Unhandled event type: ${event.type}`);
    }

    res.status(200).json({ received: true });
  } catch (error) {
    console.error('Webhook processing error:', error);
    res.status(500).json({ error: 'Webhook processing failed' });
  }
}

async function handleCheckoutCompleted(session: Stripe.Checkout.Session) {
  console.log('Checkout completed:', session.id);

  // Update payment status in database
  const payment = await prisma.payment.update({
    where: { sessionId: session.id },
    data: {
      status: 'COMPLETED',
      paidAt: new Date(),
      stripePaymentIntentId: session.payment_intent as string
    },
    include: {
      user: true
    }
  });

  // Generate and save invoice
  await generateAndSaveInvoice(payment);

  // Send confirmation email (implement as needed)
  // await sendPaymentConfirmationEmail(payment);
}

async function handlePaymentSucceeded(paymentIntent: Stripe.PaymentIntent) {
  console.log('Payment succeeded:', paymentIntent.id);

  // Update payment status
  await prisma.payment.updateMany({
    where: { stripePaymentIntentId: paymentIntent.id },
    data: {
      status: 'COMPLETED',
      paidAt: new Date()
    }
  });
}

async function handlePaymentFailed(paymentIntent: Stripe.PaymentIntent) {
  console.log('Payment failed:', paymentIntent.id);

  // Update payment status
  await prisma.payment.updateMany({
    where: { stripePaymentIntentId: paymentIntent.id },
    data: {
      status: 'FAILED',
      failureReason: paymentIntent.last_payment_error?.message || 'Payment failed'
    }
  });
}

async function handleInvoicePaymentSucceeded(invoice: Stripe.Invoice) {
  console.log('Invoice payment succeeded:', invoice.id);
  
  // Handle subscription or recurring payment logic here
}

async function generateAndSaveInvoice(payment: any) {
  try {
    // Generate PDF invoice
    const invoiceData = {
      invoiceNumber: `INV-${payment.id.toString().padStart(6, '0')}`,
      date: new Date(),
      dueDate: new Date(),
      customer: {
        name: payment.user.firstName + ' ' + payment.user.lastName,
        email: payment.user.email,
        address: payment.user.address || ''
      },
      items: [
        {
          description: payment.description,
          quantity: 1,
          unitPrice: payment.amount,
          total: payment.amount
        }
      ],
      subtotal: payment.amount,
      tax: 0,
      total: payment.amount,
      currency: payment.currency
    };

    const pdfBuffer = await generateInvoicePDF(invoiceData);

    // Save invoice to database
    const invoice = await prisma.invoice.create({
      data: {
        invoiceNumber: invoiceData.invoiceNumber,
        paymentId: payment.id,
        userId: payment.userId,
        amount: payment.amount,
        currency: payment.currency,
        status: 'PAID',
        issuedAt: new Date(),
        paidAt: new Date(),
        pdfData: pdfBuffer
      }
    });

    console.log('Invoice generated and saved:', invoice.invoiceNumber);
  } catch (error) {
    console.error('Failed to generate invoice:', error);
  }
}
