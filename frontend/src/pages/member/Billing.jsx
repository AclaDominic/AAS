import { useState, useEffect } from 'react'
import { offersService } from '../../services/offersService'
import MemberLayout from '../../components/layout/MemberLayout'

function Billing() {
  const [activeTab, setActiveTab] = useState('pending')
  const [allPayments, setAllPayments] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    loadPayments()
  }, [])

  const loadPayments = async () => {
    try {
      setLoading(true)
      const data = await offersService.getAllPayments()
      setAllPayments(data)
    } catch (error) {
      console.error('Error loading payments:', error)
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

  const handlePayOnline = async (id) => {
    if (!confirm('Process this payment online now?')) {
      return
    }

    try {
      await offersService.processOnlinePayment(id)
      loadPayments()
      alert('Payment processed successfully! Your membership is now active.')
    } catch (error) {
      const errorMessage = error.response?.data?.message || 'Failed to process payment'
      alert(errorMessage)
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
        return '#28a745' // green
      case 'CANCELLED':
        return '#ffc107' // yellow/warning
      case 'FAILED':
        return '#dc3545' // red
      case 'PENDING':
        return '#ff6b35' // orange
      default:
        return '#6c757d' // gray
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

  // Filter payments based on active tab
  const pendingPayments = allPayments.filter(p => p.status === 'PENDING')
  const historyPayments = allPayments.filter(p => 
    p.status === 'PAID' || p.status === 'CANCELLED' || p.status === 'FAILED'
  )

  const currentPayments = activeTab === 'pending' ? pendingPayments : historyPayments

  if (loading) {
    return (
      <MemberLayout>
        <div style={{ padding: '40px', textAlign: 'center', color: '#ffffff' }}>
          Loading...
        </div>
      </MemberLayout>
    )
  }

  return (
    <MemberLayout>
      <div style={{ padding: '40px', minHeight: 'calc(100vh - 80px)' }}>
        <div style={{ maxWidth: '1200px', margin: '0 auto' }}>
          <h1 style={{ fontSize: '3rem', marginBottom: '30px', color: '#ff6b35' }}>
            Billing
          </h1>

          {/* Tab Navigation */}
          <div style={{ 
            display: 'flex', 
            gap: '10px', 
            marginBottom: '30px',
            borderBottom: '2px solid rgba(100, 108, 255, 0.3)',
          }}>
            <button
              onClick={() => setActiveTab('pending')}
              style={{
                padding: '12px 24px',
                backgroundColor: activeTab === 'pending' ? '#646cff' : 'transparent',
                color: activeTab === 'pending' ? '#ffffff' : 'rgba(255, 255, 255, 0.7)',
                border: 'none',
                borderBottom: activeTab === 'pending' ? '3px solid #ff6b35' : '3px solid transparent',
                borderRadius: '6px 6px 0 0',
                cursor: 'pointer',
                fontSize: '1rem',
                fontWeight: activeTab === 'pending' ? 'bold' : 'normal',
                transition: 'all 0.2s',
              }}
            >
              Pending {pendingPayments.length > 0 && `(${pendingPayments.length})`}
            </button>
            <button
              onClick={() => setActiveTab('history')}
              style={{
                padding: '12px 24px',
                backgroundColor: activeTab === 'history' ? '#646cff' : 'transparent',
                color: activeTab === 'history' ? '#ffffff' : 'rgba(255, 255, 255, 0.7)',
                border: 'none',
                borderBottom: activeTab === 'history' ? '3px solid #ff6b35' : '3px solid transparent',
                borderRadius: '6px 6px 0 0',
                cursor: 'pointer',
                fontSize: '1rem',
                fontWeight: activeTab === 'history' ? 'bold' : 'normal',
                transition: 'all 0.2s',
              }}
            >
              History {historyPayments.length > 0 && `(${historyPayments.length})`}
            </button>
          </div>

          {/* Tab Content */}
          {currentPayments.length === 0 ? (
            <div style={{
              padding: '40px',
              textAlign: 'center',
              backgroundColor: 'rgba(100, 108, 255, 0.1)',
              borderRadius: '12px',
              color: '#ffffff',
            }}>
              <p style={{ fontSize: '1.2rem' }}>
                {activeTab === 'pending' 
                  ? 'No pending payments.' 
                  : 'No payment history.'}
              </p>
              {activeTab === 'pending' && (
                <p style={{ color: 'rgba(255, 255, 255, 0.6)' }}>
                  Visit the Membership page to purchase a membership.
                </p>
              )}
            </div>
          ) : (
            <div style={{ display: 'flex', flexDirection: 'column', gap: '20px' }}>
              {currentPayments.map((payment) => (
                <div
                  key={payment.id}
                  style={{
                    backgroundColor: 'rgba(100, 108, 255, 0.1)',
                    borderRadius: '12px',
                    padding: '25px',
                    border: '1px solid rgba(100, 108, 255, 0.3)',
                  }}
                >
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'start', marginBottom: '15px' }}>
                    <div style={{ flex: 1 }}>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '15px', marginBottom: '10px' }}>
                        <h3 style={{ color: '#ffffff', margin: 0, fontSize: '1.5rem' }}>
                          {payment.membership_offer?.name}
                        </h3>
                        {activeTab === 'history' && (
                          <span
                            style={{
                              padding: '4px 12px',
                              backgroundColor: getStatusColor(payment.status) + '20',
                              color: getStatusColor(payment.status),
                              borderRadius: '6px',
                              fontSize: '0.85rem',
                              fontWeight: 'bold',
                              border: `1px solid ${getStatusColor(payment.status)}40`,
                            }}
                          >
                            {getStatusLabel(payment.status)}
                          </span>
                        )}
                      </div>
                      <div style={{ color: 'rgba(255, 255, 255, 0.7)', marginBottom: '5px' }}>
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
                            Payment Code:
                          </div>
                          <div style={{ color: '#ff6b35', fontSize: '1.5rem', fontWeight: 'bold', fontFamily: 'monospace' }}>
                            {payment.payment_code}
                          </div>
                          <div style={{ color: 'rgba(255, 255, 255, 0.6)', fontSize: '0.8rem', marginTop: '5px' }}>
                            Bring this code when paying in person
                          </div>
                        </div>
                      )}
                    </div>
                    <div style={{ textAlign: 'right', minWidth: '200px' }}>
                      <div style={{ color: '#ffffff', fontSize: '2rem', fontWeight: 'bold', marginBottom: '10px' }}>
                        {formatPrice(payment.amount)}
                      </div>
                      <div style={{ color: 'rgba(255, 255, 255, 0.6)', fontSize: '0.9rem', marginBottom: '5px' }}>
                        {payment.payment_method?.replace('_', ' ')}
                      </div>
                      <div style={{ color: 'rgba(255, 255, 255, 0.6)', fontSize: '0.9rem', marginBottom: '5px' }}>
                        Created: {formatDate(payment.created_at)}
                      </div>
                      {activeTab === 'history' && payment.payment_date && (
                        <div style={{ color: 'rgba(255, 255, 255, 0.6)', fontSize: '0.9rem' }}>
                          Paid: {formatDateTime(payment.payment_date)}
                        </div>
                      )}
                    </div>
                  </div>

                  {/* Action Buttons - Only show in Pending tab */}
                  {activeTab === 'pending' && (
                    <div style={{ display: 'flex', gap: '10px', marginTop: '20px' }}>
                      {payment.payment_method === 'CASH' || payment.payment_method === 'CASH_WALKIN' ? (
                        <>
                          <button
                            onClick={() => handlePayOnline(payment.id)}
                            style={{
                              padding: '10px 20px',
                              backgroundColor: '#646cff',
                              color: 'white',
                              border: 'none',
                              borderRadius: '6px',
                              cursor: 'pointer',
                              fontSize: '0.9rem',
                              transition: 'background-color 0.2s',
                            }}
                            onMouseOver={(e) => e.target.style.backgroundColor = '#5558e8'}
                            onMouseOut={(e) => e.target.style.backgroundColor = '#646cff'}
                          >
                            Pay Online Now
                          </button>
                          <button
                            onClick={() => handleCancel(payment.id)}
                            style={{
                              padding: '10px 20px',
                              backgroundColor: '#dc3545',
                              color: 'white',
                              border: 'none',
                              borderRadius: '6px',
                              cursor: 'pointer',
                              fontSize: '0.9rem',
                              transition: 'background-color 0.2s',
                            }}
                            onMouseOver={(e) => e.target.style.backgroundColor = '#c82333'}
                            onMouseOut={(e) => e.target.style.backgroundColor = '#dc3545'}
                          >
                            Cancel
                          </button>
                        </>
                      ) : (
                        <button
                          onClick={() => handleCancel(payment.id)}
                          style={{
                            padding: '10px 20px',
                            backgroundColor: '#dc3545',
                            color: 'white',
                            border: 'none',
                            borderRadius: '6px',
                            cursor: 'pointer',
                            fontSize: '0.9rem',
                            transition: 'background-color 0.2s',
                          }}
                          onMouseOver={(e) => e.target.style.backgroundColor = '#c82333'}
                          onMouseOut={(e) => e.target.style.backgroundColor = '#dc3545'}
                        >
                          Cancel
                        </button>
                      )}
                    </div>
                  )}
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </MemberLayout>
  )
}

export default Billing
