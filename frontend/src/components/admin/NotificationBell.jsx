import { useState, useEffect, useRef } from 'react'
import { useNavigate } from 'react-router-dom'
import api from '../../services/api'
import './NotificationBell.css'

function NotificationBell() {
  const [notifications, setNotifications] = useState([])
  const [unreadCount, setUnreadCount] = useState(0)
  const [isOpen, setIsOpen] = useState(false)
  const [loading, setLoading] = useState(false)
  const dropdownRef = useRef(null)
  const navigate = useNavigate()

  // Fetch notifications
  const fetchNotifications = async () => {
    try {
      setLoading(true)
      const response = await api.get('/api/admin/notifications')
      setNotifications(response.data.notifications || [])
      setUnreadCount(response.data.unread_count || 0)
    } catch (error) {
      console.error('Error fetching notifications:', error)
    } finally {
      setLoading(false)
    }
  }

  // Poll for unread count (real-time updates)
  useEffect(() => {
    const fetchUnreadCount = async () => {
      try {
        const response = await api.get('/api/admin/notifications/unread-count')
        setUnreadCount(response.data.unread_count || 0)
      } catch (error) {
        console.error('Error fetching unread count:', error)
      }
    }

    // Fetch immediately
    fetchUnreadCount()
    fetchNotifications()

    // Poll every 10 seconds for real-time updates
    const interval = setInterval(() => {
      fetchUnreadCount()
      if (isOpen) {
        fetchNotifications()
      }
    }, 10000)

    return () => clearInterval(interval)
  }, [isOpen])

  // Close dropdown when clicking outside
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setIsOpen(false)
      }
    }

    const handleEscape = (event) => {
      if (event.key === 'Escape') {
        setIsOpen(false)
      }
    }

    if (isOpen) {
      document.addEventListener('mousedown', handleClickOutside)
      document.addEventListener('keydown', handleEscape)
    }

    return () => {
      document.removeEventListener('mousedown', handleClickOutside)
      document.removeEventListener('keydown', handleEscape)
    }
  }, [isOpen])

  const handleToggle = () => {
    setIsOpen(!isOpen)
    if (!isOpen) {
      fetchNotifications()
    }
  }

  const handleMarkAsRead = async (notificationId) => {
    try {
      await api.post(`/api/admin/notifications/${notificationId}/read`)
      setNotifications(prev =>
        prev.map(notif =>
          notif.id === notificationId ? { ...notif, read_at: new Date().toISOString() } : notif
        )
      )
      setUnreadCount(prev => Math.max(0, prev - 1))
    } catch (error) {
      console.error('Error marking notification as read:', error)
    }
  }

  const handleMarkAllAsRead = async () => {
    try {
      await api.post('/api/admin/notifications/read-all')
      setNotifications(prev =>
        prev.map(notif => ({ ...notif, read_at: notif.read_at || new Date().toISOString() }))
      )
      setUnreadCount(0)
    } catch (error) {
      console.error('Error marking all as read:', error)
    }
  }

  const handleNotificationClick = (notification) => {
    if (!notification.read_at) {
      handleMarkAsRead(notification.id)
    }
    
    // Navigate to reservations page
    navigate('/admin/reservations')
    setIsOpen(false)
  }

  const formatNotification = (notification) => {
    const data = notification.data || {}
    const categoryName = data.category === 'GYM' ? 'Gym' : 'Badminton Court'
    const referenceNumber = data.reference_number || `RES-${notification.id}`
    const userName = data.user_name || 'Unknown User'
    
    return {
      title: `New ${categoryName} Reservation`,
      message: `${userName} created a reservation (${referenceNumber})`,
      reference: referenceNumber,
      category: categoryName,
      time: notification.created_at_human || 'Just now',
    }
  }

  return (
    <div className="notification-bell-container" ref={dropdownRef}>
      <button className="notification-bell-button" onClick={handleToggle}>
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path
            d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
          />
          <path
            d="M13.73 21a2 2 0 0 1-3.46 0"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
          />
        </svg>
        {unreadCount > 0 && (
          <span className="notification-badge">{unreadCount > 99 ? '99+' : unreadCount}</span>
        )}
      </button>

      {isOpen && (
        <div className="notification-dropdown">
          <div className="notification-dropdown-header">
            <h3>Notifications</h3>
            {unreadCount > 0 && (
              <button className="mark-all-read-btn" onClick={handleMarkAllAsRead}>
                Mark all as read
              </button>
            )}
          </div>

          <div className="notification-list">
            {loading ? (
              <div className="notification-loading">Loading...</div>
            ) : notifications.length === 0 ? (
              <div className="notification-empty">No notifications</div>
            ) : (
              notifications.map((notification) => {
                const formatted = formatNotification(notification)
                const isUnread = !notification.read_at

                return (
                  <div
                    key={notification.id}
                    className={`notification-item ${isUnread ? 'unread' : ''}`}
                    onClick={() => handleNotificationClick(notification)}
                  >
                    <div className="notification-icon">
                      {formatted.category === 'Gym' ? '💪' : '🏸'}
                    </div>
                    <div className="notification-content">
                      <div className="notification-title">{formatted.title}</div>
                      <div className="notification-message">{formatted.message}</div>
                      <div className="notification-meta">
                        <span className="notification-reference">Ref: {formatted.reference}</span>
                        <span className="notification-time">{formatted.time}</span>
                      </div>
                    </div>
                    {isUnread && <div className="notification-dot"></div>}
                  </div>
                )
              })
            )}
          </div>

          {notifications.length > 0 && (
            <div className="notification-footer">
              <button onClick={() => navigate('/admin/reservations')}>
                View All Reservations
              </button>
            </div>
          )}
        </div>
      )}
    </div>
  )
}

export default NotificationBell

