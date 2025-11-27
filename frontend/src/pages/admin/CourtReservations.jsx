import { useState, useEffect } from 'react'
import AdminLayout from '../../components/layout/AdminLayout'
import api from '../../services/api'

function CourtReservations() {
  const [reservations, setReservations] = useState([])
  const [loading, setLoading] = useState(true)
  const [filters, setFilters] = useState({
    date: '',
    court_number: '',
    status: '',
    member_search: '',
  })
  const [message, setMessage] = useState(null)

  useEffect(() => {
    fetchReservations()
  }, [])

  const fetchReservations = async () => {
    try {
      setLoading(true)
      const params = new URLSearchParams()
      if (filters.date) params.append('date', filters.date)
      if (filters.court_number) params.append('court_number', filters.court_number)
      if (filters.status) params.append('status', filters.status)
      if (filters.member_search) params.append('member_search', filters.member_search)

      const response = await api.get(`/api/admin/reservations?${params.toString()}`)
      setReservations(response.data.data || [])
    } catch (error) {
      console.error('Error fetching reservations:', error)
      setMessage({ type: 'error', text: 'Failed to load reservations' })
    } finally {
      setLoading(false)
    }
  }

  const handleFilterChange = (field, value) => {
    setFilters(prev => ({ ...prev, [field]: value }))
  }

  const handleApplyFilters = () => {
    fetchReservations()
  }

  const handleCancel = async (id) => {
    if (!confirm('Are you sure you want to cancel this reservation?')) {
      return
    }

    try {
      await api.post(`/api/admin/reservations/${id}/cancel`, {
        reason: 'Cancelled by admin',
      })
      setMessage({ type: 'success', text: 'Reservation cancelled successfully!' })
      fetchReservations()
    } catch (error) {
      console.error('Error cancelling reservation:', error)
      setMessage({
        type: 'error',
        text: error.response?.data?.message || 'Failed to cancel reservation',
      })
    }
  }

  const getStatusColor = (status) => {
    switch (status) {
      case 'CONFIRMED':
        return '#28a745'
      case 'CANCELLED':
        return '#dc3545'
      case 'COMPLETED':
        return '#6c757d'
      case 'PENDING':
        return '#ffc107'
      default:
        return '#6c757d'
    }
  }

  if (loading) {
    return (
      <AdminLayout>
        <div style={{ padding: '40px' }}>
          <div>Loading...</div>
        </div>
      </AdminLayout>
    )
  }

  return (
    <AdminLayout>
      <div style={{ padding: '40px' }}>
        <h1 style={{ marginBottom: '30px', fontSize: '2.5rem' }}>Court Reservations</h1>

        {message && (
          <div
            style={{
              padding: '12px 16px',
              marginBottom: '20px',
              borderRadius: '4px',
              backgroundColor: message.type === 'success' ? '#d4edda' : '#f8d7da',
              color: message.type === 'success' ? '#155724' : '#721c24',
              border: `1px solid ${message.type === 'success' ? '#c3e6cb' : '#f5c6cb'}`,
            }}
          >
            {message.text}
          </div>
        )}

        {/* Filters */}
        <div
          style={{
            backgroundColor: 'white',
            padding: '20px',
            borderRadius: '8px',
            marginBottom: '20px',
            boxShadow: '0 2px 4px rgba(0,0,0,0.1)',
          }}
        >
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '16px', marginBottom: '16px' }}>
            <div>
              <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold' }}>Date</label>
              <input
                type="date"
                value={filters.date}
                onChange={(e) => handleFilterChange('date', e.target.value)}
                style={{
                  width: '100%',
                  padding: '8px 12px',
                  border: '1px solid #ddd',
                  borderRadius: '4px',
                }}
              />
            </div>
            <div>
              <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold' }}>Court Number</label>
              <input
                type="number"
                min="1"
                value={filters.court_number}
                onChange={(e) => handleFilterChange('court_number', e.target.value)}
                placeholder="All"
                style={{
                  width: '100%',
                  padding: '8px 12px',
                  border: '1px solid #ddd',
                  borderRadius: '4px',
                }}
              />
            </div>
            <div>
              <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold' }}>Status</label>
              <select
                value={filters.status}
                onChange={(e) => handleFilterChange('status', e.target.value)}
                style={{
                  width: '100%',
                  padding: '8px 12px',
                  border: '1px solid #ddd',
                  borderRadius: '4px',
                }}
              >
                <option value="">All</option>
                <option value="CONFIRMED">Confirmed</option>
                <option value="CANCELLED">Cancelled</option>
                <option value="COMPLETED">Completed</option>
                <option value="PENDING">Pending</option>
              </select>
            </div>
            <div>
              <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold' }}>Member Search</label>
              <input
                type="text"
                value={filters.member_search}
                onChange={(e) => handleFilterChange('member_search', e.target.value)}
                placeholder="Name or email"
                style={{
                  width: '100%',
                  padding: '8px 12px',
                  border: '1px solid #ddd',
                  borderRadius: '4px',
                }}
              />
            </div>
          </div>
          <button
            onClick={handleApplyFilters}
            style={{
              padding: '10px 20px',
              backgroundColor: '#646cff',
              color: 'white',
              border: 'none',
              borderRadius: '4px',
              cursor: 'pointer',
              fontWeight: 'bold',
            }}
          >
            Apply Filters
          </button>
        </div>

        {/* Reservations Table */}
        <div
          style={{
            backgroundColor: 'white',
            borderRadius: '8px',
            overflow: 'hidden',
            boxShadow: '0 2px 4px rgba(0,0,0,0.1)',
          }}
        >
          <table style={{ width: '100%', borderCollapse: 'collapse' }}>
            <thead>
              <tr style={{ backgroundColor: '#f8f9fa' }}>
                <th style={{ padding: '16px', textAlign: 'left', borderBottom: '2px solid #dee2e6' }}>Date</th>
                <th style={{ padding: '16px', textAlign: 'left', borderBottom: '2px solid #dee2e6' }}>Time</th>
                <th style={{ padding: '16px', textAlign: 'left', borderBottom: '2px solid #dee2e6' }}>Court</th>
                <th style={{ padding: '16px', textAlign: 'left', borderBottom: '2px solid #dee2e6' }}>Member</th>
                <th style={{ padding: '16px', textAlign: 'left', borderBottom: '2px solid #dee2e6' }}>Duration</th>
                <th style={{ padding: '16px', textAlign: 'left', borderBottom: '2px solid #dee2e6' }}>Status</th>
                <th style={{ padding: '16px', textAlign: 'left', borderBottom: '2px solid #dee2e6' }}>Actions</th>
              </tr>
            </thead>
            <tbody>
              {reservations.length === 0 ? (
                <tr>
                  <td colSpan="7" style={{ padding: '40px', textAlign: 'center', color: '#666' }}>
                    No reservations found
                  </td>
                </tr>
              ) : (
                reservations.map((reservation) => (
                  <tr key={reservation.id} style={{ borderBottom: '1px solid #dee2e6' }}>
                    <td style={{ padding: '16px' }}>
                      {new Date(reservation.start_time).toLocaleDateString()}
                    </td>
                    <td style={{ padding: '16px' }}>
                      {new Date(reservation.start_time).toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit',
                      })}{' '}
                      -{' '}
                      {new Date(reservation.end_time).toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit',
                      })}
                    </td>
                    <td style={{ padding: '16px' }}>Court {reservation.court_number}</td>
                    <td style={{ padding: '16px' }}>
                      {reservation.user?.name || 'N/A'}
                      <br />
                      <span style={{ fontSize: '0.875rem', color: '#666' }}>
                        {reservation.user?.email || ''}
                      </span>
                    </td>
                    <td style={{ padding: '16px' }}>{reservation.duration_minutes} min</td>
                    <td style={{ padding: '16px' }}>
                      <span
                        style={{
                          padding: '4px 12px',
                          borderRadius: '12px',
                          backgroundColor: getStatusColor(reservation.status) + '20',
                          color: getStatusColor(reservation.status),
                          fontSize: '0.875rem',
                          fontWeight: 'bold',
                        }}
                      >
                        {reservation.status}
                      </span>
                    </td>
                    <td style={{ padding: '16px' }}>
                      {reservation.status === 'CONFIRMED' && (
                        <button
                          onClick={() => handleCancel(reservation.id)}
                          style={{
                            padding: '6px 12px',
                            backgroundColor: '#dc3545',
                            color: 'white',
                            border: 'none',
                            borderRadius: '4px',
                            cursor: 'pointer',
                            fontSize: '0.875rem',
                          }}
                        >
                          Cancel
                        </button>
                      )}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </AdminLayout>
  )
}

export default CourtReservations

