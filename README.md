# BookingHomse

![BookingHomse Logo](public/images/BookingHomse169whiteBG.png)
![BookingHomse Demo](public/images/BookingHomseDemo.gif)

## Overview

BookingHomse is a comprehensive booking, reminder, and appointment management software designed specifically for equine facilities. Built with Laravel, Vue.js, and Inertia.js, it streamlines the process of scheduling riding sessions, managing horse availability, and coordinating group activities.

Whether you're running a riding school, boarding stable, or equestrian center, BookingHomse helps you keep your horses healthy, your schedule organized, and your riders informed.

## Features

### Implemented

- **🐴 Timeslot Management** - Create and manage riding availability with customizable start/end times, capacity, price, and assignments.
- **📅 Interactive Booking Calendar** - A full-featured FullCalendar interface for both public booking requests and administrative oversight.
- **🐎 Horse Management** - Track your stable with a dedicated horse database, including breed, description, and bookability status.
- **👤 Trainer Profiles** - Manage trainers and associate them with specific timeslots.
- **🖼️ Integrated Media Manager** - A custom-built media management system with directory organization, image deduplication, and automatic thumbnail generation.
- **⚙️ Site Configuration** - Global settings for site branding, logo management, and dynamic booking hour constraints.
- **🔐 Robust Authentication** - Secure access control for admins and clients, with automatic redirect handling for the booking flow.

### Planned & Upcoming

- **🐴 Horse Cooldown Management** - Automatically track and enforce cooldown periods after riding sessions.
- **📱 SMS & Email Reminders** - Automated notifications for upcoming bookings and schedule changes.
- **🗓️ Google Calendar Sync** - Two-way synchronization with external calendars using Spatie Laravel Google Calendar.

## Technology Stack

- **Backend**: Laravel 12 (PHP 8.4)
- **Frontend**: Vue 3 with Inertia.js v2
- **Bundling**: Vite 7
- **Styling**: Tailwind CSS 4
- **Calendar**: FullCalendar 6

## Getting Started

### Prerequisites

- PHP 8.4+
- Node.js & NPM
- Composer

### Installation & Local Development

1. **Clone & Install Dependencies**:
   ```bash
   composer install
   npm install
   ```

2. **Environment Setup**:
   Copy `.env.example` to `.env` and configure your database and `APP_URL`.
   Set `VITE_TZ` for your preferred calendar timezone (e.g., `VITE_TZ=America/New_York`).

3. **Database Setup**:
   ```bash
   php artisan migrate
   ```

4. **Start Development Servers**:
   ```bash
   php artisan serve
   # and in a separate terminal
   npm run dev
   ```

5. **Access the Application**:
   - Public Booking: [http://127.0.0.1:8000/request-booking](http://127.0.0.1:8000/request-booking)
   - Admin Dashboard: [http://127.0.0.1:8000/dashboard/timeslots](http://127.0.0.1:8000/dashboard/timeslots)
