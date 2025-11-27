import { useEffect } from 'react'
import './TermsModal.css'

function TermsModal({ isOpen, onClose }) {
  useEffect(() => {
    if (isOpen) {
      document.body.style.overflow = 'hidden'
    } else {
      document.body.style.overflow = 'unset'
    }
    
    return () => {
      document.body.style.overflow = 'unset'
    }
  }, [isOpen])

  if (!isOpen) return null

  return (
    <div className="terms-modal-overlay" onClick={onClose}>
      <div className="terms-modal-container" onClick={(e) => e.stopPropagation()}>
        <div className="terms-modal-header">
          <h2 className="terms-modal-title">Terms and Conditions</h2>
          <button className="terms-modal-close" onClick={onClose} aria-label="Close">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
            </svg>
          </button>
        </div>
        
        <div className="terms-modal-content">
          <div className="terms-section">
            <h3>1. Acceptance of Terms</h3>
            <p>
              By accessing and using L.R. Camacho Badminton & Gym services, you accept and agree to be bound by the terms 
              and provision of this agreement. If you do not agree to abide by the above, please do not use this service.
            </p>
          </div>

          <div className="terms-section">
            <h3>2. Membership and Services</h3>
            <p>
              Membership fees are non-refundable. All memberships are subject to our facility rules and regulations. 
              Court reservations are subject to availability and must be made in advance through our online system.
            </p>
          </div>

          <div className="terms-section">
            <h3>3. Payment Terms</h3>
            <p>
              All payments must be made in advance. We accept cash, credit/debit cards, and digital payment methods. 
              Membership fees are charged according to the selected plan. Late payments may result in suspension of services.
            </p>
          </div>

          <div className="terms-section">
            <h3>4. Cancellation and Refund Policy</h3>
            <p>
              Court reservations can be cancelled up to 2 hours before the scheduled time. No refunds will be issued 
              for cancellations made less than 2 hours before the reservation time. Membership cancellations require 
              30 days written notice.
            </p>
          </div>

          <div className="terms-section">
            <h3>5. Facility Rules and Conduct</h3>
            <p>
              All members and guests must follow facility rules, including proper attire, equipment usage, and respectful 
              conduct. Violation of rules may result in suspension or termination of membership without refund.
            </p>
          </div>

          <div className="terms-section">
            <h3>6. Privacy Policy</h3>
            <p>
              We are committed to protecting your privacy. Your personal information will be used solely for facility 
              management, communication, and service delivery. We do not share your information with third parties 
              without your consent, except as required by law.
            </p>
          </div>

          <div className="terms-section">
            <h3>7. Liability and Waiver</h3>
            <p>
              By using our facilities, you acknowledge that participation in physical activities involves inherent risks. 
              You agree to assume all risks and release L.R. Camacho Badminton & Gym from any liability for injuries 
              or damages that may occur during facility use.
            </p>
          </div>

          <div className="terms-section">
            <h3>8. Changes to Terms</h3>
            <p>
              We reserve the right to modify these terms at any time. Changes will be effective immediately upon posting. 
              Continued use of our services after changes constitutes acceptance of the new terms.
            </p>
          </div>

          <div className="terms-section">
            <h3>9. Contact Information</h3>
            <p>
              For questions about these terms, please contact us at:
              <br />
              Email: info@lrcamacho.com
              <br />
              Phone: Q9XX-XXX-XXXX
            </p>
          </div>
        </div>

        <div className="terms-modal-footer">
          <button className="terms-modal-button" onClick={onClose}>
            I Understand
          </button>
        </div>
      </div>
    </div>
  )
}

export default TermsModal

