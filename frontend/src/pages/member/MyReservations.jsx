import { useState, useEffect } from 'react'
import MemberLayout from '../../components/layout/MemberLayout'
import api from '../../services/api'

function MyReservations() {
  const [reservations, setReservations] = useState([])
  const [loading, setLoading] = useState(true)
  const [statusFilter, setStatusFilter] = useState('all')
  const [message, setMessage] = useState(null)

  useEffect(() => {
    fetchReservations()
  }, [statusFilter])

  const fetchReservations = async () => {
    try {
      setLoading(true)
      const params = statusFilter !== 'all' ? `?status=${statusFilter}` : ''
      const response = await api.get(`/api/reservations${params}`)
      setReservations(response.data.data || [])
    } catch (error) {
      console.error('Error fetching reservations:', error)
      setMessage({ type: 'error', text: 'Failed to load reservations' })
    } finally {
      setLoading(false)
    }
  }

  const handleCancel = async (id) => {
    if (!confirm('Are you sure you want to cancel this reservation?')) {
      return
    }

    try {
      await api.post(`/api/reservations/${id}/cancel`, {
        reason: 'Cancelled by member',
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

  const formatDuration = (minutes) => {
    const hours = Math.floor(minutes / 60)
    const mins = minutes % 60
    if (hours > 0) {
      return mins > 0 ? `${hours}hr ${mins}min` : `${hours}hr`
    }
    return `${mins}min`
  }

  if (loading) {
    return (
      <MemberLayout>
        <div style={{ padding: '40px' }}>
          <div>Loading...</div>
        </div>
      </MemberLayout>
    )
  }

  return (
    <MemberLayout>
      <div style={{ padding: '40px' }}>
        <h1 style={{ marginBottom: '30px', fontSize: '2.5rem' }}>My Reservations</h1>

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

        {/* Status Filter */}
        <div style={{ marginBottom: '20px' }}>
          <label style={{ marginRight: '12px', fontWeight: 'bold' }}>Filter:</label>
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            style={{
              padding: '8px 12px',
              border: '1px solid #ddd',
              borderRadius: '4px',
              fontSize: '1rem',
            }}
          >
            <option value="all">All Reservations</option>
            <option value="upcoming">Upcoming</option>
            <option value="past">Past</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>

        {/* Reservations List */}
        <div
          style={{
            backgroundColor: 'white',
            borderRadius: '8px',
            overflow: 'hidden',
            boxShadow: '0 2px 4px rgba(0,0,0,0.1)',
          }}
        >
          {reservations.length === 0 ? (
            <div style={{ padding: '40px', textAlign: 'center', color: '#666' }}>
              No reservations found
            </div>
          ) : (
            <table style={{ width: '100%', borderCollapse: 'collapse' }}>
              <thead>
                <tr style={{ backgroundColor: '#f8f9fa' }}>
                  <th style={{ padding: '16px', textAlign: 'left', borderBottom: '2px solid #dee2e6' }}>Date</th>
                  <th style={{ padding: '16px', textAlign: 'left', borderBottom: '2px solid #dee2e6' }}>Time</th>
                  <th style={{ padding: '16px', textAlign: 'left', borderBottom: '2px solid #dee2e6' }}>Court</th>
                  <th style={{ padding: '16px', textAlign: 'left', borderBottom: '2px solid #dee2e6' }}>Duration</th>
                  <th style={{ padding: '16px', textAlign: 'left', borderBottom: '2px solid #dee2e6' }}>Status</th>
                  <th style={{ padding: '16px', textAlign: 'left', borderBottom: '2px solid #dee2e6' }}>Actions</th>
                </tr>
              </thead>
              <tbody>
                {reservations.map((reservation) => (
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
                    <td style={{ padding: '16px' }}>{formatDuration(reservation.duration_minutes)}</td>
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
                      {reservation.status === 'CONFIRMED' &&
                        new Date(reservation.start_time) > new Date() && (
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
                ))}
              </tbody>
            </table>
          )}
        </div>
      </div>
    </MemberLayout>
  )
}

export default MyReservations

