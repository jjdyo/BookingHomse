# BookingHomse

![BookingHomse Logo](public/images/BookingHomse169whiteBG.png)
![BookingHomse Demo](public/images/BookingHomseDemo.gif)

## Overview

BookingHomse is a comprehensive booking, reminder, and appointment management software designed specifically for equine facilities. Built with Laravel, Vue.js, and Inertia.js, it streamlines the process of scheduling riding sessions, managing horse availability, and coordinating group activities.

Whether you're running a riding school, boarding stable, or equestrian center, BookingHomse helps you keep your horses healthy, your schedule organized, and your riders informed.

## Features

### Current & Upcoming

- **🐴 Horse Cooldown Management** - Give your horses the rest they deserve. Automatically track and enforce cooldown periods after riding sessions to ensure horse welfare and prevent overwork.

- **📱 SMS & Email Reminders** - Never miss an appointment again. Automated notifications keep riders informed about upcoming bookings and any schedule changes.

- **👥 Group Ride Capabilities** - Book multiple horses for group outings with friends and family. Group bookings can be configured to require management approval for better control.

- **📊 Management Dashboard** - Comprehensive administrative interface to oversee all upcoming and requested appointments at a glance. Accept, decline, or reschedule bookings with ease.

- **⚙️ Flexible Approval System** - Configure auto-acceptance for routine bookings or require manual approval for complex scenarios like group rides or specific horses.

- **🔔 Smart Notifications** - Keep everyone in the loop with real-time updates on booking status, approvals, and schedule changes.

## Technology Stack

- **Backend**: Laravel
- **Frontend**: Vue.js with Inertia.js
- **Architecture**: Modern SPA experience with server-side routing

## Request Booking Calendar (Foundation)

A blank Vue FullCalendar has been added to the Request Booking page as a foundation for future features.

- Route: GET /request-booking (named: request-booking)
- Vue page: resources/js/pages/RequestBooking.vue
- Packages used: @fullcalendar/core, @fullcalendar/vue3, @fullcalendar/daygrid, @fullcalendar/timegrid, @fullcalendar/interaction

### How to run locally

1. Install PHP and JS dependencies:
   - composer install
   - npm install
2. Copy .env.example to .env and configure DB and APP_URL. Optionally set VITE_TZ for calendar timezone (e.g., VITE_TZ=America/New_York).
3. Run database migrations:
   - php artisan migrate
4. Start dev servers:
   - php artisan serve
   - npm run dev
5. Visit http://127.0.0.1:8000/request-booking to see the blank calendar.

Notes:
- The calendar currently renders without events. Backend endpoints and GCal sync will be added later.
