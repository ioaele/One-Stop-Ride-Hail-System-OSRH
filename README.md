# One-Stop Ride-Hail System (OSRH)
A web-based platform for managing ride-hailing and vehicle rental services.

OSRH is a full-stack ride service system that allows users to request rides, track drivers in real time, manage vehicles, process payments, and analyze system activity.

The system supports multiple user roles and advanced routing logic including multi-zone transport.
## Features
- Ride request system
- Real-time driver tracking
- Vehicle rental functionality
- Multi-hop ride routing
- Payment processing
- Rating & feedback system
- Driver and fleet management
- Reports & statistics dashboard

## User Roles
### Passenger
- Create account / login
- Request rides or rent vehicles
- Track rides live
- Pay and rate drivers

### Driver
- Set availability status
- Accept or reject ride requests
- Complete trips
- Upload verification documents

### Company
- Manage vehicle fleet
- Upload company and vehicle documents

### Admin
- Full system access
- Manage operators
- View analytics & reports

### Operator
- Verify documents
- Approve or reject submissions
- Record vehicle safety inspections

## Tech Stack

**Frontend**
- HTML
- CSS
- JavaScript
- Leaflet.js
- Geolocation API

**Backend**
- PHP
- Microsoft SQL Server
- Stored Procedures

**Database**
- 3NF normalized schema
- Geography spatial data types
- Triggers & constraints
- Role-based access

## Security

- Password hashing
- GDPR consent tracking
- Encrypted card data storage
- Role-based authorization

## Requirements

- Modern browser (Chrome, Firefox, Edge)
- PHP server environment
- Microsoft SQL Server

## Current Limitations

- Uses polling instead of real-time push updates
- Depends on external geocoding APIs
- Static pricing (no surge pricing)
- Web-only application (no mobile app)
- No push notifications

## Future Improvements

- WebSockets for live updates
- Mobile app version
- Dynamic pricing
- In-app chat
- Animated map tracking
- Machine learning ETA prediction
- Multi-language support
- Advanced analytics dashboard
