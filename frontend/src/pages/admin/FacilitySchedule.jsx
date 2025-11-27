import { useState, useEffect } from 'react'
import AdminLayout from '../../components/layout/AdminLayout'
import api from '../../services/api'

const DAY_NAMES = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']

function FacilitySchedule() {
  const [schedules, setSchedules] = useState([])
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [message, setMessage] = useState(null)

  useEffect(() => {
    fetchSchedules()
  }, [])

  const fetchSchedules = async () => {
    try {
      setLoading(true)
      const response = await api.get('/api/admin/facility/schedule')
      setSchedules(response.data)
    } catch (error) {
      console.error('Error fetching schedules:', error)
      setMessage({ type: 'error', text: 'Failed to load facility schedule' })
    } finally {
      setLoading(false)
    }
  }

  const handleToggle = (dayOfWeek) => {
    setSchedules(prev =>
      prev.map(schedule =>
        schedule.day_of_week === dayOfWeek
          ? { ...schedule, is_open: !schedule.is_open }
          : schedule
      )
    )
  }

  const handleTimeChange = (dayOfWeek, field, value) => {
    setSchedules(prev =>
      prev.map(schedule =>
        schedule.day_of_week === dayOfWeek
          ? { ...schedule, [field]: value }
          : schedule
      )
    )
  }

  const handleSave = async () => {
    try {
      setSaving(true)
      setMessage(null)

      // Format schedules for API
      const schedulesToSave = schedules.map(schedule => ({
        day_of_week: schedule.day_of_week,
        open_time: schedule.is_open ? schedule.open_time : null,
        close_time: schedule.is_open ? schedule.close_time : null,
        is_open: schedule.is_open,
      }))

      await api.put('/api/admin/facility/schedule', {
        schedules: schedulesToSave,
      })

      setMessage({ type: 'success', text: 'Facility schedule updated successfully!' })
      await fetchSchedules()
    } catch (error) {
      console.error('Error saving schedule:', error)
      setMessage({
        type: 'error',
        text: error.response?.data?.message || 'Failed to update facility schedule',
      })
    } finally {
      setSaving(false)
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
        <h1 style={{ marginBottom: '30px', fontSize: '2.5rem' }}>Facility Schedule</h1>

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

        <div style={{ marginBottom: '30px' }}>
          <table
            style={{
              width: '100%',
              borderCollapse: 'collapse',
              backgroundColor: 'white',
              borderRadius: '8px',
              overflow: 'hidden',
              boxShadow: '0 2px 4px rgba(0,0,0,0.1)',
            }}
          >
            <thead>
              <tr style={{ backgroundColor: '#f8f9fa' }}>
                <th style={{ padding: '16px', textAlign: 'left', borderBottom: '2px solid #dee2e6' }}>Day</th>
                <th style={{ padding: '16px', textAlign: 'left', borderBottom: '2px solid #dee2e6' }}>Status</th>
                <th style={{ padding: '16px', textAlign: 'left', borderBottom: '2px solid #dee2e6' }}>Open Time</th>
                <th style={{ padding: '16px', textAlign: 'left', borderBottom: '2px solid #dee2e6' }}>Close Time</th>
              </tr>
            </thead>
            <tbody>
              {schedules.map((schedule) => (
                <tr key={schedule.day_of_week} style={{ borderBottom: '1px solid #dee2e6' }}>
                  <td style={{ padding: '16px', fontWeight: 'bold' }}>
                    {DAY_NAMES[schedule.day_of_week] || `Day ${schedule.day_of_week}`}
                  </td>
                  <td style={{ padding: '16px' }}>
                    <label style={{ display: 'flex', alignItems: 'center', cursor: 'pointer' }}>
                      <input
                        type="checkbox"
                        checked={schedule.is_open || false}
                        onChange={() => handleToggle(schedule.day_of_week)}
                        style={{ marginRight: '8px', width: '18px', height: '18px' }}
                      />
                      <span>{schedule.is_open ? 'Open' : 'Closed'}</span>
                    </label>
                  </td>
                  <td style={{ padding: '16px' }}>
                    <input
                      type="time"
                      value={schedule.open_time || '08:00'}
                      onChange={(e) => handleTimeChange(schedule.day_of_week, 'open_time', e.target.value)}
                      disabled={!schedule.is_open}
                      style={{
                        padding: '8px 12px',
                        border: '1px solid #ddd',
                        borderRadius: '4px',
                        fontSize: '1rem',
                        opacity: schedule.is_open ? 1 : 0.5,
                      }}
                    />
                  </td>
                  <td style={{ padding: '16px' }}>
                    <input
                      type="time"
                      value={schedule.close_time || '22:00'}
                      onChange={(e) => handleTimeChange(schedule.day_of_week, 'close_time', e.target.value)}
                      disabled={!schedule.is_open}
                      style={{
                        padding: '8px 12px',
                        border: '1px solid #ddd',
                        borderRadius: '4px',
                        fontSize: '1rem',
                        opacity: schedule.is_open ? 1 : 0.5,
                      }}
                    />
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        <button
          onClick={handleSave}
          disabled={saving}
          style={{
            padding: '12px 24px',
            backgroundColor: saving ? '#ccc' : '#646cff',
            color: 'white',
            border: 'none',
            borderRadius: '4px',
            fontSize: '1rem',
            cursor: saving ? 'not-allowed' : 'pointer',
            fontWeight: 'bold',
          }}
        >
          {saving ? 'Saving...' : 'Save Schedule'}
        </button>
      </div>
    </AdminLayout>
  )
}

export default FacilitySchedule

