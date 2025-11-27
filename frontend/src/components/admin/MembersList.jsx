import { useState } from 'react'
import '../../pages/admin/AdminPages.css'

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

  const getStatusBadge = (status) => {
    switch (status) {
      case 'active':
        return 'admin-badge-success'
      case 'expired':
        return 'admin-badge-warning'
      case 'inactive':
        return 'admin-badge-danger'
      default:
        return 'admin-badge-info'
    }
  }

  if (loading) {
    return (
      <div className="admin-loading">
        <div className="admin-spinner"></div>
        <p>Loading members...</p>
      </div>
    )
  }

  if (members.length === 0) {
    return (
      <div className="admin-empty">
        <p>No members found.</p>
      </div>
    )
  }

  return (
    <table className="admin-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Status</th>
          <th>Active Subscriptions</th>
          <th>Total Spent</th>
          <th>Last Payment</th>
          <th>Registered</th>
        </tr>
      </thead>
      <tbody>
        {members.map((member) => (
          <tr
            key={member.id}
            onClick={() => onMemberClick(member)}
            style={{ cursor: 'pointer' }}
          >
            <td>{member.name}</td>
            <td>{member.email}</td>
            <td>
              <span className={`admin-badge ${getStatusBadge(member.membership_status)}`}>
                {member.membership_status}
              </span>
            </td>
            <td>{member.active_subscriptions_count}</td>
              <td>{formatPrice(member.total_spent)}</td>
            <td>{formatDate(member.last_payment_date)}</td>
            <td>{formatDate(member.created_at)}</td>
          </tr>
        ))}
      </tbody>
    </table>
  )
}

export default MembersList

