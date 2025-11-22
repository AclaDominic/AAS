import api from './api'

export const membersService = {
  // Get all members with pagination, search, and filters
  async getMembers(params = {}) {
    const response = await api.get('/api/admin/members', { params })
    return response.data
  },

  // Get member details
  async getMember(id) {
    const response = await api.get(`/api/admin/members/${id}`)
    return response.data
  },

  // Get member statistics
  async getStats() {
    const response = await api.get('/api/admin/members/stats')
    return response.data
  },
}

