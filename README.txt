BOOKBAZAAR - LOCAL XAMPP SETUP
================================

WHAT THIS STARTER PROJECT INCLUDES
- Responsive home page with search/category filter
- User registration, login, logout
- User book listing form with optional image upload
- Image validation (JPG, PNG, WebP, max 3 MB)
- MySQL storage for users and book details
- Admin login and dashboard
- Admin approve/reject/delete book listings
- Password hashing, prepared SQL statements, CSRF form tokens

INSTALL ON WINDOWS WITH XAMPP
1. Extract the bookbazaar folder into:
   C:\xampp\htdocs\bookbazaar
   Ensure index.php is directly inside that folder, not inside a second nested bookbazaar folder.

2. Open XAMPP Control Panel. Start Apache and MySQL.

3. Open phpMyAdmin:
   http://localhost/phpmyadmin
   Click Import, select database.sql from the project folder, then click Import/Go.
   Alternatively, open the SQL tab and paste the contents of database.sql.

4. Create the first admin:
   Open http://localhost/bookbazaar/setup_admin.php
   Enter an admin name, email, and a strong password of at least 12 characters.
   Once the admin is created, DELETE:
   C:\xampp\htdocs\bookbazaar\setup_admin.php
   Do not leave this setup script online.

5. Open:
   Website:     http://localhost/bookbazaar/
   User login:  http://localhost/bookbazaar/login.php
   User signup: http://localhost/bookbazaar/register.php
   Admin login: http://localhost/bookbazaar/admin/login.php

6. Register a regular user, log in, then use Sell a Book to submit a book.
   Listings are pending until the admin approves them. Approved listings appear on the home page.

IMPORTANT NOTES
- Default XAMPP MySQL username is often root with an empty password. If you set a MySQL password, update config/database.php.
- Uploaded images are stored under uploads/books; their paths are stored in MySQL.
- This is a local learning starter, not a production-ready marketplace. It does not process payments, ship books, message sellers, or include full account recovery.
- Before deploying publicly, use HTTPS, secure session cookie settings, stronger server configuration, rate limiting, privacy/legal pages, backups, and a reviewed production admin provisioning process.
- If uploads fail, check PHP upload_max_filesize and post_max_size in php.ini.


BUYING / ORDER SYSTEM UPDATE
============================
If you already imported database.sql and created users/books, do NOT re-import the full database to avoid confusion.
1. Back up your database first in phpMyAdmin (Export).
2. Open phpMyAdmin -> click the bookbazaar database -> Import -> select orders_schema.sql -> Go.
   IMPORTANT: run orders_schema.sql only ONCE because it adds books.is_sold.
3. Replace/update the project files with this updated package. Keep your existing config/database.php credentials.
4. COD works without external accounts. Online payment uses Razorpay and requires API keys in config/payment.php.
5. For online payment, use Razorpay TEST keys first; never put secret keys in public repositories. Payment is marked paid only after server-side signature verification.
6. Test flow: buyer account -> open an approved book -> Buy this book -> fill checkout -> choose COD -> Place order.
7. Buyer order history: my-orders.php. Seller orders: my-sales.php. Admin order management: admin/orders.php.
8. Buyer and seller coordinate delivery themselves. The app stores the buyer's delivery address and contact details, so keep this local project private and do not expose it publicly without privacy/security review.
9. An order reserves the book by setting books.is_sold=1. Admin can cancel the order to make the book available again. Razorpay orders that remain unpaid may also need admin cancellation to release the listing.
