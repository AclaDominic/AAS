import { useState, useEffect } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'
import lrLogo from '../assets/lrlogo.jpg'
import TermsModal from '../components/TermsModal'
import './Auth.css'

function Register() {
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [contactNumber, setContactNumber] = useState('')
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [agreeToTerms, setAgreeToTerms] = useState(false)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)
  const [showTerms, setShowTerms] = useState(false)
  const { register } = useAuth()
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

    if (password !== passwordConfirmation) {
      setError('Passwords do not match')
      return
    }

    if (!agreeToTerms) {
      setError('Please agree to the Terms and Privacy Policy')
      return
    }

    setLoading(true)

    try {
      await register(name, email, password, passwordConfirmation)
      navigate('/dashboard')
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to register')
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
            <h1 className="auth-welcome-text">Create your Account!</h1>
            <div className="auth-logo-container">
              <img src={lrLogo} alt="L.R. Camacho Logo" className="auth-logo-image" />
            </div>
          </div>
        </div>
      </div>
      <div className="auth-right">
             <div className="auth-form-container">
          <h2 className="auth-title">Register</h2>
          <p className="auth-subtitle">Create your account to get started.</p>
          {error && <div className="auth-error">{error}</div>}
          <form onSubmit={handleSubmit} className="auth-form">
            <div className="auth-field">
              <label className="auth-label">Full Name</label>
              <input
                type="text"
                value={name}
                onChange={(e) => setName(e.target.value)}
                placeholder="Enter your full name"
                required
                className="auth-input"
              />
            </div>
            <div className="auth-field">
              <label className="auth-label">Contact Number</label>
              <input
                type="tel"
                value={contactNumber}
                onChange={(e) => setContactNumber(e.target.value)}
                placeholder="Enter your number"
                required
                className="auth-input"
              />
            </div>
            <div className="auth-field">
              <label className="auth-label">Email</label>
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
            <div className="auth-field">
              <label className="auth-label">Confirm Password</label>
              <input
                type="password"
                value={passwordConfirmation}
                onChange={(e) => setPasswordConfirmation(e.target.value)}
                placeholder="Enter your password"
                required
                className="auth-input"
              />
            </div>
            <div className="auth-checkbox-field">
              <input
                type="checkbox"
                id="agree-terms"
                checked={agreeToTerms}
                onChange={(e) => setAgreeToTerms(e.target.checked)}
                className="auth-checkbox"
              />
              <label htmlFor="agree-terms" className="auth-checkbox-label">
                I agree to the <Link to="#" className="auth-link-inline" onClick={(e) => { e.preventDefault(); setShowTerms(true); }}>Terms and Privacy Policy</Link>
              </label>
            </div>
            <button
              type="submit"
              disabled={loading}
              className="auth-submit-button"
            >
              {loading ? 'Registering...' : 'Register'}
            </button>
          </form>
          <div className="auth-footer">
            <div className="auth-footer-links">
              <Link to="/login" className="auth-link">Already have an account? Login</Link>
              <Link to="/" className="auth-back-button-footer">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M19 12H5M5 12L12 19M5 12L12 5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                </svg>
                Back to Home
              </Link>
            </div>
          </div>
        </div>
      </div>
      <TermsModal isOpen={showTerms} onClose={() => setShowTerms(false)} />
    </div>
  )
}

export default Register
