import api from './api'

export const offersService = {
  // Membership Offers
  async getOffers() {
    const response = await api.get('/api/admin/offers')
    return response.data
  },

  async getOffer(id) {
    const response = await api.get(`/api/admin/offers/${id}`)
    return response.data
  },

  async createOffer(data) {
    const response = await api.post('/api/admin/offers', data)
    return response.data
  },

  async updateOffer(id, data) {
    const response = await api.put(`/api/admin/offers/${id}`, data)
    return response.data
  },

  async deleteOffer(id) {
    const response = await api.delete(`/api/admin/offers/${id}`)
    return response.data
  },

  // Public: Get active offers for members
  async getActiveOffers() {
    const response = await api.get('/api/offers')
    return response.data
  },

  // Promos
  async getPromos() {
    const response = await api.get('/api/admin/promos')
    return response.data
  },

  async getPromo(id) {
    const response = await api.get(`/api/admin/promos/${id}`)
    return response.data
  },

  async createPromo(data) {
    const response = await api.post('/api/admin/promos', data)
    return response.data
  },

  async updatePromo(id, data) {
    const response = await api.put(`/api/admin/promos/${id}`, data)
    return response.data
  },

  async deletePromo(id) {
    const response = await api.delete(`/api/admin/promos/${id}`)
    return response.data
  },

  // Public: Get active promos for members
  async getActivePromos() {
    const response = await api.get('/api/promos/active')
    return response.data
  },

  // First-Time Discounts
  async getFirstTimeDiscounts() {
    const response = await api.get('/api/admin/first-time-discounts')
    return response.data
  },

  async getFirstTimeDiscount(id) {
    const response = await api.get(`/api/admin/first-time-discounts/${id}`)
    return response.data
  },

  async createFirstTimeDiscount(data) {
    const response = await api.post('/api/admin/first-time-discounts', data)
    return response.data
  },

  async updateFirstTimeDiscount(id, data) {
    const response = await api.put(`/api/admin/first-time-discounts/${id}`, data)
    return response.data
  },

  async deleteFirstTimeDiscount(id) {
    const response = await api.delete(`/api/admin/first-time-discounts/${id}`)
    return response.data
  },

  // Public: Get eligible first-time discounts
  async getEligibleFirstTimeDiscounts() {
    const response = await api.get('/api/first-time-discounts/eligible')
    return response.data
  },

  // Check eligibility
  async checkEligibility() {
    const response = await api.get('/api/memberships/eligibility')
    return response.data
  },

  // Payments
  async initiatePayment(data) {
    const response = await api.post('/api/payments/initiate', data)
    return response.data
  },

  async getAllPayments() {
    const response = await api.get('/api/payments')
    return response.data
  },

  async getPendingPayments() {
    const response = await api.get('/api/payments/pending')
    return response.data
  },

  async cancelPayment(id) {
    const response = await api.post(`/api/payments/${id}/cancel`)
    return response.data
  },

  async processOnlinePayment(id) {
    const response = await api.post(`/api/payments/${id}/process-online`)
    return response.data
  },

  // Admin: Find payment by code
  async findPaymentByCode(code) {
    const response = await api.get(`/api/admin/payments/code/${code}`)
    return response.data
  },

  // Admin: Mark payment as paid
  async markPaymentAsPaid(id, paymentDate = null) {
    const response = await api.post(`/api/admin/payments/${id}/mark-paid`, {
      payment_date: paymentDate,
    })
    return response.data
  },
}

