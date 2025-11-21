import MemberNavbar from '../member/MemberNavbar'

function MemberLayout({ children }) {
  return (
    <div style={{ minHeight: '100vh', backgroundColor: '#1a1a1a', position: 'relative', overflow: 'hidden' }}>
      {/* Background decorative elements */}
      <div style={{
        position: 'absolute',
        top: 0,
        right: 0,
        width: '300px',
        height: '300px',
        background: 'linear-gradient(135deg, rgba(100, 108, 255, 0.1) 0%, rgba(100, 108, 255, 0.05) 100%)',
        borderRadius: '50%',
        filter: 'blur(60px)',
        zIndex: 0,
      }} />
      <div style={{
        position: 'absolute',
        bottom: 0,
        right: 0,
        width: '400px',
        height: '400px',
        background: 'linear-gradient(45deg, rgba(100, 108, 255, 0.1) 0%, rgba(100, 108, 255, 0.05) 100%)',
        borderRadius: '50%',
        filter: 'blur(80px)',
        zIndex: 0,
      }} />
      
      <MemberNavbar />
      <main style={{ position: 'relative', zIndex: 1 }}>
        {children}
      </main>
    </div>
  )
}

export default MemberLayout

