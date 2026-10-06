# Student Pickup System Documentation

## Overview
The Student Pickup System is a web-based application designed to streamline the student dismissal process. It utilizes RFID cards tapped by parents/guardians to notify the school and display the names of the students ready for pickup.

## How It Works
The system follows a simple workflow when a parent taps their RFID card:

1. **Card Tapping (Frontend - `index.php`)**:
   - The main interface (`index.php`) features a hidden input field that listens for RFID scanner input.
   - When a parent taps their RFID card, the scanner inputs the card's ID and automatically triggers an "Enter" keypress.

2. **Data Submission (`fungsi.js`)**:
   - A JavaScript event listener in `fungsi.js` captures the "Enter" keypress.
   - It sends the RFID data asynchronously (via AJAX) to the backend saving script (`simpan.php`).
   - The input field is immediately cleared, ready for the next tap.

3. **Validation and Notification (`simpan.php`)**:
   - The backend receives the RFID ID and checks it against the `parents_card` table in the database.
   - If a match is found, it identifies the corresponding student's `rfidid`.
   - The system then updates the `student` table, setting the `tiime` column to the current timestamp to mark that the parent has arrived.
   - Additionally, it retrieves the phone numbers for the student and their homeroom teacher, and sends an automated WhatsApp notification via an external API to inform them that the parent is waiting at the front gate.

4. **Real-time Display (`data.php` & `fungsi.js`)**:
   - The frontend continuously polls the server every second for updates.
   - `data.php` queries the `student` table for students whose `tiime` was updated within the last 12 minutes (720 seconds) and who haven't been marked as dismissed.
   - `fungsi.js` receives this list and dynamically populates a scrolling table on `index.php` with the students' names and grades.
   - If no students are currently waiting for pickup, the system automatically hides the table and displays a placeholder YouTube video (configured in the `video` database table).

## Database Structure (Database: `pickup`)
Based on the system's queries, it relies on the following key tables:

*   **`parents_card`**: Links a parent's RFID card to a student.
    *   `rfidid_parents`: The ID from the parent's RFID card.
    *   `rfidid`: The corresponding student's ID.
*   **`student`**: Stores student details and pickup status.
    *   `rfidid`: The student's unique ID.
    *   `student_name`: Name of the student.
    *   `grade`: The student's grade/class.
    *   `phone`: The student's phone number (if applicable).
    *   `tiime`: Timestamp of when the parent's card was tapped.
    *   `status`: Current pickup status.
*   **`homeroom`**: Stores homeroom teacher contact info.
    *   `grade`: The grade/class.
    *   `phone`: The homeroom teacher's phone number for notifications.
*   **`video`**: Stores the link for the idle screen video.
    *   `id`: Video ID (currently fetches ID = 1).
    *   `link`: The YouTube embed link.

## File Structure Reference
*   `index.php`: The main UI dashboard displaying the scrolling list of students and the video player.
*   `connect.php`: Handles the MySQL database connection credentials.
*   `fungsi.js`: Contains all frontend JavaScript logic, AJAX requests, and the auto-scrolling animation for the table.
*   `simpan.php`: Backend script that processes the RFID tap, updates the database, and sends WhatsApp messages.
*   `data.php`: API endpoint that provides a JSON list of students recently marked for pickup.
*   `checkid.php`: A utility endpoint to check if a specific RFID exists in the `parents_card` table and returns JSON.
*   `qr.php`: Provides a simple interface displaying a QR code.
