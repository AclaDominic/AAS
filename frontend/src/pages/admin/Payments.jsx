import { useState } from 'react'
import { offersService } from '../../services/offersService'
import AdminLayout from '../../components/layout/AdminLayout'

function Payments() {
  const [code, setCode] = useState('')
  const [payment, setPayment] = useState(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [success, setSuccess] = useState('')

  const handleSearch = async (e) => {
    e.preventDefault()
    setError('')
    setSuccess('')
    setPayment(null)

    if (code.length !== 8) {
      setError('Payment code must be 8 characters.')
      return
    }

    try {
      setLoading(true)
      const data = await offersService.findPaymentByCode(code.toUpperCase())
      setPayment(data)
    } catch (err) {
      setError(err.response?.data?.message || 'Payment not found.')
      setPayment(null)
    } finally {
      setLoading(false)
    }
  }

  const handleMarkAsPaid = async () => {
    if (!confirm('Mark this payment as paid? This will create the membership subscription.')) {
      return
    }

    try {
      setLoading(true)
      setError('')
      await offersService.markPaymentAsPaid(payment.id)
      setSuccess('Payment marked as paid and subscription created successfully!')
      setPayment(null)
      setCode('')
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to mark payment as paid.')
    } finally {
      setLoading(false)
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
    return new Date(dateString).toLocaleDateString()
  }

  return (
    <AdminLayout>
      <div style={{ padding: '40px' }}>
        <h1 style={{ marginBottom: '30px', fontSize: '2.5rem', color: '#646cff' }}>
          Payment Processing
        </h1>

        <div style={{
          backgroundColor: 'white',
          padding: '30px',
          borderRadius: '12px',
          marginBottom: '30px',
          boxShadow: '0 2px 8px rgba(0, 0, 0, 0.1)',
        }}>
          <h2 style={{ marginTop: 0, marginBottom: '20px' }}>Enter Payment Code</h2>
          
          <form onSubmit={handleSearch} style={{ display: 'flex', gap: '10px', marginBottom: '20px' }}>
            <input
              type="text"
              value={code}
              onChange={(e) => setCode(e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 8))}
              placeholder="Enter 8-character code"
              maxLength={8}
              style={{
                flex: 1,
                padding: '12px',
                fontSize: '1.2rem',
                borderRadius: '6px',
                border: '1px solid #ddd',
                fontFamily: 'monospace',
                letterSpacing: '2px',
                textTransform: 'uppercase',
              }}
            />
            <button
              type="submit"
              disabled={loading || code.length !== 8}
              style={{
                padding: '12px 30px',
                backgroundColor: code.length === 8 ? '#646cff' : '#ccc',
                color: 'white',
                border: 'none',
                borderRadius: '6px',
                cursor: code.length === 8 ? 'pointer' : 'not-allowed',
                fontSize: '1rem',
                fontWeight: 'bold',
              }}
            >
              {loading ? 'Searching...' : 'Search'}
            </button>
          </form>

          {error && (
            <div style={{
              padding: '15px',
              backgroundColor: '#f8d7da',
              color: '#721c24',
              borderRadius: '6px',
              marginBottom: '20px',
            }}>
              {error}
            </div>
          )}

          {success && (
            <div style={{
              padding: '15px',
              backgroundColor: '#d4edda',
              color: '#155724',
              borderRadius: '6px',
              marginBottom: '20px',
            }}>
              {success}
            </div>
          )}
        </div>

        {payment && (
          <div style={{
            backgroundColor: 'white',
            padding: '30px',
            borderRadius: '12px',
            boxShadow: '0 2px 8px rgba(0, 0, 0, 0.1)',
          }}>
            <h2 style={{ marginTop: 0, marginBottom: '20px' }}>Payment Details</h2>

            {payment.status !== 'PENDING' && (
              <div style={{
                padding: '15px',
                backgroundColor: '#fff3cd',
                color: '#856404',
                borderRadius: '6px',
                marginBottom: '20px',
              }}>
                This payment is already {payment.status.toLowerCase()}.
              </div>
            )}

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px', marginBottom: '20px' }}>
              <div>
                <div style={{ color: '#666', fontSize: '0.9rem', marginBottom: '5px' }}>Payment Code</div>
                <div style={{ fontSize: '1.5rem', fontWeight: 'bold', fontFamily: 'monospace', color: '#646cff' }}>
                  {payment.payment_code}
                </div>
              </div>
              <div>
                <div style={{ color: '#666', fontSize: '0.9rem', marginBottom: '5px' }}>Amount</div>
                <div style={{ fontSize: '1.5rem', fontWeight: 'bold' }}>
                  {formatPrice(payment.amount)}
                </div>
              </div>
              <div>
                <div style={{ color: '#666', fontSize: '0.9rem', marginBottom: '5px' }}>Status</div>
                <div style={{
                  display: 'inline-block',
                  padding: '6px 12px',
                  borderRadius: '4px',
                  backgroundColor: payment.status === 'PENDING' ? '#fff3cd' : '#d4edda',
                  color: payment.status === 'PENDING' ? '#856404' : '#155724',
                  fontWeight: 'bold',
                }}>
                  {payment.status}
                </div>
              </div>
              <div>
                <div style={{ color: '#666', fontSize: '0.9rem', marginBottom: '5px' }}>Payment Method</div>
                <div style={{ fontSize: '1rem' }}>
                  {payment.payment_method?.replace('_', ' ')}
                </div>
              </div>
              <div>
                <div style={{ color: '#666', fontSize: '0.9rem', marginBottom: '5px' }}>Member</div>
                <div style={{ fontSize: '1rem' }}>
                  {payment.user?.name} ({payment.user?.email})
                </div>
              </div>
              <div>
                <div style={{ color: '#666', fontSize: '0.9rem', marginBottom: '5px' }}>Created</div>
                <div style={{ fontSize: '1rem' }}>
                  {formatDate(payment.created_at)}
                </div>
              </div>
            </div>

            <div style={{
              padding: '20px',
              backgroundColor: '#f8f9fa',
              borderRadius: '8px',
              marginBottom: '20px',
            }}>
              <h3 style={{ marginTop: 0, marginBottom: '15px' }}>Membership Offer</h3>
              <div style={{ marginBottom: '10px' }}>
                <strong>{payment.membership_offer?.name}</strong>
              </div>
              <div style={{ color: '#666', marginBottom: '5px' }}>
                Category: {payment.membership_offer?.category?.replace('_', ' ')}
              </div>
              <div style={{ color: '#666', marginBottom: '5px' }}>
                Duration: {payment.membership_offer?.duration_value} {payment.membership_offer?.duration_type?.toLowerCase()}
                {payment.membership_offer?.duration_value > 1 ? 's' : ''}
              </div>
              <div style={{ color: '#666' }}>
                Billing: {payment.membership_offer?.billing_type?.replace('_', ' ')}
              </div>

              {payment.promo && (
                <div style={{ marginTop: '15px', paddingTop: '15px', borderTop: '1px solid #ddd' }}>
                  <div style={{ color: '#ff6b35', fontWeight: 'bold' }}>Promo Applied:</div>
                  <div>{payment.promo.name}</div>
                  <div style={{ color: '#666', fontSize: '0.9rem' }}>
                    {payment.promo.discount_type === 'PERCENTAGE' 
                      ? `${payment.promo.discount_value}% off`
                      : `₱${payment.promo.discount_value} off`}
                  </div>
                </div>
              )}

              {payment.first_time_discount && (
                <div style={{ marginTop: '15px', paddingTop: '15px', borderTop: '1px solid #ddd' }}>
                  <div style={{ color: '#ff6b35', fontWeight: 'bold' }}>First-Time Discount Applied:</div>
                  <div>{payment.first_time_discount.name}</div>
                  <div style={{ color: '#666', fontSize: '0.9rem' }}>
                    {payment.first_time_discount.discount_type === 'PERCENTAGE' 
                      ? `${payment.first_time_discount.discount_value}% off`
                      : `₱${payment.first_time_discount.discount_value} off`}
                  </div>
                </div>
              )}
            </div>

            {payment.status === 'PENDING' && payment.payment_method === 'CASH' && (
              <button
                onClick={handleMarkAsPaid}
                disabled={loading}
                style={{
                  padding: '15px 30px',
                  backgroundColor: '#28a745',
                  color: 'white',
                  border: 'none',
                  borderRadius: '6px',
                  cursor: loading ? 'not-allowed' : 'pointer',
                  fontSize: '1rem',
                  fontWeight: 'bold',
                  opacity: loading ? 0.6 : 1,
                }}
              >
                {loading ? 'Processing...' : 'Mark as Paid'}
              </button>
            )}
          </div>
        )}
      </div>
    </AdminLayout>
  )
}

export default Payments

