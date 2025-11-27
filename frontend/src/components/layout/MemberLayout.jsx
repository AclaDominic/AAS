import { useState, useEffect } from 'react'
import MemberSidebar from '../member/MemberSidebar'
import './MemberLayout.css'

function MemberLayout({ children }) {
  const [sidebarOpen, setSidebarOpen] = useState(true)

  useEffect(() => {
    document.body.classList.add('member-page-active')
    document.getElementById('root')?.classList.add('member-root')
    
    return () => {
      document.body.classList.remove('member-page-active')
      document.getElementById('root')?.classList.remove('member-root')
    }
  }, [])

  return (
    <div className="member-layout">
      <MemberSidebar isOpen={sidebarOpen} onToggle={() => setSidebarOpen(!sidebarOpen)} />
      <div className="member-main-content">
        <div className="member-header">
          <button 
            className="member-hamburger"
            onClick={() => setSidebarOpen(!sidebarOpen)}
          >
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3 12H21M3 6H21M3 18H21" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
            </svg>
          </button>
          <div className="member-header-brand">
            <span className="member-brand-text">L.R. CAMACHO</span>
            <span className="member-brand-icon">💪</span>
          </div>
          <div className="member-search-bar">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="11" cy="11" r="8" stroke="currentColor" strokeWidth="2"/>
              <path d="m21 21-4.35-4.35" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
            </svg>
            <input type="text" placeholder="Search" />
          </div>
        </div>
        <div className="member-content-area">
          {children}
        </div>
      </div>
    </div>
  )
}

export default MemberLayout
