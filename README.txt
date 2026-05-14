RAILWAY MANAGEMENT SYSTEM + TRAIN TICKET BOOKING SYSTEM

This is the redesigned version with a unique arrangement.

PHP/Web:
Railway Management System

C# Windows Forms:
Train Ticket Booking System

Database:
SQLite

Connection:
C# connects to PHP through api.php.

Design change:
- PHP is now a station operations board with sidebar, stat board, route cards, and passenger manifest.
- C# is now a ticket counter console layout with sidebar, top board, route board, ticket window, and manifest monitor.
- It does not use the same old two-column form/table arrangement.
- No custom drawing code.
- No FillEllipse.
- No C# background image.
- No designer dependency.
- .NET Framework 4.8.

Run PHP:
cd php-api
php -S localhost:8000

If php not found:
/c/xampp/php/php.exe -S localhost:8000

Open:
http://localhost:8000/index.php

Run C#:
Open csharp\TrainTicketBookingApp.sln in Visual Studio.
Build > Rebuild Solution.
Press F5.
