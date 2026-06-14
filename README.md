# SGIPC Programming Club Portfolio Website

SGIPC (Special Group Interested in Programming Contest) is a programming contest enthusiastic club portfolio website.

This application is built using **PHP (with PDO)**, **HTML5**, **CSS3**, and **JavaScript**. It utilizes a database to handle membership applications, designations (such as President, GS, Treasurer), member resources, and admin approvals.

---

## Features

1. **Public Portfolio (`index.php`)**:
   - Modern glassmorphic dark programming-theme UI.
   - Interactive typing terminal highlighting competitive programming topics.
   - Live counters displaying club metrics.
   - Dynamic **Administration** grid showcasing official designated leaders (President, GS, Treasurer, etc.) with direct links to their Codeforces profiles.
2. **Registration Handling (`register.php`)**:
   - Interactive form collecting personal details (Name, Email, Student ID, Dept, Batch) and competitive programming attributes (Codeforces/Vjudge handles, experience, and statement of interest).
   - Form-level JavaScript checks and backend security validations (password matching, minimum length, email uniqueness).
3. **Admin Control Panel (`admin.php`)**:
   - Secure admin authentication.
   - Statistics board showing applicant counts, pending list, and approved members.
   - **Review Table**: Easily Approve or Reject candidates with a single click.
   - **Role Assignment**: Dynamic dropdown to assign approved members to official roles (e.g. *President*, *General Secretary*, *Treasurer*, *Trainer*, *Web Master*, etc.). The designated members instantly appear in the public frontpage!
   - **Resource Publisher**: Share training material links, sheets, or announcements with approved members.
4. **Member Dashboard (`member.php`)**:
   - Distinct views based on status:
     - **Pending**: Tells the applicant their request is under review.
     - **Approved**: Generates an interactive SGIPC Member ID Badge, lists the resource links shared by administrators, and allows updating Vjudge/Codeforces handles.
     - **Rejected**: Informs the user of the decision.
5. **Unified Authentication (`login.php` & `logout.php`)**:
   - Single portal logging in both members and admins, routing them automatically to their respective dashboards.

---

## Getting Started / How to Run

### Prerequisite
You need PHP and a server environment (like XAMPP, LAMPP, WampServer, or PHP CLI).

### Quick Start (PHP Local Server)
1. Start the built-in PHP development server:
   ```bash
   php -S localhost:8000
   ```
2. Open your web browser and navigate to:
   [http://localhost:8000](http://localhost:8000)
