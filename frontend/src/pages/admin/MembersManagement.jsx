import { useState, useEffect } from 'react'
import { membersService } from '../../services/membersService'
import AdminLayout from '../../components/layout/AdminLayout'
import MembersList from '../../components/admin/MembersList'
import MemberDetail from '../../components/admin/MemberDetail'

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
      <div style={{ padding: '40px' }}>
        <h1 style={{ marginBottom: '30px', fontSize: '2.5rem', color: '#646cff' }}>
          Members Management
        </h1>

        {/* Statistics Cards */}
        {stats && (
          <div
            style={{
              display: 'grid',
              gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))',
              gap: '20px',
              marginBottom: '30px',
            }}
          >
            <div style={{ padding: '20px', backgroundColor: 'white', borderRadius: '8px', boxShadow: '0 2px 4px rgba(0,0,0,0.1)' }}>
              <div style={{ color: '#666', fontSize: '0.9rem', marginBottom: '5px' }}>Total Members</div>
              <div style={{ fontSize: '2rem', fontWeight: 'bold', color: '#646cff' }}>{stats.total_members}</div>
            </div>
            <div style={{ padding: '20px', backgroundColor: 'white', borderRadius: '8px', boxShadow: '0 2px 4px rgba(0,0,0,0.1)' }}>
              <div style={{ color: '#666', fontSize: '0.9rem', marginBottom: '5px' }}>Active</div>
              <div style={{ fontSize: '2rem', fontWeight: 'bold', color: '#28a745' }}>{stats.active_members}</div>
            </div>
            <div style={{ padding: '20px', backgroundColor: 'white', borderRadius: '8px', boxShadow: '0 2px 4px rgba(0,0,0,0.1)' }}>
              <div style={{ color: '#666', fontSize: '0.9rem', marginBottom: '5px' }}>Expired</div>
              <div style={{ fontSize: '2rem', fontWeight: 'bold', color: '#ffc107' }}>{stats.expired_members}</div>
            </div>
            <div style={{ padding: '20px', backgroundColor: 'white', borderRadius: '8px', boxShadow: '0 2px 4px rgba(0,0,0,0.1)' }}>
              <div style={{ color: '#666', fontSize: '0.9rem', marginBottom: '5px' }}>Total Revenue</div>
              <div style={{ fontSize: '2rem', fontWeight: 'bold', color: '#646cff' }}>
                ₱{new Intl.NumberFormat('en-PH').format(stats.total_revenue)}
              </div>
            </div>
          </div>
        )}

        {/* Search and Filters */}
        <div
          style={{
            backgroundColor: 'white',
            padding: '20px',
            borderRadius: '8px',
            marginBottom: '20px',
            boxShadow: '0 2px 4px rgba(0,0,0,0.1)',
          }}
        >
          <form onSubmit={handleSearch} style={{ marginBottom: '15px' }}>
            <div style={{ display: 'flex', gap: '10px' }}>
              <input
                type="text"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                placeholder="Search by name or email..."
                style={{
                  flex: 1,
                  padding: '10px',
                  border: '1px solid #ddd',
                  borderRadius: '6px',
                  fontSize: '1rem',
                }}
              />
              <button
                type="submit"
                style={{
                  padding: '10px 20px',
                  backgroundColor: '#646cff',
                  color: 'white',
                  border: 'none',
                  borderRadius: '6px',
                  cursor: 'pointer',
                  fontSize: '1rem',
                }}
              >
                Search
              </button>
            </div>
          </form>

          <div style={{ display: 'flex', gap: '15px', flexWrap: 'wrap' }}>
            <div>
              <label style={{ display: 'block', marginBottom: '5px', fontSize: '0.9rem', color: '#666' }}>
                Status
              </label>
              <select
                value={statusFilter}
                onChange={(e) => {
                  setStatusFilter(e.target.value)
                  handleFilterChange()
                }}
                style={{
                  padding: '8px',
                  border: '1px solid #ddd',
                  borderRadius: '6px',
                  fontSize: '1rem',
                }}
              >
                <option value="">All</option>
                <option value="active">Active</option>
                <option value="expired">Expired</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>

            <div>
              <label style={{ display: 'block', marginBottom: '5px', fontSize: '0.9rem', color: '#666' }}>
                Category
              </label>
              <select
                value={categoryFilter}
                onChange={(e) => {
                  setCategoryFilter(e.target.value)
                  handleFilterChange()
                }}
                style={{
                  padding: '8px',
                  border: '1px solid #ddd',
                  borderRadius: '6px',
                  fontSize: '1rem',
                }}
              >
                <option value="">All</option>
                <option value="GYM">Gym</option>
                <option value="BADMINTON_COURT">Badminton</option>
              </select>
            </div>

            <div>
              <label style={{ display: 'block', marginBottom: '5px', fontSize: '0.9rem', color: '#666' }}>
                Sort By
              </label>
              <select
                value={sortBy}
                onChange={(e) => {
                  setSortBy(e.target.value)
                  handleFilterChange()
                }}
                style={{
                  padding: '8px',
                  border: '1px solid #ddd',
                  borderRadius: '6px',
                  fontSize: '1rem',
                }}
              >
                <option value="created_at">Registration Date</option>
                <option value="name">Name</option>
                <option value="email">Email</option>
              </select>
            </div>

            <div>
              <label style={{ display: 'block', marginBottom: '5px', fontSize: '0.9rem', color: '#666' }}>
                Order
              </label>
              <select
                value={sortOrder}
                onChange={(e) => {
                  setSortOrder(e.target.value)
                  handleFilterChange()
                }}
                style={{
                  padding: '8px',
                  border: '1px solid #ddd',
                  borderRadius: '6px',
                  fontSize: '1rem',
                }}
              >
                <option value="desc">Descending</option>
                <option value="asc">Ascending</option>
              </select>
            </div>
          </div>
        </div>

        {/* Members List */}
        <div style={{ backgroundColor: 'white', borderRadius: '8px', boxShadow: '0 2px 4px rgba(0,0,0,0.1)' }}>
          <MembersList members={members} onMemberClick={handleMemberClick} loading={loading} />
        </div>

        {/* Pagination */}
        {pagination && pagination.last_page > 1 && (
          <div style={{ display: 'flex', justifyContent: 'center', gap: '10px', marginTop: '20px' }}>
            <button
              onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
              disabled={currentPage === 1}
              style={{
                padding: '8px 16px',
                backgroundColor: currentPage === 1 ? '#ccc' : '#646cff',
                color: 'white',
                border: 'none',
                borderRadius: '6px',
                cursor: currentPage === 1 ? 'not-allowed' : 'pointer',
              }}
            >
              Previous
            </button>
            <span style={{ padding: '8px 16px', display: 'flex', alignItems: 'center' }}>
              Page {pagination.current_page} of {pagination.last_page}
            </span>
            <button
              onClick={() => setCurrentPage((p) => Math.min(pagination.last_page, p + 1))}
              disabled={currentPage === pagination.last_page}
              style={{
                padding: '8px 16px',
                backgroundColor: currentPage === pagination.last_page ? '#ccc' : '#646cff',
                color: 'white',
                border: 'none',
                borderRadius: '6px',
                cursor: currentPage === pagination.last_page ? 'not-allowed' : 'pointer',
              }}
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

