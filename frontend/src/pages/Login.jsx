import { useState, useEffect } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'
import lrLogo from '../assets/lrlogo.jpg'
import TermsModal from '../components/TermsModal'
import './Auth.css'

function Login() {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)
  const [showTerms, setShowTerms] = useState(false)
  const { login } = useAuth()
  const navigate = useNavigate()

  useEffect(() => {
    document.body.classList.add('auth-page-active')
    document.getElementById('root')?.classList.add('auth-root')
    
    return () => {
      document.body.classList.remove('auth-page-active')
      document.getElementById('root')?.classList.remove('auth-root')
    }
  }, [])

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')
    setLoading(true)

    try {
      await login(email, password)
      navigate('/dashboard')
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to login')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="auth-page">
      <div className="auth-left">
        <div className="auth-graphic-container">
          <div className="auth-graphic-background">
            <div className="auth-graphic-grid"></div>
            <h1 className="auth-welcome-text">Welcome!</h1>
            <div className="auth-logo-container">
              <img src={lrLogo} alt="L.R. Camacho Logo" className="auth-logo-image" />
            </div>
          </div>
        </div>
      </div>
      <div className="auth-right">
        <div className="auth-brand-top"></div>
        <div className="auth-form-container">
          <h2 className="auth-title">Login</h2>
          <p className="auth-subtitle">Welcome back! Please login to your account.</p>
          {error && <div className="auth-error">{error}</div>}
          <form onSubmit={handleSubmit} className="auth-form">
            <div className="auth-field">
              <label className="auth-label">Username</label>
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="Enter your username"
                required
                className="auth-input"
              />
            </div>
            <div className="auth-field">
              <label className="auth-label">Password</label>
              <input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="Enter your password"
                required
                className="auth-input"
              />
            </div>
            <Link to="#" className="auth-forgot-link">Forgot Password?</Link>
            <button
              type="submit"
              disabled={loading}
              className="auth-submit-button"
            >
              {loading ? 'Logging in...' : 'Login'}
            </button>
          </form>
          <div className="auth-footer">
            <div className="auth-footer-links">
              <p className="auth-footer-text">
                Don't have an account? <Link to="/register" className="auth-link">Register Now!</Link>
              </p>
              <Link to="/" className="auth-back-button-footer">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M19 12H5M5 12L12 19M5 12L12 5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                </svg>
                Back to Home
              </Link>
            </div>
            <Link to="#" className="auth-link-small" onClick={(e) => { e.preventDefault(); setShowTerms(true); }}>Terms and Services</Link>
            <p className="auth-contact-text">
              Have a problem? Contact us! <span className="auth-phone">Q9XX-XXX-XXXX</span>
            </p>
          </div>
        </div>
      </div>
      <TermsModal isOpen={showTerms} onClose={() => setShowTerms(false)} />
    </div>
  )
}

export default Login
