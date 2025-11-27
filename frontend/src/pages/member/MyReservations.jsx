import { useState, useEffect } from 'react'
import MemberLayout from '../../components/layout/MemberLayout'
import api from '../../services/api'
import './MemberPages.css'

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
        <div className="member-loading">Loading...</div>
      </MemberLayout>
    )
  }

  return (
    <MemberLayout>
      <div className="reservations-container">
        <h1 className="reservations-title">My Reservations</h1>

        {message && (
          <div className={`court-booking-message ${message.type}`} style={{ marginBottom: '20px' }}>
            {message.text}
          </div>
        )}

        {/* Status Filter */}
        <div className="reservations-filter">
          <label className="reservations-filter-label">Filter:</label>
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="reservations-filter-select"
          >
            <option value="all">All Reservations</option>
            <option value="upcoming">Upcoming</option>
            <option value="past">Past</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>

        {/* Reservations List */}
        <div className="reservations-table-container">
          {reservations.length === 0 ? (
            <div style={{ padding: '40px', textAlign: 'center', color: 'rgba(255, 255, 255, 0.6)' }}>
              No reservations found
            </div>
          ) : (
            <table className="reservations-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Time</th>
                  <th>Court</th>
                  <th>Duration</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {reservations.map((reservation) => (
                  <tr key={reservation.id}>
                    <td>
                      {new Date(reservation.start_time).toLocaleDateString()}
                    </td>
                    <td>
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
                    <td>Court {reservation.court_number}</td>
                    <td>{formatDuration(reservation.duration_minutes)}</td>
                    <td>
                      <span
                        className="reservations-status-badge"
                        style={{
                          backgroundColor: getStatusColor(reservation.status) + '20',
                          color: getStatusColor(reservation.status),
                        }}
                      >
                        {reservation.status}
                      </span>
                    </td>
                    <td>
                      {reservation.status === 'CONFIRMED' &&
                        new Date(reservation.start_time) > new Date() && (
                          <button
                            onClick={() => handleCancel(reservation.id)}
                            className="reservations-button"
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
