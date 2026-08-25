You are a senior Laravel backend engineer and software architect.

Build the production-ready backend API for a laundry management SaaS called "LaundryOS".

The backend must be designed to work with a separate frontend application.

The backend is the source of truth for:

- authentication
- authorization
- business ownership
- customers
- services
- orders
- order pricing
- payments
- order status
- tracking
- automation
- notifications
- reports
- inventory
- business settings

The backend must NOT depend on frontend business logic.

The frontend is only a client.

The backend must validate and calculate all important business operations independently.

==================================================

1. TECHNOLOGY
   \==================================================

Use:

- PHP 8.3+
- Laravel 12+
- Laravel Sanctum for API authentication
- MySQL 8+
- Laravel Queue
- Laravel Scheduler
- Laravel Notifications
- Laravel Events and Listeners
- Laravel Policies
- Laravel Form Requests
- Laravel API Resources
- Laravel Jobs
- Laravel Cache where useful
- PHPUnit or Pest for testing

Use Laravel conventions instead of inventing unnecessary architecture.

Do not over-engineer.

Use Laravel's built-in features wherever appropriate.

================================================== 2. API-FIRST ARCHITECTURE
==================================================

The backend must expose a clean REST API.

Architecture:

Frontend
↓
HTTP API
↓
Laravel Routes
↓
Controllers
↓
Form Requests
↓
Application / Domain Logic
↓
Models / Services
↓
Database

Automation:

Domain Event
↓
Event Listener
↓
Job
↓
Queue
↓
Worker
↓
Notification / Automation Action

Do not put complex business logic directly inside controllers.

================================================== 3. PROJECT STRUCTURE
==================================================

Use a clean Laravel structure.

Recommended:

app/
├── Console/
│ └── Commands/
│
├── Enums/
│
├── Events/
│
├── Exceptions/
│
├── Http/
│ ├── Controllers/
│ │ └── Api/
│ │ └── V1/
│ │
│ ├── Requests/
│ │ └── Api/
│ │ └── V1/
│ │
│ └── Resources/
│ └── Api/
│ └── V1/
│
├── Jobs/
│
├── Listeners/
│
├── Models/
│
├── Notifications/
│
├── Policies/
│
├── Services/
│ ├── Orders/
│ ├── Payments/
│ ├── Automation/
│ ├── Reports/
│ ├── Tracking/
│ └── Inventory/
│
└── Support/
├── Exceptions/
└── Helpers/

database/
├── factories/
├── migrations/
└── seeders/

routes/
├── api.php
└── console.php

Do not create unnecessary folders.

Keep classes focused.

================================================== 4. AUTHENTICATION
==================================================

There is exactly one authenticated role:

OWNER

There are no:

- staff
- employee
- manager
- cashier
- admin

Every registered user is an owner.

Use Laravel Sanctum.

Authentication endpoints:

POST /api/v1/auth/register

POST /api/v1/auth/login

POST /api/v1/auth/logout

GET /api/v1/auth/me

POST /api/v1/auth/forgot-password

POST /api/v1/auth/reset-password

================================================== 5. REGISTRATION FLOW
==================================================

Registration should create:

1. User
2. Business

The user becomes the owner of the business.

Conceptual:

User
↓
owns
↓
Business

Do not allow a user to access another business.

Use database transaction.

If business creation fails:

rollback user creation.

================================================== 6. USER MODEL
==================================================

User fields:

- id
- name
- email
- password
- email_verified_at
- remember_token
- created_at
- updated_at

Do not store plain-text passwords.

Use Laravel's password hashing.

================================================== 7. BUSINESS MODEL
==================================================

Business:

- id
- name
- phone
- address
- timezone
- logo_path
- opening_hours
- created_at
- updated_at

A user owns one business in the initial version.

Keep the architecture extensible for future multi-business support.

================================================== 8. BUSINESS OWNERSHIP
==================================================

Every business-owned record must have:

business_id

Examples:

Customer
Service
Order
InventoryItem
Expense
AutomationSetting
Notification

Never trust business_id from request payload.

Always derive it from authenticated user context.

Bad:

$request->business_id

Good:

auth()->user()->business

The backend must enforce ownership.

================================================== 9. AUTHORIZATION
==================================================

Use Laravel Policies.

Policies should protect:

- Business
- Customer
- Service
- Order
- InventoryItem
- Expense
- AutomationSetting

Example:

User A cannot access:

User B's orders.

Even if User A knows the order ID.

Authorization must happen server-side.

================================================== 10. API VERSIONING
==================================================

All API endpoints must use:

/api/v1/

Example:

/api/v1/orders

This makes future API versions possible.

================================================== 11. API RESPONSE FORMAT
==================================================

Use consistent JSON.

Success:

{
"success": true,
"data": {}
}

Collection:

{
"success": true,
"data": [],
"meta": {}
}

Error:

{
"success": false,
"error": {
"code": "ORDER_NOT_FOUND",
"message": "Order could not be found."
}
}

Validation:

{
"success": false,
"error": {
"code": "VALIDATION_ERROR",
"message": "The given data was invalid.",
"fields": {
"weight": [
"Weight must be greater than zero."
]
}
}
}

Keep error responses predictable.

================================================== 12. API RESOURCES
==================================================

Do not return Eloquent models directly from controllers.

Use API Resources.

Examples:

UserResource
BusinessResource
CustomerResource
ServiceResource
OrderResource
PaymentResource
InventoryResource
NotificationResource
ReportResource

Only expose fields that the API client actually needs.

================================================== 13. DATABASE ENTITIES
==================================================

Core entities:

users
businesses
customers
services
orders
order_items
payments
order_status_histories
inventory_items
expenses
automation_settings
notifications

Optional:

automation_logs

================================================== 14. CUSTOMERS TABLE
==================================================

customers:

- id
- business_id
- name
- phone
- notes
- created_at
- updated_at
- deleted_at

Use soft deletes where appropriate.

Do not physically delete customer history unnecessarily.

================================================== 15. SERVICES TABLE
==================================================

services:

- id
- business_id
- name
- pricing_type
- price
- estimated_duration_minutes
- is_active
- created_at
- updated_at

pricing_type initially:

PER_KG

Design it so future pricing types are possible.

================================================== 16. ORDERS TABLE
==================================================

orders:

- id
- business_id
- customer_id
- order_number
- status
- subtotal
- extras_total
- total
- payment_status
- estimated_completion_at
- ready_at
- completed_at
- tracking_token_hash
- created_at
- updated_at

Do not expose internal IDs unnecessarily.

================================================== 17. ORDER ITEMS TABLE
==================================================

order_items:

- id
- order_id
- service_id
- service_name_snapshot
- unit_price
- quantity
- unit
- subtotal
- created_at
- updated_at

IMPORTANT:

Store service_name_snapshot and unit_price.

Historical orders must not change when the service price changes later.

Example:

Today:

Wash + Iron
Rp10,000/kg

Tomorrow:

Wash + Iron
Rp12,000/kg

Old orders must remain:

Rp10,000/kg

================================================== 18. PAYMENTS TABLE
==================================================

payments:

- id
- order_id
- amount
- method
- status
- paid_at
- created_at
- updated_at

Payment methods:

CASH
QRIS
TRANSFER

Payment status:

PAID
UNPAID
PARTIAL

================================================== 19. ORDER STATUS HISTORY
==================================================

order_status_histories:

- id
- order_id
- from_status
- to_status
- changed_by
- created_at

changed_by may reference:

- owner user ID

System-generated events may use nullable changed_by.

================================================== 20. INVENTORY
==================================================

inventory_items:

- id
- business_id
- name
- unit
- quantity
- minimum_quantity
- created_at
- updated_at

Examples:

Detergent
8
L

Plastic
300
pcs

Hangers
120
pcs

================================================== 21. EXPENSES
==================================================

expenses:

- id
- business_id
- category
- description
- amount
- expense_date
- created_at
- updated_at

Keep expense management simple.

================================================== 22. AUTOMATION SETTINGS
==================================================

automation_settings:

- id
- business_id
- tracking_enabled
- ready_notification_enabled
- pickup_reminder_enabled
- unpaid_reminder_enabled
- daily_summary_enabled
- weekly_summary_enabled
- overdue_alert_enabled
- pickup_reminder_delay_hours
- created_at
- updated_at

Do not expose technical queue configuration to the owner.

================================================== 23. NOTIFICATIONS
==================================================

notifications:

- id
- business_id
- customer_id nullable
- order_id nullable
- type
- channel
- status
- payload
- scheduled_at nullable
- sent_at nullable
- failed_at nullable
- created_at
- updated_at

Statuses:

PENDING
SENT
FAILED
CANCELLED

Channels:

IN_APP
WHATSAPP
EMAIL

Only implement actual providers when configured.

================================================== 24. AUTOMATION LOGS
==================================================

Optional but recommended:

automation_logs:

- id
- business_id
- order_id nullable
- event
- action
- status
- metadata
- executed_at
- created_at

This provides traceability.

================================================== 25. ENUMS
==================================================

Use PHP Enums.

OrderStatus:

NEW
WASHING
IRONING
READY
COMPLETED

PaymentStatus:

PAID
UNPAID
PARTIAL

PaymentMethod:

CASH
QRIS
TRANSFER

PricingType:

PER_KG

NotificationStatus:

PENDING
SENT
FAILED
CANCELLED

NotificationChannel:

IN_APP
WHATSAPP
EMAIL

================================================== 26. ORDER STATE MACHINE
==================================================

Allowed transitions:

NEW → WASHING

WASHING → IRONING

IRONING → READY

READY → COMPLETED

Do not allow:

NEW → READY

NEW → COMPLETED

WASHING → COMPLETED

etc.

The backend must validate transitions.

Create a dedicated service:

OrderStatusService

Methods:

changeStatus()
canTransition()

Do not implement status transition logic directly inside controllers.

================================================== 27. ORDER CREATION
==================================================

Endpoint:

POST /api/v1/orders

Request:

customer_id

service_id

weight

extras

payment

The backend must:

1. authenticate owner
2. authorize customer
3. authorize service
4. validate service is active
5. validate weight
6. calculate subtotal
7. calculate extras
8. calculate total
9. generate order number
10. generate secure tracking token
11. calculate estimated completion
12. create order
13. create order item
14. create payment if applicable
15. create status history
16. dispatch automation event

All critical operations should happen inside a DB transaction.

================================================== 28. PRICE CALCULATION
==================================================

Never trust client-submitted totals.

Client may send:

weight = 4.5

Backend retrieves:

service price = 10000

Calculate:

45000

Then extras:

3000

Final:

48000

Backend stores:

subtotal = 45000
extras_total = 3000
total = 48000

The client-provided total must be ignored.

================================================== 29. PRICE CALCULATION SERVICE
==================================================

Create:

OrderPricingService

Responsibilities:

calculateItemSubtotal()
calculateExtrasTotal()
calculateOrderTotal()

Example:

calculateItemSubtotal(
Service $service,
    float $quantity
)

Return a money-safe value.

Avoid floating-point errors for financial calculations.

Prefer integer minor units or a reliable money strategy.

For Indonesian Rupiah:

Rp48,000

can be represented as:

48000

Do not use floating point for monetary persistence.

================================================== 30. ORDER NUMBER GENERATION
==================================================

Format:

LD-YYMMDD-XXX

Example:

LD-260826-018

Must be unique per business.

Do not rely only on timestamps.

Implement a safe generation strategy.

Potential approach:

Business-specific sequence.

Ensure concurrent order creation cannot generate duplicates.

================================================== 31. TRACKING TOKEN
==================================================

Generate a cryptographically secure random token.

Never use:

order ID

timestamp

incrementing number

as the public token.

Store a secure hash of the token if possible.

The raw token is only returned when creating the order.

================================================== 32. PUBLIC TRACKING
==================================================

Endpoint:

GET /api/v1/public/orders/{trackingToken}

No authentication.

Return only:

- business name
- business phone
- order number
- customer first name if appropriate
- status
- status history
- total
- estimated completion

Never return:

- customer phone
- internal IDs
- business analytics
- payment internals
- other orders

================================================== 33. PUBLIC TRACKING SECURITY
==================================================

The tracking token must be:

- long
- random
- unpredictable
- rate-limit protected

If token is invalid:

return a generic response.

Do not reveal:

"order exists but token is wrong"

or:

"this order belongs to another business"

Use:

"Order could not be found."

================================================== 34. ORDER STATUS UPDATE
==================================================

Endpoint:

PATCH /api/v1/orders/{order}/status

Request:

{
"status": "READY"
}

Backend:

1. authorize order
2. validate transition
3. update status
4. update timestamps
5. create status history
6. dispatch domain event
7. trigger automation

================================================== 35. STATUS TIMESTAMPS
==================================================

When:

NEW:

created_at

READY:

ready_at

COMPLETED:

completed_at

Do not overwrite original timestamps unnecessarily.

================================================== 36. EVENTS
==================================================

Create domain events:

OrderCreated
OrderStatusChanged
OrderReady
OrderCompleted
PaymentRecorded
InventoryLow

Events should represent business facts.

Do not put heavy processing directly inside events.

================================================== 37. LISTENERS
==================================================

Listeners may:

- create automation records
- update derived data
- dispatch jobs
- create notifications

Heavy tasks should be queued.

================================================== 38. QUEUES
==================================================

Use Laravel Queue.

Queue jobs for:

- WhatsApp notification
- email notification
- pickup reminder
- daily summary
- weekly summary
- automation processing

Do not make the HTTP request wait for external messaging services.

================================================== 39. JOBS
==================================================

Examples:

SendCustomerNotificationJob

ProcessPickupReminderJob

SendDailySummaryJob

SendWeeklySummaryJob

CheckOverdueOrdersJob

CheckLowInventoryJob

Jobs must be idempotent where possible.

================================================== 40. SCHEDULER
==================================================

Use Laravel Scheduler for recurring checks.

Examples:

Every hour:

CheckPickupReminders

Every hour:

CheckOverdueOrders

Every morning:

SendDailySummaries

Every Monday:

SendWeeklySummaries

Do not use browser timers.

================================================== 41. PICKUP REMINDER
==================================================

Rule:

If:

order.status == READY

AND:

ready_at <= now() - configured delay

AND:

order.status != COMPLETED

Then:

create/send reminder.

Before sending:

re-check order status.

If order is already COMPLETED:

cancel the reminder.

================================================== 42. OVERDUE DETECTION
==================================================

Rule:

estimated_completion_at < now()

AND:

status != COMPLETED

Then:

mark as overdue for business reporting.

Do not mutate order status to a fake "OVERDUE" state unless explicitly designed.

Overdue is a derived condition.

================================================== 43. UNPAID REMINDER
==================================================

If:

payment_status != PAID

AND:

configured reminder enabled

then create reminder according to business rules.

Never send duplicate reminders endlessly.

Store notification history.

================================================== 44. NOTIFICATION IDEMPOTENCY
==================================================

Prevent duplicate automated messages.

Example:

An order should not receive the same READY notification multiple times because a worker retried.

Use:

- unique notification keys
- database constraints
- idempotency checks

================================================== 45. FAILED JOBS
==================================================

Configure failed job handling.

A failed WhatsApp message must not corrupt:

- order
- payment
- status

External notification failure should be isolated from core order state.

================================================== 46. TRANSACTIONS
==================================================

Use DB transactions for:

- registration + business creation
- order creation
- payment recording
- status update + history
- important inventory mutations

Do not put external API calls inside long-running DB transactions.

================================================== 47. PAYMENTS
==================================================

Endpoint:

POST /api/v1/orders/{order}/payments

Request:

amount
method

Backend recalculates:

amount due
payment status
remaining balance
change if cash

Do not trust frontend calculations.

================================================== 48. PAYMENT RULES
==================================================

Example:

Total:
48000

Paid:
50000

Cash:

Change:
2000

Payment status:

PAID

Example:

Total:
48000

Paid:
20000

Payment status:

PARTIAL

Remaining:

28000

Example:

Total:
48000

Paid:
0

Payment status:

UNPAID

================================================== 49. PAYMENT INTEGRITY
==================================================

Prevent:

- negative payment
- payment above total unless explicitly supported
- duplicate payment records
- modifying finalized payments without audit

Use server-side validation.

================================================== 50. CUSTOMER API
==================================================

GET /api/v1/customers

GET /api/v1/customers/{customer}

POST /api/v1/customers

PATCH /api/v1/customers/{customer}

DELETE /api/v1/customers/{customer}

Support:

- search
- pagination
- sorting

Search:

name
phone

================================================== 51. CUSTOMER STATISTICS
==================================================

Customer detail may return:

- total orders
- total spending
- last order
- most common service
- average order value

These are derived metrics.

Do not store them redundantly unless there is a measured performance reason.

================================================== 52. CUSTOMER REPEAT ORDER
==================================================

Endpoint may expose:

GET /api/v1/customers/{customer}/summary

Return:

last service
average weight
last extras
order count

Frontend can use this to provide:

"Use Previous Order"

================================================== 53. SERVICE API
==================================================

GET /api/v1/services

POST /api/v1/services

GET /api/v1/services/{service}

PATCH /api/v1/services/{service}

DELETE /api/v1/services/{service}

Only active services can be used for new orders.

Deleting a service should not destroy historical order item snapshots.

Prefer deactivation where appropriate.

================================================== 54. ORDER API
==================================================

GET /api/v1/orders

GET /api/v1/orders/{order}

POST /api/v1/orders

PATCH /api/v1/orders/{order}

PATCH /api/v1/orders/{order}/status

GET /api/v1/orders/{order}/timeline

GET /api/v1/orders/{order}/payments

================================================== 55. ORDER FILTERS
==================================================

Support:

status

payment_status

date_from

date_to

search

Search:

order_number

customer_name

customer_phone

Pagination is required.

Do not load thousands of orders into memory.

================================================== 56. DASHBOARD API
==================================================

Endpoint:

GET /api/v1/dashboard

Return:

attention

today_summary

recent_orders

automation_health

Example:

{
"attention": {
"overdue": 2,
"ready_for_pickup": 4,
"unpaid": 3
},
"today": {
"orders": 34,
"revenue": 1240000,
"processing": 12,
"ready": 10
}
}

Use efficient queries.

================================================== 57. REPORTS API
==================================================

GET /api/v1/reports/summary

GET /api/v1/reports/revenue

GET /api/v1/reports/services

GET /api/v1/reports/orders

Support date ranges.

Examples:

today

this_week

this_month

custom

================================================== 58. REPORT DEFINITIONS
==================================================

Revenue must be clearly defined.

Do not mix:

gross order value

and

actual collected payment

without labeling them.

Return explicit fields:

order_value

collected_payment

outstanding_payment

================================================== 59. INVENTORY API
==================================================

GET /api/v1/inventory

POST /api/v1/inventory

PATCH /api/v1/inventory/{item}

DELETE /api/v1/inventory/{item}

POST /api/v1/inventory/{item}/adjust

Adjustment:

quantity_delta

reason

================================================== 60. LOW STOCK
==================================================

An item is low stock when:

quantity <= minimum_quantity

Do not automatically alter stock.

Only alert the owner.

================================================== 61. AUTOMATION API
==================================================

GET /api/v1/automation/settings

PATCH /api/v1/automation/settings

GET /api/v1/automation/logs

The owner should only see business-friendly settings.

Do not expose queue internals.

================================================== 62. NOTIFICATION API
==================================================

GET /api/v1/notifications

PATCH /api/v1/notifications/{notification}/read

POST /api/v1/notifications/{notification}/retry

Only allow retry when appropriate.

================================================== 63. WHATSAPP INTEGRATION
==================================================

Design an abstraction:

NotificationChannel

Implement:

InAppNotificationChannel

EmailNotificationChannel

WhatsAppNotificationChannel

WhatsApp implementation should depend on a provider abstraction.

Do not hardcode a specific provider deeply into business logic.

Example:

NotificationService
↓
ChannelResolver
↓
WhatsAppChannel
↓
Provider

If no provider is configured:

return a controlled failure.

Never pretend that a message was sent.

================================================== 64. NOTIFICATION TEMPLATES
==================================================

Templates:

ORDER_CREATED

ORDER_READY

PICKUP_REMINDER

UNPAID_REMINDER

DAILY_SUMMARY

WEEKLY_SUMMARY

Templates should support business information and order variables.

Do not hardcode message strings throughout Jobs.

================================================== 65. SETTINGS
==================================================

Business settings endpoints:

GET /api/v1/business

PATCH /api/v1/business

Account:

GET /api/v1/account

PATCH /api/v1/account

Services:

/services

Automation:

/automation/settings

================================================== 66. BUSINESS TIMEZONE
==================================================

Every business must have a timezone.

Default:

Asia/Jakarta

Do not hardcode timezone logic everywhere.

Use business timezone for:

- reminders
- reports
- daily summaries
- opening hours
- date filtering

Store timestamps consistently.

================================================== 67. DATE FILTERING
==================================================

When owner asks:

"today"

calculate today according to:

business timezone

not server timezone.

Example:

Business timezone:

Asia/Jakarta

Server timezone:

UTC

Reports must still represent the business day correctly.

================================================== 68. VALIDATION
==================================================

Use Form Request classes.

Examples:

RegisterRequest
LoginRequest
CreateCustomerRequest
UpdateCustomerRequest
CreateServiceRequest
UpdateServiceRequest
CreateOrderRequest
UpdateOrderRequest
ChangeOrderStatusRequest
CreatePaymentRequest
UpdateAutomationSettingsRequest

Do not put large validation arrays inside controllers.

================================================== 69. BUSINESS RULE VALIDATION
==================================================

Form Requests validate input shape.

Domain/Application Services validate business rules.

Example:

Form Request:

weight must be numeric and > 0.

Order service:

service must belong to current business.

Order service:

service must be active.

Order service:

order cannot transition from READY to WASHING.

Keep these responsibilities separate.

================================================== 70. QUERY OPTIMIZATION
==================================================

Avoid N+1 queries.

Use:

with()
withCount()
withSum()
select()
pagination

Example:

Orders list should eager-load:

customer
orderItems
payment

Do not load unnecessary relationships.

================================================== 71. DATABASE INDEXES
==================================================

Add appropriate indexes.

Examples:

business_id

customer_id

service_id

order_number

status

payment_status

estimated_completion_at

ready_at

created_at

tracking token hash

Composite indexes where justified.

================================================== 72. UNIQUE CONSTRAINTS
==================================================

Examples:

business + order_number

business + service name where appropriate

tracking token hash

Do not rely only on application-level uniqueness checks.

Database constraints must protect integrity.

================================================== 73. SOFT DELETES
==================================================

Consider soft deletes for:

Customer
Service
InventoryItem

Do not soft-delete orders casually.

Orders are business records.

================================================== 74. DATABASE MIGRATIONS
==================================================

Every schema change must use migrations.

Never manually modify production schema.

Migrations must:

- have correct foreign keys
- have indexes
- have nullable rules
- have cascading behavior intentionally defined

================================================== 75. FACTORIES
==================================================

Create factories for:

User
Business
Customer
Service
Order
OrderItem
Payment
InventoryItem

Factories should produce realistic test data.

================================================== 76. SEEDERS
==================================================

Create a development seeder.

Example:

Demo business

Demo owner

Several services

Several customers

Several orders

Inventory

Automation settings

Do not use random meaningless data.

================================================== 77. TEST SUITE
==================================================

Create tests for:

Authentication

Authorization

Customer CRUD

Service CRUD

Order creation

Price calculation

Payment calculation

Order status transitions

Tracking

Automation

Reports

Inventory

================================================== 78. AUTH TEST
==================================================

Test:

Owner A logs in.

Owner A can access own business.

Owner A cannot access Owner B's data.

================================================== 79. ORDER TEST
==================================================

Given:

Service:

Wash + Iron

Price:

10000

Weight:

4.5

Extra:

3000

Expected total:

48000

Assert backend calculation.

================================================== 80. STATUS TEST
==================================================

Test valid:

NEW → WASHING

WASHING → IRONING

IRONING → READY

READY → COMPLETED

Test invalid transitions.

They must return validation/business error.

================================================== 81. TRACKING TEST
==================================================

Create order.

Retrieve public tracking using valid token.

Assert safe fields exist.

Assert private fields do not exist.

Try invalid token.

Assert generic 404-style response.

================================================== 82. AUTOMATION TEST
==================================================

When order becomes READY:

assert:

OrderReady event dispatched.

Automation job scheduled.

Notification created when enabled.

================================================== 83. REMINDER TEST
==================================================

READY order older than configured threshold:

reminder should be created.

COMPLETED order:

reminder must NOT be sent.

================================================== 84. PAYMENT TEST
==================================================

Total:

48000

Payment:

50000

Method:

CASH

Expected:

paid

change:

2000

Also test:

20000 payment:

PARTIAL

0 payment:

UNPAID

================================================== 85. API RATE LIMITING
==================================================

Protect:

login

registration

public tracking

password reset

notification endpoints

Use Laravel rate limiting.

Public tracking especially needs protection against brute-force attempts.

================================================== 86. MASS ASSIGNMENT
==================================================

Use guarded/fillable carefully.

Never allow:

business_id

owner ID

payment status

total

or security-sensitive fields

to be freely mass-assigned from user input.

================================================== 87. FINANCIAL SECURITY
==================================================

Never trust:

subtotal

total

payment status

change

from frontend.

Recalculate server-side.

The backend is authoritative.

================================================== 88. LOGGING
==================================================

Log important failures.

Do not log:

- passwords
- authentication tokens
- raw tracking tokens
- sensitive personal information unnecessarily

Use structured logs where possible.

================================================== 89. EXCEPTION HANDLING
==================================================

Create domain-friendly exceptions when useful.

Examples:

OrderStatusTransitionException

UnauthorizedBusinessAccessException

InvalidPaymentException

OrderNotFoundException

Map them to predictable API responses.

Do not expose stack traces in production.

================================================== 90. OBSERVABILITY
==================================================

Important automation jobs should be traceable.

For failures:

record:

- job
- business
- order
- event
- failure reason

Do not let silent failures exist.

================================================== 91. API DOCUMENTATION
==================================================

Document the API.

Prefer:

OpenAPI / Swagger

or another machine-readable API documentation format.

Document:

- authentication
- endpoints
- request fields
- response formats
- errors
- pagination
- filters

================================================== 92. PAGINATION
==================================================

Collections must use pagination.

Examples:

orders

customers

notifications

inventory

automation logs

Do not return unbounded datasets.

================================================== 93. SORTING
==================================================

Allow safe sorting only by whitelisted fields.

Never blindly accept arbitrary SQL column names from query parameters.

================================================== 94. FILTERING
==================================================

Filters must use whitelisted values.

Do not dynamically construct unsafe queries.

Use Laravel query builder/Eloquent safely.

================================================== 95. API RESOURCE EXPANSION
==================================================

Avoid returning massive nested payloads.

For example:

OrderResource should not always include:

all customer orders

all notifications

all automation logs

Only include related data when appropriate.

================================================== 96. CACHING
==================================================

Cache carefully.

Good candidates:

active services

business settings

dashboard metrics when appropriate

Do not cache financial state carelessly.

Invalidate cache when underlying data changes.

================================================== 97. CONCURRENCY
==================================================

Handle concurrent order creation.

Two requests must not create the same:

order number

tracking token

payment record

Use:

database constraints

transactions

locks where necessary

================================================== 98. IDEMPOTENCY
==================================================

Important mutation endpoints should support idempotency where appropriate.

Especially:

payment creation

order creation

external notification processing

Do not create duplicate business records because of request retries.

================================================== 99. QUEUE IDEMPOTENCY
==================================================

A job may execute more than once.

Jobs must check current state before performing irreversible actions.

Example:

SendPickupReminderJob:

if order.status !== READY:

return

Then check if equivalent notification was already sent.

================================================== 100. API SECURITY
==================================================

Follow:

- authentication
- authorization
- validation
- rate limiting
- CSRF considerations depending on client architecture
- secure cookies/tokens
- database constraints
- safe error responses

Never assume frontend restrictions are enough.

================================================== 101. CORS
==================================================

Configure CORS for the actual frontend origin.

Do not use:

allow_origins = *

in production unless there is a deliberate reason.

================================================== 102. ENVIRONMENT
==================================================

Use:

.env

for local configuration.

Provide:

.env.example

Include:

APP_URL

DB_*

SANCTUM configuration

QUEUE_CONNECTION

CACHE_STORE

MAIL_*

WHATSAPP provider configuration

Do not commit secrets.

================================================== 103. QUEUE DRIVER
==================================================

Development may use:

database

Production can use:

Redis

The code must not depend directly on a specific queue driver.

================================================== 104. CACHE
==================================================

Use Laravel's cache abstraction.

Do not directly couple business logic to Redis.

================================================== 105. STORAGE
==================================================

Business logo:

use Laravel filesystem abstraction.

Do not store absolute server paths in database.

Store a file reference/path.

================================================== 106. FILE VALIDATION
==================================================

For business logo:

validate:

- image
- MIME
- size
- dimensions where appropriate

Never trust file extensions.

================================================== 107. CRON
==================================================

Production scheduler should run Laravel scheduler continuously using the proper server configuration.

Do not depend on frontend execution.

================================================== 108. API ROUTE GROUP
==================================================

Conceptually:

Route::prefix('v1')->group(function () {

    Route::prefix('auth')->group(...);

    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/dashboard', ...);

        Route::apiResource('customers', ...);

        Route::apiResource('services', ...);

        Route::apiResource('orders', ...);

        Route::patch(
            '/orders/{order}/status',
            ...
        );

        Route::apiResource('inventory', ...);

        Route::get('/reports/summary', ...);

        Route::get(
            '/automation/settings',
            ...
        );

    });

    Route::get(
        '/public/orders/{trackingToken}',
        ...
    );

});

Adapt route definitions to Laravel conventions.

================================================== 109. CONTROLLER RULE
==================================================

Controllers should be thin.

Example:

public function store(CreateOrderRequest $request)
{
    $order = $this->orderService->create(
        $request->validated(),
$request->user()
);

    return new OrderResource($order);

}

Do not put:

- 100 lines of calculations
- database transactions
- notification logic
- automation rules

inside controllers.

================================================== 110. SERVICE RULE
==================================================

Services should contain meaningful application/domain operations.

Examples:

OrderService
OrderPricingService
OrderStatusService
PaymentService
TrackingService
AutomationService
ReportService

Do not create services that merely wrap one Eloquent call without adding value.

================================================== 111. MODEL RULE
==================================================

Models should define:

- relationships
- casts
- scopes
- simple domain behavior

Do not turn models into enormous god objects.

================================================== 112. SCOPES
==================================================

Useful scopes:

Order::active()

Order::ready()

Order::unpaid()

Order::overdue()

Customer::forBusiness()

Service::active()

Keep scopes readable.

================================================== 113. BUSINESS CONTEXT
==================================================

Avoid passing business_id around manually everywhere.

Create a reliable way to resolve:

$currentBusiness

from authenticated owner.

For example:

$user->business

Then services receive the Business model where appropriate.

================================================== 114. MULTI-TENANCY
==================================================

LaundryOS is logically multi-tenant.

Tenant boundary:

Business.

Every business-owned query must be scoped to the authenticated business.

Do not depend only on frontend filtering.

Authorization and query scoping must both protect tenant isolation.

================================================== 115. QUERY SECURITY
==================================================

Never allow:

GET /orders?business_id=another-business

to bypass tenant boundaries.

Ignore client-supplied business ownership fields.

================================================== 116. ORDER RESOURCE
==================================================

OrderResource should expose:

id only if necessary

order_number

customer

items

status

subtotal

extras_total

total

payment_status

estimated_completion_at

ready_at

completed_at

created_at

tracking_url only when appropriate/authenticated

Do not expose tracking token itself in normal order responses unless intentionally required.

================================================== 117. TRACKING URL
==================================================

When creating an order:

return a full tracking URL to the owner.

Example:

https://app.example.com/track/LD-260826-018/secure-token

The backend should generate the URL using configured application/frontend URL.

Do not hardcode domains.

================================================== 118. ORDER CREATION RESPONSE
==================================================

Response:

{
"success": true,
"data": {
"order": {},
"tracking_url": "..."
}
}

The raw tracking URL may only be returned at creation or explicitly regenerated under secure business rules.

================================================== 119. TRACKING TOKEN STORAGE
==================================================

Prefer:

tracking_token_hash

rather than storing raw token.

To retrieve:

hash provided token

lookup by hash

This reduces damage if the database is leaked.

================================================== 120. TRACKING TOKEN ROTATION
==================================================

Consider supporting token rotation.

If owner requests a new tracking link:

invalidate previous token.

Only implement this if required by UX.

================================================== 121. PUBLIC TRACKING RATE LIMIT
==================================================

Apply aggressive but reasonable rate limits to:

/public/orders/*

Do not allow unlimited token guessing.

================================================== 122. REPORT QUERIES
==================================================

Reports should be generated using database aggregation.

Use:

SUM

COUNT

GROUP BY

date ranges

Do not retrieve every order into PHP memory just to calculate totals.

================================================== 123. MONEY
==================================================

Use integer values for IDR.

Example:

48000

not:

48000.00

When API response needs currency formatting, frontend can format it.

Backend should return numeric amounts consistently.

================================================== 124. WEIGHT
==================================================

Weight can support decimals.

Example:

4.5 kg

Use appropriate database precision.

Do not use floating-point calculations carelessly.

Use decimal database columns and consistent casting/handling.

================================================== 125. ORDER EXTRAS
==================================================

For initial implementation, extras can be represented as structured JSON if the business requirements remain simple.

Example:

[
{
"name": "Premium fragrance",
"amount": 3000
}
]

However:

final persisted totals must still be calculated and validated server-side.

================================================== 126. HISTORICAL DATA
==================================================

Historical order data must remain stable.

If a service changes:

Old order:

price remains unchanged.

If customer changes name:

Historical order can still reference the customer record, but order snapshot data may be introduced later if legal/business requirements demand it.

Do not destroy historical financial records.

================================================== 127. REPORT DATE LOGIC
==================================================

Define:

Today

Week

Month

according to business timezone.

Do not use:

now()->startOfDay()

blindly if application/server timezone differs from business timezone.

================================================== 128. DAILY SUMMARY JOB
==================================================

Daily summary job:

For each business with daily summary enabled:

1. resolve business timezone
2. calculate previous/current business day summary according to configured rule
3. generate summary
4. send configured notification
5. record automation log

Do not send duplicates.

================================================== 129. WEEKLY SUMMARY JOB
==================================================

For each business:

calculate:

- orders
- revenue
- order value
- outstanding payment
- top service
- new customers
- overdue orders

Compare with previous period where practical.

================================================== 130. AUTOMATION SETTINGS DEFAULTS
==================================================

When business is created:

Enable:

tracking
ready notification
pickup reminder
overdue alert
daily summary

External messaging should only become active when provider configuration exists.

================================================== 131. AUTOMATION HEALTH
==================================================

Dashboard automation health should calculate:

Tracking:
enabled/working

Reminders:
enabled/working

Daily summary:
enabled/working

WhatsApp:
connected/not connected

Do not simply return true because a toggle is on.

================================================== 132. WHATSAPP PROVIDER
==================================================

Create an interface such as:

WhatsAppProvider

Methods conceptually:

sendMessage()

Provider implementation should be swappable.

Example future providers:

Meta WhatsApp Cloud API

Twilio

Other provider

Do not hardcode business logic to one provider.

================================================== 133. EMAIL
==================================================

Use Laravel Notifications/Mail abstraction.

Do not send email directly from controllers.

================================================== 134. IN-APP NOTIFICATIONS
==================================================

Owner dashboard notifications should be stored in the database.

Use Laravel's notification system where appropriate.

================================================== 135. NOTIFICATION DUPLICATION
==================================================

Define a deterministic automation key.

Example:

order:{order_id}:ready_notification

Before sending:

check whether this automation action already completed.

================================================== 136. AUTOMATION LOGGING
==================================================

Log:

event

action

status

order

business

metadata

timestamp

Possible status:

SUCCESS

FAILED

SKIPPED

================================================== 137. SKIPPED AUTOMATION
==================================================

Example:

Order READY

Pickup reminder triggered

But order is now COMPLETED.

Automation log:

SKIPPED

reason:

ORDER_ALREADY_COMPLETED

This is useful for debugging.

================================================== 138. INVENTORY ALERT
==================================================

When inventory quantity falls below threshold:

create an owner notification.

Do not spam.

One active low-stock alert per inventory item until stock is replenished.

================================================== 139. INVENTORY ADJUSTMENT
==================================================

Every adjustment should record:

quantity before

quantity after

delta

reason

For a more complete future version, add:

inventory_movements

Do not overbuild the first version if not required.

================================================== 140. API FILTERING
==================================================

Orders:

GET /orders?status=READY

GET /orders?payment_status=UNPAID

GET /orders?search=Andi

GET /orders?date_from=2026-08-01

GET /orders?date_to=2026-08-26

Whitelist filters.

================================================== 141. PAGINATION RESPONSE
==================================================

Use Laravel pagination metadata.

Example:

{
"success": true,
"data": [],
"meta": {
"current_page": 1,
"last_page": 5,
"per_page": 20,
"total": 98
}
}

================================================== 142. API DOCUMENTATION EXAMPLES
==================================================

Document request and response examples.

Especially:

POST /orders

PATCH /orders/{order}/status

POST /orders/{order}/payments

GET /public/orders/{trackingToken}

================================================== 143. DEVELOPMENT EXPERIENCE
==================================================

Provide clear commands:

composer install

php artisan migrate

php artisan db:seed

php artisan serve

php artisan queue:work

php artisan schedule:work

Tests:

php artisan test

================================================== 144. CODE STYLE
==================================================

Follow Laravel/PHP standards.

Use:

PSR-12

Laravel conventions

Strict typing where practical.

Prefer:

declare(strict_types=1);

in appropriate PHP files.

Use meaningful names.

================================================== 145. NO GOD CLASSES
==================================================

Avoid:

LaundryService.php

with 2000 lines.

Avoid:

OrderController.php

containing every order-related operation.

Separate responsibilities.

================================================== 146. NO REPOSITORY OVER-ENGINEERING
==================================================

Do not create:

OrderRepositoryInterface
OrderRepository
OrderRepositoryFactory
OrderRepositoryManager

unless there is an actual architectural reason.

Eloquent is already a useful data access abstraction.

Keep the architecture practical.

================================================== 147. NO GENERIC SERVICE GARBAGE
==================================================

Avoid classes such as:

DataService
HelperService
CommonService
UtilityService

Put logic where it belongs.

================================================== 148. DATABASE TRANSACTIONS
==================================================

Example order creation:

DB::transaction(function () {

    validate customer

    validate service

    calculate pricing

    create order

    create order item

    create payment

    create status history

});

Then dispatch asynchronous events/jobs after the transaction is safely committed when appropriate.

================================================== 149. AFTER COMMIT EVENTS
==================================================

Automation events should not process against partially committed data.

Use Laravel's after-commit capabilities where appropriate.

Critical order data must exist before automation runs.

================================================== 150. SECURITY REVIEW
==================================================

Before completion, verify:

- authentication
- authorization
- tenant isolation
- SQL injection protection
- mass assignment protection
- rate limiting
- secure token generation
- password security
- secret management
- CORS
- file upload validation
- error exposure
- logging hygiene

================================================== 151. PERFORMANCE REVIEW
==================================================

Verify:

- no N+1 queries
- indexes
- pagination
- aggregation queries
- queueing external calls
- caching only where useful
- no giant payloads

================================================== 152. API CONTRACT
==================================================

The frontend should be able to consume the API without knowing:

- Eloquent
- database structure
- Laravel internals
- automation internals

The API contract must be stable and explicit.

================================================== 153. FRONTEND COMPATIBILITY
==================================================

The backend should support the LaundryOS frontend defined in the product specification.

The frontend needs:

Authentication

Dashboard

Customers

Orders

Order creation

Payments

Public tracking

Reports

Inventory

Automation settings

Notifications

================================================== 154. IMPLEMENTATION ORDER
==================================================

PHASE 1

Laravel project

Database

Authentication

Sanctum

Business ownership

API structure

Error format

PHASE 2

Customers

Services

Orders

Pricing

Payments

Status history

PHASE 3

Public tracking

Tracking token

Tracking API

PHASE 4

Events

Listeners

Queues

Automation

Notifications

Reminders

PHASE 5

Dashboard

Reports

Inventory

PHASE 6

API documentation

Tests

Security review

Performance review

Code cleanup

================================================== 155. MVP PRIORITY
==================================================

P0:

Authentication
Business
Customers
Services
Orders
Pricing
Payments
Order status
Tracking

P1:

Automation
Queues
Reminders
Notifications
Dashboard

P2:

Reports
Inventory
Daily summary
Weekly summary

P3:

WhatsApp provider
Advanced analytics
Advanced inventory

================================================== 156. DEFINITION OF DONE
==================================================

The backend is complete only when:

Authentication works.

Owner isolation works.

Customer CRUD works.

Service CRUD works.

Order creation works.

Server-side pricing works.

Payment calculations work.

Order status transitions are enforced.

Tracking works without login.

Tracking token is secure.

Automation events work.

Queues work.

Scheduled reminders work.

Notifications do not duplicate.

Reports return correct data.

Inventory works.

API responses are consistent.

Validation is implemented.

Authorization is implemented.

Tests cover critical flows.

Database indexes exist.

No major N+1 queries exist.

No secrets are committed.

No fake integrations exist.

Code is maintainable.

================================================== 157. FINAL ENGINEERING PRINCIPLE
==================================================

Build this backend as a real SaaS backend.

Do not build a collection of CRUD endpoints.

The backend must understand the business.

The frontend should ask:

"Create this order."

The backend should determine:

- whether the owner is authorized
- whether the customer belongs to the business
- whether the service belongs to the business
- whether the service is active
- what the correct price is
- what the correct payment state is
- what the next valid status is
- what automation should happen
- what notifications should be scheduled
- what data can be publicly exposed

The backend is the source of truth.

================================================== 158. FINAL ARCHITECTURAL PRINCIPLE
==================================================

Keep the system simple enough to maintain.

Use Laravel conventions first.

Use domain/application services where business logic becomes meaningful.

Use events for business facts.

Use jobs for asynchronous work.

Use scheduler for recurring work.

Use policies for authorization.

Use Form Requests for input validation.

Use API Resources for response contracts.

Use database transactions for atomic business operations.

Use database constraints for integrity.

Use queues for external/slow operations.

Use tests for critical business rules.

Do not over-engineer.

================================================== 159. FINAL PRODUCT PRINCIPLE
==================================================

LaundryOS exists to reduce the owner's workload.

The backend should automate repetitive work without taking control away from the owner.

Automate:

- calculations
- order numbers
- tracking
- reminders
- reports
- alerts
- summaries

Require owner action for:

- physical laundry status
- final completion
- important financial operations
- destructive actions

The system should be reliable, transparent, secure, and easy to extend.
