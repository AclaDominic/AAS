import { useState, useEffect } from 'react'
import MemberLayout from '../../components/layout/MemberLayout'
import api from '../../services/api'

function CourtBooking() {
  const [selectedDate, setSelectedDate] = useState('')
  const [slots, setSlots] = useState([])
  const [selectedSlot, setSelectedSlot] = useState(null)
  const [durationOptions, setDurationOptions] = useState([])
  const [selectedDuration, setSelectedDuration] = useState(null)
  const [settings, setSettings] = useState(null)
  const [loading, setLoading] = useState(false)
  const [booking, setBooking] = useState(false)
  const [message, setMessage] = useState(null)

  useEffect(() => {
    fetchSettings()
    // Set default date to tomorrow
    const tomorrow = new Date()
    tomorrow.setDate(tomorrow.getDate() + 1)
    setSelectedDate(tomorrow.toISOString().split('T')[0])
  }, [])

  useEffect(() => {
    if (selectedDate) {
      fetchAvailableSlots()
    }
  }, [selectedDate])

  useEffect(() => {
    if (selectedSlot) {
      fetchDurationOptions()
    } else {
      setDurationOptions([])
      setSelectedDuration(null)
    }
  }, [selectedSlot])

  const fetchSettings = async () => {
    try {
      const response = await api.get('/api/facility/settings')
      setSettings(response.data)
    } catch (error) {
      console.error('Error fetching settings:', error)
      // Fallback to defaults
      setSettings({
        advance_booking_days: 30,
        minimum_reservation_duration_minutes: 30,
        number_of_courts: 2,
      })
    }
  }

  const fetchAvailableSlots = async () => {
    try {
      setLoading(true)
      const response = await api.get(`/api/courts/available-slots?date=${selectedDate}`)
      setSlots(response.data.slots || [])
      setSelectedSlot(null)
      setMessage(null)
    } catch (error) {
      console.error('Error fetching slots:', error)
      setMessage({ type: 'error', text: 'Failed to load available slots' })
      setSlots([])
    } finally {
      setLoading(false)
    }
  }

  const fetchDurationOptions = async () => {
    if (!selectedSlot) return

    try {
      const dateTime = selectedDate + ' ' + selectedSlot + ':00'
      const response = await api.get(`/api/courts/reservation-options?date=${selectedDate}&start_time=${dateTime}`)
      setDurationOptions(response.data.options || [])
      if (response.data.options && response.data.options.length > 0) {
        setSelectedDuration(response.data.options[0].duration_minutes)
      }
    } catch (error) {
      console.error('Error fetching duration options:', error)
      setDurationOptions([])
    }
  }

  const getMaxDate = () => {
    if (!settings) return ''
    const maxDate = new Date()
    maxDate.setDate(maxDate.getDate() + settings.advance_booking_days)
    return maxDate.toISOString().split('T')[0]
  }

  const handleBooking = async () => {
    if (!selectedSlot || !selectedDuration) {
      setMessage({ type: 'error', text: 'Please select a time slot and duration' })
      return
    }

    try {
      setBooking(true)
      setMessage(null)

      const startTime = selectedDate + ' ' + selectedSlot + ':00'
      
      await api.post('/api/reservations', {
        reservation_date: selectedDate,
        start_time: startTime,
        duration_minutes: selectedDuration,
      })

      setMessage({ type: 'success', text: 'Reservation created successfully!' })
      setSelectedSlot(null)
      setSelectedDuration(null)
      setDurationOptions([])
      fetchAvailableSlots()
    } catch (error) {
      console.error('Error creating reservation:', error)
      setMessage({
        type: 'error',
        text: error.response?.data?.error || error.response?.data?.message || 'Failed to create reservation',
      })
    } finally {
      setBooking(false)
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

  return (
    <MemberLayout>
      <div style={{ padding: '40px' }}>
        <h1 style={{ marginBottom: '30px', fontSize: '2.5rem' }}>Book Badminton Court</h1>

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

        {/* Date Selector */}
        <div style={{ marginBottom: '30px' }}>
          <label
            style={{
              display: 'block',
              marginBottom: '8px',
              fontWeight: 'bold',
              fontSize: '1.1rem',
            }}
          >
            Select Date
          </label>
          <input
            type="date"
            value={selectedDate}
            onChange={(e) => setSelectedDate(e.target.value)}
            min={new Date().toISOString().split('T')[0]}
            max={getMaxDate()}
            style={{
              padding: '10px 12px',
              border: '1px solid #ddd',
              borderRadius: '4px',
              fontSize: '1rem',
            }}
          />
        </div>

        {/* Time Slots Grid */}
        {loading ? (
          <div style={{ padding: '40px', textAlign: 'center' }}>Loading available slots...</div>
        ) : slots.length === 0 ? (
          <div style={{ padding: '40px', textAlign: 'center', color: '#666' }}>
            No available slots for this date. The facility may be closed.
          </div>
        ) : (
          <>
            <div style={{ marginBottom: '30px' }}>
              <h2 style={{ marginBottom: '20px', fontSize: '1.5rem' }}>Available Time Slots</h2>
              <div
                style={{
                  display: 'grid',
                  gridTemplateColumns: 'repeat(auto-fill, minmax(150px, 1fr))',
                  gap: '12px',
                }}
              >
                {slots.map((slot) => (
                  <button
                    key={slot.time_string}
                    onClick={() => setSelectedSlot(slot.is_available ? slot.time_string : null)}
                    disabled={!slot.is_available}
                    style={{
                      padding: '16px',
                      backgroundColor: selectedSlot === slot.time_string ? '#646cff' : slot.is_available ? '#f8f9fa' : '#e9ecef',
                      color: selectedSlot === slot.time_string ? 'white' : slot.is_available ? '#333' : '#999',
                      border: `2px solid ${selectedSlot === slot.time_string ? '#646cff' : slot.is_available ? '#ddd' : '#ccc'}`,
                      borderRadius: '8px',
                      cursor: slot.is_available ? 'pointer' : 'not-allowed',
                      fontSize: '1rem',
                      fontWeight: selectedSlot === slot.time_string ? 'bold' : 'normal',
                      transition: 'all 0.2s',
                    }}
                  >
                    <div>{slot.time_string}</div>
                    {slot.is_available && (
                      <div style={{ fontSize: '0.875rem', marginTop: '4px', opacity: 0.8 }}>
                        {slot.available_courts} court{slot.available_courts !== 1 ? 's' : ''}
                      </div>
                    )}
                  </button>
                ))}
              </div>
            </div>

            {/* Duration Selection */}
            {selectedSlot && durationOptions.length > 0 && (
              <div style={{ marginBottom: '30px' }}>
                <h2 style={{ marginBottom: '20px', fontSize: '1.5rem' }}>Select Duration</h2>
                <select
                  value={selectedDuration || ''}
                  onChange={(e) => setSelectedDuration(parseInt(e.target.value))}
                  style={{
                    padding: '12px 16px',
                    border: '1px solid #ddd',
                    borderRadius: '4px',
                    fontSize: '1rem',
                    minWidth: '200px',
                  }}
                >
                  {durationOptions.map((option) => (
                    <option key={option.duration_minutes} value={option.duration_minutes}>
                      {option.label}
                    </option>
                  ))}
                </select>
              </div>
            )}

            {/* Booking Button */}
            {selectedSlot && selectedDuration && (
              <button
                onClick={handleBooking}
                disabled={booking}
                style={{
                  padding: '14px 28px',
                  backgroundColor: booking ? '#ccc' : '#646cff',
                  color: 'white',
                  border: 'none',
                  borderRadius: '4px',
                  fontSize: '1.1rem',
                  cursor: booking ? 'not-allowed' : 'pointer',
                  fontWeight: 'bold',
                }}
              >
                {booking ? 'Booking...' : `Book Court for ${formatDuration(selectedDuration)}`}
              </button>
            )}
          </>
        )}
      </div>
    </MemberLayout>
  )
}

export default CourtBooking

