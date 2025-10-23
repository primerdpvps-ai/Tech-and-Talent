import jsPDF from 'jspdf';

interface InvoiceItem {
  description: string;
  quantity: number;
  unitPrice: number;
  total: number;
}

interface Customer {
  name: string;
  email: string;
  address: string;
}

interface InvoiceData {
  invoiceNumber: string;
  date: Date;
  dueDate: Date;
  customer: Customer;
  items: InvoiceItem[];
  subtotal: number;
  tax: number;
  total: number;
  currency: string;
}

export async function generateInvoicePDF(data: InvoiceData): Promise<Buffer> {
  const doc = new jsPDF();
  
  // Company header
  doc.setFontSize(20);
  doc.setFont('helvetica', 'bold');
  doc.text('TTS PMS', 20, 30);
  
  doc.setFontSize(10);
  doc.setFont('helvetica', 'normal');
  doc.text('Professional Management System', 20, 38);
  doc.text('Email: admin@tts-pms.com', 20, 45);
  doc.text('Phone: +1 (555) 123-4567', 20, 52);
  
  // Invoice title and number
  doc.setFontSize(24);
  doc.setFont('helvetica', 'bold');
  doc.text('INVOICE', 150, 30);
  
  doc.setFontSize(12);
  doc.setFont('helvetica', 'normal');
  doc.text(`Invoice #: ${data.invoiceNumber}`, 150, 45);
  doc.text(`Date: ${data.date.toLocaleDateString()}`, 150, 52);
  doc.text(`Due Date: ${data.dueDate.toLocaleDateString()}`, 150, 59);
  
  // Customer information
  doc.setFontSize(14);
  doc.setFont('helvetica', 'bold');
  doc.text('Bill To:', 20, 80);
  
  doc.setFontSize(11);
  doc.setFont('helvetica', 'normal');
  doc.text(data.customer.name, 20, 90);
  doc.text(data.customer.email, 20, 97);
  if (data.customer.address) {
    const addressLines = data.customer.address.split('\n');
    addressLines.forEach((line, index) => {
      doc.text(line, 20, 104 + (index * 7));
    });
  }
  
  // Items table header
  const tableStartY = 130;
  doc.setFontSize(11);
  doc.setFont('helvetica', 'bold');
  
  // Table headers
  doc.text('Description', 20, tableStartY);
  doc.text('Qty', 120, tableStartY);
  doc.text('Unit Price', 140, tableStartY);
  doc.text('Total', 170, tableStartY);
  
  // Draw header line
  doc.line(20, tableStartY + 3, 190, tableStartY + 3);
  
  // Items
  doc.setFont('helvetica', 'normal');
  let currentY = tableStartY + 15;
  
  data.items.forEach((item) => {
    doc.text(item.description, 20, currentY);
    doc.text(item.quantity.toString(), 120, currentY);
    doc.text(`${data.currency} ${item.unitPrice.toFixed(2)}`, 140, currentY);
    doc.text(`${data.currency} ${item.total.toFixed(2)}`, 170, currentY);
    currentY += 10;
  });
  
  // Draw line before totals
  doc.line(120, currentY + 5, 190, currentY + 5);
  
  // Totals
  currentY += 15;
  doc.setFont('helvetica', 'normal');
  doc.text('Subtotal:', 140, currentY);
  doc.text(`${data.currency} ${data.subtotal.toFixed(2)}`, 170, currentY);
  
  if (data.tax > 0) {
    currentY += 8;
    doc.text('Tax:', 140, currentY);
    doc.text(`${data.currency} ${data.tax.toFixed(2)}`, 170, currentY);
  }
  
  currentY += 8;
  doc.setFont('helvetica', 'bold');
  doc.text('Total:', 140, currentY);
  doc.text(`${data.currency} ${data.total.toFixed(2)}`, 170, currentY);
  
  // Payment information
  currentY += 25;
  doc.setFontSize(10);
  doc.setFont('helvetica', 'normal');
  doc.text('Payment Status: PAID', 20, currentY);
  doc.text('Payment Method: Online Payment', 20, currentY + 7);
  
  // Footer
  doc.setFontSize(8);
  doc.text('Thank you for your business!', 20, 270);
  doc.text('This is a computer-generated invoice.', 20, 277);
  
  // Convert to buffer
  const pdfOutput = doc.output('arraybuffer');
  return Buffer.from(pdfOutput);
}

export function formatCurrency(amount: number, currency: string = 'USD'): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency,
  }).format(amount);
}
