import { useState, useEffect } from 'react'
import { membersService } from '../../services/membersService'
import AdminLayout from '../../components/layout/AdminLayout'
import api from '../../services/api'

function Reports() {
  const [activeTab, setActiveTab] = useState('payment-history')
  const [paymentHistory, setPaymentHistory] = useState([])
  const [customerBalances, setCustomerBalances] = useState([])
  const [paymentSummary, setPaymentSummary] = useState([])
  const [loading, setLoading] = useState(false)
  const [filters, setFilters] = useState({
    start_date: new Date(new Date().setMonth(new Date().getMonth() - 1)).toISOString().split('T')[0],
    end_date: new Date().toISOString().split('T')[0],
    status: '',
    period: 'monthly',
  })

  useEffect(() => {
    loadData()
  }, [activeTab, filters])

  const loadData = async () => {
    try {
      setLoading(true)
      if (activeTab === 'payment-history') {
        const params = new URLSearchParams({
          start_date: filters.start_date,
          end_date: filters.end_date,
          ...(filters.status && { status: filters.status }),
        })
        const response = await api.get(`/api/admin/reports/payment-history?${params}`)
        setPaymentHistory(response.data.payments?.data || [])
      } else if (activeTab === 'customer-balances') {
        const response = await api.get('/api/admin/reports/customer-balances')
        setCustomerBalances(response.data || [])
      } else if (activeTab === 'payment-summary') {
        const params = new URLSearchParams({
          period: filters.period,
          start_date: filters.start_date,
          end_date: filters.end_date,
        })
        const response = await api.get(`/api/admin/reports/payments-summary?${params}`)
        setPaymentSummary(response.data.summary || [])
      }
    } catch (error) {
      console.error('Error loading reports:', error)
    } finally {
      setLoading(false)
    }
  }

  const handleExport = async () => {
    try {
      const response = await api.get('/api/admin/reports/export?type=payment_history', {
        responseType: 'blob',
      })
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `payment_history_${new Date().toISOString().split('T')[0]}.csv`)
      document.body.appendChild(link)
      link.click()
      link.remove()
    } catch (error) {
      console.error('Error exporting:', error)
      alert('Failed to export report.')
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
    if (!dateString) return 'N/A'
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    })
  }

  return (
    <AdminLayout>
      <div style={{ padding: '40px' }}>
        <h1 style={{ marginBottom: '30px', fontSize: '2.5rem', color: '#646cff' }}>Reports</h1>

        {/* Tab Navigation */}
        <div style={{ display: 'flex', gap: '10px', marginBottom: '30px', borderBottom: '2px solid #ddd' }}>
          <button
            onClick={() => setActiveTab('payment-history')}
            style={{
              padding: '12px 24px',
              backgroundColor: activeTab === 'payment-history' ? '#646cff' : 'transparent',
              color: activeTab === 'payment-history' ? '#ffffff' : '#333',
              border: 'none',
              borderBottom: activeTab === 'payment-history' ? '3px solid #646cff' : '3px solid transparent',
              borderRadius: '6px 6px 0 0',
              cursor: 'pointer',
              fontSize: '1rem',
              fontWeight: activeTab === 'payment-history' ? 'bold' : 'normal',
            }}
          >
            Payment History
          </button>
          <button
            onClick={() => setActiveTab('customer-balances')}
            style={{
              padding: '12px 24px',
              backgroundColor: activeTab === 'customer-balances' ? '#646cff' : 'transparent',
              color: activeTab === 'customer-balances' ? '#ffffff' : '#333',
              border: 'none',
              borderBottom: activeTab === 'customer-balances' ? '3px solid #646cff' : '3px solid transparent',
              borderRadius: '6px 6px 0 0',
              cursor: 'pointer',
              fontSize: '1rem',
              fontWeight: activeTab === 'customer-balances' ? 'bold' : 'normal',
            }}
          >
            Customer Balances
          </button>
          <button
            onClick={() => setActiveTab('payment-summary')}
            style={{
              padding: '12px 24px',
              backgroundColor: activeTab === 'payment-summary' ? '#646cff' : 'transparent',
              color: activeTab === 'payment-summary' ? '#ffffff' : '#333',
              border: 'none',
              borderBottom: activeTab === 'payment-summary' ? '3px solid #646cff' : '3px solid transparent',
              borderRadius: '6px 6px 0 0',
              cursor: 'pointer',
              fontSize: '1rem',
              fontWeight: activeTab === 'payment-summary' ? 'bold' : 'normal',
            }}
          >
            Payment Summary
          </button>
        </div>

        {/* Filters */}
        <div style={{ backgroundColor: 'white', padding: '20px', borderRadius: '8px', marginBottom: '20px' }}>
          <div style={{ display: 'flex', gap: '15px', flexWrap: 'wrap' }}>
            <div>
              <label style={{ display: 'block', marginBottom: '5px', fontSize: '0.9rem' }}>Start Date</label>
              <input
                type="date"
                value={filters.start_date}
                onChange={(e) => setFilters({ ...filters, start_date: e.target.value })}
                style={{ padding: '8px', border: '1px solid #ddd', borderRadius: '6px' }}
              />
            </div>
            <div>
              <label style={{ display: 'block', marginBottom: '5px', fontSize: '0.9rem' }}>End Date</label>
              <input
                type="date"
                value={filters.end_date}
                onChange={(e) => setFilters({ ...filters, end_date: e.target.value })}
                style={{ padding: '8px', border: '1px solid #ddd', borderRadius: '6px' }}
              />
            </div>
            {activeTab === 'payment-history' && (
              <div>
                <label style={{ display: 'block', marginBottom: '5px', fontSize: '0.9rem' }}>Status</label>
                <select
                  value={filters.status}
                  onChange={(e) => setFilters({ ...filters, status: e.target.value })}
                  style={{ padding: '8px', border: '1px solid #ddd', borderRadius: '6px' }}
                >
                  <option value="">All</option>
                  <option value="PAID">Paid</option>
                  <option value="PENDING">Pending</option>
                  <option value="CANCELLED">Cancelled</option>
                </select>
              </div>
            )}
            {activeTab === 'payment-summary' && (
              <div>
                <label style={{ display: 'block', marginBottom: '5px', fontSize: '0.9rem' }}>Period</label>
                <select
                  value={filters.period}
                  onChange={(e) => setFilters({ ...filters, period: e.target.value })}
                  style={{ padding: '8px', border: '1px solid #ddd', borderRadius: '6px' }}
                >
                  <option value="daily">Daily</option>
                  <option value="weekly">Weekly</option>
                  <option value="monthly">Monthly</option>
                </select>
              </div>
            )}
            {activeTab === 'payment-history' && (
              <div style={{ display: 'flex', alignItems: 'flex-end' }}>
                <button
                  onClick={handleExport}
                  style={{
                    padding: '8px 16px',
                    backgroundColor: '#28a745',
                    color: 'white',
                    border: 'none',
                    borderRadius: '6px',
                    cursor: 'pointer',
                  }}
                >
                  Export CSV
                </button>
              </div>
            )}
          </div>
        </div>

        {/* Content */}
        {loading ? (
          <div style={{ textAlign: 'center', padding: '40px' }}>Loading...</div>
        ) : (
          <div style={{ backgroundColor: 'white', borderRadius: '8px', padding: '20px' }}>
            {activeTab === 'payment-history' && (
              <div>
                <h2 style={{ marginBottom: '20px' }}>Payment History</h2>
                {paymentHistory.length === 0 ? (
                  <p>No payments found.</p>
                ) : (
                  <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                    <thead>
                      <tr style={{ backgroundColor: '#f8f9fa' }}>
                        <th style={{ padding: '12px', textAlign: 'left' }}>ID</th>
                        <th style={{ padding: '12px', textAlign: 'left' }}>User</th>
                        <th style={{ padding: '12px', textAlign: 'left' }}>Membership</th>
                        <th style={{ padding: '12px', textAlign: 'left' }}>Amount</th>
                        <th style={{ padding: '12px', textAlign: 'left' }}>Status</th>
                        <th style={{ padding: '12px', textAlign: 'left' }}>Payment Method</th>
                        <th style={{ padding: '12px', textAlign: 'left' }}>Date</th>
                      </tr>
                    </thead>
                    <tbody>
                      {paymentHistory.map((payment) => (
                        <tr key={payment.id} style={{ borderBottom: '1px solid #ddd' }}>
                          <td style={{ padding: '12px' }}>{payment.id}</td>
                          <td style={{ padding: '12px' }}>{payment.user?.name}</td>
                          <td style={{ padding: '12px' }}>{payment.membership_offer?.name}</td>
                          <td style={{ padding: '12px' }}>{formatPrice(payment.amount)}</td>
                          <td style={{ padding: '12px' }}>{payment.status}</td>
                          <td style={{ padding: '12px' }}>{payment.payment_method?.replace('_', ' ')}</td>
                          <td style={{ padding: '12px' }}>{formatDate(payment.payment_date || payment.created_at)}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                )}
              </div>
            )}

            {activeTab === 'customer-balances' && (
              <div>
                <h2 style={{ marginBottom: '20px' }}>Customer Balances</h2>
                {customerBalances.length === 0 ? (
                  <p>No customer data found.</p>
                ) : (
                  <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                    <thead>
                      <tr style={{ backgroundColor: '#f8f9fa' }}>
                        <th style={{ padding: '12px', textAlign: 'left' }}>Name</th>
                        <th style={{ padding: '12px', textAlign: 'left' }}>Email</th>
                        <th style={{ padding: '12px', textAlign: 'right' }}>Total Paid</th>
                        <th style={{ padding: '12px', textAlign: 'right' }}>Total Owed</th>
                        <th style={{ padding: '12px', textAlign: 'right' }}>Balance</th>
                      </tr>
                    </thead>
                    <tbody>
                      {customerBalances.map((customer) => (
                        <tr key={customer.id} style={{ borderBottom: '1px solid #ddd' }}>
                          <td style={{ padding: '12px' }}>{customer.name}</td>
                          <td style={{ padding: '12px' }}>{customer.email}</td>
                          <td style={{ padding: '12px', textAlign: 'right' }}>{formatPrice(customer.total_paid)}</td>
                          <td style={{ padding: '12px', textAlign: 'right' }}>{formatPrice(customer.total_owed)}</td>
                          <td style={{ padding: '12px', textAlign: 'right', color: customer.balance >= 0 ? '#28a745' : '#dc3545' }}>
                            {formatPrice(customer.balance)}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                )}
              </div>
            )}

            {activeTab === 'payment-summary' && (
              <div>
                <h2 style={{ marginBottom: '20px' }}>Payment Summary ({filters.period})</h2>
                {paymentSummary.length === 0 ? (
                  <p>No payment data found for the selected period.</p>
                ) : (
                  <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                    <thead>
                      <tr style={{ backgroundColor: '#f8f9fa' }}>
                        <th style={{ padding: '12px', textAlign: 'left' }}>Period</th>
                        <th style={{ padding: '12px', textAlign: 'right' }}>Count</th>
                        <th style={{ padding: '12px', textAlign: 'right' }}>Total Amount</th>
                      </tr>
                    </thead>
                    <tbody>
                      {paymentSummary.map((item, index) => (
                        <tr key={index} style={{ borderBottom: '1px solid #ddd' }}>
                          <td style={{ padding: '12px' }}>
                            {filters.period === 'daily' && item.date}
                            {filters.period === 'weekly' && `Week ${item.week}, ${item.year}`}
                            {filters.period === 'monthly' && `${new Date(item.year, item.month - 1).toLocaleString('default', { month: 'long' })} ${item.year}`}
                          </td>
                          <td style={{ padding: '12px', textAlign: 'right' }}>{item.count}</td>
                          <td style={{ padding: '12px', textAlign: 'right' }}>{formatPrice(item.total)}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                )}
              </div>
            )}
          </div>
        )}
      </div>
    </AdminLayout>
  )
}

export default Reports

