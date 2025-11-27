import { Link, useLocation } from 'react-router-dom'
import lrLogo from '../assets/lrlogo.jpg'

function PublicHeader() {
  const location = useLocation()

  return (
    <header className="landing-header">
      <div className="header-left">
        <button className="menu-icon" aria-label="Menu">
          <span></span>
          <span></span>
          <span></span>
        </button>
        <Link to="/" className="logo-container">
       
          <span className="logo-text">L.R. CAMACHO </span>
        </Link>
      </div>
      <nav className="header-nav">
        <Link to="/" className={`nav-link ${location.pathname === '/' ? 'active' : ''}`}>
          Home
        </Link>
        <Link to="/services" className={`nav-link ${location.pathname === '/services' ? 'active' : ''}`}>
          Services
        </Link>
        <Link to="/about" className={`nav-link ${location.pathname === '/about' ? 'active' : ''}`}>
          About Us
        </Link>
        <Link to="/faq" className={`nav-link ${location.pathname === '/faq' ? 'active' : ''}`}>
          FAQ
        </Link>
      </nav>
      <div className="header-right">
        {location.pathname !== '/' && (
          <Link to="/" className="header-back-button">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M19 12H5M5 12L12 19M5 12L12 5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
            </svg>
            Back
          </Link>
        )}
        <Link to="/login" className="profile-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="12" cy="8" r="4" stroke="white" strokeWidth="2" fill="none"/>
            <path d="M6 21c0-3.314 2.686-6 6-6s6 2.686 6 6" stroke="white" strokeWidth="2" fill="none"/>
          </svg>
        </Link>
      </div>
    </header>
  )
}

export default PublicHeader

