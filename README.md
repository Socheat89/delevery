# 🚚 DeliTrack – Real-time Delivery Location Tracking

A full-stack delivery driver tracking web application built with **Pure PHP**, **MySQL**, **Leaflet.js** (OpenStreetMap), and **Bootstrap 5**.

---

## 🚀 Quick Start

### 1. Prerequisites
- XAMPP (or any Apache + PHP 8 + MySQL setup)
- Place this folder at: `C:\xampp\htdocs\location\`

### 2. Database Setup
Visit: [http://localhost/location/setup.php](http://localhost/location/setup.php)

This will:
- Create the `delivery_tracking` database
- Create all tables (`users`, `locations`, `deliveries`)
- Insert a default admin + 3 sample drivers

### 3. Login
Visit: [http://localhost/location/](http://localhost/location/)

**Admin credentials:**
- Email: `admin@delivery.com`
- Password: `admin123`

**Sample driver credentials:**
- Email: `john@delivery.com`
- Password: `driver123`

---

## 📂 Folder Structure

```
/location
├── config/
│   ├── database.php        # PDO connection factory
│   └── session.php         # Auth helpers (requireAdmin, requireDriver…)
├── admin/
│   ├── index.php           # Admin dashboard
│   ├── map.php             # Full-screen live map
│   ├── drivers.php         # Driver management (CRUD)
│   ├── history.php         # Route history with filter
│   └── export.php          # CSV export
├── driver/
│   └── index.php           # Mobile driver panel
├── api/
│   ├── update_location.php # POST: save GPS ping
│   ├── get_all_locations.php # GET: all driver locations (admin)
│   ├── stats.php           # GET: dashboard stats
│   ├── start_delivery.php  # POST: start delivery session
│   ├── stop_delivery.php   # POST: stop delivery session
│   ├── save_driver.php     # POST: create/update driver
│   ├── delete_driver.php   # POST: delete driver
│   └── logout.php          # Logout + set offline
├── assets/                 # PWA icons
├── index.php               # Login page
├── setup.php               # DB initializer
├── manifest.json           # PWA manifest
├── sw.js                   # Service worker
└── .htaccess               # Security rules
```

---

## 👤 User Roles

### Admin
- Dashboard with live stats (online drivers, active deliveries, GPS pings)
- Full-screen live map — all driver markers auto-update every **5 seconds**
- Driver filter panel on the map
- Route history with date/driver filter + map visualization
- CSV export
- Create / Edit / Delete drivers

### Driver (Mobile)
- Start / Stop delivery button
- Auto-send GPS every **12 seconds** via `watchPosition`
- Live map showing own position
- GPS accuracy meter
- Session duration timer
- Speed display

---

## 🔐 Security
- Passwords hashed with `password_hash(PASSWORD_BCRYPT)`
- All DB queries use **PDO prepared statements**
- Session-based role checking on every page
- `.htaccess` blocks direct access to config files

---

## 📱 PWA
- `manifest.json` allows "Install to Home Screen"
- Service Worker (`sw.js`) provides basic offline support
- Optimized for mobile viewports

---

## 🗺 Map
Uses **Leaflet.js** with **OpenStreetMap** tiles — **no API key needed!**

---
