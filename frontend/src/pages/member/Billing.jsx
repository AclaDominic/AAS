import { useState, useEffect } from 'react'
import { offersService } from '../../services/offersService'
import MemberLayout from '../../components/layout/MemberLayout'
import './MemberPages.css'

function Billing() {
  const [activeTab, setActiveTab] = useState('pending')
  const [allPayments, setAllPayments] = useState([])
  const [billingStatements, setBillingStatements] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    loadPayments()
    loadBillingStatements()
  }, [])

  const loadPayments = async () => {
    try {
      const data = await offersService.getAllPayments()
      setAllPayments(data)
    } catch (error) {
      console.error('Error loading payments:', error)
    }
  }

  const loadBillingStatements = async () => {
    try {
      const data = await offersService.getBillingStatements()
      setBillingStatements(data || [])
    } catch (error) {
      console.error('Error loading billing statements:', error)
      setBillingStatements([])
    } finally {
      setLoading(false)
    }
  }

  const handleCancel = async (id) => {
    if (!confirm('Are you sure you want to cancel this payment? This action cannot be undone.')) {
      return
    }

    try {
      await offersService.cancelPayment(id)
      loadPayments()
      alert('Payment cancelled successfully.')
    } catch (error) {
      const errorMessage = error.response?.data?.message || 'Failed to cancel payment'
      alert(errorMessage)
    }
  }

  const handlePayOnline = async (id, paymentMethodPreference = null) => {
    if (!confirm('Process this payment online now using Credit/Debit Card? You will be redirected to Maya to complete the payment.')) {
      return
    }

    try {
      const result = await offersService.processOnlinePayment(id, 'CARD')
      
      if (result.checkout_url) {
        window.location.href = result.checkout_url
      } else {
        loadPayments()
        loadBillingStatements()
        alert('Payment processed successfully! Your membership is now active.')
      }
    } catch (error) {
      const errorMessage = error.response?.data?.message || 'Failed to process payment'
      alert(errorMessage)
    }
  }

  const handleDownloadInvoice = async (statementId) => {
    try {
      const blob = await offersService.downloadInvoice(statementId)
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `invoice-${statementId}.pdf`)
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
    } catch (error) {
      console.error('Error downloading invoice:', error)
      alert('Failed to download invoice. Please try again.')
    }
  }

  const handleDownloadReceipt = async (paymentId) => {
    try {
      const blob = await offersService.downloadReceipt(paymentId)
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `receipt-${paymentId}.pdf`)
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
    } catch (err) {
      console.error('Error downloading receipt:', err)
      alert('Failed to download receipt. Please try again.')
    }
  }

  const getStatementStatusColor = (status) => {
    switch (status) {
      case 'PAID':
        return '#28a745'
      case 'PENDING':
        return '#ff6b35'
      case 'OVERDUE':
        return '#dc3545'
      default:
        return '#6c757d'
    }
  }

  const formatPrice = (price) => {
    return new Intl.NumberFormat('en-PH', {
      style: 'currency',
      currency: 'PHP',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(price)
  }

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    })
  }

  const formatDateTime = (dateString) => {
    return new Date(dateString).toLocaleString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  const getStatusColor = (status) => {
    switch (status) {
      case 'PAID':
        return '#28a745'
      case 'CANCELLED':
        return '#ffc107'
      case 'FAILED':
        return '#dc3545'
      case 'PENDING':
        return '#ff6b35'
      default:
        return '#6c757d'
    }
  }

  const getStatusLabel = (status) => {
    switch (status) {
      case 'PAID':
        return 'Paid'
      case 'CANCELLED':
        return 'Cancelled'
      case 'FAILED':
        return 'Failed'
      case 'PENDING':
        return 'Pending'
      default:
        return status
    }
  }

  const pendingPayments = allPayments.filter(p => p.status === 'PENDING')
  const historyPayments = allPayments.filter(p => 
    p.status === 'PAID' || p.status === 'CANCELLED' || p.status === 'FAILED'
  )

  const currentPayments = activeTab === 'pending' ? pendingPayments : historyPayments

  if (loading) {
    return (
      <MemberLayout>
        <div className="member-loading">Loading...</div>
      </MemberLayout>
    )
  }

  return (
    <MemberLayout>
      <div className="billing-container">
        <h1 className="billing-title">Order History</h1>
        <p style={{ color: 'rgba(255, 255, 255, 0.6)', marginBottom: '30px', fontSize: '0.95rem' }}>
          Manage billing information and view receipts
        </p>

        {/* Tab Navigation */}
        <div className="billing-tabs">
          <button
            onClick={() => setActiveTab('pending')}
            className={`billing-tab ${activeTab === 'pending' ? 'active' : ''}`}
          >
            Pending {pendingPayments.length > 0 && `(${pendingPayments.length})`}
          </button>
          <button
            onClick={() => setActiveTab('history')}
            className={`billing-tab ${activeTab === 'history' ? 'active' : ''}`}
          >
            History {historyPayments.length > 0 && `(${historyPayments.length})`}
          </button>
          <button
            onClick={() => setActiveTab('statements')}
            className={`billing-tab ${activeTab === 'statements' ? 'active' : ''}`}
          >
            Billing Statements {billingStatements.length > 0 && `(${billingStatements.length})`}
          </button>
        </div>

        {/* Tab Content */}
        {activeTab === 'statements' ? (
          billingStatements.length === 0 ? (
            <div className="billing-empty">
              <p>No billing statements found.</p>
              <p>Billing statements will appear here when your membership is up for renewal.</p>
            </div>
          ) : (
            <div className="billing-list">
              {billingStatements.map((statement) => (
                <div key={statement.id} className="billing-card">
                  <div className="billing-card-header">
                    <div style={{ flex: 1 }}>
                      <h3 className="billing-card-title">
                        {statement.membership_subscription?.membership_offer?.name || 'Membership Renewal'}
                        <span
                          className="billing-status-badge"
                          style={{
                            backgroundColor: getStatementStatusColor(statement.status) + '20',
                            color: getStatementStatusColor(statement.status),
                            border: `1px solid ${getStatementStatusColor(statement.status)}40`,
                          }}
                        >
                          {statement.status}
                        </span>
                      </h3>
                      <div className="billing-card-info">
                        Statement Date: {formatDate(statement.statement_date)}
                      </div>
                      {statement.period_start && statement.period_end && (
                        <div className="billing-card-info">
                          Period: {formatDate(statement.period_start)} - {formatDate(statement.period_end)}
                        </div>
                      )}
                      {statement.due_date && (
                        <div className="billing-card-info">
                          Due Date: {formatDate(statement.due_date)}
                        </div>
                      )}
                      <div className="billing-card-actions" style={{ marginTop: '15px' }}>
                        <button
                          onClick={() => handleDownloadInvoice(statement.id)}
                          className="billing-button billing-button-primary"
                        >
                          {statement.invoice ? 'Download Invoice' : 'Generate & Download Invoice'}
                        </button>
                        {statement.payment && statement.payment.status === 'PAID' && (
                          <button
                            onClick={() => handleDownloadReceipt(statement.payment.id)}
                            className="billing-button billing-button-success"
                          >
                            Download Receipt
                          </button>
                        )}
                      </div>
                      {statement.payment && (
                        <div className="billing-card-info" style={{ marginTop: '10px', fontSize: '0.9rem', color: 'rgba(255, 255, 255, 0.6)' }}>
                          Payment Code: {statement.payment.payment_code || 'N/A'}
                        </div>
                      )}
                    </div>
                    <div className="billing-card-price">
                      <div className="billing-price-amount">
                        {formatPrice(statement.amount)}
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )
        ) : currentPayments.length === 0 ? (
          <div className="billing-empty">
            <p>
              {activeTab === 'pending' 
                ? 'No pending payments.' 
                : 'No payment history.'}
            </p>
            {activeTab === 'pending' && (
              <p>Visit the Membership page to purchase a membership.</p>
            )}
          </div>
        ) : (
          <div className="billing-list">
            {currentPayments.map((payment) => (
              <div key={payment.id} className="billing-card">
                <div className="billing-card-header">
                  <div style={{ flex: 1 }}>
                    <h3 className="billing-card-title">
                      {payment.membership_offer?.name}
                      {activeTab === 'history' && (
                        <span
                          className="billing-status-badge"
                          style={{
                            backgroundColor: getStatusColor(payment.status) + '20',
                            color: getStatusColor(payment.status),
                            border: `1px solid ${getStatusColor(payment.status)}40`,
                          }}
                        >
                          {getStatusLabel(payment.status)}
                        </span>
                      )}
                    </h3>
                    <div className="billing-card-info">
                      Category: {payment.membership_offer?.category?.replace('_', ' ')}
                    </div>
                    {payment.promo && (
                      <div style={{ color: '#ff6b35', marginBottom: '5px' }}>
                        Promo: {payment.promo.name}
                      </div>
                    )}
                    {payment.first_time_discount && (
                      <div style={{ color: '#ff6b35', marginBottom: '5px' }}>
                        Discount: {payment.first_time_discount.name}
                      </div>
                    )}
                    {payment.payment_code && activeTab === 'pending' && (
                      <div style={{ 
                        marginTop: '15px',
                        padding: '10px',
                        backgroundColor: 'rgba(255, 107, 53, 0.2)',
                        borderRadius: '6px',
                        display: 'inline-block',
                      }}>
                        <div style={{ color: 'rgba(255, 255, 255, 0.7)', fontSize: '0.9rem' }}>
                          Reference Code:
                        </div>
                        <div style={{ color: '#ff6b35', fontSize: '1.5rem', fontWeight: 'bold', fontFamily: 'monospace' }}>
                          {payment.payment_code}
                        </div>
                        <div style={{ color: 'rgba(255, 255, 255, 0.6)', fontSize: '0.8rem', marginTop: '5px' }}>
                          {payment.payment_method === 'CASH' || payment.payment_method === 'CASH_WALKIN' 
                            ? 'Bring this code when paying in person'
                            : 'Use this code to pay walk-in or keep for your records'}
                        </div>
                      </div>
                    )}
                  </div>
                  <div className="billing-card-price">
                    <div className="billing-price-amount">
                      {formatPrice(payment.amount)}
                    </div>
                    <div className="billing-card-info">
                      {payment.payment_method?.replace('_', ' ')}
                    </div>
                    <div className="billing-card-info">
                      Created: {formatDate(payment.created_at)}
                    </div>
                    {activeTab === 'history' && payment.payment_date && (
                      <div className="billing-card-info">
                        Paid: {formatDateTime(payment.payment_date)}
                      </div>
                    )}
                  </div>
                </div>

                {/* Action Buttons */}
                {activeTab === 'pending' && (
                  <div className="billing-card-actions">
                    {(payment.payment_method === 'CASH' || payment.payment_method === 'CASH_WALKIN' || 
                      payment.payment_method === 'ONLINE_MAYA' || payment.payment_method === 'ONLINE_CARD') && (
                      <button
                        onClick={() => handlePayOnline(payment.id)}
                        className="billing-button billing-button-primary"
                      >
                        Pay with Credit/Debit Card
                      </button>
                    )}
                    <button
                      onClick={() => handleCancel(payment.id)}
                      className="billing-button billing-button-danger"
                    >
                      Cancel
                    </button>
                  </div>
                )}
                {activeTab === 'history' && payment.status === 'PAID' && (
                  <div className="billing-card-actions">
                    <button
                      onClick={() => handleDownloadReceipt(payment.id)}
                      className="billing-button billing-button-success"
                    >
                      Download Receipt
                    </button>
                  </div>
                )}
              </div>
            ))}
          </div>
        )}

        {/* Payment Methods Section */}
        <div className="billing-payment-methods">
          <h2 className="billing-payment-methods-title">Payment Method</h2>
          <p className="billing-payment-methods-subtitle">
            Manage billing information and view receipts
          </p>
          <div className="billing-payment-icons">
            <div className="billing-payment-icon">GCash</div>
            <div className="billing-payment-icon">VISA</div>
            <div className="billing-payment-icon">PayPal</div>
          </div>
        </div>
      </div>
    </MemberLayout>
  )
}

export default Billing
