import { useState, useEffect } from 'react'
import MemberLayout from '../../components/layout/MemberLayout'
import api from '../../services/api'
import './MemberPages.css'

function CourtBooking() {
  const [category, setCategory] = useState('BADMINTON_COURT')
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
    if (selectedDate && category === 'BADMINTON_COURT') {
      fetchAvailableSlots()
    } else if (category === 'GYM') {
      // For gym, we don't need to fetch slots
      setSlots([])
      setSelectedSlot(null)
    }
  }, [selectedDate, category])

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
      // For gym reservations, we might not need slots, but we'll still call the API
      // The backend should handle gym reservations differently
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

      // Format start time - both gym and badminton court use HH:mm format
      const startTime = `${selectedDate} ${selectedSlot}:00`
      
      await api.post('/api/reservations', {
        category: category,
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
        <h1 className="court-booking-title">Book Facility</h1>

        {message && (
          <div className={`court-booking-message ${message.type}`}>
            {message.text}
          </div>
        )}

        {/* Category Selection */}
        <div className="court-booking-field">
          <label className="court-booking-label">Select Category</label>
          <div style={{ display: 'flex', gap: '16px', marginTop: '8px' }}>
            <button
              type="button"
              onClick={() => {
                setCategory('BADMINTON_COURT')
                setSelectedSlot(null)
                setSelectedDuration(null)
                setDurationOptions([])
              }}
              style={{
                flex: 1,
                padding: '16px 24px',
                backgroundColor: category === 'BADMINTON_COURT' ? '#646cff' : 'rgba(255, 255, 255, 0.1)',
                color: category === 'BADMINTON_COURT' ? 'white' : 'rgba(255, 255, 255, 0.8)',
                border: `2px solid ${category === 'BADMINTON_COURT' ? '#646cff' : 'rgba(255, 255, 255, 0.2)'}`,
                borderRadius: '8px',
                cursor: 'pointer',
                fontSize: '1rem',
                fontWeight: category === 'BADMINTON_COURT' ? 'bold' : 'normal',
                transition: 'all 0.3s ease',
              }}
            >
              🏸 Badminton Court
            </button>
            <button
              type="button"
              onClick={() => {
                setCategory('GYM')
                setSelectedSlot(null)
                setSelectedDuration(null)
                setDurationOptions([])
              }}
              style={{
                flex: 1,
                padding: '16px 24px',
                backgroundColor: category === 'GYM' ? '#646cff' : 'rgba(255, 255, 255, 0.1)',
                color: category === 'GYM' ? 'white' : 'rgba(255, 255, 255, 0.8)',
                border: `2px solid ${category === 'GYM' ? '#646cff' : 'rgba(255, 255, 255, 0.2)'}`,
                borderRadius: '8px',
                cursor: 'pointer',
                fontSize: '1rem',
                fontWeight: category === 'GYM' ? 'bold' : 'normal',
                transition: 'all 0.3s ease',
              }}
            >
              💪 Gym
            </button>
          </div>
        </div>

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

        {/* Time Slots Grid - Only show for badminton court */}
        {category === 'BADMINTON_COURT' ? (
          loading ? (
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
        )
        ) : (
          /* Gym Reservation - Direct time selection */
          <>
            <div className="court-booking-field">
              <label className="court-booking-label">Select Start Time</label>
              <input
                type="time"
                value={selectedSlot || ''}
                onChange={(e) => {
                  setSelectedSlot(e.target.value)
                  setSelectedDuration(null)
                  setDurationOptions([])
                }}
                className="court-booking-input"
                style={{ maxWidth: '300px' }}
              />
            </div>

            {selectedSlot && (
              <div className="court-booking-field">
                <label className="court-booking-label">Select Duration</label>
                <select
                  value={selectedDuration || ''}
                  onChange={(e) => setSelectedDuration(parseInt(e.target.value))}
                  className="court-booking-select"
                  style={{ maxWidth: '300px' }}
                >
                  <option value="">Select duration</option>
                  <option value="30">30 minutes</option>
                  <option value="60">1 hour</option>
                  <option value="90">1.5 hours</option>
                  <option value="120">2 hours</option>
                  <option value="180">3 hours</option>
                </select>
              </div>
            )}

            {selectedSlot && selectedDuration && (
              <button
                onClick={handleBooking}
                disabled={booking}
                className="court-booking-button"
              >
                {booking ? 'Booking...' : `Book Gym for ${formatDuration(selectedDuration)}`}
              </button>
            )}
          </>
        )}
      </div>
    </MemberLayout>
  )
}

export default CourtBooking
