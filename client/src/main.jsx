import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'

import {
  createBrowserRouter,
  RouterProvider,
} from 'react-router-dom'

import { HelmetProvider } from 'react-helmet-async'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { AuthProvider } from './context/AuthContext'
import { ThemeProvider } from './context/ThemeContext'
import { ToastProvider } from './context/ToastContext'
import { GoogleOAuthProvider } from '@react-oauth/google'

import MainLayout from './layouts/MainLayout.jsx'
import Homepage from './layouts/Homepage.jsx'
import Aboutpage from './layouts/Aboutpage.jsx'
import LoginPage from './layouts/LoginPage.jsx'
import RegisterPage from './layouts/RegisterPage.jsx'
import ForgotPassword from './layouts/Forgotpassword.jsx'
import ResetPassword from './layouts/Resetpassword.jsx'
import SalonDetail from './layouts/SalonDetail.jsx'
import Trending from './layouts/Trending.jsx'
import MostPopular from './layouts/MostPopular.jsx'
import Deals from './layouts/Deals.jsx'
import Services from './layouts/Services.jsx'
import Salons from './layouts/Salons.jsx'
import TermsAndConditions from './layouts/TermsAndConditions.jsx'
import PrivacyPolicy from './layouts/PrivacyPolicy.jsx'

// Customer Dashboard
import ProtectedRoute from './components/ProtectedRoute.jsx'
import DashboardLayout from './layouts/customer/DashboardLayout.jsx'
import DashboardOverview from './layouts/customer/DashboardOverview.jsx'
import UpcomingBookings from './layouts/customer/UpcomingBookings.jsx'
import BookingHistory from './layouts/customer/BookingHistory.jsx'
import ProfilePage from './layouts/customer/ProfilePage.jsx'
import ChangePassword from './layouts/customer/ChangePassword.jsx'
import FavoriteSalons from './layouts/customer/FavoriteSalons.jsx'

// Admin & Owner Dashboards
import RoleRoute from './components/RoleRoute.jsx'
import AdminLayout from './layouts/AdminLayout.jsx'
import AdminDashboard from './layouts/AdminDashboard.jsx'
import AdminBookings from './layouts/admin/AdminBookings.jsx'
import AdminSalons from './layouts/admin/AdminSalons.jsx'
import AdminUsers from './layouts/admin/AdminUsers.jsx'
import AdminSubscriptions from './layouts/admin/AdminSubscriptions.jsx'
import OwnerLayout from './layouts/OwnerLayout.jsx'
import OwnerDashboard from './layouts/OwnerDashboard.jsx'
import OwnerSalons from './layouts/owner/OwnerSalons.jsx'
import OwnerBookings from './layouts/owner/OwnerBookings.jsx'
import OwnerServices from './layouts/owner/OwnerServices.jsx'
import OwnerSubscription from './layouts/owner/OwnerSubscription.jsx'
import OwnerProfile from './layouts/owner/OwnerProfile.jsx'
import AdminProfile from './layouts/admin/AdminProfile.jsx'
import AdminPackages from './layouts/admin/AdminPackages.jsx'
import AdminCategories from './layouts/admin/AdminCategories.jsx'
import AdminDepartments from './layouts/admin/AdminDepartments.jsx'


// Create a client with caching configuration
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 5 * 60 * 1000, // Data stays fresh for 5 minutes (no refetch)
      cacheTime: 10 * 60 * 1000, // Cache persists for 10 minutes
      refetchOnWindowFocus: false, // Don't refetch when window regains focus
      refetchOnMount: false, // Don't refetch on component mount if data exists
      retry: 1, // Retry failed requests once
    },
  },
})

const router = createBrowserRouter([
  {
    element: <MainLayout />,
    children: [
      { path: "/", element: <Homepage /> },
      { path: "login", element: <LoginPage /> },
      { path: "register", element: <RegisterPage /> },
      { path: "forgot-password", element: <ForgotPassword /> },
      { path: "reset-password", element: <ResetPassword /> },
      { path: "trending", element: <Trending /> },
      { path: "popular", element: <MostPopular /> },
      { path: "/salon/:id", element: <SalonDetail /> },
      { path: "/about", element: <Aboutpage /> },
      { path: "/deals", element: <Deals /> },
      { path: "/services", element: <Services /> },
      { path: "/salons", element: <Salons /> },
      { path: "/terms-and-condition", element: <TermsAndConditions /> },
      { path: "/privacy-policy", element: <PrivacyPolicy /> },
    ],
  },
  {
    path: "/dashboard",
    element: (
      <ProtectedRoute>
        <DashboardLayout />
      </ProtectedRoute>
    ),
    children: [
      { index: true, element: <DashboardOverview /> },
      { path: "upcoming", element: <UpcomingBookings /> },
      { path: "history", element: <BookingHistory /> },
      { path: "favorites", element: <FavoriteSalons /> },
      { path: "profile", element: <ProfilePage /> },
      { path: "change-password", element: <ChangePassword /> },
    ],
  },
  {
    path: "/admin",
    element: (
      <RoleRoute role="admin">
        <AdminLayout />
      </RoleRoute>
    ),
    children: [
      { index: true,              element: <AdminDashboard /> },
      { path: "bookings",         element: <AdminBookings /> },
      { path: "salons",           element: <AdminSalons /> },
      { path: "users",            element: <AdminUsers /> },
      { path: "subscriptions",    element: <AdminSubscriptions /> },
      { path: "profile",          element: <AdminProfile /> },
      { path: "packages",         element: <AdminPackages /> },
      { path: "categories",       element: <AdminCategories /> },
      { path: "departments",      element: <AdminDepartments /> },
    ],
  },
  {
    path: "/owner",
    element: (
      <RoleRoute role="owner">
        <OwnerLayout />
      </RoleRoute>
    ),
    children: [
      { index: true,           element: <OwnerDashboard /> },
      { path: "salons",        element: <OwnerSalons /> },
      { path: "bookings",      element: <OwnerBookings /> },
      { path: "services",      element: <OwnerServices /> },
      { path: "subscription",  element: <OwnerSubscription /> },
      { path: "profile",       element: <OwnerProfile /> },
    ],
  },
])

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <HelmetProvider>
      <ThemeProvider>
        <GoogleOAuthProvider clientId={import.meta.env.VITE_GOOGLE_CLIENT_ID}>
          <AuthProvider>
            <QueryClientProvider client={queryClient}>
              <ToastProvider>
                <RouterProvider router={router} />
              </ToastProvider>
            </QueryClientProvider>
          </AuthProvider>
        </GoogleOAuthProvider>
      </ThemeProvider>
    </HelmetProvider>
  </StrictMode>
)
