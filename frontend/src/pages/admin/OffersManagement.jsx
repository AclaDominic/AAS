import { useState } from 'react'
import AdminLayout from '../../components/layout/AdminLayout'
import OffersList from '../../components/admin/OffersList'
import PromosList from '../../components/admin/PromosList'
import FirstTimeDiscountsList from '../../components/admin/FirstTimeDiscountsList'
import './AdminPages.css'

function OffersManagement() {
  const [activeTab, setActiveTab] = useState('offers')

  const tabs = [
    { id: 'offers', label: 'Membership Offers' },
    { id: 'promos', label: 'Promos' },
    { id: 'discounts', label: 'First-Time Discounts' },
  ]

  return (
    <AdminLayout>
      <div className="admin-page-container">
        <h1 className="admin-page-title">Offers Management</h1>

        {/* Tabs */}
        <div className="admin-tabs-container">
          {tabs.map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              className={`admin-tab ${activeTab === tab.id ? 'active' : ''}`}
            >
              {tab.label}
            </button>
          ))}
        </div>

        {/* Tab Content */}
        <div>
          {activeTab === 'offers' && <OffersList />}
          {activeTab === 'promos' && <PromosList />}
          {activeTab === 'discounts' && <FirstTimeDiscountsList />}
        </div>
      </div>
    </AdminLayout>
  )
}

export default OffersManagement

