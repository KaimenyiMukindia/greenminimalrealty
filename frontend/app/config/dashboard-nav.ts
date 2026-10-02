export const dashboardNav = [
  {
    id: 'content',
    label: 'Content Management',
    icon: '▤',
    tabs: [
      { label: 'Pages', to: '/dashboard/page-meta' },
      { label: 'Menus', to: '/dashboard/menus' },
      { label: 'Services', to: '/dashboard/services' },
      { label: 'Testimonials', to: '/dashboard/testimonials' },
      { label: 'Values', to: '/dashboard/values' },
      { label: 'Sustainability', to: '/dashboard/pillars' },
      { label: 'Stats', to: '/dashboard/stats' },
      { label: 'Site Settings', to: '/dashboard/settings?rail=content' },
      { label: 'SEO', to: '/dashboard/seo' },
    ],
  },
  {
    id: 'workspace',
    label: 'Workspace',
    icon: '⌂',
    tabs: [
      { label: 'Property Listings', to: '/dashboard/properties' },
      { label: 'Airbnb Management', to: '/dashboard/airbnb-listings' },
      { label: 'Billing', to: '/dashboard/billing' },
    ],
  },
  { id: 'media', label: 'Media', icon: '▧', tabs: [{ label: 'Media Library', to: '/dashboard/media' }] },
  { id: 'users', label: 'Users', icon: '♙', tabs: [{ label: 'Team Members', to: '/dashboard/users' }] },
  { id: 'settings', label: 'Settings', icon: '⚙', tabs: [{ label: 'Site Settings', to: '/dashboard/settings?rail=settings' }] },
  { id: 'account', label: 'Account', icon: '◉', tabs: [{ label: 'Profile & Password', to: '/dashboard/account' }] },
] as const
