import { useState, useEffect } from 'react'
import MemberLayout from '../../components/layout/MemberLayout'
import api from '../../services/api'
import './MemberPages.css'

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
      <div className="court-booking-container">
        <h1 className="court-booking-title">Book Badminton Court</h1>

        {message && (
          <div className={`court-booking-message ${message.type}`}>
            {message.text}
          </div>
        )}

        {/* Date Selector */}
        <div className="court-booking-field">
          <label className="court-booking-label">Select Date</label>
          <input
            type="date"
            value={selectedDate}
            onChange={(e) => setSelectedDate(e.target.value)}
            min={new Date().toISOString().split('T')[0]}
            max={getMaxDate()}
            className="court-booking-input"
          />
        </div>

        {/* Time Slots Grid */}
        {loading ? (
          <div style={{ padding: '40px', textAlign: 'center', color: '#ffffff' }}>Loading available slots...</div>
        ) : slots.length === 0 ? (
          <div style={{ padding: '40px', textAlign: 'center', color: 'rgba(255, 255, 255, 0.6)' }}>
            No available slots for this date. The facility may be closed.
          </div>
        ) : (
          <>
            <div style={{ marginBottom: '30px' }}>
              <h2 style={{ marginBottom: '20px', fontSize: '1.5rem', color: '#ffffff' }}>Available Time Slots</h2>
              <div className="court-booking-slots-grid">
                {slots.map((slot) => (
                  <button
                    key={slot.time_string}
                    onClick={() => setSelectedSlot(slot.is_available ? slot.time_string : null)}
                    disabled={!slot.is_available}
                    className={`court-booking-slot ${selectedSlot === slot.time_string ? 'selected' : ''}`}
                    style={{
                      color: selectedSlot === slot.time_string ? 'white' : slot.is_available ? '#ffffff' : 'rgba(255, 255, 255, 0.4)',
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
              <div className="court-booking-field">
                <h2 style={{ marginBottom: '20px', fontSize: '1.5rem', color: '#ffffff' }}>Select Duration</h2>
                <select
                  value={selectedDuration || ''}
                  onChange={(e) => setSelectedDuration(parseInt(e.target.value))}
                  className="court-booking-select"
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
                className="court-booking-button"
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
