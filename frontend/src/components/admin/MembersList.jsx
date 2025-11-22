import { useState } from 'react'

function MembersList({ members, onMemberClick, loading }) {
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

  const getStatusColor = (status) => {
    switch (status) {
      case 'active':
        return '#28a745'
      case 'expired':
        return '#ffc107'
      case 'inactive':
        return '#6c757d'
      default:
        return '#6c757d'
    }
  }

  if (loading) {
    return (
      <div style={{ padding: '40px', textAlign: 'center' }}>
        <p>Loading members...</p>
      </div>
    )
  }

  if (members.length === 0) {
    return (
      <div style={{ padding: '40px', textAlign: 'center', color: '#666' }}>
        <p>No members found.</p>
      </div>
    )
  }

  return (
    <div style={{ overflowX: 'auto' }}>
      <table style={{ width: '100%', borderCollapse: 'collapse', backgroundColor: 'white' }}>
        <thead>
          <tr style={{ backgroundColor: '#f8f9fa', borderBottom: '2px solid #dee2e6' }}>
            <th style={{ padding: '12px', textAlign: 'left', fontWeight: 'bold' }}>Name</th>
            <th style={{ padding: '12px', textAlign: 'left', fontWeight: 'bold' }}>Email</th>
            <th style={{ padding: '12px', textAlign: 'left', fontWeight: 'bold' }}>Status</th>
            <th style={{ padding: '12px', textAlign: 'left', fontWeight: 'bold' }}>Active Subscriptions</th>
            <th style={{ padding: '12px', textAlign: 'left', fontWeight: 'bold' }}>Total Spent</th>
            <th style={{ padding: '12px', textAlign: 'left', fontWeight: 'bold' }}>Last Payment</th>
            <th style={{ padding: '12px', textAlign: 'left', fontWeight: 'bold' }}>Registered</th>
          </tr>
        </thead>
        <tbody>
          {members.map((member) => (
            <tr
              key={member.id}
              onClick={() => onMemberClick(member)}
              style={{
                borderBottom: '1px solid #dee2e6',
                cursor: 'pointer',
                transition: 'background-color 0.2s',
              }}
              onMouseEnter={(e) => {
                e.currentTarget.style.backgroundColor = '#f8f9fa'
              }}
              onMouseLeave={(e) => {
                e.currentTarget.style.backgroundColor = 'white'
              }}
            >
              <td style={{ padding: '12px' }}>{member.name}</td>
              <td style={{ padding: '12px' }}>{member.email}</td>
              <td style={{ padding: '12px' }}>
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
              </td>
              <td style={{ padding: '12px' }}>{member.active_subscriptions_count}</td>
              <td style={{ padding: '12px' }}>{formatPrice(member.total_spent)}</td>
              <td style={{ padding: '12px' }}>{formatDate(member.last_payment_date)}</td>
              <td style={{ padding: '12px' }}>{formatDate(member.created_at)}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}

export default MembersList

