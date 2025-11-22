import { useState, useEffect } from 'react'
import { offersService } from '../../services/offersService'
import AdminLayout from '../../components/layout/AdminLayout'

function Payments() {
  const [code, setCode] = useState('')
  const [payment, setPayment] = useState(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [success, setSuccess] = useState('')
  
  // Billing statement generation
  const [activeTab, setActiveTab] = useState('search')
  const [startDate, setStartDate] = useState('')
  const [endDate, setEndDate] = useState('')
  const [generating, setGenerating] = useState(false)
  const [generationResult, setGenerationResult] = useState(null)

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

  const handleGenerateStatements = async (e) => {
    e.preventDefault()
    setError('')
    setSuccess('')
    setGenerationResult(null)

    if (!startDate || !endDate) {
      setError('Please select both start and end dates.')
      return
    }

    if (new Date(startDate) >= new Date(endDate)) {
      setError('End date must be after start date.')
      return
    }

    try {
      setGenerating(true)
      const result = await offersService.generateBillingStatements(startDate, endDate)
      setGenerationResult(result)
      setSuccess(`Successfully generated ${result.summary?.statements_generated || 0} billing statement(s).`)
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to generate billing statements.')
      setGenerationResult(null)
    } finally {
      setGenerating(false)
    }
  }

  // Set default dates on component mount
  useEffect(() => {
    const today = new Date().toISOString().split('T')[0]
    const futureDate = new Date()
    futureDate.setDate(futureDate.getDate() + 30)
    const futureDateStr = futureDate.toISOString().split('T')[0]
    if (!startDate) setStartDate(today)
    if (!endDate) setEndDate(futureDateStr)
  }, [])

  return (
    <AdminLayout>
      <div style={{ padding: '40px' }}>
        <h1 style={{ marginBottom: '30px', fontSize: '2.5rem', color: '#646cff' }}>
          Payment Processing
        </h1>

        {/* Tab Navigation */}
        <div style={{ 
          display: 'flex', 
          gap: '10px', 
          marginBottom: '30px',
          borderBottom: '2px solid #ddd',
        }}>
          <button
            onClick={() => setActiveTab('search')}
            style={{
              padding: '12px 24px',
              backgroundColor: activeTab === 'search' ? '#646cff' : 'transparent',
              color: activeTab === 'search' ? '#ffffff' : '#333',
              border: 'none',
              borderBottom: activeTab === 'search' ? '3px solid #646cff' : '3px solid transparent',
              borderRadius: '6px 6px 0 0',
              cursor: 'pointer',
              fontSize: '1rem',
              fontWeight: activeTab === 'search' ? 'bold' : 'normal',
            }}
          >
            Search Payment
          </button>
          <button
            onClick={() => setActiveTab('generate')}
            style={{
              padding: '12px 24px',
              backgroundColor: activeTab === 'generate' ? '#646cff' : 'transparent',
              color: activeTab === 'generate' ? '#ffffff' : '#333',
              border: 'none',
              borderBottom: activeTab === 'generate' ? '3px solid #646cff' : '3px solid transparent',
              borderRadius: '6px 6px 0 0',
              cursor: 'pointer',
              fontSize: '1rem',
              fontWeight: activeTab === 'generate' ? 'bold' : 'normal',
            }}
          >
            Generate Billing Statements
          </button>
        </div>

        {activeTab === 'generate' && (
          <div style={{
            backgroundColor: 'white',
            padding: '30px',
            borderRadius: '12px',
            marginBottom: '30px',
            boxShadow: '0 2px 8px rgba(0, 0, 0, 0.1)',
          }}>
            <h2 style={{ marginTop: 0, marginBottom: '20px' }}>Manual Billing Statement Generation</h2>
            
            <div style={{
              backgroundColor: '#fff3cd',
              border: '2px solid #ffc107',
              borderRadius: '8px',
              padding: '20px',
              marginBottom: '25px',
              boxShadow: '0 2px 4px rgba(0,0,0,0.1)',
            }}>
              <div style={{ display: 'flex', alignItems: 'start', gap: '15px' }}>
                <div style={{
                  fontSize: '28px',
                  lineHeight: '1',
                  flexShrink: 0,
                }}>⚠️</div>
                <div style={{ flex: 1 }}>
                  <strong style={{ 
                    color: '#856404', 
                    display: 'block', 
                    marginBottom: '8px',
                    fontSize: '1.1rem',
                  }}>
                    ⚡ Automatic Billing Generation is Active
                  </strong>
                  <p style={{ color: '#856404', margin: '0 0 10px 0', fontSize: '0.95rem', lineHeight: '1.6' }}>
                    <strong>Billing statements are automatically generated daily at 1:00 AM</strong> for all recurring subscriptions expiring within 5 days. 
                    This process runs automatically and requires no admin intervention.
                  </p>
                  <div style={{
                    backgroundColor: 'rgba(255,255,255,0.7)',
                    padding: '12px',
                    borderRadius: '6px',
                    marginTop: '10px',
                  }}>
                    <strong style={{ color: '#856404', display: 'block', marginBottom: '6px', fontSize: '0.9rem' }}>
                      Use this manual tool only for:
                    </strong>
                    <ul style={{ 
                      color: '#856404', 
                      margin: '0', 
                      paddingLeft: '20px', 
                      fontSize: '0.9rem',
                      lineHeight: '1.8',
                    }}>
                      <li>Generating statements for custom date ranges (outside the 5-day window)</li>
                      <li>Catching up on missed statements if scheduler was down</li>
                      <li>Bulk generation for reporting or audits</li>
                      <li>Testing or debugging purposes</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>

            <p style={{ color: '#666', marginBottom: '20px' }}>
              Generate billing statements for recurring subscriptions that expire within the specified date range.
            </p>

            <form onSubmit={handleGenerateStatements}>
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px', marginBottom: '20px' }}>
                <div>
                  <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold' }}>
                    Start Date
                  </label>
                  <input
                    type="date"
                    value={startDate}
                    onChange={(e) => setStartDate(e.target.value)}
                    required
                    style={{
                      width: '100%',
                      padding: '12px',
                      fontSize: '1rem',
                      borderRadius: '6px',
                      border: '1px solid #ddd',
                    }}
                  />
                </div>
                <div>
                  <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold' }}>
                    End Date
                  </label>
                  <input
                    type="date"
                    value={endDate}
                    onChange={(e) => setEndDate(e.target.value)}
                    required
                    style={{
                      width: '100%',
                      padding: '12px',
                      fontSize: '1rem',
                      borderRadius: '6px',
                      border: '1px solid #ddd',
                    }}
                  />
                </div>
              </div>

              <button
                type="submit"
                disabled={generating || !startDate || !endDate}
                style={{
                  padding: '15px 30px',
                  backgroundColor: generating || !startDate || !endDate ? '#ccc' : '#646cff',
                  color: 'white',
                  border: 'none',
                  borderRadius: '6px',
                  cursor: generating || !startDate || !endDate ? 'not-allowed' : 'pointer',
                  fontSize: '1rem',
                  fontWeight: 'bold',
                  opacity: generating || !startDate || !endDate ? 0.6 : 1,
                }}
              >
                {generating ? 'Generating...' : 'Generate Statements'}
              </button>
            </form>

            {error && (
              <div style={{
                padding: '15px',
                backgroundColor: '#f8d7da',
                color: '#721c24',
                borderRadius: '6px',
                marginTop: '20px',
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
                marginTop: '20px',
              }}>
                {success}
              </div>
            )}

            {generationResult && (
              <div style={{
                marginTop: '30px',
                padding: '20px',
                backgroundColor: '#f8f9fa',
                borderRadius: '8px',
              }}>
                <h3 style={{ marginTop: 0, marginBottom: '15px' }}>Generation Summary</h3>
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: '20px' }}>
                  <div>
                    <div style={{ color: '#666', fontSize: '0.9rem', marginBottom: '5px' }}>Statements Generated</div>
                    <div style={{ fontSize: '1.5rem', fontWeight: 'bold' }}>
                      {generationResult.summary?.statements_generated || 0}
                    </div>
                  </div>
                  <div>
                    <div style={{ color: '#666', fontSize: '0.9rem', marginBottom: '5px' }}>Total Amount</div>
                    <div style={{ fontSize: '1.5rem', fontWeight: 'bold' }}>
                      {formatPrice(generationResult.summary?.total_amount || 0)}
                    </div>
                  </div>
                  <div>
                    <div style={{ color: '#666', fontSize: '0.9rem', marginBottom: '5px' }}>Users Affected</div>
                    <div style={{ fontSize: '1.5rem', fontWeight: 'bold' }}>
                      {generationResult.summary?.users_affected || 0}
                    </div>
                  </div>
                </div>

                {generationResult.errors && generationResult.errors.length > 0 && (
                  <div style={{ marginTop: '20px' }}>
                    <div style={{ color: '#dc3545', fontWeight: 'bold', marginBottom: '10px' }}>
                      Errors ({generationResult.errors.length}):
                    </div>
                    <ul style={{ color: '#666', paddingLeft: '20px' }}>
                      {generationResult.errors.map((error, index) => (
                        <li key={index}>{error}</li>
                      ))}
                    </ul>
                  </div>
                )}
              </div>
            )}
          </div>
        )}

        {activeTab === 'search' && (
          <>

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
          </>
        )}
      </div>
    </AdminLayout>
  )
}

export default Payments

