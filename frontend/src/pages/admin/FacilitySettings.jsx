import { useState, useEffect } from 'react'
import AdminLayout from '../../components/layout/AdminLayout'
import api from '../../services/api'

function FacilitySettings() {
  const [settings, setSettings] = useState({
    number_of_courts: 2,
    minimum_reservation_duration_minutes: 30,
    advance_booking_days: 30,
  })
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [message, setMessage] = useState(null)

  useEffect(() => {
    fetchSettings()
  }, [])

  const fetchSettings = async () => {
    try {
      setLoading(true)
      const response = await api.get('/api/admin/facility/settings')
      setSettings(response.data)
    } catch (error) {
      console.error('Error fetching settings:', error)
      setMessage({ type: 'error', text: 'Failed to load facility settings' })
    } finally {
      setLoading(false)
    }
  }

  const handleChange = (field, value) => {
    setSettings(prev => ({
      ...prev,
      [field]: value,
    }))
  }

  const handleSave = async () => {
    try {
      setSaving(true)
      setMessage(null)

      await api.put('/api/admin/facility/settings', settings)

      setMessage({ type: 'success', text: 'Facility settings updated successfully!' })
      await fetchSettings()
    } catch (error) {
      console.error('Error saving settings:', error)
      setMessage({
        type: 'error',
        text: error.response?.data?.message || 'Failed to update facility settings',
      })
    } finally {
      setSaving(false)
    }
  }

  const durationOptions = [
    { value: 30, label: '30 minutes' },
    { value: 60, label: '1 hour' },
    { value: 90, label: '1 hour 30 minutes' },
    { value: 120, label: '2 hours' },
    { value: 150, label: '2 hours 30 minutes' },
    { value: 180, label: '3 hours' },
  ]

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
        <h1 style={{ marginBottom: '30px', fontSize: '2.5rem' }}>Facility Settings</h1>

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

        <div
          style={{
            backgroundColor: 'white',
            padding: '30px',
            borderRadius: '8px',
            boxShadow: '0 2px 4px rgba(0,0,0,0.1)',
            maxWidth: '600px',
          }}
        >
          <div style={{ marginBottom: '24px' }}>
            <label
              style={{
                display: 'block',
                marginBottom: '8px',
                fontWeight: 'bold',
                fontSize: '1rem',
              }}
            >
              Number of Badminton Courts
            </label>
            <input
              type="number"
              min="1"
              value={settings.number_of_courts}
              onChange={(e) => handleChange('number_of_courts', parseInt(e.target.value) || 1)}
              style={{
                width: '100%',
                padding: '10px 12px',
                border: '1px solid #ddd',
                borderRadius: '4px',
                fontSize: '1rem',
              }}
            />
          </div>

          <div style={{ marginBottom: '24px' }}>
            <label
              style={{
                display: 'block',
                marginBottom: '8px',
                fontWeight: 'bold',
                fontSize: '1rem',
              }}
            >
              Minimum Reservation Duration
            </label>
            <select
              value={settings.minimum_reservation_duration_minutes}
              onChange={(e) => handleChange('minimum_reservation_duration_minutes', parseInt(e.target.value))}
              style={{
                width: '100%',
                padding: '10px 12px',
                border: '1px solid #ddd',
                borderRadius: '4px',
                fontSize: '1rem',
              }}
            >
              {durationOptions.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </select>
          </div>

          <div style={{ marginBottom: '24px' }}>
            <label
              style={{
                display: 'block',
                marginBottom: '8px',
                fontWeight: 'bold',
                fontSize: '1rem',
              }}
            >
              Advance Booking Days
            </label>
            <input
              type="number"
              min="1"
              max="365"
              value={settings.advance_booking_days}
              onChange={(e) => handleChange('advance_booking_days', parseInt(e.target.value) || 1)}
              style={{
                width: '100%',
                padding: '10px 12px',
                border: '1px solid #ddd',
                borderRadius: '4px',
                fontSize: '1rem',
              }}
            />
            <p style={{ marginTop: '8px', color: '#666', fontSize: '0.875rem' }}>
              Maximum number of days in advance members can book courts
            </p>
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
            {saving ? 'Saving...' : 'Save Settings'}
          </button>
        </div>
      </div>
    </AdminLayout>
  )
}

export default FacilitySettings

