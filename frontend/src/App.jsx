import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import { useAuth } from './contexts/AuthContext'
import Landing from './pages/Landing'
import Home from './pages/Home'
import Services from './pages/Services'
import AboutUs from './pages/AboutUs'
import FAQ from './pages/FAQ'
import Login from './pages/Login'
import Register from './pages/Register'
import Dashboard from './pages/Dashboard'
import Membership from './pages/member/Membership'
import Billing from './pages/member/Billing'
import OffersManagement from './pages/admin/OffersManagement'
import Payments from './pages/admin/Payments'
import MembersManagement from './pages/admin/MembersManagement'
import Reports from './pages/admin/Reports'
import FacilitySchedule from './pages/admin/FacilitySchedule'
import FacilitySettings from './pages/admin/FacilitySettings'
import CourtReservations from './pages/admin/CourtReservations'
import CourtBooking from './pages/member/CourtBooking'
import MyReservations from './pages/member/MyReservations'
import './App.css'

function ProtectedRoute({ children }) {
  const { user, loading } = useAuth()

  if (loading) {
    return <div>Loading...</div>
  }

  if (!user) {
    return <Navigate to="/login" replace />
  }

  return children
}

function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<Home />} />
        <Route path="/services" element={<Services />} />
        <Route path="/about" element={<AboutUs />} />
        <Route path="/faq" element={<FAQ />} />
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
        <Route
          path="/dashboard"
          element={
            <ProtectedRoute>
              <Dashboard />
            </ProtectedRoute>
          }
        />
        <Route
          path="/membership"
          element={
            <ProtectedRoute>
              <Membership />
            </ProtectedRoute>
          }
        />
        <Route
          path="/billing"
          element={
            <ProtectedRoute>
              <Billing />
            </ProtectedRoute>
          }
        />
        <Route
          path="/admin/offers"
          element={
            <ProtectedRoute>
              <OffersManagement />
            </ProtectedRoute>
          }
        />
        <Route
          path="/admin/payments"
          element={
            <ProtectedRoute>
              <Payments />
            </ProtectedRoute>
          }
        />
        <Route
          path="/admin/members"
          element={
            <ProtectedRoute>
              <MembersManagement />
            </ProtectedRoute>
          }
        />
        <Route
          path="/admin/reports"
          element={
            <ProtectedRoute>
              <Reports />
            </ProtectedRoute>
          }
        />
        <Route
          path="/admin/facility-schedule"
          element={
            <ProtectedRoute>
              <FacilitySchedule />
            </ProtectedRoute>
          }
        />
        <Route
          path="/admin/facility-settings"
          element={
            <ProtectedRoute>
              <FacilitySettings />
            </ProtectedRoute>
          }
        />
        <Route
          path="/admin/reservations"
          element={
            <ProtectedRoute>
              <CourtReservations />
            </ProtectedRoute>
          }
        />
        <Route
          path="/courts/booking"
          element={
            <ProtectedRoute>
              <CourtBooking />
            </ProtectedRoute>
          }
        />
        <Route
          path="/courts/reservations"
          element={
            <ProtectedRoute>
              <MyReservations />
            </ProtectedRoute>
          }
        />
      </Routes>
    </BrowserRouter>
  )
}

export default App
