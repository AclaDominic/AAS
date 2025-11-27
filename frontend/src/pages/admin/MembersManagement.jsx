import { useState, useEffect } from 'react'
import { membersService } from '../../services/membersService'
import AdminLayout from '../../components/layout/AdminLayout'
import MembersList from '../../components/admin/MembersList'
import MemberDetail from '../../components/admin/MemberDetail'
import './AdminPages.css'

function MembersManagement() {
  const [members, setMembers] = useState([])
  const [selectedMember, setSelectedMember] = useState(null)
  const [loading, setLoading] = useState(true)
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const [categoryFilter, setCategoryFilter] = useState('')
  const [sortBy, setSortBy] = useState('created_at')
  const [sortOrder, setSortOrder] = useState('desc')
  const [currentPage, setCurrentPage] = useState(1)
  const [stats, setStats] = useState(null)
  const [pagination, setPagination] = useState(null)

  useEffect(() => {
    loadMembers()
    loadStats()
  }, [currentPage, search, statusFilter, categoryFilter, sortBy, sortOrder])

  const loadMembers = async () => {
    try {
      setLoading(true)
      const params = {
        page: currentPage,
        per_page: 15,
        search,
        status: statusFilter || undefined,
        category: categoryFilter || undefined,
        sort_by: sortBy,
        sort_order: sortOrder,
      }
      const data = await membersService.getMembers(params)
      setMembers(data.data || [])
      setPagination({
        current_page: data.current_page,
        last_page: data.last_page,
        per_page: data.per_page,
        total: data.total,
      })
    } catch (error) {
      console.error('Error loading members:', error)
    } finally {
      setLoading(false)
    }
  }

  const loadStats = async () => {
    try {
      const data = await membersService.getStats()
      setStats(data)
    } catch (error) {
      console.error('Error loading stats:', error)
    }
  }

  const handleMemberClick = async (member) => {
    try {
      const memberData = await membersService.getMember(member.id)
      setSelectedMember(memberData)
    } catch (error) {
      console.error('Error loading member details:', error)
    }
  }

  const handleSearch = (e) => {
    e.preventDefault()
    setCurrentPage(1)
    loadMembers()
  }

  const handleFilterChange = () => {
    setCurrentPage(1)
    loadMembers()
  }

  return (
    <AdminLayout>
      <div className="admin-page-container">
        <h1 className="admin-page-title">Members Management</h1>

        {/* Statistics Cards */}
        {stats && (
          <div className="admin-stats-grid">
            <div className="admin-stat-card">
              <div className="admin-stat-label">Total Members</div>
              <div className="admin-stat-value admin-stat-value-primary">{stats.total_members}</div>
            </div>
            <div className="admin-stat-card">
              <div className="admin-stat-label">Active</div>
              <div className="admin-stat-value admin-stat-value-success">{stats.active_members}</div>
            </div>
            <div className="admin-stat-card">
              <div className="admin-stat-label">Expired</div>
              <div className="admin-stat-value admin-stat-value-warning">{stats.expired_members}</div>
            </div>
            <div className="admin-stat-card">
              <div className="admin-stat-label">Total Revenue</div>
              <div className="admin-stat-value admin-stat-value-primary">
                ₱{new Intl.NumberFormat('en-PH').format(stats.total_revenue)}
              </div>
            </div>
          </div>
        )}

        {/* Search and Filters */}
        <div className="admin-card">
          <form onSubmit={handleSearch} style={{ marginBottom: '15px' }}>
            <div className="admin-search-bar">
              <input
                type="text"
                className="admin-input admin-search-input"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                placeholder="Search by name or email..."
              />
              <button type="submit" className="admin-button admin-button-primary">
                Search
              </button>
            </div>
          </form>

          <div style={{ display: 'flex', gap: '15px', flexWrap: 'wrap' }}>
            <div className="admin-form-group" style={{ minWidth: '150px' }}>
              <label className="admin-label">Status</label>
              <select
                className="admin-select admin-filter-select"
                value={statusFilter}
                onChange={(e) => {
                  setStatusFilter(e.target.value)
                  handleFilterChange()
                }}
              >
                <option value="">All</option>
                <option value="active">Active</option>
                <option value="expired">Expired</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>

            <div className="admin-form-group" style={{ minWidth: '150px' }}>
              <label className="admin-label">Category</label>
              <select
                className="admin-select admin-filter-select"
                value={categoryFilter}
                onChange={(e) => {
                  setCategoryFilter(e.target.value)
                  handleFilterChange()
                }}
              >
                <option value="">All</option>
                <option value="GYM">Gym</option>
                <option value="BADMINTON_COURT">Badminton</option>
              </select>
            </div>

            <div className="admin-form-group" style={{ minWidth: '150px' }}>
              <label className="admin-label">Sort By</label>
              <select
                className="admin-select admin-filter-select"
                value={sortBy}
                onChange={(e) => {
                  setSortBy(e.target.value)
                  handleFilterChange()
                }}
              >
                <option value="created_at">Registration Date</option>
                <option value="name">Name</option>
                <option value="email">Email</option>
              </select>
            </div>

            <div className="admin-form-group" style={{ minWidth: '150px' }}>
              <label className="admin-label">Order</label>
              <select
                className="admin-select admin-filter-select"
                value={sortOrder}
                onChange={(e) => {
                  setSortOrder(e.target.value)
                  handleFilterChange()
                }}
              >
                <option value="desc">Descending</option>
                <option value="asc">Ascending</option>
              </select>
            </div>
          </div>
        </div>

        {/* Members List */}
        <div className="admin-table-container">
          <MembersList members={members} onMemberClick={handleMemberClick} loading={loading} />
        </div>

        {/* Pagination */}
        {pagination && pagination.last_page > 1 && (
          <div className="admin-pagination">
            <button
              className="admin-pagination-button"
              onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
              disabled={currentPage === 1}
            >
              Previous
            </button>
            <span style={{ padding: '8px 16px', display: 'flex', alignItems: 'center', color: '#ffffff' }}>
              Page {pagination.current_page} of {pagination.last_page}
            </span>
            <button
              className="admin-pagination-button"
              onClick={() => setCurrentPage((p) => Math.min(pagination.last_page, p + 1))}
              disabled={currentPage === pagination.last_page}
            >
              Next
            </button>
          </div>
        )}

        {/* Member Detail Modal */}
        {selectedMember && (
          <MemberDetail member={selectedMember} onClose={() => setSelectedMember(null)} />
        )}
      </div>
    </AdminLayout>
  )
}

export default MembersManagement

