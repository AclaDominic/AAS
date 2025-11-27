import { useState, useEffect } from 'react'
import { membersService } from '../../services/membersService'
import AdminLayout from '../../components/layout/AdminLayout'
import api from '../../services/api'
import './AdminPages.css'

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
      <div className="admin-page-container">
        <h1 className="admin-page-title">Reports</h1>

        {/* Tab Navigation */}
        <div className="admin-tabs-container">
          <button
            onClick={() => setActiveTab('payment-history')}
            className={`admin-tab ${activeTab === 'payment-history' ? 'active' : ''}`}
          >
            Payment History
          </button>
          <button
            onClick={() => setActiveTab('customer-balances')}
            className={`admin-tab ${activeTab === 'customer-balances' ? 'active' : ''}`}
          >
            Customer Balances
          </button>
          <button
            onClick={() => setActiveTab('payment-summary')}
            className={`admin-tab ${activeTab === 'payment-summary' ? 'active' : ''}`}
          >
            Payment Summary
          </button>
        </div>

        {/* Filters */}
        <div className="admin-card" style={{ marginBottom: '20px' }}>
          <div className="admin-search-bar" style={{ flexWrap: 'wrap' }}>
            <div className="admin-form-group" style={{ minWidth: '150px' }}>
              <label className="admin-label">Start Date</label>
              <input
                type="date"
                className="admin-input"
                value={filters.start_date}
                onChange={(e) => setFilters({ ...filters, start_date: e.target.value })}
              />
            </div>
            <div className="admin-form-group" style={{ minWidth: '150px' }}>
              <label className="admin-label">End Date</label>
              <input
                type="date"
                className="admin-input"
                value={filters.end_date}
                onChange={(e) => setFilters({ ...filters, end_date: e.target.value })}
              />
            </div>
            {activeTab === 'payment-history' && (
              <div className="admin-form-group" style={{ minWidth: '150px' }}>
                <label className="admin-label">Status</label>
                <select
                  className="admin-select"
                  value={filters.status}
                  onChange={(e) => setFilters({ ...filters, status: e.target.value })}
                >
                  <option value="">All</option>
                  <option value="PAID">Paid</option>
                  <option value="PENDING">Pending</option>
                  <option value="CANCELLED">Cancelled</option>
                </select>
              </div>
            )}
            {activeTab === 'payment-summary' && (
              <div className="admin-form-group" style={{ minWidth: '150px' }}>
                <label className="admin-label">Period</label>
                <select
                  className="admin-select"
                  value={filters.period}
                  onChange={(e) => setFilters({ ...filters, period: e.target.value })}
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
                  className="admin-button admin-button-success"
                >
                  Export CSV
                </button>
              </div>
            )}
          </div>
        </div>

        {/* Content */}
        {loading ? (
          <div className="admin-loading">
            <div className="admin-spinner"></div>
            <p>Loading...</p>
          </div>
        ) : (
          <div className="admin-table-container">
            {activeTab === 'payment-history' && (
              <div>
                <h2 className="admin-card-title" style={{ marginBottom: '20px', color: '#ffffff' }}>Payment History</h2>
                {paymentHistory.length === 0 ? (
                  <div className="admin-empty">
                    <p>No payments found.</p>
                  </div>
                ) : (
                  <table className="admin-table">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Membership</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Payment Method</th>
                        <th>Date</th>
                      </tr>
                    </thead>
                    <tbody>
                      {paymentHistory.map((payment) => (
                        <tr key={payment.id}>
                          <td>{payment.id}</td>
                          <td>{payment.user?.name}</td>
                          <td>{payment.membership_offer?.name}</td>
                          <td>{formatPrice(payment.amount)}</td>
                          <td>
                            <span className={`admin-badge ${
                              payment.status === 'PAID' ? 'admin-badge-success' :
                              payment.status === 'PENDING' ? 'admin-badge-warning' :
                              'admin-badge-danger'
                            }`}>
                              {payment.status}
                            </span>
                          </td>
                          <td>{payment.payment_method?.replace('_', ' ')}</td>
                          <td>{formatDate(payment.payment_date || payment.created_at)}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                )}
              </div>
            )}

            {activeTab === 'customer-balances' && (
              <div>
                <h2 className="admin-card-title" style={{ marginBottom: '20px', color: '#ffffff' }}>Customer Balances</h2>
                {customerBalances.length === 0 ? (
                  <div className="admin-empty">
                    <p>No customer data found.</p>
                  </div>
                ) : (
                  <table className="admin-table">
                    <thead>
                      <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th style={{ textAlign: 'right' }}>Total Paid</th>
                        <th style={{ textAlign: 'right' }}>Total Owed</th>
                        <th style={{ textAlign: 'right' }}>Balance</th>
                      </tr>
                    </thead>
                    <tbody>
                      {customerBalances.map((customer) => (
                        <tr key={customer.id}>
                          <td>{customer.name}</td>
                          <td>{customer.email}</td>
                          <td style={{ textAlign: 'right' }}>{formatPrice(customer.total_paid)}</td>
                          <td style={{ textAlign: 'right' }}>{formatPrice(customer.total_owed)}</td>
                          <td style={{ textAlign: 'right' }}>
                            <span className={`admin-badge ${customer.balance >= 0 ? 'admin-badge-success' : 'admin-badge-danger'}`}>
                              {formatPrice(customer.balance)}
                            </span>
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
                <h2 className="admin-card-title" style={{ marginBottom: '20px', color: '#ffffff' }}>Payment Summary ({filters.period})</h2>
                {paymentSummary.length === 0 ? (
                  <div className="admin-empty">
                    <p>No payment data found for the selected period.</p>
                  </div>
                ) : (
                  <table className="admin-table">
                    <thead>
                      <tr>
                        <th>Period</th>
                        <th style={{ textAlign: 'right' }}>Count</th>
                        <th style={{ textAlign: 'right' }}>Total Amount</th>
                      </tr>
                    </thead>
                    <tbody>
                      {paymentSummary.map((item, index) => (
                        <tr key={index}>
                          <td>
                            {filters.period === 'daily' && item.date}
                            {filters.period === 'weekly' && `Week ${item.week}, ${item.year}`}
                            {filters.period === 'monthly' && `${new Date(item.year, item.month - 1).toLocaleString('default', { month: 'long' })} ${item.year}`}
                          </td>
                          <td style={{ textAlign: 'right' }}>{item.count}</td>
                          <td style={{ textAlign: 'right', fontWeight: 'bold' }}>{formatPrice(item.total)}</td>
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

