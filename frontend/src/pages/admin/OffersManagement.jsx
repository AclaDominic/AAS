import { useState } from 'react'
import AdminLayout from '../../components/layout/AdminLayout'
import OffersList from '../../components/admin/OffersList'
import PromosList from '../../components/admin/PromosList'
import FirstTimeDiscountsList from '../../components/admin/FirstTimeDiscountsList'

function OffersManagement() {
  const [activeTab, setActiveTab] = useState('offers')

  const tabs = [
    { id: 'offers', label: 'Membership Offers' },
    { id: 'promos', label: 'Promos' },
    { id: 'discounts', label: 'First-Time Discounts' },
  ]

  return (
    <AdminLayout>
      <div style={{ padding: '40px' }}>
        <h1 style={{ marginBottom: '30px', fontSize: '2.5rem' }}>Offers Management</h1>

        {/* Tabs */}
        <div style={{ 
          display: 'flex', 
          borderBottom: '2px solid #ddd', 
          marginBottom: '30px',
          gap: '10px'
        }}>
          {tabs.map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              style={{
                padding: '12px 24px',
                backgroundColor: 'transparent',
                border: 'none',
                borderBottom: activeTab === tab.id ? '3px solid #646cff' : '3px solid transparent',
                color: activeTab === tab.id ? '#646cff' : '#666',
                fontWeight: activeTab === tab.id ? 'bold' : 'normal',
                cursor: 'pointer',
                fontSize: '1rem',
                transition: 'all 0.2s',
              }}
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

