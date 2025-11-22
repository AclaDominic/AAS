function MemberDetail({ member, onClose }) {
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

  const formatDateTime = (dateString) => {
    if (!dateString) return 'N/A'
    return new Date(dateString).toLocaleString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  const getStatusColor = (status) => {
    switch (status?.toUpperCase()) {
      case 'ACTIVE':
        return '#28a745'
      case 'EXPIRED':
        return '#ffc107'
      case 'CANCELLED':
        return '#dc3545'
      default:
        return '#6c757d'
    }
  }

  if (!member) return null

  return (
    <div
      style={{
        position: 'fixed',
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
        backgroundColor: 'rgba(0, 0, 0, 0.5)',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        zIndex: 1000,
        padding: '20px',
      }}
      onClick={onClose}
    >
      <div
        style={{
          backgroundColor: 'white',
          borderRadius: '12px',
          maxWidth: '900px',
          width: '100%',
          maxHeight: '90vh',
          overflow: 'auto',
          position: 'relative',
        }}
        onClick={(e) => e.stopPropagation()}
      >
        <div style={{ padding: '30px', borderBottom: '1px solid #dee2e6' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <h2 style={{ margin: 0, fontSize: '2rem' }}>Member Details</h2>
            <button
              onClick={onClose}
              style={{
                padding: '8px 16px',
                backgroundColor: '#dc3545',
                color: 'white',
                border: 'none',
                borderRadius: '6px',
                cursor: 'pointer',
                fontSize: '1rem',
              }}
            >
              Close
            </button>
          </div>
        </div>

        <div style={{ padding: '30px' }}>
          {/* Member Information */}
          <div style={{ marginBottom: '30px' }}>
            <h3 style={{ marginBottom: '15px', color: '#646cff' }}>Member Information</h3>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '15px' }}>
              <div>
                <strong>Name:</strong> {member.name}
              </div>
              <div>
                <strong>Email:</strong> {member.email}
              </div>
              <div>
                <strong>Status:</strong>{' '}
                <span
                  style={{
                    padding: '4px 12px',
                    borderRadius: '4px',
                    backgroundColor: getStatusColor(member.membership_status) + '20',
                    color: getStatusColor(member.membership_status),
                    fontWeight: 'bold',
                    fontSize: '0.85rem',
                    textTransform: 'capitalize',
                  }}
                >
                  {member.membership_status}
                </span>
              </div>
              <div>
                <strong>Registered:</strong> {formatDate(member.created_at)}
              </div>
              <div>
                <strong>Total Spent:</strong> {formatPrice(member.total_spent)}
              </div>
              <div>
                <strong>Total Owed:</strong> {formatPrice(member.total_owed)}
              </div>
            </div>
          </div>

          {/* Active Subscriptions */}
          <div style={{ marginBottom: '30px' }}>
            <h3 style={{ marginBottom: '15px', color: '#646cff' }}>
              Active Subscriptions ({member.active_subscriptions?.length || 0})
            </h3>
            {member.active_subscriptions && member.active_subscriptions.length > 0 ? (
              <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
                {member.active_subscriptions.map((sub) => (
                  <div
                    key={sub.id}
                    style={{
                      padding: '15px',
                      border: '1px solid #dee2e6',
                      borderRadius: '8px',
                      backgroundColor: '#f8f9fa',
                    }}
                  >
                    <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '10px' }}>
                      <strong>{sub.membership_offer?.name}</strong>
                      <span
                        style={{
                          padding: '4px 12px',
                          borderRadius: '4px',
                          backgroundColor: getStatusColor(sub.status) + '20',
                          color: getStatusColor(sub.status),
                          fontWeight: 'bold',
                          fontSize: '0.85rem',
                        }}
                      >
                        {sub.status}
                      </span>
                    </div>
                    <div style={{ fontSize: '0.9rem', color: '#666' }}>
                      <div>Category: {sub.membership_offer?.category?.replace('_', ' ')}</div>
                      <div>Price Paid: {formatPrice(sub.price_paid)}</div>
                      <div>Start Date: {formatDate(sub.start_date)}</div>
                      <div>End Date: {formatDate(sub.end_date)}</div>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <p style={{ color: '#666' }}>No active subscriptions.</p>
            )}
          </div>

          {/* Payment History */}
          <div>
            <h3 style={{ marginBottom: '15px', color: '#646cff' }}>
              Payment History ({member.payments?.length || 0})
            </h3>
            {member.payments && member.payments.length > 0 ? (
              <div style={{ display: 'flex', flexDirection: 'column', gap: '10px', maxHeight: '400px', overflowY: 'auto' }}>
                {member.payments.map((payment) => (
                  <div
                    key={payment.id}
                    style={{
                      padding: '15px',
                      border: '1px solid #dee2e6',
                      borderRadius: '8px',
                      backgroundColor: '#f8f9fa',
                    }}
                  >
                    <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '10px' }}>
                      <strong>{payment.membership_offer?.name}</strong>
                      <div>
                        <span
                          style={{
                            padding: '4px 12px',
                            borderRadius: '4px',
                            backgroundColor: payment.status === 'PAID' ? '#28a74520' : '#ffc10720',
                            color: payment.status === 'PAID' ? '#28a745' : '#ffc107',
                            fontWeight: 'bold',
                            fontSize: '0.85rem',
                            marginRight: '10px',
                          }}
                        >
                          {payment.status}
                        </span>
                        <strong>{formatPrice(payment.amount)}</strong>
                      </div>
                    </div>
                    <div style={{ fontSize: '0.9rem', color: '#666' }}>
                      <div>Payment Method: {payment.payment_method?.replace('_', ' ')}</div>
                      <div>Payment Date: {formatDateTime(payment.payment_date || payment.created_at)}</div>
                      {payment.payment_code && <div>Payment Code: {payment.payment_code}</div>}
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <p style={{ color: '#666' }}>No payment history.</p>
            )}
          </div>
        </div>
      </div>
    </div>
  )
}

export default MemberDetail

